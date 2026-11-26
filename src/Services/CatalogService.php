<?php
declare(strict_types=1);

namespace Sunset\Services;

use RuntimeException;
use Sunset\Models\Book;
use Sunset\Models\SearchCache;

/** Orquesta la base local (MySQL) con Open Library: búsqueda híbrida y detalles bajo demanda. */
final class CatalogService
{
    /** Si la base local devuelve menos que esto, se complementa con Open Library. */
    private const MIN_LOCAL_RESULTS = 8;
    private const ONDEMAND_LIMIT    = 40;

    private OpenLibraryService $ol;

    public function __construct(?OpenLibraryService $ol = null)
    {
        // En la web no se hace esperar al usuario: 8 s y un solo intento.
        $this->ol = $ol ?? new OpenLibraryService(timeout: 8, attempts: 1);
    }

    /** Busca primero en MySQL y, si hay pocos resultados, trae más desde Open Library. */
    public function search(string $query, int $limit = 24): array
    {
        $normalized = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $query) ?? ''));
        if (mb_strlen($normalized) < 2) {
            return [];
        }

        $results = Book::search($normalized, $limit);
        if (count($results) >= self::MIN_LOCAL_RESULTS || SearchCache::isFresh($normalized)) {
            return $results;
        }

        try {
            $docs = $this->ol->search($normalized, 1, self::ONDEMAND_LIMIT);
        } catch (RuntimeException) {
            return $results;        // sin internet: se muestra lo que haya en la base
        }

        foreach ($docs as $doc) {
            if (is_array($doc)) {
                Book::upsertFromDoc($doc);
            }
        }
        SearchCache::mark($normalized, count($docs));

        return Book::search($normalized, $limit);
    }

    /** Devuelve el libro con su sinopsis, pidiéndola a Open Library la primera vez. */
    public function ensureDetails(int $bookId): ?array
    {
        $book = Book::findById($bookId);
        if ($book === null || (int) $book['details_fetched'] === 1) {
            return $book;
        }

        try {
            $work = $this->ol->work($book['ol_work_key']);
        } catch (RuntimeException) {
            return $book;           // no se marca: se reintentará la próxima vez
        }

        Book::saveDetails($bookId, $work !== null ? self::extractDescription($work) : null);
        return Book::findById($bookId);
    }

    private static function extractDescription(array $work): ?string
    {
        $d = $work['description'] ?? null;
        if (is_array($d)) {
            $d = $d['value'] ?? null;
        }
        if (!is_string($d)) {
            return null;
        }
        $d = mb_substr(trim($d), 0, 8000);
        return $d !== '' ? $d : null;
    }
}
