<?php
declare(strict_types=1);

/**
 * Recebe imagens enviadas (foto de perfil, logo). Nunca guarda o arquivo original:
 * a imagem é aberta, conferida e REGRAVADA do zero. Isso remove qualquer coisa
 * escondida dentro do arquivo e padroniza o tamanho.
 */
final class Imagem
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_LADO = 6000;   // evita imagens gigantes que estourariam a memória

    /**
     * @return array{ok:bool, erro?:string, bin?:string}
     * @param string $modo 'foto' (quadrada, JPEG) ou 'logo' (mantém proporção e transparência, PNG)
     */
    public static function processar(?array $arq, string $modo): array
    {
        if (!$arq || ($arq['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'erro' => 'Escolha uma imagem.'];
        }
        if ($arq['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'erro' => in_array($arq['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'A imagem é grande demais (máximo 5 MB).' : 'Não foi possível receber o arquivo. Tente de novo.'];
        }
        if (!extension_loaded('gd')) {
            return ['ok' => false, 'erro' => 'O servidor não tem suporte a imagens ativado.'];
        }
        if ($arq['size'] > self::MAX_BYTES) {
            return ['ok' => false, 'erro' => 'A imagem é grande demais (máximo 5 MB).'];
        }
        $info = @getimagesize($arq['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return ['ok' => false, 'erro' => 'Use uma imagem JPG, PNG ou WEBP.'];
        }
        if ($info[0] > self::MAX_LADO || $info[1] > self::MAX_LADO || $info[0] < 1 || $info[1] < 1) {
            return ['ok' => false, 'erro' => 'A imagem tem dimensões grandes demais.'];
        }
        $dados = (string) file_get_contents($arq['tmp_name']);
        $orig = @imagecreatefromstring($dados);
        if (!$orig) {
            return ['ok' => false, 'erro' => 'Não consegui abrir essa imagem. Tente salvar de novo em JPG ou PNG.'];
        }
        // fotos de celular vêm "de lado": corrige pela orientação gravada no arquivo
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $ex = @exif_read_data($arq['tmp_name']);
            $o = (int) ($ex['Orientation'] ?? 1);
            $giro = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($giro) {
                $r = imagerotate($orig, $giro, 0);
                if ($r) {
                    $orig = $r;
                }
            }
        }
        $w = imagesx($orig);
        $h = imagesy($orig);

        if ($modo === 'foto') {
            $lado = min($w, $h);
            $dest = imagecreatetruecolor(320, 320);
            imagefill($dest, 0, 0, imagecolorallocate($dest, 255, 255, 255));
            imagecopyresampled($dest, $orig, 0, 0, intdiv($w - $lado, 2), intdiv($h - $lado, 2), 320, 320, $lado, $lado);
            ob_start();
            imagejpeg($dest, null, 86);
        } else {
            $esc = min(1, 640 / $w, 160 / $h);
            $nw = max(1, (int) round($w * $esc));
            $nh = max(1, (int) round($h * $esc));
            $dest = imagecreatetruecolor($nw, $nh);
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            imagefill($dest, 0, 0, imagecolorallocatealpha($dest, 0, 0, 0, 127));
            imagecopyresampled($dest, $orig, 0, 0, 0, 0, $nw, $nh, $w, $h);
            ob_start();
            imagepng($dest, null, 7);
        }
        $bin = (string) ob_get_clean();
        return $bin !== '' ? ['ok' => true, 'bin' => $bin] : ['ok' => false, 'erro' => 'Falha ao preparar a imagem.'];
    }

    public static function pasta(string $nome): string
    {
        $p = APP_ROOT . '/storage/' . $nome;
        if (!is_dir($p)) {
            @mkdir($p, 0775, true);
        }
        return $p;
    }

    /** Guarda a imagem com um nome aleatório e devolve o código (24 letras/números). */
    public static function guardar(string $pasta, string $bin, string $ext): string
    {
        $cod = bin2hex(random_bytes(12));
        file_put_contents(self::pasta($pasta) . "/$cod.$ext", $bin);
        return $cod;
    }

    public static function apagar(string $pasta, ?string $cod, string $ext): void
    {
        if ($cod && preg_match('/^[0-9a-f]{24}$/', $cod)) {
            @unlink(self::pasta($pasta) . "/$cod.$ext");
        }
    }

    /** Envia o arquivo guardado ao navegador (com cache longo: o código muda a cada troca). */
    public static function enviar(string $pasta, string $cod, string $ext, string $tipo): never
    {
        $f = self::pasta($pasta) . "/$cod.$ext";
        if (!preg_match('/^[0-9a-f]{24}$/', $cod) || !is_file($f)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . $tipo);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=31536000, immutable');
        header('Content-Length: ' . filesize($f));
        readfile($f);
        exit;
    }
}
