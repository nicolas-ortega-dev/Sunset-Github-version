<?php
declare(strict_types=1);

namespace Sunset\Models;

use Sunset\Core\Database;

final class User
{
    /** Datos públicos del usuario (nunca incluye el hash). */
    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT id, username, email, bio, location, website, avatar_path, created_at
             FROM users WHERE id = :id'
        );
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    /** Busca por correo o por nombre de usuario (incluye el hash, solo para login). */
    public static function findByLogin(string $login): ?array
    {
        $st = Database::pdo()->prepare(
            'SELECT id, username, password_hash FROM users
             WHERE email = :email OR username = :username LIMIT 1'
        );
        $st->execute(['email' => $login, 'username' => $login]);
        return $st->fetch() ?: null;
    }

    public static function usernameExists(string $username): bool
    {
        $st = Database::pdo()->prepare('SELECT 1 FROM users WHERE username = :v LIMIT 1');
        $st->execute(['v' => $username]);
        return (bool) $st->fetchColumn();
    }

    public static function emailExists(string $email): bool
    {
        $st = Database::pdo()->prepare('SELECT 1 FROM users WHERE email = :v LIMIT 1');
        $st->execute(['v' => $email]);
        return (bool) $st->fetchColumn();
    }

    public static function create(string $username, string $email, string $password): int
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)'
        );
        $st->execute([
            'u' => $username,
            'e' => $email,
            'p' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int) $pdo->lastInsertId();
    }
}
