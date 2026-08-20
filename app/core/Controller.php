<?php
class Controller {
    public function view($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.php";
    }

    /**
     * 404 con el diseño del sitio. Ofrece un enlace al primer producto activo
     * para que el visitante no quede en una vía muerta.
     */
    protected function notFound(string $mensaje = 'No encontramos la página que buscas.'): void {
        http_response_code(404);

        $volver = '';
        if (class_exists('Producto')) {
            $p = (new Producto())->obtenerPrimeroActivo();
            if ($p && !empty($p['slug'])) $volver = BASE_URL . '/producto/' . rawurlencode($p['slug']);
        }

        $this->view('errors/404', ['mensaje' => $mensaje, 'volver' => $volver]);
        exit;
    }

    protected function requireCsrf(): void {
        $token = trim((string)($_POST['csrf_token'] ?? ''));
        $valid = $_SESSION['csrf_token'] ?? '';
        if ($token === '' || !hash_equals($valid, $token)) {
            http_response_code(403);
            die('Acción no autorizada.');
        }
    }
}

function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}
