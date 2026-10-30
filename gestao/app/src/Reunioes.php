<?php
declare(strict_types=1);

/** Base de Reuniões: histórico formal de reuniões e atas (memorial, seção 22). */
final class Reunioes
{
    public const TIPOS = ['Geral', 'Setor', 'Projeto', 'Externa'];

    public static function filtros(array $q): array
    {
        $f = [];
        foreach (['setor', 'projeto', 'participante'] as $k) {
            if (!empty($q[$k]) && ctype_digit((string) $q[$k])) {
                $f[$k] = (int) $q[$k];
            }
        }
        if (!empty($q['tipo']) && in_array($q['tipo'], self::TIPOS, true)) {
            $f['tipo'] = (string) $q['tipo'];
        }
        $t = mb_substr(trim((string) ($q['q'] ?? '')), 0, 100);
        if ($t !== '') {
            $f['q'] = $t;
        }
        return $f;
    }

    public static function consultar(array $f, string $dir = 'desc', ?string $ini = null, ?string $fim = null): array
    {
        $w = ['1=1'];
        $p = [];
        if (isset($f['tipo'])) {
            $w[] = 'm.kind = ?';
            $p[] = $f['tipo'];
        }
        if (isset($f['projeto'])) {
            $w[] = 'm.project_id = ?';
            $p[] = $f['projeto'];
        }
        if (isset($f['setor'])) {
            $w[] = 'EXISTS (SELECT 1 FROM meeting_sectors x WHERE x.meeting_id = m.id AND x.sector_id = ?)';
            $p[] = $f['setor'];
        }
        if (isset($f['participante'])) {
            // "Minhas reuniões": participa ou é o responsável pela ata
            $w[] = '(EXISTS (SELECT 1 FROM meeting_people x WHERE x.meeting_id = m.id AND x.user_id = ?) OR m.minutes_by = ?)';
            array_push($p, $f['participante'], $f['participante']);
        }
        if (isset($f['q'])) {
            $w[] = '(m.title LIKE ? OR m.notes LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['q']) . '%';
            array_push($p, $like, $like);
        }
        if ($ini !== null && $fim !== null) {
            $w[] = 'm.meeting_date BETWEEN ? AND ?';
            array_push($p, $ini, $fim);
        }
        $dir = $dir === 'asc' ? 'ASC' : 'DESC';
        $linhas = Db::all(
            "SELECT m.*, pr.name AS projeto, pr.color AS projeto_cor, u.name AS ata_nome, u.photo AS ata_photo
               FROM meetings m LEFT JOIN projects pr ON pr.id = m.project_id LEFT JOIN users u ON u.id = m.minutes_by
              WHERE " . implode(' AND ', $w) . " ORDER BY (m.meeting_date IS NULL), m.meeting_date $dir, m.id $dir LIMIT 1000",
            $p
        );
        self::anexar($linhas);
        return $linhas;
    }

    private static function anexar(array &$linhas): void
    {
        if (!$linhas) {
            return;
        }
        $ids = array_map(fn($l) => (int) $l['id'], $linhas);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $set = [];
        foreach (Db::all("SELECT ms.meeting_id, s.id, s.name, s.color FROM meeting_sectors ms JOIN sectors s ON s.id = ms.sector_id WHERE ms.meeting_id IN ($in) ORDER BY s.sort_order, s.name", $ids) as $r) {
            $set[(int) $r['meeting_id']][] = $r;
        }
        $pes = [];
        foreach (Db::all("SELECT mp.meeting_id, u.id, u.name, u.photo FROM meeting_people mp JOIN users u ON u.id = mp.user_id WHERE mp.meeting_id IN ($in) ORDER BY u.name", $ids) as $r) {
            $pes[(int) $r['meeting_id']][] = $r;
        }
        foreach ($linhas as &$l) {
            $l['setores'] = $set[(int) $l['id']] ?? [];
            $l['participantes'] = $pes[(int) $l['id']] ?? [];
        }
    }

    public static function obter(int $id): ?array
    {
        $l = Db::all('SELECT m.* FROM meetings m WHERE m.id = ?', [$id]);
        if (!$l) {
            return null;
        }
        self::anexar($l);
        $l[0]['_setores'] = array_map(fn($r) => (int) $r['id'], $l[0]['setores']);
        $l[0]['_participantes'] = array_map(fn($r) => (int) $r['id'], $l[0]['participantes']);
        return $l[0];
    }

