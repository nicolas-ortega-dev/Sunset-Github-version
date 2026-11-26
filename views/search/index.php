<?php
/** @var string $q @var array $books @var array $users @var array $forums */
use Sunset\Models\Forum;
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
$tab = $_GET['tab'] ?? 'books';
?>
<section class="sunset-card">
  <h1 class="h4">Buscar en Sunset</h1>
  <form class="catalog-search mb-3" action="<?= e(base_url().'/index.php') ?>" method="get" role="search">
    <input type="hidden" name="page" value="search">
    <i class="bi bi-search" aria-hidden="true"></i>
    <input type="search" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Título, usuario, foro…" autofocus aria-label="Buscar">
    <button type="submit" class="btn-sunset-dark"><i class="bi bi-search"></i> <span>Buscar</span></button>
  </form>

  <?php if ($q !== '' && mb_strlen($q) >= 2): ?>
  <nav class="feed-tabs mb-3">
    <a href="<?= e(url('search', ['q'=>$q,'tab'=>'books'])) ?>" class="<?= $tab==='books'?'is-active':'' ?>">
      Libros <span class="badge"><?= count($books) ?></span>
    </a>
    <a href="<?= e(url('search', ['q'=>$q,'tab'=>'users'])) ?>" class="<?= $tab==='users'?'is-active':'' ?>">
      Usuarios <span class="badge"><?= count($users) ?></span>
    </a>
    <a href="<?= e(url('search', ['q'=>$q,'tab'=>'forums'])) ?>" class="<?= $tab==='forums'?'is-active':'' ?>">
      Foros <span class="badge"><?= count($forums) ?></span>
    </a>
  </nav>

  <?php if ($tab === 'books'): ?>
    <?php if (!$books): ?><p class="feed-empty">No encontramos libros para «<?= e($q) ?>».</p><?php else: ?>
    <div class="book-grid">
      <?php foreach ($books as $book): require ROOT_PATH.'/views/partials/book_card.php'; endforeach; ?>
    </div>
    <?php endif; ?>

  <?php elseif ($tab === 'users'): ?>
    <?php if (!$users): ?><p class="feed-empty">No encontramos usuarios para «<?= e($q) ?>».</p><?php else: ?>
    <ul class="notif-list">
      <?php foreach ($users as $u): ?>
      <li class="notif-item">
        <a class="notif-actor" href="<?= e(url('profile', ['u'=>$u['username']])) ?>">
          <?php if (!empty($u['avatar_path'])): ?><img src="<?= e(asset($u['avatar_path'])) ?>" alt=""><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?>
          <strong><?= e($u['username']) ?></strong>
        </a>
        <?php if (!empty($u['bio'])): ?><small><?= e(mb_substr($u['bio'],0,80)) ?></small><?php endif; ?>
        <?php if ((int)$u['id'] !== Auth::id()): ?>
        <form action="<?= e(url('follow')) ?>" method="post" class="ms-auto">
          <?= Csrf::field() ?>
          <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="return_to" value="search:<?= e($q) ?>">
          <button type="submit" class="btn-sunset-toggle">Seguir</button>
        </form>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

  <?php elseif ($tab === 'forums'): ?>
    <?php if (!$forums): ?><p class="feed-empty">No encontramos foros para «<?= e($q) ?>».</p><?php else: ?>
    <div class="forum-list">
      <?php foreach ($forums as $f): require ROOT_PATH.'/views/partials/forum_card.php'; endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <?php else: ?>
    <p class="text-muted">Escribe al menos 2 caracteres para buscar libros, usuarios y foros.</p>
  <?php endif; ?>
</section>
