<?php
declare(strict_types=1);

namespace Sunset\Helpers;

/** Mensajes que se muestran una sola vez (ej: "Bienvenido"). */
final class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function consume(): array
    {
        $messages = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messages;
    }
}
