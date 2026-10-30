<?php
declare(strict_types=1);

/**
 * Proteção contra "pedidos forjados": todo formulário e chamada que altera dados
 * precisa levar um código secreto que só a página legítima conhece.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function conferir(): bool
    {
        $enviado = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return is_string($enviado) && $enviado !== '' && hash_equals(self::token(), $enviado);
    }
}
