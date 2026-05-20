<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Cargar variables del .env
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        putenv(trim($_k) . '=' . trim($_v));
    }
}
unset($_envFile, $_line, $_k, $_v);

session_start();

// BASE_URL: vacío en producción (raíz), '/tienda_mvc' en local.
// Definido en app/config/config.php como 'base_url'.
$_baseCfg = require __DIR__ . '/app/config/config.php';
define('BASE_URL', rtrim($_baseCfg['base_url'] ?? '', '/'));
unset($_baseCfg);

require_once __DIR__ . '/app/core/Controller.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/core/Database.php';

spl_autoload_register(function ($class) {
    if (file_exists(__DIR__ . "/app/controllers/{$class}.php")) {
        require_once __DIR__ . "/app/controllers/{$class}.php";
    } elseif (file_exists(__DIR__ . "/app/models/{$class}.php")) {
        require_once __DIR__ . "/app/models/{$class}.php";
    }
});

$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$urlParts = explode('/', $url);

// --- RUTA ESPECIAL: /producto/{slug} ---
if (!empty($urlParts[0]) && $urlParts[0] === 'producto' && !empty($urlParts[1])) {
    $controllerName = 'LandingController';
    $method         = 'verPorSlug';
    $params         = [$urlParts[1]];
} else {
    // Controller por defecto: LandingController@index
    $controllerName = !empty($urlParts[0]) ? ucfirst($urlParts[0]) . 'Controller' : 'LandingController';
    $method         = $urlParts[1] ?? 'index';
    $params         = array_slice($urlParts, 2);

    if (!class_exists($controllerName)) {
        $controllerName = 'LandingController';
        $method         = 'index';
        $params         = [];
    }
}

$controller = new $controllerName();

if (!method_exists($controller, $method)) {
    $method = 'index';
}

call_user_func_array([$controller, $method], $params);
