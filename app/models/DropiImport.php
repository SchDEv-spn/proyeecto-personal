<?php

/**
 * Importación de órdenes desde la exportación a Excel de Dropi ("Mis órdenes →
 * Descargar"). Es la salida práctica mientras la API de Dropi no devuelva órdenes:
 * el pedido se escribe UNA vez en Dropi y de ahí pasa a Seguimiento con su estatus,
 * su guía y su dinero (valor, costo, flete, comisión, ganancia).
 *
 * Cada orden se guarda en dropi_ordenes (ver DropiOrden), una fila por ID de Dropi.
 * Se espera que se descargue y suba todos los días un rango amplio (15 días o más):
 * subir lo mismo de nuevo no duplica nada, solo se actualiza lo que cambió.
 *
 * Reglas que conviene no romper:
 *  - El estado de la orden sale SIEMPRE del estatus de Dropi. Lo que hace la
 *    vendedora (avisar al cliente) se anota aparte, en las marcas aviso_*_at.
 *  - Los días "en oficina" se cuentan desde que Dropi registra el paquete ahí. Dropi
 *    actualiza ese dato una vez al día (hacia las 10 a. m.) y suele fechar el
 *    movimiento con la hora de su sincronización, no la de la llegada real; por eso
 *    se toma la fecha del archivo solo si es anterior a la primera vez que el sistema
 *    vio la orden en oficina.
 *  - Al guardar una orden por primera vez se recuperan, del registro de mensajes
 *    (wa_mensajes_log), los avisos que ya se le habían enviado a ese teléfono desde
 *    la fecha del pedido: así el primer cargue no llena la lista de "por avisar" con
 *    gente que ya fue avisada con el compositor anterior.
 *  - De la exportación solo se conserva lo que Seguimiento usa. Direcciones,
 *    documentos, correos y notas no se guardan.
 */
class DropiImport extends Model
{
    /** Estatus de Dropi (comparados con clave_color) → estado de la orden. NOVEDAD se trata aparte. */
    private const ESTADOS = [
        'guiagenerada'            => 'confirmado',
        'enesperaderutadomestica' => 'confirmado',
        'enprocesamiento'         => 'confirmado',
        'enbodegatransportadora'  => 'enviado',
        'despachada'              => 'enviado',
        'enreparto'               => 'enviado',
        'novedad'                 => 'enviado',
        'preparadoparatransportadora' => 'enviado', // ya salió de la bodega rumbo a la transportadora
        'reclameenoficina'        => 'en_oficina',
        'entregado'               => 'entregado',
        'cancelado'               => 'cancelado',
        'rechazado'               => 'cancelado', // la orden no siguió: se trata como cancelada, no como devolución
    ];

    /**
     * Estatus que se conocen pero que a propósito no se clasifican: la orden queda como
     * "Sin clasificar" (se ve en "Todas las órdenes", con el estatus de Dropi debajo) y la
     * importación no avisa por ellos.
     */
    private const ESTATUS_QUIETOS = ['intentodeentrega', 'enreexpedicion'];

    public const ETIQUETAS = [
        'confirmado' => 'Confirmado',
        'enviado'    => 'Enviado',
        'en_oficina' => 'En oficina',
        'entregado'  => 'Entregado',
        'cancelado'  => 'Cancelado',
        'devolucion' => 'Devolución',
        'otro'       => 'Sin clasificar',
    ];

    /** Nombres de departamento que Dropi escribe distinto a la lista de la tienda. */
    private const ALIAS_DEPARTAMENTO = [
        'nortesantander' => 'Norte de Santander',
        'guajira'        => 'La Guajira',
    ];

    /** Municipios que Dropi nombra distinto: "departamento|municipio" (con clave_color) → nombre en la lista de la tienda. */
    private const ALIAS_MUNICIPIO = [
        'cauca|elbordo'        => 'Patía', // El Bordo es la cabecera del municipio de Patía
        'huila|sanjosedeisnos' => 'Isnos',  // Isnos se llama también San José de Isnos
    ];

    /** Campos que se comparan para saber si una orden cambió desde la última importación. */
    private const COMPARAR = [
        'estado', 'estatus', 'guia', 'novedad', 'nombre', 'apellidos', 'telefono', 'municipio', 'departamento',
        'producto', 'cantidad', 'valor', 'costo_proveedor', 'flete', 'flete_devolucion', 'comision', 'ganancia',
        'tipo_entrega', 'ultimo_mov', 'motivo_cancelacion',
    ];
    private const NUMERICOS = ['cantidad', 'valor', 'costo_proveedor', 'flete', 'flete_devolucion', 'comision', 'ganancia'];

    private static ?array $ubicaciones = null;

    public function __construct()
    {
        parent::__construct();
        new DropiOrden(); // crea las tablas si todavía no existen
    }

