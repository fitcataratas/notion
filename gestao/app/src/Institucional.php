<?php
declare(strict_types=1);

/** Textos institucionais, "Quem faz o quê" e página Projetos. */
final class Institucional
{
    public const CHAVES = ['missao', 'visao', 'valores', 'quem_faz_aviso', 'externo_intro', 'datas_importantes'];

    public static function texto(string $chave): string
    {
        return (string) Db::value('SELECT v FROM settings WHERE k = ?', [$chave]);
    }

    public const BLOG_AVISO_PADRAO = 'PUBLICAÇÃO TODA SEGUNDA! Só preencher a “Data de Publicação”!';

    public const AVISO_CORES = ['#c98a00' => 'Amarelo', '#dd6b20' => 'Laranja', '#c0392b' => 'Vermelho', '#d53f8c' => 'Rosa',
        '#6b46c1' => 'Roxo', '#2b6cb0' => 'Azul', '#167a3b' => 'Verde', '#5f6b63' => 'Cinza'];
    public const AVISO_POSICOES = ['topo' => 'No topo da página', 'ideias' => 'Entre o Banco de Ideias e as colunas',
        'pautas' => 'Antes das Pautas', 'fim' => 'No fim da página'];

    /** Aviso do Blog (texto, cor, lugar). Nunca editado → texto padrão; salvo em branco → não aparece. */
    public static function avisoBlog(): string
    {
        return self::avisoBlogDados()['texto'];
    }

    public static function avisoBlogDados(): array
    {
        $m = [];
        foreach (Db::all("SELECT k, v FROM settings WHERE k IN ('blog_aviso','blog_aviso_cor','blog_aviso_pos','blog_aviso_pauta')") as $r) {
            $m[$r['k']] = $r['v'];
        }
        return [
            'texto' => array_key_exists('blog_aviso', $m) && $m['blog_aviso'] !== null ? (string) $m['blog_aviso'] : self::BLOG_AVISO_PADRAO,
            'cor' => cor_valida($m['blog_aviso_cor'] ?? null, '#c98a00'),
            'pos' => isset(self::AVISO_POSICOES[$m['blog_aviso_pos'] ?? '']) ? $m['blog_aviso_pos'] : 'topo',
            'na_pauta' => ($m['blog_aviso_pauta'] ?? '1') !== '0',
        ];
    }

