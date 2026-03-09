<?php

class AdminPedidosController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        // ✅ Rango permitido: hoy | ayer | semana | mes
        $rango = $_GET['rango'] ?? 'mes';
        $permitidos = ['hoy', 'ayer', 'semana', 'mes'];
        if (!in_array($rango, $permitidos, true)) $rango = 'mes';

        // ✅ Fechas inicio/fin (America/Bogota) en formato SQL
        list($inicioObj, $finObj) = $this->calcularRangoFechas($rango);
        $inicioStr = $inicioObj->format('Y-m-d H:i:s');
        $finStr    = $finObj->format('Y-m-d H:i:s');

        $pedidoModel = new Pedido();

        // ✅ Ideal: filtrar desde el modelo/DB
        if (method_exists($pedidoModel, 'obtenerPorRango')) {
            $pedidos = $pedidoModel->obtenerPorRango($inicioStr, $finStr, 300);
        } else {
            // ✅ Fallback: filtrar en PHP
            $todos = $pedidoModel->obtenerTodos(2000);

            $inicioTs = $inicioObj->getTimestamp();
            $finTs    = $finObj->getTimestamp();

            $pedidos = array_values(array_filter($todos, function ($p) use ($inicioTs, $finTs) {
                $ts = strtotime($p['created_at'] ?? '');
                if (!$ts) return false;
                return ($ts >= $inicioTs && $ts < $finTs);
            }));

            $pedidos = array_slice($pedidos, 0, 300);
        }

        // ======= Métricas sobre el rango =======
        $totalPedidos    = count($pedidos);
        $totalUtilidad   = 0.0;
        $totalVenta      = 0.0;
        $totalProveedor  = 0.0;
        $pedidosNuevos   = 0;

        foreach ($pedidos as $p) {
            $estado = $p['estado'] ?? '';

            $cantidad = (int)($p['cantidad_total'] ?? 1);
            if ($cantidad < 1) $cantidad = 1;

            $precioUnit     = (float)($p['precio_venta'] ?? 0);
            $precioProvUnit = (float)($p['precio_proveedor'] ?? 0);

            // Total cobrado al cliente
            $precioTotal = isset($p['precio_total'])
                ? (float)$p['precio_total']
                : ($precioUnit * $cantidad);

            // Costo envío: prioridad pedido -> producto -> 0
            $costoEnvio = 0.0;
            if (isset($p['costo_envio'])) {
                $costoEnvio = (float)$p['costo_envio'];
            } elseif (isset($p['producto_costo_envio'])) {
                $costoEnvio = (float)$p['producto_costo_envio'];
            }
            if ($costoEnvio < 0) $costoEnvio = 0;

            // Utilidad total
            if (isset($p['utilidad_total'])) {
                $utilidadTotal = (float)$p['utilidad_total'];
            } else {
                $costoTotal = ($precioProvUnit * $cantidad) + $costoEnvio;
                $utilidadTotal = $precioTotal - $costoTotal;
            }
            if (!is_finite($utilidadTotal)) $utilidadTotal = 0.0;

            if ($estado === 'nuevo') {
                $pedidosNuevos++;
            }

            // Métricas: excluimos cancelados
            if ($estado === 'cancelado') {
                continue;
            }

            $totalVenta     += $precioTotal;
            $totalUtilidad  += $utilidadTotal;
            $totalProveedor += ($precioProvUnit * $cantidad);
        }

        $this->view('admin/pedidos/index', [
            'pedidos'          => $pedidos,
            'total_pedidos'    => $totalPedidos,
            'total_utilidad'   => $totalUtilidad,
            'total_venta'      => $totalVenta,
            'total_proveedor'  => $totalProveedor,
            'pedidos_nuevos'   => $pedidosNuevos,
            'rango'            => $rango,
        ]);
    }

    // ✅ ÚNICA versión del helper (sin duplicados)
    private function calcularRangoFechas(string $rango): array
    {
        $tz  = new DateTimeZone('America/Bogota');
        $now = new DateTime('now', $tz);

        switch ($rango) {
            case 'hoy':
                $inicio = (clone $now)->setTime(0, 0, 0);
                $fin    = (clone $inicio)->modify('+1 day');
                break;

            case 'ayer':
                $fin    = (clone $now)->setTime(0, 0, 0);
                $inicio = (clone $fin)->modify('-1 day');
                break;

            case 'semana':
                $inicio = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
                $fin    = (clone $inicio)->modify('+1 week');
                break;

            case 'mes':
            default:
                $inicio = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
                $fin    = (clone $inicio)->modify('first day of next month');
                break;
        }

        return [$inicio, $fin];
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
            if (!empty($_POST['ajax']) && $_POST['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
                return;
            }
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'entregado', 'cancelado'];
        if (!in_array($estado, $estadosPosibles, true)) {
            if (!empty($_POST['ajax']) && $_POST['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Estado no permitido']);
                return;
            }
            header("Location: /tienda_mvc/AdminPedidos/index");
            exit;
        }

        $pedidoModel = new Pedido();
        $pedidoModel->actualizarEstado($id, $estado);

        if (!empty($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'     => true,
                'id'     => $id,
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
