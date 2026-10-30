<?php
declare(strict_types=1);

/** Escapa texto para HTML. Use SEMPRE ao exibir qualquer dado. */
function e(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/', array $query = []): string
{
    $query = array_filter($query, fn($v) => $v !== null && $v !== '' && $v !== []);
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function redirect(string $to): never
{
    // Chamada feita pelo JavaScript da página (arrastar e soltar): responde em JSON, sem recarregar nada.
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
        $msgs = array_map(fn($f) => $f['msg'], array_filter(flash() ?? [], fn($f) => $f['tipo'] !== 'erro'));
        View::json(['ok' => true, 'mensagem' => implode(' ', $msgs)]);
    }
    header('Location: ' . $to, true, 303);
    exit;
}

function slug(string $s): string
{
    $s = mb_strtolower($s);
    $s = strtr($s, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
}

function data_br(?string $ymd): string
{
    if (!$ymd) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d', substr($ymd, 0, 10));
    return $d ? $d->format('d/m/Y') : '';
}

function data_curta(?string $ymd): string
{
    if (!$ymd) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d', substr($ymd, 0, 10));
    return $d ? $d->format('d/m') : '';
}

function hora_curta(?string $hms): string
{
    return $hms ? substr($hms, 0, 5) : '';
}

function datahora_br(?string $dt): string
{
    if (!$dt) {
        return '';
    }
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
    return $d ? $d->format('d/m/Y H:i') : '';
}

function iniciais(string $nome): string
{
    $p = preg_split('/\s+/', trim($nome)) ?: [];
    $a = mb_substr($p[0] ?? '', 0, 1);
    $b = count($p) > 1 ? mb_substr(end($p), 0, 1) : '';
    return mb_strtoupper($a . $b);
}

/** Valida data AAAA-MM-DD; devolve null se inválida. */
function data_valida(?string $s): ?string
{
    $s = trim((string) $s);
    if ($s === '') {
        return null;
    }
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

function hora_valida(?string $s): ?string
{
    $s = trim((string) $s);
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s) ? $s . ':00' : null;
}

function cor_valida(?string $c, string $padrao = '#64748b'): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $c) ? strtolower($c) : $padrao;
}

/** Texto simples -> HTML seguro: quebra de linhas e links clicáveis (http/https). */
function texto_html(?string $t): string
{
    $t = (string) $t;
    if ($t === '') {
        return '';
    }
    $h = e($t);
    // [texto](https://...) e endereços soltos
    $h = preg_replace_callback(
        '~\[([^\]]+)\]\((https?://[^\s)]+)\)|(?<![">=])(https?://[^\s<]+)~u',
        function ($m) {
            if (!empty($m[2])) {
                return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
            }
            $u = rtrim($m[3], '.,;:!?)');
            $resto = substr($m[3], strlen($u));
            return '<a href="' . $u . '" target="_blank" rel="noopener noreferrer">' . $u . '</a>' . $resto;
        },
        $h
    );
    return nl2br($h, false);
}

function gerar_senha(int $tam = 14): string
{
    $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < $tam; $i++) {
        $s .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
    }
    return $s;
}

function ip_cliente(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function flash(?string $msg = null, string $tipo = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['_flash'][] = ['msg' => $msg, 'tipo' => $tipo];
        return null;
    }
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

// ------------------------------------------------------------ peças de tela

/** Etiqueta colorida de setor/projeto. A cor vem do cadastro (cor_valida garante #rrggbb). */
function chip(string $nome, string $cor): string
{
    return '<span class="chip" style="--c:' . e(cor_valida($cor)) . '">' . e($nome) . '</span>';
}

function chip_status(string $status): string
{
    return '<span class="st st-' . e(slug($status)) . '">' . e($status) . '</span>';
}

function chip_prioridade(?string $p): string
{
    return $p ? '<span class="pr pr-' . e(slug($p)) . '">' . e($p) . '</span>' : '';
}

/** Bolinha da pessoa: foto, se tiver; senão as iniciais. */
function avatar_html(array $p, string $classe = ''): string
{
    $foto = (string) ($p['photo'] ?? '');
    $ok = preg_match('/^[0-9a-f]{24}$/', $foto) === 1;
    $cl = trim('avatar ' . $classe . ($ok ? ' foto' : ''));
    $nome = e($p['name'] ?? '?');
    if ($ok) {
        return '<span class="' . e($cl) . '" title="' . $nome . '"><img src="/foto/' . $foto . '" alt="' . $nome . '" loading="lazy"></span>';
    }
    return '<span class="' . e($cl) . '" title="' . $nome . '">' . e(iniciais($p['name'] ?? '?')) . '</span>';
}

function avatares(array $pessoas, int $max = 3): string
{
    if (!$pessoas) {
        return '';
    }
    $h = '<span class="avatares">';
    foreach (array_slice($pessoas, 0, $max) as $p) {
        $h .= avatar_html($p, 'peq');
    }
    if (count($pessoas) > $max) {
        $h .= '<span class="avatar peq mais" title="' . e(implode(', ', array_column(array_slice($pessoas, $max), 'name'))) . '">+' . (count($pessoas) - $max) . '</span>';
    }
    return $h . '</span>';
}

/** Mantém os filtros atuais da barra de endereço, trocando só o que for pedido. */
function url_com(array $troca, ?string $caminho = null): string
{
    $q = array_merge($_GET, $troca);
    $caminho ??= (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    return url($caminho, $q);
}

function tarefa_atrasada(array $t, string $hoje): bool
{
    if (!in_array($t['status'], Config::STATUS_ABERTOS, true)) {
        return false;
    }
    $fim = $t['end_date'] ?: $t['start_date'];
    return $fim !== null && $fim < $hoje;
}

function data_tarefa(array $t): string
{
    if (!$t['start_date']) {
        return '';
    }
    $s = data_curta($t['start_date']) . ($t['start_time'] ? ' ' . hora_curta($t['start_time']) : '');
    if ($t['end_date'] && $t['end_date'] !== $t['start_date']) {
        $s .= ' → ' . data_curta($t['end_date']);
    }
    return $s;
}

const MESES_PT = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

/** Tema a aplicar: o da pessoa, ou o padrão do sistema. Vazio = segue o aparelho. */
function tema_atual(?array $u): string
{
    $t = $u['theme'] ?? 'auto';
    if ($t === 'auto') {
        $t = Aparencia::get('tema');
    }
    return in_array($t, ['claro', 'escuro'], true) ? $t : '';
}

/** Link do CSS gerado pela aparência (o código muda quando as escolhas mudam). */
function tema_link(): string
{
    return '<link rel="stylesheet" href="/tema.css?v=' . substr(md5(Aparencia::css()), 0, 10) . '">';
}
