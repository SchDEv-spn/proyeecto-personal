<?php
$f = $_GET['f'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $f) || str_contains($f, '..')) {
    http_response_code(404); exit;
}

// ~/uploads/ — 3 niveles arriba de DOCUMENT_ROOT en Hostinger
$base = rtrim(dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))), '/') . '/uploads/';

if (!is_dir($base)) {
    // Fallback local: ruta relativa al directorio de este script
    $base = __DIR__ . '/public/uploads/';
}

$realBase = realpath($base);
$path     = realpath($base . $f);

if (!$path || !$realBase || !str_starts_with($path, $realBase) || !is_file($path)) {
    http_response_code(404); exit;
}

$size = filesize($path);
$mime = mime_content_type($path) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=2592000');
header('Accept-Ranges: bytes');

// WKWebView (navegador interno de Facebook/Instagram en iPhone) exige
// soporte de rangos para reproducir <video>: sin 206 + Accept-Ranges,
// no reproduce y muestra un recuadro negro.
$start = 0;
$end   = $size - 1;
$status = 200;

if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m)) {
    if ($m[1] === '' && $m[2] !== '') {
        // Rango-sufijo: los últimos N bytes
        $end   = $size - 1;
        $start = max(0, $size - (int)$m[2]);
    } else {
        $start = (int)$m[1];
        $end   = ($m[2] !== '') ? min((int)$m[2], $size - 1) : $size - 1;
    }

    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }

    $status = 206;
}

$length = $end - $start + 1;

if ($status === 206) {
    http_response_code(206);
    header("Content-Range: bytes {$start}-{$end}/{$size}");
}
header('Content-Length: ' . $length);

if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['HEAD'], true)) {
    exit;
}

$fp = fopen($path, 'rb');
if ($fp === false) { http_response_code(500); exit; }

fseek($fp, $start);
$bytesLeft = $length;
while ($bytesLeft > 0 && !feof($fp)) {
    $chunk = fread($fp, min(8192, $bytesLeft));
    if ($chunk === false) break;
    echo $chunk;
    $bytesLeft -= strlen($chunk);
}
fclose($fp);
