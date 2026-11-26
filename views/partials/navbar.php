<?php
$me     = \Sunset\Helpers\Auth::user();
if (!\Sunset\Helpers\Auth::check()) return;
$unread = \Sunset\Models\Notification::unreadCount((int)\Sunset\Helpers\Auth::id());
?>
<header class="sunset-nav">
  <div class="sunset-nav-inner">
    <?php require ROOT_PATH . '/views/partials/logo.php'; ?>

    <form class="sunset-search" action="<?= e(base_url().'/index.php') ?>" method="get" role="search">
      <input type="hidden" name="page" value="search">
      <i class="bi bi-search" aria-hidden="true"></i>
      <input type="search" name="q" maxlength="100" placeholder="Usuarios, foros, libros..."
             aria-label="Buscar usuarios, foros o libros">
    </form>

    <nav class="sunset-nav-links">
      <a href="<?= e(url('forums')) ?>" class="nav-text-link">Foros</a>

      <div class="notif-wrap" id="notif-wrap">
        <button class="nav-icon-btn" id="notif-btn" type="button"
                aria-label="Notificaciones" aria-expanded="false" aria-controls="notif-panel">
          <i class="bi <?= $unread > 0 ? 'bi-bell-fill' : 'bi-bell' ?>" id="notif-icon"></i>
          <?php if ($unread > 0): ?>
            <span class="notif-badge" id="notif-badge"><?= min($unread, 99) ?></span>
          <?php else: ?>
            <span class="notif-badge d-none" id="notif-badge">0</span>
          <?php endif; ?>
        </button>
        <div class="notif-panel" id="notif-panel" hidden>
          <div class="notif-panel-head">
            <strong>Notificaciones</strong>
            <a href="<?= e(url('notifications')) ?>" class="notif-panel-all">Ver todas</a>
          </div>
          <div class="notif-panel-body" id="notif-panel-body">
            <p class="notif-panel-empty">Cargando…</p>
          </div>
        </div>
      </div>

      <div class="dropdown">
        <button class="sunset-user" data-bs-toggle="dropdown" aria-expanded="false" type="button">
          <?php if (!empty($me['avatar_path'])): ?>
            <img src="<?= e(asset($me['avatar_path'])) ?>" alt="" class="sunset-avatar">
          <?php else: ?>
            <i class="bi bi-person-circle sunset-avatar-icon"></i>
          <?php endif; ?>
          <span class="nav-username"><?= e($me['username'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="<?= e(url('profile', ['u' => $me['username'] ?? ''])) ?>">Mi perfil</a></li>
          <li><a class="dropdown-item" href="<?= e(url('profile_edit')) ?>">Editar perfil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form action="<?= e(url('logout')) ?>" method="post">
              <?= \Sunset\Helpers\Csrf::field() ?>
              <button class="dropdown-item" type="submit">Cerrar sesión</button>
            </form>
          </li>
        </ul>
      </div>
    </nav>
  </div>
</header>
