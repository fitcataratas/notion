<?php
declare(strict_types=1);
/**
 * Preenche os responsáveis de cada setor (lista combinada com a direção).
 *
 *   docker compose exec app php bin/carregar-responsaveis.php
 *
 * Pode rodar de novo quando Mira e Maria forem cadastradas: o programa procura as pessoas pelo
 * primeiro nome e refaz a lista dos setores abaixo. Quem não for encontrado é avisado na tela.
 * Depois disso, o administrador muda tudo pelo próprio sistema (página do setor → Editar).
 */
require __DIR__ . '/../app/src/Env.php';
require __DIR__ . '/../app/src/Db.php';

$lista = [
    'Arquitetura' => ['Mira', 'Maria'],
    'Caravanas'   => ['Marcos'],
    'Comercial'   => ['Bruna', 'Milena'],
    'Coordenação' => ['Mayara', 'Jessica'],
    'Marketing'   => ['Mayara', 'Raisa', 'Kristen', 'Julia'],
    'Design'      => ['Mayara', 'Julia'],
    'Secretaria'  => ['Joaquin'],
    'TI'          => ['João Pedro'],
];

$limpa = fn(string $s): string => strtolower(strtr(iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s, ["'" => '', '`' => '', '^' => '', '~' => '', '"' => '']));
$usuarios = Db::all("SELECT id, name FROM users WHERE active = 1 AND role <> 'externo'");

$achar = function (string $nome) use ($usuarios, $limpa): array {
    $alvo = $limpa($nome);
    $ok = [];
    foreach ($usuarios as $u) {
        $n = $limpa($u['name']);
        if ($n === $alvo || str_starts_with($n, $alvo . ' ')) {
            $ok[] = $u;
        }
    }
    return $ok;
};

$pdo = Db::pdo();
$pdo->beginTransaction();
try {
    foreach ($lista as $setor => $nomes) {
        $s = Db::one('SELECT id FROM sectors WHERE name = ?', [$setor]);
        if (!$s) {
            echo "AVISO: o setor \"$setor\" não existe; ignorado.\n";
            continue;
        }
        $ids = [];
        foreach ($nomes as $nome) {
            $r = $achar($nome);
            if (count($r) === 1) {
                $ids[] = (int) $r[0]['id'];
            } elseif (!$r) {
                echo "AVISO: \"$nome\" (setor $setor) ainda não está cadastrada(o). Cadastre e rode este comando de novo.\n";
            } else {
                echo "AVISO: \"$nome\" (setor $setor) bate com mais de uma pessoa (" . implode(', ', array_column($r, 'name')) . "); ignorada(o).\n";
            }
        }
        if (!$ids) {
            continue;
        }
        Db::run('DELETE FROM sector_leads WHERE sector_id = ?', [(int) $s['id']]);
        foreach ($ids as $i => $uid) {
            Db::run('INSERT INTO sector_leads (sector_id, user_id, role_label, sort_order) VALUES (?,?,?,?)', [(int) $s['id'], $uid, 'Responsável', $i]);
        }
        echo "OK  $setor: " . count($ids) . " responsável(is).\n";
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}
