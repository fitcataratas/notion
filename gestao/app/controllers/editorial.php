<?php
declare(strict_types=1);

// Dashboard Editorial (Marketing). Equipe e administradores; perfis só os administradores.
$dadosEd = fn() => ['perfis' => Editorial::perfis(), 'pessoas' => Usuarios::todos(true)];

Router::add('GET', '/editorial', function (array $p, array $u) use ($dadosEd) {
    $visao = ($_GET['view'] ?? '') === 'lista' ? 'lista' : 'semana';
    $f = Editorial::filtros($_GET);
    $hoje = date('Y-m-d');
    $dados = $dadosEd() + ['titulo' => 'Dashboard Editorial', 'visao' => $visao, 'f' => $f, 'hoje' => $hoje,
        'agrupar' => ($_GET['agrupar'] ?? '') === 'rede' ? 'rede' : 'perfil'];
    // Os números do topo respeitam os filtros de perfil e rede (o de status só filtra a lista).
    $contagem = Editorial::consultar(array_diff_key($f, ['status' => 1]));
    $dados['contagem'] = array_count_values(array_column($contagem, 'status'));
    $dados['atrasados'] = count(array_filter($contagem, fn($r) => Editorial::atrasado($r, $hoje)));
    $dados['total_geral'] = (int) Db::value('SELECT COUNT(*) FROM editorial_posts');
    if ($visao === 'semana') {
        $ref = data_valida($_GET['semana'] ?? '') ? new DateTimeImmutable($_GET['semana']) : new DateTimeImmutable($hoje);
        $seg = $ref->modify('-' . ((int) $ref->format('N') - 1) . ' days');
        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $dias[] = $seg->modify("+$i days");
        }
        $dados += ['dias' => $dias, 'seg' => $seg, 'ant' => $seg->modify('-7 days')->format('Y-m-d'), 'prox' => $seg->modify('+7 days')->format('Y-m-d'),
            'linhas' => Editorial::consultar($f, $dias[0]->format('Y-m-d'), $dias[6]->format('Y-m-d'))];
    } else {
        $dados['linhas'] = Editorial::consultar($f);
    }
    View::render('editorial/painel', $dados);
}, ['papel' => 'equipe']);

Router::add('GET', '/editorial/novo', function (array $p, array $u) use ($dadosEd) {
    $v = ['status' => 'Ideia', 'pub_date' => data_valida($_GET['data'] ?? '') ? $_GET['data'] : date('Y-m-d'),
        'profile_id' => ctype_digit((string) ($_GET['perfil'] ?? '')) ? (int) $_GET['perfil'] : '',
        'network' => in_array($_GET['rede'] ?? '', Editorial::REDES, true) ? $_GET['rede'] : Editorial::REDES[0],
        'format' => Editorial::FORMATOS[0], 'owner_id' => ''];
    View::render('editorial/form', $dadosEd() + ['titulo' => 'Novo conteúdo', 'v' => $v, 'id' => null, 'erros' => [], 'volta' => volta_segura($_GET['volta'] ?? '/editorial', '/editorial')]);
}, ['papel' => 'equipe']);

Router::add('POST', '/editorial', function (array $p, array $u) use ($dadosEd) {
    [$d, $erros] = Editorial::validar($_POST);
    $volta = volta_segura($_POST['volta'] ?? '/editorial', '/editorial');
    if ($erros) {
        http_response_code(422);
        View::render('editorial/form', $dadosEd() + ['titulo' => 'Novo conteúdo', 'v' => $d, 'id' => null, 'erros' => $erros, 'volta' => $volta]);
        return;
    }
    Editorial::salvar(null, $d, $u);
    flash('Conteúdo criado.');
    redirect($volta);
}, ['papel' => 'equipe']);

Router::add('GET', '/editorial/(?P<id>\d+)', function (array $p, array $u) use ($dadosEd) {
    $v = Editorial::obter((int) $p['id']);
    if (!$v) {
        View::naoEncontrado();
    }
    View::render('editorial/form', $dadosEd() + ['titulo' => $v['title'], 'v' => $v, 'id' => (int) $v['id'], 'erros' => [], 'volta' => volta_segura($_GET['volta'] ?? '/editorial', '/editorial')]);
}, ['papel' => 'equipe']);

