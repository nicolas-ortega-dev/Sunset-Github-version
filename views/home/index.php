<?php
/** @var array $books @var bool $hasMore @var ?string $category @var ?string $lang @var int $nextOffset
 *  @var string $feedMode @var array $posts @var bool $postsMore @var int $postsNext */
$categoryLabels = ['libro' => 'Libros', 'manga' => 'Manga', 'comic' => 'Cómics'];
$moreUrl = url('catalog_more', array_filter(['category' => $category, 'lang' => $lang]));
$filtering = $category !== null || $lang !== null;
?>
<div class="row g-4">

  <!-- Feed -->
  <div class="col-lg-5">
    <?php
      $returnTo = 'home'; $presetBook = null; $lockBook = false; $composerTitle = '¿Qué quieres contarnos?';
      require ROOT_PATH . '/views/partials/composer.php';
    ?>

    <a class="d-lg-none jump-link" href="#catalog"><i class="bi bi-arrow-down-circle" aria-hidden="true"></i> Ir al catálogo de obras</a>

    <div class="feed-head">
      <h2 class="h5 m-0">Publicaciones</h2>
      <nav class="feed-tabs" aria-label="Tipo de feed">
        <a href="<?= e(url('home', array_filter(['category' => $category, 'lang' => $lang]))) ?>" class="<?= $feedMode === 'all' ? 'is-active' : '' ?>">Para ti</a>
        <a href="<?= e(url('home', array_filter(['category' => $category, 'lang' => $lang, 'feed' => 'following']))) ?>" class="<?= $feedMode === 'following' ? 'is-active' : '' ?>">Siguiendo</a>
      </nav>
    </div>

    <div id="feed" class="feed">
      <?php foreach ($posts as $post): ?>
        <?php require ROOT_PATH . '/views/partials/post_card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php if (!$posts): ?>
      <p class="feed-empty">
        <?= $feedMode === 'following'
            ? 'Aún no hay publicaciones de las personas que sigues. Mira la pestaña «Para ti».'
            : 'Todavía no hay publicaciones. ¡Sé la primera persona en compartir algo!' ?>
      </p>
    <?php endif; ?>

    <?php if ($postsMore): ?>
      <div class="text-center mt-3">
        <button type="button" class="btn-sunset-outline" data-feed-more
                data-before="<?= (int) $postsNext ?>" data-feed="<?= e($feedMode) ?>">
          <i class="bi bi-arrow-down" aria-hidden="true"></i> Ver publicaciones anteriores
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Catálogo -->
  <div class="col-lg-7">
    <section class="sunset-card" id="catalog"
             data-more-url="<?= e($moreUrl) ?>" data-offset="<?= (int) $nextOffset ?>">

      <div class="catalog-head">
        <h1 class="h4 m-0">Catálogo de obras</h1>

        <div class="dropdown">
          <button class="btn-sunset-outline" type="button" data-bs-toggle="dropdown"
                  data-bs-auto-close="outside" aria-expanded="false">
            <i class="bi bi-sliders" aria-hidden="true"></i> Filtros
            <?php if ($filtering): ?><span class="filter-dot" aria-label="Filtros activos"></span><?php endif; ?>
          </button>
          <form class="dropdown-menu dropdown-menu-end filter-menu" method="get" action="<?= e(base_url() . '/index.php') ?>">
            <input type="hidden" name="page" value="home">
            <?php if ($feedMode === 'following'): ?><input type="hidden" name="feed" value="following"><?php endif; ?>
            <p class="filter-title">Tipo de obra</p>
            <?php foreach ([''=>'Todas'] + $categoryLabels as $value => $label): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="category" id="cat-<?= e($value ?: 'all') ?>"
                       value="<?= e($value) ?>" <?= ($category ?? '') === $value ? 'checked' : '' ?>>
                <label class="form-check-label" for="cat-<?= e($value ?: 'all') ?>"><?= e($label) ?></label>
              </div>
            <?php endforeach; ?>
            <hr>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" name="lang" value="spa" id="lang-spa" <?= $lang === 'spa' ? 'checked' : '' ?>>
              <label class="form-check-label" for="lang-spa">Solo en español</label>
            </div>
            <button type="submit" class="btn-sunset-dark w-100 mt-3"><i class="bi bi-check2" aria-hidden="true"></i> Aplicar</button>
            <?php if ($filtering): ?>
              <a class="filter-clear" href="<?= e(url('home')) ?>">Quitar filtros</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <form class="catalog-search" action="<?= e(base_url() . '/index.php') ?>" method="get" role="search">
        <input type="hidden" name="page" value="search">
        <i class="bi bi-journal-text" aria-hidden="true"></i>
        <input type="search" name="q" maxlength="100" placeholder="Buscar una obra" aria-label="Buscar una obra">
        <button type="submit" class="btn-sunset-dark"><i class="bi bi-search" aria-hidden="true"></i> <span>Buscar</span></button>
      </form>

      <?php if (!$books): ?>
        <p class="text-muted my-4">No hay obras con esos filtros.</p>
      <?php else: ?>
        <div class="book-grid" id="book-grid">
          <?php foreach ($books as $book): ?>
            <?php require ROOT_PATH . '/views/partials/book_card.php'; ?>
          <?php endforeach; ?>
        </div>
        <?php if ($hasMore): ?>
          <div class="text-center mt-4">
            <button type="button" id="load-more" class="btn-sunset-dark"><i class="bi bi-plus-lg" aria-hidden="true"></i> Ver más</button>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</div>
