<?php
declare(strict_types=1);

namespace Sunset\Models;

use PDO;
use Sunset\Core\Database;

final class Book
{
    /** Mínimo de reseñas que "pesa" la nota global en el promedio ponderado. */
    public const MIN_VOTES   = 5;
    /** Nota promedio global que se asume para libros con pocas reseñas. */
    public const GLOBAL_MEAN = 3.5;

    private const COLS = 'id, ol_work_key, title, authors, description, language, category, subjects, isbn,
        cover_id, first_publish_year, edition_count, details_fetched, rating_count,
        IF(rating_count = 0, NULL, rating_sum / rating_count) AS rating_avg';

    /** Nota ponderada: score = (v/(v+m))*R + (m/(v+m))*C (constantes numéricas, no hay entrada del usuario). */
    private static function scoreSql(): string
    {
        $m = (string) (float) self::MIN_VOTES;
        $c = (string) (float) self::GLOBAL_MEAN;
        return "IF(rating_count = 0, $c, (rating_count / (rating_count + $m)) * (rating_sum / rating_count)"
             . " + ($m / (rating_count + $m)) * $c)";
    }

    // ------------------------------------------------------------ lectura

    public static function findById(int $id): ?array
    {
        $st = Database::pdo()->prepare('SELECT ' . self::COLS . ' FROM books WHERE id = :id');
        $st->execute(['id' => $id]);
        return $st->fetch() ?: null;
    }

    public static function count(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM books')->fetchColumn();
    }

