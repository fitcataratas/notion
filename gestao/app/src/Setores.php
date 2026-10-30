<?php
declare(strict_types=1);

/** Páginas dos setores: responsáveis, descrição, páginas de texto e links de documentos. */
final class Setores
{
    public const GRUPOS_LINK = ['Documentos', 'Planilhas', 'Orçamentos', 'Outros'];

    public static function obter(int $id): ?array
    {
        $s = Db::one('SELECT * FROM sectors WHERE id = ?', [$id]);
        if (!$s) {
            return null;
        }
        $s['responsaveis'] = self::responsaveis($id);
        $s['filhos'] = Db::all('SELECT id, name, color FROM sectors WHERE parent_id = ? ORDER BY sort_order, name', [$id]);
        $s['pai'] = $s['parent_id'] ? Db::one('SELECT id, name FROM sectors WHERE id = ?', [$s['parent_id']]) : null;
        return $s;
    }

    public static function responsaveis(int $id): array
    {
        return Db::all(
            'SELECT u.id, u.name, u.email, u.photo, l.role_label FROM sector_leads l JOIN users u ON u.id = l.user_id
              WHERE l.sector_id = ? ORDER BY l.sort_order, u.name',
            [$id]
        );
    }

    /** Setores em ordem de menu: cada setor seguido dos que fazem parte dele. */
    public static function arvore(): array
    {
        $todos = Usuarios::setores();
        $por = [];
        foreach ($todos as $s) {
            $por[(int) ($s['parent_id'] ?? 0)][] = $s;
        }
        $fora = [];
        $andar = function (int $pai, int $nivel) use (&$andar, &$fora, $por): void {
            foreach ($por[$pai] ?? [] as $s) {
                $s['nivel'] = $nivel;
                $fora[] = $s;
                $andar((int) $s['id'], $nivel + 1);
            }
        };
        $andar(0, 0);
        return $fora;
    }

    public static function paginas(int $id): array
    {
        return Db::all('SELECT id, title, updated_at FROM pages WHERE sector_id = ? ORDER BY sort_order, title', [$id]);
    }

    /** @return array<string,array> links agrupados por tipo */
    public static function links(int $id): array
    {
        $por = array_fill_keys(self::GRUPOS_LINK, []);
        foreach (Db::all('SELECT * FROM sector_links WHERE sector_id = ? ORDER BY title', [$id]) as $l) {
            $por[in_array($l['grp'], self::GRUPOS_LINK, true) ? $l['grp'] : 'Outros'][] = $l;
        }
        return array_filter($por);
    }

    /** Só aceita endereços http(s); evita "javascript:" e semelhantes. */
    public static function urlValida(string $u): ?string
    {
        $u = trim($u);
        if ($u === '' || mb_strlen($u) > 2000 || !preg_match('#^https?://#i', $u) || !filter_var($u, FILTER_VALIDATE_URL)) {
            return null;
        }
        return $u;
    }

    /** Resumo das tarefas do setor: contagem por status. */
    public static function contagem(int $id, array $u): array
    {
        return Tarefas::contarPorStatus(['setor' => $id], $u);
    }

    /** Edita descrição, "faz parte de" e responsáveis. @return string[] erros */
    public static function salvarDetalhes(int $id, array $in, array $u): array
    {
        $erros = [];
        $desc = trim((string) ($in['description'] ?? ''));
        if (mb_strlen($desc) > 3000) {
            $erros[] = 'A descrição pode ter até 3.000 letras.';
        }
        $pai = ctype_digit((string) ($in['parent_id'] ?? '')) && (int) $in['parent_id'] > 0 ? (int) $in['parent_id'] : null;
        if ($pai !== null) {
            $ok = Db::one('SELECT id, parent_id FROM sectors WHERE id = ?', [$pai]);
            if (!$ok || $pai === $id || $ok['parent_id'] !== null) {
                $erros[] = 'Escolha um setor principal válido (um setor que já não faça parte de outro).';
            } elseif (Db::value('SELECT 1 FROM sectors WHERE parent_id = ?', [$id])) {
                $erros[] = 'Este setor tem outros dentro dele, então não pode fazer parte de outro.';
            }
        }
        $marcados = [];
        foreach ((array) ($in['resp'] ?? []) as $uid) {
            if (ctype_digit((string) $uid)) {
                $marcados[(int) $uid] = mb_substr(trim((string) (($in['funcao'] ?? [])[$uid] ?? '')), 0, 60) ?: 'Responsável';
            }
        }
        if ($marcados) {
            $in2 = implode(',', array_fill(0, count($marcados), '?'));
            $existem = Db::all("SELECT id FROM users WHERE id IN ($in2)", array_keys($marcados));
            $marcados = array_intersect_key($marcados, array_flip(array_column($existem, 'id')));
        }
        if ($erros) {
            return $erros;
        }
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            Db::run('UPDATE sectors SET description = ?, parent_id = ? WHERE id = ?', [$desc === '' ? null : $desc, $pai, $id]);
            Db::run('DELETE FROM sector_leads WHERE sector_id = ?', [$id]);
            $ordem = 0;
            foreach ($marcados as $uid => $rotulo) {
                Db::run('INSERT INTO sector_leads (sector_id, user_id, role_label, sort_order) VALUES (?,?,?,?)', [$id, $uid, $rotulo, $ordem++]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return [];
    }
}
