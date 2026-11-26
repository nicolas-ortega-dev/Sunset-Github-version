<?php /** @var array $forum @var ?string $role @var array $messages @var array $members */
use Sunset\Helpers\Auth; use Sunset\Helpers\Csrf;
$isAdmin=$role==='admin'; $isMember=$role!==null; ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="sunset-card forum-panel">
      <div class="forum-head">
        <div>
          <h1 class="h4 m-0"><?= e($forum['name']) ?></h1>
          <?php if(!empty($forum['description'])): ?><p class="text-muted mb-0"><?= e($forum['description']) ?></p><?php endif; ?>
          <?php if(!empty($forum['book_title'])): ?><a class="chip" href="<?= e(url('book',['id'=>$forum['book_id']])) ?>"><i class="bi bi-journal-bookmark"></i> <?= e($forum['book_title']) ?></a><?php endif; ?>
        </div>
        <?php if($isAdmin): ?>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn-sunset-outline" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil"></i> Editar</button>
          <form action="<?= e(url('forum_delete')) ?>" method="post" onsubmit="return confirm('¿Eliminar este foro permanentemente?');">
            <?= Csrf::field() ?><input type="hidden" name="forum_id" value="<?= (int)$forum['id'] ?>">
            <button type="submit" class="btn-sunset-toggle"><i class="bi bi-trash3"></i></button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <div class="chat-window" id="chat-window" data-forum-id="<?= (int)$forum['id'] ?>">
        <?php foreach($messages as $msg): require ROOT_PATH.'/views/partials/chat_message.php'; endforeach; ?>
      </div>

      <?php if($isMember): ?>
      <form class="chat-form" id="chat-form" data-forum-id="<?= (int)$forum['id'] ?>">
        <?= Csrf::field() ?>
        <textarea name="body" rows="2" maxlength="2000" placeholder="Escribe un mensaje…" aria-label="Mensaje"></textarea>
        <button type="submit" class="btn-sunset-dark"><i class="bi bi-send"></i></button>
      </form>
      <?php else: ?>
      <div class="chat-join-banner">
        <p>Únete al foro para poder escribir mensajes.</p>
        <form action="<?= e(url('forum_join')) ?>" method="post">
          <?= Csrf::field() ?><input type="hidden" name="forum_id" value="<?= (int)$forum['id'] ?>"><input type="hidden" name="action" value="join">
          <button type="submit" class="btn-sunset-dark">Unirse</button>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="sunset-card mb-3">
      <h2 class="h6">Miembros (<?= count($members) ?>)</h2>
      <ul class="member-list">
        <?php foreach($members as $m): ?>
        <li>
          <a class="member-item" href="<?= e(url('profile',[ 'u'=>$m['username']])) ?>">
            <span class="chat-avatar"><?php if(!empty($m['avatar_path'])): ?><img src="<?= e(asset($m['avatar_path'])) ?>" alt=""><?php else: ?><i class="bi bi-person-circle"></i><?php endif; ?></span>
            <span><?= e($m['username']) ?></span>
            <?php if($m['role']==='admin'): ?><span class="chip" style="font-size:.7rem;padding:.1rem .4rem">Admin</span><?php endif; ?>
          </a>
          <?php if($isAdmin&&(int)$m['id']!==(int)Auth::id()): ?>
          <form action="<?= e(url('forum_kick')) ?>" method="post">
            <?= Csrf::field() ?><input type="hidden" name="forum_id" value="<?= (int)$forum['id'] ?>"><input type="hidden" name="user_id" value="<?= (int)$m['id'] ?>">
            <button type="submit" class="icon-btn" title="Expulsar" onclick="return confirm('¿Expulsar a <?= e($m['username']) ?>?')"><i class="bi bi-person-dash"></i></button>
          </form>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php if($isMember&&!$isAdmin): ?>
      <form action="<?= e(url('forum_join')) ?>" method="post" class="mt-2">
        <?= Csrf::field() ?><input type="hidden" name="forum_id" value="<?= (int)$forum['id'] ?>"><input type="hidden" name="action" value="leave">
        <button type="submit" class="btn-sunset-toggle w-100">Salir del foro</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if($isAdmin): ?>
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content p-3">
    <h2 class="h5 mb-3">Editar foro</h2>
    <form action="<?= e(url('forum_edit')) ?>" method="post">
      <?= Csrf::field() ?><input type="hidden" name="forum_id" value="<?= (int)$forum['id'] ?>">
      <div class="mb-3"><label class="form-label fw-semibold">Nombre</label><input class="form-control" name="name" value="<?= e($forum['name']) ?>" maxlength="100" required></div>
      <div class="mb-3"><label class="form-label fw-semibold">Descripción</label><textarea class="form-control" name="description" rows="3" maxlength="300"><?= e($forum['description']??'') ?></textarea></div>
      <button type="submit" class="btn-sunset-dark w-100">Guardar</button>
    </form>
  </div></div>
</div>
<?php endif; ?>
