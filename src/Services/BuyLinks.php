<?php
declare(strict_types=1);

namespace Sunset\Services;

use Sunset\Core\Config;

/** Enlaces de salida del "hub de compra". Sunset no vende: solo redirige. */
final class BuyLinks
{
    /** Búsqueda en Buscalibre por título y autor principal. */
    public static function buscalibre(array $book): string
    {
        $author = trim(explode(',', (string) $book['authors'])[0]);
        if ($author === 'Autor desconocido') {
            $author = '';
        }
        $query    = trim($book['title'] . ' ' . $author);
        $template = (string) Config::get('links.buscalibre_search', 'https://www.buscalibre.cl/libros/search?q={q}');
        return str_replace('{q}', rawurlencode($query), $template);
    }


    /**
     * Genera ofertas simuladas para las librerías demo.
     * El precio es determinista (mismo libro = mismo precio siempre).
     * @return array[] [{name, type, price, url, is_demo}]
     */
    public static function demoOffers(array $book): array
    {
        if (!(bool) Config::get('app.demo_mode', true)) {
            return [];
        }
        $pdo = \Sunset\Core\Database::pdo();
        $st  = $pdo->query("SELECT * FROM bookstores WHERE is_demo = 1 AND is_active = 1");
        $stores = $st->fetchAll();
        $offers = [];
        $base   = (($book['id'] ?? 1) * 1619 + 7) % 100; // seudo-aleatorio reproducible
        foreach ($stores as $i => $store) {
            $price = 8990 + (($base + $i * 2131) % 15) * 500; // rango 8990–16490
            $url   = $store['website'] ?? '#';
            if ($store['search_url']) {
                $q   = rawurlencode(trim($book['title'] . ' ' . explode(',', $book['authors'])[0]));
                $url = str_replace('{q}', $q, $store['search_url']);
            }
            $offers[] = [
                'name'    => $store['name'],
                'type'    => $store['type'],
                'price'   => '$' . number_format($price, 0, ',', '.'),
                'url'     => $url,
                'is_demo' => true,
            ];
        }
        return $offers;
    }

    public static function openLibrary(array $book): string
    {
        return OpenLibraryService::workUrl($book['ol_work_key']);
    }
}
