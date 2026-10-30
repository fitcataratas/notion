<?php
declare(strict_types=1);

/**
 * Quem pode o quê (memorial, seções 27 e 35):
 *   admin   → tudo, inclusive estrutura (usuários, setores, projetos) e excluir
 *   equipe  → cria e edita conteúdo; não mexe na estrutura
 *   externo → só enxerga as tarefas em que é responsável e os pedidos de Marketing/Design que ele mesmo fez;
 *             muda o status só das tarefas em que é responsável; comenta nas que enxerga
 */
final class Perm
{
    public static function admin(array $u): bool
    {
        return $u['role'] === 'admin';
    }

    public static function equipe(array $u): bool
    {
        return in_array($u['role'], ['admin', 'equipe'], true);
    }

    public static function externo(array $u): bool
    {
        return $u['role'] === 'externo';
    }

    /** Externo só vê tarefa em que é responsável, ou pedido de Marketing/Design em que é o solicitante. */
    public static function veTarefa(array $u, int $tarefaId): bool
    {
        if (!self::externo($u)) {
            return true;
        }
        return self::responsavel($u, $tarefaId) || (bool) Db::value(
            "SELECT 1 FROM tasks t JOIN task_people tp ON tp.task_id = t.id AND tp.user_id = ? AND tp.role = 'solicitante'
              WHERE t.id = ? AND t.mkt_stage IS NOT NULL AND t.mkt_stage <> ''",
            [$u['id'], $tarefaId]
        );
    }

    public static function responsavel(array $u, int $tarefaId): bool
    {
        return (bool) Db::value(
            "SELECT 1 FROM task_people WHERE task_id = ? AND user_id = ? AND role = 'responsavel'",
            [$tarefaId, $u['id']]
        );
    }

    public static function editaTarefa(array $u): bool
    {
        return self::equipe($u);
    }

    public static function mudaStatus(array $u, int $tarefaId): bool
    {
        return self::externo($u) ? self::responsavel($u, $tarefaId) : true;
    }
}
