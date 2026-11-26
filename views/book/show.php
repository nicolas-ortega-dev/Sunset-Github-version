<?php
use Sunset\Helpers\Csrf;
use Sunset\Models\Forum;
use Sunset\Services\OpenLibraryService;

/** @var array $book @var array $shelf @var array $links @var array $posts @var string $returnTo */
$langNames = ['spa' => 'Español', 'eng' => 'Inglés', 'fre' => 'Francés', 'por' => 'Portugués',
              'jpn' => 'Japonés', 'ger' => 'Alemán', 'ita' => 'Italiano'];
$catNames  = ['libro' => 'Libro', 'manga' => 'Manga', 'comic' => 'Cómic'];
$rating    = $book['rating_avg'] !== null ? number_format((float) $book['rating_avg'], 1) : null;
$subjects  = array_slice(array_filter(array_map('trim', explode('|', (string) $book['subjects']))), 0, 6);

$shelfButtons = [
    'read'     => ['Leído',    'bi-check-circle',  'bi-check-circle-fill'],
    'wishlist' => ['Deseados', 'bi-bookmark-heart', 'bi-bookmark-heart-fill'],
];
?>
<article class="sunset-card book-detail">
  <div class="row g-4">

    <div class="col-12 col-md-4 col-lg-3 text-center">
      <div class="book-cover book-cover-large">
        <span class="book-cover-fallback"><?= e($book['title']) ?></span>
        <?php if (!empty($book['cover_id'])): ?>
          <img src="<?= e(OpenLibraryService::coverUrl((int) $book['cover_id'], 'L')) ?>"
               alt="Portada de <?= e($book['title']) ?>" onerror="this.remove()">
        <?php endif; ?>
      </div>
      <p class="book-score">
        <i class="bi bi-star-fill" aria-hidden="true"></i>
        <?php if ($rating !== null): ?>
          <strong><?= e($rating) ?></strong>
          <small>(<?= (int) $book['rating_count'] ?> reseña<?= (int) $book['rating_count'] === 1 ? '' : 's' ?>)</small>
        <?php else: ?>
          <small>Aún sin reseñas</small>
        <?php endif; ?>
      </p>
    </div>

    <div class="col-12 col-md-8 col-lg-9">
      <h1 class="book-h1"><?= e($book['title']) ?></h1>
      <p class="book-by"><?= e($book['authors']) ?></p>

      <p class="book-meta">
        <span class="chip"><?= e($catNames[$book['category']] ?? 'Libro') ?></span>
        <span class="chip"><?= e($langNames[$book['language']] ?? strtoupper((string) $book['language'])) ?></span>
        <?php if (!empty($book['first_publish_year'])): ?><span class="chip">Desde <?= (int) $book['first_publish_year'] ?></span><?php endif; ?>
      </p>

      <div class="action-row">
        <?php foreach ($shelfButtons as $status => [$label, $iconOff, $iconOn]): ?>
          <?php $on = !empty($shelf[$status]); ?>
          <form action="<?= e(url('shelf')) ?>" method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <button type="submit" class="btn-sunset-toggle <?= $on ? 'is-on' : '' ?>" aria-pressed="<?= $on ? 'true' : 'false' ?>">
              <i class="bi <?= $on ? $iconOn : $iconOff ?>" aria-hidden="true"></i> <?= e($label) ?>
            </button>
          </form>
        <?php endforeach; ?>
      </div>

      <h2 class="h6 section-label">Sinopsis</h2>
      <?php if (!empty($book['description'])): ?>
        <p class="book-desc"><?= nl2br(e($book['description'])) ?></p>
      <?php else: ?>
        <p class="text-muted">Esta obra aún no tiene sinopsis disponible.</p>
      <?php endif; ?>

      <?php if ($subjects): ?>
        <p class="book-meta">
          <?php foreach ($subjects as $s): ?><span class="chip chip-soft"><?= e($s) ?></span><?php endforeach; ?>
        </p>
      <?php endif; ?>

      <h2 class="h6 section-label">Dónde conseguirlo</h2>
      <div class="action-row">
        <a class="btn-sunset-dark" href="<?= e($links['buscalibre']) ?>" target="_blank" rel="noopener noreferrer">
          <i class="bi bi-bag-heart" aria-hidden="true"></i> Buscalibre
        </a>
        <a class="btn-sunset-dark" href="<?= e($links['openlibrary']) ?>" target="_blank" rel="noopener noreferrer">
          <i class="bi bi-book-half" aria-hidden="true"></i> Open Library
        </a>
      </div>
      <p class="small text-muted mt-2 mb-0">Sunset no vende libros: te lleva a tiendas externas.</p>

      <?php if (!empty($demoOffers)): ?>
      <h2 class="h6 section-label mt-3">Librerías <span class="chip chip-soft" style="font-size:.7rem;vertical-align:middle">Demo</span></h2>
      <div class="store-list">
        <?php foreach ($demoOffers as $offer): ?>
        <a class="store-card <?= $offer['is_demo'] ? 'is-demo' : '' ?>" href="<?= e($offer['url']) ?>" target="_blank" rel="noopener noreferrer">
          <span class="store-icon"><i class="bi <?= $offer['type'] === 'virtual' ? 'bi-globe' : 'bi-shop' ?>"></i></span>
          <span class="store-info">
            <strong><?= e($offer['name']) ?></strong>
            <small><?= $offer['type'] === 'virtual' ? 'Tienda virtual' : 'Librería física' ?></small>
          </span>
          <span class="store-price"><?= e($offer['price']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</article>

<div class="row g-4 mt-1">
  <div class="col-lg-6">
    <?php
      $presetBook = $book; $lockBook = true; $composerTitle = 'Deja una reseña';
      require ROOT_PATH . '/views/partials/composer.php';
    ?>
  </div>
  <div class="col-lg-6">
    <h2 class="h5 mb-3">Reseñas de usuarios</h2>
    <div class="feed">
      <?php foreach ($posts as $post): ?>
        <?php require ROOT_PATH . '/views/partials/post_card.php'; ?>
      <?php endforeach; ?>
    </div>
    <?php if (!$posts): ?>
      <p class="feed-empty">Todavía nadie ha reseñado esta obra. ¡Puedes ser la primera persona!</p>
    <?php endif; ?>

    <?php $relForums=Forum::forBook((int)$book['id']); ?>
    <section class="sunset-card mt-3">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <h2 class="h6 m-0">Foros relacionados</h2>
        <a class="btn-sunset-dark" href="<?= e(url('forum_create',['book_id'=>$book['id']])) ?>"><i class="bi bi-plus-lg"></i> Crear foro</a>
      </div>
      <?php if(!$relForums): ?><p class="text-muted">Aún no hay foros para esta obra. ¡Crea el primero!</p><?php else: ?>
      <div class="forum-list">
        <?php foreach($relForums as $f):
          require ROOT_PATH.'/views/partials/forum_card.php';
        endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>
</div>
