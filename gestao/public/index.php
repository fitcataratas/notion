<?php
declare(strict_types=1);

// Ponto de entrada único do site: todo endereço passa por aqui.
require __DIR__ . '/../app/bootstrap.php';

foreach (['auth', 'conta', 'home', 'tarefas', 'solicitacoes', 'setores', 'postagens', 'blog', 'reunioes', 'notificacoes', 'institucional', 'arrastar', 'editorial', 'admin'] as $controlador) {
    require __DIR__ . "/../app/controllers/$controlador.php";
}

Router::dispatch();
