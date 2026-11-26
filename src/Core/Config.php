<?php
declare(strict_types=1);

namespace Sunset\Core;

final class Config
{
    private static ?array $data = null;

    /** Config::get('db.host') */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::$data ??= require dirname(__DIR__, 2) . '/config/config.php';

        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
