<?php
declare(strict_types=1);

// Cronograma de Postagens (tabela, calendário, filtro por formato) e Ideias de Conteúdo.
// Só equipe e administradores. Quem é externo não vê esta área.

function dados_postagem(): array
{
    return ['listas' => Postagens::listas(), 'pessoas' => Usuarios::todos(true)];
}

Router::add('GET', '/postagens', function (array $p, array $u) {
    $visao = ($_GET['view'] ?? '') === 'calendario' ? 'calendario' : 'tabela';
    $f = Postagens::filtros($_GET);
    $dados = dados_postagem() + ['f' => $f, 'visao' => $visao, 'titulo' => 'Cronograma de Postagens'];
    if ($visao === 'tabela') {
        $ordem = array_key_exists($_GET['ordem'] ?? '', ['data' => 1, 'titulo' => 1, 'situacao' => 1, 'formato' => 1, 'projeto' => 1]) ? $_GET['ordem'] : 'data';
        $dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $porPagina = 50;
        $pag = max(1, (int) ($_GET['pag'] ?? 1));
        $r = Postagens::consultar($f, $ordem, $dir, $porPagina, ($pag - 1) * $porPagina);
        $dados += ['linhas' => $r['linhas'], 'total' => $r['total'], 'ordem' => $ordem, 'dir' => $dir, 'pag' => $pag, 'paginas' => max(1, (int) ceil($r['total'] / $porPagina))];
    } else {
        $mes = mes_valido($_GET['mes'] ?? null);
        $primeiro = new DateTimeImmutable("$mes-01");
        $ultimo = $primeiro->modify('last day of this month');
        $ini = $primeiro->modify('-' . (int) $primeiro->format('w') . ' days');
        $fim = $ultimo->modify('+' . (6 - (int) $ultimo->format('w')) . ' days');
        $r = Postagens::consultar(array_diff_key($f, ['sem_data' => 1]), 'data', 'asc', 1500, 0, $ini->format('Y-m-d'), $fim->format('Y-m-d'));
        $porDia = [];
        foreach ($r['linhas'] as $l) {
            $porDia[$l['post_date']][] = $l;
        }
        $semData = Postagens::consultar(array_merge($f, ['sem_data' => 1]), 'data', 'asc', 1)['total'];
        $dados += [
            'mes' => $mes, 'primeiro' => $primeiro, 'ini' => $ini, 'fim' => $fim, 'por_dia' => $porDia,
            'anterior' => $primeiro->modify('-1 month')->format('Y-m'), 'proximo' => $primeiro->modify('+1 month')->format('Y-m'),
            'sem_data_total' => $semData,
        ];
    }
    View::render('postagens/lista', $dados);
}, ['papel' => 'equipe']);

// ------------------------------------------------------------- nova / editar
Router::add('GET', '/postagens/nova', function (array $p, array $u) {
    $v = ['_pessoas' => [], '_plataformas' => []];
    if (!empty($_GET['data']) && data_valida($_GET['data'])) {
        $v['post_date'] = $_GET['data'];
    }
    $ideia = null;
    if (!empty($_GET['ideia']) && ctype_digit((string) $_GET['ideia'])) {
        $ideia = Db::one('SELECT id, title, notes FROM ideas WHERE id = ? AND used = 0', [(int) $_GET['ideia']]);
        if ($ideia) {
            $v['title'] = $ideia['title'];
            $v['content'] = $ideia['notes'];
        }
    }
    $v['status_id'] = (int) Db::value("SELECT id FROM post_options WHERE kind = 'situacao' ORDER BY sort_order LIMIT 1") ?: null;
    View::render('postagens/form', dados_postagem() + ['titulo' => 'Nova postagem', 'v' => $v, 'id' => null, 'erros' => [], 'ideia' => $ideia, 'volta' => volta_segura($_GET['volta'] ?? '/postagens')]);
}, ['papel' => 'equipe']);

