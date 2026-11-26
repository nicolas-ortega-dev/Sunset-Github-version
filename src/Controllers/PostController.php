<?php
declare(strict_types=1);

namespace Sunset\Controllers;

use RuntimeException;
use Sunset\Core\DuplicateReviewException;
use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Helpers\Flash;
use Sunset\Models\Book;
use Sunset\Models\Comment;
use Sunset\Models\Post;
use Sunset\Services\ImageUploader;
use Sunset\Services\OpenLibraryService;

final class PostController
{
    private const MAX_COMMENT = 1000;

    /** POST ?page=post_create  (formulario normal con foto: funciona bien en celulares) */
    public function create(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $returnTo  = input('return_to');
        $body      = input('body');
        $bookId    = (int) input('book_id');
        $ratingRaw = input('rating');
        $rating    = $ratingRaw === '' ? null : (int) $ratingRaw;

        $book  = null;
        $error = null;
        if (mb_strlen($body) > Post::MAX_BODY) {
            $error = 'El texto es demasiado largo (máximo ' . Post::MAX_BODY . ' caracteres).';
        } elseif ($bookId > 0 && ($book = Book::findById($bookId)) === null) {
            $error = 'La obra vinculada no existe.';
        } elseif ($rating !== null && ($rating < 1 || $rating > 5)) {
            $error = 'La puntuación debe ser de 1 a 5 estrellas.';
        } elseif ($rating !== null && $book === null) {
            $error = 'Para puntuar, primero vincula una obra.';
        }

        $hasImage = is_array($_FILES['image'] ?? null)
            && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($error === null && $body === '' && !$hasImage && $rating === null) {
            $error = 'Escribe algo, agrega una foto o puntúa una obra.';
        }

        $imagePath = null;
        if ($error === null && $hasImage) {
            try {
                $imagePath = ImageUploader::savePostImage($_FILES['image']);
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        if ($error === null) {
            try {
                Post::create((int) Auth::id(), $body, $book['id'] ?? null, $rating, $imagePath);
                Flash::add('success', $rating !== null ? 'Reseña publicada.' : 'Publicación creada.');
            } catch (DuplicateReviewException) {
                if ($imagePath !== null) {
                    ImageUploader::delete($imagePath);
                }
                $error = 'Ya reseñaste esta obra. Elimina tu reseña anterior si quieres escribir otra.';
            }
        }

        if ($error !== null) {
            Flash::add('danger', $error);
            $_SESSION['old_post'] = $body;      // para no perder lo escrito
        }
        $this->redirectBack($returnTo);
    }

    /** POST ?page=post_delete */
    public function delete(): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $row = Post::delete((int) input('post_id'), (int) Auth::id());
        if ($row !== null) {
            if (!empty($row['image_path'])) {
                ImageUploader::delete($row['image_path']);
            }
            Flash::add('success', 'Publicación eliminada.');
        }
        $this->redirectBack(input('return_to'));
    }

    /** POST ?page=like  (AJAX) */
    public function like(): void
    {
        if (!Auth::check()) {
            json_response(['error' => 'No autorizado'], 401);
        }
        Csrf::verify();

        $result = Post::toggleLike((int) Auth::id(), (int) input('post_id'));
        if ($result === null) {
            json_response(['error' => 'La publicación no existe'], 404);
        }
        json_response($result);
    }

    /** GET ?page=comments&post_id=N  (AJAX) */
    public function comments(): void
    {
        if (!Auth::check()) {
            json_response(['error' => 'No autorizado'], 401);
        }
        $comments = Comment::forPost((int) ($_GET['post_id'] ?? 0));

        $html = '';
        foreach ($comments as $comment) {
            $html .= View::partial('partials/comment_item', ['comment' => $comment]);
        }
        json_response(['html' => $html]);
    }

    /** POST ?page=comment  (AJAX) */
    public function comment(): void
    {
        if (!Auth::check()) {
            json_response(['error' => 'No autorizado'], 401);
        }
        Csrf::verify();

        $postId = (int) input('post_id');
        $body   = input('body');
        if ($body === '' || mb_strlen($body) > self::MAX_COMMENT) {
            json_response(['error' => 'El comentario debe tener entre 1 y ' . self::MAX_COMMENT . ' caracteres.'], 422);
        }

        $comment = Comment::add($postId, (int) Auth::id(), $body);
        if ($comment === null) {
            json_response(['error' => 'La publicación no existe'], 404);
        }
        json_response([
            'html'  => View::partial('partials/comment_item', ['comment' => $comment]),
            'count' => Comment::count($postId),
        ]);
    }

    /** GET ?page=feed_more&before=ID&feed=all|following  (AJAX) */
    public function feedMore(): void
    {
        if (!Auth::check()) {
            json_response(['error' => 'No autorizado'], 401);
        }
        $mode   = (($_GET['feed'] ?? '') === 'following') ? 'following' : 'all';
        $before = max(1, (int) ($_GET['before'] ?? 0));

        $rows  = Post::feed((int) Auth::id(), $mode, Post::FEED_SIZE + 1, $before);
        $posts = array_slice($rows, 0, Post::FEED_SIZE);

        $html = '';
        foreach ($posts as $post) {
            $html .= View::partial('partials/post_card', ['post' => $post, 'returnTo' => 'home']);
        }
        json_response([
            'html'    => $html,
            'hasMore' => count($rows) > Post::FEED_SIZE,
            'next'    => $posts ? (int) end($posts)['id'] : $before,
        ]);
    }

    /** GET ?page=book_lookup&q=texto  (AJAX: sugerencias para "Vincular obra", solo catálogo local) */
    public function bookLookup(): void
    {
        if (!Auth::check()) {
            json_response(['error' => 'No autorizado'], 401);
        }
        $q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        if (mb_strlen($q) < 2) {
            json_response(['books' => []]);
        }

        $books = array_map(static fn (array $b): array => [
            'id'     => (int) $b['id'],
            'title'  => $b['title'],
            'author' => trim(explode(',', (string) $b['authors'])[0]),
            'cover'  => $b['cover_id'] ? OpenLibraryService::coverUrl((int) $b['cover_id'], 'S') : null,
        ], Book::search($q, 8));

        json_response(['books' => $books]);
    }

    /** Vuelve a la página de origen (solo destinos permitidos). */
    private function redirectBack(string $returnTo): never
    {
        if (preg_match('/^book:(\d+)$/', $returnTo, $m)) {
            redirect('book', ['id' => (int) $m[1]]);
        }
        redirect('home');
    }
}
