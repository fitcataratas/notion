<?php
declare(strict_types=1);

/** Notificações dentro do sistema (o "sininho"). Ninguém é avisado do que ele mesmo fez. */
final class Notificacoes
{
    public static function avisar(int $paraUsuario, string $tipo, string $texto, string $url, ?array $autor): void
    {
        $autorId = $autor ? (int) $autor['id'] : null;
        if ($autorId !== null && $autorId === $paraUsuario) {
            return;
        }
        if (!Db::value('SELECT 1 FROM users WHERE id = ? AND active = 1', [$paraUsuario])) {
            return;
        }
        Db::run(
            'INSERT INTO notifications (user_id, actor_id, kind, body, url) VALUES (?,?,?,?,?)',
            [$paraUsuario, $autorId, $tipo, mb_substr($texto, 0, 400), mb_substr($url, 0, 300)]
        );
    }

    /** Avisa várias pessoas de uma vez (sem repetir ninguém). */
    public static function avisarVarios(array $ids, string $tipo, string $texto, string $url, ?array $autor, bool $soEquipe = false): void
    {
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            if ($soEquipe && Db::value("SELECT 1 FROM users WHERE id = ? AND role = 'externo'", [$id])) {
                continue;   // externo não abre essa área
            }
            self::avisar($id, $tipo, $texto, $url, $autor);
        }
    }

    public static function naoLidas(int $usuario): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$usuario]);
    }

    public static function recentes(int $usuario, int $limite = 8): array
    {
        return Db::all('SELECT id, body, url, kind, read_at, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, $limite), [$usuario]);
    }

    public static function lista(int $usuario, int $limite = 100): array
    {
        return self::recentes($usuario, $limite);
    }

    /** Marca como lida e devolve o endereço para onde levar a pessoa (ou null se não for dela). */
    public static function abrir(int $id, int $usuario): ?string
    {
        $n = Db::one('SELECT url FROM notifications WHERE id = ? AND user_id = ?', [$id, $usuario]);
        if (!$n) {
            return null;
        }
        Db::run('UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = ?', [$id]);
        return $n['url'];
    }

    public static function marcarTodas(int $usuario): void
    {
        Db::run('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$usuario]);
    }

    /** Apaga as já lidas com mais de 60 dias, para a lista não crescer sem fim. */
    public static function limpar(): void
    {
        Db::run('DELETE FROM notifications WHERE read_at IS NOT NULL AND read_at < (NOW() - INTERVAL 60 DAY)');
    }

    /** Pessoas ligadas a uma tarefa (qualquer papel). */
    public static function pessoasDaTarefa(int $tarefaId): array
    {
        return array_map('intval', array_column(Db::all('SELECT DISTINCT user_id FROM task_people WHERE task_id = ?', [$tarefaId]), 'user_id'));
    }
}
