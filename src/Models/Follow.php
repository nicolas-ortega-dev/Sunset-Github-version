<?php declare(strict_types=1);
namespace Sunset\Models;
use Sunset\Core\Database;

final class Follow {
    public static function isFollowing(int $followerId, int $followedId): bool {
        $st = Database::pdo()->prepare('SELECT 1 FROM follows WHERE follower_id=:a AND followed_id=:b');
        $st->execute(['a'=>$followerId,'b'=>$followedId]);
        return (bool)$st->fetchColumn();
    }

    public static function toggle(int $followerId, int $followedId): bool {
        if ($followerId === $followedId) return false;
        $pdo = Database::pdo();
        $del = $pdo->prepare('DELETE FROM follows WHERE follower_id=:a AND followed_id=:b');
        $del->execute(['a'=>$followerId,'b'=>$followedId]);
        if ($del->rowCount() > 0) {
            Notification::removeFollow($followedId, $followerId);
            return false;
        }
        $pdo->prepare('INSERT IGNORE INTO follows(follower_id,followed_id) VALUES(:a,:b)')->execute(['a'=>$followerId,'b'=>$followedId]);
        Notification::create($followedId, $followerId, 'follow');
        return true;
    }

    public static function counts(int $userId): array {
        $pdo = Database::pdo();
        $fl = (int)$pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id=:u')
            ->execute(['u'=>$userId]) ? (function() use($pdo,$userId){
                $s=$pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id=:u');
                $s->execute(['u'=>$userId]); return (int)$s->fetchColumn();
            })() : 0;
        $fd = (function() use($pdo,$userId){
            $s=$pdo->prepare('SELECT COUNT(*) FROM follows WHERE followed_id=:u');
            $s->execute(['u'=>$userId]); return (int)$s->fetchColumn();
        })();
        $flCount = (function() use($pdo,$userId){
            $s=$pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id=:u');
            $s->execute(['u'=>$userId]); return (int)$s->fetchColumn();
        })();
        return ['following'=>$flCount,'followers'=>$fd];
    }
}
