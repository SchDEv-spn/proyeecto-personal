<?php

class AdminPedidosController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        // ✅ Rango permitido: hoy | ayer | semana | mes | personalizado
        $rango = $_GET['rango'] ?? 'mes';
        $permitidos = ['hoy', 'ayer', 'semana', 'mes', 'personalizado'];
        if (!in_array($rango, $permitidos, true)) $rango = 'mes';

        // Fechas custom para rango personalizado
        $desdeStr = null;
        $hastaStr = null;
        if ($rango === 'personalizado') {
            $desdeStr = isset($_GET['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde']) ? $_GET['desde'] : null;
            $hastaStr = isset($_GET['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta']) ? $_GET['hasta'] : null;
            if (!$desdeStr || !$hastaStr || $desdeStr > $hastaStr) $rango = 'mes';
        }

        // ✅ Fechas inicio/fin (America/Bogota) en formato SQL
        list($inicioObj, $finObj) = $this->calcularRangoFechas($rango, $desdeStr, $hastaStr);
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
        $pedidosActivos  = 0;

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

            $pedidosActivos++;
            $totalVenta     += $precioTotal;
            $totalUtilidad  += $utilidadTotal;
            $totalProveedor += ($precioProvUnit * $cantidad);
        }

        $ticketPromedio = $pedidosActivos > 0 ? round($totalVenta / $pedidosActivos) : 0;

        // ======= Período previo para tendencias =======
        list($prevInicioObj, $prevFinObj) = $this->calcularRangoPrevio($rango, $inicioObj, $finObj);

        $pedidosPrev = [];
        if (method_exists($pedidoModel, 'obtenerPorRango')) {
            $pedidosPrev = $pedidoModel->obtenerPorRango(
                $prevInicioObj->format('Y-m-d H:i:s'),
                $prevFinObj->format('Y-m-d H:i:s'),
                300
            );
        }

        $prevPedidos  = count($pedidosPrev);
        $prevVenta    = 0.0;
        $prevUtilidad = 0.0;

        foreach ($pedidosPrev as $pp) {
            if (($pp['estado'] ?? '') === 'cancelado') continue;
            $cant2        = max(1, (int)($pp['cantidad_total'] ?? 1));
            $punit2       = (float)($pp['precio_venta']     ?? 0);
            $pprov2       = (float)($pp['precio_proveedor'] ?? 0);
            $ptotal2      = isset($pp['precio_total']) ? (float)$pp['precio_total'] : ($punit2 * $cant2);
            $envio2       = 0.0;
            if (isset($pp['costo_envio']))          $envio2 = (float)$pp['costo_envio'];
            elseif (isset($pp['producto_costo_envio'])) $envio2 = (float)$pp['producto_costo_envio'];
            if ($envio2 < 0) $envio2 = 0;
            $prevVenta    += $ptotal2;
            $prevUtilidad += $ptotal2 - ($pprov2 * $cant2) - $envio2;
        }

        $tendencias = [
            'pedidos'  => $this->calcTrendPct((float)$totalPedidos, (float)$prevPedidos),
            'ventas'   => $this->calcTrendPct($totalVenta,    $prevVenta),
            'utilidad' => $this->calcTrendPct($totalUtilidad, $prevUtilidad),
        ];

        $plantillasWa = (new PlantillaWa())->keyedByEstado();

        $this->view('admin/pedidos/index', [
            'pedidos'          => $pedidos,
            'total_pedidos'    => $totalPedidos,
            'total_utilidad'   => $totalUtilidad,
            'total_venta'      => $totalVenta,
            'total_proveedor'  => $totalProveedor,
            'pedidos_nuevos'   => $pedidosNuevos,
            'ticket_promedio'  => $ticketPromedio,
            'rango'            => $rango,
            'desde'            => $desdeStr,
            'hasta'            => $hastaStr,
            'tendencias'       => $tendencias,
            'plantillas_wa'    => $plantillasWa,
        ]);
    }

    private function calcularRangoPrevio(string $rango, DateTime $inicio, ?DateTime $finActual = null): array
    {
        $fin = clone $inicio;
        switch ($rango) {
            case 'personalizado':
                if ($finActual !== null) {
                    $durSecs = $finActual->getTimestamp() - $inicio->getTimestamp();
                    $inicio = (clone $fin)->modify("-{$durSecs} seconds");
                } else {
                    $inicio = (clone $fin)->modify('-1 month');
                }
                break;
            case 'hoy':
            case 'ayer':
                $inicio = (clone $fin)->modify('-1 day');
                break;
            case 'semana':
                $inicio = (clone $fin)->modify('-1 week');
                break;
            case 'mes':
            default:
                $inicio = (clone $fin)->modify('-1 month');
                break;
        }
        return [$inicio, $fin];
    }

    private function calcTrendPct(float $current, float $prev): array
    {
        if ($prev == 0.0) {
            return ['pct' => null, 'dir' => 'flat', 'label' => '—'];
        }
        $pct = (int)round(($current - $prev) / $prev * 100);
        $dir = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
        $lbl = ($pct > 0 ? '+' : '') . $pct . '%';
        return ['pct' => $pct, 'dir' => $dir, 'label' => $lbl];
    }

    // ✅ ÚNICA versión del helper (sin duplicados)
    private function calcularRangoFechas(string $rango, ?string $desdeStr = null, ?string $hastaStr = null): array
    {
        $tz  = new DateTimeZone('America/Bogota');
        $now = new DateTime('now', $tz);

        switch ($rango) {
            case 'personalizado':
                $inicio = (new DateTime($desdeStr, $tz))->setTime(0, 0, 0);
                $fin    = (new DateTime($hastaStr, $tz))->setTime(0, 0, 0)->modify('+1 day');
                break;

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

    public function exportarCsv()
    {
        $this->requireLogin();

        $rango      = $_GET['rango'] ?? 'mes';
        $permitidos = ['hoy', 'ayer', 'semana', 'mes', 'personalizado'];
        if (!in_array($rango, $permitidos, true)) $rango = 'mes';

        $desdeStr = null;
        $hastaStr = null;
        if ($rango === 'personalizado') {
            $desdeStr = isset($_GET['desde']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['desde']) ? $_GET['desde'] : null;
            $hastaStr = isset($_GET['hasta']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['hasta']) ? $_GET['hasta'] : null;
            if (!$desdeStr || !$hastaStr || $desdeStr > $hastaStr) $rango = 'mes';
        }

        list($inicioObj, $finObj) = $this->calcularRangoFechas($rango, $desdeStr, $hastaStr);
        $pedidoModel = new Pedido();

        if (method_exists($pedidoModel, 'obtenerPorRango')) {
            $pedidos = $pedidoModel->obtenerPorRango(
                $inicioObj->format('Y-m-d H:i:s'),
                $finObj->format('Y-m-d H:i:s'),
                2000
            );
        } else {
            $pedidos = $pedidoModel->obtenerTodos(2000);
        }

        $rangoLabel = ($rango === 'personalizado' && $desdeStr && $hastaStr)
            ? $desdeStr . '_' . $hastaStr
            : $rango;
        $filename = 'pedidos_' . $rangoLabel . '_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM para Excel

        fputcsv($out, [
            'ID', 'Fecha', 'Estado',
            'Nombre', 'Apellidos', 'Teléfono',
            'Municipio', 'Departamento',
            'Producto', 'Cantidad',
            'Precio Total', 'Costo Proveedor', 'Costo Envío', 'Utilidad',
            'Tipo Entrega', 'Dirección',
        ]);

        foreach ($pedidos as $p) {
            $cant        = max(1, (int)($p['cantidad_total'] ?? 1));
            $punit       = (float)($p['precio_venta']      ?? 0);
            $ptotal      = isset($p['precio_total']) ? (float)$p['precio_total'] : ($punit * $cant);
            $pprov       = (float)($p['precio_proveedor']  ?? 0);
            $cenvio      = isset($p['costo_envio'])
                ? (float)$p['costo_envio']
                : (float)($p['producto_costo_envio'] ?? 0);
            $utilidad    = $ptotal - ($pprov * $cant) - $cenvio;

            fputcsv($out, [
                $p['id']              ?? '',
                $p['created_at']      ?? '',
                $p['estado']          ?? '',
                $p['nombre']          ?? '',
                $p['apellidos']       ?? '',
                $p['telefono']        ?? '',
                $p['municipio']       ?? '',
                $p['departamento']    ?? '',
                $p['producto_nombre'] ?? '',
                $cant,
                number_format($ptotal,   2, '.', ''),
                number_format($pprov * $cant, 2, '.', ''),
                number_format($cenvio,   2, '.', ''),
                number_format($utilidad, 2, '.', ''),
                $p['tipo_entrega']    ?? '',
                $p['direccion']       ?? '',
            ]);
        }

        fclose($out);
        exit;
    }

    public function actualizarTelefono()
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $id       = (int)(  $_POST['id']       ?? 0);
        $telefono = trim((string)($_POST['telefono'] ?? ''));

        if ($id <= 0 || $telefono === '') {
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            exit;
        }

        if (!preg_match('/^[\d\s\+\-\(\)]{6,20}$/', $telefono)) {
            echo json_encode(['ok' => false, 'error' => 'Formato de teléfono no válido']);
            exit;
        }

        $pedidoModel = new Pedido();
        $ok = $pedidoModel->actualizarTelefono($id, $telefono);

        echo json_encode($ok
            ? ['ok' => true, 'telefono' => $telefono]
            : ['ok' => false, 'error' => 'No se pudo guardar']
        );
        exit;
    }

    public function detalle()
    {
        $this->requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header("Location: " . BASE_URL . "/AdminPedidos/index");
            exit;
        }

        $pedidoModel = new Pedido();
        $pedido = $pedidoModel->obtenerPorId($id);

        if (!$pedido) {
            header("Location: " . BASE_URL . "/AdminPedidos/index");
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
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminPedidos/index");
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
            header("Location: " . BASE_URL . "/AdminPedidos/index");
            exit;
        }

        $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];
        if (!in_array($estado, $estadosPosibles, true)) {
            if (!empty($_POST['ajax']) && $_POST['ajax'] == '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Estado no permitido']);
                return;
            }
            header("Location: " . BASE_URL . "/AdminPedidos/index");
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

        header("Location: " . BASE_URL . "/AdminPedidos/index");
        exit;
    }

    public function contadores()
    {
        $this->requireLogin();

        $pedidoModel   = new Pedido();
        $pedidosNuevos = $pedidoModel->contarNuevos();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['pedidos_nuevos' => $pedidosNuevos]);
        exit();
    }

    private function requireLogin()
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/Auth/login");
            exit;
        }
    }
}
