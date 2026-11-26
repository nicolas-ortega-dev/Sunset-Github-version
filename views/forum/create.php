<?php /** @var array|null $book */ use Sunset\Helpers\Csrf; ?>
<div class="sunset-card" style="max-width:600px;margin:0 auto">
  <h1 class="h4 mb-3"><i class="bi bi-plus-circle"></i> Crear foro</h1>
  <form action="<?= e(url('forum_create')) ?>" method="post">
    <?= Csrf::field() ?>
    <input type="hidden" name="book_id" value="<?= $book?(int)$book['id']:'' ?>">
    <div class="mb-3">
      <label class="form-label fw-semibold">Nombre del foro *</label>
      <input class="form-control" name="name" maxlength="100" required placeholder="Ej: Fans de Harry Potter">
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold">Descripción (opcional)</label>
      <textarea class="form-control" name="description" rows="3" maxlength="300" placeholder="De qué trata este foro…"></textarea>
    </div>
    <?php if($book): ?>
    <div class="mb-3"><p class="mb-1 fw-semibold">Obra vinculada</p><span class="chip"><i class="bi bi-journal-bookmark"></i> <?= e($book['title']) ?></span></div>
    <?php endif; ?>
    <button type="submit" class="btn-sunset-dark w-100"><i class="bi bi-check2"></i> Crear foro</button>
  </form>
</div>
