<?php
declare(strict_types=1);

/** Leitura de configurações vindas das variáveis de ambiente (docker-compose / .env). */
final class Env
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $v = getenv($key);
        if ($v === false || $v === '') {
            return $default;
        }
        return $v;
    }

    public static function isDev(): bool
    {
        return self::get('APP_ENV', 'producao') === 'desenvolvimento';
    }
}
