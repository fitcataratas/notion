<?php
declare(strict_types=1);

/**
 * Aparência do sistema, editável pelo administrador (Administração → Aparência).
 * Tudo fica na tabela "settings" (chaves que começam com ap_). O CSS final é gerado
 * aqui e entregue em /tema.css, por cima do visual padrão.
 */
final class Aparencia
{
    public const CANTOS = ['retos' => ['Retos', '4px'], 'medios' => ['Médios', '10px'], 'redondos' => ['Bem arredondados', '18px']];
    public const TEXTO = ['pequeno' => ['Pequeno', '14px'], 'normal' => ['Normal', '15px'], 'grande' => ['Grande', '17px']];
    public const TEMAS = ['auto' => 'Automático (segue o aparelho)', 'claro' => 'Claro', 'escuro' => 'Escuro'];
    public const CORES_SUGERIDAS = ['#167a3b', '#0f766e', '#2563eb', '#7c3aed', '#be185d', '#dc2626', '#d97706', '#475569'];

    private const COR_STATUS = ['a-fazer' => '#d97706', 'em-andamento' => '#2563eb', 'aguardando' => '#ea580c', 'concluido' => '#16a34a', 'cancelado' => '#dc2626'];
    private const COR_PRIO = ['alta' => '#dc2626', 'media' => '#d97706', 'baixa' => '#6b7280'];

    private static ?array $cache = null;

    public static function padroes(): array
    {
        $d = ['nome' => 'De Angeli', 'subtitulo' => 'Gestão interna', 'marca' => '#167a3b', 'cantos' => 'medios',
              'texto' => 'normal', 'tema' => 'auto', 'logo' => ''];
        foreach (self::COR_STATUS as $k => $c) {
            $d['status_' . $k] = $c;
        }
        foreach (self::COR_PRIO as $k => $c) {
            $d['prio_' . $k] = $c;
        }
        return $d;
    }

    public static function todas(): array
    {
        if (self::$cache === null) {
            self::$cache = self::padroes();
            try {
                foreach (Db::all("SELECT k, v FROM settings WHERE k LIKE 'ap\\_%'") as $r) {
                    $chave = substr($r['k'], 3);
                    if (array_key_exists($chave, self::$cache) && $r['v'] !== null && $r['v'] !== '') {
                        self::$cache[$chave] = $r['v'];
                    }
                }
            } catch (Throwable) {
                // banco ainda sem a tabela: usa o padrão
            }
        }
        return self::$cache;
    }

    public static function get(string $k): string
    {
        return (string) (self::todas()[$k] ?? '');
    }

    public static function nome(): string
    {
        return self::get('nome');
    }

    /** Nome → sigla de 2 letras para o selo do menu (ex.: "De Angeli" → "DA"). */
    public static function sigla(): string
    {
        return iniciais(self::nome()) ?: 'GS';
    }

    /** Valida e grava. $in vem de $_POST. @return string[] erros */
    public static function salvar(array $in): array
    {
        $erros = [];
        $nome = trim((string) ($in['nome'] ?? ''));
        $sub = trim((string) ($in['subtitulo'] ?? ''));
        if ($nome === '' || mb_strlen($nome) > 40) {
            $erros[] = 'Informe o nome do sistema (até 40 letras).';
        }
        if (mb_strlen($sub) > 40) {
            $erros[] = 'O subtítulo pode ter até 40 letras.';
        }
        if (!isset(self::CANTOS[$in['cantos'] ?? ''])) {
            $erros[] = 'Escolha o formato dos cantos.';
        }
        if (!isset(self::TEXTO[$in['texto'] ?? ''])) {
            $erros[] = 'Escolha o tamanho do texto.';
        }
        if (!isset(self::TEMAS[$in['tema'] ?? ''])) {
            $erros[] = 'Escolha o tema padrão.';
        }
        $cores = [];
        foreach (array_merge(['marca'], array_map(fn ($k) => "status_$k", array_keys(self::COR_STATUS)), array_map(fn ($k) => "prio_$k", array_keys(self::COR_PRIO))) as $k) {
            $v = (string) ($in[$k] ?? '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                $erros[] = 'Uma das cores é inválida. Escolha de novo.';
                break;
            }
            $cores[$k] = strtolower($v);
        }
        if ($erros) {
            return $erros;
        }
        $novo = ['nome' => $nome, 'subtitulo' => $sub, 'cantos' => $in['cantos'], 'texto' => $in['texto'], 'tema' => $in['tema']] + $cores;
        foreach ($novo as $k => $v) {
            self::gravar($k, (string) $v);
        }
        return [];
    }

    public static function gravar(string $k, string $v): void
    {
        Db::run('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', ['ap_' . $k, $v]);
        self::$cache = null;
    }

    public static function restaurar(): void
    {
        $logo = self::get('logo');
        Db::run("DELETE FROM settings WHERE k LIKE 'ap\\_%'");
        self::$cache = null;
        Imagem::apagar('aparencia', $logo, 'png');
    }

    /** Preto ou branco, o que ficar mais legível sobre a cor. */
    public static function textoSobre(string $hex): string
    {
        [$r, $g, $b] = array_map(fn ($c) => hexdec($c) / 255, str_split(ltrim($hex, '#'), 2));
        $lin = fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $lum = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
        return $lum > 0.38 ? '#1d2420' : '#ffffff';
    }

    /** CSS gerado a partir das escolhas. Só contém cores/medidas já validadas. */
    public static function css(): string
    {
        $a = self::todas();
        $m = $a['marca'];
        $c = [];
        $c[] = ":root{--marca:$m;--marca-texto:" . self::textoSobre($m) . ";--marca-escura:color-mix(in srgb,$m 72%,#000);"
             . "--marca-clara:color-mix(in srgb,$m 13%,var(--superficie));--raio:" . self::CANTOS[$a['cantos']][1] . ';--fonte:' . self::TEXTO[$a['texto']][1] . '}';
        $c[] = "@media (prefers-color-scheme:dark){:root:not([data-tema=\"claro\"]){--marca-escura:color-mix(in srgb,$m 62%,#fff)}}";
        $c[] = ":root[data-tema=\"escuro\"]{--marca-escura:color-mix(in srgb,$m 62%,#fff)}";
        foreach (array_keys(self::COR_STATUS) as $k) {
            $x = $a["status_$k"];
            $c[] = ".st-$k{--cor:$x;--b:color-mix(in srgb,var(--cor) 18%,var(--superficie));--t:color-mix(in srgb,var(--cor) 72%,var(--texto));--l:var(--cor)}";
        }
        foreach (array_keys(self::COR_PRIO) as $k) {
            $x = $a["prio_$k"];
            $c[] = ".pr-$k{--cor:$x;background:color-mix(in srgb,var(--cor) 18%,var(--superficie));color:color-mix(in srgb,var(--cor) 72%,var(--texto))}";
        }
        return implode("\n", $c) . "\n";
    }

    public static function urlLogo(): ?string
    {
        $l = self::get('logo');
        return preg_match('/^[0-9a-f]{24}$/', $l) ? "/marca/$l" : (is_file(APP_ROOT . '/public/assets/logo.png') ? '/assets/logo.png' : null);
    }
}
