<?php
/**
 * CRON — Refresco rápido de guías de Dropi vía MCP
 * =============================================================
 * Llama a DropiMcpSync::ejecutar(): consulta Dropi directo por su conector MCP, crea
 * las órdenes nuevas de los últimos 30 días y pone al día estatus/guía de las que ya
 * están, sin esperar el Excel. Pensado para correr cada 2-3 horas.
 *
 * Programar en el cron de Hostinger (hPanel → Avanzado → Cron Jobs):
 *   php /home/<usuario>/public_html/cron_dropi_mcp_sync.php
 *
 * No hace nada si Dropi (MCP) todavía no está conectado desde el panel
 * (Seguimiento → "Conectar actualización automática") — no es un error, solo
 * queda sin trabajo hasta que alguien lo conecte.
 *
 * Igual que seed_landing_qa.php: solo por línea de comandos, nunca por web (este
 * archivo vive en el webroot).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/app/helpers.php';
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

$resultado = (new DropiMcpSync())->ejecutar();

if (!$resultado['ok']) {
    fwrite(STDERR, '[dropi-mcp-sync] ' . $resultado['error'] . "\n");
    exit(1);
}

echo sprintf(
    "[dropi-mcp-sync] %d orden(es) revisadas — %d nueva(s), %d actualizada(s), %d sin cambios, %d omitida(s) (sin teléfono válido), %d pendiente(s) para la próxima.\n",
    $resultado['total'],
    $resultado['nuevas'],
    $resultado['actualizadas'],
    $resultado['iguales'],
    $resultado['omitidas'],
    $resultado['pendientes']
);
if ($resultado['sin_clasificar']) {
    echo '[dropi-mcp-sync] Estatus sin clasificar: ' . json_encode($resultado['sin_clasificar'], JSON_UNESCAPED_UNICODE) . "\n";
}
