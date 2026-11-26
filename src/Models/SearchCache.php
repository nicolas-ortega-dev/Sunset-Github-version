<?php
declare(strict_types=1);

namespace Sunset\Models;

use Sunset\Core\Database;

/** Recuerda qué búsquedas ya se consultaron a Open Library, para no repetirlas. */
final class SearchCache
{
    private const TTL_DAYS = 30;

    public static function isFresh(string $normalizedQuery): bool
    {
        $st = Database::pdo()->prepare(
            'SELECT 1 FROM search_cache
             WHERE query_hash = :h AND created_at > (NOW() - INTERVAL ' . self::TTL_DAYS . ' DAY)'
        );
        $st->execute(['h' => sha1($normalizedQuery)]);
        return (bool) $st->fetchColumn();
    }

    public static function mark(string $normalizedQuery, int $resultCount): void
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO search_cache (query_hash, query_text, result_count)
             VALUES (:h, :q, :n)
             ON DUPLICATE KEY UPDATE result_count = VALUES(result_count), created_at = CURRENT_TIMESTAMP'
        );
        $st->execute([
            'h' => sha1($normalizedQuery),
            'q' => mb_substr($normalizedQuery, 0, 200),
            'n' => $resultCount,
        ]);
    }
}
