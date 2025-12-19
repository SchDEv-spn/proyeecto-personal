<?php

class LandingController extends Controller
{
    /**
     * Landing por producto_id (/?producto_id=2 o ?id=2).
     * Si no llega, usa 1 por defecto.
     */
    public function index()
    {
        $productoId = (int)($_GET['producto_id'] ?? ($_GET['id'] ?? 1));
        if ($productoId <= 0) {
            $productoId = 1;
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorId($productoId);

        // Si no existe ese producto, intenta con 1 o 404
        if (!$producto) {
            $productoId = 1;
            $producto   = $productoModel->obtenerPorId($productoId);
            if (!$producto) {
                header("HTTP/1.0 404 Not Found");
                echo "Producto no encontrado";
                exit;
            }
        }

        // Mensaje de éxito simple (cuando se viene de enviarPedido)
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);

        // Config de landing por producto
        $configModel = new LandingConfig();
        $config      = $configModel->obtenerPorProducto($productoId) ?? [];

        // Render sin errores ni old (GET limpio)
        $this->view('landing/index', [
            'producto' => $producto,
            'errores'  => [],
            'old'      => [],
            'success'  => $success,
            'config'   => $config,
        ]);
    }

    /**
     * Procesa el formulario "Realiza tu pedido".
     */
    public function enviarPedido()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/Landing/index");
            exit;
        }

        $productoId = (int)($_POST['producto_id'] ?? 1);
        if ($productoId <= 0) {
            $productoId = 1;
        }

        // Campos del formulario
        $nombre       = trim($_POST['nombre']       ?? '');
        $apellidos    = trim($_POST['apellidos']    ?? '');
        $telefono     = trim($_POST['telefono']     ?? '');
        $color        = trim($_POST['color']        ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $municipio    = trim($_POST['municipio']    ?? '');
        $tipoEntrega  = trim($_POST['tipo_entrega'] ?? '');
        $direccion    = trim($_POST['direccion']    ?? '');

        $old = [
            'nombre'       => $nombre,
            'apellidos'    => $apellidos,
            'telefono'     => $telefono,
            'color'        => $color,
            'departamento' => $departamento,
            'municipio'    => $municipio,
            'tipo_entrega' => $tipoEntrega,
            'direccion'    => $direccion,
        ];

        // Validaciones
        $errores = [];

        if ($nombre === '')        $errores[] = "El nombre es obligatorio.";
        if ($apellidos === '')     $errores[] = "Los apellidos son obligatorios.";
        if ($telefono === '')      $errores[] = "El número de WhatsApp es obligatorio.";
        if ($departamento === '')  $errores[] = "Selecciona un departamento.";
        if ($municipio === '')     $errores[] = "Selecciona un municipio.";
        if ($tipoEntrega === '')   $errores[] = "Selecciona cómo quieres recibir tu pedido.";
        if ($tipoEntrega === 'domicilio' && $direccion === '') {
            $errores[] = "La dirección es obligatoria para envío a domicilio.";
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorId($productoId);

        $configModel  = new LandingConfig();
        $config       = $configModel->obtenerPorProducto($productoId) ?? [];

        // Si hay errores, volvemos a la landing con los mensajes
        if (!empty($errores)) {
            $this->view('landing/index', [
                'producto' => $producto,
                'errores'  => $errores,
                'old'      => $old,
                'success'  => '',
                'config'   => $config,
            ]);
            return;
        }

        // Guardar pedido
        $pedidoModel = new Pedido();

        $precioVenta     = (float)($producto['precio_venta']     ?? 0);
        $precioProveedor = (float)($producto['precio_proveedor'] ?? 0);
        $utilidad        = $precioVenta - $precioProveedor;

        $pedidoData = [
            'producto_id'      => $productoId,
            'nombre'           => $nombre,
            'apellidos'        => $apellidos,
            'telefono'         => $telefono,
            'color'            => $color,
            'departamento'     => $departamento,
            'municipio'        => $municipio,
            'tipo_entrega'     => $tipoEntrega,
            'direccion'        => ($tipoEntrega === 'domicilio') ? $direccion : null,
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'utilidad'         => $utilidad,
            'estado'           => 'nuevo',
        ];

        $pedidoModel->crear($pedidoData);

        // Flash de éxito
        $_SESSION['success'] = "Tu pedido se ha registrado correctamente. En breve un asesor te contactará por WhatsApp.";

        // Redirigir a la landing del mismo producto (versión por ID)
        header("Location: /tienda_mvc/Landing/index?producto_id=" . $productoId);
        exit;
    }

    /**
     * Landing por slug: /producto/{slug}
     */
    public function verPorSlug($slug)
    {
        $slug = trim($slug);
        if ($slug === '') {
            header("Location: /tienda_mvc/");
            exit;
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorSlug($slug);

        if (!$producto) {
            header("HTTP/1.0 404 Not Found");
            echo "Producto no encontrado";
            exit;
        }

        $productoId = (int)$producto['id'];

        $configModel = new LandingConfig();
        $config      = $configModel->obtenerPorProducto($productoId) ?? [];

        // Reutilizamos el mismo mensaje de éxito que en index()
        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);

        // En este flujo normalmente no usamos errores/old por sesión,
        // porque en enviarPedido devolvemos directamente la vista con errores
        $this->view('landing/index', [
            'producto' => $producto,
            'config'   => $config,
            'success'  => $success,
            'errores'  => [],
            'old'      => [],
        ]);
    }
}
