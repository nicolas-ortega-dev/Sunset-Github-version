<?php
/** @var array $msg @var int|null $myId @var int $forumId */
use Sunset\Helpers\Auth;
use Sunset\Helpers\Csrf;
use Sunset\Models\Forum;
$myId   ??= Auth::id();
$forumId ??= (int)$msg['forum_id'];
$mine = (int)$msg['user_id'] === (int)$myId;
?>
<div class="chat-msg <?= $mine ? 'mine' : '' ?>" id="msg-<?= (int)$msg['id'] ?>">
  <span class="chat-avatar" aria-hidden="true">
    <?php if (!empty($msg['avatar_path'])): ?>
      <img src="<?= e(asset($msg['avatar_path'])) ?>" alt="">
    <?php else: ?>
      <i class="bi bi-person-circle"></i>
    <?php endif; ?>
  </span>
  <div class="chat-bubble">
    <strong><?= e($msg['username']) ?></strong>
    <small title="<?= e(full_date($msg['created_at'])) ?>"><?= e(format_date($msg['created_at'])) ?></small>
    <p><?= nl2br(e($msg['body'])) ?></p>
  </div>
  <?php if ($myId !== null && Forum::memberRole($forumId, $myId) === 'admin' && !$mine): ?>
    <form action="<?= e(url('chat_delete')) ?>" method="post" class="chat-del">
      <?= Csrf::field() ?>
      <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
      <input type="hidden" name="forum_id" value="<?= (int)$forumId ?>">
      <button type="button" class="icon-btn" data-del-msg="<?= (int)$msg['id'] ?>">
        <i class="bi bi-x"></i>
      </button>
    </form>
  <?php endif; ?>
</div>
