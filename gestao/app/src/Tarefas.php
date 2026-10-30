<?php
declare(strict_types=1);

/**
 * Central de Tarefas — a ÚNICA fonte das tarefas (memorial, seção 38).
 * Quadro, tabela, calendário, Minhas Tarefas e páginas de setor são apenas
 * filtros diferentes sobre estas mesmas linhas: nada é copiado.
 */
final class Tarefas
{
    /** Campos de texto/lista simples editáveis, com o rótulo usado no histórico. */
    private const CAMPOS = [
        'title' => 'Atividade', 'status' => 'Status', 'priority' => 'Prioridade', 'type' => 'Tipo',
        'start_date' => 'Data', 'start_time' => 'Hora', 'end_date' => 'Data final',
        'requested_deadline' => 'Prazo solicitado', 'publish_date' => 'Data de publicação/impressão',
        'mkt_stage' => 'Etapa Marketing e Design', 'material_type' => 'Tipo de material',
        'briefing' => 'Briefing', 'content' => 'Conteúdo', 'piece_goal' => 'Objetivo da peça',
        'target_audience' => 'Público alvo', 'format_specs' => 'Formato e especificações',
        'references_text' => 'Referências', 'materials_folder' => 'Pasta de materiais',
        'requester_notes' => 'Observações do solicitante', 'body' => 'Texto da página',
    ];

    private const ORDENAVEIS = [
        'atividade' => 't.title',
        'status' => 'FIELD(t.status, %s)',
        'data' => 't.start_date',
        'prioridade' => "FIELD(t.priority, 'Alta','Média','Baixa')",
        'atualizado' => 't.updated_at',
    ];

    // ---------------------------------------------------------------- filtros

    /** Lê e limpa os filtros que vêm da barra de endereço. */
    public static function filtros(array $q): array
    {
        $f = [];
        $f['q'] = mb_substr(trim((string) ($q['q'] ?? '')), 0, 100);
        $st = (string) ($q['status'] ?? '');
        $f['status'] = ($st === 'abertas' || in_array($st, Config::STATUS, true)) ? $st : '';
        $pr = (string) ($q['prioridade'] ?? '');
        $f['prioridade'] = in_array($pr, Config::PRIORIDADES, true) ? $pr : '';
        $tp = (string) ($q['tipo'] ?? '');
        $f['tipo'] = in_array($tp, Config::TIPOS, true) ? $tp : '';
        foreach (['setor', 'projeto', 'responsavel'] as $k) {
            $f[$k] = ctype_digit((string) ($q[$k] ?? '')) ? (int) $q[$k] : 0;
        }
        $f['mine'] = !empty($q['mine']) ? 1 : 0;
        $f['solicitacao'] = !empty($q['solicitacao']) ? 1 : 0;      // só pedidos de Marketing/Design
        $f['solicitante_eu'] = !empty($q['solicitante_eu']) ? 1 : 0; // só os pedidos que eu fiz
        $f['sem_data'] = !empty($q['sem_data']) ? 1 : 0;
        return $f;
    }

