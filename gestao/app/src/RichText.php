<?php
declare(strict_types=1);

/** Texto formatado (negrito, listas, links…) com limpeza no servidor: só entram as marcas permitidas abaixo. */
final class RichText
{
    private const TROCA = ['strong' => 'strong', 'b' => 'strong', 'em' => 'em', 'i' => 'em', 'u' => 'u', 's' => 's', 'strike' => 's', 'del' => 's',
        'h1' => 'h2', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h3', 'h5' => 'h3', 'h6' => 'h3', 'p' => 'p', 'div' => 'p',
        'ul' => 'ul', 'ol' => 'ol', 'li' => 'li', 'blockquote' => 'blockquote'];
    private const DESCARTA = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'noscript', 'head', 'title', 'meta', 'link', 'form', 'textarea', 'select', 'button', 'input'];

    /** Devolve o HTML limpo, ou null se não sobrou conteúdo. Texto sem marcas vira parágrafos. */
    public static function limpar(?string $in): ?string
    {
        $in = trim((string) $in);
        if ($in === '') {
            return null;
        }
        if (!preg_match('/<\/?[a-z][^>]*>/i', $in)) {
            return self::deTextoSimples($in);
        }
        $dom = new DOMDocument('1.0', 'UTF-8');
        $antes = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><body><div id="raiz">' . $in . '</div></body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($antes);
        $raiz = (new DOMXPath($dom))->query('//div[@id="raiz"]')->item(0);
        if (!$raiz) {
            return self::deTextoSimples(strip_tags($in));
        }
        $h = self::filhos($raiz);
        $h = preg_replace('~<(p|h2|h3|li|blockquote|strong|em|u|s)>(\s|<br>)*</\1>~i', '', $h);
        $h = preg_replace('~(<p>\s*</p>\s*)+~', '', (string) $h);
        $h = trim((string) $h);
        return trim(strip_tags($h)) === '' && !preg_match('/<(br|li)/', $h) ? null : $h;
    }

    private static function filhos(DOMNode $n): string
    {
        $h = '';
        foreach ($n->childNodes as $c) {
            $h .= self::no($c);
        }
        return $h;
    }

    private static function no(DOMNode $n): string
    {
        if ($n instanceof DOMText) {
            return htmlspecialchars($n->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (!$n instanceof DOMElement) {
            return '';
        }
        $t = strtolower($n->tagName);
        if (in_array($t, self::DESCARTA, true)) {
            return '';
        }
        $dentro = self::filhos($n);
        if ($t === 'br') {
            return '<br>';
        }
        if ($t === 'a') {
            $href = trim($n->getAttribute('href'));
            if (preg_match('~^(https?://|mailto:)[^\s"<>]{1,500}$~i', $href)) {
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . $dentro . '</a>';
            }
            return $dentro;
        }
        if (isset(self::TROCA[$t])) {
            $tag = self::TROCA[$t];
            // o Google Docs embrulha tudo em <b style="font-weight:normal">: isso não é negrito
            if ($tag === 'strong' && preg_match('/font-weight\s*:\s*(normal|400)/i', $n->getAttribute('style'))) {
                return $dentro;
            }
            return "<$tag>$dentro</$tag>";
        }
        if ($t === 'span') {   // negrito/itálico vindos de texto colado
            $st = $n->getAttribute('style');
            if (preg_match('/font-weight\s*:\s*(bold|[6-9]00)/i', $st)) {
                $dentro = "<strong>$dentro</strong>";
            }
            if (preg_match('/font-style\s*:\s*italic/i', $st)) {
                $dentro = "<em>$dentro</em>";
            }
            if (preg_match('/text-decoration[^;]*underline/i', $st)) {
                $dentro = "<u>$dentro</u>";
            }
        }
        return $dentro;
    }

    /** Texto simples (como era antes) → parágrafos; endereços http(s) viram link. */
    public static function deTextoSimples(string $t): string
    {
        $t = str_replace(["\r\n", "\r"], "\n", trim($t));
        $out = [];
        foreach (preg_split('/\n{2,}/', $t) ?: [] as $par) {
            $h = htmlspecialchars($par, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $h = preg_replace_callback('~(?<![">=])(https?://[^\s<]+)~u', function ($m) {
                $u = rtrim($m[1], '.,;:!?)');
                return '<a href="' . $u . '" target="_blank" rel="noopener noreferrer">' . $u . '</a>' . substr($m[1], strlen($u));
            }, $h);
            $out[] = '<p>' . str_replace("\n", '<br>', (string) $h) . '</p>';
        }
        return implode('', $out);
    }

    /** O que vai dentro do editor: texto antigo (simples) é convertido na hora; texto já formatado segue como está. */
    public static function paraEditor(?string $notas, bool $formatado): string
    {
        $notas = (string) $notas;
        if ($notas === '') {
            return '';
        }
        return $formatado ? $notas : self::deTextoSimples($notas);
    }
}
