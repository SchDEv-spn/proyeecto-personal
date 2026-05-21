<?php
$f = $_GET['f'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $f) || str_contains($f, '..')) {
    http_response_code(404); exit;
}

// ~/uploads/ — 3 niveles arriba de DOCUMENT_ROOT en Hostinger
$base = rtrim(dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))), '/') . '/uploads/';

if (!is_dir($base)) {
    // Fallback local: public/uploads/
    $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/uploads/';
}

$realBase = realpath($base);
$path     = realpath($base . $f);

if (!$path || !$realBase || !str_starts_with($path, $realBase) || !is_file($path)) {
    http_response_code(404); exit;
}

$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=2592000');
readfile($path);
