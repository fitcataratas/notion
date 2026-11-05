<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('VIEWS', APP_ROOT . '/app/views');

foreach (['Env', 'Db', 'Config', 'helpers', 'Csrf', 'Audit', 'Auth', 'Perm', 'View', 'Router', 'Tarefas', 'Usuarios', 'Imagem', 'Aparencia', 'Setores', 'Postagens', 'Blog', 'Notificacoes', 'Reunioes', 'Institucional', 'Avisos'] as $arq) {
    require_once APP_ROOT . "/app/src/$arq.php";
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('America/Sao_Paulo');

// Erros nunca aparecem para o visitante: ficam no registro do servidor.
set_exception_handler(function (Throwable $e): void {
    error_log('[gestao] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $isApi = str_starts_with(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/api/');
    if ($isApi) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erro' => 'Erro interno. Tente novamente.']);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Erro</title><body style="font-family:sans-serif;padding:2rem">'
           . '<h1>Algo deu errado</h1><p>O erro foi registrado. Tente novamente em instantes.</p>';
        if (Env::isDev()) {
            echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
        }
    }
});
