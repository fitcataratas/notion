<?php
declare(strict_types=1);

/** Blog: Banco de Ideias (quadro) e Pautas, com as 3 automações do Notion (memorial, seções 13 a 20). */
final class Blog
{
    /** Colunas do quadro, na ordem do Notion, com a cor de cada uma. */
    public const AVALIACOES = [
        'Sem Avaliação' => '#718096', 'Em revisão' => '#b7791f', 'Aprovado' => '#2f855a',
        'Publicado' => '#2b6cb0', 'Reprovado' => '#c53030', 'Cancelado' => '#c05621',
    ];
    public const STATUS_PAUTA = [
        'Ideia recebida' => '#718096', 'Em pesquisa' => '#b7791f', 'Pesquisa entregue' => '#2b6cb0', 'Em redação' => '#7c5cbf',
        'Pronto para publicar' => '#d53f8c', 'Publicado' => '#2f855a', 'Cancelada' => '#c53030',
    ];
    public const PUBLICOS = ['Agentes de viagens', 'Operadoras', 'Destinos', 'Hotelaria', 'Expositores', 'Profissionais do turismo', 'Público geral'];
    public const DIAS_PESQUISA = 5;   // pesquisa = publicação − 5 dias
    public const DIAS_TEXTO = 3;      // texto = publicação − 3 dias

    /** Mover ideias entre as colunas: qualquer pessoa da equipe (e administradores). */
    public static function podeAvaliar(array $u): bool
    {
        return Perm::equipe($u);
    }

    /** Apagar ideia: só administradores e responsáveis do setor Coordenação. */
    public static function podeApagar(array $u): bool
    {
        if (Perm::admin($u)) {
            return true;
        }
        return (bool) Db::value(
            "SELECT 1 FROM sector_leads l JOIN sectors s ON s.id = l.sector_id WHERE s.name = 'Coordenação' AND l.user_id = ? LIMIT 1",
            [(int) $u['id']]
        );
    }

    /** [prazo da pesquisa, data do texto] calculados a partir da publicação (formato AAAA-MM-DD). */
    public static function prazos(?string $publicacao): array
    {
        if (!$publicacao || !data_valida($publicacao)) {
            return [null, null];
        }
        $d = new DateTimeImmutable($publicacao);
        return [$d->modify('-' . self::DIAS_PESQUISA . ' days')->format('Y-m-d'), $d->modify('-' . self::DIAS_TEXTO . ' days')->format('Y-m-d')];
    }

    public static function ehSegunda(string $ymd): bool
    {
        return (new DateTimeImmutable($ymd))->format('N') === '1';
    }

    // ------------------------------------------------------------------ ideias
    public static function ideias(string $q = ''): array
    {
        $w = '';
        $p = [];
        if ($q !== '') {
            $w = 'WHERE i.title LIKE ?';
            $p[] = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        }
        return Db::all(
            "SELECT i.id, i.title, i.evaluation, i.created_at, i.image_code, pa.id AS pauta_id, pa.status AS pauta_status
               FROM blog_ideas i LEFT JOIN blog_pautas pa ON pa.idea_id = i.id $w ORDER BY i.created_at DESC, i.id DESC",
            $p
        );
    }

    /** Responsáveis de todas as ideias: [id da ideia => [pessoas]]. */
    public static function responsaveisPorIdeia(): array
    {
        $m = [];
        foreach (Db::all('SELECT bp.idea_id, u.id, u.name, u.photo FROM blog_idea_people bp JOIN users u ON u.id = bp.user_id ORDER BY u.name') as $r) {
            $m[(int) $r['idea_id']][] = $r;
        }
        return $m;
    }

    public static function ideia(int $id): ?array
    {
        $i = Db::one(
            'SELECT i.*, u.name AS autor, pa.id AS pauta_id, pa.status AS pauta_status
               FROM blog_ideas i LEFT JOIN users u ON u.id = i.created_by LEFT JOIN blog_pautas pa ON pa.idea_id = i.id WHERE i.id = ?',
            [$id]
        );
        if ($i) {
            $i['_resp'] = array_map('intval', array_column(Db::all('SELECT user_id FROM blog_idea_people WHERE idea_id = ?', [$id]), 'user_id'));
        }
        return $i;
    }