    public static function salvarAvisoBlog(array $in, array $u): void
    {
        $cor = ($in['cor'] ?? '') === 'outra' ? ($in['cor_outra'] ?? '') : ($in['cor'] ?? '');
        $pos = isset(self::AVISO_POSICOES[$in['pos'] ?? '']) ? $in['pos'] : 'topo';
        $linhas = [
            'blog_aviso' => mb_substr(trim((string) ($in['texto'] ?? '')), 0, 300),
            'blog_aviso_cor' => cor_valida((string) $cor, '#c98a00'),
            'blog_aviso_pos' => $pos,
            'blog_aviso_pauta' => empty($in['na_pauta']) ? '0' : '1',
        ];
        foreach ($linhas as $k => $v) {
            Db::run('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
        }
        Audit::registrar('editar', 'configuracao', null, 'Alterou o aviso do Post - Blog', null, $u);
    }

    public static function salvarTextos(array $in, array $u): void
    {
        $limites = ['missao' => 2000, 'visao' => 2000, 'valores' => 2000, 'quem_faz_aviso' => 1000, 'externo_intro' => 2000, 'datas_importantes' => 2000];
        foreach ($limites as $k => $max) {
            if (array_key_exists($k, $in)) {
                Db::run('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, mb_substr(trim((string) $in[$k]), 0, $max)]);
            }
        }
        Audit::registrar('editar', 'configuracao', null, 'Alterou os textos da página inicial e institucionais', null, $u);
    }

    // ------------------------------------------------------------ quem faz o quê
    public static function quemFaz(): array
    {
        $l = Db::all(
            'SELECT w.*, u.photo, u.name AS usuario_nome FROM who_does w LEFT JOIN users u ON u.id = w.user_id ORDER BY w.sort_order, w.id'
        );
        foreach ($l as &$r) {
            $r['lista_duties'] = self::linhas($r['duties']);
            $r['lista_contact'] = self::linhas($r['contact_for']);
        }
        return $l;
    }

    public static function linhas(?string $t): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $t) ?: []), fn($x) => $x !== ''));
    }

    /** Devolve a lista de erros (vazia = salvou). */
    public static function salvarQuemFaz(?int $id, array $in, array $u): array
    {
        $nome = trim((string) ($in['person_name'] ?? ''));
        $funcao = trim((string) ($in['role_label'] ?? ''));
        $duties = trim((string) ($in['duties'] ?? ''));
        $contato = trim((string) ($in['contact_for'] ?? ''));
        $e = [];
        if ($nome === '' || mb_strlen($nome) > 120) {
            $e[] = 'Informe o nome da pessoa (até 120 letras).';
        }
        if ($funcao === '' || mb_strlen($funcao) > 160) {
            $e[] = 'Informe o setor ou a função (até 160 letras).';
        }
        if (mb_strlen($duties) > 5000 || mb_strlen($contato) > 5000) {
            $e[] = 'Algum texto passou do tamanho permitido.';
        }
        $uid = ($in['user_id'] ?? '') !== '' && ctype_digit((string) $in['user_id']) ? (int) $in['user_id'] : null;
        if ($uid && !Db::value('SELECT 1 FROM users WHERE id = ?', [$uid])) {
            $uid = null;
        }
        if ($e) {
            return $e;
        }
        $ordem = max(0, min(999, (int) ($in['sort_order'] ?? 0)));
        if ($id === null) {
            if ($ordem === 0) {
                $ordem = (int) Db::value('SELECT COALESCE(MAX(sort_order),0)+1 FROM who_does');
            }
            Db::run('INSERT INTO who_does (user_id, person_name, role_label, duties, contact_for, sort_order) VALUES (?,?,?,?,?,?)', [$uid, $nome, $funcao, $duties, $contato, $ordem]);
            Audit::registrar('criar', 'configuracao', Db::insertId(), 'Quem faz o quê: incluiu ' . $nome, null, $u);
        } else {
            Db::run('UPDATE who_does SET user_id = ?, person_name = ?, role_label = ?, duties = ?, contact_for = ?, sort_order = ? WHERE id = ?', [$uid, $nome, $funcao, $duties, $contato, $ordem, $id]);
            Audit::registrar('editar', 'configuracao', $id, 'Quem faz o quê: editou ' . $nome, null, $u);
        }
        return [];
    }

    // ------------------------------------------------------------------ projetos
    public static function projetos(): array
    {
        $abertos = "'" . implode("','", Config::STATUS_ABERTOS) . "'";
        return Db::all(
            "SELECT p.id, p.name, p.color, p.description, p.is_main,
                    (SELECT COUNT(*) FROM task_projects tp JOIN tasks t ON t.id = tp.task_id WHERE tp.project_id = p.id AND t.status IN ($abertos)) AS tarefas_abertas,
                    (SELECT COUNT(*) FROM task_projects tp WHERE tp.project_id = p.id) AS tarefas_total,
                    (SELECT COUNT(*) FROM posts po JOIN post_options o ON o.id = po.project_id WHERE o.name = p.name) AS postagens,
                    (SELECT COUNT(*) FROM meetings m WHERE m.project_id = p.id) AS reunioes
               FROM projects p ORDER BY p.is_main DESC, p.sort_order, p.name"
        );
    }

    public static function salvarProjeto(int $id, array $in, array $u): bool
    {
        $p = Db::one('SELECT name FROM projects WHERE id = ?', [$id]);
        if (!$p) {
            return false;
        }
        $desc = mb_substr(trim((string) ($in['description'] ?? '')), 0, 3000);
        Db::run('UPDATE projects SET description = ?, is_main = ? WHERE id = ?', [$desc !== '' ? $desc : null, !empty($in['is_main']) ? 1 : 0, $id]);
        Audit::registrar('editar', 'projeto', $id, 'Editou a descrição do projeto ' . $p['name'], null, $u);
        return true;
    }
}
