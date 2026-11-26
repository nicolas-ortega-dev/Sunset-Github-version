<?php
declare(strict_types=1);

namespace Sunset\Models;

use PDO;
use PDOException;
use Sunset\Core\Database;
use Sunset\Core\DuplicateReviewException;

final class Post
{
    public const MAX_BODY  = 2000;
    public const FEED_SIZE = 10;

    /** Publicación + autor + obra vinculada + contadores + si yo di "me gusta" (:me). */
    private const SELECT = 'SELECT p.id, p.user_id, p.body, p.image_path, p.rating, p.created_at,
            u.username, u.avatar_path,
            b.id AS book_id, b.title AS book_title, b.authors AS book_authors, b.cover_id AS book_cover,
            IF(b.rating_count = 0, NULL, b.rating_sum / b.rating_count) AS book_avg,
            b.rating_count AS book_votes,
            (SELECT COUNT(*) FROM post_likes l WHERE l.post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
            EXISTS(SELECT 1 FROM post_likes l2 WHERE l2.post_id = p.id AND l2.user_id = :me) AS liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN books b ON b.id = p.book_id ';

    /**
     * Feed paginado hacia atrás (id menor a $before).
     * @param string $mode all = toda la comunidad | following = tú y a quienes sigues
     */
    public static function feed(int $me, string $mode = 'all', int $limit = self::FEED_SIZE, int $before = 4294967295): array
    {
        $where = 'WHERE p.id < :before';
        if ($mode === 'following') {
            $where .= ' AND (p.user_id = :me1 OR p.user_id IN (SELECT followed_id FROM follows WHERE follower_id = :me2))';
        }
        $st = Database::pdo()->prepare(self::SELECT . $where . ' ORDER BY p.id DESC LIMIT :lim');
        $st->bindValue(':me', $me, PDO::PARAM_INT);
        $st->bindValue(':before', $before, PDO::PARAM_INT);
        if ($mode === 'following') {
            $st->bindValue(':me1', $me, PDO::PARAM_INT);
            $st->bindValue(':me2', $me, PDO::PARAM_INT);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Reseñas (publicaciones con puntuación) de una obra. */
    public static function reviewsForBook(int $me, int $bookId, int $limit = 30): array
    {
        $st = Database::pdo()->prepare(
            self::SELECT . 'WHERE p.book_id = :bk AND p.rating IS NOT NULL ORDER BY p.id DESC LIMIT :lim'
        );
        $st->bindValue(':me', $me, PDO::PARAM_INT);
        $st->bindValue(':bk', $bookId, PDO::PARAM_INT);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /**
     * Crea una publicación. Si trae puntuación y obra, es una reseña y entra al promedio del libro.
     * @throws DuplicateReviewException si el usuario ya reseñó esa obra
     */
    public static function create(int $userId, string $body, ?int $bookId, ?int $rating, ?string $imagePath): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare(
                'INSERT INTO posts (user_id, book_id, rating, body, image_path, review_book_id)
                 VALUES (:u, :b, :r, :body, :img, :rb)'
            );
            $st->execute([
                'u'    => $userId,
                'b'    => $bookId,
                'r'    => $rating,
                'body' => $body,
                'img'  => $imagePath,
                'rb'   => $rating !== null ? $bookId : null,
            ]);
            $id = (int) $pdo->lastInsertId();
            if ($rating !== null && $bookId !== null) {
                Book::addRating($bookId, $rating);
            }
            $pdo->commit();
            return $id;
        } catch (PDOException $e) {
            $pdo->rollBack();
            if (($e->errorInfo[1] ?? 0) === 1062 && $rating !== null) {
                throw new DuplicateReviewException();
            }
            throw $e;
        }
    }

    /** Borra una publicación propia. Devuelve sus datos (para borrar la imagen) o null si no corresponde. */
    public static function delete(int $postId, int $userId): ?array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT id, book_id, rating, image_path FROM posts WHERE id = :id AND user_id = :u');
        $st->execute(['id' => $postId, 'u' => $userId]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM posts WHERE id = :id');
            $del->execute(['id' => $postId]);
            if ($row['rating'] !== null && $row['book_id'] !== null) {
                Book::removeRating((int) $row['book_id'], (int) $row['rating']);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $row;
    }

    /** Da o quita "me gusta". Devuelve ['liked' => bool, 'count' => int] o null si no existe la publicación. */
    public static function toggleLike(int $userId, int $postId): ?array
    {
        $pdo = Database::pdo();
        $st = $pdo->prepare('SELECT user_id FROM posts WHERE id = :id');
        $st->execute(['id' => $postId]);
        $owner = $st->fetchColumn();
        if ($owner === false) {
            return null;
        }
        $owner = (int) $owner;

        $del = $pdo->prepare('DELETE FROM post_likes WHERE user_id = :u AND post_id = :p');
        $del->execute(['u' => $userId, 'p' => $postId]);

        if ($del->rowCount() > 0) {
            $liked = false;
            Notification::removeLike($owner, $userId, $postId);
        } else {
            $ins = $pdo->prepare('INSERT IGNORE INTO post_likes (user_id, post_id) VALUES (:u, :p)');
            $ins->execute(['u' => $userId, 'p' => $postId]);
            $liked = true;
            Notification::create($owner, $userId, 'like', $postId);
        }

        $cnt = $pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :p');
        $cnt->execute(['p' => $postId]);
        return ['liked' => $liked, 'count' => (int) $cnt->fetchColumn()];
    }
}
