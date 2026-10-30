<?php
declare(strict_types=1);

// Missão/Visão/Valores, Quem faz o quê, Projetos e o espaço de quem é externo.

Router::add('GET', '/missao', function (array $p, array $u) {
    View::render('institucional/missao', ['titulo' => 'Missão, Visão e Valores', 'missao' => Institucional::texto('missao'), 'visao' => Institucional::texto('visao'), 'valores' => Institucional::texto('valores')]);
}, ['papel' => 'equipe']);

Router::add('GET', '/quem-faz-o-que', function (array $p, array $u) {
    View::render('institucional/quem_faz', ['titulo' => 'Quem faz o quê', 'linhas' => Institucional::quemFaz(), 'aviso' => Institucional::texto('quem_faz_aviso')]);
}, ['papel' => 'equipe']);

Router::add('GET', '/projetos', function (array $p, array $u) {
    View::render('institucional/projetos', ['titulo' => 'Projetos', 'projetos' => Institucional::projetos()]);
}, ['papel' => 'equipe']);

// ------------------------------------------------------------- administração
Router::add('GET', '/admin/quem-faz', function (array $p, array $u) {
    View::render('admin/quem_faz', ['titulo' => 'Quem faz o quê: editar', 'linhas' => Institucional::quemFaz(), 'pessoas' => Usuarios::todos(true), 'aviso' => Institucional::texto('quem_faz_aviso'), 'erros' => []]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/quem-faz', function (array $p, array $u) {
    $e = Institucional::salvarQuemFaz(null, $_POST, $u);
    $e ? flash(implode(' ', $e), 'erro') : flash('Pessoa incluída.');
    redirect('/admin/quem-faz');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/quem-faz/(?P<id>\d+)', function (array $p, array $u) {
    if (!Db::value('SELECT 1 FROM who_does WHERE id = ?', [(int) $p['id']])) {
        View::naoEncontrado();
    }
    $e = Institucional::salvarQuemFaz((int) $p['id'], $_POST, $u);
    $e ? flash(implode(' ', $e), 'erro') : flash('Salvo.');
    redirect('/admin/quem-faz#l' . (int) $p['id']);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/quem-faz/(?P<id>\d+)/excluir', function (array $p, array $u) {
    $n = Db::value('SELECT person_name FROM who_does WHERE id = ?', [(int) $p['id']]);
    if ($n === null) {
        View::naoEncontrado();
    }
    Db::run('DELETE FROM who_does WHERE id = ?', [(int) $p['id']]);
    Audit::registrar('excluir', 'configuracao', (int) $p['id'], 'Quem faz o quê: removeu ' . $n, null, $u);
    flash('Linha removida.');
    redirect('/admin/quem-faz');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/quem-faz/aviso', function (array $p, array $u) {
    Institucional::salvarTextos(['quem_faz_aviso' => $_POST['quem_faz_aviso'] ?? ''], $u);
    flash('Aviso atualizado.');
    redirect('/admin/quem-faz');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/projetos/(?P<id>\d+)/detalhes', function (array $p, array $u) {
    if (!Institucional::salvarProjeto((int) $p['id'], $_POST, $u)) {
        View::naoEncontrado();
    }
    flash('Projeto atualizado.');
    redirect('/projetos');
}, ['papel' => 'admin']);

// -------------------------------------------- espaço de quem é externo (ex.: Vacy)
Router::add('GET', '/espaco', function (array $p, array $u) {
    $r = Tarefas::consultar(['mine' => 1], $u, 'data', 'asc', 300);
    $por = array_fill_keys(Config::STATUS, []);
    foreach ($r['linhas'] as $l) {
        $por[$l['status']][] = $l;
    }
    View::render('institucional/espaco', [
        'titulo' => 'Meu espaço', 'colunas' => $por, 'total' => $r['total'], 'intro' => Institucional::texto('externo_intro'),
    ]);
});
