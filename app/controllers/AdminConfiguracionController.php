<?php

/**
 * Tokens de integraciones (Facebook CAPI, TikTok Events API, Telegram)
 * guardados en app_settings — misma idea que el token de Dropi en
 * AdminProductosController, para no depender de editar tienda_config.php
 * a mano en el servidor cada vez.
 */
class AdminConfiguracionController extends Controller
{
    /** Únicas claves que este panel puede leer/escribir en app_settings. */
    private const CLAVES = ['fb_capi_token', 'tiktok_capi_token', 'telegram_bot_token', 'telegram_chat_id'];

    public function index()
    {
        $this->requireLogin();

        $settings = new AppSettings();
        $estado = [];
        foreach (self::CLAVES as $clave) {
            $estado[$clave] = $settings->hasKey($clave);
        }

        $this->view('admin/configuracion/index', [
            'estado' => $estado,
        ]);
    }

    /** Guarda un token individual en app_settings (AJAX). */
    public function guardarToken()
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $clave = trim((string)($_POST['clave'] ?? ''));
        $valor = trim((string)($_POST['valor'] ?? ''));

        if (!in_array($clave, self::CLAVES, true)) {
            echo json_encode(['ok' => false, 'error' => 'Clave no reconocida.']);
            return;
        }
        if ($valor === '') {
            echo json_encode(['ok' => false, 'error' => 'Ingresa un valor válido.']);
            return;
        }

        (new AppSettings())->set($clave, $valor);
        echo json_encode(['ok' => true]);
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/Auth/login");
            exit;
        }
    }
}
