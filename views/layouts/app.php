<!doctype html>
<html lang="es">
<head>
  <?php require ROOT_PATH . '/views/partials/head.php'; ?>
  <meta name="csrf" content="<?= e(\Sunset\Helpers\Csrf::token()) ?>">
  <meta name="sunset-base" content="<?= e(base_url() . '/index.php') ?>">
</head>
<body class="app-body">
  <?php require ROOT_PATH . '/views/partials/navbar.php'; ?>
  <div class="container sunset-container">
    <?php foreach (\Sunset\Helpers\Flash::consume() as $f): ?>
      <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'danger' ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?= $content ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?php foreach (($scripts ?? []) as $script): ?>
    <?php $jsFile = ROOT_PATH . '/public/' . $script; ?>
    <script src="<?= e(asset($script) . '?v=' . (is_file($jsFile) ? filemtime($jsFile) : '1')) ?>" defer></script>
  <?php endforeach; ?>
</body>
</html>