    /** Monta o trecho WHERE. O usuário externo só enxerga as próprias tarefas. */
    private static function onde(array $f, array $u, ?string $ini = null, ?string $fim = null): array
    {
        $w = ['1=1'];
        $p = [];
        $resp = "EXISTS (SELECT 1 FROM task_people tp WHERE tp.task_id = t.id AND tp.role = 'responsavel' AND tp.user_id = ?)";

        if (Perm::externo($u)) {
            $w[] = "($resp OR (t.mkt_stage IS NOT NULL AND t.mkt_stage <> '' AND EXISTS (SELECT 1 FROM task_people tq WHERE tq.task_id = t.id AND tq.role = 'solicitante' AND tq.user_id = ?)))";
            array_push($p, $u['id'], $u['id']);
        }
        if (!empty($f['solicitacao'])) {
            $w[] = "t.mkt_stage IS NOT NULL AND t.mkt_stage <> ''";
        }
        if (!empty($f['solicitante_eu'])) {
            $w[] = "EXISTS (SELECT 1 FROM task_people ts2 WHERE ts2.task_id = t.id AND ts2.role = 'solicitante' AND ts2.user_id = ?)";
            $p[] = $u['id'];
        }
        if (!empty($f['mine'])) {
            $w[] = $resp;
            $p[] = $u['id'];
        }
        if (!empty($f['responsavel'])) {
            $w[] = $resp;
            $p[] = $f['responsavel'];
        }
        if (!empty($f['setor'])) {
            $w[] = "EXISTS (SELECT 1 FROM task_sectors ts WHERE ts.task_id = t.id AND ts.kind = 'executor' AND ts.sector_id = ?)";
            $p[] = $f['setor'];
        }
        if (!empty($f['projeto'])) {
            $w[] = 'EXISTS (SELECT 1 FROM task_projects tj WHERE tj.task_id = t.id AND tj.project_id = ?)';
            $p[] = $f['projeto'];
        }
        if (!empty($f['status'])) {
            if ($f['status'] === 'abertas') {
                $w[] = 't.status IN (' . implode(',', array_fill(0, count(Config::STATUS_ABERTOS), '?')) . ')';
                array_push($p, ...Config::STATUS_ABERTOS);
            } else {
                $w[] = 't.status = ?';
                $p[] = $f['status'];
            }
        }
        if (!empty($f['prioridade'])) {
            $w[] = 't.priority = ?';
            $p[] = $f['prioridade'];
        }
        if (!empty($f['tipo'])) {
            $w[] = 't.type = ?';
            $p[] = $f['tipo'];
        }
        if (!empty($f['q'])) {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $w[] = '(t.title LIKE ? OR t.briefing LIKE ?)';
            array_push($p, $like, $like);
        }
        if (!empty($f['sem_data'])) {
            $w[] = 't.start_date IS NULL';
        }
        if ($ini !== null && $fim !== null) {
            $w[] = 't.start_date IS NOT NULL AND t.start_date <= ? AND COALESCE(t.end_date, t.start_date) >= ?';
            array_push($p, $fim, $ini);
        }
        return [implode(' AND ', $w), $p];
    }

    // --------------------------------------------------------------- consultas

    /**
     * @return array{linhas:array,total:int}
     */
    public static function consultar(array $f, array $u, string $ordem = 'data', string $dir = 'asc', int $limite = 500, int $deslocar = 0, ?string $ini = null, ?string $fim = null): array
    {
        [$where, $params] = self::onde($f, $u, $ini, $fim);
        $total = (int) Db::value("SELECT COUNT(*) FROM tasks t WHERE $where", $params);

        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $chave = self::ORDENAVEIS[$ordem] ?? self::ORDENAVEIS['data'];
        if ($ordem === 'status') {
            $lista = implode(',', array_map(fn($s) => Db::pdo()->quote($s), Config::STATUS));
            $chave = sprintf($chave, $lista);
        }
        // tarefas sem o valor ficam sempre no fim da lista
        $nulos = in_array($ordem, ['data'], true) ? 't.start_date IS NULL, ' : '';
        $sql = "SELECT t.* FROM tasks t WHERE $where ORDER BY {$nulos}{$chave} $dir, t.id DESC LIMIT " . (int) $limite . ' OFFSET ' . (int) $deslocar;
        $linhas = Db::all($sql, $params);
        self::anexarRelacoes($linhas);
        return ['linhas' => $linhas, 'total' => $total];
    }

