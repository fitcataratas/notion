<?php
declare(strict_types=1);

/** Avisos e textos que o administrador coloca em qualquer página (cor, estilo e lugar à escolha). */
final class Avisos
{
    public const ESTILOS = ['faixa' => 'Faixa colorida', 'texto' => 'Texto simples (sem caixa)'];
    public const POSICOES = ['topo' => 'No topo da página', 'fim' => 'No fim da página'];

    /** Nome de cada página (usado só para o histórico). */
    public const NOMES = [
        'inicio' => 'Início', 'minhas-tarefas' => 'Minhas tarefas', 'tarefas' => 'Central de Tarefas', 'solicitacoes' => 'Solicitações MKT e Design',
        'postagens' => 'Cronograma de Postagens', 'ideias' => 'Ideias de Conteúdo', 'blog' => 'Post - Blog', 'reunioes' => 'Reuniões',
        'quem-faz-o-que' => 'Quem faz o quê', 'missao' => 'Missão, Visão e Valores', 'projetos' => 'Projetos', 'espaco' => 'Meu espaço',
    ];

    /** Qual "página" é esta? Só páginas de primeiro nível (e cada setor) recebem avisos; formulários e telas de admin não. */
    public static function chave(string $caminho): ?string
    {
        $caminho = rtrim($caminho, '/') ?: '/';
        if ($caminho === '/') {
            return 'inicio';
        }
        if (preg_match('~^/setores/(\d+)$~', $caminho, $m)) {
            return 'setor-' . $m[1];
        }
        if (preg_match('~^/([a-z-]+)$~', $caminho, $m) && isset(self::NOMES[$m[1]])) {
            return $m[1];
        }
        return null;
    }

    public static function nome(string $pagina): string
    {
        if (str_starts_with($pagina, 'setor-')) {
            return 'Setor ' . (string) Db::value('SELECT name FROM sectors WHERE id = ?', [(int) substr($pagina, 6)]);
        }
        return self::NOMES[$pagina] ?? $pagina;
    }

    public static function daPagina(string $pagina): array
    {
        return Db::all('SELECT * FROM page_notices WHERE page = ? ORDER BY sort_order, id', [$pagina]);
    }

    private static function dados(array $in, array $atual = []): array
    {
        $cor = ($in['cor'] ?? '') === 'outra' ? ($in['cor_outra'] ?? '') : ($in['cor'] ?? '');
        return [
            'body' => mb_substr(trim((string) ($in['texto'] ?? '')), 0, 1500),
            'color' => cor_valida((string) $cor, '#c98a00'),
            'style' => isset(self::ESTILOS[$in['estilo'] ?? '']) ? $in['estilo'] : 'faixa',
            'pos' => isset(self::POSICOES[$in['pos'] ?? '']) ? $in['pos'] : 'topo',
        ];
    }

    public static function criar(string $pagina, array $in, array $u): bool
    {
        $d = self::dados($in);
        if ($d['body'] === '') {
            return false;
        }
        Db::run(
            'INSERT INTO page_notices (page, body, color, style, pos, sort_order) VALUES (?, ?, ?, ?, ?, ?)',
            [$pagina, $d['body'], $d['color'], $d['style'], $d['pos'], (int) Db::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM page_notices WHERE page = ?', [$pagina])]
        );
        Audit::registrar('criar', 'configuracao', Db::insertId(), 'Adicionou um aviso em ' . self::nome($pagina), ['texto' => $d['body']], $u);
        return true;
    }

    /** Salva; texto em branco apaga o aviso. */
    public static function salvar(int $id, array $in, array $u): bool
    {
        $a = Db::one('SELECT * FROM page_notices WHERE id = ?', [$id]);
        if (!$a) {
            return false;
        }
        $d = self::dados($in);
        if ($d['body'] === '') {
            return self::excluir($id, $u);
        }
        Db::run('UPDATE page_notices SET body = ?, color = ?, style = ?, pos = ? WHERE id = ?', [$d['body'], $d['color'], $d['style'], $d['pos'], $id]);
        Audit::registrar('editar', 'configuracao', $id, 'Editou um aviso em ' . self::nome($a['page']), ['texto' => $d['body']], $u);
        return true;
    }

    public static function excluir(int $id, array $u): bool
    {
        $a = Db::one('SELECT * FROM page_notices WHERE id = ?', [$id]);
        if (!$a) {
            return false;
        }
        Db::run('DELETE FROM page_notices WHERE id = ?', [$id]);
        Audit::registrar('excluir', 'configuracao', $id, 'Removeu um aviso de ' . self::nome($a['page']), ['texto' => $a['body']], $u);
        return true;
    }
}