    public static function validar(array $in): array
    {
        $e = [];
        $titulo = trim((string) ($in['title'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            $e[] = 'Dê um nome à reunião (até 255 letras).';
        }
        $data = trim((string) ($in['meeting_date'] ?? ''));
        if ($data !== '' && !data_valida($data)) {
            $e[] = 'A data da reunião não é válida.';
            $data = '';
        }
        $tipo = (string) ($in['kind'] ?? 'Geral');
        if (!in_array($tipo, self::TIPOS, true)) {
            $e[] = 'Escolha um tipo de reunião válido.';
            $tipo = 'Geral';
        }
        $url = trim((string) ($in['minutes_url'] ?? ''));
        if ($url !== '' && (!Setores::urlValida($url) || mb_strlen($url) > 600)) {
            $e[] = 'O link da ata precisa começar com http:// ou https://.';
        }
        $proj = ($in['project_id'] ?? '') !== '' && ctype_digit((string) $in['project_id']) ? (int) $in['project_id'] : null;
        if ($proj && !Db::value('SELECT 1 FROM projects WHERE id = ?', [$proj])) {
            $e[] = 'O projeto escolhido não existe mais.';
            $proj = null;
        }
        $ata = ($in['minutes_by'] ?? '') !== '' && ctype_digit((string) $in['minutes_by']) ? (int) $in['minutes_by'] : null;
        if ($ata && !Db::value('SELECT 1 FROM users WHERE id = ?', [$ata])) {
            $e[] = 'A pessoa responsável pela ata não existe mais.';
            $ata = null;
        }
        $notas = (string) ($in['notes'] ?? '');
        if (mb_strlen($notas) > 200000) {
            $e[] = 'As anotações estão grandes demais.';
        }
        $ids = function (string $campo, string $tabela) use ($in): array {
            $r = [];
            foreach ((array) ($in[$campo] ?? []) as $v) {
                if (ctype_digit((string) $v) && Db::value("SELECT 1 FROM $tabela WHERE id = ?", [(int) $v])) {
                    $r[] = (int) $v;
                }
            }
            return array_values(array_unique($r));
        };
        return [[
            'title' => $titulo, 'meeting_date' => $data !== '' ? $data : null, 'kind' => $tipo, 'project_id' => $proj,
            'minutes_by' => $ata, 'minutes_url' => $url !== '' ? $url : null, 'notes' => trim($notas) !== '' ? $notas : null,
            '_setores' => $ids('setores', 'sectors'), '_participantes' => $ids('participantes', 'users'),
        ], $e];
    }

    public static function salvar(?int $id, array $d, array $u): int
    {
        $campos = ['title', 'meeting_date', 'kind', 'project_id', 'minutes_by', 'minutes_url', 'notes'];
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $novo = $id === null;
            $antesPessoas = $novo ? [] : array_map('intval', array_column(Db::all('SELECT user_id FROM meeting_people WHERE meeting_id = ?', [$id]), 'user_id'));
            if ($novo) {
                Db::run('INSERT INTO meetings (' . implode(',', $campos) . ', created_by) VALUES (' . implode(',', array_fill(0, count($campos), '?')) . ',?)', [...array_map(fn($c) => $d[$c], $campos), (int) $u['id']]);
                $id = Db::insertId();
            } else {
                Db::run('UPDATE meetings SET ' . implode(',', array_map(fn($c) => "$c = ?", $campos)) . ' WHERE id = ?', [...array_map(fn($c) => $d[$c], $campos), $id]);
            }
            Db::run('DELETE FROM meeting_sectors WHERE meeting_id = ?', [$id]);
            foreach ($d['_setores'] as $s) {
                Db::run('INSERT INTO meeting_sectors (meeting_id, sector_id) VALUES (?,?)', [$id, $s]);
            }
            Db::run('DELETE FROM meeting_people WHERE meeting_id = ?', [$id]);
            foreach ($d['_participantes'] as $p) {
                Db::run('INSERT INTO meeting_people (meeting_id, user_id) VALUES (?,?)', [$id, $p]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        Audit::registrar($novo ? 'criar' : 'editar', 'reuniao', $id, ($novo ? 'Registrou' : 'Editou') . ' a reunião ' . $d['title'], null, $u);
        foreach ($d['_participantes'] as $p) {
            if (!in_array($p, $antesPessoas, true)) {
                Notificacoes::avisarVarios([$p], 'reuniao', ($u['name'] ?? 'Alguém') . ' incluiu você na reunião “' . $d['title'] . '”' . ($d['meeting_date'] ? ' de ' . data_br($d['meeting_date']) : ''), '/reunioes/' . $id, $u, true);
            }
        }
        return $id;
    }

    public static function excluir(int $id, array $u): void
    {
        $t = (string) Db::value('SELECT title FROM meetings WHERE id = ?', [$id]);
        Db::run('DELETE FROM meetings WHERE id = ?', [$id]);
        Audit::registrar('excluir', 'reuniao', $id, 'Excluiu a reunião ' . $t, null, $u);
    }
}
