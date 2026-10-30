<?php

class AdminPlantillasWaController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $model      = new PlantillaWa();
        $plantillas = $model->keyedByEstado();

        // Órdenes de Dropi: tarjetas de resumen y vista con la que se abre la página.
        $ordenes    = new DropiOrden();
        $contadores = $ordenes->contadores();
        $vista = (string)($_GET['vista'] ?? '');
        if (!in_array($vista, DropiOrden::VISTAS, true)) {
            $vista = $contadores['por_avisar'] > 0 ? 'por_avisar'
                : ($contadores['esperando'] > 0 ? 'esperando'
                : ($contadores['en_camino'] > 0 ? 'en_camino' : 'todas'));
        }

        // Productos de Dropi que aún no se emparejan con uno de la tienda (con una sugerencia por el nombre).
        $sinEmparejar    = $ordenes->productosSinEmparejar();
        $productosTienda = $sinEmparejar ? $ordenes->productosTienda() : [];
        foreach ($sinEmparejar as &$s) $s['sugerido'] = DropiOrden::sugerirProducto($s['nombre'], $productosTienda);
        unset($s);

        $this->view('admin/plantillas_wa/index', [
            'sinEmparejar'      => $sinEmparejar,
            'productosTienda'   => $productosTienda,
            'plantillas'        => $plantillas,
            'contadores'        => $contadores,
            'ultimaImportacion' => $ordenes->ultimaImportacion(),
            'mcpUltima'         => $ordenes->ultimaActualizacionMcp(),
            'vistaInicial'      => $vista,
            'mcpConectado'      => (new DropiMcpAuth())->conectado(),
            'mcpError'          => $_SESSION['admin_dropi_mcp_error']   ?? null,
            'mcpSuccess'        => $_SESSION['admin_dropi_mcp_success'] ?? null,
        ]);
        unset($_SESSION['admin_dropi_mcp_error'], $_SESSION['admin_dropi_mcp_success']);
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
            'estatus'        => (string)($_GET['estatus']        ?? ''),
            'transportadora' => (string)($_GET['transportadora'] ?? ''),
            'aviso'          => (string)($_GET['aviso']          ?? ''),
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
            'opciones'        => $pagina['opciones'],
            'dias'            => $pagina['dias'],   // solo en la cola de avisos: cuántas órdenes por día
            'hoy'             => date('Y-m-d'),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Dice a qué producto de la tienda corresponde un producto de Dropi (o que no lo tiene) y lo
     * aplica a todas las órdenes ya importadas. producto_id: el id del producto, o "ninguno".
     */
    public function emparejarProducto()
    {
        $this->requireLogin();
        $this->requireCsrf();
        $this->soloPost();

        $eleccion = (string)($_POST['producto_id'] ?? '');
        if ($eleccion === 'ninguno')                            $pid = null;
        elseif (ctype_digit($eleccion) && (int)$eleccion > 0)   $pid = (int)$eleccion;
        else $this->responderJson(['ok' => false, 'error' => 'Elige tu producto, o "No lo tengo en mi tienda".']);

        $ordenes = new DropiOrden();
        if (!$ordenes->emparejarProducto((string)($_POST['dropi_id'] ?? ''), $pid)) {
            $this->responderJson(['ok' => false, 'error' => 'No encontré ese producto de Dropi o ese producto de tu tienda.']);
        }
        $this->responderJson(['ok' => true, 'pendientes' => count($ordenes->productosSinEmparejar())]);
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

        $plantillas = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_reparto', 'en_oficina', 'recordatorio_oficina', 'ultimo_aviso_oficina', 'novedad', 'entregado', 'cancelado'];
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
     * "Ya avisé a todos los de este día" (título de cada día de la cola): los marca como avisados sin enviar mensajes.
     * Recibe el día y los mismos filtros de la lista que se está viendo.
     */
    public function marcarAvisadosDia()
    {
        $this->requireLogin();
        $this->requireCsrf();
        $this->soloPost();

        $ordenes = new DropiOrden();
        $n = $ordenes->marcarAvisadosDelDia([
            'vista'          => (string)($_POST['vista'] ?? ''),
            'aviso'          => (string)($_POST['aviso'] ?? ''),
            'buscar'         => (string)($_POST['buscar'] ?? ''),
            'desde'          => (string)($_POST['desde'] ?? ''),
            'hasta'          => (string)($_POST['hasta'] ?? ''),
            'estatus'        => (string)($_POST['estatus'] ?? ''),
            'transportadora' => (string)($_POST['transportadora'] ?? ''),
        ], (string)($_POST['dia'] ?? ''));

        $this->responderJson(['ok' => true, 'marcadas' => $n, 'contadores' => $ordenes->contadores()]);
    }

    /* ───────────────  Clientes con devolución  ───────────────
     *
     * Quien devuelve un pedido le cuesta el flete de regreso al dueño. Cada devolución
     * que fue culpa del cliente lo deja marcado (una vez: "devolvió antes"; dos o más:
     * "reincidente"); el dueño puede decir que una devolución no fue culpa del cliente
     * y también marcar a mano a alguien. La marca se muestra en Seguimiento, en esta
     * pantalla y en los pedidos nuevos de la landing (listado, detalle y Telegram).
     */

    public function clientesRiesgo()
    {
        $this->requireLogin();
        $this->view('admin/plantillas_wa/clientes_riesgo', ['clientes' => (new DropiOrden())->clientesRiesgo()]);
    }

    public function marcarDevolucion()
    {
        $this->requireLogin();
        $this->requireCsrf();
        $this->soloPost();

        $ok = (new DropiOrden())->marcarDevolucion((int)($_POST['orden_id'] ?? 0), !empty($_POST['exenta']), (string)($_POST['nota'] ?? ''));
        $this->responderJson($ok ? ['ok' => true] : ['ok' => false, 'error' => 'No encontré esa devolución.']);
    }

    public function agregarClienteRiesgo()
    {
        $this->requireLogin();
        $this->requireCsrf();
        $this->soloPost();

        $ok = (new DropiOrden())->agregarClienteRiesgo((string)($_POST['telefono'] ?? ''), (string)($_POST['nombre'] ?? ''), (string)($_POST['nota'] ?? ''));
        $this->responderJson($ok ? ['ok' => true] : ['ok' => false, 'error' => 'Escribe un teléfono válido.']);
    }

    public function quitarClienteRiesgo()
    {
        $this->requireLogin();
        $this->requireCsrf();
        $this->soloPost();

        (new DropiOrden())->quitarClienteRiesgo((int)($_POST['id'] ?? 0));
        $this->responderJson(['ok' => true]);
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responderJson(['ok' => false, 'error' => 'Método no permitido']);
        }
    }

    private function responderJson(array $datos): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
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
        } elseif ($estado === 'devolucion') {
            $tiempo = 'Devolución ' . $dias($diasEstado);
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
            $avisoHtml = '<small class="seg-sub" style="color:var(--red);font-weight:700">Sin aviso</small>';
        } elseif (in_array($estado, ['confirmado', 'enviado'], true) && trim((string)$o['guia']) !== '') {
            // Tiene guía y todavía no se le ha enviado al cliente: que se note en cualquier lista.
            $avisoHtml = '<small class="seg-sub" style="color:var(--red);font-weight:700">Guía sin enviar</small>';
        } else {
            $avisoHtml = '';
        }

        $insignias = '';
        if ($siguiente && $siguiente['estado'] === 'recordatorio_oficina') $insignias .= ' <span class="status-tag status-contactado">Toca recordatorio</span>';
        if ($siguiente && $siguiente['estado'] === 'ultimo_aviso_oficina')  $insignias .= ' <span class="status-tag status-cancelado">Última oportunidad</span>';
        if (!empty($o['sin_actualizar'])) $insignias .= ' <span class="status-tag" style="background:var(--surface-3);color:var(--tx-muted)" title="No apareció en tus últimas descargas de Dropi">Sin actualizar</span>';

        $estadoHtml = '<span class="status-tag status-' . (['otro' => 'contactado', 'devolucion' => 'cancelado'][$estado] ?? $e($estado)) . '">' . $e($etiquetas[$estado] ?? $estado) . '</span>'
            . '<small class="seg-sub">Dropi: ' . $e($o['estatus'] !== '' ? $o['estatus'] : '—') . '</small>'
            . ($o['novedad'] !== '' ? '<small class="seg-sub" style="color:var(--gold);font-weight:700">Novedad'
                . (isset(DropiOrden::NOVEDAD_ETIQUETAS[$o['novedad_tipo']]) ? ' · ' . $e(DropiOrden::NOVEDAD_ETIQUETAS[$o['novedad_tipo']]) : '')
                . ': ' . $e($o['novedad']) . '</small>' : '');

        $precioTxt = (float)$o['valor'] > 0 ? '$' . number_format((float)$o['valor'], 0, ',', '.') : '';

        $lugar = $e($o['municipio']) . ($o['departamento'] !== '' ? ' <small class="seg-sub">' . $e($o['departamento']) . '</small>' : '')
            . ($o['guia'] !== '' ? '<small class="seg-sub">Guía ' . $e($o['guia']) . '</small>' : '')
            . ($estado === 'devolucion' && (float)$o['flete_devolucion'] > 0
                ? '<small class="seg-sub" style="color:var(--red);font-weight:700">Flete de devolución $' . number_format((float)$o['flete_devolucion'], 0, ',', '.') . '</small>' : '');

        // Botón: el mensaje que toca, o "Mensaje" para abrir el compositor con cualquier plantilla.
        if ($siguiente) {
            $accion = '<button type="button" class="btn-primary" data-act="avisar" data-id="' . (int)$o['id'] . '"><i class="fab fa-whatsapp" aria-hidden="true"></i> ' . $e($siguiente['etiqueta']) . '</button>'
                . '<button type="button" class="btn-ghost seg-ya" data-act="ya" data-id="' . (int)$o['id'] . '" title="Márcalo si ya le avisaste por otro medio">Ya avisé</button>';
        } else {
            $accion = '<button type="button" class="btn-primary btn-primary--soft" data-act="avisar" data-id="' . (int)$o['id'] . '"><i class="fab fa-whatsapp" aria-hidden="true"></i> Mensaje</button>';
        }

        $clienteHtml = '<strong>' . $e($nombre !== '' ? $nombre : 'Sin nombre') . '</strong><small class="seg-sub">' . $e($o['telefono']) . '</small>'
            . riesgo_cliente_chip($o['riesgo'] ?? null)
            . '<span class="seg-m"><small class="seg-sub">' . $e($o['producto']) . ' · ' . $e($o['municipio']) . '</small>'
            . $estadoHtml . '<small class="seg-sub"><b>' . $e($tiempo) . '</b></small>' . $avisoHtml . $insignias . '</span>';

        $fila = [
            'dia'      => $o['estatus_desde'] ? substr((string)$o['estatus_desde'], 0, 10) : '',   // para agrupar la cola por día
            'cliente'  => $clienteHtml,
            'estado'   => $estadoHtml . $insignias,
            'tiempo'   => '<b>' . $e($tiempo) . '</b>' . $avisoHtml,
            'producto' => $productoHtml,
            'lugar'    => $lugar,
            'accion'   => $accion,
        ];

        $novedadTextos = DropiOrden::novedadTextos($o['novedad_tipo'] !== '' ? $o['novedad_tipo'] : 'otra', (string)$o['municipio'], $precioTxt);

        $transportadora = DropiOrden::transportadoraInfo((string)($o['transportadora'] ?? ''));

        $dato = [
            'ordenId'      => (int)$o['id'],
            'telefono'     => $o['telefono'],
            'nombre'       => $o['nombre'],
            'apellidos'    => $o['apellidos'],
            'producto'     => $o['producto'],
            'cantidad'     => (string)max(1, (int)$o['cantidad']),
            'precio'       => $precioTxt,
            'municipio'    => $o['municipio'],
            'departamento' => $o['departamento'],
            'tipoEntrega'  => $o['tipo_entrega'] !== '' ? $o['tipo_entrega'] : 'oficina',
            'transportadora' => $transportadora['nombre'] ?? '',
            'rastreoUrl'     => $transportadora['rastreo'] ?? '',
            'numeroGuia'   => $o['guia'],
            'estado'       => $siguiente ? $siguiente['estado'] : DropiOrden::plantillaPorDefecto($estado),
            'siguiente'    => $siguiente ? $siguiente['estado'] : null,
            // Para armar el mensaje: hasta cuándo puede recoger, y qué pasó + qué se le pide si hay novedad.
            'fechaLimite'  => $estado === 'en_oficina' ? DropiOrden::fechaLimite($o['estatus_desde']) : '',
            'novedadMotivo'   => $novedadTextos['motivo'],
            'novedadPeticion' => $novedadTextos['peticion'],
        ];

        return [$fila, $dato];
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
        $mapaProductos  = $import->mapaProductos(array_keys($productosDropi));
        $this->vistaImportarDropi([
            'paso'           => 'previa',
            'token'          => $_SESSION['dropi_import_token'],
            'previa'         => $import->procesar($lectura['ordenes'], false),
            'omitidas'       => $lectura['omitidas'],
            'productosDropi' => $productosDropi,
            'mapa'           => $mapaProductos,
            'locales'        => $import->productosLocales(),
            'sugerencias'    => $this->sugerenciasDeProductos($productosDropi, $mapaProductos),
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

    /**
     * Para los productos de Dropi que todavía no se emparejan: cuál de la tienda se parece (ver
     * DropiOrden::sugerirProducto). Llega preseleccionado en la vista previa; el dueño lo confirma.
     *
     * @param  array<string, array{nombre: string}> $productosDropi
     * @param  array<string, ?int>                  $mapa
     * @return array<string, int>
     */
    private function sugerenciasDeProductos(array $productosDropi, array $mapa): array
    {
        $pendientes = array_filter($productosDropi, static fn($p, $id) => ($mapa[$id] ?? null) === null, ARRAY_FILTER_USE_BOTH);
        if (!$pendientes) return [];

        $tienda = (new DropiOrden())->productosTienda();
        $out = [];
        foreach ($pendientes as $id => $p) {
            $sugerido = DropiOrden::sugerirProducto($p['nombre'], $tienda);
            if ($sugerido !== null) $out[$id] = $sugerido;
        }
        return $out;
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
