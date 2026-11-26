<?php
declare(strict_types=1);

namespace Sunset\Models;

use Sunset\Core\Database;

final class Comment
{
    private const SELECT = 'SELECT c.id, c.post_id, c.user_id, c.body, c.created_at, u.username, u.avatar_path
                            FROM comments c JOIN users u ON u.id = c.user_id ';

    public static function forPost(int $postId, int $limit = 100): array
    {
        $st = Database::pdo()->prepare(self::SELECT . 'WHERE c.post_id = :p ORDER BY c.id ASC LIMIT :lim');
        $st->bindValue(':p', $postId, \PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $st = Database::pdo()->prepare(self::SELECT . 'WHERE c.id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public static function count(int $postId): int
    {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM comments WHERE post_id = :p');
        $st->execute(['p' => $postId]);
        return (int) $st->fetchColumn();
    }

    /** Agrega un comentario y avisa al autor de la publicación. null si la publicación no existe. */
    public static function add(int $postId, int $userId, string $body): ?array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT user_id FROM posts WHERE id = :id');
        $st->execute(['id' => $postId]);
        $owner = $st->fetchColumn();
        if ($owner === false) {
            return null;
        }

        $ins = $pdo->prepare('INSERT INTO comments (post_id, user_id, body) VALUES (:p, :u, :b)');
        $ins->execute(['p' => $postId, 'u' => $userId, 'b' => $body]);
        $id = (int) $pdo->lastInsertId();

        Notification::create((int) $owner, $userId, 'comment', $postId);
        return self::find($id);
    }
}
