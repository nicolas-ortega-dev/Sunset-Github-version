<?php declare(strict_types=1);
namespace Sunset\Controllers;

use Sunset\Core\Database;
use Sunset\Core\View;
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Helpers\Flash;
use Sunset\Models\Follow;
use Sunset\Models\Forum;
use Sunset\Models\Post;
use Sunset\Models\User;
use Sunset\Services\ImageUploader;

final class ProfileController {

    public function show(): void {
        Auth::requireLogin();
        $pdo = Database::pdo();
        $username = is_string($_GET['u'] ?? null) ? $_GET['u'] : null;

        if ($username) {
            $st = $pdo->prepare('SELECT id,username,bio,location,website,avatar_path,created_at FROM users WHERE username=:u');
            $st->execute(['u' => $username]);
            $user = $st->fetch() ?: null;
        } else {
            $user = User::findById((int)Auth::id());
        }

        if (!$user) {
            http_response_code(404);
            View::render('profile/not_found', ['title' => 'Usuario no encontrado']);
            return;
        }

        $counts     = Follow::counts((int)$user['id']);
        $isMe       = (int)$user['id'] === (int)Auth::id();
        $isFollowing = $isMe ? false : Follow::isFollowing((int)Auth::id(), (int)$user['id']);

        // publicaciones del usuario
        $st = $pdo->prepare(
            'SELECT p.id,p.user_id,p.body,p.image_path,p.rating,p.created_at,
                u.username,u.avatar_path,
                b.id AS book_id,b.title AS book_title,b.authors AS book_authors,b.cover_id AS book_cover,
                IF(b.rating_count=0,NULL,b.rating_sum/b.rating_count) AS book_avg,
                b.rating_count AS book_votes,
                (SELECT COUNT(*) FROM post_likes l WHERE l.post_id=p.id) AS like_count,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id=p.id) AS comment_count,
                0 AS liked
             FROM posts p JOIN users u ON u.id=p.user_id LEFT JOIN books b ON b.id=p.book_id
             WHERE p.user_id=:uid ORDER BY p.id DESC LIMIT 20'
        );
        $st->execute(['uid' => $user['id']]);
        $posts = $st->fetchAll();

        $forums = Forum::myForums((int)$user['id'], 6);
        $st2    = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id=:u');
        $st2->execute(['u' => $user['id']]);
        $postCount = (int)$st2->fetchColumn();

        View::render('profile/show', [
            'title'       => $user['username'],
            'user'        => $user,
            'counts'      => $counts,
            'isMe'        => $isMe,
            'isFollowing' => $isFollowing,
            'posts'       => $posts,
            'forums'      => $forums,
            'postCount'   => $postCount,
            'scripts'     => ['js/posts.js'],
        ]);
    }

    public function follow(): void {
        Auth::requireLogin();
        Csrf::verify();
        $targetId = (int)input('user_id');
        if ($targetId > 0 && $targetId !== (int)Auth::id()) {
            Follow::toggle((int)Auth::id(), $targetId);
        }
        $ref = input('return_to');
        if (preg_match('/^profile:(.+)$/', $ref, $m)) {
            redirect('profile', ['u' => $m[1]]);
        } elseif (preg_match('/^search:(.+)$/', $ref, $m)) {
            redirect('search', ['q' => $m[1], 'tab' => 'users']);
        } elseif ($ref === 'notifications') {
            redirect('notifications');
        }
        redirect('profile');
    }

    public function editForm(): void {
        Auth::requireLogin();
        $user = User::findById((int)Auth::id());
        View::render('profile/edit', ['title' => 'Editar perfil', 'user' => $user]);
    }

    public function editSave(): void {
        Auth::requireLogin();
        Csrf::verify();

        $bio      = mb_substr(trim(input('bio')), 0, 300);
        $location = mb_substr(trim(input('location')), 0, 100);
        $website  = mb_substr(trim(input('website')), 0, 200);

        $avatarPath = null;
        $hasImage   = is_array($_FILES['avatar'] ?? null)
            && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($hasImage) {
            try {
                $avatarPath = ImageUploader::savePostImage($_FILES['avatar']);
            } catch (\RuntimeException $e) {
                Flash::add('danger', $e->getMessage());
                redirect('profile_edit');
            }
        }

        $pdo  = Database::pdo();
        if ($avatarPath !== null) {
            $pdo->prepare('UPDATE users SET bio=:b,location=:l,website=:w,avatar_path=:a WHERE id=:id')
                ->execute(['b'=>$bio,'l'=>$location,'w'=>$website,'a'=>$avatarPath,'id'=>Auth::id()]);
        } else {
            $pdo->prepare('UPDATE users SET bio=:b,location=:l,website=:w WHERE id=:id')
                ->execute(['b'=>$bio,'l'=>$location,'w'=>$website,'id'=>Auth::id()]);
        }

        Flash::add('success', 'Perfil actualizado.');
        redirect('profile');
    }
}
