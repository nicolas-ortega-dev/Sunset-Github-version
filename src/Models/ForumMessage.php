<?php declare(strict_types=1);
namespace Sunset\Models;
use PDO; use Sunset\Core\Database;

final class ForumMessage {
    private const COLS='m.id,m.forum_id,m.user_id,m.body,m.created_at,u.username,u.avatar_path';

    public static function recent(int $forumId,int $limit=50,int $before=PHP_INT_MAX): array {
        $st=Database::pdo()->prepare('SELECT '.self::COLS.' FROM forum_messages m JOIN users u ON u.id=m.user_id WHERE m.forum_id=:f AND m.id<:b ORDER BY m.id DESC LIMIT :lim');
        $st->bindValue(':f',$forumId,PDO::PARAM_INT); $st->bindValue(':b',$before,PDO::PARAM_INT); $st->bindValue(':lim',$limit,PDO::PARAM_INT);
        $st->execute(); return array_reverse($st->fetchAll());
    }

    public static function since(int $forumId,int $afterId): array {
        $st=Database::pdo()->prepare('SELECT '.self::COLS.' FROM forum_messages m JOIN users u ON u.id=m.user_id WHERE m.forum_id=:f AND m.id>:a ORDER BY m.id ASC LIMIT 50');
        $st->execute(['f'=>$forumId,'a'=>$afterId]); return $st->fetchAll();
    }

    public static function add(int $forumId,int $userId,string $body): array {
        $pdo=Database::pdo();
        $pdo->prepare('INSERT INTO forum_messages(forum_id,user_id,body) VALUES(:f,:u,:b)')->execute(['f'=>$forumId,'u'=>$userId,'b'=>$body]);
        $id=(int)$pdo->lastInsertId();
        $st=$pdo->prepare('SELECT '.self::COLS.' FROM forum_messages m JOIN users u ON u.id=m.user_id WHERE m.id=:id');
        $st->execute(['id'=>$id]); return $st->fetch();
    }

    public static function delete(int $id,int $forumId): void {
        Database::pdo()->prepare('DELETE FROM forum_messages WHERE id=:id AND forum_id=:f')->execute(['id'=>$id,'f'=>$forumId]);
    }
}
