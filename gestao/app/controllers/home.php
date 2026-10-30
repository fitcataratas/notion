<?php
declare(strict_types=1);

Router::add('GET', '/', function (array $p, array $u) {
    if (Perm::externo($u)) {
        redirect('/espaco');
    }
    $hoje = date('Y-m-d');
    $minhas = Tarefas::consultar(['mine' => 1, 'status' => 'abertas'], $u, 'data', 'asc', 40);
    $contagem = Tarefas::contarPorStatus(['mine' => 1], $u);
    $proximas = Tarefas::consultar([], $u, 'data', 'asc', 15, 0, $hoje, date('Y-m-d', strtotime('+14 days')));
    $datas = (string) Db::value("SELECT v FROM settings WHERE k = 'datas_importantes'");
    View::render('home', [
        'titulo' => 'Início',
        'minhas' => $minhas['linhas'],
        'minhas_total' => $minhas['total'],
        'contagem' => $contagem,
        'proximas' => $proximas['linhas'],
        'datas' => $datas,
        'hoje' => $hoje,
    ]);
});
