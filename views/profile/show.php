<?php /** @var array $user @var array $counts @var bool $isMe @var bool $isFollowing @var array $posts @var array $forums @var int $postCount */
use Sunset\Helpers\Auth; use Sunset\Helpers\Csrf; use Sunset\Models\Forum; ?>
<div class="profile-header sunset-card mb-3">
  <div class="profile-avatar">
    <?php if(!empty($user['avatar_path'])): ?><img src="<?= e(asset($user['avatar_path'])) ?>" alt="Avatar de <?= e($user['username']) ?>"><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?>
  </div>
  <div class="profile-info">
    <h1 class="h3 mb-0"><?= e($user['username']) ?></h1>
    <?php if(!empty($user['bio'])): ?><p class="mb-1"><?= nl2br(e($user['bio'])) ?></p><?php endif; ?>
    <?php if(!empty($user['location'])): ?><small><i class="bi bi-geo-alt"></i> <?= e($user['location']) ?></small><?php endif; ?>
    <div class="profile-stats">
      <span><strong><?= (int)$counts['followers'] ?></strong> seguidores</span>
      <span><strong><?= (int)$counts['following'] ?></strong> siguiendo</span>
      <span><strong><?= $postCount ?></strong> publicaciones</span>
    </div>
    <?php if($isMe): ?>
    <a class="btn-sunset-outline mt-2" href="<?= e(url('profile_edit')) ?>"><i class="bi bi-pencil"></i> Editar perfil</a>
    <?php endif; ?>
    <?php if(!$isMe): ?>
    <form action="<?= e(url('follow')) ?>" method="post" class="mt-2">
      <?= Csrf::field() ?>
      <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
      <input type="hidden" name="return_to" value="profile:<?= e($user['username']) ?>">
      <button type="submit" class="btn-sunset-toggle <?= $isFollowing?'is-on':'' ?>">
        <i class="bi <?= $isFollowing?'bi-person-check-fill':'bi-person-plus' ?>"></i>
        <?= $isFollowing?'Siguiendo':'Seguir' ?>
      </button>
      <?php if($isFollowing): ?>
      <input type="hidden" name="action_remove" value="1">
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <h2 class="h5 mb-2">Publicaciones</h2>
    <div class="feed">
      <?php $returnTo='home'; foreach($posts as $post): require ROOT_PATH.'/views/partials/post_card.php'; endforeach; ?>
    </div>
    <?php if(!$posts): ?><p class="feed-empty">Este usuario aún no ha publicado nada.</p><?php endif; ?>
  </div>
  <div class="col-lg-5">
    <div class="sunset-card">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="h6 m-0">Foros en los que participa</h2>
      </div>
      <?php if(!$forums): ?><p class="text-muted">No participa en ningún foro.</p><?php else: ?>
      <div class="forum-list">
        <?php foreach($forums as $f): require ROOT_PATH.'/views/partials/forum_card.php'; endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
