<?php /** @var array $user */ use Sunset\Helpers\Csrf; ?>
<div class="sunset-card" style="max-width:560px;margin:0 auto">
  <h1 class="h4 mb-3"><i class="bi bi-person-gear"></i> Editar perfil</h1>
  <form action="<?= e(url('profile_edit')) ?>" method="post" enctype="multipart/form-data">
    <?= Csrf::field() ?>

    <div class="mb-3 text-center" data-avatar-wrap>
      <div class="avatar-preview-wrap">
        <?php if (!empty($user['avatar_path'])): ?>
          <img id="avatar-preview" src="<?= e(asset($user['avatar_path'])) ?>" class="profile-avatar-preview" alt="Avatar actual">
        <?php else: ?>
          <i id="avatar-icon" class="bi bi-person-circle" style="font-size:5rem;color:var(--sunset-blue);display:block"></i>
          <img id="avatar-preview" src="" class="profile-avatar-preview" alt="Vista previa" style="display:none">
        <?php endif; ?>
      </div>
      <div class="mt-2 d-flex justify-content-center gap-2">
        <label class="btn-sunset-outline" for="avatar-input">
          <i class="bi bi-camera"></i> <?= empty($user['avatar_path']) ? 'Agregar foto' : 'Cambiar foto' ?>
        </label>
        <button type="button" id="avatar-remove" class="btn-sunset-outline" style="display:none">
          <i class="bi bi-x-lg"></i> Quitar
        </button>
      </div>
      <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" class="visually-hidden">
      <small id="avatar-hint" class="text-muted d-block mt-1" style="display:none!important"></small>
    </div>

    <div class="mb-3">
      <label class="form-label fw-semibold">Biografía</label>
      <textarea class="form-control" name="bio" rows="3" maxlength="300"
                placeholder="Cuéntanos algo sobre ti…"><?= e($user['bio'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold">Ubicación</label>
      <input class="form-control" name="location" maxlength="100"
             value="<?= e($user['location'] ?? '') ?>" placeholder="Ciudad, País">
    </div>
    <div class="mb-3">
      <label class="form-label fw-semibold">Sitio web</label>
      <input class="form-control" name="website" maxlength="200" type="url"
             value="<?= e($user['website'] ?? '') ?>" placeholder="https://…">
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn-sunset-dark flex-fill"><i class="bi bi-check2"></i> Guardar</button>
      <a class="btn-sunset-outline" href="<?= e(url('profile')) ?>">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function () {
  const input   = document.getElementById('avatar-input');
  const preview = document.getElementById('avatar-preview');
  const icon    = document.getElementById('avatar-icon');
  const remove  = document.getElementById('avatar-remove');

  input.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 12 * 1024 * 1024) {
      alert('La foto pesa más de 12 MB. Elige otra.');
      this.value = '';
      return;
    }
    const url = URL.createObjectURL(file);
    preview.src = url;
    preview.style.display = 'block';
    if (icon) icon.style.display = 'none';
    remove.style.display = 'inline-flex';
  });

  remove.addEventListener('click', function () {
    if (preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
    preview.src = '';
    preview.style.display = 'none';
    if (icon) icon.style.display = 'block';
    input.value = '';
    remove.style.display = 'none';
  });
})();
</script>
