<?php
declare(strict_types=1);

/** Só aceita endereços internos do próprio site como destino de retorno. */
function volta_segura(?string $v, string $padrao = '/'): string
{
    $v = (string) $v;
    if ($v !== '' && $v[0] === '/' && !str_starts_with($v, '//') && !str_contains($v, '\\') && !preg_match('/[\r\n]/', $v)) {
        return $v;
    }
    return $padrao;
}

Router::add('GET', '/login', function () {
    if (Auth::usuario()) {
        redirect('/');
    }
    View::render('login', ['titulo' => 'Entrar', 'erro' => null, 'email' => '', 'volta' => volta_segura($_GET['volta'] ?? '/')], false);
}, ['publica' => true]);

Router::add('POST', '/login', function () {
    $email = (string) ($_POST['email'] ?? '');
    $volta = volta_segura($_POST['volta'] ?? '/');
    $r = Auth::entrar($email, (string) ($_POST['senha'] ?? ''));
    if ($r['ok']) {
        redirect($volta);
    }
    http_response_code(401);
    View::render('login', ['titulo' => 'Entrar', 'erro' => $r['msg'], 'email' => $email, 'volta' => $volta], false);
}, ['publica' => true]);

Router::add('POST', '/sair', function (array $p, array $u) {
    Audit::registrar('logout', 'usuario', (int) $u['id'], 'Saiu do sistema', null, $u);
    Auth::encerrar();
    redirect('/login');
});

Router::add('GET', '/conta/senha', function (array $p, array $u) {
    View::render('conta_senha', ['titulo' => 'Trocar senha', 'erros' => [], 'obrigatoria' => (int) $u['must_change_password'] === 1]);
});

Router::add('POST', '/conta/senha', function (array $p, array $u) {
    $erros = [];
    $atual = (string) ($_POST['atual'] ?? '');
    $nova = (string) ($_POST['nova'] ?? '');
    $conf = (string) ($_POST['confirma'] ?? '');
    $hash = (string) Db::value('SELECT password_hash FROM users WHERE id = ?', [$u['id']]);
    if (!password_verify($atual, $hash)) {
        $erros[] = 'A senha atual não confere.';
    }
    if ($nova !== $conf) {
        $erros[] = 'A confirmação não é igual à nova senha.';
    }
    if ($msg = Auth::validarSenha($nova, $u['email'])) {
        $erros[] = $msg;
    }
    if ($nova === $atual) {
        $erros[] = 'A nova senha precisa ser diferente da atual.';
    }
    if ($erros) {
        http_response_code(422);
        View::render('conta_senha', ['titulo' => 'Trocar senha', 'erros' => $erros, 'obrigatoria' => (int) $u['must_change_password'] === 1]);
        return;
    }
    Db::run('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($nova, PASSWORD_DEFAULT), $u['id']]);
    Audit::registrar('trocar_senha', 'usuario', (int) $u['id'], 'Trocou a própria senha', null, $u);
    session_regenerate_id(true);
    flash('Senha alterada com sucesso.');
    redirect('/');
});
