<?php
$cfg = require __DIR__ . '/../app/config/config.php';
$base = rtrim($cfg['base_url'] ?? '', '/');

header('Content-Type: application/manifest+json');
echo json_encode([
    'name'             => 'Panel Admin',
    'short_name'       => 'Admin',
    'description'      => 'Panel de gestión de pedidos y productos',
    'start_url'        => $base . '/AdminPedidos/index',
    'display'          => 'standalone',
    'orientation'      => 'portrait',
    'background_color' => '#080A0E',
    'theme_color'      => '#C9A84C',
    'icons'            => [
        [
            'src'     => $base . '/public/icons/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src'     => $base . '/public/icons/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES);