    /** Preenche setores, projetos e pessoas de cada tarefa com poucas consultas. */
    private static function anexarRelacoes(array &$linhas): void
    {
        if (!$linhas) {
            return;
        }
        $ids = array_column($linhas, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $por = [];
        foreach ($linhas as $i => $l) {
            $por[$l['id']] = $i;
            $linhas[$i] += ['setores' => [], 'setores_solicitantes' => [], 'projetos' => [], 'responsaveis' => [], 'solicitantes' => [], 'aprovadores' => []];
        }
        foreach (Db::all("SELECT ts.task_id, ts.kind, s.id, s.name, s.color FROM task_sectors ts JOIN sectors s ON s.id = ts.sector_id WHERE ts.task_id IN ($in) ORDER BY s.sort_order, s.name", $ids) as $r) {
            $linhas[$por[$r['task_id']]][$r['kind'] === 'executor' ? 'setores' : 'setores_solicitantes'][] = ['id' => (int) $r['id'], 'name' => $r['name'], 'color' => $r['color']];
        }
        foreach (Db::all("SELECT tp.task_id, p.id, p.name, p.color FROM task_projects tp JOIN projects p ON p.id = tp.project_id WHERE tp.task_id IN ($in) ORDER BY p.sort_order, p.name", $ids) as $r) {
            $linhas[$por[$r['task_id']]]['projetos'][] = ['id' => (int) $r['id'], 'name' => $r['name'], 'color' => $r['color']];
        }
        $chaves = ['responsavel' => 'responsaveis', 'solicitante' => 'solicitantes', 'aprovador' => 'aprovadores'];
        foreach (Db::all("SELECT tp.task_id, tp.role, u.id, u.name, u.photo FROM task_people tp JOIN users u ON u.id = tp.user_id WHERE tp.task_id IN ($in) ORDER BY u.name", $ids) as $r) {
            $linhas[$por[$r['task_id']]][$chaves[$r['role']]][] = ['id' => (int) $r['id'], 'name' => $r['name'], 'photo' => $r['photo']];
        }
    }

    public static function obter(int $id): ?array
    {
        $l = Db::all('SELECT * FROM tasks WHERE id = ?', [$id]);
        if (!$l) {
            return null;
        }
        self::anexarRelacoes($l);
        return $l[0];
    }

    public static function contarPorStatus(array $f, array $u): array
    {
        [$where, $params] = self::onde($f, $u);
        $r = Db::all("SELECT t.status, COUNT(*) n FROM tasks t WHERE $where GROUP BY t.status", $params);
        $out = array_fill_keys(Config::STATUS, 0);
        foreach ($r as $x) {
            $out[$x['status']] = (int) $x['n'];
        }
        return $out;
    }

    public static function comentarios(int $id): array
    {
        return Db::all('SELECT c.*, u.name FROM comments c LEFT JOIN users u ON u.id = c.user_id WHERE c.task_id = ? ORDER BY c.created_at, c.id', [$id]);
    }

    public static function historico(int $id): array
    {
        return Db::all("SELECT * FROM audit_log WHERE entity = 'tarefa' AND entity_id = ? ORDER BY id DESC LIMIT 50", [$id]);
    }

    // ----------------------------------------------------------------- gravar

    /**
     * Valida os dados vindos do formulário. Devolve [dados limpos, erros].
     * @return array{0:array,1:array}
     */
    public static function validar(array $in): array
    {
        $erros = [];
        $d = [];
        $d['title'] = trim((string) ($in['title'] ?? ''));
        if ($d['title'] === '') {
            $erros[] = 'Informe o nome da atividade.';
        } elseif (mb_strlen($d['title']) > 255) {
            $erros[] = 'O nome da atividade é muito longo (máximo 255 letras).';
        }
        $d['status'] = in_array($in['status'] ?? '', Config::STATUS, true) ? $in['status'] : 'A fazer';
        $d['priority'] = in_array($in['priority'] ?? '', Config::PRIORIDADES, true) ? $in['priority'] : null;
        $d['type'] = in_array($in['type'] ?? '', Config::TIPOS, true) ? $in['type'] : null;
        $d['material_type'] = in_array($in['material_type'] ?? '', Config::TIPOS_MATERIAL, true) ? $in['material_type'] : null;
        $d['mkt_stage'] = in_array($in['mkt_stage'] ?? '', Config::ETAPAS_MKT, true) ? $in['mkt_stage'] : null;

        foreach (['start_date' => 'Data', 'end_date' => 'Data final', 'requested_deadline' => 'Prazo solicitado', 'publish_date' => 'Data de publicação/impressão'] as $k => $rotulo) {
            $bruto = trim((string) ($in[$k] ?? ''));
            $d[$k] = data_valida($bruto);
            if ($bruto !== '' && $d[$k] === null) {
                $erros[] = "$rotulo inválida.";
            }
        }
        $d['start_time'] = hora_valida($in['start_time'] ?? '');
        if ($d['start_date'] === null) {
            $d['start_time'] = null;
        }
        if ($d['end_date'] !== null && $d['start_date'] !== null && $d['end_date'] < $d['start_date']) {
            $erros[] = 'A data final não pode ser antes da data inicial.';
        }
        if ($d['end_date'] !== null && $d['start_date'] === null) {
            $d['end_date'] = null;
        }

        $limites = ['briefing' => 20000, 'content' => 100000, 'piece_goal' => 5000, 'target_audience' => 5000,
            'format_specs' => 5000, 'references_text' => 10000, 'materials_folder' => 2000, 'requester_notes' => 10000, 'body' => 200000];
        foreach ($limites as $k => $max) {
            $v = trim((string) ($in[$k] ?? ''));
            if (mb_strlen($v) > $max) {
                $erros[] = 'Um dos textos está longo demais.';
                $v = mb_substr($v, 0, $max);
            }
            $d[$k] = $v === '' ? null : $v;
        }

        // listas de ids (só aceita ids que existem)
        $d['_setores'] = self::idsValidos('sectors', $in['setores'] ?? []);
        $d['_setores_solicitantes'] = self::idsValidos('sectors', $in['setores_solicitantes'] ?? []);
        $d['_projetos'] = self::idsValidos('projects', $in['projetos'] ?? []);
        foreach (['responsaveis', 'solicitantes', 'aprovadores'] as $k) {
            $d["_$k"] = self::idsValidos('users', $in[$k] ?? []);
        }
        return [$d, $erros];
    }

    private static function idsValidos(string $tabela, mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
        if (!$ids) {
            return [];
        }
        // $tabela vem sempre de dentro do código (nunca do visitante)
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = Db::pdo()->prepare("SELECT id FROM $tabela WHERE id IN ($in)");
        $st->execute($ids);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Cria ou atualiza a tarefa (com setores, projetos e pessoas) e registra o histórico. */
    public static function salvar(?int $id, array $d, array $u): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $antes = $id ? self::obter($id) : null;
            if ($id && !$antes) {
                throw new RuntimeException('Tarefa não encontrada.');
            }
            $cols = array_keys(self::CAMPOS);
            if ($id) {
                $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
                Db::run("UPDATE tasks SET $set WHERE id = ?", [...array_map(fn($c) => $d[$c], $cols), $id]);
            } else {
                $lista = implode(', ', $cols);
                $marc = implode(', ', array_fill(0, count($cols), '?'));
                Db::run("INSERT INTO tasks ($lista, created_by) VALUES ($marc, ?)", [...array_map(fn($c) => $d[$c], $cols), $u['id']]);
                $id = Db::insertId();
            }

            Db::run('DELETE FROM task_sectors WHERE task_id = ?', [$id]);
            foreach ($d['_setores'] as $s) {
                Db::run("INSERT INTO task_sectors (task_id, sector_id, kind) VALUES (?,?, 'executor')", [$id, $s]);
            }
            foreach ($d['_setores_solicitantes'] as $s) {
                Db::run("INSERT INTO task_sectors (task_id, sector_id, kind) VALUES (?,?, 'solicitante')", [$id, $s]);
            }
            Db::run('DELETE FROM task_projects WHERE task_id = ?', [$id]);
            foreach ($d['_projetos'] as $p) {
                Db::run('INSERT INTO task_projects (task_id, project_id) VALUES (?,?)', [$id, $p]);
            }
            Db::run('DELETE FROM task_people WHERE task_id = ?', [$id]);
            foreach (['responsavel' => '_responsaveis', 'solicitante' => '_solicitantes', 'aprovador' => '_aprovadores'] as $papel => $k) {
                foreach ($d[$k] as $uid) {
                    Db::run('INSERT INTO task_people (task_id, user_id, role) VALUES (?,?,?)', [$id, $uid, $papel]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $depois = self::obter($id);
        // Notificação: quem acabou de ser marcado como responsável.
        $antesIds = $antes ? array_map(fn($r) => (int) $r['id'], $antes['responsaveis']) : [];
        foreach ($depois['responsaveis'] as $r) {
            if (!in_array((int) $r['id'], $antesIds, true)) {
                Notificacoes::avisar((int) $r['id'], 'responsavel', ($u['name'] ?? 'Alguém') . ' colocou você como responsável por “' . $depois['title'] . '”', '/tarefas/' . $id, $u);
            }
        }
        if (!$antes) {
            Audit::registrar('criar', 'tarefa', $id, 'Criou a tarefa "' . $depois['title'] . '"', null, $u);
        } else {
            $dif = self::diferencas($antes, $depois);
            if ($dif) {
                Audit::registrar('editar', 'tarefa', $id, 'Alterou: ' . implode(', ', array_keys($dif)), $dif, $u);
            }
        }
        return $id;
    }

    /** Compara duas versões da tarefa e devolve só o que mudou: rótulo => [antes, depois]. */
    private static function diferencas(array $a, array $b): array
    {
        $dif = [];
        foreach (self::CAMPOS as $c => $rotulo) {
            $va = (string) ($a[$c] ?? '');
            $vb = (string) ($b[$c] ?? '');
            if ($va !== $vb) {
                $dif[$rotulo] = [mb_substr($va, 0, 200), mb_substr($vb, 0, 200)];
            }
        }
        $nomes = fn(array $l) => implode(', ', array_column($l, 'name'));
        foreach ([
            'setores' => 'Setor', 'setores_solicitantes' => 'Setor solicitante', 'projetos' => 'Projeto',
            'responsaveis' => 'Responsável', 'solicitantes' => 'Solicitante', 'aprovadores' => 'Aprovação',
        ] as $k => $rotulo) {
            if ($nomes($a[$k]) !== $nomes($b[$k])) {
                $dif[$rotulo] = [$nomes($a[$k]), $nomes($b[$k])];
            }
        }
        return $dif;
    }

    public static function mudarStatus(int $id, string $status, array $u): bool
    {
        if (!in_array($status, Config::STATUS, true)) {
            return false;
        }
        $antes = Db::value('SELECT status FROM tasks WHERE id = ?', [$id]);
        if ($antes === null) {
            return false;
        }
        if ($antes !== $status) {
            Db::run('UPDATE tasks SET status = ? WHERE id = ?', [$status, $id]);
            Audit::registrar('editar', 'tarefa', $id, 'Alterou: Status', ['Status' => [$antes, $status]], $u);
        }
        return true;
    }

    public static function excluir(int $id, array $u): void
    {
        $t = Db::value('SELECT title FROM tasks WHERE id = ?', [$id]);
        if ($t === null) {
            return;
        }
        Db::run('DELETE FROM tasks WHERE id = ?', [$id]);
        Audit::registrar('excluir', 'tarefa', $id, 'Excluiu a tarefa "' . $t . '"', null, $u);
    }

    public static function comentar(int $id, string $texto, array $u): void
    {
        Db::run('INSERT INTO comments (task_id, user_id, body) VALUES (?,?,?)', [$id, $u['id'], $texto]);
        Audit::registrar('comentar', 'tarefa', $id, 'Comentou na tarefa', null, $u);
        $titulo = (string) Db::value('SELECT title FROM tasks WHERE id = ?', [$id]);
        Notificacoes::avisarVarios(Notificacoes::pessoasDaTarefa($id), 'comentario', ($u['name'] ?? 'Alguém') . ' comentou em “' . $titulo . '”', '/tarefas/' . $id . '#comentarios', $u);
    }

    // ------------------------------------------------ pedidos de Marketing e Design

    /**
     * Cria um pedido pelo formulário. Cada pedido é UMA tarefa, com a etapa "Nova solicitação",
     * no setor que recebe os pedidos (Marketing) e com quem pediu como solicitante.
     * @return array{0:?int,1:array} [id da tarefa, erros]
     */
    public static function criarSolicitacao(array $in, array $u): array
    {
        $recebe = (int) Db::value("SELECT v FROM settings WHERE k = 'sol_setor_recebe'");
        $dados = [
            'title' => $in['title'] ?? '', 'status' => 'A fazer', 'type' => 'Tarefa', 'mkt_stage' => 'Nova solicitação',
            'material_type' => $in['material_type'] ?? '', 'requested_deadline' => $in['requested_deadline'] ?? '',
            'publish_date' => $in['publish_date'] ?? '', 'briefing' => $in['briefing'] ?? '', 'piece_goal' => $in['piece_goal'] ?? '',
            'target_audience' => $in['target_audience'] ?? '', 'format_specs' => $in['format_specs'] ?? '', 'content' => $in['content'] ?? '',
            'references_text' => $in['references_text'] ?? '', 'materials_folder' => $in['materials_folder'] ?? '',
            'requester_notes' => $in['requester_notes'] ?? '',
            'setores' => $recebe ? [$recebe] : [],
            'projetos' => is_array($in['projetos'] ?? null) ? $in['projetos'] : [],
            'setores_solicitantes' => !empty($in['setor_solicitante']) ? [$in['setor_solicitante']] : [],
            'solicitantes' => [$u['id']],
        ];
        [$d, $e] = self::validar($dados);
        $e = array_map(fn($m) => $m === 'Informe o nome da atividade.' ? 'Dê um nome ao pedido.' : $m, $e);
        if ($d['material_type'] === null) {
            $e[] = 'Escolha o tipo de material.';
        }
        if ($d['briefing'] === null) {
            $e[] = 'Explique o pedido no campo "O que você precisa?".';
        }
        if ($d['requested_deadline'] === null) {
            $e[] = $dados['requested_deadline'] === '' ? 'Informe até quando você precisa.' : null;
        } elseif ($d['requested_deadline'] < date('Y-m-d')) {
            $e[] = 'O prazo desejado não pode ser uma data que já passou.';
        }
        $e = array_values(array_filter($e));
        if ($e) {
            return [null, $e];
        }
        $id = self::salvar(null, $d, $u);
        return [$id, []];
    }

    /** Move o pedido de etapa e aplica as regras automáticas. */
    public static function mudarEtapa(int $id, string $etapa, array $u): bool
    {
        if (!in_array($etapa, Config::ETAPAS_MKT, true)) {
            return false;
        }
        $antes = Db::value('SELECT mkt_stage FROM tasks WHERE id = ?', [$id]);
        if ($antes === null && !Db::value('SELECT 1 FROM tasks WHERE id = ?', [$id])) {
            return false;
        }
        if ((string) $antes !== $etapa) {
            Db::run('UPDATE tasks SET mkt_stage = ? WHERE id = ?', [$etapa, $id]);
            Audit::registrar('editar', 'tarefa', $id, 'Alterou: Etapa Marketing e Design', ['Etapa Marketing e Design' => [(string) $antes, $etapa]], $u);
            self::regrasEtapa($id, $antes === null ? null : (string) $antes, $u);
        }
        return true;
    }

    /**
     * Regras automáticas quando a etapa muda (valem também ao editar a tarefa):
     *   Aguardando informações → status Aguardando
     *   Aprovada para o Design → o setor Design passa a ver a tarefa
     *   Em criação             → status Em andamento (e Design)
     *   Concluída              → status Concluído (e Design)
     */
    public static function regrasEtapa(int $id, ?string $antes, array $u): void
    {
        $t = Db::one('SELECT status, mkt_stage FROM tasks WHERE id = ?', [$id]);
        $etapa = (string) ($t['mkt_stage'] ?? '');
        if (!$t || $etapa === '' || $etapa === (string) $antes) {
            return;
        }
        $tituloPedido = (string) Db::value('SELECT title FROM tasks WHERE id = ?', [$id]);
        $solic = array_map('intval', array_column(Db::all("SELECT user_id FROM task_people WHERE task_id = ? AND role = 'solicitante'", [$id]), 'user_id'));
        Notificacoes::avisarVarios($solic, 'pedido', 'Seu pedido “' . $tituloPedido . '” foi para a etapa “' . $etapa . '”', '/tarefas/' . $id, $u);
        $msgs = [];
        $novo = null;
        if ($etapa === 'Aguardando informações' && in_array($t['status'], ['A fazer', 'Em andamento'], true)) {
            $novo = 'Aguardando';
        } elseif ($etapa === 'Em criação' && in_array($t['status'], ['A fazer', 'Aguardando'], true)) {
            $novo = 'Em andamento';
        } elseif ($etapa === 'Concluída' && !in_array($t['status'], ['Concluído', 'Cancelado'], true)) {
            $novo = 'Concluído';
        }
        if ($novo) {
            Db::run('UPDATE tasks SET status = ? WHERE id = ?', [$novo, $id]);
            $msgs[] = "status → $novo";
        }
        if (in_array($etapa, ['Aprovada para o Design', 'Em criação', 'Concluída'], true)) {
            $design = (int) Db::value("SELECT v FROM settings WHERE k = 'sol_setor_design'");
            if ($design && Db::value('SELECT 1 FROM sectors WHERE id = ?', [$design])
                && !Db::value("SELECT 1 FROM task_sectors WHERE task_id = ? AND sector_id = ? AND kind = 'executor'", [$id, $design])) {
                Db::run("INSERT IGNORE INTO task_sectors (task_id, sector_id, kind) VALUES (?,?, 'executor')", [$id, $design]);
                $msgs[] = 'setor Design adicionado';
            }
        }
        if ($msgs) {
            Audit::registrar('editar', 'tarefa', $id, 'Regra automática da etapa "' . $etapa . '": ' . implode('; ', $msgs), null, $u);
        }
    }

    /** Arrastar no calendário: desloca a data (e a data final, se houver) em N dias. */
    public static function moverDias(int $id, int $dias, array $u): bool
    {
        $t = Db::one('SELECT title, start_date, end_date FROM tasks WHERE id = ?', [$id]);
        if (!$t || !$t['start_date'] || $dias === 0 || abs($dias) > 3660) {
            return false;
        }
        $mv = fn(?string $d) => $d === null ? null : (new DateTimeImmutable($d))->modify(($dias > 0 ? '+' : '') . $dias . ' days')->format('Y-m-d');
        Db::run('UPDATE tasks SET start_date = ?, end_date = ? WHERE id = ?', [$mv($t['start_date']), $mv($t['end_date']), $id]);
        Audit::registrar('editar', 'tarefa', $id, 'Mudou a data de "' . $t['title'] . '" no calendário', ['Data' => [data_br($t['start_date']), data_br($mv($t['start_date']))]], $u);
        return true;
    }
}
