<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use Sunset\Controllers\AuthController;
use Sunset\Controllers\BookController;
use Sunset\Controllers\HomeController;
use Sunset\Controllers\ForumController;
use Sunset\Controllers\NotificationController;
use Sunset\Controllers\PostController;
use Sunset\Controllers\ProfileController;
use Sunset\Controllers\SearchController;

/*
 * Tabla de rutas: 'pagina' => [método HTTP => [Controlador, 'acción']]
 * Para agregar una pantalla nueva, basta con sumar una línea aquí.
 */
$routes = [
    'home'         => ['GET'  => [HomeController::class, 'index']],
    'catalog_more' => ['GET'  => [HomeController::class, 'more']],
    'search'       => ['GET'  => [SearchController::class, 'index']],
    'book'         => ['GET'  => [BookController::class, 'show']],
    'shelf'        => ['POST' => [BookController::class, 'shelf']],
    'post_create'  => ['POST' => [PostController::class, 'create']],
    'post_delete'  => ['POST' => [PostController::class, 'delete']],
    'like'         => ['POST' => [PostController::class, 'like']],
    'comments'     => ['GET'  => [PostController::class, 'comments']],
    'comment'      => ['POST' => [PostController::class, 'comment']],
    'feed_more'    => ['GET'  => [PostController::class, 'feedMore']],
    'book_lookup'    => ['GET'  => [PostController::class, 'bookLookup']],
    'forums'         => ['GET'  => [ForumController::class, 'index']],
    'forum'          => ['GET'  => [ForumController::class, 'show']],
    'forum_create'   => ['GET'  => [ForumController::class, 'create'],
                         'POST' => [ForumController::class, 'store']],
    'forum_edit'     => ['POST' => [ForumController::class, 'edit']],
    'forum_delete'   => ['POST' => [ForumController::class, 'delete']],
    'forum_join'     => ['POST' => [ForumController::class, 'join']],
    'forum_kick'     => ['POST' => [ForumController::class, 'kick']],
    'chat_messages'  => ['GET'  => [ForumController::class, 'messages']],
    'chat_send'      => ['POST' => [ForumController::class, 'send']],
    'chat_delete'    => ['POST' => [ForumController::class, 'deleteMessage']],
    'notifications'  => ['GET'  => [NotificationController::class, 'index']],
    'notif_panel'    => ['GET'  => [NotificationController::class, 'panel']],
    'notif_read'     => ['POST' => [NotificationController::class, 'markRead']],
    'follow'         => ['POST' => [ProfileController::class, 'follow']],
    'profile'        => ['GET'  => [ProfileController::class, 'show']],
    'profile_edit'   => ['GET'  => [ProfileController::class, 'editForm'],
                         'POST' => [ProfileController::class, 'editSave']],
    'auth'     => ['GET'  => [AuthController::class, 'showForm']],
    'register' => ['POST' => [AuthController::class, 'register']],
    'login'    => ['POST' => [AuthController::class, 'login']],
    'logout'   => ['POST' => [AuthController::class, 'logout']],
];

$page   = is_string($_GET['page'] ?? null) ? $_GET['page'] : 'home';
$method = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET';

if (!isset($routes[$page])) {
    http_response_code(404);
    exit('Página no encontrada.');
}
if (!isset($routes[$page][$method])) {
    http_response_code(405);
    exit('Método no permitido.');
}

[$class, $action] = $routes[$page][$method];
(new $class())->$action();
