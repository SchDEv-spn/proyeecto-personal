<?php

/**
 * Renderiza un banner de éxito.
 * Devuelve '' si $message está vacío.
 */
function alert_success(string $message): string
{
    if ($message === '') return '';
    $msg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div class="admin-alert-success" role="status" aria-live="polite" id="admin-toast">
  <i class="fas fa-circle-check" aria-hidden="true"></i>
  <span>{$msg}</span>
</div>
<script>(function(){var t=document.getElementById('admin-toast');if(!t)return;setTimeout(function(){t.classList.add('is-hiding');setTimeout(function(){t.remove();},400);},3000);})();</script>
HTML;
}

/**
 * Renderiza un banner de error con lista de mensajes.
 * Devuelve '' si $errors está vacío.
 *
 * @param string[] $errors
 */
function alert_error(array $errors, string $title = 'Revisa estos campos'): string
{
    if (empty($errors)) return '';
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $items    = array_map(
        fn($e) => '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>',
        $errors
    );
    $list = implode("\n      ", $items);
    return <<<HTML
<div class="admin-alert-error" role="alert" aria-live="polite">
  <div class="admin-alert-title">
    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i> {$titleEsc}
  </div>
  <ul>
      {$list}
  </ul>
</div>
HTML;
}
