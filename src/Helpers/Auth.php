<?php
declare(strict_types=1);

namespace Sunset\Helpers;

use Sunset\Models\User;

final class Auth
{
    private static ?array $user = null;

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && ($id = self::id()) !== null) {
            self::$user = User::findById($id);
            if (self::$user === null) {      // el usuario ya no existe
                unset($_SESSION['user_id']);
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);         // evita fijación de sesión
        $_SESSION['user_id'] = $userId;
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        session_destroy();
        self::$user = null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('auth', ['mode' => 'login']);
        }
    }

    public static function guestOnly(): void
    {
        if (self::check()) {
            redirect('home');
        }
    }
}