Router::add('POST', '/editorial/(?P<id>\d+)', function (array $p, array $u) use ($dadosEd) {
    $id = (int) $p['id'];
    if (!Editorial::obter($id)) {
        View::naoEncontrado();
    }
    [$d, $erros] = Editorial::validar($_POST);
    $volta = volta_segura($_POST['volta'] ?? '/editorial', '/editorial');
    if ($erros) {
        http_response_code(422);
        View::render('editorial/form', $dadosEd() + ['titulo' => 'Editar conteúdo', 'v' => $d, 'id' => $id, 'erros' => $erros, 'volta' => $volta]);
        return;
    }
    Editorial::salvar($id, $d, $u);
    flash('Conteúdo salvo.');
    redirect($volta);
}, ['papel' => 'equipe']);

Router::add('POST', '/editorial/(?P<id>\d+)/status', function (array $p, array $u) {
    if (!Editorial::mudarStatus((int) $p['id'], (string) ($_POST['status'] ?? ''), $u)) {
        View::naoEncontrado();
    }
    flash('Status atualizado.');
    redirect(volta_segura($_POST['volta'] ?? '/editorial', '/editorial'));
}, ['papel' => 'equipe']);

Router::add('POST', '/editorial/(?P<id>\d+)/excluir', function (array $p, array $u) {
    if (!Editorial::obter((int) $p['id'])) {
        View::naoEncontrado();
    }
    Editorial::excluir((int) $p['id'], $u);
    flash('Conteúdo excluído.');
    redirect(volta_segura($_POST['volta'] ?? '/editorial', '/editorial'));
}, ['papel' => 'admin']);

// ------------------------------------------------ perfis (só administradores)
Router::add('GET', '/admin/editorial', function (array $p, array $u) {
    View::render('admin/editorial_perfis', ['titulo' => 'Dashboard Editorial: perfis', 'perfis' => Editorial::perfis()]);
}, ['papel' => 'admin']);

$validarPerfil = function (array $in): array {
    $nome = trim((string) ($in['name'] ?? ''));
    $cor = cor_valida((string) ($in['color'] ?? ''));
    return [$nome, $cor, $nome === '' || mb_strlen($nome) > 80];
};

Router::add('POST', '/admin/editorial', function (array $p, array $u) use ($validarPerfil) {
    [$nome, $cor, $ruim] = $validarPerfil($_POST);
    if ($ruim) {
        flash('Dê um nome ao perfil (até 80 letras).', 'erro');
    } else {
        Db::run('INSERT INTO editorial_profiles (name, color, sort_order) VALUES (?,?, (SELECT COALESCE(MAX(s.sort_order),0)+1 FROM editorial_profiles s))', [$nome, $cor]);
        Audit::registrar('criar', 'editorial', null, 'Criou o perfil editorial ' . $nome, null, $u);
        flash('Perfil incluído.');
    }
    redirect('/admin/editorial');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/editorial/(?P<id>\d+)', function (array $p, array $u) use ($validarPerfil) {
    if (!Db::value('SELECT 1 FROM editorial_profiles WHERE id = ?', [(int) $p['id']])) {
        View::naoEncontrado();
    }
    [$nome, $cor, $ruim] = $validarPerfil($_POST);
    if ($ruim) {
        flash('Dê um nome ao perfil (até 80 letras).', 'erro');
    } else {
        Db::run('UPDATE editorial_profiles SET name = ?, color = ? WHERE id = ?', [$nome, $cor, (int) $p['id']]);
        Audit::registrar('editar', 'editorial', (int) $p['id'], 'Editou o perfil editorial ' . $nome, null, $u);
        flash('Perfil salvo.');
    }
    redirect('/admin/editorial');
}, ['papel' => 'admin']);

Router::add('POST', '/admin/editorial/(?P<id>\d+)/excluir', function (array $p, array $u) {
    $n = Db::value('SELECT name FROM editorial_profiles WHERE id = ?', [(int) $p['id']]);
    if ($n === null) {
        View::naoEncontrado();
    }
    Db::run('DELETE FROM editorial_profiles WHERE id = ?', [(int) $p['id']]);
    Audit::registrar('excluir', 'editorial', (int) $p['id'], 'Removeu o perfil editorial ' . $n . ' (e seus conteúdos)', null, $u);
    flash('Perfil removido.');
    redirect('/admin/editorial');
}, ['papel' => 'admin']);
