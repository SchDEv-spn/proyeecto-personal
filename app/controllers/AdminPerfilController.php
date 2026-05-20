<?php

class AdminPerfilController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $id = (int)$_SESSION['usuario_id'];
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorId($id);

        if (!$usuario) {
            header("Location: " . BASE_URL . "/Auth/logout");
            exit;
        }

        $flash = $_SESSION['perfil_flash'] ?? null;
        unset($_SESSION['perfil_flash']);

        $this->view('admin/perfil/index', [
            'usuario'        => $usuario,
            'flash'          => $flash,
            'usuarioNombre'  => $usuario['nombre'],
            'usuarioEmail'   => $usuario['email'],
        ]);
    }

    public function guardar()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminPerfil/index");
            exit;
        }

        $id     = (int)$_SESSION['usuario_id'];
        $accion = $_POST['accion'] ?? '';

        $usuarioModel = new Usuario();

        if ($accion === 'perfil') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email  = trim($_POST['email']  ?? '');

            if ($nombre === '' || $email === '') {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'Nombre y email son obligatorios.'];
                header("Location: " . BASE_URL . "/AdminPerfil/index");
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'El email no tiene un formato válido.'];
                header("Location: " . BASE_URL . "/AdminPerfil/index");
                exit;
            }

            $ok = $usuarioModel->actualizarPerfil($id, $nombre, $email);

            if ($ok) {
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_email']  = $email;
                $_SESSION['perfil_flash'] = ['tipo' => 'ok', 'msg' => 'Perfil actualizado correctamente.'];
            } else {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'No se pudo guardar. Intenta de nuevo.'];
            }

        } elseif ($accion === 'password') {
            $actual   = $_POST['password_actual']   ?? '';
            $nueva    = $_POST['password_nueva']    ?? '';
            $confirmar = $_POST['password_confirmar'] ?? '';

            if ($actual === '' || $nueva === '' || $confirmar === '') {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'Todos los campos de contraseña son obligatorios.'];
                header("Location: " . BASE_URL . "/AdminPerfil/index");
                exit;
            }

            if ($nueva !== $confirmar) {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'La nueva contraseña y la confirmación no coinciden.'];
                header("Location: " . BASE_URL . "/AdminPerfil/index");
                exit;
            }

            if (strlen($nueva) < 8) {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'La contraseña debe tener al menos 8 caracteres.'];
                header("Location: " . BASE_URL . "/AdminPerfil/index");
                exit;
            }

            $usuario = $usuarioModel->obtenerPorId($id);
            if (!$usuario || !password_verify($actual, $usuario['password_hash'])) {
                $_SESSION['perfil_flash'] = ['tipo' => 'error', 'msg' => 'La contraseña actual es incorrecta.'];
                header("Location: " . BASE_URL . "/AdminPerfil/index");
                exit;
            }

            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            $ok   = $usuarioModel->cambiarPassword($id, $hash);

            $_SESSION['perfil_flash'] = $ok
                ? ['tipo' => 'ok',    'msg' => 'Contraseña actualizada correctamente.']
                : ['tipo' => 'error', 'msg' => 'No se pudo cambiar la contraseña. Intenta de nuevo.'];
        }

        header("Location: " . BASE_URL . "/AdminPerfil/index");
        exit;
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/Auth/login");
            exit;
        }
    }
}
