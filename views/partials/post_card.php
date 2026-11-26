<?php
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Services\OpenLibraryService;

/** @var array $post @var string $returnTo */
$mine  = (int) $post['user_id'] === Auth::id();
$liked = (int) $post['liked'] === 1;
?>
<article class="post-card sunset-card" id="post-<?= (int) $post['id'] ?>" data-post-id="<?= (int) $post['id'] ?>">
  <header class="post-head">
    <span class="post-avatar" aria-hidden="true">
      <?php if (!empty($post['avatar_path'])): ?>
        <img src="<?= e(asset($post['avatar_path'])) ?>" alt="">
      <?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?>
    </span>
    <div class="post-who">
      <strong><?= e($post['username']) ?></strong>
      <small title="<?= e(full_date($post['created_at'])) ?>"><?= e(format_date($post['created_at'])) ?></small>
    </div>
    <?php if ($mine): ?>
      <form action="<?= e(url('post_delete')) ?>" method="post" class="ms-auto"
            onsubmit="return confirm('¿Eliminar esta publicación? No se puede deshacer.');">
        <?= Csrf::field() ?>
        <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
        <button type="submit" class="icon-btn" aria-label="Eliminar publicación"><i class="bi bi-trash3"></i></button>
      </form>
    <?php endif; ?>
  </header>

  <?php if (!empty($post['book_id'])): ?>
    <a class="post-book" href="<?= e(url('book', ['id' => $post['book_id']])) ?>">
      <span class="post-book-cover">
        <?php if (!empty($post['book_cover'])): ?>
          <img src="<?= e(OpenLibraryService::coverUrl((int) $post['book_cover'], 'S')) ?>" alt="" loading="lazy" onerror="this.remove()">
        <?php endif; ?>
      </span>
      <span class="post-book-info">
        <span class="post-book-title"><?= e($post['book_title']) ?></span>
        <span class="post-ratings">
          <?php if ($post['rating'] !== null): ?>
            <span class="rating-user" title="Puntuación de <?= e($post['username']) ?>">
              <?= stars((int) $post['rating']) ?> <strong><?= (int) $post['rating'] ?>/5</strong>
            </span>
          <?php endif; ?>
          <?php if ($post['book_avg'] !== null): ?>
            <span class="rating-avg" title="Promedio de todas las reseñas en Sunset">
              Promedio <i class="bi bi-star-fill" aria-hidden="true"></i> <?= e(number_format((float) $post['book_avg'], 1)) ?>
              <small>(<?= (int) $post['book_votes'] ?>)</small>
            </span>
          <?php endif; ?>
        </span>
      </span>
    </a>
  <?php endif; ?>

  <?php if ($post['body'] !== ''): ?>
    <p class="post-body"><?= nl2br(e($post['body'])) ?></p>
  <?php endif; ?>

  <?php if (!empty($post['image_path'])): ?>
    <img class="post-image" src="<?= e(asset($post['image_path'])) ?>" alt="Foto de la publicación de <?= e($post['username']) ?>" loading="lazy">
  <?php endif; ?>

  <footer class="post-actions">
    <button type="button" class="post-action <?= $liked ? 'is-liked' : '' ?>" data-like aria-pressed="<?= $liked ? 'true' : 'false' ?>">
      <i class="bi <?= $liked ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
      <span data-like-count><?= (int) $post['like_count'] ?></span>
      <span class="visually-hidden">Me gusta</span>
    </button>
    <button type="button" class="post-action" data-comments-toggle aria-expanded="false">
      <i class="bi bi-chat" aria-hidden="true"></i>
      <span data-comment-count><?= (int) $post['comment_count'] ?></span>
      <span class="post-action-label">Comentar</span>
    </button>
  </footer>

  <div class="post-comments" data-comments hidden>
    <ul class="comment-list" data-comment-list></ul>
    <form class="comment-form" data-comment-form>
      <input type="text" name="body" maxlength="1000" placeholder="Escribe un comentario…" aria-label="Escribe un comentario" autocomplete="off" required>
      <button type="submit" class="btn-sunset-dark" aria-label="Enviar comentario"><i class="bi bi-send" aria-hidden="true"></i></button>
    </form>
  </div>
</article>
