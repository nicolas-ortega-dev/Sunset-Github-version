<?php
/** @var array $f */
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Models\Forum;
$role = Forum::memberRole((int)$f['id'], (int)Auth::id());
?>
<div class="forum-card">
  <span class="forum-avatar"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
  <div class="forum-info">
    <a class="forum-name" href="<?= e(url('forum', ['id' => $f['id']])) ?>"><?= e($f['name']) ?></a>
    <?php if (!empty($f['book_title'])): ?>
      <small class="forum-book"><i class="bi bi-journal-bookmark"></i> <?= e($f['book_title']) ?></small>
    <?php endif; ?>
    <small><i class="bi bi-people"></i> <?= (int)$f['member_count'] ?> miembro<?= (int)$f['member_count'] === 1 ? '' : 's' ?></small>
  </div>
  <form action="<?= e(url('forum_join')) ?>" method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="forum_id" value="<?= (int)$f['id'] ?>">
    <input type="hidden" name="action" value="<?= $role ? 'leave' : 'join' ?>">
    <input type="hidden" name="return_to" value="forums">
    <button type="submit" class="btn-sunset-toggle <?= $role ? 'is-on' : '' ?>">
      <?= $role ? 'Unido' : 'Unirse' ?>
    </button>
  </form>
</div>
