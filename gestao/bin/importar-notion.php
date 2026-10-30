<?php
declare(strict_types=1);
/**
 * Importa as tarefas e as pessoas do backup do Notion (arquivos em
 * database/dados-importacao/). Pode rodar de novo sem duplicar nada.
 *
 *   docker compose exec app php bin/importar-notion.php
 *
 * Os usuários importados NÃO ganham acesso ao sistema: o administrador
 * cadastra o e-mail e libera a senha em "Administração → Usuários".
 */
require __DIR__ . '/../app/src/Env.php';
require __DIR__ . '/../app/src/Db.php';

$pasta = __DIR__ . '/../database/dados-importacao';
$pessoas = json_decode((string) @file_get_contents("$pasta/pessoas.json"), true);
$tarefas = json_decode((string) @file_get_contents("$pasta/tarefas.json"), true);
if (!is_array($pessoas) || !is_array($tarefas)) {
    fwrite(STDERR, "Arquivos de importação não encontrados em $pasta\n");
    exit(1);
}

// E-mails que o backup trouxe no lugar do nome (ver LEIAME: o administrador deve conferir)
$emailsConhecidos = [
    'jessica@deangelieventos.com' => 'Jessica Domingues',
    'marcos@deangelieventos.com' => 'Marcos Welder',
];
$externos = ['Vacy Alvaro', 'Elaine da Luz'];     // Vacy: jornalista externo; Elaine: convidada no Notion
$semAcesso = ['De Angeli'];                         // conta genérica do Notion

// No Notion, quem não tinha nome preenchido aparece pelo e-mail. Aqui vira o nome real.
$nomeReal = function (string $n) use ($emailsConhecidos): string {
    $n = trim($n);
    if (str_contains($n, '@')) {
        return $emailsConhecidos[strtolower($n)] ?? ucfirst(explode('@', $n)[0]);
    }
    return $n;
};

$pdo = Db::pdo();
$pdo->beginTransaction();
try {
    // ---- pessoas
    $novos = 0;
    $porNome = [];
    foreach ($pessoas as $p) {
        $nome = trim($p['nome']);
        $email = str_contains($nome, '@') ? strtolower($nome) : null;
        $nome = $nomeReal($nome);
        $papel = in_array($nome, $externos, true) ? 'externo' : 'equipe';
        $ativo = in_array($nome, $semAcesso, true) ? 0 : 1;
        $existe = Db::one('SELECT id, email FROM users WHERE name = ?', [$nome]);
        if ($existe) {
            if ($email && !$existe['email'] && !Db::value('SELECT 1 FROM users WHERE email = ?', [$email])) {
                Db::run('UPDATE users SET email = ? WHERE id = ?', [$email, $existe['id']]);
            }
            $porNome[$nome] = (int) $existe['id'];
            continue;
        }
        Db::run('INSERT INTO users (name, email, role, active) VALUES (?,?,?,?)', [$nome, $email, $papel, $ativo]);
        $porNome[$nome] = Db::insertId();
        $novos++;
    }
    // quem aparece em tarefas mas não na lista de pessoas
    $garantir = function (string $nome) use (&$porNome, &$novos, $nomeReal): int {
        $nome = $nomeReal($nome);
        if (!isset($porNome[$nome])) {
            $r = Db::one('SELECT id FROM users WHERE name = ?', [$nome]);
            if (!$r) {
                Db::run("INSERT INTO users (name, role) VALUES (?, 'equipe')", [$nome]);
                $porNome[$nome] = Db::insertId();
                $novos++;
            } else {
                $porNome[$nome] = (int) $r['id'];
            }
        }
        return $porNome[$nome];
    };

    // ---- setores e projetos (cria o que faltar)
    $garantirCat = function (string $tabela, string $nome): int {
        $r = Db::one("SELECT id FROM $tabela WHERE name = ?", [$nome]);
        if ($r) {
            return (int) $r['id'];
        }
        $ordem = (int) Db::value("SELECT COALESCE(MAX(sort_order),0)+1 FROM $tabela");
        Db::run("INSERT INTO $tabela (name, sort_order) VALUES (?,?)", [$nome, $ordem]);
        return Db::insertId();
    };

    // ---- tarefas
    $criadas = 0;
    $puladas = 0;
    foreach ($tarefas as $i => $t) {
        $chave = 'central:' . ($i + 1) . ':' . substr(sha1($t['titulo']), 0, 12);
        if (Db::value('SELECT 1 FROM tasks WHERE import_key = ?', [$chave])) {
            $puladas++;
            continue;
        }
        $status = in_array($t['status'], ['A fazer', 'Em andamento', 'Aguardando', 'Concluído', 'Cancelado'], true) ? $t['status'] : 'A fazer';
        Db::run(
            'INSERT INTO tasks (title, status, priority, type, start_date, start_time, end_date, requested_deadline, publish_date,
                                mkt_stage, material_type, briefing, content, piece_goal, target_audience, format_specs,
                                references_text, materials_folder, requester_notes, body, import_key)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $t['titulo'], $status, $t['prioridade'], $t['tipo'], $t['data_inicio'],
                $t['hora_inicio'] ? $t['hora_inicio'] . ':00' : null, $t['data_fim'], $t['prazo_solicitado'], $t['data_publicacao'],
                $t['etapa_mkt_design'], $t['tipo_material'], $t['briefing'], $t['conteudo'], $t['objetivo_peca'], $t['publico_alvo'],
                $t['formato_especificacoes'], $t['referencias'], $t['pasta_materiais'], $t['observacoes_solicitante'],
                trim((string) $t['corpo']) !== '' ? $t['corpo'] : null, $chave,
            ]
        );
        $id = Db::insertId();
        foreach (array_unique($t['setores']) as $n) {
            Db::run("INSERT IGNORE INTO task_sectors (task_id, sector_id, kind) VALUES (?,?, 'executor')", [$id, $garantirCat('sectors', $n)]);
        }
        foreach (array_unique($t['setor_solicitante']) as $n) {
            Db::run("INSERT IGNORE INTO task_sectors (task_id, sector_id, kind) VALUES (?,?, 'solicitante')", [$id, $garantirCat('sectors', $n)]);
        }
        foreach (array_unique($t['projetos']) as $n) {
            Db::run('INSERT IGNORE INTO task_projects (task_id, project_id) VALUES (?,?)', [$id, $garantirCat('projects', $n)]);
        }
        foreach (['responsaveis' => 'responsavel', 'solicitantes' => 'solicitante', 'aprovadores' => 'aprovador'] as $campo => $papel) {
            foreach (array_unique($t[$campo]) as $n) {
                Db::run('INSERT IGNORE INTO task_people (task_id, user_id, role) VALUES (?,?,?)', [$id, $garantir($n), $papel]);
            }
        }
        $criadas++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Importação cancelada (nada foi gravado): ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Importação concluída.\n";
echo "  Pessoas novas:      $novos\n";
echo "  Tarefas importadas: $criadas\n";
echo "  Já existiam:        $puladas\n";