    /* ─────────────────────────────  Lectura del archivo  ───────────────────────────── */

    /**
     * Lee el .xlsx y devuelve las órdenes (una por ID de Dropi, con sus líneas de
     * producto) ya normalizadas. Sirve tanto la descarga "con productos" como la
     * de órdenes a secas; esta última no trae producto ni cantidad.
     *
     * @return array{ordenes: array<int, array>, omitidas: array<int, array{id: string, motivo: string}>}
     * @throws RuntimeException
     */
    public function leerArchivo(string $ruta): array
    {
        $filas = XlsxLector::filas($ruta);

        $cab = null;
        $desde = 0;
        foreach (array_slice($filas, 0, 8, true) as $n => $fila) {
            $mapa = [];
            foreach ($fila as $col => $texto) $mapa[clave_color($texto)] = $col;
            if (isset($mapa['id'], $mapa['telefono'], $mapa['estatus'])) {
                $cab = $mapa;
                $desde = $n + 1;
                break;
            }
        }
        if ($cab === null) {
            throw new RuntimeException('Este archivo no parece una exportación de órdenes de Dropi: no encuentro las columnas ID, TELÉFONO y ESTATUS.');
        }

        $porId = [];
        $omitidas = [];
        foreach (array_slice($filas, $desde) as $fila) {
            $g = static fn(string $k): string => isset($cab[$k]) ? ($fila[$cab[$k]] ?? '') : '';

            $id = (string)preg_replace('/\D+/', '', $g('id'));
            if ($id === '') continue; // fila suelta (totales, notas…)

            $telefono = $this->normalizarTelefono($g('telefono'));
            if (strlen($telefono) < 7) {
                $omitidas[$id] = ['id' => $id, 'motivo' => 'No trae un teléfono válido.'];
                continue;
            }

            // Con productos, cada fila es una línea y el dinero viene por línea; sin productos, una fila por orden.
            $linea = [
                'pid'       => (string)preg_replace('/\D+/', '', $g('productoid')),
                'nombre'    => $g('producto'),
                'variacion' => $g('variacion'),
                'cantidad'  => isset($cab['cantidad']) ? max(1, (int)$this->numero($g('cantidad'))) : null,
                'total'     => $this->numero($g('totaldelaorden') !== '' ? $g('totaldelaorden') : $g('valordecompraenproductos')),
                'costo'     => $this->numero($g('precioproveedorxcantidad') !== '' ? $g('precioproveedorxcantidad') : $g('totalenpreciosdeproveedor')),
                'flete'     => $this->numero($g('precioflete')),
                'flete_dev' => $this->numero($g('costodevolucionflete')),
                'comision'  => $this->numero($g('comision')),
                'ganancia'  => $g('ganancia') !== '' ? $this->numero($g('ganancia')) : null,
            ];

            if (!isset($porId[$id])) {
                $solucionada = clave_color($g('fuesolucionadalanovedad')) === 'si';
                $porId[$id] = [
                    'id'             => $id,
                    'fecha'          => $this->fechaHora($g('fecha'), $g('hora')),
                    'nombre'         => $g('nombrecliente'),
                    'telefono'       => $telefono,
                    'guia'           => $g('numeroguia'),
                    'estatus'        => mb_strtoupper($g('estatus'), 'UTF-8'),
                    'departamento'   => $g('departamentodestino'),
                    'ciudad'         => $g('ciudaddestino'),
                    'transportadora' => $g('transportadora'),
                    // Solo importa la novedad que sigue abierta.
                    'novedad'        => $solucionada ? '' : mb_substr($g('novedad'), 0, 255),
                    'ultimo_mov'     => mb_substr($g('ultimomovimiento'), 0, 120),
                    'ultimo_mov_at'  => $this->fechaHora($g('fechadeultimomovimiento'), $g('horadeultimomovimiento')),
                    'cancelacion'    => mb_substr($g('motivodecancelacion'), 0, 255),
                    'lineas'         => [],
                ];
            }
            $porId[$id]['lineas'][] = $linea;
        }

        $ordenes = [];
        foreach ($porId as $o) {
            $l = $o['lineas'];
            $o['total']     = array_sum(array_column($l, 'total'));
            $o['costo']     = array_sum(array_column($l, 'costo'));
            $o['flete']     = array_sum(array_column($l, 'flete'));
            $o['flete_dev'] = array_sum(array_column($l, 'flete_dev'));
            $o['comision']  = array_sum(array_column($l, 'comision'));
            $ganancias      = array_filter(array_column($l, 'ganancia'), static fn($x) => $x !== null);
            $o['ganancia']  = $ganancias ? array_sum($ganancias) : null;
            $cantidades     = array_column($l, 'cantidad');
            $o['cantidad']  = in_array(null, $cantidades, true) ? null : array_sum($cantidades);
            $ordenes[] = $o;
        }

        return ['ordenes' => $ordenes, 'omitidas' => array_values($omitidas)];
    }

