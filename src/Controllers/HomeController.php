<?php
declare(strict_types=1);

namespace Sunset\Controllers;

use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Models\Book;
use Sunset\Models\Post;

final class HomeController
{
    private const PAGE_SIZE = 8;

    public function index(): void
    {
        Auth::requireLogin();
        [$category, $lang] = $this->filters();

        $feedMode = (($_GET['feed'] ?? '') === 'following') ? 'following' : 'all';
        $postRows = Post::feed((int) Auth::id(), $feedMode, Post::FEED_SIZE + 1);
        $posts    = array_slice($postRows, 0, Post::FEED_SIZE);

        $rows = Book::catalog($category, $lang, self::PAGE_SIZE + 1, 0);

        View::render('home/index', [
            'title'      => 'Inicio',
            'books'      => array_slice($rows, 0, self::PAGE_SIZE),
            'hasMore'    => count($rows) > self::PAGE_SIZE,
            'category'   => $category,
            'lang'       => $lang,
            'nextOffset' => self::PAGE_SIZE,
            'feedMode'   => $feedMode,
            'posts'      => $posts,
            'postsMore'  => count($postRows) > Post::FEED_SIZE,
            'postsNext'  => $posts ? (int) end($posts)['id'] : 0,
            'scripts'    => ['js/catalog.js', 'js/posts.js'],
        ]);
    }

    /** AJAX: siguiente tanda de libros para el botón "Ver más". */
    public function more(): void
    {
        if (!Auth::check()) {
            json_response(['error' => 'No autorizado'], 401);
        }

        [$category, $lang] = $this->filters();
        $offset = min(10000, max(0, (int) ($_GET['offset'] ?? 0)));

        $rows  = Book::catalog($category, $lang, self::PAGE_SIZE + 1, $offset);
        $books = array_slice($rows, 0, self::PAGE_SIZE);

        $html = '';
        foreach ($books as $book) {
            $html .= View::partial('partials/book_card', ['book' => $book]);
        }
        json_response([
            'html'    => $html,
            'hasMore' => count($rows) > self::PAGE_SIZE,
            'next'    => $offset + count($books),
        ]);
    }

    /** Filtros validados desde la URL: [categoría|null, idioma|null]. */
    private function filters(): array
    {
        $category = $_GET['category'] ?? null;
        $category = (is_string($category) && in_array($category, ['libro', 'manga', 'comic'], true)) ? $category : null;
        $lang     = (($_GET['lang'] ?? null) === 'spa') ? 'spa' : null;
        return [$category, $lang];
    }
}
