<?php

/**
 * Conexión OAuth con el MCP de Dropi (ver DropiMcpAuth) y disparo manual del refresco
 * rápido de guías (DropiMcpSync) — el mismo que corre solo por cron_dropi_mcp_sync.php.
 */
class AdminDropiMcpController extends Controller
{
    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/Auth/login");
            exit;
        }
    }

    /**
     * BASE_URL es solo el prefijo de ruta (vacío en producción, "/tienda_mvc" en
     * local — ver index.php) — Dropi exige un redirect_uri absoluto (con esquema y
     * host), igual que cualquier servidor OAuth. Se arma con el host de la propia
     * petición, mismo criterio que es_entorno_local().
     */
    private function redirectUri(): string
    {
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        return $esquema . '://' . $host . BASE_URL . '/AdminDropiMcp/callback';
    }

    private function volver(): void
    {
        header('Location: ' . BASE_URL . '/AdminPlantillasWa/index');
        exit;
    }

    public function conectar(): void
    {
        $this->requireLogin();

        $r = (new DropiMcpAuth())->urlAutorizacion($this->redirectUri());
        if (!$r['ok']) {
            $_SESSION['admin_dropi_mcp_error'] = $r['error'];
            $this->volver();
        }

        header('Location: ' . $r['url']);
        exit;
    }

    public function callback(): void
    {
        $this->requireLogin();

        if (!empty($_GET['error'])) {
            $_SESSION['admin_dropi_mcp_error'] = 'Dropi no autorizó la conexión: ' . (string)$_GET['error'];
            $this->volver();
        }

        $code = (string)($_GET['code'] ?? '');
        $state = (string)($_GET['state'] ?? '');
        if ($code === '') {
            $_SESSION['admin_dropi_mcp_error'] = 'Dropi no devolvió un código de autorización.';
            $this->volver();
        }

        $r = (new DropiMcpAuth())->procesarCallback($code, $state, $this->redirectUri());
        if (!$r['ok']) {
            $_SESSION['admin_dropi_mcp_error'] = $r['error'];
        } else {
            $_SESSION['admin_dropi_mcp_success'] = 'Dropi conectado — el refresco automático de guías ya puede correr.';
        }
        $this->volver();
    }

    public function desconectar(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        (new DropiMcpAuth())->desconectar();
        $_SESSION['admin_dropi_mcp_success'] = 'Se desconectó el MCP de Dropi.';
        $this->volver();
    }

    /** Dispara un refresco ahora mismo (botón "Actualizar guías ahora"). Mismo código que usa el cron. */
    public function probar(): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        $r = (new DropiMcpSync())->ejecutar();
        echo json_encode($r);
    }
}
