<?php
declare(strict_types=1);

namespace Sunset\Helpers;

/** Cada método devuelve el mensaje de error, o null si el dato es válido. */
final class Validator
{
    public static function username(string $v): ?string
    {
        return preg_match('/^[A-Za-z0-9_]{3,30}$/', $v)
            ? null
            : 'Usa entre 3 y 30 caracteres: letras, números o guion bajo.';
    }

    public static function email(string $v): ?string
    {
        return (strlen($v) <= 150 && filter_var($v, FILTER_VALIDATE_EMAIL))
            ? null
            : 'Ingresa un correo electrónico válido.';
    }

    public static function password(string $v): ?string
    {
        if (mb_strlen($v) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (strlen($v) > 72) { // límite real de bcrypt
            return 'La contraseña no puede superar los 72 caracteres.';
        }
        return null;
    }
}