    /**
     * Productos de Dropi que aparecen en las órdenes, con cuántas órdenes tiene cada uno.
     *
     * @return array<string, array{nombre: string, ordenes: int}>  indexado por ID de producto de Dropi
     */
    public function productosDeDropi(array $ordenes): array
    {
        $out = [];
        foreach ($ordenes as $o) {
            $vistos = [];
            foreach ($o['lineas'] as $l) {
                if ($l['pid'] === '' || isset($vistos[$l['pid']])) continue;
                $vistos[$l['pid']] = true;
                $out[$l['pid']] ??= ['nombre' => $l['nombre'], 'ordenes' => 0];
                $out[$l['pid']]['ordenes']++;
            }
        }
        return $out;
    }

    /* ─────────────────────────  Producto de Dropi → producto de la tienda  ───────────────────────── */

    /** @return array<int, array{nombre: string, activo: int}>  productos de la tienda por id */
    public function productosLocales(): array
    {
        $out = [];
        foreach ($this->db->query('SELECT id, nombre, activo FROM productos ORDER BY activo DESC, nombre')->fetchAll(PDO::FETCH_ASSOC) as $p) {
            $out[(int)$p['id']] = ['nombre' => (string)$p['nombre'], 'activo' => (int)$p['activo']];
        }
        return $out;
    }

