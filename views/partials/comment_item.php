<?php /** @var array $comment */ ?>
<li class="comment">
  <span class="comment-avatar" aria-hidden="true">
    <?php if (!empty($comment['avatar_path'])): ?>
      <img src="<?= e(asset($comment['avatar_path'])) ?>" alt="">
    <?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?>
  </span>
  <div class="comment-body">
    <strong><?= e($comment['username']) ?></strong>
    <small title="<?= e(full_date($comment['created_at'])) ?>"><?= e(format_date($comment['created_at'])) ?></small>
    <p><?= nl2br(e($comment['body'])) ?></p>
  </div>
</li>
