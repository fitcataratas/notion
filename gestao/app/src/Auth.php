<?php
declare(strict_types=1);

/** Sessão, login, bloqueio de tentativas e senhas. */
final class Auth
{
    private static ?array $usuario = null;
    private static bool $carregado = false;

    public static function iniciarSessao(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = str_starts_with((string) Env::get('APP_URL', ''), 'https://');
        $pasta = APP_ROOT . '/storage/sessions';
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0700, true);
        }
        if (is_dir($pasta) && is_writable($pasta)) {
            session_save_path($pasta);
        }
        // O prefixo __Host- obriga o navegador a só aceitar o cookie por conexão segura
        session_name($https ? '__Host-gestao' : 'gestao');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        if (isset($_SESSION['uid'])) {
            $agora = time();
            $ocioso = $agora - (int) ($_SESSION['ultimo'] ?? 0) > Config::SESSAO_OCIOSA_SEG;
            $velho = $agora - (int) ($_SESSION['inicio'] ?? 0) > Config::SESSAO_MAXIMA_SEG;
            if ($ocioso || $velho) {
                self::encerrar();
                session_start();
                flash('Sua sessão expirou. Entre novamente.', 'aviso');
            } else {
                $_SESSION['ultimo'] = $agora;
            }
        }
    }

    /** Usuário logado (recarregado do banco a cada pedido: mudanças de perfil valem na hora). */
    public static function usuario(): ?array
    {
        if (self::$carregado) {
            return self::$usuario;
        }
        self::$carregado = true;
        $id = $_SESSION['uid'] ?? null;
        if ($id) {
            $u = Db::one('SELECT id, name, email, role, active, must_change_password, photo, theme FROM users WHERE id = ?', [$id]);
            if ($u && (int) $u['active'] === 1) {
                self::$usuario = $u;
            } else {
                self::encerrar();
            }
        }
        return self::$usuario;
    }

    public static function exigir(bool $api = false): array
    {
        $u = self::usuario();
        if (!$u) {
            if ($api) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['erro' => 'Sessão expirada. Entre novamente.']);
                exit;
            }
            $volta = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? '/') : '/';
            redirect('/login?' . http_build_query(['volta' => $volta]));
        }
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if ((int) $u['must_change_password'] === 1 && !in_array($caminho, ['/conta/senha', '/sair'], true)) {
            if ($api) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['erro' => 'Troque sua senha antes de continuar.']);
                exit;
            }
            redirect('/conta/senha');
        }
        return $u;
    }

    /** @return array{ok:bool,msg:string} */
    public static function entrar(string $email, string $senha): array
    {
        $email = mb_strtolower(trim($email));
        $ip = ip_cliente();
        $janela = Config::LOGIN_JANELA_MIN;

        $falhasEmail = (int) Db::value(
            "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL $janela MINUTE)",
            [$email]
        );
        $falhasIp = (int) Db::value(
            "SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > (NOW() - INTERVAL $janela MINUTE)",
            [$ip]
        );
        if ($falhasEmail >= Config::LOGIN_MAX_FALHAS_EMAIL || $falhasIp >= Config::LOGIN_MAX_FALHAS_IP) {
            Audit::registrar('login_bloqueado', 'usuario', null, "Login bloqueado por excesso de tentativas ($email)", null, ['id' => null, 'name' => $email]);
            return ['ok' => false, 'msg' => "Muitas tentativas. Aguarde $janela minutos e tente de novo."];
        }

        $u = $email !== '' ? Db::one('SELECT * FROM users WHERE email = ?', [$email]) : null;
        // Sempre confere uma senha (mesmo se o e-mail não existir) para não revelar quais e-mails existem pelo tempo de resposta
        $hash = $u['password_hash'] ?? password_hash('senha-que-nunca-sera-usada', PASSWORD_DEFAULT);
        $confere = password_verify($senha, $hash);
        $valido = $u && $confere && !empty($u['password_hash']) && (int) $u['active'] === 1;

        Db::run('INSERT INTO login_attempts (email, ip, success) VALUES (?,?,?)', [$email, $ip, $valido ? 1 : 0]);

        if (!$valido) {
            Audit::registrar('login_falhou', 'usuario', $u ? (int) $u['id'] : null, "Tentativa de login sem sucesso ($email)", null, ['id' => null, 'name' => $email]);
            return ['ok' => false, 'msg' => 'E-mail ou senha incorretos.'];
        }

        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
        }
        Db::run('DELETE FROM login_attempts WHERE email = ? AND success = 0', [$email]);
        Db::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$u['id']]);

        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $u['id'];
        $_SESSION['inicio'] = $_SESSION['ultimo'] = time();
        unset($_SESSION['_csrf']);
        self::$carregado = false;
        Audit::registrar('login', 'usuario', (int) $u['id'], 'Entrou no sistema', null, ['id' => $u['id'], 'name' => $u['name']]);
        return ['ok' => true, 'msg' => ''];
    }

    public static function encerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$usuario = null;
        self::$carregado = true;
    }

    /** Regras de senha. Devolve a mensagem de erro, ou null se estiver boa. */
    public static function validarSenha(string $senha, ?string $email = null): ?string
    {
        if (mb_strlen($senha) < Config::SENHA_MIN) {
            return 'A senha precisa ter pelo menos ' . Config::SENHA_MIN . ' caracteres.';
        }
        if ($email && mb_strtolower($senha) === mb_strtolower($email)) {
            return 'A senha não pode ser igual ao e-mail.';
        }
        if (!preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
            return 'Use letras e números na senha.';
        }
        return null;
    }
}
