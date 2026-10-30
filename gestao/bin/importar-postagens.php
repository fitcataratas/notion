<?php
declare(strict_types=1);
/**
 * Traz as 68 postagens e as ideias de conteúdo do backup do Notion.
 *
 *   docker compose exec app php bin/importar-postagens.php
 *
 * Pode rodar mais de uma vez: postagens com o mesmo título e a mesma data não são repetidas.
 */
require __DIR__ . '/../app/src/Env.php';
require __DIR__ . '/../app/src/Db.php';

$pasta = __DIR__ . '/../database/dados-importacao/';
$posts = json_decode((string) file_get_contents($pasta . 'postagens.json'), true);
$ideias = json_decode((string) file_get_contents($pasta . 'ideias.json'), true);
if (!is_array($posts) || !is_array($ideias)) {
    fwrite(STDERR, "Arquivos de dados não encontrados em database/dados-importacao.\n");
    exit(1);
}

$norm = fn(string $s): string => strtolower(preg_replace('/[^a-z0-9]+/i', '', iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s));
$cache = [];
$opcao = function (string $kind, ?string $nome) use (&$cache, $norm): ?int {
    if ($nome === null || trim($nome) === '') {
        return null;
    }
    if (!isset($cache[$kind])) {
        $cache[$kind] = [];
        foreach (Db::all('SELECT id, name FROM post_options WHERE kind = ?', [$kind]) as $o) {
            $cache[$kind][$norm($o['name'])] = (int) $o['id'];
        }
    }
    $k = $norm($nome);
    if (!isset($cache[$kind][$k])) {
        $ordem = (int) Db::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM post_options WHERE kind = ?', [$kind]);
        Db::run('INSERT INTO post_options (kind, name, color, sort_order) VALUES (?,?,?,?)', [$kind, trim($nome), '#64748b', $ordem]);
        $cache[$kind][$k] = Db::insertId();
        echo "  novo item em $kind: " . trim($nome) . "\n";
    }
    return $cache[$kind][$k];
};
$usuarios = [];
foreach (Db::all('SELECT id, name FROM users') as $u) {
    $usuarios[$norm($u['name'])] = (int) $u['id'];
}

$pdo = Db::pdo();
$pdo->beginTransaction();
try {
    $novas = 0;
    $puladas = 0;
    $semPessoa = [];
    foreach ($posts as $x) {
        $ja = Db::value('SELECT id FROM posts WHERE title = ? AND ((post_date IS NULL AND ? IS NULL) OR post_date = ?)', [$x['titulo'], $x['data'], $x['data']]);
        if ($ja) {
            $puladas++;
            continue;
        }
        // O projeto é de escolha única. Se o Notion tinha mais de um, fica o primeiro e os outros vão anotados no conteúdo.
        $projs = array_values(array_filter(array_map('trim', explode(',', (string) $x['projeto']))));
        $x['projeto'] = $projs[0] ?? null;
        if (count($projs) > 1) {
            $x['conteudo'] = trim(($x['conteudo'] ?? '') . "\n\n(No Notion esta postagem também era dos projetos: " . implode(', ', array_slice($projs, 1)) . '.)');
            echo '  ' . $x['titulo'] . ': ficou o projeto ' . $projs[0] . '; os outros foram anotados no conteúdo.' . "\n";
        }
        Db::run(
            'INSERT INTO posts (title, post_date, project_id, type_id, format_id, status_id, objective, content) VALUES (?,?,?,?,?,?,?,?)',
            [$x['titulo'], $x['data'], $opcao('projeto', $x['projeto']), $opcao('tipo', $x['tipo']), $opcao('formato', $x['formato']), $opcao('situacao', $x['situacao']), $x['objetivo'], $x['conteudo']]
        );
        $id = Db::insertId();
        foreach (array_unique(array_map(fn($n) => $opcao('plataforma', $n), $x['plataformas'])) as $oid) {
            if ($oid) {
                Db::run('INSERT IGNORE INTO post_platforms (post_id, option_id) VALUES (?,?)', [$id, $oid]);
            }
        }
        foreach ($x['profissionais'] as $nome) {
            $uid = $usuarios[$norm($nome)] ?? null;
            if ($uid) {
                Db::run('INSERT IGNORE INTO post_people (post_id, user_id) VALUES (?,?)', [$id, $uid]);
            } else {
                $semPessoa[$nome] = true;
            }
        }
        $novas++;
    }
    $ni = 0;
    foreach ($ideias as $i) {
        if (Db::value('SELECT 1 FROM ideas WHERE title = ?', [$i['titulo']])) {
            continue;
        }
        Db::run('INSERT INTO ideas (title, used, created_at) VALUES (?,?,COALESCE(?, NOW()))', [$i['titulo'], $i['usada'] ? 1 : 0, $i['criado']]);
        $ni++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "Postagens novas: $novas (já existiam: $puladas). Ideias novas: $ni.\n";
if ($semPessoa) {
    echo 'Aviso: estas pessoas não foram achadas em Usuários e ficaram sem vínculo: ' . implode(', ', array_keys($semPessoa)) . "\n";
}
