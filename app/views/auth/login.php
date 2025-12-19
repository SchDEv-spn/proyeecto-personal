<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Acceso al panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/tienda_mvc/public/css/login.css">
</head>

<body class="login-body">

<?php
$errores = $errores ?? [];
$old     = $old     ?? [];
?>

<main class="login-shell">
  <section class="login-card" aria-label="Formulario de inicio de sesión">

    <header class="login-header">
      <div class="login-brand">
        <span class="brand-pill">
          <span class="dot"></span>
          FEDORA ULTIMATE
        </span>
      </div>

      <h1>Iniciar sesión</h1>
      <p>Ingresa tus credenciales para acceder al panel de administración.</p>
    </header>

    <?php if (!empty($errores)): ?>
      <div class="admin-alert-error" role="alert" aria-live="polite">
        <ul>
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="/tienda_mvc/Auth/procesar" method="POST" class="login-form" autocomplete="on">

      <div class="admin-form-group">
        <label for="email">Correo electrónico</label>
        <div class="input-wrap">
          <span class="input-icon" aria-hidden="true">✉️</span>
          <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
            placeholder="correo@dominio.com"
            autocomplete="email"
            required
          >
        </div>
      </div>

      <div class="admin-form-group">
        <label for="password">Contraseña</label>
        <div class="input-wrap">
          <span class="input-icon" aria-hidden="true">🔒</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          >
        </div>
      </div>

      <div class="admin-form-actions">
        <button type="submit" class="btn-estado btn-estado--full">
          Entrar
        </button>
      </div>

      <div class="login-footer">
        <small>Acceso restringido. Si no tienes permisos, contacta al administrador.</small>
      </div>

    </form>
  </section>
</main>

</body>
</html>