Router::add('POST', '/postagens', function (array $p, array $u) {
    [$d, $erros] = Postagens::validar($_POST);
    $ideia = !empty($_POST['ideia']) && ctype_digit((string) $_POST['ideia']) ? Db::one('SELECT id, title, notes FROM ideas WHERE id = ? AND used = 0', [(int) $_POST['ideia']]) : null;
    if ($erros) {
        http_response_code(422);
        View::render('postagens/form', dados_postagem() + ['titulo' => 'Nova postagem', 'v' => $d, 'id' => null, 'erros' => $erros, 'ideia' => $ideia, 'volta' => volta_segura($_POST['volta'] ?? '/postagens')]);
        return;
    }
    $id = Postagens::salvar(null, $d, $u);
    if ($ideia) {
        Db::run('UPDATE ideas SET used = 1, post_id = ? WHERE id = ?', [$id, (int) $ideia['id']]);
    }
    flash('Postagem criada.');
    redirect('/postagens/' . $id);
}, ['papel' => 'equipe']);

Router::add('GET', '/postagens/(?P<id>\d+)', function (array $p, array $u) {
    $x = Postagens::obter((int) $p['id']);
    if (!$x) {
        View::naoEncontrado();
    }
    $x['_pessoas'] = array_map(fn($r) => (int) $r['id'], $x['pessoas']);
    $x['_plataformas'] = array_map(fn($r) => (int) $r['id'], $x['plataformas']);
    View::render('postagens/form', dados_postagem() + ['titulo' => $x['title'], 'v' => $x, 'id' => (int) $x['id'], 'erros' => [], 'ideia' => null, 'volta' => volta_segura($_GET['volta'] ?? '/postagens')]);
}, ['papel' => 'equipe']);

Router::add('POST', '/postagens/(?P<id>\d+)', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Postagens::obter($id)) {
        View::naoEncontrado();
    }
    [$d, $erros] = Postagens::validar($_POST);
    if ($erros) {
        http_response_code(422);
        View::render('postagens/form', dados_postagem() + ['titulo' => 'Editar postagem', 'v' => $d, 'id' => $id, 'erros' => $erros, 'ideia' => null, 'volta' => volta_segura($_POST['volta'] ?? '/postagens')]);
        return;
    }
    Postagens::salvar($id, $d, $u);
    flash('Alterações salvas.');
    redirect('/postagens/' . $id . '?' . http_build_query(['volta' => volta_segura($_POST['volta'] ?? '/postagens')]));
}, ['papel' => 'equipe']);

Router::add('POST', '/postagens/(?P<id>\d+)/situacao', function (array $p, array $u) {
    if (!ctype_digit((string) ($_POST['status_id'] ?? '')) || !Postagens::mudarSituacao((int) $p['id'], (int) $_POST['status_id'], $u)) {
        View::naoEncontrado();
    }
    flash('Situação atualizada.');
    redirect(volta_segura($_POST['volta'] ?? '/postagens'));
}, ['papel' => 'equipe']);

Router::add('POST', '/postagens/(?P<id>\d+)/excluir', function (array $p, array $u) {
    if (!Postagens::obter((int) $p['id'])) {
        View::naoEncontrado();
    }
    Postagens::excluir((int) $p['id'], $u);
    flash('Postagem excluída.');
    redirect('/postagens');
}, ['papel' => 'admin']);

// ---------------------------------------------------------------- ideias
Router::add('GET', '/ideias', function (array $p, array $u) {
    $todas = !empty($_GET['todas']);
    View::render('postagens/ideias', ['titulo' => 'Ideias de Conteúdo', 'ideias' => Postagens::ideias($todas), 'todas' => $todas]);
}, ['papel' => 'equipe']);

Router::add('POST', '/ideias', function (array $p, array $u) {
    $t = trim((string) ($_POST['title'] ?? ''));
    $n = trim((string) ($_POST['notes'] ?? ''));
    if ($t === '' || mb_strlen($t) > 255 || mb_strlen($n) > 10000) {
        flash('Escreva a ideia (até 255 letras no título).', 'erro');
    } else {
        Db::run('INSERT INTO ideas (title, notes, created_by) VALUES (?,?,?)', [$t, $n !== '' ? $n : null, (int) $u['id']]);
        Audit::registrar('criar', 'postagem', null, 'Registrou a ideia de conteúdo ' . $t, null, $u);
        flash('Ideia guardada.');
    }
    redirect('/ideias');
}, ['papel' => 'equipe']);

