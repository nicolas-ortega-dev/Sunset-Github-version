<?php
declare(strict_types=1);

namespace Sunset\Controllers;

use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Models\Book;
use Sunset\Models\Post;
use Sunset\Models\UserBook;
use Sunset\Services\BuyLinks;
use Sunset\Services\CatalogService;

final class BookController
{
    public function show(): void
    {
        Auth::requireLogin();
        $id   = (int)($_GET['id'] ?? 0);
        $book = $id > 0 ? (new CatalogService())->ensureDetails($id) : null;
        if ($book === null) {
            http_response_code(404);
            View::render('book/not_found', ['title' => 'Obra no encontrada']);
            return;
        }
        View::render('book/show', [
            'title'      => $book['title'],
            'book'       => $book,
            'shelf'      => UserBook::statuses((int)Auth::id(), $id),
            'posts'      => Post::reviewsForBook((int)Auth::id(), $id),
            'returnTo'   => 'book:' . $id,
            'links'      => [
                'buscalibre'  => BuyLinks::buscalibre($book),
                'openlibrary' => BuyLinks::openLibrary($book),
            ],
            'demoOffers' => BuyLinks::demoOffers($book),
            'scripts'    => ['js/posts.js'],
        ]);
    }

    public function shelf(): void
    {
        Auth::requireLogin();
        Csrf::verify();
        $bookId = (int)input('book_id');
        $status = input('status');
        if ($bookId > 0 && Book::findById($bookId) !== null && in_array($status, UserBook::STATUSES, true)) {
            UserBook::toggle((int)Auth::id(), $bookId, $status);
        }
        redirect('book', ['id' => $bookId]);
    }
}
