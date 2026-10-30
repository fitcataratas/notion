<?php
declare(strict_types=1);

// Perfil de cada pessoa (foto e tema) + arquivos de aparência servidos ao navegador.

Router::add('GET', '/conta', function (array $p, array $u) {
    View::render('conta_perfil', ['titulo' => 'Meu perfil', 'eu' => Usuarios::obter((int) $u['id'])]);
});

Router::add('POST', '/conta/foto', function (array $p, array $u) {
    $erro = Usuarios::definirFoto((int) $u['id'], $_FILES['foto'] ?? null);
    if ($erro) {
        flash($erro, 'erro');
    } else {
        Audit::registrar('editar', 'usuario', (int) $u['id'], 'Trocou a própria foto', null, $u);
        flash('Foto atualizada.');
    }
    redirect('/conta');
});

Router::add('POST', '/conta/foto/remover', function (array $p, array $u) {
    Usuarios::removerFoto((int) $u['id']);
    flash('Foto removida.');
    redirect('/conta');
});

Router::add('POST', '/conta/tema', function (array $p, array $u) {
    $t = (string) ($_POST['tema'] ?? '');
    if (isset(Aparencia::TEMAS[$t])) {
        Db::run('UPDATE users SET theme = ? WHERE id = ?', [$t, $u['id']]);
        flash('Tema salvo.');
    }
    redirect('/conta');
});

// Foto de qualquer pessoa: só para quem está logado. O código na URL muda a cada troca de foto.
Router::add('GET', '/foto/(?P<cod>[0-9a-f]{24})', function (array $p) {
    Imagem::enviar('fotos', $p['cod'], 'jpg', 'image/jpeg');
});

// Logo: pública (aparece na tela de login).
Router::add('GET', '/marca/(?P<cod>[0-9a-f]{24})', function (array $p) {
    Imagem::enviar('aparencia', $p['cod'], 'png', 'image/png');
}, ['publica' => true]);

// Cores e formas escolhidas na Administração → Aparência.
Router::add('GET', '/tema\.css', function () {
    $css = Aparencia::css();
    header('Content-Type: text/css; charset=utf-8');
    header('Cache-Control: public, max-age=31536000, immutable');
    echo $css;
}, ['publica' => true]);
