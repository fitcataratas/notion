<?php
declare(strict_types=1);

// Sininho: vale para todos os logins (equipe, administradores e externos).
Router::add('GET', '/notificacoes', function (array $p, array $u) {
    View::render('notificacoes/lista', ['titulo' => 'Notificações', 'itens' => Notificacoes::lista((int) $u['id'])]);
});

Router::add('GET', '/notificacoes/(?P<id>\d+)/abrir', function (array $p, array $u) {
    $url = Notificacoes::abrir((int) $p['id'], (int) $u['id']);
    if ($url === null) {
        View::naoEncontrado();
    }
    redirect(volta_segura($url, '/notificacoes'));
});

Router::add('POST', '/notificacoes/lidas', function (array $p, array $u) {
    Notificacoes::marcarTodas((int) $u['id']);
    Notificacoes::limpar();
    flash('Todas as notificações foram marcadas como lidas.');
    redirect('/notificacoes');
});
