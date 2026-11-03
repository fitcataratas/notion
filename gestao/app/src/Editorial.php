<?php
declare(strict_types=1);

/** Dashboard Editorial: quadro de produção de conteúdo por perfil e rede. */
final class Editorial
{
    public const STATUS = ['Ideia' => '#9aa0a6', 'Roteiro' => '#c98a00', 'Produção' => '#e4572e', 'Aprovação' => '#8e5bd6', 'Agendado' => '#2e86de', 'Publicado' => '#2e9e5b'];
    public const FORMATOS = ['Reels / vídeo curto', 'Carrossel', 'Post estático', 'Stories', 'Vídeo longo', 'Artigo / blog', 'Newsletter', 'Outro'];
    public const REDES = ['Instagram', 'TikTok', 'YouTube', 'LinkedIn', 'Site / Blog', 'Canal Whats', 'Newsletter', 'RD Marketing'];

    public static function perfis(): array
    {
        return Db::all('SELECT p.*, (SELECT COUNT(*) FROM editorial_posts x WHERE x.profile_id = p.id) AS total FROM editorial_profiles p ORDER BY p.sort_order, p.name');
    }

    public static function filtros(array $q): array
    {
        $f = [];
        if (!empty($q['perfil']) && ctype_digit((string) $q['perfil'])) {
            $f['perfil'] = (int) $q['perfil'];
        }
        if (!empty($q['rede']) && in_array($q['rede'], self::REDES, true)) {
            $f['rede'] = (string) $q['rede'];
        }
        if (!empty($q['status']) && isset(self::STATUS[$q['status']])) {
            $f['status'] = (string) $q['status'];
        }
        return $f;
    }

    public static function consultar(array $f, ?string $ini = null, ?string $fim = null): array
    {
        $w = ['1=1'];
        $p = [];
        foreach (['perfil' => 'e.profile_id', 'rede' => 'e.network', 'status' => 'e.status'] as $k => $col) {
            if (isset($f[$k])) {
                $w[] = "$col = ?";
                $p[] = $f[$k];
            }
        }
        if ($ini !== null && $fim !== null) {
            $w[] = 'e.pub_date BETWEEN ? AND ?';
            array_push($p, $ini, $fim);
        }
        return Db::all(
            'SELECT e.*, pr.name AS perfil, pr.color AS perfil_cor, u.name AS dono
               FROM editorial_posts e JOIN editorial_profiles pr ON pr.id = e.profile_id LEFT JOIN users u ON u.id = e.owner_id
              WHERE ' . implode(' AND ', $w) . ' ORDER BY e.pub_date, e.id LIMIT 2000',
            $p
        );
    }

    public static function atrasado(array $r, string $hoje): bool
    {
        return $r['pub_date'] < $hoje && $r['status'] !== 'Publicado';
    }

    public static function obter(int $id): ?array
    {
        return Db::one('SELECT * FROM editorial_posts WHERE id = ?', [$id]);
    }

    /** @return array{0:array,1:array} dados limpos e lista de erros */
    public static function validar(array $in): array
    {
        $e = [];
        $titulo = trim((string) ($in['title'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            $e[] = 'Dê um título ao conteúdo (até 255 letras).';
        }
        $perfil = ctype_digit((string) ($in['profile_id'] ?? '')) ? (int) $in['profile_id'] : 0;
        if (!$perfil || !Db::value('SELECT 1 FROM editorial_profiles WHERE id = ?', [$perfil])) {
            $e[] = 'Escolha o perfil.';
        }
        $rede = (string) ($in['network'] ?? '');
        if (!in_array($rede, self::REDES, true)) {
            $e[] = 'Escolha a rede ou canal.';
        }
        $fmt = (string) ($in['format'] ?? '');
        if (!in_array($fmt, self::FORMATOS, true)) {
            $e[] = 'Escolha o formato.';
        }
        $st = (string) ($in['status'] ?? 'Ideia');
        if (!isset(self::STATUS[$st])) {
            $e[] = 'Escolha o status.';
        }
        $data = (string) ($in['pub_date'] ?? '');
        if (!data_valida($data)) {
            $e[] = 'Informe uma data de publicação válida.';
        }
        $dono = ctype_digit((string) ($in['owner_id'] ?? '')) ? (int) $in['owner_id'] : null;
        if ($dono && !Db::value('SELECT 1 FROM users WHERE id = ? AND active = 1', [$dono])) {
            $dono = null;
        }
        $conteudo = (string) ($in['content'] ?? '');
        if (mb_strlen($conteudo) > 100000) {
            $e[] = 'O conteúdo está grande demais.';
        }
        return [[
            'title' => $titulo, 'profile_id' => $perfil, 'network' => $rede, 'format' => $fmt, 'status' => $st,
            'pub_date' => $data, 'owner_id' => $dono, 'content' => trim($conteudo) !== '' ? $conteudo : null,
        ], $e];
    }

    public static function salvar(?int $id, array $d, array $u): int
    {
        $campos = ['title', 'profile_id', 'network', 'format', 'status', 'pub_date', 'owner_id', 'content'];
        $antes = $id ? self::obter($id) : null;
        if ($id === null) {
            Db::run('INSERT INTO editorial_posts (' . implode(',', $campos) . ', created_by) VALUES (' . implode(',', array_fill(0, count($campos), '?')) . ',?)', [...array_map(fn($c) => $d[$c], $campos), (int) $u['id']]);
            $id = Db::insertId();
        } else {
            Db::run('UPDATE editorial_posts SET ' . implode(',', array_map(fn($c) => "$c = ?", $campos)) . ' WHERE id = ?', [...array_map(fn($c) => $d[$c], $campos), $id]);
        }
        Audit::registrar($antes ? 'editar' : 'criar', 'editorial', $id, ($antes ? 'Editou' : 'Criou') . ' o conteúdo "' . $d['title'] . '"', null, $u);
        if ($d['owner_id'] && (!$antes || (int) $antes['owner_id'] !== (int) $d['owner_id'])) {
            Notificacoes::avisarVarios([(int) $d['owner_id']], 'editorial', ($u['name'] ?? 'Alguém') . ' colocou você como responsável pelo conteúdo “' . $d['title'] . '”', '/editorial/' . $id, $u, true);
        }
        return $id;
    }

    public static function mudarStatus(int $id, string $status, array $u): bool
    {
        $r = self::obter($id);
        if (!$r || !isset(self::STATUS[$status])) {
            return false;
        }
        if ($r['status'] !== $status) {
            Db::run('UPDATE editorial_posts SET status = ? WHERE id = ?', [$status, $id]);
            Audit::registrar('editar', 'editorial', $id, 'Status de "' . $r['title'] . '": ' . $r['status'] . ' → ' . $status, null, $u);
        }
        return true;
    }

    public static function excluir(int $id, array $u): void
    {
        $t = (string) Db::value('SELECT title FROM editorial_posts WHERE id = ?', [$id]);
        Db::run('DELETE FROM editorial_posts WHERE id = ?', [$id]);
        Audit::registrar('excluir', 'editorial', $id, 'Excluiu o conteúdo "' . $t . '"', null, $u);
    }
}