Router::add('POST', '/ideias/(?P<id>\d+)/excluir', function (array $p, array $u) {
    Db::run('DELETE FROM ideas WHERE id = ?', [(int) $p['id']]);
    flash('Ideia apagada.');
    redirect('/ideias');
}, ['papel' => 'equipe']);

// -------------------------------------------- administrador: listas e cores
Router::add('GET', '/admin/postagens', function (array $p, array $u) {
    $blocos = [];
    foreach (Postagens::LISTAS as $k => $cfg) {
        $itens = Postagens::opcoes($k);
        foreach ($itens as &$i) {
            $i['usos'] = Postagens::usos((int) $i['id'], $k);
        }
        $blocos[$k] = ['cfg' => $cfg, 'itens' => $itens];
    }
    View::render('admin/postagens_listas', ['titulo' => 'Cronograma: listas e cores', 'blocos' => $blocos]);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/postagens/(?P<kind>[a-z]+)', function (array $p, array $u) {
    $k = $p['kind'];
    if (!isset(Postagens::LISTAS[$k])) {
        View::naoEncontrado();
    }
    $nome = trim((string) ($_POST['name'] ?? ''));
    if ($nome === '' || mb_strlen($nome) > 60) {
        flash('Informe um nome (até 60 letras).', 'erro');
    } elseif (Db::value('SELECT 1 FROM post_options WHERE kind = ? AND name = ?', [$k, $nome])) {
        flash('Já existe um item com esse nome nessa lista.', 'erro');
    } else {
        $ordem = (int) Db::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM post_options WHERE kind = ?', [$k]);
        Db::run('INSERT INTO post_options (kind, name, color, sort_order) VALUES (?,?,?,?)', [$k, $nome, cor_valida($_POST['color'] ?? null), $ordem]);
        Audit::registrar('criar', 'configuracao', Db::insertId(), 'Cronograma: novo item “' . $nome . '” em ' . Postagens::LISTAS[$k][0], null, $u);
        flash('Item criado.');
    }
    redirect('/admin/postagens#' . $k);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/postagens/item/(?P<id>\d+)', function (array $p, array $u) {
    $o = Postagens::opcao((int) $p['id']);
    if (!$o) {
        View::naoEncontrado();
    }
    $nome = trim((string) ($_POST['name'] ?? ''));
    $ordem = max(0, min(999, (int) ($_POST['sort_order'] ?? 0)));
    if ($nome === '' || mb_strlen($nome) > 60) {
        flash('Informe um nome (até 60 letras).', 'erro');
    } elseif (Db::value('SELECT 1 FROM post_options WHERE kind = ? AND name = ? AND id <> ?', [$o['kind'], $nome, $o['id']])) {
        flash('Já existe um item com esse nome nessa lista.', 'erro');
    } else {
        Db::run('UPDATE post_options SET name = ?, color = ?, sort_order = ? WHERE id = ?', [$nome, cor_valida($_POST['color'] ?? null), $ordem, $o['id']]);
        Audit::registrar('editar', 'configuracao', (int) $o['id'], 'Cronograma: editou “' . $o['name'] . '” em ' . Postagens::LISTAS[$o['kind']][0], null, $u);
        flash('Item salvo.');
    }
    redirect('/admin/postagens#' . $o['kind']);
}, ['papel' => 'admin']);

Router::add('POST', '/admin/postagens/item/(?P<id>\d+)/excluir', function (array $p, array $u) {
    $o = Postagens::opcao((int) $p['id']);
    if (!$o) {
        View::naoEncontrado();
    }
    if (Postagens::usos((int) $o['id'], $o['kind']) > 0) {
        flash('Esse item está em uso por postagens. Troque nas postagens antes de excluir.', 'erro');
    } else {
        Db::run('DELETE FROM post_options WHERE id = ?', [$o['id']]);
        Audit::registrar('excluir', 'configuracao', (int) $o['id'], 'Cronograma: excluiu “' . $o['name'] . '” de ' . Postagens::LISTAS[$o['kind']][0], null, $u);
        flash('Item excluído.');
    }
    redirect('/admin/postagens#' . $o['kind']);
}, ['papel' => 'admin']);
