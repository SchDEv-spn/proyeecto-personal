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

// Reparar symlink de uploads si el autodeploy lo sobreescribió con directorio de git
$_uploadsDir    = __DIR__ . '/public/uploads';
$_uploadsTarget = dirname(dirname(dirname(__DIR__))) . '/uploads';
if (!is_link($_uploadsDir) && is_dir($_uploadsTarget)) {
    if (is_dir($_uploadsDir)) {
        foreach (scandir($_uploadsDir) as $_f) {
            if ($_f !== '.' && $_f !== '..') @unlink($_uploadsDir . '/' . $_f);
        }
        @rmdir($_uploadsDir);
    }
    @symlink($_uploadsTarget, $_uploadsDir);
}
unset($_uploadsDir, $_uploadsTarget, $_f);

// BASE_URL: vacío en producción (raíz), '/tienda_mvc' en local.
$_cfgFile = __DIR__ . '/app/config/config.php';
$_baseCfg = file_exists($_cfgFile) ? (require $_cfgFile) : [];
define('BASE_URL', rtrim(($_baseCfg['base_url'] ?? ''), '/'));
// Exportar claves del config como env vars (para código que usa getenv())
foreach (['telegram_bot_token' => 'TELEGRAM_BOT_TOKEN', 'telegram_chat_id' => 'TELEGRAM_CHAT_ID'] as $_k => $_v) {
    if (!empty($_baseCfg[$_k]) && !getenv($_v)) putenv($_v . '=' . $_baseCfg[$_k]);
}
unset($_cfgFile, $_baseCfg, $_k, $_v);

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
