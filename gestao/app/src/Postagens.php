<?php
declare(strict_types=1);

/** Cronograma de Postagens e Ideias de Conteúdo (memorial, seções 11 e 12). */
final class Postagens
{
    /** Listas editáveis pelo administrador: chave => [rótulo, rótulo no singular, coluna em posts]. */
    public const LISTAS = [
        'situacao'   => ['Situação do post', 'situação', 'status_id'],
        'formato'    => ['Formato', 'formato', 'format_id'],
        'projeto'    => ['Projeto', 'projeto', 'project_id'],
        'tipo'       => ['Tipo', 'tipo', 'type_id'],
        'plataforma' => ['Plataforma', 'plataforma', null],
    ];

    public static function opcoes(string $kind): array
    {
        return Db::all('SELECT id, name, color FROM post_options WHERE kind = ? ORDER BY sort_order, name', [$kind]);
    }

    /** Todas as listas de uma vez, para filtros e formulário. */
    public static function listas(): array
    {
        $r = [];
        foreach (array_keys(self::LISTAS) as $k) {
            $r[$k] = self::opcoes($k);
        }
        return $r;
    }

    public static function opcao(int $id): ?array
    {
        return Db::one('SELECT id, kind, name, color FROM post_options WHERE id = ?', [$id]);
    }

    /** Quantas postagens usam cada item da lista (para bloquear exclusão). */
    public static function usos(int $id, string $kind): int
    {
        if ($kind === 'plataforma') {
            return (int) Db::value('SELECT COUNT(*) FROM post_platforms WHERE option_id = ?', [$id]);
        }
        $col = self::LISTAS[$kind][2];
        return (int) Db::value("SELECT COUNT(*) FROM posts WHERE $col = ?", [$id]);
    }

    public static function filtros(array $q): array
    {
        $f = [];
        foreach (['formato', 'projeto', 'situacao', 'tipo', 'plataforma', 'profissional'] as $k) {
            if (!empty($q[$k]) && ctype_digit((string) $q[$k])) {
                $f[$k] = (int) $q[$k];
            }
        }
        $t = mb_substr(trim((string) ($q['q'] ?? '')), 0, 100);
        if ($t !== '') {
            $f['q'] = $t;
        }
        if (!empty($q['sem_data'])) {
            $f['sem_data'] = 1;
        }
        return $f;
    }

