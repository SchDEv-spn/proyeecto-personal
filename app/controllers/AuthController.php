<?php

class AuthController extends Controller
{
    public function login()
    {
        // Si ya está logueado, lo mandamos al panel
        if (!empty($_SESSION['usuario_id'])) {
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $errores = $_SESSION['login_errores'] ?? [];
        $old     = $_SESSION['login_old']     ?? [];

        unset($_SESSION['login_errores'], $_SESSION['login_old']);

        $this->view('auth/login', [
            'errores' => $errores,
            'old'     => $old,
        ]);
    }

    public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/Auth/login");
            exit;
        }

        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        $errores = [];

        if ($email === '') {
            $errores[] = "El correo electrónico es obligatorio.";
        }
        if ($password === '') {
            $errores[] = "La contraseña es obligatoria.";
        }

        if (!empty($errores)) {
            $_SESSION['login_errores'] = $errores;
            $_SESSION['login_old'] = [
                'email' => $email,
            ];
            header("Location: /tienda_mvc/Auth/login");
            exit;
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorEmail($email);

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            $_SESSION['login_errores'] = ["Correo o contraseña incorrectos."];
            $_SESSION['login_old'] = [
                'email' => $email,
            ];
            header("Location: /tienda_mvc/Auth/login");
            exit;
        }

        // Login OK: guardamos datos mínimos en sesión
        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email'] = $usuario['email'];

        header("Location: /tienda_mvc/AdminPedidos/index");
        exit;
    }

    public function logout()
    {
        // Cerramos sesión actual
        $_SESSION = [];
        if (session_id() !== '' || isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();

        header("Location: /tienda_mvc/Auth/login");
        exit;
    }
}
