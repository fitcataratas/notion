<?php
declare(strict_types=1);

// Página de cada setor: responsáveis, resumo, páginas de texto e links de documentos.
// A lista de tarefas do setor é a MESMA Central de Tarefas, só filtrada (memorial, seção 8).

Router::add('GET', '/setores/(?P<id>\d+)', function (array $p, array $u) {
    $s = Setores::obter((int) $p['id']);
    if (!$s) {
        View::naoEncontrado();
    }
    $id = (int) $s['id'];
    $abertas = Tarefas::consultar(['setor' => $id, 'status' => 'abertas'], $u, 'data', 'asc', 8);
    View::render('setores/ver', [
        'titulo' => $s['name'], 's' => $s, 'contagem' => Setores::contagem($id, $u),
        'abertas' => $abertas['linhas'], 'abertas_total' => $abertas['total'],
        'paginas' => Setores::paginas($id), 'links' => Setores::links($id), 'hoje' => date('Y-m-d'),
        'recebe_pedidos' => in_array($id, [(int) Db::value("SELECT v FROM settings WHERE k = 'sol_setor_recebe'"), (int) Db::value("SELECT v FROM settings WHERE k = 'sol_setor_design'")], true),
    ]);
}, ['papel' => 'equipe']);

// ---------------------------------------------------------------- páginas de texto
Router::add('GET', '/setores/(?P<id>\d+)/paginas/nova', function (array $p, array $u) {
    $s = Setores::obter((int) $p['id']);
    if (!$s) {
        View::naoEncontrado();
    }
    View::render('setores/pagina_form', ['titulo' => 'Nova página', 's' => $s, 'pg' => ['title' => '', 'body' => ''], 'id' => null, 'erros' => []]);
}, ['papel' => 'equipe']);

Router::add('POST', '/setores/(?P<id>\d+)/paginas', function (array $p, array $u) {
    $s = Setores::obter((int) $p['id']);
    if (!$s) {
        View::naoEncontrado();
    }
    $titulo = trim((string) ($_POST['title'] ?? ''));
    $corpo = trim((string) ($_POST['body'] ?? ''));
    $erros = [];
    if ($titulo === '' || mb_strlen($titulo) > 160) {
        $erros[] = 'Dê um título à página (até 160 letras).';
    }
    if (mb_strlen($corpo) > 200000) {
        $erros[] = 'O texto está longo demais.';
    }
    if ($erros) {
        http_response_code(422);
        View::render('setores/pagina_form', ['titulo' => 'Nova página', 's' => $s, 'pg' => ['title' => $titulo, 'body' => $corpo], 'id' => null, 'erros' => $erros]);
        return;
    }
    Db::run('INSERT INTO pages (sector_id, title, body, created_by, updated_by) VALUES (?,?,?,?,?)', [$s['id'], $titulo, $corpo === '' ? null : $corpo, $u['id'], $u['id']]);
    $pid = Db::insertId();
    Audit::registrar('criar', 'pagina', $pid, "Criou a página \"$titulo\" no setor {$s['name']}", null, $u);
    flash('Página criada.');
    redirect('/paginas/' . $pid);
}, ['papel' => 'equipe']);

function pagina_ou_404(int $id): array
{
    $pg = Db::one('SELECT p.*, uc.name AS autor, uu.name AS editor FROM pages p LEFT JOIN users uc ON uc.id = p.created_by LEFT JOIN users uu ON uu.id = p.updated_by WHERE p.id = ?', [$id]);
    if (!$pg) {
        View::naoEncontrado();
    }
    return $pg;
}

Router::add('GET', '/paginas/(?P<id>\d+)', function (array $p, array $u) {
    $pg = pagina_ou_404((int) $p['id']);
    View::render('setores/pagina', ['titulo' => $pg['title'], 'pg' => $pg, 's' => Setores::obter((int) $pg['sector_id'])]);
}, ['papel' => 'equipe']);

Router::add('GET', '/paginas/(?P<id>\d+)/editar', function (array $p, array $u) {
    $pg = pagina_ou_404((int) $p['id']);
    View::render('setores/pagina_form', ['titulo' => 'Editar página', 's' => Setores::obter((int) $pg['sector_id']), 'pg' => $pg, 'id' => (int) $pg['id'], 'erros' => []]);
}, ['papel' => 'equipe']);

