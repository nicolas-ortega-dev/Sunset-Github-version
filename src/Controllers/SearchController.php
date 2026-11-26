<?php declare(strict_types=1);
namespace Sunset\Controllers;

use Sunset\Core\Database;
use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Models\Forum;
use Sunset\Services\CatalogService;

final class SearchController {
    public function index(): void {
        Auth::requireLogin();
        $q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';

        $books = $users = $forums = [];
        if (mb_strlen($q) >= 2) {
            $books  = (new CatalogService())->search($q, 24);
            $forums = Forum::search($q, 10);
            $pdo    = Database::pdo();
            $st     = $pdo->prepare(
                'SELECT id,username,bio,avatar_path FROM users
                 WHERE username LIKE :q OR bio LIKE :q2 LIMIT 10'
            );
            $like = '%' . $q . '%';
            $st->execute(['q' => $like, 'q2' => $like]);
            $users = $st->fetchAll();
        }

        View::render('search/index', [
            'title'  => 'Buscar',
            'q'      => $q,
            'books'  => $books,
            'users'  => $users,
            'forums' => $forums,
        ]);
    }
}
