<?php /** @var array $forums @var string $q @var string $tab */ ?>
<div class="sunset-card">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h1 class="h4 m-0"><i class="bi bi-people-fill" aria-hidden="true"></i> Foros</h1>
    <a class="btn-sunset-dark" href="<?= e(url('forum_create')) ?>"><i class="bi bi-plus-lg"></i> Crear foro</a>
  </div>
  <form class="catalog-search mb-3" method="get" action="<?= e(base_url().'/index.php') ?>" role="search">
    <input type="hidden" name="page" value="forums">
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Buscar foros…" aria-label="Buscar foros">
    <button type="submit" class="btn-sunset-dark"><i class="bi bi-search"></i> <span>Buscar</span></button>
  </form>
  <?php if($q===''): ?>
  <nav class="feed-tabs mb-3">
    <a href="<?= e(url('forums')) ?>" class="<?= $tab==='explore'?'is-active':'' ?>">Explorar</a>
    <a href="<?= e(url('forums',['tab'=>'mine'])) ?>" class="<?= $tab==='mine'?'is-active':'' ?>">Mis foros</a>
  </nav>
  <?php endif; ?>
  <?php if(!$forums): ?><p class="feed-empty">No se encontraron foros.</p><?php endif; ?>
  <div class="forum-list">
    <?php foreach($forums as $f): require ROOT_PATH.'/views/partials/forum_card.php'; endforeach; ?>
  </div>
</div>
