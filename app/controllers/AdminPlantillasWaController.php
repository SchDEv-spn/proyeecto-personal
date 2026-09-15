<?php

class AdminPlantillasWaController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $model      = new PlantillaWa();
        $plantillas = $model->keyedByEstado();

        $productos = array_values(array_filter(
            (new Producto())->obtenerTodos(),
            fn(array $p) => !empty($p['activo'])
        ));

        // Mismo dataset que usa el formulario de la landing (departamento => [municipios]).
        $ubicaciones = require __DIR__ . '/../data/colombia.php';

        $this->view('admin/plantillas_wa/index', [
            'plantillas'  => $plantillas,
            'productos'   => $productos,
            'ubicaciones' => $ubicaciones,
        ]);
    }

    /**
     * Busca pedidos de la landing por teléfono para el compositor de
     * mensajes. Devuelve los datos ya formateados (mismas claves que usa
     * el picker de WhatsApp: nombre, apellidos, producto, cantidad, precio,
     * municipio, departamento, estado, tipoEntrega) para que el JS los pase
     * directo a window.WaPicker.open() sin transformarlos de nuevo.
     */
    public function buscarPedido()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $telefono = trim((string)($_GET['telefono'] ?? ''));
        if ($telefono === '') {
            echo json_encode(['ok' => true, 'pedidos' => []]);
            exit;
        }

        $rows = (new Pedido())->buscarPorTelefono($telefono);

        $pedidos = array_map(function (array $p) {
            $cantidad = max(1, (int)($p['cantidad_total'] ?? 1));
            $precio   = isset($p['precio_total'])
                ? (float)$p['precio_total']
                : (float)($p['precio_venta'] ?? 0) * $cantidad;

            return [
                'id'           => (int)$p['id'],
                'telefono'     => $p['telefono'] ?? '',
                'nombre'       => $p['nombre'] ?? '',
                'apellidos'    => $p['apellidos'] ?? '',
                'producto'     => $p['producto_nombre'] ?? '',
                'cantidad'     => (string)$cantidad,
                'precio'       => '$' . number_format($precio, 0, ',', '.'),
                'municipio'    => $p['municipio'] ?? '',
                'departamento' => $p['departamento'] ?? '',
                'estado'       => $p['estado'] ?? 'nuevo',
                'tipoEntrega'  => $p['tipo_entrega'] ?? '',
                'fecha'        => !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '',
            ];
        }, $rows);

        echo json_encode(['ok' => true, 'pedidos' => $pedidos], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Registra un mensaje armado a mano para un número que no corresponde a
     * un pedido de la landing (p. ej. clientes de las vendedoras de
     * WhatsApp). Solo deja constancia de que se armó/envió, no guarda el
     * texto completo del mensaje.
     */
    public function registrarEnvioManual()
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $nombre   = trim((string)($_POST['nombre']   ?? ''));
        $estado   = trim((string)($_POST['estado']   ?? ''));

        if ($telefono === '') {
            echo json_encode(['ok' => false, 'error' => 'Falta el teléfono']);
            exit;
        }

        $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
        $ok = (new WaMensajeLog())->registrar($telefono, $nombre, $estado, $usuarioNombre);

        echo json_encode(['ok' => $ok]);
        exit;
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/Auth/login');
            exit;
        }
    }
}
