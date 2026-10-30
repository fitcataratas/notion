<?php
declare(strict_types=1);

/** Registro de quem fez o quê. Nunca grava senhas. */
final class Audit
{
    public static function registrar(
        string $acao,
        string $entidade,
        ?int $entidadeId,
        string $resumo,
        ?array $detalhes = null,
        ?array $usuario = null
    ): void {
        $usuario ??= Auth::usuario();
        try {
            Db::run(
                'INSERT INTO audit_log (user_id, user_name, action, entity, entity_id, summary, details, ip)
                 VALUES (?,?,?,?,?,?,?,?)',
                [
                    $usuario['id'] ?? null,
                    $usuario['name'] ?? null,
                    $acao,
                    $entidade,
                    $entidadeId,
                    mb_substr($resumo, 0, 255),
                    $detalhes ? json_encode($detalhes, JSON_UNESCAPED_UNICODE) : null,
                    ip_cliente(),
                ]
            );
        } catch (Throwable $e) {
            error_log('[gestao] falha ao registrar histórico: ' . $e->getMessage());
        }
    }
}