    /**
     * Qué producto de la tienda es cada producto de Dropi. Primero lo que el dueño
     * eligió aquí; si no hay nada elegido, el producto de la tienda que ya tenga ese
     * ID de Dropi (solo se lee: esta pantalla no toca productos.dropi_product_id,
     * porque con él puesto los pedidos de la landing se empezarían a enviar a Dropi).
     *
     * @param  string[] $dropiIds
     * @return array<string, ?int>  id de Dropi → id de producto de la tienda (null = ninguno)
     */
    public function mapaProductos(array $dropiIds): array
    {
        $mapa = [];
        $guardado = [];
        foreach ($this->db->query('SELECT dropi_product_id, producto_id FROM dropi_productos')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $guardado[(string)$r['dropi_product_id']] = $r['producto_id'] === null ? null : (int)$r['producto_id'];
        }

        $porDropi = [];
        foreach ($this->db->query('SELECT id, dropi_product_id FROM productos WHERE dropi_product_id IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $porDropi[(string)$r['dropi_product_id']] = (int)$r['id'];
        }

        foreach ($dropiIds as $id) {
            $id = (string)$id;
            $mapa[$id] = array_key_exists($id, $guardado) ? $guardado[$id] : ($porDropi[$id] ?? null);
        }
        return $mapa;
    }

    /**
     * @param array<string, int>    $eleccion id de Dropi → id de producto de la tienda (0 o ausente = ninguno)
     * @param array<string, string> $nombres  id de Dropi → nombre en Dropi
     */
    public function guardarMapa(array $eleccion, array $nombres): void
    {
        $locales = $this->productosLocales();
        $stmt = $this->db->prepare("
            INSERT INTO dropi_productos (dropi_product_id, dropi_nombre, producto_id)
            VALUES (:id, :nombre, :pid)
            ON DUPLICATE KEY UPDATE dropi_nombre = VALUES(dropi_nombre), producto_id = VALUES(producto_id),
                                    sin_equivalente = IF(VALUES(producto_id) IS NULL, sin_equivalente, 0)
        ");
        foreach ($nombres as $dropiId => $nombre) {
            $dropiId = (string)preg_replace('/\D+/', '', (string)$dropiId);
            if ($dropiId === '') continue;

            $pid = (int)($eleccion[$dropiId] ?? 0);
            $stmt->execute([
                ':id'     => $dropiId,
                ':nombre' => mb_substr(trim((string)$nombre), 0, 190),
                ':pid'    => isset($locales[$pid]) ? $pid : null,
            ]);
        }
    }

    /* ─────────────────────────────  Vista previa e importación  ───────────────────────────── */

    /**
     * Compara las órdenes con lo guardado. Con $aplicar = false solo calcula (vista
     * previa); con true además lo escribe, en una transacción. Las dos rutas usan este
     * mismo código, así que lo que se ve es lo que se guarda.
     *
     * @return array{
     *   filas: array<int, array>, conteo: array<string,int>, estatus_desconocidos: array<string,int>,
     *   ubicaciones_sin_reconocer: string[], coinciden_landing: int
     * }
     */
    public function procesar(array $ordenes, bool $aplicar = false, string $archivo = '', string $usuario = ''): array
    {
        $mapa    = $this->mapaProductos(array_keys($this->productosDeDropi($ordenes)));
        $locales = $this->productosLocales();
        $existentes = $this->ordenesExistentes(array_column($ordenes, 'id'));
        $landing    = $this->telefonosDeLanding();
        $ahora      = date('Y-m-d H:i:s');
        $previos    = null; // avisos ya enviados (se carga solo si hay órdenes nuevas)

        usort($ordenes, fn($a, $b) => [(string)$a['fecha'], (int)$a['id']] <=> [(string)$b['fecha'], (int)$b['id']]);

        $res = [
            'filas' => [], 'conteo' => ['nuevo' => 0, 'actualiza' => 0, 'igual' => 0],
            'estatus_desconocidos' => [], 'ubicaciones_sin_reconocer' => [], 'coinciden_landing' => 0,
        ];
        $iguales = [];

        $inicio = $aplicar && !$this->db->inTransaction();
        if ($inicio) $this->db->beginTransaction();

        try {
            foreach ($ordenes as $o) {
                $d  = $this->derivar($o, $mapa, $locales);
                $ex = $existentes[$o['id']] ?? null;

                if (!$d['ubicacion_ok']) $res['ubicaciones_sin_reconocer'][$d['municipio'] . ' (' . $d['departamento'] . ')'] = true;
                if (isset($landing[$this->sufijo($o['telefono'])])) $res['coinciden_landing']++;
                if ($d['estado'] === 'otro' && $o['estatus'] !== '' && !in_array(clave_color($o['estatus']), self::ESTATUS_QUIETOS, true)) {
                    $res['estatus_desconocidos'][$o['estatus']] = ($res['estatus_desconocidos'][$o['estatus']] ?? 0) + 1;
                }

                if (!$ex && $previos === null) $previos = $this->avisosPrevios();
                $plan = $this->planear($o, $d, $ex, $previos ?? [], $ahora);

                if ($aplicar) {
                    if ($plan['accion'] === 'nuevo' || $plan['accion'] === 'actualiza') {
                        $this->escribir($plan, $o, $ex, $mapa, $ahora);
                    } else {
                        $iguales[] = (int)$ex['id'];
                    }
                }

                $res['conteo'][$plan['accion']]++;
                $res['filas'][] = [
                    'id' => $o['id'], 'nombre' => trim($d['nombre'] . ' ' . $d['apellidos']), 'telefono' => $o['telefono'],
                    'producto' => ($d['producto'] ?? '') !== '' ? $d['producto'] : '—', 'ciudad' => $d['municipio'],
                    'estatus' => $o['estatus'], 'estado' => $d['estado'], 'accion' => $plan['accion'], 'detalle' => $plan['detalle'],
                ];
            }

            if ($aplicar) {
                // Las que no cambiaron también "se vieron" hoy: así se distingue lo vigente de lo que dejó de aparecer.
                foreach (array_chunk($iguales, 500) as $ids) {
                    $this->db->prepare('UPDATE dropi_ordenes SET visto_at = ? WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')')
                        ->execute(array_merge([$ahora], $ids));
                }
                (new DropiOrden())->registrarImportacion($usuario, $archivo, count($ordenes), $res['conteo']['nuevo'], $res['conteo']['actualiza'], $res['conteo']['igual']);
            }

            if ($inicio) $this->db->commit();
        } catch (\Throwable $e) {
            if ($inicio && $this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        $res['filas'] = array_reverse($res['filas']); // lo más reciente primero
        $res['ubicaciones_sin_reconocer'] = array_keys($res['ubicaciones_sin_reconocer']);
        return $res;
    }

    /**
     * Lo que quedaría guardado para esta orden: producto de la tienda, nombre y
     * apellido, departamento y municipio con el nombre de la lista de la tienda,
     * estado de Seguimiento y tipo de entrega por transportadora. 'producto' y
     * 'cantidad' son null cuando el archivo no trae productos (así no pisan lo que ya hay).
     */
    private function derivar(array $o, array $mapa, array $locales): array
    {
        $nombres = [];
        foreach ($o['lineas'] as $l) {
            if ($l['pid'] === '' && $l['nombre'] === '') continue;
            $pid = $mapa[$l['pid']] ?? null;
            $nombres[$pid !== null && isset($locales[$pid]) ? $locales[$pid]['nombre'] : $l['nombre']] = true;
        }

        [$nombre, $apellidos] = $this->partirNombre($this->titulo($o['nombre']));
        [$departamento, $municipio, $ubicacionOk] = $this->resolverUbicacion($o['departamento'], $o['ciudad']);

        $transportadora = clave_color($o['transportadora']);
        $tipo = $transportadora === '' ? '' : (strpos($transportadora, 'interrapid') !== false ? 'oficina' : 'domicilio');
        // Devolución: cualquier estatus de Dropi con esa raíz ("DEVOLUCION", "EN DEVOLUCION", "DEVUELTO"…).
        // Esas sí le cobran el flete de regreso al dueño, así que se guardan como estado propio.
        $claveEstatus = clave_color($o['estatus']);
        $estado = self::ESTADOS[$claveEstatus] ?? (preg_match('/devol|devuelt/', $claveEstatus) ? 'devolucion' : 'otro');

        // Novedad: la que reporta Dropi y, si no hay, el último movimiento cuando cuenta algo del cliente
        // (no tenía el dinero, no contestó, la dirección no existe). Lo demás del movimiento
        // (obstrucción en la vía, tránsito…) no es algo que el cliente pueda resolver.
        $novedad = $o['novedad'];
        if ($novedad === '' && $estado === 'enviado' && in_array(DropiOrden::novedadTipo($o['ultimo_mov']), ['pago', 'contacto', 'direccion'], true)) {
            $novedad = mb_substr($o['ultimo_mov'], 0, 255);
        }

        return [
            'nombre'             => $nombre,
            'apellidos'          => $apellidos,
            'telefono'           => $o['telefono'],
            'guia'               => mb_substr($o['guia'], 0, 50),
            'transportadora'     => mb_substr($o['transportadora'], 0, 40),
            'tipo_entrega'       => $tipo,
            'departamento'       => $departamento,
            'municipio'          => $municipio,
            'ubicacion_ok'       => $ubicacionOk ? 1 : 0,
            'estatus'            => mb_substr($o['estatus'], 0, 60),
            'estado'             => $estado,
            'ultimo_mov'         => $o['ultimo_mov'],
            'ultimo_mov_at'      => $o['ultimo_mov_at'],
            'novedad'            => $novedad,
            'novedad_tipo'       => $novedad !== '' ? DropiOrden::novedadTipo($novedad) : '',
            'motivo_cancelacion' => $o['cancelacion'],
            'producto'           => $nombres ? mb_substr(implode(' + ', array_keys($nombres)), 0, 190) : null,
            'cantidad'           => $o['cantidad'],
            'valor'              => $o['total'],
            'costo_proveedor'    => $o['costo'],
            'flete'              => $o['flete'],
            'flete_devolucion'   => $o['flete_dev'],
            'comision'           => $o['comision'],
            'ganancia'           => $o['ganancia'],
            'fecha_pedido'       => $o['fecha'],
            'ubicacion_ok_bool'  => $ubicacionOk,
        ];
    }

    /**
     * Decide qué hacer con una orden frente a lo ya guardado (nueva, cambió o igual)
     * y qué campos escribir.
     *
     * @return array{accion: string, detalle: string, campos: array<string,mixed>, avisos: array<string,string>}
     */
    private function planear(array $o, array $d, ?array $ex, array $previos, string $ahora): array
    {
        $campos = $d;
        unset($campos['ubicacion_ok_bool']);
        if ($campos['producto'] === null)  unset($campos['producto']);
        if ($campos['cantidad'] === null)  unset($campos['cantidad']);
        $campos['estatus_desde'] = $this->estatusDesde($d, $ex, $ahora);

        // Si la orden pasa a devolución ahora, se guarda por qué (una sola vez: no se recalcula mientras siga
        // devuelta). Se deduce del estado en que estaba y de su novedad, no de lo que diga Dropi después.
        if ($d['estado'] === 'devolucion' && (!$ex || (string)$ex['estado'] !== 'devolucion')) {
            $campos['devolucion_causa'] = DropiOrden::devolucionCausa($ex['estado'] ?? null, (string)($ex['novedad_tipo'] ?? ''), trim($d['novedad'] . ' ' . $d['ultimo_mov']));
        }

        if (!$ex) {
            // Avisos que ya se le habían enviado a este teléfono desde la fecha del pedido.
            $avisos = [];
            $porEstado = $previos[$this->sufijo($o['telefono'])] ?? [];
            foreach (DropiOrden::COLUMNA_AVISO as $mensaje => $col) {
                $cuando = $porEstado[$mensaje] ?? null;
                if ($cuando !== null && ($o['fecha'] === null || $cuando >= $o['fecha'])) $avisos[$col] = $cuando;
            }

            $detalle = 'Entra como ' . $this->etiqueta($d['estado']);
            if ($d['estado'] === 'en_oficina') $detalle .= isset($avisos['aviso_oficina_at']) ? ' · ya se le avisó' : ' · por avisar';
            return ['accion' => 'nuevo', 'detalle' => $detalle . '.', 'campos' => $campos + $avisos, 'avisos' => $avisos];
        }

        // Lo que el archivo no trae no borra lo que ya hay (p. ej. una guía escrita desde el compositor).
        foreach (['guia', 'tipo_entrega', 'transportadora'] as $k) {
            if ($campos[$k] === '') unset($campos[$k]);
        }

        $cambios = [];
        if (clave_color((string)$ex['estatus']) !== clave_color($d['estatus'])) {
            $cambios[] = 'Estatus: ' . ($ex['estatus'] !== '' ? $ex['estatus'] : '—') . ' → ' . ($d['estatus'] !== '' ? $d['estatus'] : '—');
        } elseif ($d['ultimo_mov'] !== '' && clave_color($d['ultimo_mov']) !== clave_color((string)$ex['ultimo_mov'])) {
            $cambios[] = 'Movimiento: ' . mb_substr($d['ultimo_mov'], 0, 40);
        }
        if ($d['guia'] !== '' && $d['guia'] !== (string)$ex['guia']) $cambios[] = $ex['guia'] === '' ? 'Guía' : 'Guía nueva';
        if ($d['novedad'] !== (string)$ex['novedad']) {
            $cambios[] = $d['novedad'] !== '' ? 'Novedad' : 'Novedad resuelta';
            $campos['aviso_novedad_at'] = null; // la novedad cambió: el aviso anterior ya no corresponde
        }
        if ($this->difiere($d, $ex, ['nombre', 'apellidos', 'telefono', 'municipio', 'departamento', 'producto', 'cantidad', 'tipo_entrega'])) $cambios[] = 'Datos del cliente';
        if ($this->difiere($d, $ex, ['valor', 'costo_proveedor', 'flete', 'flete_devolucion', 'comision', 'ganancia'])) $cambios[] = 'Valores';

        if (!$cambios && !$this->difiere($d, $ex, self::COMPARAR)) {
            return ['accion' => 'igual', 'detalle' => 'Sin cambios.', 'campos' => [], 'avisos' => []];
        }
        return ['accion' => 'actualiza', 'detalle' => $cambios ? implode(' · ', $cambios) : 'Datos de Dropi.', 'campos' => $campos, 'avisos' => []];
    }

    /** ¿Alguno de estos campos del archivo es distinto de lo guardado? (ignora los que el archivo no trae) */
    private function difiere(array $d, array $ex, array $claves): bool
    {
        foreach ($claves as $k) {
            if (!array_key_exists($k, $d) || $d[$k] === null && !in_array($k, ['ganancia'], true)) continue;
            $a = $d[$k];
            $b = $ex[$k] ?? null;
            if (in_array($k, self::NUMERICOS, true)) {
                if ($a === null || $b === null) { if ($a !== $b) return true; continue; }
                if (round((float)$a, 2) !== round((float)$b, 2)) return true;
            } elseif (mb_strtolower(trim((string)$a), 'UTF-8') !== mb_strtolower(trim((string)$b), 'UTF-8')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Desde cuándo está la orden en su estatus actual. Si el estatus no cambió se
     * conserva lo guardado. Si cambió (u orden nueva), es ahora, salvo que Dropi
     * traiga una fecha de movimiento anterior para "reclame en oficina" o "entregado".
     */
    private function estatusDesde(array $d, ?array $ex, string $ahora): string
    {
        if ($ex && !empty($ex['estatus_desde']) && clave_color((string)$ex['estatus']) === clave_color($d['estatus'])) {
            return (string)$ex['estatus_desde'];
        }

        $mov = $d['ultimo_mov_at'];
        if ($mov !== null && $mov < $ahora) {
            $clave = clave_color((string)$d['ultimo_mov']);
            if ($d['estado'] === 'en_oficina' && $clave === 'reclameenoficina') return $mov;
            if ($d['estado'] === 'entregado' && strpos($clave, 'entregad') === 0) return $mov;
        }
        return $ahora;
    }

    private function escribir(array $plan, array $o, ?array $ex, array $mapa, string $ahora): void
    {
        $campos = $plan['campos'];

        if (!$ex) {
            $campos['dropi_id']       = $o['id'];
            $campos['primera_vez_at'] = $ahora;
            $campos['visto_at']       = $ahora;
            $cols = array_keys($campos);
            $this->db->prepare('INSERT INTO dropi_ordenes (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')')->execute($campos);
            $ordenId = (int)$this->db->lastInsertId();
        } else {
            $campos['visto_at'] = $ahora;
            $sets = [];
            foreach (array_keys($campos) as $k) $sets[] = "{$k} = :{$k}";
            $campos['_id'] = (int)$ex['id'];
            $this->db->prepare('UPDATE dropi_ordenes SET ' . implode(', ', $sets) . ' WHERE id = :_id')->execute($campos);
            $ordenId = (int)$ex['id'];
        }

        // Las líneas se reemplazan (un archivo sin productos no toca las que ya hay).
        if (isset($campos['producto'])) {
            $this->db->prepare('DELETE FROM dropi_orden_lineas WHERE orden_id = ?')->execute([$ordenId]);
            $ins = $this->db->prepare('
                INSERT INTO dropi_orden_lineas (orden_id, dropi_product_id, producto, producto_id, variacion, cantidad, total, costo_proveedor)
                VALUES (:o, :dp, :p, :pid, :v, :c, :t, :k)
            ');
            foreach ($o['lineas'] as $l) {
                if ($l['pid'] === '' && $l['nombre'] === '') continue;
                $ins->execute([
                    ':o' => $ordenId, ':dp' => $l['pid'] !== '' ? $l['pid'] : null, ':p' => mb_substr($l['nombre'], 0, 190),
                    ':pid' => $mapa[$l['pid']] ?? null, ':v' => mb_substr($l['variacion'], 0, 100),
                    ':c' => (int)($l['cantidad'] ?? 1), ':t' => $l['total'], ':k' => $l['costo'],
                ]);
            }
        }
    }

    /* ───────────────────────────  Vista previa entre un paso y otro  ─────────────────────────── */

    /**
     * La vista previa y la confirmación son dos peticiones: lo leído del archivo se
     * deja unos minutos en un temporal (solo lo que la importación usa) y se
     * identifica con un token que también queda en la sesión de quien lo subió.
     */
    public function guardarTemporal(array $lectura): string
    {
        $this->limpiarTemporales();
        $token = bin2hex(random_bytes(16));
        $ruta  = $this->rutaTemporal($token);
        file_put_contents($ruta, json_encode($lectura, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), LOCK_EX);
        @chmod($ruta, 0600);
        return $token;
    }

    public function leerTemporal(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) return null;
        $ruta = $this->rutaTemporal($token);
        if (!is_file($ruta) || filemtime($ruta) < time() - 7200) return null;

        $lectura = json_decode((string)file_get_contents($ruta), true);
        return is_array($lectura) ? $lectura : null;
    }

    public function borrarTemporal(string $token): void
    {
        if (preg_match('/^[a-f0-9]{32}$/', $token)) @unlink($this->rutaTemporal($token));
    }

    private function limpiarTemporales(): void
    {
        foreach (glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dropi_import_*.json') ?: [] as $f) {
            if (filemtime($f) < time() - 21600) @unlink($f);
        }
    }

    private function rutaTemporal(string $token): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dropi_import_' . $token . '.json';
    }

    /* ─────────────────────────────────  Utilidades  ───────────────────────────────── */

    /** @return array<string, array> órdenes ya guardadas, por ID de Dropi (como texto) */
    private function ordenesExistentes(array $dropiIds): array
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique(array_map('strval', $dropiIds))), 500) as $ids) {
            $stmt = $this->db->prepare('SELECT * FROM dropi_ordenes WHERE dropi_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
            $stmt->execute($ids);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $out[(string)$r['dropi_id']] = $r;
        }
        return $out;
    }

    /**
     * Mensajes ya enviados por teléfono, del registro del compositor anterior:
     * [últimos 8 dígitos][plantilla] = primera vez que se envió.
     *
     * @return array<string, array<string, string>>
     */
    private function avisosPrevios(): array
    {
        $out = [];
        try {
            new WaMensajeLog(); // crea la tabla si todavía no existe
            $q = $this->db->query('SELECT telefono, estado_usado, MIN(created_at) AS cuando FROM wa_mensajes_log GROUP BY telefono, estado_usado');
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $suf = $this->sufijo((string)$r['telefono']);
                if (!isset($out[$suf][$r['estado_usado']]) || $r['cuando'] < $out[$suf][$r['estado_usado']]) {
                    $out[$suf][$r['estado_usado']] = (string)$r['cuando'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[DropiImport] avisosPrevios: ' . $e->getMessage());
        }
        return $out;
    }

    private function telefonosDeLanding(): array
    {
        $out = [];
        try {
            foreach ($this->db->query('SELECT telefono FROM pedidos')->fetchAll(PDO::FETCH_COLUMN) as $t) {
                $out[$this->sufijo((string)$t)] = true;
            }
        } catch (\PDOException $e) {
            error_log('[DropiImport] pedidos: ' . $e->getMessage());
        }
        return $out;
    }

    private function sufijo(string $telefono): string
    {
        return substr((string)preg_replace('/\D+/', '', $telefono), -8);
    }

    /** Solo dígitos y sin el prefijo de país (mismo criterio que LandingController::normalizarTelefono). */
    private function normalizarTelefono(string $raw): string
    {
        $digitos = (string)preg_replace('/\D+/', '', $raw);
        if (preg_match('/^(?:00)?57(3\d{9})$/', $digitos, $m)) return $m[1];
        return $digitos;
    }

    /** Mismo criterio que LandingController::partirNombreCompleto: 2 palabras → 1+1, 3 → 1+2, 4 o más → 2+resto. */
    private function partirNombre(string $completo): array
    {
        $partes = preg_split('/\s+/u', trim($completo), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $n = count($partes);
        if ($n <= 1) return [$partes[0] ?? '', ''];

        $corte = ($n >= 4) ? 2 : 1;
        return [implode(' ', array_slice($partes, 0, $corte)), implode(' ', array_slice($partes, $corte))];
    }

    private function titulo(string $s): string
    {
        return mb_convert_case(mb_strtolower(trim($s), 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    private function etiqueta(string $estado): string
    {
        return self::ETIQUETAS[$estado] ?? $estado;
    }

    /** "109000", "37010.75", "109.000", "$ 109.000,50" → float. */
    private function numero(string $s): float
    {
        $s = trim(str_replace(['$', ' '], '', $s));
        if ($s === '') return 0.0;
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        } elseif (strpos($s, ',') !== false && strpos($s, '.') === false) {
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }
        return is_numeric($s) ? (float)$s : 0.0;
    }

    /** "23-10-2026" + "09:57" → "2026-10-23 09:57:00" (hora de Colombia, como la escribe Dropi). */
    private function fechaHora(string $fecha, string $hora): ?string
    {
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $fecha, $m)) {
            [$d, $mes, $a] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $fecha, $m)) {
            [$a, $mes, $d] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        } else {
            return null;
        }
        if (!checkdate($mes, $d, $a)) return null;

        $h = preg_match('/^(\d{1,2}):(\d{2})/', $hora, $t) && (int)$t[1] < 24 && (int)$t[2] < 60
            ? sprintf('%02d:%02d', (int)$t[1], (int)$t[2])
            : '00:00';
        return sprintf('%04d-%02d-%02d %s:00', $a, $mes, $d, $h);
    }

    /**
     * Departamento y municipio de Dropi ("VALLE" / "EL CARMEN DE BOLIVAR") con el
     * nombre que usa la lista de la tienda ("Valle del Cauca" / "El Carmen de Bolívar"),
     * que es el que reconocen los selectores de Seguimiento. Si no se reconoce se
     * conserva lo de Dropi en formato título y se avisa.
     *
     * @return array{0: string, 1: string, 2: bool} [departamento, municipio, reconocido]
     */
    private function resolverUbicacion(string $departamento, string $ciudad): array
    {
        self::$ubicaciones ??= require __DIR__ . '/../data/colombia.php';
        $ub = self::$ubicaciones;

        $kd = clave_color($departamento);
        $dep = null;
        if ($kd !== '') {
            foreach (array_keys($ub) as $nombre) {
                if (clave_color($nombre) === $kd) { $dep = $nombre; break; }
            }
            if ($dep === null && isset(self::ALIAS_DEPARTAMENTO[$kd]) && isset($ub[self::ALIAS_DEPARTAMENTO[$kd]])) {
                $dep = self::ALIAS_DEPARTAMENTO[$kd];
            }
            if ($dep === null) { // "VALLE" → "Valle del Cauca", solo si no hay duda
                $candidatos = array_filter(array_keys($ub), fn($n) => strpos(clave_color($n), $kd) === 0);
                if (count($candidatos) === 1) $dep = reset($candidatos);
            }
        }

        $mun = null;
        if ($dep !== null) {
            $claves = $this->clavesMunicipio($ciudad);
            foreach ($claves as $kc) {
                $alias = self::ALIAS_MUNICIPIO[clave_color($dep) . '|' . $kc] ?? null;
                foreach ($ub[$dep] as $m) {
                    if (clave_color($m) === $kc || $m === $alias) { $mun = $m; break 2; }
                }
            }
            if ($mun === null) { // "Cartagena" → "Cartagena de Indias", solo si es el único del departamento que empieza así
                foreach ($claves as $kc) {
                    $candidatos = array_values(array_filter($ub[$dep], fn($m) => strpos(clave_color($m), $kc . 'de') === 0));
                    if (count($candidatos) === 1) { $mun = $candidatos[0]; break; }
                }
            }
        }

        $ok = $dep !== null && $mun !== null;
        return [$dep ?? $this->titulo($departamento), $mun ?? $this->titulo($ciudad), $ok];
    }

    /**
     * Formas en que se puede buscar un municipio de Dropi en la lista de la tienda, de la más
     * literal a la más suelta: como viene, sin la marca entre paréntesis ("Granada (M)" es el de
     * Meta, "Sucre (S)" el de Sucre) y cada parte cuando Dropi junta dos ("La Apartada Y La Frontera").
     *
     * @return string[] claves (clave_color)
     */
    private function clavesMunicipio(string $ciudad): array
    {
        $limpio = trim((string)preg_replace('/\s*\([^)]*\)/u', '', $ciudad));
        $claves = [clave_color($ciudad), clave_color($limpio)];
        foreach (preg_split('/\s+y\s+/iu', $limpio) ?: [] as $parte) $claves[] = clave_color($parte);
        return array_values(array_unique(array_filter($claves, fn($k) => $k !== '')));
    }
}
