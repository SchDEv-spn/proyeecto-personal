<?php
class Controller {
    public function view($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../views/{$view}.php";
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