    public static function criarIdeia(string $titulo, ?string $notas, array $u): int|string
    {
        $titulo = trim($titulo);
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            return 'Escreva a ideia (até 255 letras).';
        }
        if (self::tituloExiste($titulo, null)) {
            return 'Já existe uma ideia com esse título no Banco de Ideias.';
        }
        Db::run('INSERT INTO blog_ideas (title, notes, created_by) VALUES (?,?,?)', [$titulo, $notas !== null && trim($notas) !== '' ? $notas : null, (int) $u['id']]);
        $id = Db::insertId();
        Audit::registrar('criar', 'blog', $id, 'Nova ideia de blog: ' . $titulo, null, $u);
        return $id;
    }

    private static function tituloExiste(string $titulo, ?int $exceto): bool
    {
        return (bool) Db::value('SELECT 1 FROM blog_ideas WHERE title = ? AND id <> ?', [$titulo, (int) $exceto]);
    }

    /**
     * Salva título, texto, responsáveis e imagem da ideia. Devolve a mensagem de erro, ou null se deu certo.
     * @param int[] $responsaveis ids marcados
     */
    public static function salvarIdeia(int $id, string $titulo, ?string $notas, array $responsaveis, ?array $imagem, bool $removerImagem, array $u): ?string
    {
        $titulo = trim($titulo);
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            return 'Escreva o título da ideia (até 255 letras).';
        }
        if (mb_strlen((string) $notas) > 200000) {
            return 'O texto está grande demais.';
        }
        if (self::tituloExiste($titulo, $id)) {
            return 'Já existe outra ideia com esse título.';
        }
        $atual = Db::one('SELECT image_code, image_ext FROM blog_ideas WHERE id = ?', [$id]);
        $temArquivo = $imagem && ($imagem['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $img = null;
        if ($temArquivo) {
            $img = Imagem::processar($imagem, 'artigo');
            if (!$img['ok']) {
                return $img['erro'] ?? 'Não consegui receber a imagem.';
            }
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $responsaveis))));
        if ($ids) {
            $marcas = implode(',', array_fill(0, count($ids), '?'));
            $ids = array_map('intval', array_column(Db::all("SELECT id FROM users WHERE active = 1 AND role <> 'externo' AND id IN ($marcas)", $ids), 'id'));
        }
        $antes = array_map('intval', array_column(Db::all('SELECT user_id FROM blog_idea_people WHERE idea_id = ?', [$id]), 'user_id'));

        Db::run('UPDATE blog_ideas SET title = ?, notes = ? WHERE id = ?', [$titulo, $notas !== null && trim($notas) !== '' ? $notas : null, $id]);
        Db::run('DELETE FROM blog_idea_people WHERE idea_id = ?', [$id]);
        foreach ($ids as $uid) {
            Db::run('INSERT INTO blog_idea_people (idea_id, user_id) VALUES (?, ?)', [$id, $uid]);
        }
        foreach (array_diff($ids, $antes) as $uid) {
            Notificacoes::avisar($uid, 'blog', ($u['name'] ?? 'Alguém') . ' colocou você como responsável pela ideia de blog “' . $titulo . '”', '/blog/ideias/' . $id, $u);
        }
        $mudou = [];
        if (array_diff($ids, $antes) || array_diff($antes, $ids)) {
            $nomes = fn(array $l) => $l ? implode(', ', array_column(Db::all('SELECT name FROM users WHERE id IN (' . implode(',', array_map('intval', $l)) . ') ORDER BY name'), 'name')) : '—';
            $mudou['Responsáveis'] = [$nomes($antes), $nomes($ids)];
        }
        if ($img && $img['ok']) {
            $cod = Imagem::guardar('blog', $img['bin'], $img['ext']);
            $nome = mb_substr(preg_replace('/[^\p{L}\p{N}._ -]+/u', '', pathinfo((string) $imagem['name'], PATHINFO_FILENAME)) ?: 'imagem', 0, 100);
            Db::run('UPDATE blog_ideas SET image_code = ?, image_ext = ?, image_name = ? WHERE id = ?', [$cod, $img['ext'], $nome . '.' . $img['ext'], $id]);
            if ($atual && $atual['image_code']) {
                Imagem::apagar('blog', $atual['image_code'], (string) $atual['image_ext']);
            }
            $mudou['Imagem'] = [$atual && $atual['image_code'] ? 'trocada' : '—', 'enviada'];
        } elseif ($removerImagem && $atual && $atual['image_code']) {
            Imagem::apagar('blog', $atual['image_code'], (string) $atual['image_ext']);
            Db::run('UPDATE blog_ideas SET image_code = NULL, image_ext = NULL, image_name = NULL WHERE id = ?', [$id]);
            $mudou['Imagem'] = ['enviada', 'removida'];
        }
        Audit::registrar('editar', 'blog', $id, 'Editou a ideia de blog ' . $titulo, $mudou ?: null, $u);
        return null;
    }

    /**
     * Muda a avaliação e dispara as automações do Notion.
     * Devolve a mensagem para o usuário, ou null se a avaliação for inválida.
     */
    public static function mudarAvaliacao(int $id, string $nova, array $u): ?string
    {
        $i = Db::one('SELECT id, title, evaluation, created_by FROM blog_ideas WHERE id = ?', [$id]);
        if (!$i || !isset(self::AVALIACOES[$nova])) {
            return null;
        }
        if ($i['evaluation'] === $nova) {
            return 'A ideia já estava em “' . $nova . '”.';
        }
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            Db::run('UPDATE blog_ideas SET evaluation = ? WHERE id = ?', [$nova, $id]);
            $pauta = Db::one('SELECT id, status FROM blog_pautas WHERE idea_id = ?', [$id]);
            $msg = 'Ideia movida para “' . $nova . '”.';
            if ($nova === 'Aprovado') {
                // Ideia aprovada → criar pauta (só se ainda não existir: evita duplicar).
                if (!$pauta) {
                    Db::run(
                        "INSERT INTO blog_pautas (title, status, idea_id, requester_id) VALUES (?, 'Ideia recebida', ?, ?)",
                        [$i['title'], $id, $i['created_by']]
                    );
                    $pid = Db::insertId();
                    Audit::registrar('criar', 'blog', $pid, 'Pauta criada automaticamente a partir de: ' . $i['title'], null, $u);
                    $msg .= ' A pauta foi criada automaticamente.';
                } else {
                    $msg .= ' Essa ideia já tinha pauta (' . $pauta['status'] . '); nada foi duplicado.';
                }
            } elseif ($nova === 'Cancelado' && $pauta) {
                Db::run("UPDATE blog_pautas SET status = 'Cancelada' WHERE id = ?", [$pauta['id']]);
                $msg .= ' A pauta foi marcada como Cancelada.';
                self::avisarPauta((int) $pauta['id'], 'A pauta “' . $i['title'] . '” foi cancelada', $u);
            } elseif ($nova === 'Publicado' && $pauta) {
                Db::run("UPDATE blog_pautas SET status = 'Publicado' WHERE id = ?", [$pauta['id']]);
                $msg .= ' A pauta foi marcada como Publicado.';
                self::avisarPauta((int) $pauta['id'], 'A pauta “' . $i['title'] . '” foi publicada', $u);
            } elseif ($nova === 'Publicado' && !$pauta) {
                $msg .= ' Atenção: essa ideia não tem pauta.';
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        Audit::registrar('editar', 'blog', $id, 'Ideia “' . $i['title'] . '”: avaliação ' . $i['evaluation'] . ' → ' . $nova, ['Avaliação' => [$i['evaluation'], $nova]], $u);
        return $msg;
    }

    /** Ideia que já gerou pauta não é apagada: move-se para Cancelado. */
    public static function excluirIdeia(int $id, array $u): ?string
    {
        $i = Db::one('SELECT title FROM blog_ideas WHERE id = ?', [$id]);
        if (!$i) {
            return 'Ideia não encontrada.';
        }
        if (Db::value('SELECT 1 FROM blog_pautas WHERE idea_id = ?', [$id])) {
            return 'Essa ideia já gerou uma pauta e não pode ser apagada. Mova para “Cancelado”.';
        }
        $im = Db::one('SELECT image_code, image_ext FROM blog_ideas WHERE id = ?', [$id]);
        Db::run('DELETE FROM blog_ideas WHERE id = ?', [$id]);
        if ($im && $im['image_code']) {
            Imagem::apagar('blog', $im['image_code'], (string) $im['image_ext']);
        }
        Audit::registrar('excluir', 'blog', $id, 'Apagou a ideia de blog ' . $i['title'], null, $u);
        return null;
    }

    // ------------------------------------------------------------------ pautas
    public static function pautas(array $f = []): array
    {
        $w = ['1=1'];
        $p = [];
        if (!empty($f['status']) && isset(self::STATUS_PAUTA[$f['status']])) {
            $w[] = 'pa.status = ?';
            $p[] = $f['status'];
        }
        if (!empty($f['abertas'])) {
            $w[] = "pa.status NOT IN ('Publicado','Cancelada')";
        }
        $linhas = Db::all(
            'SELECT pa.*, r.name AS pesquisa_nome, r.photo AS pesquisa_photo, s.name AS solicitante_nome
               FROM blog_pautas pa LEFT JOIN users r ON r.id = pa.researcher_id LEFT JOIN users s ON s.id = pa.requester_id
              WHERE ' . implode(' AND ', $w) . ' ORDER BY (pa.publish_date IS NULL), pa.publish_date DESC, pa.id DESC',
            $p
        );
        foreach ($linhas as &$l) {
            [$l['prazo_pesquisa'], $l['data_texto']] = self::prazos($l['publish_date']);
        }
        return $linhas;
    }

    public static function pauta(int $id): ?array
    {
        $l = Db::one(
            'SELECT pa.*, i.title AS ideia_titulo FROM blog_pautas pa LEFT JOIN blog_ideas i ON i.id = pa.idea_id WHERE pa.id = ?',
            [$id]
        );
        if ($l) {
            [$l['prazo_pesquisa'], $l['data_texto']] = self::prazos($l['publish_date']);
        }
        return $l;
    }

    /** Confere o formulário da pauta. Devolve [dados, erros]. */
    public static function validarPauta(array $in): array
    {
        $e = [];
        $titulo = trim((string) ($in['title'] ?? ''));
        if ($titulo === '' || mb_strlen($titulo) > 255) {
            $e[] = 'Dê um título à pauta (até 255 letras).';
        }
        $data = trim((string) ($in['publish_date'] ?? ''));
        if ($data !== '' && !data_valida($data)) {
            $e[] = 'A data de publicação não é válida.';
            $data = '';
        }
        $status = (string) ($in['status'] ?? 'Ideia recebida');
        if (!isset(self::STATUS_PAUTA[$status])) {
            $e[] = 'Escolha um status da pauta válido.';
            $status = 'Ideia recebida';
        }
        $url = trim((string) ($in['article_url'] ?? ''));
        if ($url !== '' && (!Setores::urlValida($url) || mb_strlen($url) > 500)) {
            $e[] = 'O link do artigo precisa começar com http:// ou https://.';
        }
        $pessoa = function (string $k) use ($in, &$e): ?int {
            $v = $in[$k] ?? '';
            if ($v === '' || $v === null) {
                return null;
            }
            if (!ctype_digit((string) $v) || !Db::value('SELECT 1 FROM users WHERE id = ?', [(int) $v])) {
                $e[] = 'Uma das pessoas escolhidas não existe mais.';
                return null;
            }
            return (int) $v;
        };
        $tema = trim((string) ($in['theme'] ?? ''));
        $pub = trim((string) ($in['audience'] ?? ''));
        $notas = (string) ($in['notes'] ?? '');
        if (mb_strlen($tema) > 5000 || mb_strlen($pub) > 160 || mb_strlen($notas) > 200000) {
            $e[] = 'Algum campo passou do tamanho permitido.';
        }
        return [[
            'title' => $titulo, 'theme' => $tema !== '' ? $tema : null, 'audience' => $pub !== '' ? $pub : null,
            'researcher_id' => $pessoa('researcher_id'), 'publish_date' => $data !== '' ? $data : null, 'status' => $status,
            'requester_id' => $pessoa('requester_id'), 'article_url' => $url !== '' ? $url : null, 'notes' => trim($notas) !== '' ? $notas : null,
        ], $e];
    }

    public static function salvarPauta(?int $id, array $d, array $u): int
    {
        $campos = ['title', 'theme', 'audience', 'researcher_id', 'publish_date', 'status', 'requester_id', 'article_url', 'notes'];
        $antes = $id ? Db::one('SELECT status, idea_id FROM blog_pautas WHERE id = ?', [$id]) : null;
        if ($id) {
            Db::run('UPDATE blog_pautas SET ' . implode(',', array_map(fn($c) => "$c = ?", $campos)) . ' WHERE id = ?', [...array_map(fn($c) => $d[$c], $campos), $id]);
        } else {
            Db::run('INSERT INTO blog_pautas (' . implode(',', $campos) . ') VALUES (' . implode(',', array_fill(0, count($campos), '?')) . ')', array_map(fn($c) => $d[$c], $campos));
            $id = Db::insertId();
        }
        Audit::registrar($antes ? 'editar' : 'criar', 'blog', $id, ($antes ? 'Editou' : 'Criou') . ' a pauta ' . $d['title'], $antes && $antes['status'] !== $d['status'] ? ['Status' => [$antes['status'], $d['status']]] : null, $u);
        self::sincronizarIdeia($id, $d['status']);
        $nome = ($u['name'] ?? 'Alguém');
        $url = '/blog/pautas/' . $id;
        $antesPesq = (int) ($antes['researcher_id'] ?? 0);
        $antesSol = (int) ($antes['requester_id'] ?? 0);
        if ($d['researcher_id'] && (int) $d['researcher_id'] !== $antesPesq) {
            Notificacoes::avisarVarios([(int) $d['researcher_id']], 'blog', $nome . ' colocou você como responsável pela pesquisa da pauta “' . $d['title'] . '”', $url, $u, true);
        }
        if ($d['requester_id'] && (int) $d['requester_id'] !== $antesSol && (int) $d['requester_id'] !== (int) $d['researcher_id']) {
            Notificacoes::avisarVarios([(int) $d['requester_id']], 'blog', $nome . ' colocou você como solicitante da pauta “' . $d['title'] . '”', $url, $u, true);
        }
        if ($antes && $antes['status'] !== $d['status']) {
            self::avisarPauta($id, 'A pauta “' . $d['title'] . '” mudou para “' . $d['status'] . '”', $u);
        }
        return $id;
    }

    public static function mudarStatusPauta(int $id, string $status, array $u): bool
    {
        $p = Db::one('SELECT title, status FROM blog_pautas WHERE id = ?', [$id]);
        if (!$p || !isset(self::STATUS_PAUTA[$status])) {
            return false;
        }
        if ($p['status'] !== $status) {
            Db::run('UPDATE blog_pautas SET status = ? WHERE id = ?', [$status, $id]);
            Audit::registrar('editar', 'blog', $id, 'Pauta “' . $p['title'] . '”: ' . $p['status'] . ' → ' . $status, ['Status' => [$p['status'], $status]], $u);
            self::sincronizarIdeia($id, $status);
            self::avisarPauta($id, 'A pauta “' . $p['title'] . '” mudou para “' . $status . '”', $u);
        }
        return true;
    }

    /** Avisa quem pesquisa e quem pediu a pauta. */
    public static function avisarPauta(int $pautaId, string $texto, array $u): void
    {
        $p = Db::one('SELECT researcher_id, requester_id FROM blog_pautas WHERE id = ?', [$pautaId]);
        if ($p) {
            Notificacoes::avisarVarios(array_filter([(int) $p['researcher_id'], (int) $p['requester_id']]), 'blog', $texto, '/blog/pautas/' . $pautaId, $u, true);
        }
    }

    /** Pauta publicada ou cancelada leva a ideia de origem junto, para o quadro nunca ficar desencontrado. */
    private static function sincronizarIdeia(int $pautaId, string $status): void
    {
        $alvo = ['Publicado' => 'Publicado', 'Cancelada' => 'Cancelado'][$status] ?? null;
        if ($alvo) {
            Db::run('UPDATE blog_ideas SET evaluation = ? WHERE id = (SELECT idea_id FROM blog_pautas WHERE id = ?) AND evaluation <> ?', [$alvo, $pautaId, $alvo]);
        }
    }

    public static function excluirPauta(int $id, array $u): void
    {
        $t = (string) Db::value('SELECT title FROM blog_pautas WHERE id = ?', [$id]);
        Db::run('DELETE FROM blog_pautas WHERE id = ?', [$id]);
        Audit::registrar('excluir', 'blog', $id, 'Excluiu a pauta ' . $t, null, $u);
    }
}
