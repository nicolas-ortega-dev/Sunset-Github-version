<?php
use Sunset\Services\OpenLibraryService;

/** @var array $book */
$rating = $book['rating_avg'] !== null ? number_format((float) $book['rating_avg'], 1) : '–';
$author = trim(explode(',', (string) $book['authors'])[0]);
?>
<a class="book-card" href="<?= e(url('book', ['id' => $book['id']])) ?>">
  <div class="book-cover">
    <span class="book-cover-fallback"><?= e($book['title']) ?></span>
    <?php if (!empty($book['cover_id'])): ?>
      <img src="<?= e(OpenLibraryService::coverUrl((int) $book['cover_id'], 'M')) ?>"
           alt="Portada de <?= e($book['title']) ?>" loading="lazy" onerror="this.remove()">
    <?php endif; ?>
    <span class="book-rating" title="Nota de los usuarios de Sunset"><i class="bi bi-star-fill" aria-hidden="true"></i> <?= e($rating) ?></span>
  </div>
  <div class="book-title"><?= e($book['title']) ?></div>
  <div class="book-author"><?= e($author) ?></div>
</a>
