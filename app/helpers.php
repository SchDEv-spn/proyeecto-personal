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

/**
 * ¿La ruta apunta a un vídeo? Decidido por la extensión del archivo, no por el
 * campo de tipo guardado: los dos se desincronizan al cambiar de imagen a vídeo
 * y entonces un .mp4 termina dentro de un <img> que nunca carga.
 */
function es_video(?string $path): bool
{
    if (!$path) return false;
    $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?? $path, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'mov', 'webm', 'ogg', 'm4v'], true);
}

/**
 * Id del Facebook Pixel de la landing — única fuente de verdad.
 * Vive aquí y no repetido en la vista y en el controlador (Conversions API
 * necesita el mismo id que el pixel del navegador para poder deduplicar).
 * Pendiente moverlo a landing_config por producto (ver AUDITORIA.md M8).
 */
function fb_pixel_id(): string
{
    return '1248724310406936';
}

/**
 * Id del TikTok Pixel de la landing — misma idea que fb_pixel_id().
 */
function tiktok_pixel_id(): string
{
    return 'DA6HCRBC77U98E0UGEQG';
}

/**
 * URL de un asset estático (CSS/JS) con ?v=<filemtime> para romper la
 * caché en cada deploy. El .htaccess manda Cache-Control: max-age de un
 * año solo para peticiones CON ?v= — sin la versión, ese año de caché
 * dejaría a un visitante recurrente con el CSS/JS de antes del último
 * deploy, ya que la URL no cambió.
 */
function asset_url(string $relativePath): string
{
    $relativePath = ltrim($relativePath, '/');
    $fsPath       = __DIR__ . '/../' . $relativePath;
    $v            = is_file($fsPath) ? filemtime($fsPath) : time();
    return BASE_URL . '/' . $relativePath . '?v=' . $v;
}

/**
 * Precio total con descuento multicantidad. Única fuente de verdad: antes
 * existían dos copias (LandingController y Pedido) con reglas distintas — la
 * del modelo tenía 15% y 20% escritos a mano e ignoraba la configuración
 * del producto, así que daba precios incorrectos en silencio.
 *
 * 1ra unidad sin descuento · 2da al -d2% · 3ra en adelante al -d3%.
 */
function total_con_descuento(int $cantidad, float $precioUnit, int $d2, int $d3, int $activo = 1): float
{
    if ($cantidad <= 0) return 0.0;

    $d2 = max(0, min(100, $d2));
    $d3 = max(0, min(100, $d3));

    if ((int)$activo !== 1) {
        return $precioUnit * $cantidad;
    }

    if ($cantidad === 1) return $precioUnit;

    $total = $precioUnit;                                  // 1ra sin descuento
    $total += $precioUnit * (1 - ($d2 / 100));             // 2da

    if ($cantidad >= 3) {
        $total += ($cantidad - 2) * ($precioUnit * (1 - ($d3 / 100)));
    }

    return $total;
}

/**
 * ¿Estamos en un entorno de desarrollo (XAMPP local, red interna, dominio .test)?
 *
 * Se usa para no disparar analytics (Clarity, Pixel) desde local: hasta ahora
 * las pruebas en localhost se mezclaban con el tráfico real en el mismo
 * proyecto de Clarity y sesgaban todos los promedios.
 *
 * APP_ENV en .env manda cuando está definido; si no (producción no lleva .env,
 * está en .gitignore), se decide por el host de la petición.
 */
function es_entorno_local(): bool
{
    $env = getenv('APP_ENV');
    if ($env !== false && $env !== '') {
        return strtolower(trim($env)) !== 'production';
    }

    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = explode(':', $host)[0];

    if ($host === '' || $host === 'localhost' || $host === '127.0.0.1' || $host === '::1') return true;
    if (str_ends_with($host, '.local') || str_ends_with($host, '.test')) return true;
    if (str_starts_with($host, '192.168.') || str_starts_with($host, '10.')) return true;

    return false;
}
