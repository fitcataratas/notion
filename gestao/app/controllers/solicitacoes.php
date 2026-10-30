<?php
declare(strict_types=1);

// Pedidos de Marketing e Design (memorial, seção 7): cada pedido é UMA tarefa na Central.
// Qualquer pessoa com login pode pedir; quem é externo só enxerga os pedidos que fez.

Router::add('GET', '/solicitacoes', function (array $p, array $u) {
    $f = ['solicitacao' => 1];
    if (!empty($_GET['minhas']) || Perm::externo($u)) {
        $f['solicitante_eu'] = 1;
    }
    if (!empty($_GET['projeto']) && ctype_digit((string) $_GET['projeto'])) {
        $f['projeto'] = (int) $_GET['projeto'];
    }
    $q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    if ($q !== '') {
        $f['q'] = $q;
    }
    $r = Tarefas::consultar($f, $u, 'data', 'asc', 600);
    $por = array_fill_keys(Config::ETAPAS_MKT, []);
    foreach ($r['linhas'] as $l) {
        $por[$l['mkt_stage']][] = $l;
    }
    View::render('solicitacoes/lista', [
        'titulo' => Perm::externo($u) ? 'Meus pedidos' : 'Solicitações de Marketing e Design',
        'colunas' => $por, 'total' => $r['total'], 'projetos' => Usuarios::projetos(),
        'f' => ['minhas' => !empty($_GET['minhas']) ? 1 : 0, 'projeto' => $f['projeto'] ?? 0, 'q' => $q],
        'hoje' => date('Y-m-d'),
    ]);
});

function dados_formulario_pedido(): array
{
    return ['setores' => Usuarios::setores(), 'projetos' => Usuarios::projetos()];
}

Router::add('GET', '/solicitacoes/nova', function (array $p, array $u) {
    View::render('solicitacoes/nova', ['titulo' => 'Nova solicitação', 'erros' => [], 'v' => []] + dados_formulario_pedido());
});

Router::add('POST', '/solicitacoes', function (array $p, array $u) {
    [$id, $erros] = Tarefas::criarSolicitacao($_POST, $u);
    if ($erros) {
        http_response_code(422);
        View::render('solicitacoes/nova', ['titulo' => 'Nova solicitação', 'erros' => $erros, 'v' => $_POST] + dados_formulario_pedido());
        return;
    }
    Audit::registrar('criar', 'tarefa', $id, 'Enviou uma solicitação de Marketing e Design', null, $u);
    flash('Pedido enviado! O Marketing vai avaliar, definir responsável e prazo e avisar aqui se precisar de mais informações.');
    redirect('/solicitacoes?minhas=1');
});

Router::add('POST', '/solicitacoes/(?P<id>\d+)/etapa', function (array $p, array $u) {
    $id = (int) $p['id'];
    if (!Tarefas::mudarEtapa($id, (string) ($_POST['etapa'] ?? ''), $u)) {
        View::naoEncontrado();
    }
    flash('Etapa atualizada.');
    redirect(volta_segura($_POST['volta'] ?? '/solicitacoes'));
}, ['papel' => 'equipe']);
