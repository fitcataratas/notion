<?php
declare(strict_types=1);

/** Dados comuns às telas de tarefa: setores, projetos e pessoas para filtros e formulário. */
function dados_apoio(): array
{
    return [
        'setores' => Usuarios::setores(),
        'projetos' => Usuarios::projetos(),
        'pessoas' => Usuarios::todos(true),
    ];
}

function mes_valido(?string $m): string
{
    $m = (string) $m;
    if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) {
        return $m;
    }
    return date('Y-m');
}

// --------------------------------------------------------------- lista (3 visões)
$listar = function (array $p, array $u, array $forcar = []) {
    $visao = in_array($_GET['view'] ?? '', ['quadro', 'tabela', 'calendario'], true) ? $_GET['view'] : 'quadro';
    $q = array_merge($_GET, $forcar);
    $f = Tarefas::filtros($q);
    $dados = dados_apoio() + ['f' => $f, 'visao' => $visao, 'titulo' => !empty($forcar['mine']) ? 'Minhas tarefas' : 'Central de Tarefas'];
    $dados['so_minhas'] = !empty($forcar['mine']);

    if ($visao === 'quadro') {
        $r = Tarefas::consultar($f, $u, 'data', 'asc', 600);
        $por = array_fill_keys(Config::STATUS, []);
        foreach ($r['linhas'] as $l) {
            $por[$l['status']][] = $l;
        }
        $dados += ['colunas' => $por, 'total' => $r['total']];
    } elseif ($visao === 'tabela') {
        $ordem = array_key_exists($_GET['ordem'] ?? '', ['atividade' => 1, 'status' => 1, 'data' => 1, 'prioridade' => 1, 'atualizado' => 1]) ? $_GET['ordem'] : 'data';
        $dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $porPagina = 50;
        $pag = max(1, (int) ($_GET['pag'] ?? 1));
        $r = Tarefas::consultar($f, $u, $ordem, $dir, $porPagina, ($pag - 1) * $porPagina);
        $dados += ['linhas' => $r['linhas'], 'total' => $r['total'], 'ordem' => $ordem, 'dir' => $dir, 'pag' => $pag, 'paginas' => max(1, (int) ceil($r['total'] / $porPagina))];
    } else {
        $mes = mes_valido($_GET['mes'] ?? null);
        $primeiro = new DateTimeImmutable("$mes-01");
        $ultimo = $primeiro->modify('last day of this month');
        $ini = $primeiro->modify('-' . (int) $primeiro->format('w') . ' days');            // domingo da 1ª semana
        $fim = $ultimo->modify('+' . (6 - (int) $ultimo->format('w')) . ' days');            // sábado da última semana
        $r = Tarefas::consultar($f, $u, 'data', 'asc', 1500, 0, $ini->format('Y-m-d'), $fim->format('Y-m-d'));
        $porDia = [];
        foreach ($r['linhas'] as $l) {
            $a = new DateTimeImmutable($l['start_date']);
            $b = new DateTimeImmutable($l['end_date'] ?: $l['start_date']);
            for ($d = max($a, $ini); $d <= min($b, $fim); $d = $d->modify('+1 day')) {
                $porDia[$d->format('Y-m-d')][] = $l;
            }
        }
        $semData = Tarefas::consultar(array_merge($f, ['sem_data' => 1]), $u, 'atualizado', 'desc', 1)['total'];
        $dados += [
            'mes' => $mes, 'primeiro' => $primeiro, 'ini' => $ini, 'fim' => $fim, 'por_dia' => $porDia,
            'anterior' => $primeiro->modify('-1 month')->format('Y-m'), 'proximo' => $primeiro->modify('+1 month')->format('Y-m'),
            'sem_data_total' => $semData,
        ];
    }
    View::render('tarefas/lista', $dados);
};

Router::add('GET', '/tarefas', fn(array $p, array $u) => $listar($p, $u));
Router::add('GET', '/minhas-tarefas', fn(array $p, array $u) => $listar($p, $u, ['mine' => 1]));

// ------------------------------------------------------------- nova / editar
Router::add('GET', '/tarefas/nova', function (array $p, array $u) {
    $inicial = ['status' => 'A fazer', 'responsaveis' => [], 'solicitantes' => [], 'aprovadores' => [], 'setores' => [], 'setores_solicitantes' => [], 'projetos' => []];
    foreach (['setor' => 'setores', 'projeto' => 'projetos'] as $q => $campo) {
        if (!empty($_GET[$q]) && ctype_digit((string) $_GET[$q])) {
            $inicial[$campo] = [['id' => (int) $_GET[$q]]];
        }
    }
    if (!empty($_GET['data']) && data_valida($_GET['data'])) {
        $inicial['start_date'] = $_GET['data'];
    }
    View::render('tarefas/form', dados_apoio() + ['titulo' => 'Nova tarefa', 't' => $inicial, 'id' => null, 'erros' => [], 'pode_editar' => true, 'volta' => volta_segura($_GET['volta'] ?? '/tarefas')]);
}, ['papel' => 'equipe']);

/** Transforma o objeto da tarefa (com listas de itens) no formato que o formulário reenvia. */
function tarefa_para_form(array $t): array
{
    foreach (['setores', 'setores_solicitantes', 'projetos', 'responsaveis', 'solicitantes', 'aprovadores'] as $k) {
        $t[$k] = $t[$k] ?? [];
    }
    return $t;
}

