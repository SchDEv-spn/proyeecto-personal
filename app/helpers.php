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
 * Clave para comparar un nombre de color escrito en dos sitios distintos: los
 * colores del producto (producto_colores, se escriben al crear el producto) y las
 * "Variantes de color" del editor de la landing (nombre + tono + fotos). Se
 * ignoran tildes, mayúsculas y signos: "Café Miel" = "cafe-miel".
 */
function clave_color(string $nombre): string
{
    $s = mb_strtolower(trim($nombre), 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return (string)preg_replace('/[^a-z0-9]+/', '', $s);
}

/**
 * Claves con las que un color del producto puede emparejarse con una variante
 * del editor: la del nombre completo y, si es una etiqueta compuesta como las
 * que arma Dropi ("Negro / M", "Rojo / Talla 38"), la de cada parte — así la
 * foto de "Negro" sirve a "Negro / M" y a "Negro / L". El nombre completo va
 * primero para que un emparejamiento exacto gane a uno parcial.
 *
 * @return string[]
 */
function claves_color(string $color): array
{
    $claves = [clave_color($color)];
    foreach (preg_split('/\s*\/\s*/u', $color) ?: [] as $parte) {
        $claves[] = clave_color($parte);
    }
    return array_values(array_unique(array_filter($claves, fn($k) => $k !== '')));
}

/**
 * Reduce una imagen subida para que no pese de más en un celular con datos
 * limitados: lado mayor de $lado px y recompresión. Devuelve true si dejó la
 * versión reducida en $destino; false si no hizo nada (el servidor no tiene GD,
 * formato que no sabe reducir, imagen ya liviana, o cualquier error) y entonces
 * quien llama guarda el archivo original tal cual: nunca es motivo para
 * rechazar la foto.
 *
 * Las fotos de celular traen la orientación en el EXIF y el navegador la aplica
 * al mostrarlas; al recomprimir ese dato se pierde, así que se gira la imagen
 * antes. Si no hay forma de leer el EXIF, el JPEG se deja como vino.
 */
function reducir_imagen(string $origen, string $destino, string $ext, int $lado = 900): bool
{
    $ext = strtolower($ext);
    if (!function_exists('imagecreatetruecolor')) return false;

    $info = @getimagesize($origen);
    if ($info === false) return false;
    $ancho = (int)$info[0];
    $alto  = (int)$info[1];
    if ($ancho < 1 || $alto < 1 || $ancho * $alto > 40000000) return false; // evita agotar la memoria

    $pesoOriginal = (int)@filesize($origen);
    if (max($ancho, $alto) <= $lado && $pesoOriginal <= 350 * 1024) return false; // ya es liviana

    if ($ext === 'jpg' || $ext === 'jpeg') {
        if (!function_exists('imagecreatefromjpeg') || !function_exists('exif_read_data')) return false;
        $img = @imagecreatefromjpeg($origen);
    } elseif ($ext === 'png') {
        if (!function_exists('imagecreatefrompng')) return false;
        $img = @imagecreatefrompng($origen);
    } elseif ($ext === 'webp') {
        if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) return false;
        $img = @imagecreatefromwebp($origen);
    } else {
        return false;
    }
    if (!$img) return false;

    if ($ext === 'jpg' || $ext === 'jpeg') {
        $exif = @exif_read_data($origen);
        $orientacion = (int)(is_array($exif) ? ($exif['Orientation'] ?? 1) : 1);
        if (in_array($orientacion, [2, 4, 5, 7], true)) return false; // espejadas: no se tocan
        $giro = [3 => 180, 6 => -90, 8 => 90][$orientacion] ?? 0;
        if ($giro !== 0) {
            $girada = @imagerotate($img, $giro, 0);
            if ($girada) $img = $girada;
        }
        $ancho = imagesx($img);
        $alto  = imagesy($img);
    }

    $escala = min(1, $lado / max($ancho, $alto));
    $nAncho = max(1, (int)round($ancho * $escala));
    $nAlto  = max(1, (int)round($alto * $escala));

    $nuevo = imagecreatetruecolor($nAncho, $nAlto);
    if ($ext === 'png' || $ext === 'webp') {
        imagealphablending($nuevo, false);
        imagesavealpha($nuevo, true);
        imagefilledrectangle($nuevo, 0, 0, $nAncho, $nAlto, imagecolorallocatealpha($nuevo, 0, 0, 0, 127));
    }
    imagecopyresampled($nuevo, $img, 0, 0, 0, 0, $nAncho, $nAlto, $ancho, $alto);

    if ($ext === 'png')       $ok = imagepng($nuevo, $destino, 6);
    elseif ($ext === 'webp')  $ok = imagewebp($nuevo, $destino, 82);
    else                      $ok = imagejpeg($nuevo, $destino, 82);

    if (!$ok || !is_file($destino) || (int)filesize($destino) >= $pesoOriginal) {
        if (is_file($destino)) @unlink($destino);
        return false;
    }
    return true;
}

/**
 * Guarda una foto subida en $dir (con barra final) como <prefijo>_<hora>_<n>.<ext>,
 * reducida si el servidor puede. Devuelve el nombre del archivo o null si no se
 * pudo guardar. $mover existe para poder probarla fuera de una subida real.
 */
function guardar_foto_subida(string $tmp, string $dir, string $prefijo, string $ext, int $lado = 900, ?callable $mover = null): ?string
{
    $ext     = strtolower($ext);
    $nombre  = $prefijo . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $destino = $dir . $nombre;

    if (reducir_imagen($tmp, $destino, $ext, $lado)) return $nombre;

    $mover = $mover ?: 'move_uploaded_file';
    return $mover($tmp, $destino) ? $nombre : null;
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

/**
 * Etiqueta "⚠ Devolvió 1 vez · $15.000" para un cliente con devoluciones anteriores (ver
 * DropiOrden::riesgoPorTelefonos). Vacía si no hay nada que avisar. Una devolución sale en
 * ámbar; los reincidentes y los marcados a mano, en rojo. Ya escapada: lista para imprimir.
 */
function riesgo_cliente_chip(?array $riesgo): string
{
    if (!$riesgo) return '';

    $color = ($riesgo['nivel'] ?? '') === 'devolvio' ? 'var(--gold)' : 'var(--red)';
    return '<small class="dt-cell-sub riesgo-cliente" style="display:block;color:' . $color . ';font-weight:700">'
        . '<i class="fas fa-triangle-exclamation" aria-hidden="true"></i> '
        . htmlspecialchars(DropiOrden::riesgoEtiqueta($riesgo), ENT_QUOTES, 'UTF-8') . '</small>';
}