    /**
     * Catálogo ordenado por nota ponderada. Empates: español primero y luego más ediciones.
     * @param string|null $category libro | manga | comic | null (todas)
     * @param string|null $language código de 3 letras (ej: spa) | null (todos)
     */
    public static function catalog(?string $category = null, ?string $language = null, int $limit = 24, int $offset = 0): array
    {
        $conds = [];
        $binds = [];
        if ($category !== null && in_array($category, ['libro', 'manga', 'comic'], true)) {
            $conds[] = 'category = :cat';
            $binds[':cat'] = $category;
        }
        if ($language !== null && preg_match('/^[a-z]{3}$/', $language)) {
            $conds[] = 'language = :lang';
            $binds[':lang'] = $language;
        }
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $sql = 'SELECT ' . self::COLS . ', ' . self::scoreSql() . ' AS score FROM books ' . $where
             . " ORDER BY score DESC, (language = 'spa') DESC, edition_count DESC, id ASC LIMIT :lim OFFSET :off";

        $st = Database::pdo()->prepare($sql);
        foreach ($binds as $name => $value) {
            $st->bindValue($name, $value);
        }
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    /** Búsqueda local por título/autor: FULLTEXT por prefijo y, si no hay resultados, LIKE. */
    public static function search(string $query, int $limit = 24): array
    {
        $terms = self::terms($query);
        if (!$terms) {
            return [];
        }
        $pdo  = Database::pdo();
        $rows = [];

        // El índice FULLTEXT ignora palabras de menos de 3 letras.
        $long = array_values(array_filter($terms, static fn (string $t): bool => mb_strlen($t) >= 3));
        if ($long) {
            $boolean = implode(' ', array_map(static fn (string $t): string => '+' . $t . '*', $long));
            $st = $pdo->prepare(
                'SELECT ' . self::COLS . ', ' . self::scoreSql() . ' AS score,
                        MATCH(title, authors) AGAINST (:q1 IN BOOLEAN MODE) AS rel
                 FROM books
                 WHERE MATCH(title, authors) AGAINST (:q2 IN BOOLEAN MODE)
                 ORDER BY rel DESC, score DESC, edition_count DESC
                 LIMIT :lim'
            );
            $st->bindValue(':q1', $boolean);
            $st->bindValue(':q2', $boolean);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll();
        }

        if (!$rows) {
            $like = '%' . implode(' ', $terms) . '%';
            $st = $pdo->prepare(
                'SELECT ' . self::COLS . ', ' . self::scoreSql() . ' AS score
                 FROM books
                 WHERE title LIKE :l1 OR authors LIKE :l2
                 ORDER BY score DESC, edition_count DESC
                 LIMIT :lim'
            );
            $st->bindValue(':l1', $like);
            $st->bindValue(':l2', $like);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll();
        }
        return $rows;
    }

    // ---------------------------------------------------------- escritura

    /**
     * Guarda un resultado de search.json de Open Library.
     * @return bool true si se insertó un libro nuevo; false si ya existía o no cumple los requisitos.
     */
    public static function upsertFromDoc(array $doc, bool $requireCover = true): bool
    {
        if (!preg_match('#^/works/(OL\d+W)$#', (string) ($doc['key'] ?? ''), $m)) {
            return false;
        }
        $workKey = $m[1];

        // Edición que Open Library eligió para la búsqueda (con language:spa, una en español).
        // Si existe, se usa su título y portada; si no, los de la obra.
        $edition = is_array($doc['editions']['docs'][0] ?? null) ? $doc['editions']['docs'][0] : [];

        $editionTitle = trim((string) ($edition['title'] ?? ''));
        $title = self::clip($editionTitle !== '' ? $editionTitle : trim((string) ($doc['title'] ?? '')), 300);

        $coverId = (int) ($edition['cover_i'] ?? 0);
        if ($coverId <= 0) {
            $coverId = (int) ($doc['cover_i'] ?? 0);   // portada de otra edición de la misma obra
        }
        if ($title === '' || ($requireCover && $coverId <= 0)) {
            return false;
        }

        $editionLangs = self::strings($edition['language'] ?? []);
        $language = self::language($editionLangs ?: self::strings($doc['language'] ?? []));
        $isbn = self::isbn([...self::strings($edition['isbn'] ?? []), ...self::strings($doc['isbn'] ?? [])]);

        $authors = self::strings($doc['author_name'] ?? []);
        $authors = $authors ? self::clip(implode(', ', array_slice($authors, 0, 3)), 300) : 'Autor desconocido';

        $subjects = array_map(static fn (string $s): string => self::clip($s, 60),
            array_slice(self::strings($doc['subject'] ?? []), 0, 15));
        $subjectsText = self::clip(implode(' | ', $subjects), 500);

        $year = (int) ($doc['first_publish_year'] ?? 0);
        $st = Database::pdo()->prepare(
            'INSERT INTO books
               (ol_work_key, title, authors, language, category, subjects, isbn, cover_id, first_publish_year, edition_count)
             VALUES (:k, :t, :a, :l, :c, :s, :i, :cv, :y, :e)
             ON DUPLICATE KEY UPDATE id = id'
        );
        $st->execute([
            'k'  => $workKey,
            't'  => $title,
            'a'  => $authors,
            'l'  => $language,
            'c'  => self::category($subjectsText),
            's'  => $subjectsText !== '' ? $subjectsText : null,
            'i'  => $isbn,
            'cv' => $coverId > 0 ? $coverId : null,
            'y'  => ($year >= 1000 && $year <= 2100) ? $year : null,
            'e'  => max(0, (int) ($doc['edition_count'] ?? 0)),
        ]);
        return $st->rowCount() === 1;
    }

    /** Guarda la sinopsis y marca el libro como "detalles ya consultados". */
    public static function saveDetails(int $id, ?string $description): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE books SET description = COALESCE(:d, description), details_fetched = 1 WHERE id = :id'
        );
        $st->execute(['d' => $description, 'id' => $id]);
    }

    /** Suma una reseña al promedio del libro. */
    public static function addRating(int $bookId, int $rating): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE books SET rating_sum = rating_sum + :r, rating_count = rating_count + 1 WHERE id = :id'
        );
        $st->execute(['r' => $rating, 'id' => $bookId]);
    }

    /** Quita una reseña del promedio (al borrar la publicación). */
    public static function removeRating(int $bookId, int $rating): void
    {
        $st = Database::pdo()->prepare(
            'UPDATE books
             SET rating_sum = IF(rating_sum >= :r1, rating_sum - :r2, 0),
                 rating_count = IF(rating_count > 0, rating_count - 1, 0)
             WHERE id = :id'
        );
        $st->execute(['r1' => $rating, 'r2' => $rating, 'id' => $bookId]);
    }

    // ----------------------------------------------------------- ayudas

    /** Palabras de la búsqueda, sin símbolos (así no se cuelan operadores de FULLTEXT). */
    private static function terms(string $query): array
    {
        $clean = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $query) ?? '';
        $terms = preg_split('/\s+/u', trim($clean), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = array_filter($terms, static fn (string $t): bool => mb_strlen($t) >= 2);
        return array_slice(array_values($terms), 0, 6);
    }

    private static function strings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        return array_values(array_filter($value, static fn ($v): bool => is_string($v) && trim($v) !== ''));
    }

    private static function clip(string $text, int $max): string
    {
        return mb_substr($text, 0, $max);
    }

    private static function category(string $subjects): string
    {
        if (preg_match('/manga|manhwa|manhua/iu', $subjects)) {
            return 'manga';
        }
        if (preg_match('/comic|cómic|graphic novel|historieta/iu', $subjects)) {
            return 'comic';
        }
        return 'libro';
    }

    private static function language(array $langs): string
    {
        if (in_array('spa', $langs, true)) {
            return 'spa';
        }
        if (in_array('eng', $langs, true)) {
            return 'eng';
        }
        return isset($langs[0]) ? substr($langs[0], 0, 3) : 'und';
    }

    /** Prefiere un ISBN-13; si no hay, usa el primero que exista. */
    private static function isbn(array $isbns): ?string
    {
        $first = null;
        foreach ($isbns as $raw) {
            $digits = preg_replace('/[^0-9Xx]/', '', $raw) ?? '';
            if ($digits === '') {
                continue;
            }
            $first ??= $digits;
            if (strlen($digits) === 13 && (str_starts_with($digits, '978') || str_starts_with($digits, '979'))) {
                return $digits;
            }
        }
        return $first !== null ? substr($first, 0, 20) : null;
    }
}
