<?php
declare(strict_types=1);

// Base de Reuniões: histórico formal das reuniões e atas. Equipe e administradores.
$dadosReuniao = fn() => ['pessoas' => Usuarios::todos(true), 'setores' => Usuarios::setores(), 'projetos' => Usuarios::projetos()];

Router::add('GET', '/reunioes', function (array $p, array $u) use ($dadosReuniao) {
    $visoes = ['tabela', 'gerais', 'calendario', 'setor', 'minhas'];
    $visao = in_array($_GET['view'] ?? '', $visoes, true) ? $_GET['view'] : 'tabela';
    $f = Reunioes::filtros($_GET);
    if ($visao === 'gerais') {
        $f['tipo'] = 'Geral';
    }
    if ($visao === 'minhas') {
        $f['participante'] = (int) $u['id'];
    }
    $dados = $dadosReuniao() + ['titulo' => 'Reuniões', 'visao' => $visao, 'f' => $f];
    if ($visao === 'calendario') {
        $mes = mes_valido($_GET['mes'] ?? null);
        $primeiro = new DateTimeImmutable("$mes-01");
        $ultimo = $primeiro->modify('last day of this month');
        $ini = $primeiro->modify('-' . (int) $primeiro->format('w') . ' days');
        $fim = $ultimo->modify('+' . (6 - (int) $ultimo->format('w')) . ' days');
        $linhas = Reunioes::consultar($f, 'asc', $ini->format('Y-m-d'), $fim->format('Y-m-d'));
        $porDia = [];
        foreach ($linhas as $l) {
            $porDia[$l['meeting_date']][] = $l;
        }
        $dados += ['mes' => $mes, 'primeiro' => $primeiro, 'ini' => $ini, 'fim' => $fim, 'por_dia' => $porDia,
            'anterior' => $primeiro->modify('-1 month')->format('Y-m'), 'proximo' => $primeiro->modify('+1 month')->format('Y-m')];
    } else {
        $linhas = Reunioes::consultar($f, $visao === 'setor' ? 'desc' : 'desc');
        $dados['linhas'] = $linhas;
        if ($visao === 'setor') {
            $grupos = [];
            foreach (Usuarios::setores() as $s) {
                $grupos[(int) $s['id']] = ['setor' => $s, 'reunioes' => []];
            }
            $sem = [];
            foreach ($linhas as $l) {
                if (!$l['setores']) {
                    $sem[] = $l;
                }
                foreach ($l['setores'] as $s) {
                    $grupos[(int) $s['id']]['reunioes'][] = $l;
                }
            }
            $dados += ['grupos' => array_filter($grupos, fn($g) => $g['reunioes']), 'sem_setor' => $sem];
        }
    }
    View::render('reunioes/lista', $dados);
}, ['papel' => 'equipe']);

Router::add('GET', '/reunioes/nova', function (array $p, array $u) use ($dadosReuniao) {
    $v = ['kind' => 'Geral', '_setores' => [], '_participantes' => [], 'minutes_by' => (int) $u['id']];
    if (!empty($_GET['data']) && data_valida($_GET['data'])) {
        $v['meeting_date'] = $_GET['data'];
    }
    View::render('reunioes/form', $dadosReuniao() + ['titulo' => 'Nova reunião', 'v' => $v, 'id' => null, 'erros' => []]);
}, ['papel' => 'equipe']);

Router::add('POST', '/reunioes', function (array $p, array $u) use ($dadosReuniao) {
    [$d, $erros] = Reunioes::validar($_POST);
    if ($erros) {
        http_response_code(422);
        View::render('reunioes/form', $dadosReuniao() + ['titulo' => 'Nova reunião', 'v' => $d, 'id' => null, 'erros' => $erros]);
        return;
    }
    $id = Reunioes::salvar(null, $d, $u);
    flash('Reunião registrada.');
    redirect('/reunioes/' . $id);
}, ['papel' => 'equipe']);

Router::add('GET', '/reunioes/(?P<id>\d+)', function (array $p, array $u) use ($dadosReuniao) {
    $v = Reunioes::obter((int) $p['id']);
    if (!$v) {
        View::naoEncontrado();
    }
    View::render('reunioes/form', $dadosReuniao() + ['titulo' => $v['title'], 'v' => $v, 'id' => (int) $v['id'], 'erros' => []]);
}, ['papel' => 'equipe']);

Router::add('POST', '/reunioes/(?P<id>\d+)', function (array $p, array $u) use ($dadosReuniao) {
    $id = (int) $p['id'];
    if (!Reunioes::obter($id)) {
        View::naoEncontrado();
    }
    [$d, $erros] = Reunioes::validar($_POST);
    if ($erros) {
        http_response_code(422);
        View::render('reunioes/form', $dadosReuniao() + ['titulo' => 'Editar reunião', 'v' => $d, 'id' => $id, 'erros' => $erros]);
        return;
    }
    Reunioes::salvar($id, $d, $u);
    flash('Reunião salva.');
    redirect('/reunioes/' . $id);
}, ['papel' => 'equipe']);

Router::add('POST', '/reunioes/(?P<id>\d+)/excluir', function (array $p, array $u) {
    if (!Reunioes::obter((int) $p['id'])) {
        View::naoEncontrado();
    }
    Reunioes::excluir((int) $p['id'], $u);
    flash('Reunião excluída.');
    redirect('/reunioes');
}, ['papel' => 'admin']);
