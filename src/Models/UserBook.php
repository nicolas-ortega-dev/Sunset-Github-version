<?php
declare(strict_types=1);

namespace Sunset\Models;

use Sunset\Core\Database;

/** Listas personales: "Leídos" (read) y "Deseados" (wishlist). */
final class UserBook
{
    public const STATUSES = ['read', 'wishlist'];

    public static function statuses(int $userId, int $bookId): array
    {
        $st = Database::pdo()->prepare('SELECT status FROM user_books WHERE user_id = :u AND book_id = :b');
        $st->execute(['u' => $userId, 'b' => $bookId]);
        $found = $st->fetchAll(\PDO::FETCH_COLUMN);
        return ['read' => in_array('read', $found, true), 'wishlist' => in_array('wishlist', $found, true)];
    }

    /**
     * Activa o desactiva una lista. Son excluyentes: marcar "Leído" quita "Deseado" y viceversa.
     */
    public static function toggle(int $userId, int $bookId, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        $other = $status === 'read' ? 'wishlist' : 'read';
        $pdo   = Database::pdo();

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM user_books WHERE user_id = :u AND book_id = :b AND status = :s');
            $del->execute(['u' => $userId, 'b' => $bookId, 's' => $status]);

            if ($del->rowCount() === 0) {       // no estaba marcado: se marca (y se quita la otra lista)
                $del->execute(['u' => $userId, 'b' => $bookId, 's' => $other]);
                $ins = $pdo->prepare('INSERT IGNORE INTO user_books (user_id, book_id, status) VALUES (:u, :b, :s)');
                $ins->execute(['u' => $userId, 'b' => $bookId, 's' => $status]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
