<?php
declare(strict_types=1);
/**
 * Traz as 20 ideias e as pautas do Blog do backup do Notion.
 *
 *   docker compose exec app php bin/importar-blog.php
 *
 * Pode rodar de novo sem duplicar (ideias e pautas com o mesmo título são puladas).
 * Cada pauta é ligada à ideia de mesmo título.
 */
require __DIR__ . '/../app/src/Env.php';
require __DIR__ . '/../app/src/Db.php';

$pasta = __DIR__ . '/../database/dados-importacao/';
$ideias = json_decode((string) file_get_contents($pasta . 'blog_ideias.json'), true);
$pautas = json_decode((string) file_get_contents($pasta . 'blog_pautas.json'), true);
if (!is_array($ideias) || !is_array($pautas)) {
    fwrite(STDERR, "Arquivos de dados não encontrados em database/dados-importacao.\n");
    exit(1);
}
$validas = ['Sem Avaliação', 'Em revisão', 'Aprovado', 'Reprovado', 'Cancelado', 'Publicado'];
$pdo = Db::pdo();
$pdo->beginTransaction();
try {
    $ni = 0;
    foreach ($ideias as $i) {
        if (Db::value('SELECT 1 FROM blog_ideas WHERE title = ?', [$i['titulo']])) {
            continue;
        }
        $av = in_array($i['avaliacao'], $validas, true) ? $i['avaliacao'] : 'Sem Avaliação';
        Db::run('INSERT INTO blog_ideas (title, notes, evaluation) VALUES (?,?,?)', [$i['titulo'], $i['notas'], $av]);
        $ni++;
    }
    $np = 0;
    foreach ($pautas as $p) {
        if (Db::value('SELECT 1 FROM blog_pautas WHERE title = ?', [$p['titulo']])) {
            continue;
        }
        $idea = Db::value('SELECT id FROM blog_ideas WHERE title = ?', [$p['titulo']]);
        if ($idea && Db::value('SELECT 1 FROM blog_pautas WHERE idea_id = ?', [(int) $idea])) {
            $idea = null;
        }
        Db::run(
            'INSERT INTO blog_pautas (title, theme, audience, status, article_url, idea_id) VALUES (?,?,?,?,?,?)',
            [$p['titulo'], $p['tema'], $p['publico'], $p['status'], $p['link'], $idea ? (int) $idea : null]
        );
        $np++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "Ideias novas: $ni. Pautas novas: $np.\n";
