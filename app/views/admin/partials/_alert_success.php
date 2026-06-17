<?php if (!empty($success)): ?>
  <div class="admin-alert-success" role="status" aria-live="polite">
    <i class="fas fa-circle-check" aria-hidden="true"></i>
    <span><?= htmlspecialchars($success) ?></span>
  </div>
<?php endif; ?>
