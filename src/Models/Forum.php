<?php declare(strict_types=1);
namespace Sunset\Models;
use PDO; use Sunset\Core\Database;

final class Forum {
    private const COLS = 'f.id, f.name, f.description, f.book_id, f.creator_id, f.created_at,
        b.title AS book_title, b.cover_id AS book_cover,
        (SELECT COUNT(*) FROM forum_members fm WHERE fm.forum_id = f.id) AS member_count';

    public static function findById(int $id): ?array {
        $st = Database::pdo()->prepare('SELECT '.self::COLS.' FROM forums f LEFT JOIN books b ON b.id=f.book_id WHERE f.id=:id');
        $st->execute(['id'=>$id]); return $st->fetch() ?: null;
    }

    public static function search(string $q, int $limit = 20): array {
        $clean = preg_replace('/[^\p{L}\p{N} ]/u', ' ', $q) ?? '';
        $words = array_filter(explode(' ', trim($clean)));
        if (!$words) return [];
        $bool = '+' . implode('* +', $words) . '*';
        $st = Database::pdo()->prepare('SELECT '.self::COLS.' FROM forums f LEFT JOIN books b ON b.id=f.book_id WHERE MATCH(f.name) AGAINST(:q IN BOOLEAN MODE) LIMIT :lim');
        $st->bindValue(':q', $bool); $st->bindValue(':lim', $limit, PDO::PARAM_INT); $st->execute();
        $r = $st->fetchAll();
        if (!$r) {
            $st = Database::pdo()->prepare('SELECT '.self::COLS.' FROM forums f LEFT JOIN books b ON b.id=f.book_id WHERE f.name LIKE :l LIMIT :lim');
            $st->bindValue(':l', '%'.$q.'%'); $st->bindValue(':lim', $limit, PDO::PARAM_INT); $st->execute();
            $r = $st->fetchAll();
        }
        return $r;
    }

    public static function all(int $limit = 40, int $offset = 0): array {
        $st = Database::pdo()->prepare('SELECT '.self::COLS.' FROM forums f LEFT JOIN books b ON b.id=f.book_id ORDER BY f.id DESC LIMIT :lim OFFSET :off');
        $st->bindValue(':lim', $limit, PDO::PARAM_INT); $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute(); return $st->fetchAll();
    }

    public static function forBook(int $bookId, int $limit = 5): array {
        $st = Database::pdo()->prepare('SELECT '.self::COLS.' FROM forums f LEFT JOIN books b ON b.id=f.book_id WHERE f.book_id=:bk ORDER BY member_count DESC LIMIT :lim');
        $st->bindValue(':bk', $bookId, PDO::PARAM_INT); $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute(); return $st->fetchAll();
    }

    public static function myForums(int $userId, int $limit = 40): array {
        $st = Database::pdo()->prepare('SELECT '.self::COLS.' FROM forums f LEFT JOIN books b ON b.id=f.book_id INNER JOIN forum_members fm ON fm.forum_id=f.id AND fm.user_id=:u ORDER BY f.id DESC LIMIT :lim');
        $st->bindValue(':u', $userId, PDO::PARAM_INT); $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute(); return $st->fetchAll();
    }

    public static function create(int $creatorId, string $name, string $desc, ?int $bookId): int {
        $pdo = Database::pdo(); $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO forums (name,description,book_id,creator_id) VALUES(:n,:d,:b,:c)')
            ->execute(['n'=>$name,'d'=>$desc?:null,'b'=>$bookId,'c'=>$creatorId]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO forum_members(forum_id,user_id,role) VALUES(:f,:u,"admin")')->execute(['f'=>$id,'u'=>$creatorId]);
        $pdo->commit(); return $id;
    }

    public static function update(int $id, string $name, string $desc): void {
        Database::pdo()->prepare('UPDATE forums SET name=:n, description=:d WHERE id=:id')->execute(['n'=>$name,'d'=>$desc?:null,'id'=>$id]);
    }

    public static function delete(int $id): void {
        Database::pdo()->prepare('DELETE FROM forums WHERE id=:id')->execute(['id'=>$id]);
    }

    public static function memberRole(int $forumId, int $userId): ?string {
        $st = Database::pdo()->prepare('SELECT role FROM forum_members WHERE forum_id=:f AND user_id=:u');
        $st->execute(['f'=>$forumId,'u'=>$userId]); $r = $st->fetchColumn(); return $r ?: null;
    }

    public static function join(int $forumId, int $userId): void {
        Database::pdo()->prepare('INSERT IGNORE INTO forum_members(forum_id,user_id) VALUES(:f,:u)')->execute(['f'=>$forumId,'u'=>$userId]);
    }

    public static function leave(int $forumId, int $userId): void {
        Database::pdo()->prepare("DELETE FROM forum_members WHERE forum_id=:f AND user_id=:u AND role<>'admin'")->execute(['f'=>$forumId,'u'=>$userId]);
    }

    public static function kick(int $forumId, int $userId): void {
        Database::pdo()->prepare("DELETE FROM forum_members WHERE forum_id=:f AND user_id=:u AND role<>'admin'")->execute(['f'=>$forumId,'u'=>$userId]);
    }

    public static function members(int $forumId, int $limit = 50): array {
        $st = Database::pdo()->prepare('SELECT u.id, u.username, u.avatar_path, fm.role FROM forum_members fm JOIN users u ON u.id=fm.user_id WHERE fm.forum_id=:f ORDER BY fm.role DESC, fm.joined_at ASC LIMIT :lim');
        $st->bindValue(':f', $forumId, PDO::PARAM_INT); $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute(); return $st->fetchAll();
    }
}
