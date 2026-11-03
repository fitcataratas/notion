<?php
declare(strict_types=1);

// Arrastar cartões no calendário para outro dia (JSON). Equipe e administradores.
$lerDias = function (): array {
    $c = json_decode((string) file_get_contents('php://input'), true);
    $de = is_array($c) ? (string) ($c['de'] ?? '') : '';
    $para = is_array($c) ? (string) ($c['para'] ?? '') : '';
    if (!data_valida($de) || !data_valida($para)) {
        View::json(['erro' => 'Data inválida.'], 422);
    }
    return [$de, $para, (int) (new DateTimeImmutable($de))->diff(new DateTimeImmutable($para))->format('%r%a')];
};

Router::add('POST', '/api/tarefas/(?P<id>\d+)/data', function (array $p, array $u) use ($lerDias) {
    [, $para, $dias] = $lerDias();
    if (!Tarefas::moverDias((int) $p['id'], $dias, $u)) {
        View::json(['erro' => 'Não foi possível mudar a data.'], 422);
    }
    View::json(['ok' => true, 'mensagem' => 'Data movida para ' . data_br($para) . '.']);
}, ['papel' => 'equipe', 'api' => true]);

Router::add('POST', '/api/postagens/(?P<id>\d+)/data', function (array $p, array $u) use ($lerDias) {
    [, $para] = $lerDias();
    $t = Db::one('SELECT title, post_date FROM posts WHERE id = ?', [(int) $p['id']]);
    if (!$t) {
        View::json(['erro' => 'Postagem não encontrada.'], 404);
    }
    Db::run('UPDATE posts SET post_date = ? WHERE id = ?', [$para, (int) $p['id']]);
    Audit::registrar('editar', 'postagem', (int) $p['id'], 'Mudou a data de "' . $t['title'] . '" no calendário', ['Data' => [$t['post_date'] ? data_br($t['post_date']) : '—', data_br($para)]], $u);
    View::json(['ok' => true, 'mensagem' => 'Postagem movida para ' . data_br($para) . '.']);
}, ['papel' => 'equipe', 'api' => true]);

Router::add('POST', '/api/reunioes/(?P<id>\d+)/data', function (array $p, array $u) use ($lerDias) {
    [, $para] = $lerDias();
    $t = Db::one('SELECT title, meeting_date FROM meetings WHERE id = ?', [(int) $p['id']]);
    if (!$t) {
        View::json(['erro' => 'Reunião não encontrada.'], 404);
    }
    Db::run('UPDATE meetings SET meeting_date = ? WHERE id = ?', [$para, (int) $p['id']]);
    Audit::registrar('editar', 'reuniao', (int) $p['id'], 'Mudou a data de "' . $t['title'] . '" no calendário', ['Data' => [$t['meeting_date'] ? data_br($t['meeting_date']) : '—', data_br($para)]], $u);
    View::json(['ok' => true, 'mensagem' => 'Reunião movida para ' . data_br($para) . '.']);
}, ['papel' => 'equipe', 'api' => true]);

Router::add('POST', '/api/editorial/(?P<id>\d+)/data', function (array $p, array $u) use ($lerDias) {
    [, $para] = $lerDias();
    $t = Db::one('SELECT title, pub_date FROM editorial_posts WHERE id = ?', [(int) $p['id']]);
    if (!$t) {
        View::json(['erro' => 'Conteúdo não encontrado.'], 404);
    }
    Db::run('UPDATE editorial_posts SET pub_date = ? WHERE id = ?', [$para, (int) $p['id']]);
    Audit::registrar('editar', 'editorial', (int) $p['id'], 'Mudou a data de "' . $t['title'] . '" no quadro', ['Data' => [data_br($t['pub_date']), data_br($para)]], $u);
    View::json(['ok' => true, 'mensagem' => 'Conteúdo movido para ' . data_br($para) . '.']);
}, ['papel' => 'equipe', 'api' => true]);
