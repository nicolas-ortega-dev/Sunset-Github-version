<?php /** @var string $mode @var array $errors @var array $old */
$isRegister = $mode === 'register'; ?>
<main class="auth-page">
  <section class="auth-brand">
    <?php require ROOT_PATH . '/views/partials/logo.php'; ?>
    <h1 class="auth-tagline">Conecta, descubre y debate sobre tus lecturas favoritas.</h1>
    <p class="auth-sub">Comparte tu feed literario, crea comunidades en torno a obras imprescindibles</p>
    <blockquote class="auth-quote">“Conectando lectores con sus próximas grandes historias.”</blockquote>
  </section>

  <section class="auth-panel">
    <nav class="auth-tabs" aria-label="Acceso">
      <a href="<?= e(url('auth', ['mode' => 'register'])) ?>" class="<?= $isRegister ? 'is-active' : '' ?>" <?= $isRegister ? 'aria-current="page"' : '' ?>>Registrarse</a>
      <a href="<?= e(url('auth', ['mode' => 'login'])) ?>" class="<?= !$isRegister ? 'is-active' : '' ?>" <?= !$isRegister ? 'aria-current="page"' : '' ?>>Iniciar sesión</a>
    </nav>

    <div class="auth-card">
      <?php if ($isRegister): ?>
        <h2>Únete a Sunset</h2>
        <p class="auth-card-sub">Crea tu cuenta gratuita en segundos.</p>

        <form action="<?= e(url('register')) ?>" method="post" novalidate>
          <?= \Sunset\Helpers\Csrf::field() ?>
          <div class="mb-3">
            <label for="username" class="form-label">Nombre de usuario</label>
            <input id="username" name="username" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['username'] ?? '') ?>" placeholder="Ej: nico_reader"
                   maxlength="30" autocomplete="username" required>
            <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username']) ?></div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input id="email" name="email" type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['email'] ?? '') ?>" placeholder="tu_correo@email.com"
                   maxlength="150" autocomplete="email" required>
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <input id="password" name="password" type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   placeholder="Mínimo 8 caracteres" minlength="8" maxlength="72" autocomplete="new-password" required>
            <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>
          <button type="submit" class="btn-sunset">Registrarse</button>
        </form>

      <?php else: ?>
        <h2>Bienvenido de vuelta</h2>
        <p class="auth-card-sub">Inicia sesión para seguir leyendo.</p>

        <?php if (isset($errors['general'])): ?>
          <div class="alert alert-danger" role="alert"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form action="<?= e(url('login')) ?>" method="post" novalidate>
          <?= \Sunset\Helpers\Csrf::field() ?>
          <div class="mb-3">
            <label for="login" class="form-label">Usuario o correo</label>
            <input id="login" name="login" class="form-control" value="<?= e($old['login'] ?? '') ?>"
                   maxlength="150" autocomplete="username" required>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <input id="password" name="password" type="password" class="form-control"
                   maxlength="72" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn-sunset">Iniciar sesión</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</main>
