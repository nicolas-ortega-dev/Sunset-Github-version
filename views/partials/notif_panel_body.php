<?php
/** @var array $notifs */
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Models\Follow;

$typeLabels = [
    'follow'  => 'te empezó a seguir',
    'like'    => 'le gustó tu publicación',
    'comment' => 'comentó tu publicación',
];
?>
<?php if (!$notifs): ?>
  <p class="notif-panel-empty">No tienes notificaciones aún.</p>
<?php else: ?>
  <ul class="notif-panel-list">
    <?php foreach ($notifs as $n): ?>
    <li class="notif-panel-item <?= (int)$n['is_read'] === 0 ? 'unread' : '' ?>">
      <a class="notif-actor" href="<?= e(url('profile', ['u' => $n['actor_username']])) ?>">
        <?php if (!empty($n['actor_avatar'])): ?>
          <img src="<?= e(asset($n['actor_avatar'])) ?>" alt="">
        <?php else: ?>
          <i class="bi bi-person-circle"></i>
        <?php endif; ?>
      </a>
      <div class="notif-panel-text">
        <a href="<?= e(url('profile', ['u' => $n['actor_username']])) ?>" class="fw-semibold"><?= e($n['actor_username']) ?></a>
        <?= $typeLabels[$n['type']] ?? $n['type'] ?>
        <small><?= e(format_date($n['created_at'])) ?></small>
      </div>
      <?php if ($n['type'] === 'follow'): ?>
        <?php $isFollowing = Follow::isFollowing((int)Auth::id(), (int)$n['actor_id']); ?>
        <form action="<?= e(url('follow')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="user_id" value="<?= (int)$n['actor_id'] ?>">
          <input type="hidden" name="return_to" value="notifications">
          <button type="submit" class="btn-sunset-toggle <?= $isFollowing ? 'is-on' : '' ?>" style="font-size:.8rem;padding:.3rem .7rem;min-height:34px">
            <?= $isFollowing ? 'Siguiendo' : 'Seguir' ?>
          </button>
        </form>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
