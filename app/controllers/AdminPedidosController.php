<?php

class AdminPedidosController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $pedidoModel = new Pedido();
        $pedidos = $pedidoModel->obtenerTodos(300);

        $totalPedidos    = count($pedidos);
        $totalUtilidad   = 0;
        $totalVenta      = 0;
        $totalProveedor  = 0;
        $pedidosNuevos   = 0;

        foreach ($pedidos as $p) {
            $cantidad = (int)($p['cantidad_total'] ?? 1);
            if ($cantidad < 1) $cantidad = 1;

            // Totales correctos (fallback por compatibilidad)
            $precioTotal   = isset($p['precio_total']) ? (float)$p['precio_total'] : ((float)($p['precio_venta'] ?? 0) * $cantidad);
            $utilidadTotal = isset($p['utilidad_total']) ? (float)$p['utilidad_total'] : ((float)($p['utilidad'] ?? 0) * $cantidad);

            $totalVenta     += $precioTotal;
            $totalUtilidad  += $utilidadTotal;

            // Costo proveedor total (sin envío porque no está en pedidos)
            $totalProveedor += ((float)($p['precio_proveedor'] ?? 0) * $cantidad);

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

        if (isset($_GET['partial']) && $_GET['partial'] == '1') {
            require __DIR__ . '/../views/admin/pedidos/_detalle_modal.php';
            return;
        }

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
            if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
                return;
            }
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

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

        $pedidoModel = new Pedido();
        $pedidoModel->actualizarEstado($id, $estado);

        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'id' => $id,
                'estado' => $estado
            ]);
            return;
        }

        header("Location: /tienda_mvc/AdminPedidos/index");
        exit;
    }

    public function contadores()
    {
        $this->requireLogin();

        $pedidoModel = new Pedido();
        $pedidos = $pedidoModel->obtenerTodos(1000);

        $pedidosNuevos = 0;
        foreach ($pedidos as $p) {
            if (($p['estado'] ?? '') === 'nuevo') {
                $pedidosNuevos++;
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['pedidos_nuevos' => $pedidosNuevos]);
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
