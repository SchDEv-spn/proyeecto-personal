<?php

class AdminPedidosController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $pedidoModel = new Pedido();
        $pedidos = $pedidoModel->obtenerTodos(300); // puedes ajustar el límite

        // Métricas para el dashboard
        $totalPedidos    = count($pedidos);
        $totalUtilidad   = 0;
        $totalVenta      = 0;
        $totalProveedor  = 0;
        $pedidosNuevos   = 0;

        foreach ($pedidos as $p) {
            $totalUtilidad  += (float)($p['utilidad'] ?? 0);
            $totalVenta     += (float)($p['precio_venta'] ?? 0);
            $totalProveedor += (float)($p['precio_proveedor'] ?? 0);

            if (($p['estado'] ?? '') === 'nuevo') {
                $pedidosNuevos++;
            }
        }

        $this->view('admin/pedidos/index', [
            'pedidos'          => $pedidos,
            'total_pedidos'    => $totalPedidos,
            'total_utilidad'   => $totalUtilidad,
            'total_venta'      => $totalVenta,
            'total_proveedor'  => $totalProveedor,
            'pedidos_nuevos'   => $pedidosNuevos,
        ]);
    }

    public function detalle()
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $pedidoModel = new Pedido();
        $pedido = $pedidoModel->obtenerPorId($id);

        if (!$pedido) {
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        // ✅ Si viene partial=1, devolvemos SOLO el HTML del modal (sin layout)
        if (isset($_GET['partial']) && $_GET['partial'] == '1') {
            // OJO: ajusta la ruta si tu estructura difiere
            require __DIR__ . '/../views/admin/pedidos/_detalle_modal.php';
            return;
        }

        // Vista normal completa (fallback)
        $this->view('admin/pedidos/detalle', [
            'pedido' => $pedido,
        ]);
    }

    public function cambiarEstado()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $id     = (int)($_POST['id'] ?? 0);
        $estado = trim($_POST['estado'] ?? '');

        if ($id <= 0 || $estado === '') {
            // ✅ Si es AJAX, respondemos JSON de error
            if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
                return;
            }

            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $pedidoModel = new Pedido();

        // (Opcional) Validar estados permitidos en servidor
        $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'entregado', 'cancelado'];
        if (!in_array($estado, $estadosPosibles, true)) {
            if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Estado no permitido']);
                return;
            }
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $pedidoModel->actualizarEstado($id, $estado);

        // ✅ Si viene ajax=1, devolvemos JSON y NO redirigimos
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'id' => $id,
                'estado' => $estado
            ]);
            return;
        }

        // Comportamiento normal
        header("Location: /tienda_mvc/AdminPedidos/index");
        exit;
    }

    // EN AdminPedidosController.php - Agrega esto después de public function detalle()
    public function contadores()
    {
        $this->requireLogin();

        $pedidoModel = new Pedido();
        $pedidos = $pedidoModel->obtenerTodos(1000);

        // SOLO contar pedidos nuevos
        $pedidosNuevos = 0;
        foreach ($pedidos as $p) {
            if (($p['estado'] ?? '') === 'nuevo') {
                $pedidosNuevos++;
            }
        }

        $data = [
            'pedidos_nuevos' => $pedidosNuevos
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    private function requireLogin()
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: /tienda_mvc/Auth/login");
            exit;
        }
    }
}
