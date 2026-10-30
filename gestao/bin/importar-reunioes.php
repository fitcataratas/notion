<?php
declare(strict_types=1);
/**
 * Traz as 6 reuniões (atas) do backup do Notion.
 *
 *   docker compose exec app php bin/importar-reunioes.php
 *
 * Pode rodar de novo sem duplicar (mesmo título e data são pulados).
 */
require __DIR__ . '/../app/src/Env.php';
require __DIR__ . '/../app/src/Db.php';

$lista = json_decode((string) file_get_contents(__DIR__ . '/../database/dados-importacao/reunioes.json'), true);
if (!is_array($lista)) {
    fwrite(STDERR, "Arquivo de dados não encontrado.\n");
    exit(1);
}
$pdo = Db::pdo();
$pdo->beginTransaction();
try {
    $n = 0;
    foreach ($lista as $r) {
        if (Db::value('SELECT 1 FROM meetings WHERE title = ? AND meeting_date = ?', [$r['titulo'], $r['data']])) {
            continue;
        }
        $proj = $r['projeto'] ? Db::value('SELECT id FROM projects WHERE name = ?', [$r['projeto']]) : null;
        Db::run('INSERT INTO meetings (title, meeting_date, kind, project_id, minutes_url) VALUES (?,?,?,?,?)', [$r['titulo'], $r['data'], $r['tipo'], $proj ?: null, $r['ata']]);
        $id = Db::insertId();
        foreach ($r['setores'] as $s) {
            $sid = Db::value('SELECT id FROM sectors WHERE name = ?', [$s]);
            if ($sid) {
                Db::run('INSERT IGNORE INTO meeting_sectors (meeting_id, sector_id) VALUES (?,?)', [$id, (int) $sid]);
            }
        }
        $n++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "Reuniões novas: $n.\n";