Router::add('POST', '/paginas/(?P<id>\d+)', function (array $p, array $u) {
    $pg = pagina_ou_404((int) $p['id']);
    $titulo = trim((string) ($_POST['title'] ?? ''));
    $corpo = trim((string) ($_POST['body'] ?? ''));
    $erros = [];
    if ($titulo === '' || mb_strlen($titulo) > 160) {
        $erros[] = 'Dê um título à página (até 160 letras).';
    }
    if (mb_strlen($corpo) > 200000) {
        $erros[] = 'O texto está longo demais.';
    }
    if ($erros) {
        http_response_code(422);
        View::render('setores/pagina_form', ['titulo' => 'Editar página', 's' => Setores::obter((int) $pg['sector_id']), 'pg' => ['title' => $titulo, 'body' => $corpo] + $pg, 'id' => (int) $pg['id'], 'erros' => $erros]);
        return;
    }
    Db::run('UPDATE pages SET title = ?, body = ?, updated_by = ? WHERE id = ?', [$titulo, $corpo === '' ? null : $corpo, $u['id'], $pg['id']]);
    Audit::registrar('editar', 'pagina', (int) $pg['id'], "Editou a página \"$titulo\"", null, $u);
    flash('Página salva.');
    redirect('/paginas/' . (int) $pg['id']);
}, ['papel' => 'equipe']);

Router::add('POST', '/paginas/(?P<id>\d+)/excluir', function (array $p, array $u) {
    $pg = pagina_ou_404((int) $p['id']);
    Db::run('DELETE FROM pages WHERE id = ?', [$pg['id']]);
    Audit::registrar('excluir', 'pagina', (int) $pg['id'], "Excluiu a página \"{$pg['title']}\"", null, $u);
    flash('Página excluída.');
    redirect('/setores/' . (int) $pg['sector_id']);
}, ['papel' => 'admin']);

// ------------------------------------------------------------ links de documentos
Router::add('POST', '/setores/(?P<id>\d+)/links', function (array $p, array $u) {
    $s = Setores::obter((int) $p['id']);
    if (!$s) {
        View::naoEncontrado();
    }
    $titulo = trim((string) ($_POST['title'] ?? ''));
    $url = Setores::urlValida((string) ($_POST['url'] ?? ''));
    $grupo = in_array($_POST['grp'] ?? '', Setores::GRUPOS_LINK, true) ? $_POST['grp'] : 'Documentos';
    if ($titulo === '' || mb_strlen($titulo) > 160) {
        flash('Dê um nome ao link (até 160 letras).', 'erro');
    } elseif ($url === null) {
        flash('Endereço inválido. Cole o link completo, começando com http:// ou https://.', 'erro');
    } else {
        Db::run('INSERT INTO sector_links (sector_id, title, url, grp) VALUES (?,?,?,?)', [$s['id'], $titulo, $url, $grupo]);
        Audit::registrar('criar', 'pagina', null, "Adicionou o link \"$titulo\" no setor {$s['name']}", null, $u);
        flash('Link adicionado.');
    }
    redirect('/setores/' . (int) $s['id'] . '#documentos');
}, ['papel' => 'equipe']);

Router::add('POST', '/links/(?P<id>\d+)/excluir', function (array $p, array $u) {
    $l = Db::one('SELECT * FROM sector_links WHERE id = ?', [(int) $p['id']]);
    if (!$l) {
        View::naoEncontrado();
    }
    Db::run('DELETE FROM sector_links WHERE id = ?', [$l['id']]);
    Audit::registrar('excluir', 'pagina', null, "Removeu o link \"{$l['title']}\"", null, $u);
    flash('Link removido.');
    redirect('/setores/' . (int) $l['sector_id'] . '#documentos');
}, ['papel' => 'equipe']);

// ------------------------------------------- administração: descrição e responsáveis
Router::add('GET', '/admin/setores/(?P<id>\d+)/detalhes', function (array $p, array $u) {
    $s = Setores::obter((int) $p['id']);
    if (!$s) {
        View::naoEncontrado();
    }
    View::render('admin/setor_detalhes', ['titulo' => 'Setor ' . $s['name'], 's' => $s, 'erros' => [], 'pessoas' => Usuarios::todos(true), 'outros' => Db::all('SELECT id, name FROM sectors WHERE id <> ? AND parent_id IS NULL ORDER BY sort_order, name', [$s['id']])]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/setores/(?P<id>\d+)/detalhes', function (array $p, array $u) {
    $s = Setores::obter((int) $p['id']);
    if (!$s) {
        View::naoEncontrado();
    }
    $erros = Setores::salvarDetalhes((int) $s['id'], $_POST, $u);
    if ($erros) {
        http_response_code(422);
        View::render('admin/setor_detalhes', ['titulo' => 'Setor ' . $s['name'], 's' => array_merge($s, ['description' => $_POST['description'] ?? '', 'parent_id' => $_POST['parent_id'] ?? null]), 'erros' => $erros, 'pessoas' => Usuarios::todos(true), 'outros' => Db::all('SELECT id, name FROM sectors WHERE id <> ? AND parent_id IS NULL ORDER BY sort_order, name', [$s['id']])]);
        return;
    }
    Audit::registrar('editar', 'setor', (int) $s['id'], "Alterou a página do setor {$s['name']} (descrição e responsáveis)", null, $u);
    flash('Setor atualizado.');
    redirect('/setores/' . (int) $s['id']);
}, ['papel' => 'admin']);
