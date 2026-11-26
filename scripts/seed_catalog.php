<?php
declare(strict_types=1);

/*
 * Carga inicial del catalogo de Sunset desde la API de Open Library.
 *
 * Uso (desde el Shell de XAMPP o cmd):
 *   C:\xampp\php\php.exe C:\xampp\htdocs\sunset\scripts\seed_catalog.php --test
 *   C:\xampp\php\php.exe C:\xampp\htdocs\sunset\scripts\seed_catalog.php
 *   C:\xampp\php\php.exe C:\xampp\htdocs\sunset\scripts\seed_catalog.php --target=10000 --pages=5
 *
 * Se puede cortar con Ctrl+C y volver a ejecutar: no duplica libros.
 */

if (PHP_SAPI !== 'cli') {
    exit("Este script solo se ejecuta desde la consola.\n");
}

require dirname(__DIR__) . '/src/bootstrap.php';

use Sunset\Models\Book;
use Sunset\Services\OpenLibraryService;

set_time_limit(0);

$opts   = getopt('', ['target::', 'pages::', 'test']);
$target = max(100, (int) ($opts['target'] ?? 10000));
$pages  = max(1, min(20, (int) ($opts['pages'] ?? 5)));

/** Consultas: [etiqueta, consulta de Open Library]. Manga y comics van primero. */
function seedQueries(): array
{
    $queries = [
        ['manga (es)',          'subject:"manga" AND language:spa'],
        ['manga',               'subject:"manga"'],
        ['comics (es)',         'subject:"comics & graphic novels" AND language:spa'],
        ['graphic novels (es)', 'subject:"graphic novels" AND language:spa'],
        ['comic books (es)',    'subject:"comic books, strips, etc." AND language:spa'],
        ['comics',              'subject:"comics & graphic novels"'],
        ['graphic novels',      'subject:"graphic novels"'],
    ];

    $genres = [
        'fantasy', 'science fiction', 'romance', 'mystery', 'thriller', 'horror',
        'historical fiction', 'classic literature', 'poetry', 'drama', 'adventure',
        'young adult', 'juvenile fiction', 'biography', 'history', 'philosophy',
        'psychology', 'self-help', 'essays', 'short stories', 'crime', 'humor',
        'magic realism', 'dystopia', 'war', 'love stories', 'novela', 'cuentos',
        'politics', 'science',
    ];
    foreach ($genres as $g) {
        $queries[] = ["es: $g", 'subject:"' . $g . '" AND language:spa'];
    }
    return $queries;
}

// ---------------------------------------------------------------- prueba
if (isset($opts['test'])) {
    echo "Probando conexion con Open Library...\n";
    try {
        $docs = (new OpenLibraryService(timeout: 20, attempts: 1))
            ->search('subject:"fantasy" AND language:spa', 1, 3);
    } catch (RuntimeException $e) {
        exit("ERROR: " . $e->getMessage() . "\n");
    }
    echo "OK. Se recibieron " . count($docs) . " resultados de ejemplo:\n";
    foreach ($docs as $d) {
        $ed = is_array($d['editions']['docs'][0] ?? null) ? $d['editions']['docs'][0] : [];
        printf("  - Obra: %s | Edicion: %s [%s] | %s | portada: %s\n",
            $d['title'] ?? '?',
            $ed['title'] ?? '(sin datos de edicion)',
            implode(',', $ed['language'] ?? []),
            implode(', ', array_slice($d['author_name'] ?? [], 0, 2)) ?: 'sin autor',
            isset($ed['cover_i']) ? 'de la edicion' : (isset($d['cover_i']) ? 'de la obra' : 'no'));
    }
    echo OpenLibraryService::usingEditionData()
        ? "Datos de edicion: ACTIVADOS (los titulos deberian verse en espanol).\n"
        : "Datos de edicion: NO disponibles (se usaran titulos de la obra).\n";
    echo "Libros actuales en la base de datos: " . Book::count() . "\n";
    exit(0);
}

// --------------------------------------------------------------- carga
$ol = new OpenLibraryService(timeout: 30, attempts: 3);

// 1) Comprobar conexion.
try {
    $ol->search('subject:"fantasy"', 1, 1);
} catch (RuntimeException $e) {
    exit("ERROR de conexion: " . $e->getMessage() . "\nRevisa tu internet y vuelve a intentar.\n");
}

// 2) Comprobar si Open Library acepta ordenar por numero de ediciones (libros mas conocidos primero).
$sort = 'editions';
try {
    $ol->search('subject:"fantasy"', 1, 1, $sort);
} catch (RuntimeException) {
    $sort = null;
    echo "Aviso: no se pudo ordenar por popularidad; se usara el orden por defecto.\n";
}

$queries = seedQueries();
$total   = Book::count();
$count   = count($queries);
echo "Libros en la base al iniciar: $total. Objetivo: $target.\n\n";

foreach ($queries as $i => [$label, $query]) {
    if ($total >= $target) {
        echo "Objetivo alcanzado.\n";
        break;
    }

    $added = 0;
    for ($page = 1; $page <= $pages; $page++) {
        try {
            $docs = $ol->search($query, $page, 100, $sort);
        } catch (RuntimeException $e) {
            echo "  error en pagina $page: " . $e->getMessage() . "\n";
            break;
        }
        if (!$docs) {
            break;
        }
        foreach ($docs as $doc) {
            if (is_array($doc) && Book::upsertFromDoc($doc)) {
                $added++;
                $total++;
            }
        }
        if (count($docs) < 100) {
            break;
        }
    }
    printf("[%d/%d] %-26s +%d nuevos (total %d)\n", $i + 1, $count, $label, $added, $total);
}

echo "\nListo. Libros en la base de datos: " . Book::count() . "\n";
