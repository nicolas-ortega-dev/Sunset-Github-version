<?php declare(strict_types=1);
namespace Sunset\Models;
use Sunset\Core\Database;

final class Notification {
    public static function create(int $userId, int $actorId, string $type, ?int $postId = null): void {
        if ($userId === $actorId || !in_array($type, ['follow','like','comment'], true)) return;
        $st = Database::pdo()->prepare('INSERT INTO notifications (user_id,actor_id,type,post_id) VALUES (:u,:a,:t,:p)');
        $st->execute(['u'=>$userId,'a'=>$actorId,'t'=>$type,'p'=>$postId]);
    }

    public static function removeFollow(int $userId, int $actorId): void {
        $st = Database::pdo()->prepare("DELETE FROM notifications WHERE user_id=:u AND actor_id=:a AND type='follow'");
        $st->execute(['u'=>$userId,'a'=>$actorId]);
    }

    public static function removeLike(int $userId, int $actorId, int $postId): void {
        $st = Database::pdo()->prepare("DELETE FROM notifications WHERE user_id=:u AND actor_id=:a AND type='like' AND post_id=:p");
        $st->execute(['u'=>$userId,'a'=>$actorId,'p'=>$postId]);
    }

    public static function allForUser(int $userId, int $limit = 30): array {
        $st = Database::pdo()->prepare(
            'SELECT n.*, u.username AS actor_username, u.avatar_path AS actor_avatar
             FROM notifications n
             JOIN users u ON u.id = n.actor_id
             WHERE n.user_id = :u
             ORDER BY n.id DESC
             LIMIT :lim'
        );
        $st->bindValue(':u', $userId, \PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function markAllRead(int $userId): void {
        Database::pdo()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=:u')->execute(['u'=>$userId]);
    }

    public static function unreadCount(int $userId): int {
        $st = Database::pdo()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=:u AND is_read=0');
        $st->execute(['u'=>$userId]);
        return (int)$st->fetchColumn();
    }
}
