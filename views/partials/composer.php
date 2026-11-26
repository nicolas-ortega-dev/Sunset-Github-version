<?php
use Sunset\Helpers\Csrf;

/**
 * @var string      $returnTo       'home' o 'book:ID'
 * @var array|null  $presetBook     obra ya vinculada (página de la obra)
 * @var bool        $lockBook       true = no se puede quitar la obra vinculada
 * @var string      $composerTitle
 */
$oldBody = (string) ($_SESSION['old_post'] ?? '');
unset($_SESSION['old_post']);
$hasBook = $presetBook !== null;
?>
<form class="composer sunset-card" action="<?= e(url('post_create')) ?>" method="post"
      enctype="multipart/form-data" data-composer data-lock-book="<?= $lockBook ? '1' : '0' ?>">
  <?= Csrf::field() ?>
  <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
  <input type="hidden" name="book_id" value="<?= $hasBook ? (int) $presetBook['id'] : '' ?>">

  <label for="composer-body" class="composer-title"><i class="bi bi-pencil-square" aria-hidden="true"></i> <?= e($composerTitle) ?></label>
  <textarea id="composer-body" name="body" rows="3" maxlength="2000"
            placeholder="Comparte tus impresiones, una frase o una reseña…"><?= e($oldBody) ?></textarea>

  <div class="composer-linked" data-linked <?= $hasBook ? '' : 'hidden' ?>>
    <span class="chip"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> <span data-linked-title><?= e($presetBook['title'] ?? '') ?></span></span>
    <?php if (!$lockBook): ?>
      <button type="button" class="icon-btn" data-unlink aria-label="Quitar obra vinculada"><i class="bi bi-x-lg"></i></button>
    <?php endif; ?>
  </div>

  <fieldset class="composer-rating" data-rating-wrap <?= $hasBook ? '' : 'hidden' ?>>
    <legend>Tu puntuación (opcional)</legend>
    <div class="star-input">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <input type="radio" name="rating" id="star-<?= $i ?>" value="<?= $i ?>" class="visually-hidden">
        <label for="star-<?= $i ?>" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>">
          <i class="bi bi-star-fill" aria-hidden="true"></i><span class="visually-hidden"><?= $i ?> estrella<?= $i > 1 ? 's' : '' ?></span>
        </label>
      <?php endfor; ?>
    </div>
    <button type="button" class="link-btn" data-rating-clear>Quitar puntuación</button>
  </fieldset>

  <div class="composer-picker" data-picker hidden>
    <input type="search" data-picker-input maxlength="100" placeholder="Buscar obra por título o autor" aria-label="Buscar obra para vincular" autocomplete="off">
    <ul class="picker-results" data-picker-results></ul>
  </div>

  <div class="image-preview" data-image-preview hidden>
    <img src="" alt="Vista previa de la foto">
    <button type="button" class="btn-sunset-outline" data-image-remove><i class="bi bi-x-lg" aria-hidden="true"></i> Quitar foto</button>
  </div>

  <div class="composer-actions">
    <div class="composer-tools">
      <?php if (!$lockBook): ?>
        <button type="button" class="btn-sunset-outline" data-picker-toggle><i class="bi bi-journal-plus" aria-hidden="true"></i> Vincular obra</button>
      <?php endif; ?>
      <label class="btn-sunset-outline" for="composer-image"><i class="bi bi-image" aria-hidden="true"></i> Agregar foto</label>
      <input id="composer-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-image-input>
    </div>
    <button type="submit" class="btn-sunset-dark"><i class="bi bi-send" aria-hidden="true"></i> Publicar</button>
  </div>
</form>
