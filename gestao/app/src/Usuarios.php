<?php
declare(strict_types=1);

/** Usuários, setores e projetos (a "estrutura" que só administradores alteram). */
final class Usuarios
{
    public static function todos(bool $soAtivos = false): array
    {
        return Db::all('SELECT id, name, email, role, active, photo, must_change_password, last_login_at, (password_hash IS NOT NULL) AS tem_senha
                        FROM users ' . ($soAtivos ? 'WHERE active = 1 ' : '') . 'ORDER BY name');
    }

    public static function obter(int $id): ?array
    {
        return Db::one('SELECT id, name, email, role, active, photo, theme, must_change_password, last_login_at, (password_hash IS NOT NULL) AS tem_senha FROM users WHERE id = ?', [$id]);
    }

    public static function setores(): array
    {
        return Db::all('SELECT s.*, (SELECT COUNT(*) FROM task_sectors ts WHERE ts.sector_id = s.id) AS usos FROM sectors s ORDER BY s.sort_order, s.name');
    }

    public static function projetos(): array
    {
        return Db::all('SELECT p.*, (SELECT COUNT(*) FROM task_projects tp WHERE tp.project_id = p.id) AS usos FROM projects p ORDER BY p.sort_order, p.name');
    }

    /** Valida nome/e-mail/perfil. @return string[] erros */
    public static function validar(array $in, ?int $idAtual = null): array
    {
        $erros = [];
        $nome = trim((string) ($in['name'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 120) {
            $erros[] = 'Informe o nome (até 120 letras).';
        } elseif (Db::value('SELECT 1 FROM users WHERE name = ? AND id <> ?', [$nome, $idAtual ?? 0])) {
            $erros[] = 'Já existe uma pessoa com esse nome.';
        }
        $email = mb_strtolower(trim((string) ($in['email'] ?? '')));
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
                $erros[] = 'E-mail inválido.';
            } elseif (Db::value('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$email, $idAtual ?? 0])) {
                $erros[] = 'Esse e-mail já está em uso.';
            }
        }
        if (!isset(Config::PAPEIS[$in['role'] ?? ''])) {
            $erros[] = 'Escolha o perfil de acesso.';
        }
        return $erros;
    }

    /** Grava a foto nova (já reduzida e regravada). @return string|null mensagem de erro */
    public static function definirFoto(int $id, ?array $arquivo): ?string
    {
        $r = Imagem::processar($arquivo, 'foto');
        if (!$r['ok']) {
            return $r['erro'];
        }
        $antiga = Db::value('SELECT photo FROM users WHERE id = ?', [$id]);
        $cod = Imagem::guardar('fotos', $r['bin'], 'jpg');
        Db::run('UPDATE users SET photo = ? WHERE id = ?', [$cod, $id]);
        Imagem::apagar('fotos', $antiga ? (string) $antiga : null, 'jpg');
        return null;
    }

    public static function removerFoto(int $id): void
    {
        $antiga = Db::value('SELECT photo FROM users WHERE id = ?', [$id]);
        Db::run('UPDATE users SET photo = NULL WHERE id = ?', [$id]);
        Imagem::apagar('fotos', $antiga ? (string) $antiga : null, 'jpg');
    }

    /** Quantas tarefas e comentários ficam ligados a uma pessoa (para avisar antes de apagar). */
    public static function usos(int $id): array
    {
        return [
            'tarefas' => (int) Db::value('SELECT COUNT(DISTINCT task_id) FROM task_people WHERE user_id = ?', [$id]),
            'comentarios' => (int) Db::value('SELECT COUNT(*) FROM comments WHERE user_id = ?', [$id]),
        ];
    }

    public static function totalAdminsAtivos(): int
    {
        return (int) Db::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND password_hash IS NOT NULL");
    }
}
