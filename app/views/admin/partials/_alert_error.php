<?php if (!empty($errores)): ?>
  <div class="admin-alert-error" role="alert" aria-live="polite">
    <div class="admin-alert-title">
      <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> Revisa estos campos
    </div>
    <ul>
      <?php foreach ($errores as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
