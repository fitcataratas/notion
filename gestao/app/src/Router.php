<?php
declare(strict_types=1);

/** Direciona cada endereço para a tela certa e aplica as regras de acesso e de segurança. */
final class Router
{
    /** @var array<int,array{0:string,1:string,2:callable,3:array}> */
    private static array $rotas = [];

    /**
     * opções: 'publica' => true (sem login) | 'papel' => 'admin'|'equipe' | 'api' => true (responde JSON)
     */
    public static function add(string $metodo, string $padrao, callable $acao, array $opcoes = []): void
    {
        self::$rotas[] = [$metodo, '#^' . $padrao . '$#', $acao, $opcoes];
    }

    public static function dispatch(): void
    {
        self::cabecalhosDeSeguranca();
        Auth::iniciarSessao();

        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($caminho !== '/' && str_ends_with($caminho, '/')) {
            $caminho = rtrim($caminho, '/');
        }

        foreach (self::$rotas as [$m, $regex, $acao, $op]) {
            if ($m !== $metodo || !preg_match($regex, $caminho, $achados)) {
                continue;
            }
            $params = array_filter($achados, 'is_string', ARRAY_FILTER_USE_KEY);
            $api = !empty($op['api']);

            if ($metodo !== 'GET' && !Csrf::conferir()) {
                if ($api) {
                    View::json(['erro' => 'Sessão inválida. Recarregue a página.'], 419);
                }
                http_response_code(419);
                View::render('erro', ['titulo' => 'Sessão inválida', 'mensagem' => 'Por segurança, esta ação foi bloqueada. Volte, recarregue a página e tente de novo.'], Auth::usuario() !== null);
                return;
            }

            $usuario = null;
            if (empty($op['publica'])) {
                $usuario = Auth::exigir($api);
                if (isset($op['papel'])) {
                    $ok = $op['papel'] === 'admin' ? Perm::admin($usuario) : Perm::equipe($usuario);
                    if (!$ok) {
                        if ($api) {
                            View::json(['erro' => 'Sem permissão.'], 403);
                        }
                        View::proibido();
                    }
                }
            }
            $acao($params, $usuario);
            return;
        }
        View::naoEncontrado();
    }

    private static function cabecalhosDeSeguranca(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
        header('Cache-Control: no-store');
        if (str_starts_with((string) Env::get('APP_URL', ''), 'https://')) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }
}
