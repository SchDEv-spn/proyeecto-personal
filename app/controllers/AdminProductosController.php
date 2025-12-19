<?php

class AdminProductosController extends Controller
{
    private function requireLogin()
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: /tienda_mvc/Auth/login");
            exit;
        }
    }

    /** Genera un slug básico a partir del nombre */
    private function generarSlug(string $texto): string
    {
        // Intentamos quitar acentos (si iconv está disponible)
        if (function_exists('iconv')) {
            $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
        }

        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');

        if ($texto === '') {
            $texto = 'producto-' . time();
        }

        return $texto;
    }

    public function index()
    {
        $this->requireLogin();

        $productoModel = new Producto();
        $productos     = $productoModel->obtenerTodos();

        $success = '';
        if (!empty($_SESSION['admin_productos_success'])) {
            $success = $_SESSION['admin_productos_success'];
            unset($_SESSION['admin_productos_success']);
        }

        $this->view('admin/productos/index', [
            'productos' => $productos,
            'success'   => $success,
        ]);
    }

    public function crear()
    {
        $this->requireLogin();

        $old = [
            'nombre'           => '',
            'slug'             => '',
            'precio_venta'     => '',
            'precio_proveedor' => '',
            'activo'           => 1,
        ];

        $this->view('admin/productos/crear', [
            'errores' => [],
            'old'     => $old,
        ]);
    }

    public function guardarNuevo()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/AdminProductos/index");
            exit;
        }

        $nombre           = trim($_POST['nombre']           ?? '');
        $slugInput        = trim($_POST['slug']             ?? '');
        $precioVenta      = (float)($_POST['precio_venta']  ?? 0);
        $precioProveedor  = (float)($_POST['precio_proveedor'] ?? 0);
        $activo           = isset($_POST['activo']) && $_POST['activo'] == '1' ? 1 : 0;

        $slug = $slugInput !== '' ? $slugInput : $this->generarSlug($nombre);

        $errores = [];

        if ($nombre === '') {
            $errores[] = "El nombre es obligatorio.";
        }
        if ($precioVenta <= 0) {
            $errores[] = "El precio de venta debe ser mayor a 0.";
        }
        if ($precioProveedor < 0) {
            $errores[] = "El precio del proveedor no puede ser negativo.";
        }

        $old = [
            'nombre'           => $nombre,
            'slug'             => $slugInput,
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'activo'           => $activo,
        ];

        if (!empty($errores)) {
            $this->view('admin/productos/crear', [
                'errores' => $errores,
                'old'     => $old,
            ]);
            return;
        }

        // Manejo de imagen principal
        $imagenPrincipal = null;

        $basePath  = dirname(__DIR__, 2); // /ruta/a/tienda_mvc
        $uploadDir = $basePath . '/public/uploads/productos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (
            isset($_FILES['imagen_principal_file']) &&
            $_FILES['imagen_principal_file']['error'] === UPLOAD_ERR_OK &&
            is_uploaded_file($_FILES['imagen_principal_file']['tmp_name'])
        ) {
            $tmpName  = $_FILES['imagen_principal_file']['tmp_name'];
            $origName = $_FILES['imagen_principal_file']['name'];

            $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $newName = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

            $destPath = $uploadDir . $newName;
            if (move_uploaded_file($tmpName, $destPath)) {
                $imagenPrincipal = '/tienda_mvc/public/uploads/productos/' . $newName;
            }
        }

        $productoModel = new Producto();
        $productoModel->crear([
            'nombre'           => $nombre,
            'slug'             => $slug,
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'imagen_principal' => $imagenPrincipal,
            'activo'           => $activo,
        ]);

        $_SESSION['admin_productos_success'] = "Producto creado correctamente.";
        header("Location: /tienda_mvc/AdminProductos/index");
        exit;
    }

    public function editar()
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header("Location: /tienda_mvc/AdminProductos/index");
            exit;
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorId($id);

        if (!$producto) {
            $_SESSION['admin_productos_success'] = "El producto no existe.";
            header("Location: /tienda_mvc/AdminProductos/index");
            exit;
        }

        $old = [
            'id'               => $producto['id'],
            'nombre'           => $producto['nombre'],
            'slug'             => $producto['slug'] ?? '',
            'precio_venta'     => $producto['precio_venta'],
            'precio_proveedor' => $producto['precio_proveedor'],
            'activo'           => $producto['activo'] ?? 1,
            'imagen_principal' => $producto['imagen_principal'] ?? '',
        ];

        $this->view('admin/productos/editar', [
            'producto' => $producto,
            'errores'  => [],
            'old'      => $old,
        ]);
    }

    public function actualizar()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/AdminProductos/index");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            header("Location: /tienda_mvc/AdminProductos/index");
            exit;
        }

        $productoModel      = new Producto();
        $productoExistente  = $productoModel->obtenerPorId($id);

        if (!$productoExistente) {
            $_SESSION['admin_productos_success'] = "El producto no existe.";
            header("Location: /tienda_mvc/AdminProductos/index");
            exit;
        }

        $nombre           = trim($_POST['nombre']           ?? '');
        $slugInput        = trim($_POST['slug']             ?? '');
        $precioVenta      = (float)($_POST['precio_venta']  ?? 0);
        $precioProveedor  = (float)($_POST['precio_proveedor'] ?? 0);
        $activo           = isset($_POST['activo']) && $_POST['activo'] == '1' ? 1 : 0;
        $imagenPrincipal  = $_POST['imagen_principal_actual'] ?? ($productoExistente['imagen_principal'] ?? null);

        // Si el slug viene vacío, lo generamos a partir del nombre
        $slug = $slugInput !== '' ? $slugInput : ($productoExistente['slug'] ?? $this->generarSlug($nombre));

        $errores = [];

        if ($nombre === '') {
            $errores[] = "El nombre es obligatorio.";
        }
        if ($precioVenta <= 0) {
            $errores[] = "El precio de venta debe ser mayor a 0.";
        }
        if ($precioProveedor < 0) {
            $errores[] = "El precio del proveedor no puede ser negativo.";
        }

        $old = [
            'id'               => $id,
            'nombre'           => $nombre,
            'slug'             => $slugInput,
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'activo'           => $activo,
            'imagen_principal' => $imagenPrincipal,
        ];

        if (!empty($errores)) {
            $this->view('admin/productos/editar', [
                'producto' => $productoExistente,
                'errores'  => $errores,
                'old'      => $old,
            ]);
            return;
        }

        // Manejo de imagen (si suben una nueva, reemplaza a la anterior)
        $basePath  = dirname(__DIR__, 2);
        $uploadDir = $basePath . '/public/uploads/productos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (
            isset($_FILES['imagen_principal_file']) &&
            $_FILES['imagen_principal_file']['error'] === UPLOAD_ERR_OK &&
            is_uploaded_file($_FILES['imagen_principal_file']['tmp_name'])
        ) {
            $tmpName  = $_FILES['imagen_principal_file']['tmp_name'];
            $origName = $_FILES['imagen_principal_file']['name'];

            $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $newName = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

            $destPath = $uploadDir . $newName;
            if (move_uploaded_file($tmpName, $destPath)) {
                $imagenPrincipal = '/tienda_mvc/public/uploads/productos/' . $newName;
            }
        }

        $productoModel->actualizar($id, [
            'nombre'           => $nombre,
            'slug'             => $slug,
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'imagen_principal' => $imagenPrincipal,
            'activo'           => $activo,
        ]);

        $_SESSION['admin_productos_success'] = "Producto actualizado correctamente.";

        header("Location: /tienda_mvc/AdminProductos/index");
        exit;
    }
}