    private static function onde(array $f, ?string $ini, ?string $fim): array
    {
        $w = ['1=1'];
        $p = [];
        foreach (['formato' => 'format_id', 'projeto' => 'project_id', 'situacao' => 'status_id', 'tipo' => 'type_id'] as $k => $col) {
            if (isset($f[$k])) {
                $w[] = "p.$col = ?";
                $p[] = $f[$k];
            }
        }
        if (isset($f['plataforma'])) {
            $w[] = 'EXISTS (SELECT 1 FROM post_platforms x WHERE x.post_id = p.id AND x.option_id = ?)';
            $p[] = $f['plataforma'];
        }
        if (isset($f['profissional'])) {
            $w[] = 'EXISTS (SELECT 1 FROM post_people x WHERE x.post_id = p.id AND x.user_id = ?)';
            $p[] = $f['profissional'];
        }
        if (isset($f['q'])) {
            $w[] = '(p.title LIKE ? OR p.content LIKE ? OR p.objective LIKE ?)';
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $f['q']) . '%';
            array_push($p, $like, $like, $like);
        }
        if (!empty($f['sem_data'])) {
            $w[] = 'p.post_date IS NULL';
        }
        if ($ini !== null && $fim !== null) {
            $w[] = 'p.post_date BETWEEN ? AND ?';
            array_push($p, $ini, $fim);
        }
        return [implode(' AND ', $w), $p];
    }

    /** Lista de postagens com os itens já unidos (cores incluídas). */
    public static function consultar(array $f, string $ordem = 'data', string $dir = 'asc', int $limite = 50, int $desvio = 0, ?string $ini = null, ?string $fim = null): array
    {
        [$w, $p] = self::onde($f, $ini, $fim);
        $cols = ['data' => 'p.post_date', 'titulo' => 'p.title', 'situacao' => 's.sort_order', 'formato' => 'fo.sort_order', 'projeto' => 'pr.sort_order'];
        $col = $cols[$ordem] ?? 'p.post_date';
        $dir = $dir === 'desc' ? 'DESC' : 'ASC';
        $total = (int) Db::value("SELECT COUNT(*) FROM posts p WHERE $w", $p);
        $linhas = Db::all(
            "SELECT p.*, s.name AS situacao, s.color AS situacao_cor, fo.name AS formato, fo.color AS formato_cor,
                    pr.name AS projeto, pr.color AS projeto_cor, ti.name AS tipo, ti.color AS tipo_cor
               FROM posts p
               LEFT JOIN post_options s ON s.id = p.status_id
               LEFT JOIN post_options fo ON fo.id = p.format_id
               LEFT JOIN post_options pr ON pr.id = p.project_id
               LEFT JOIN post_options ti ON ti.id = p.type_id
              WHERE $w
              ORDER BY ($col IS NULL), $col $dir, p.id $dir
              LIMIT " . max(1, $limite) . ' OFFSET ' . max(0, $desvio),
            $p
        );
        self::anexar($linhas);
        return ['linhas' => $linhas, 'total' => $total];
    }

    private static function anexar(array &$linhas): void
    {
        if (!$linhas) {
            return;
        }
        $ids = array_map(fn($l) => (int) $l['id'], $linhas);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pessoas = Db::all("SELECT pp.post_id, u.id, u.name, u.photo FROM post_people pp JOIN users u ON u.id = pp.user_id WHERE pp.post_id IN ($in) ORDER BY u.name", $ids);
        $plat = Db::all("SELECT pl.post_id, o.id, o.name, o.color FROM post_platforms pl JOIN post_options o ON o.id = pl.option_id WHERE pl.post_id IN ($in) ORDER BY o.sort_order", $ids);
        $pp = [];
        $ll = [];
        foreach ($pessoas as $r) {
            $pp[(int) $r['post_id']][] = $r;
        }
        foreach ($plat as $r) {
            $ll[(int) $r['post_id']][] = $r;
        }
        foreach ($linhas as &$l) {
            $l['pessoas'] = $pp[(int) $l['id']] ?? [];
            $l['plataformas'] = $ll[(int) $l['id']] ?? [];
        }
    }

    public static function obter(int $id): ?array
    {
        $linhas = Db::all(
            'SELECT p.*, s.name AS situacao FROM posts p LEFT JOIN post_options s ON s.id = p.status_id WHERE p.id = ?',
            [$id]
        );
        if (!$linhas) {
            return null;
        }
        self::anexar($linhas);
        return $linhas[0];
    }

    /** Confere os dados do formulário. Devolve [dados, erros]. */
    public static function validar(array $in): array
    {
        $erros = [];
        $titulo = trim((string) ($in['title'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            $erros[] = 'Dê um título à postagem (até 255 letras).';
        }
        $data = trim((string) ($in['post_date'] ?? ''));
        if ($data !== '' && !data_valida($data)) {
            $erros[] = 'A data de postagem não é válida.';
        }
        $obj = trim((string) ($in['objective'] ?? ''));
        if (mb_strlen($obj) > 255) {
            $erros[] = 'O objetivo pode ter até 255 letras.';
        }
        $conteudo = (string) ($in['content'] ?? '');
        if (mb_strlen($conteudo) > 100000) {
            $erros[] = 'O conteúdo está grande demais.';
        }
        $uma = function (string $campo, string $kind) use (&$erros, $in): ?int {
            $v = $in[$campo] ?? '';
            if ($v === '' || $v === null) {
                return null;
            }
            if (!ctype_digit((string) $v) || !Db::value('SELECT 1 FROM post_options WHERE id = ? AND kind = ?', [(int) $v, $kind])) {
                $erros[] = 'Uma das opções escolhidas não existe mais.';
                return null;
            }
            return (int) $v;
        };
        $d = [
            'title' => $titulo,
            'post_date' => $data !== '' ? $data : null,
            'project_id' => $uma('project_id', 'projeto'),
            'type_id' => $uma('type_id', 'tipo'),
            'format_id' => $uma('format_id', 'formato'),
            'status_id' => $uma('status_id', 'situacao'),
            'objective' => $obj !== '' ? $obj : null,
            'content' => trim($conteudo) !== '' ? $conteudo : null,
        ];
        $d['_plataformas'] = [];
        foreach ((array) ($in['plataformas'] ?? []) as $v) {
            if (ctype_digit((string) $v) && Db::value("SELECT 1 FROM post_options WHERE id = ? AND kind = 'plataforma'", [(int) $v])) {
                $d['_plataformas'][] = (int) $v;
            }
        }
        $d['_plataformas'] = array_values(array_unique($d['_plataformas']));
        $d['_pessoas'] = [];
        foreach ((array) ($in['pessoas'] ?? []) as $v) {
            if (ctype_digit((string) $v) && Db::value('SELECT 1 FROM users WHERE id = ?', [(int) $v])) {
                $d['_pessoas'][] = (int) $v;
            }
        }
        $d['_pessoas'] = array_values(array_unique($d['_pessoas']));
        return [$d, $erros];
    }

    /** Cria (id nulo) ou atualiza. Grava o histórico do que mudou. */
    public static function salvar(?int $id, array $d, array $u): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $campos = ['title', 'post_date', 'project_id', 'type_id', 'format_id', 'status_id', 'objective', 'content'];
            $antes = $id ? Db::one('SELECT * FROM posts WHERE id = ?', [$id]) : null;
            $antesPessoas = $id ? Db::all('SELECT user_id FROM post_people WHERE post_id = ?', [$id]) : [];
            if ($id) {
                $set = implode(',', array_map(fn($c) => "$c = ?", $campos));
                Db::run("UPDATE posts SET $set, updated_by = ? WHERE id = ?", [...array_map(fn($c) => $d[$c], $campos), (int) $u['id'], $id]);
            } else {
                $cols = implode(',', $campos);
                $ph = implode(',', array_fill(0, count($campos), '?'));
                Db::run("INSERT INTO posts ($cols, created_by, updated_by) VALUES ($ph,?,?)", [...array_map(fn($c) => $d[$c], $campos), (int) $u['id'], (int) $u['id']]);
                $id = Db::insertId();
            }
            Db::run('DELETE FROM post_people WHERE post_id = ?', [$id]);
            foreach ($d['_pessoas'] as $uid) {
                Db::run('INSERT INTO post_people (post_id, user_id) VALUES (?,?)', [$id, $uid]);
            }
            Db::run('DELETE FROM post_platforms WHERE post_id = ?', [$id]);
            foreach ($d['_plataformas'] as $oid) {
                Db::run('INSERT INTO post_platforms (post_id, option_id) VALUES (?,?)', [$id, $oid]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        $jaEram = $antes ? array_map('intval', array_column($antesPessoas, 'user_id')) : [];
        foreach ($d['_pessoas'] as $uid) {
            if (!in_array($uid, $jaEram, true)) {
                Notificacoes::avisarVarios([$uid], 'postagem', ($u['name'] ?? 'Alguém') . ' colocou você como profissional da postagem “' . $d['title'] . '”', '/postagens/' . $id, $u, true);
            }
        }
        $det = null;
        if ($antes) {
            $det = [];
            $rot = ['title' => 'Título', 'post_date' => 'Data', 'objective' => 'Objetivo', 'content' => 'Conteúdo'];
            foreach ($rot as $c => $nome) {
                if ((string) $antes[$c] !== (string) $d[$c]) {
                    $det[$nome] = [mb_substr((string) $antes[$c], 0, 80), mb_substr((string) $d[$c], 0, 80)];
                }
            }
            foreach (['project_id' => 'Projeto', 'type_id' => 'Tipo', 'format_id' => 'Formato', 'status_id' => 'Situação'] as $c => $nome) {
                if ((int) $antes[$c] !== (int) $d[$c]) {
                    $det[$nome] = [(string) Db::value('SELECT name FROM post_options WHERE id = ?', [(int) $antes[$c]]), (string) Db::value('SELECT name FROM post_options WHERE id = ?', [(int) $d[$c]])];
                }
            }
        }
        Audit::registrar($antes ? 'editar' : 'criar', 'postagem', $id, ($antes ? 'Editou' : 'Criou') . ' a postagem ' . $d['title'], $det ?: null, $u);
        return $id;
    }

    public static function mudarSituacao(int $id, int $situacaoId, array $u): bool
    {
        $nova = Db::one("SELECT id, name FROM post_options WHERE id = ? AND kind = 'situacao'", [$situacaoId]);
        $p = Db::one('SELECT title, status_id FROM posts WHERE id = ?', [$id]);
        if (!$nova || !$p) {
            return false;
        }
        if ((int) $p['status_id'] !== $situacaoId) {
            $antes = (string) Db::value('SELECT name FROM post_options WHERE id = ?', [(int) $p['status_id']]);
            Db::run('UPDATE posts SET status_id = ?, updated_by = ? WHERE id = ?', [$situacaoId, (int) $u['id'], $id]);
            Audit::registrar('editar', 'postagem', $id, 'Mudou a situação de ' . $p['title'], ['Situação' => [$antes, $nova['name']]], $u);
        }
        return true;
    }

    public static function excluir(int $id, array $u): void
    {
        $t = (string) Db::value('SELECT title FROM posts WHERE id = ?', [$id]);
        Db::run('DELETE FROM posts WHERE id = ?', [$id]);
        Audit::registrar('excluir', 'postagem', $id, "Excluiu a postagem $t", null, $u);
    }

    // ------------------------------------------------------------------ ideias
    public static function ideias(bool $mostrarUsadas): array
    {
        return Db::all(
            'SELECT i.*, u.name AS autor FROM ideas i LEFT JOIN users u ON u.id = i.created_by '
            . ($mostrarUsadas ? '' : 'WHERE i.used = 0 ') . 'ORDER BY i.used, i.created_at DESC, i.id DESC'
        );
    }
}
