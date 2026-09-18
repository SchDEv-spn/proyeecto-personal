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
     *
     * Si no hay pedido de la landing, cae al contacto manual guardado para
     * ese teléfono (si existe) — así no hay que volver a escribir los datos
     * de un cliente que llega por WhatsApp directo de las vendedoras.
     */
    public function buscarPedido()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $telefono = trim((string)($_GET['telefono'] ?? ''));
        if ($telefono === '') {
            echo json_encode(['ok' => true, 'pedidos' => [], 'contacto' => null]);
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
                'numeroGuia'   => $p['numero_guia'] ?? '',
                'fecha'        => !empty($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : '',
            ];
        }, $rows);

        $contacto = null;
        if (empty($pedidos)) {
            $c = (new ContactoManual())->buscarPorTelefono($telefono);
            if ($c) {
                $contacto = [
                    'nombre'       => $c['nombre'] ?? '',
                    'apellidos'    => $c['apellidos'] ?? '',
                    'producto'     => $c['producto'] ?? '',
                    'cantidad'     => $c['cantidad'] ?? '1',
                    'precio'       => $c['precio'] ?? '',
                    'municipio'    => $c['municipio'] ?? '',
                    'departamento' => $c['departamento'] ?? '',
                    'tipoEntrega'  => $c['tipo_entrega'] ?? 'domicilio',
                    'estado'       => $c['estado'] ?? 'nuevo',
                    'numeroGuia'   => $c['numero_guia'] ?? '',
                ];
            }
        }

        echo json_encode(['ok' => true, 'pedidos' => $pedidos, 'contacto' => $contacto], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Registra un mensaje armado a mano para un número que no corresponde a
     * un pedido de la landing (p. ej. clientes de las vendedoras de
     * WhatsApp). Deja constancia en el log de auditoría (no el texto
     * completo del mensaje) y guarda/actualiza el contacto manual completo
     * para que la próxima búsqueda por ese teléfono ya traiga los datos.
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
        $guia     = trim((string)($_POST['guia']     ?? ''));

        if ($telefono === '') {
            echo json_encode(['ok' => false, 'error' => 'Falta el teléfono']);
            exit;
        }

        $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
        $ok = (new WaMensajeLog())->registrar($telefono, $nombre, $estado, $usuarioNombre);

        $contactoModel = new ContactoManual();
        $contactoModel->upsert($telefono, [
            'nombre'        => trim((string)($_POST['datosNombre']       ?? $nombre)),
            'apellidos'     => trim((string)($_POST['datosApellidos']    ?? '')),
            'producto'      => trim((string)($_POST['datosProducto']     ?? '')),
            'cantidad'      => trim((string)($_POST['datosCantidad']     ?? '1')),
            'precio'        => trim((string)($_POST['datosPrecio']       ?? '')),
            'municipio'     => trim((string)($_POST['datosMunicipio']    ?? '')),
            'departamento'  => trim((string)($_POST['datosDepartamento'] ?? '')),
            'tipoEntrega'   => trim((string)($_POST['datosTipoEntrega']  ?? 'domicilio')),
            'estado'        => $estado,
            'usuarioNombre' => $usuarioNombre,
        ]);
        // Guía y fecha de aviso "en oficina" — mismo criterio que un pedido real.
        $contactoModel->registrarEnvioWa($telefono, $estado, $guia !== '' ? $guia : null);

        echo json_encode(['ok' => $ok]);
        exit;
    }

    /**
     * Pedidos reales y contactos manuales que están "en oficina" y ya
     * fueron notificados — con los días de espera, para saber a quién le
     * toca el recordatorio antes de que Interrapidísimo devuelva el
     * paquete. Se muestra como panel en Plantillas WA.
     */
    public function enOficina()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $pedidos = array_map(function (array $p) {
            $cantidad = max(1, (int)($p['cantidad_total'] ?? 1));
            $precio   = isset($p['precio_total'])
                ? (float)$p['precio_total']
                : (float)($p['precio_venta'] ?? 0) * $cantidad;

            return [
                'origen'       => 'pedido',
                'id'           => (int)$p['id'],
                'telefono'     => $p['telefono'] ?? '',
                'nombre'       => $p['nombre'] ?? '',
                'apellidos'    => $p['apellidos'] ?? '',
                'producto'     => $p['producto_nombre'] ?? '',
                'cantidad'     => (string)$cantidad,
                'precio'       => '$' . number_format($precio, 0, ',', '.'),
                'municipio'    => $p['municipio'] ?? '',
                'departamento' => $p['departamento'] ?? '',
                'tipoEntrega'  => $p['tipo_entrega'] ?? 'oficina',
                'numeroGuia'   => $p['numero_guia'] ?? '',
                'diasEsperando'=> (int)($p['dias_esperando'] ?? 0),
            ];
        }, (new Pedido())->obtenerEnOficinaEsperando());

        $contactos = array_map(function (array $c) {
            return [
                'origen'       => 'manual',
                'id'           => null,
                'telefono'     => $c['telefono'] ?? '',
                'nombre'       => $c['nombre'] ?? '',
                'apellidos'    => $c['apellidos'] ?? '',
                'producto'     => $c['producto'] ?? '',
                'cantidad'     => $c['cantidad'] ?? '1',
                'precio'       => $c['precio'] ?? '',
                'municipio'    => $c['municipio'] ?? '',
                'departamento' => $c['departamento'] ?? '',
                'tipoEntrega'  => $c['tipo_entrega'] ?? 'oficina',
                'numeroGuia'   => $c['numero_guia'] ?? '',
                'diasEsperando'=> (int)($c['dias_esperando'] ?? 0),
            ];
        }, (new ContactoManual())->obtenerEnOficinaEsperando());

        $todos = array_merge($pedidos, $contactos);
        usort($todos, fn($a, $b) => $b['diasEsperando'] <=> $a['diasEsperando']);

        echo json_encode(['ok' => true, 'items' => $todos], JSON_UNESCAPED_UNICODE);
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