Router::add('POST', '/tarefas', function (array $p, array $u) {
    [$d, $erros] = Tarefas::validar($_POST);
    if ($erros) {
        http_response_code(422);
        $t = $d + ['responsaveis' => [], 'solicitantes' => [], 'aprovadores' => [], 'setores' => [], 'setores_solicitantes' => [], 'projetos' => []];
        foreach (['setores' => '_setores', 'setores_solicitantes' => '_setores_solicitantes', 'projetos' => '_projetos', 'responsaveis' => '_responsaveis', 'solicitantes' => '_solicitantes', 'aprovadores' => '_aprovadores'] as $k => $src) {
            $t[$k] = array_map(fn($i) => ['id' => $i], $d[$src]);
        }
        View::render('tarefas/form', dados_apoio() + ['titulo' => 'Nova tarefa', 't' => $t, 'id' => null, 'erros' => $erros, 'pode_editar' => true, 'volta' => volta_segura($_POST['volta'] ?? '/tarefas')]);
        return;
    }
    $id = Tarefas::salvar(null, $d, $u);
    Tarefas::regrasEtapa($id, null, $u);
    flash('Tarefa criada.');
    redirect('/tarefas/' . $id);
}, ['papel' => 'equipe']);

Router::add('GET', '/tarefas/(?P<id>\d+)', function (array $p, array $u) {
    $id = (int) $p['id'];
    $t = Tarefas::obter($id);
    if (!$t || !Perm::veTarefa($u, $id)) {
        View::naoEncontrado();
    }
    View::render('tarefas/form', dados_apoio() + [
        'titulo' => $t['title'], 't' => tarefa_para_form($t), 'id' => $id, 'erros' => [],
        'pode_editar' => Perm::editaTarefa($u), 'pode_status' => Perm::mudaStatus($u, $id),
        'comentarios' => Tarefas::comentarios($id), 'historico' => Tarefas::historico($id),
        'volta' => volta_segura($_GET['volta'] ?? '/tarefas'),
    ]);
});

Router::add('POST', '/tarefas/(?P<id>\d+)', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Tarefas::obter($id)) {
        View::naoEncontrado();
    }
    [$d, $erros] = Tarefas::validar($_POST);
    if ($erros) {
        http_response_code(422);
        $t = $d + ['id' => $id];
        foreach (['setores' => '_setores', 'setores_solicitantes' => '_setores_solicitantes', 'projetos' => '_projetos', 'responsaveis' => '_responsaveis', 'solicitantes' => '_solicitantes', 'aprovadores' => '_aprovadores'] as $k => $src) {
            $t[$k] = array_map(fn($i) => ['id' => $i], $d[$src]);
        }
        View::render('tarefas/form', dados_apoio() + [
            'titulo' => 'Editar tarefa', 't' => $t, 'id' => $id, 'erros' => $erros, 'pode_editar' => true,
            'comentarios' => Tarefas::comentarios($id), 'historico' => Tarefas::historico($id), 'volta' => volta_segura($_POST['volta'] ?? '/tarefas'),
        ]);
        return;
    }
    $etapaAntes = Db::value('SELECT mkt_stage FROM tasks WHERE id = ?', [$id]);
    Tarefas::salvar($id, $d, $u);
    Tarefas::regrasEtapa($id, $etapaAntes === null ? null : (string) $etapaAntes, $u);
    flash('Alterações salvas.');
    redirect('/tarefas/' . $id . '?' . http_build_query(['volta' => volta_segura($_POST['volta'] ?? '/tarefas')]));
}, ['papel' => 'equipe']);

// --------------------------------------------------------- status e comentários
Router::add('POST', '/tarefas/(?P<id>\d+)/status', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Perm::mudaStatus($u, $id) || !Tarefas::mudarStatus($id, (string) ($_POST['status'] ?? ''), $u)) {
        View::naoEncontrado();
    }
    flash('Status atualizado.');
    redirect(volta_segura($_POST['volta'] ?? '/tarefas/' . $id));
});

Router::add('POST', '/tarefas/(?P<id>\d+)/comentarios', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Tarefas::obter($id) || !Perm::veTarefa($u, $id)) {
        View::naoEncontrado();
    }
    $texto = trim((string) ($_POST['texto'] ?? ''));
    if ($texto === '' || mb_strlen($texto) > 5000) {
        flash('Escreva um comentário (até 5.000 letras).', 'erro');
    } else {
        Tarefas::comentar($id, $texto, $u);
        flash('Comentário publicado.');
    }
    redirect('/tarefas/' . $id . '#comentarios');
});

Router::add('POST', '/tarefas/(?P<id>\d+)/excluir', function (array $p, array $u) {
    Tarefas::excluir((int) $p['id'], $u);
    flash('Tarefa excluída.');
    redirect('/tarefas');
}, ['papel' => 'admin']);

// ---------------------------------------------------- arrastar no quadro (JSON)
Router::add('POST', '/api/tarefas/(?P<id>\d+)/status', function (array $p, array $u) {
    $id = (int) $p['id'];
    $corpo = json_decode((string) file_get_contents('php://input'), true);
    $status = is_array($corpo) ? (string) ($corpo['status'] ?? '') : '';
    if (!Perm::mudaStatus($u, $id)) {
        View::json(['erro' => 'Sem permissão para esta tarefa.'], 403);
    }
    if (!Tarefas::mudarStatus($id, $status, $u)) {
        View::json(['erro' => 'Status inválido.'], 422);
    }
    View::json(['ok' => true, 'status' => $status]);
}, ['api' => true]);
