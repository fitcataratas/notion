<?php
declare(strict_types=1);

// Blog: Banco de Ideias (quadro) + Pautas. Equipe e administradores.
// Qualquer pessoa da equipe cadastra ideia; quem avalia é a Coordenação (e os administradores).

function dados_blog(): array
{
    return ['pessoas' => Usuarios::todos(true)];
}

Router::add('GET', '/blog', function (array $p, array $u) {
    $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    $colunas = array_fill_keys(array_keys(Blog::AVALIACOES), []);
    foreach (Blog::ideias($q) as $i) {
        $colunas[$i['evaluation']][] = $i;
    }
    $f = ['status' => (string) ($_GET['status'] ?? ''), 'abertas' => !empty($_GET['abertas']) ? 1 : 0];
    View::render('blog/painel', [
        'titulo' => 'Post - Blog', 'colunas' => $colunas, 'q' => $q, 'pode_avaliar' => Blog::podeAvaliar($u),
        'pautas' => Blog::pautas($f), 'f' => $f,
    ]);
}, ['papel' => 'equipe']);

// ----------------------------------------------------------------- ideias
Router::add('POST', '/blog/ideias', function (array $p, array $u) {
    $r = Blog::criarIdeia((string) ($_POST['title'] ?? ''), (string) ($_POST['notes'] ?? ''), $u);
    if (is_string($r)) {
        flash($r, 'erro');
    } else {
        flash('Ideia cadastrada em “Sem Avaliação”.');
    }
    redirect('/blog');
}, ['papel' => 'equipe']);

Router::add('GET', '/blog/ideias/(?P<id>\d+)', function (array $p, array $u) {
    $i = Blog::ideia((int) $p['id']);
    if (!$i) {
        View::naoEncontrado();
    }
    View::render('blog/ideia', ['titulo' => $i['title'], 'i' => $i, 'erros' => [], 'pode_avaliar' => Blog::podeAvaliar($u)]);
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/ideias/(?P<id>\d+)', function (array $p, array $u) {
    $id = (int) $p['id'];
    $i = Blog::ideia($id);
    if (!$i) {
        View::naoEncontrado();
    }
    $erro = Blog::salvarIdeia($id, (string) ($_POST['title'] ?? ''), (string) ($_POST['notes'] ?? ''), $u);
    if ($erro) {
        http_response_code(422);
        $i['title'] = (string) ($_POST['title'] ?? '');
        $i['notes'] = (string) ($_POST['notes'] ?? '');
        View::render('blog/ideia', ['titulo' => 'Editar ideia', 'i' => $i, 'erros' => [$erro], 'pode_avaliar' => Blog::podeAvaliar($u)]);
        return;
    }
    flash('Ideia salva.');
    redirect('/blog/ideias/' . $id);
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/ideias/(?P<id>\d+)/avaliacao', function (array $p, array $u) {
    if (!Blog::podeAvaliar($u)) {
        View::proibido();
    }
    $msg = Blog::mudarAvaliacao((int) $p['id'], (string) ($_POST['avaliacao'] ?? ''), $u);
    if ($msg === null) {
        View::naoEncontrado();
    }
    flash($msg);
    redirect(volta_segura($_POST['volta'] ?? '/blog', '/blog'));
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/ideias/(?P<id>\d+)/excluir', function (array $p, array $u) {
    if (!Blog::podeAvaliar($u)) {
        View::proibido();
    }
    $erro = Blog::excluirIdeia((int) $p['id'], $u);
    $erro ? flash($erro, 'erro') : flash('Ideia apagada.');
    redirect('/blog');
}, ['papel' => 'equipe']);

// ----------------------------------------------------------------- pautas
Router::add('GET', '/blog/pautas/nova', function (array $p, array $u) {
    View::render('blog/pauta', dados_blog() + ['titulo' => 'Nova pauta', 'v' => ['status' => 'Ideia recebida', 'requester_id' => (int) $u['id']], 'id' => null, 'erros' => []]);
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/pautas', function (array $p, array $u) {
    [$d, $erros] = Blog::validarPauta($_POST);
    if ($erros) {
        http_response_code(422);
        View::render('blog/pauta', dados_blog() + ['titulo' => 'Nova pauta', 'v' => $d, 'id' => null, 'erros' => $erros]);
        return;
    }
    $id = Blog::salvarPauta(null, $d, $u);
    flash('Pauta criada.');
    redirect('/blog/pautas/' . $id);
}, ['papel' => 'equipe']);

Router::add('GET', '/blog/pautas/(?P<id>\d+)', function (array $p, array $u) {
    $v = Blog::pauta((int) $p['id']);
    if (!$v) {
        View::naoEncontrado();
    }
    View::render('blog/pauta', dados_blog() + ['titulo' => $v['title'], 'v' => $v, 'id' => (int) $v['id'], 'erros' => []]);
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/pautas/(?P<id>\d+)', function (array $p, array $u) {
    $id = (int) $p['id'];
    $atual = Blog::pauta($id);
    if (!$atual) {
        View::naoEncontrado();
    }
    [$d, $erros] = Blog::validarPauta($_POST);
    if ($erros) {
        http_response_code(422);
        View::render('blog/pauta', dados_blog() + ['titulo' => 'Editar pauta', 'v' => $d + ['ideia_titulo' => $atual['ideia_titulo'], 'idea_id' => $atual['idea_id']], 'id' => $id, 'erros' => $erros]);
        return;
    }
    Blog::salvarPauta($id, $d, $u);
    flash('Pauta salva.');
    redirect('/blog/pautas/' . $id);
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/pautas/(?P<id>\d+)/status', function (array $p, array $u) {
    if (!Blog::mudarStatusPauta((int) $p['id'], (string) ($_POST['status'] ?? ''), $u)) {
        View::naoEncontrado();
    }
    flash('Status da pauta atualizado.');
    redirect(volta_segura($_POST['volta'] ?? '/blog', '/blog'));
}, ['papel' => 'equipe']);

Router::add('POST', '/blog/pautas/(?P<id>\d+)/excluir', function (array $p, array $u) {
    if (!Blog::pauta((int) $p['id'])) {
        View::naoEncontrado();
    }
    Blog::excluirPauta((int) $p['id'], $u);
    flash('Pauta excluída.');
    redirect('/blog');
}, ['papel' => 'admin']);
