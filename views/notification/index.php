<?php /** @var array $notifs */
use Sunset\Models\Follow; use Sunset\Helpers\Auth; use Sunset\Helpers\Csrf;
$typeLabels=['follow'=>'te empezó a seguir','like'=>'le gustó tu publicación','comment'=>'comentó tu publicación']; ?>
<div class="sunset-card">
  <h1 class="h4 mb-3"><i class="bi bi-bell-fill"></i> Notificaciones</h1>
  <?php if(!$notifs): ?><p class="feed-empty">No tienes notificaciones aún.</p><?php else: ?>
  <ul class="notif-list">
    <?php foreach($notifs as $n): ?>
    <li class="notif-item <?= (int)$n['is_read']===0?'unread':'' ?>">
      <a class="notif-actor" href="<?= e(url('profile',['u'=>$n['actor_username']])) ?>">
        <?php if(!empty($n['actor_avatar'])): ?><img src="<?= e(asset($n['actor_avatar'])) ?>" alt=""><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?>
        <strong><?= e($n['actor_username']) ?></strong>
      </a>
      <span><?= $typeLabels[$n['type']]??$n['type'] ?></span>
      <small><?= e(format_date($n['created_at'])) ?></small>
      <?php if($n['type']==='follow'): ?>
      <?php $isFollowing=Follow::isFollowing((int)Auth::id(),(int)$n['actor_id']); ?>
      <form action="<?= e(url('follow')) ?>" method="post" class="d-inline-flex gap-1">
        <?= Csrf::field() ?><input type="hidden" name="user_id" value="<?= (int)$n['actor_id'] ?>"><input type="hidden" name="return_to" value="notifications">
        <button type="submit" class="btn-sunset-toggle <?= $isFollowing?'is-on':'' ?>"><?= $isFollowing?'Siguiendo':'Seguir' ?></button>
      </form>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
