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

        // Órdenes de Dropi: tarjetas de resumen y vista con la que se abre la página.
        $ordenes    = new DropiOrden();
        $contadores = $ordenes->contadores();
        $vista = (string)($_GET['vista'] ?? '');
        if (!in_array($vista, DropiOrden::VISTAS, true)) {
            $vista = $contadores['por_avisar'] > 0 ? 'por_avisar'
                : ($contadores['esperando'] > 0 ? 'esperando'
                : ($contadores['en_camino'] > 0 ? 'en_camino' : 'todas'));
        }

        $this->view('admin/plantillas_wa/index', [
            'plantillas'        => $plantillas,
            'productos'         => $productos,
            'ubicaciones'       => $ubicaciones,
            'contadores'        => $contadores,
            'ultimaImportacion' => $ordenes->ultimaImportacion(),
            'vistaInicial'      => $vista,
        ]);
    }

    /**
     * Una página de órdenes de Dropi para la tabla de Seguimiento (DataTables en modo
     * servidor: el historial puede tener miles de filas). Las celdas salen ya escapadas;
     * 'datos' lleva lo que necesita el compositor de WhatsApp para armar el mensaje.
     */
    public function ordenesDropi()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $ordenes = new DropiOrden();
        $pagina = $ordenes->listar([
            'vista'  => (string)($_GET['vista']  ?? 'todas'),
            'buscar' => (string)($_GET['buscar'] ?? ''),
            'desde'  => (string)($_GET['desde']  ?? ''),
            'hasta'  => (string)($_GET['hasta']  ?? ''),
        ], (int)($_GET['start'] ?? 0), (int)($_GET['length'] ?? 25));

        $filas = [];
        $datos = [];
        foreach ($pagina['filas'] as $o) {
            [$fila, $dato] = $this->filaOrdenDropi($o);
            $filas[] = $fila;
            $datos[(int)$o['id']] = $dato;
        }

        echo json_encode([
            'draw'            => (int)($_GET['draw'] ?? 0),
            'recordsTotal'    => $pagina['total'],
            'recordsFiltered' => $pagina['filtrado'],
            'data'            => $filas,
            'datos'           => $datos,
            'contadores'      => $ordenes->contadores(),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Deja constancia de un mensaje enviado (o de un "ya le avisé" por otro medio) a
     * una orden de Dropi: marca ese aviso en la orden —que es lo que la saca de
     * "Por avisar"— y, si hubo mensaje, lo anota en el registro de WhatsApp.
     */
    public function registrarAvisoDropi()
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            exit;
        }

        $id     = (int)($_POST['orden_id'] ?? 0);
        $estado = trim((string)($_POST['estado'] ?? ''));
        $guia   = trim((string)($_POST['guia'] ?? ''));
        $solo   = !empty($_POST['solo_marcar']);

        $plantillas = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'recordatorio_oficina', 'ultimo_aviso_oficina', 'entregado', 'cancelado'];
        if (!in_array($estado, $plantillas, true)) {
            echo json_encode(['ok' => false, 'error' => 'Mensaje no reconocido']);
            exit;
        }

        $ordenes = new DropiOrden();
        $orden   = $ordenes->porId($id);
        if (!$orden || !$ordenes->registrarAviso($id, $estado, $guia)) {
            echo json_encode(['ok' => false, 'error' => 'No encontré la orden']);
            exit;
        }

        if (!$solo) {
            (new WaMensajeLog())->registrar(
                (string)$orden['telefono'],
                trim($orden['nombre'] . ' ' . $orden['apellidos']),
                $estado,
                $_SESSION['usuario_nombre'] ?? 'Admin'
            );
        }

        echo json_encode(['ok' => true, 'contadores' => $ordenes->contadores()], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Celdas (HTML ya escapado) y datos del compositor de una orden de la tabla.
     *
     * @return array{0: array<string,string>, 1: array<string,mixed>}
     */
    private function filaOrdenDropi(array $o): array
    {
        $e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $dias = static fn(int $n) => $n <= 0 ? 'hoy' : ($n === 1 ? 'ayer' : "hace {$n} días");

        $estado    = (string)$o['estado'];
        $etiquetas = DropiImport::ETIQUETAS;
        $siguiente = DropiOrden::siguienteAviso($o);
        $nombre    = trim($o['nombre'] . ' ' . $o['apellidos']);
        $diasEstado = (int)$o['dias_estado'];
        $diasPedido = (int)$o['dias_pedido'];

        // Producto con su variación (color): "Bolso en Cuero · Negro".
        $producto = [];
        foreach ($o['lineas'] as $l) {
            $texto = $e($l['producto']) . ($l['variacion'] !== '' ? ' <small class="seg-sub">' . $e($l['variacion']) . ($l['cantidad'] > 1 ? ' × ' . (int)$l['cantidad'] : '') . '</small>' : '');
            $producto[] = '<div>' . $texto . '</div>';
        }
        $productoHtml = $producto ? implode('', $producto) : $e($o['producto'] !== '' ? $o['producto'] : '—');

        // Cuánto lleva en este estado.
        if ($estado === 'en_oficina') {
            $tiempo = $diasEstado <= 0 ? 'Llegó hoy' : $diasEstado . ' día' . ($diasEstado === 1 ? '' : 's') . ' en oficina';
        } elseif ($estado === 'entregado') {
            $tiempo = 'Entregado ' . $dias($diasEstado);
        } elseif ($estado === 'cancelado') {
            $tiempo = 'Cancelado';
        } else {
            $tiempo = 'Pedido ' . $dias($diasPedido);
        }

        // Último mensaje enviado a esta orden.
        $avisos = [
            'aviso_enviado_at' => 'Guía enviada', 'aviso_oficina_at' => 'Avisado que llegó',
            'aviso_recordatorio_at' => 'Recordatorio enviado', 'aviso_ultimo_at' => 'Última oportunidad enviada',
            'aviso_entregado_at' => 'Foto pedida',
        ];
        $ultimoAviso = null;
        foreach ($avisos as $col => $texto) {
            if (!empty($o[$col]) && ($ultimoAviso === null || $o[$col] > $ultimoAviso[0])) $ultimoAviso = [$o[$col], $texto];
        }
        if ($ultimoAviso !== null) {
            $dAviso = (int)floor((strtotime(date('Y-m-d')) - strtotime(substr($ultimoAviso[0], 0, 10))) / 86400);
            $avisoHtml = '<small class="seg-sub">' . $e($ultimoAviso[1]) . ' ' . $dias($dAviso) . '</small>';
        } elseif ($estado === 'en_oficina') {
            $avisoHtml = '<small class="seg-sub" style="color:var(--err);font-weight:700">Sin aviso</small>';
        } else {
            $avisoHtml = '';
        }

        $insignias = '';
        if ($siguiente && $siguiente['estado'] === 'recordatorio_oficina') $insignias .= ' <span class="status-tag status-contactado">Toca recordatorio</span>';
        if ($siguiente && $siguiente['estado'] === 'ultimo_aviso_oficina')  $insignias .= ' <span class="status-tag status-cancelado">Última oportunidad</span>';
        if (!empty($o['sin_actualizar'])) $insignias .= ' <span class="status-tag" style="background:var(--surface-3);color:var(--tx-muted)" title="No apareció en tus últimas descargas de Dropi">Sin actualizar</span>';

        $estadoHtml = '<span class="status-tag status-' . ($estado === 'otro' ? 'contactado' : $e($estado)) . '">' . $e($etiquetas[$estado] ?? $estado) . '</span>'
            . '<small class="seg-sub">Dropi: ' . $e($o['estatus'] !== '' ? $o['estatus'] : '—') . '</small>'
            . ($o['novedad'] !== '' ? '<small class="seg-sub" style="color:var(--warn);font-weight:700">Novedad: ' . $e($o['novedad']) . '</small>' : '');

        $lugar = $e($o['municipio']) . ($o['departamento'] !== '' ? ' <small class="seg-sub">' . $e($o['departamento']) . '</small>' : '')
            . ($o['guia'] !== '' ? '<small class="seg-sub">Guía ' . $e($o['guia']) . '</small>' : '');

        // Botón: el mensaje que toca, o "Mensaje" para abrir el compositor con cualquier plantilla.
        if ($siguiente) {
            $accion = '<button type="button" class="btn-primary" data-act="avisar" data-id="' . (int)$o['id'] . '"><i class="fab fa-whatsapp" aria-hidden="true"></i> ' . $e($siguiente['etiqueta']) . '</button>'
                . '<button type="button" class="btn-ghost seg-ya" data-act="ya" data-id="' . (int)$o['id'] . '" title="Márcalo si ya le avisaste por otro medio">Ya avisé</button>';
        } else {
            $accion = '<button type="button" class="btn-primary btn-primary--soft" data-act="avisar" data-id="' . (int)$o['id'] . '"><i class="fab fa-whatsapp" aria-hidden="true"></i> Mensaje</button>';
        }

        $clienteHtml = '<strong>' . $e($nombre !== '' ? $nombre : 'Sin nombre') . '</strong><small class="seg-sub">' . $e($o['telefono']) . '</small>'
            . '<span class="seg-m"><small class="seg-sub">' . $e($o['producto']) . ' · ' . $e($o['municipio']) . '</small>'
            . $estadoHtml . '<small class="seg-sub"><b>' . $e($tiempo) . '</b></small>' . $avisoHtml . $insignias . '</span>';

        $fila = [
            'cliente'  => $clienteHtml,
            'estado'   => $estadoHtml . $insignias,
            'tiempo'   => '<b>' . $e($tiempo) . '</b>' . $avisoHtml,
            'producto' => $productoHtml,
            'lugar'    => $lugar,
            'accion'   => $accion,
        ];

        $dato = [
            'ordenId'      => (int)$o['id'],
            'telefono'     => $o['telefono'],
            'nombre'       => $o['nombre'],
            'apellidos'    => $o['apellidos'],
            'producto'     => $o['producto'],
            'cantidad'     => (string)max(1, (int)$o['cantidad']),
            'precio'       => (float)$o['valor'] > 0 ? '$' . number_format((float)$o['valor'], 0, ',', '.') : '',
            'municipio'    => $o['municipio'],
            'departamento' => $o['departamento'],
            'tipoEntrega'  => $o['tipo_entrega'] !== '' ? $o['tipo_entrega'] : 'oficina',
            'numeroGuia'   => $o['guia'],
            'estado'       => $siguiente ? $siguiente['estado'] : DropiOrden::plantillaPorDefecto($estado),
            'siguiente'    => $siguiente ? $siguiente['estado'] : null,
        ];

        return [$fila, $dato];
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

    /* ───────────────  Importar órdenes desde la exportación de Dropi  ───────────────
     *
     * Tres pasos: subir el Excel → vista previa (qué entra, qué se actualiza, a qué
     * producto de la tienda corresponde cada producto de Dropi) → importar. La lógica
     * está en DropiImport; aquí solo se valida el archivo y se pasan los datos a la vista.
     */

    private const DROPI_MAX_BYTES = 8 * 1024 * 1024;

    public function importarDropi()
    {
        $this->requireLogin();
        $this->vistaImportarDropi(['paso' => 'subir']);
    }

    public function importarDropiVista()
    {
        $this->requireLogin();
        $this->requireCsrf();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/AdminPlantillasWa/importarDropi');
            exit;
        }

        $archivo = $_FILES['archivo'] ?? null;
        $error = $this->validarArchivoDropi($archivo);
        if ($error !== null) {
            $this->vistaImportarDropi(['paso' => 'subir', 'errores' => [$error]]);
            return;
        }

        $import = new DropiImport();
        try {
            $lectura = $import->leerArchivo($archivo['tmp_name']);
        } catch (\RuntimeException $e) {
            $this->vistaImportarDropi(['paso' => 'subir', 'errores' => [$e->getMessage()]]);
            return;
        }
        if (empty($lectura['ordenes'])) {
            $this->vistaImportarDropi(['paso' => 'subir', 'errores' => [
                'El archivo no trae ninguna orden. Revisa que sea la descarga de órdenes de Dropi y que el rango de fechas tenga pedidos.',
            ]]);
            return;
        }

        $lectura['archivo'] = mb_substr(basename((string)$archivo['name']), 0, 120);
        $_SESSION['dropi_import_token'] = $import->guardarTemporal($lectura);

        $productosDropi = $import->productosDeDropi($lectura['ordenes']);
        $this->vistaImportarDropi([
            'paso'           => 'previa',
            'token'          => $_SESSION['dropi_import_token'],
            'previa'         => $import->procesar($lectura['ordenes'], false),
            'omitidas'       => $lectura['omitidas'],
            'productosDropi' => $productosDropi,
            'mapa'           => $import->mapaProductos(array_keys($productosDropi)),
            'locales'        => $import->productosLocales(),
            'nombreArchivo'  => mb_substr(basename((string)$archivo['name']), 0, 80),
        ]);
    }

    public function importarDropiConfirmar()
    {
        $this->requireLogin();
        $this->requireCsrf();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/AdminPlantillasWa/importarDropi');
            exit;
        }

        $token    = (string)($_POST['token'] ?? '');
        $esperado = (string)($_SESSION['dropi_import_token'] ?? '');
        $import   = new DropiImport();
        $lectura  = ($esperado !== '' && hash_equals($esperado, $token)) ? $import->leerTemporal($token) : null;

        if ($lectura === null) {
            $this->vistaImportarDropi(['paso' => 'subir', 'errores' => [
                'La vista previa venció o ya se importó. Sube el archivo de nuevo.',
            ]]);
            return;
        }

        $eleccion = [];
        $nombres  = [];
        foreach ($import->productosDeDropi($lectura['ordenes']) as $dropiId => $p) {
            $nombres[$dropiId]  = $p['nombre'];
            $eleccion[$dropiId] = (int)($_POST['producto'][$dropiId] ?? 0);
        }

        try {
            $import->guardarMapa($eleccion, $nombres);
            $resultado = $import->procesar($lectura['ordenes'], true, (string)($lectura['archivo'] ?? ''), (string)($_SESSION['usuario_nombre'] ?? 'Admin'));
        } catch (\Throwable $e) {
            error_log('[importarDropi] ' . $e->getMessage());
            $this->vistaImportarDropi(['paso' => 'subir', 'errores' => [
                'No se pudo importar y no se guardó nada. Intenta de nuevo; si sigue fallando, avísame.',
            ]]);
            return;
        }

        $import->borrarTemporal($token);
        unset($_SESSION['dropi_import_token']);

        $this->vistaImportarDropi([
            'paso'       => 'resultado',
            'resultado'  => $resultado,
            'omitidas'   => $lectura['omitidas'],
            'contadores' => (new DropiOrden())->contadores(),
        ]);
    }

    /** @return string|null  mensaje de error, o null si el archivo es utilizable */
    private function validarArchivoDropi(?array $archivo): ?string
    {
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Elige el archivo Excel que descargaste de Dropi.';
        }
        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE
            || (int)$archivo['size'] > self::DROPI_MAX_BYTES) {
            return 'El archivo es demasiado grande. Descarga un rango de fechas más corto.';
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
            return 'No se pudo subir el archivo. Intenta de nuevo.';
        }
        if (strtolower(pathinfo((string)$archivo['name'], PATHINFO_EXTENSION)) !== 'xlsx') {
            return 'El archivo tiene que ser un Excel .xlsx (la descarga de órdenes de Dropi).';
        }
        // Un .xlsx es un zip: empieza por "PK".
        $h = @fopen($archivo['tmp_name'], 'rb');
        $firma = $h ? fread($h, 2) : '';
        if ($h) fclose($h);
        if ($firma !== 'PK') {
            return 'El archivo no es un Excel .xlsx válido.';
        }
        return null;
    }

    private function vistaImportarDropi(array $datos): void
    {
        $this->view('admin/plantillas_wa/importar_dropi', $datos);
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/Auth/login');
            exit;
        }
    }
}
