<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

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
