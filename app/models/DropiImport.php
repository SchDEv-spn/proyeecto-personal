<?php

/**
 * Importación de órdenes desde la exportación a Excel de Dropi ("Mis órdenes →
 * Descargar"). Es la salida práctica mientras la API de Dropi no devuelva órdenes:
 * el pedido se escribe UNA vez en Dropi y de ahí pasa a Seguimiento con su
 * estatus y su guía.
 *
 * Cada orden se guarda como contacto de Seguimiento (contactos_manuales), que es
 * un registro por teléfono: si un cliente tiene dos órdenes se queda la más
 * reciente. Un mismo archivo se puede importar las veces que se quiera: cada
 * orden se reconoce por su ID de Dropi y solo se toca lo que cambió.
 *
 * Reglas que conviene no romper:
 *  - El estado de Seguimiento solo se mueve cuando Dropi cambió el estatus desde la
 *    última importación. Si no, se respeta lo que hizo la vendedora (por ejemplo,
 *    avisar "en oficina" antes de que Dropi lo refleje).
 *  - Nunca se marca a nadie como "avisado" (notificado_oficina_at): eso lo hace el
 *    envío real del mensaje desde Seguimiento.
 *  - De la exportación solo se conserva lo que Seguimiento usa. Direcciones,
 *    documentos y correos del archivo no se guardan ni se copian a ningún lado.
 */
class DropiImport extends Model
{
    /** Estatus de Dropi (comparados con clave_color) → estado de Seguimiento. */
    private const ESTADOS = [
        'guiagenerada'            => 'confirmado',
        'enesperaderutadomestica' => 'confirmado',
        'enprocesamiento'         => 'confirmado',
        'enbodegatransportadora'  => 'enviado',
        'despachada'              => 'enviado',
        'enreparto'               => 'enviado',
        'reclameenoficina'        => 'en_oficina',
        'entregado'               => 'entregado',
        'cancelado'               => 'cancelado',
    ];

    public const ETIQUETAS = [
        'nuevo'      => 'Nuevo',
        'contactado' => 'Contactado',
        'confirmado' => 'Confirmado',
        'enviado'    => 'Enviado',
        'en_oficina' => 'En oficina',
        'entregado'  => 'Entregado',
        'cancelado'  => 'Cancelado',
    ];

    /** Nombres de departamento que Dropi escribe distinto a la lista de la tienda. */
    private const ALIAS_DEPARTAMENTO = [
        'nortesantander' => 'Norte de Santander',
        'guajira'        => 'La Guajira',
    ];

    /** Municipios que Dropi nombra distinto: "departamento|municipio" (con clave_color) → nombre en la lista de la tienda. */
    private const ALIAS_MUNICIPIO = [
        'cauca|elbordo' => 'Patía', // El Bordo es la cabecera del municipio de Patía
    ];

    private static ?array $ubicaciones = null;

    public function __construct()
    {
        parent::__construct();

        // Crea contactos_manuales y sus columnas dropi_* si todavía no existen.
        new ContactoManual();

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dropi_productos (
                dropi_product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                dropi_nombre     VARCHAR(190) NOT NULL DEFAULT '',
                producto_id      INT NULL,
                updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
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

            $linea = [
                'pid'      => (string)preg_replace('/\D+/', '', $g('productoid')),
                'nombre'   => $g('producto'),
                'variacion'=> $g('variacion'),
                'cantidad' => isset($cab['cantidad']) ? max(1, (int)$this->numero($g('cantidad'))) : null,
                // Con productos, "TOTAL DE LA ORDEN" viene por línea; sin productos, la orden es una sola fila.
                'total'    => $this->numero($g('totaldelaorden') !== '' ? $g('totaldelaorden') : $g('valordecompraenproductos')),
            ];

            if (!isset($porId[$id])) {
                $solucionada = clave_color($g('fuesolucionadalanovedad')) === 'si';
                $porId[$id] = [
                    'id'            => $id,
                    'fecha'         => $this->fechaHora($g('fecha'), $g('hora')),
                    'nombre'        => $g('nombrecliente'),
                    'telefono'      => $telefono,
                    'guia'          => $g('numeroguia'),
                    'estatus'       => mb_strtoupper($g('estatus'), 'UTF-8'),
                    'departamento'  => $g('departamentodestino'),
                    'ciudad'        => $g('ciudaddestino'),
                    'transportadora'=> $g('transportadora'),
                    // Solo importa la novedad que sigue abierta.
                    'novedad'       => $solucionada ? '' : mb_substr($g('novedad'), 0, 255),
                    'lineas'        => [],
                ];
            }
            $porId[$id]['lineas'][] = $linea;
        }

        $ordenes = [];
        foreach ($porId as $o) {
            $o['total']    = array_sum(array_column($o['lineas'], 'total'));
            $cantidades    = array_column($o['lineas'], 'cantidad');
            $o['cantidad'] = in_array(null, $cantidades, true) ? null : array_sum($cantidades);
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
            ON DUPLICATE KEY UPDATE dropi_nombre = VALUES(dropi_nombre), producto_id = VALUES(producto_id)
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
     * Compara las órdenes con los contactos de Seguimiento. Con $aplicar = false solo
     * calcula (vista previa); con true además lo escribe, en una transacción. Las dos
     * rutas usan este mismo código, así que lo que se ve es lo que se guarda.
     *
     * @return array{
     *   filas: array<int, array>, conteo: array<string,int>, estatus_desconocidos: array<string,int>,
     *   ubicaciones_sin_reconocer: string[], en_oficina_sin_aviso: array<int, array>,
     *   novedades: array<int, array>, coinciden_landing: int
     * }
     */
    public function procesar(array $ordenes, bool $aplicar = false): array
    {
        $mapa    = $this->mapaProductos(array_keys($this->productosDeDropi($ordenes)));
        $locales = $this->productosLocales();
        $existentes = $this->contactosPorSufijo();
        $landing    = $this->telefonosDeLanding();

        // La más reciente de cada teléfono es la que se guarda (un contacto por teléfono).
        usort($ordenes, fn($a, $b) => [(string)$a['fecha'], (int)$a['id']] <=> [(string)$b['fecha'], (int)$b['id']]);
        $ultimaDe = [];
        foreach ($ordenes as $i => $o) $ultimaDe[$this->sufijo($o['telefono'])] = $i;

        $res = [
            'filas' => [], 'conteo' => ['nuevo' => 0, 'actualiza' => 0, 'igual' => 0, 'omitida' => 0],
            'estatus_desconocidos' => [], 'ubicaciones_sin_reconocer' => [],
            'en_oficina_sin_aviso' => [], 'novedades' => [], 'coinciden_landing' => 0,
        ];

        $inicio = $aplicar && !$this->db->inTransaction();
        if ($inicio) $this->db->beginTransaction();

        try {
            foreach ($ordenes as $i => $o) {
                $suf = $this->sufijo($o['telefono']);
                $d   = $this->derivar($o, $mapa, $locales);
                $fila = [
                    'id' => $o['id'], 'nombre' => trim($d['nombre'] . ' ' . $d['apellidos']), 'telefono' => $o['telefono'],
                    'producto' => $d['producto'] !== '' ? $d['producto'] : '—', 'ciudad' => $d['municipio'],
                    'estatus' => $o['estatus'], 'estado' => '', 'accion' => 'omitida', 'detalle' => '',
                ];

                if (($ultimaDe[$suf] ?? $i) !== $i) {
                    $fila['detalle'] = 'Este cliente tiene una orden más reciente en el archivo.';
                    $this->sumar($res, $fila);
                    continue;
                }

                if (!$d['ubicacion_ok']) $res['ubicaciones_sin_reconocer'][$d['municipio'] . ' (' . $d['departamento'] . ')'] = true;
                if (isset($landing[$suf])) $res['coinciden_landing']++;

                $claveEstatus = clave_color($o['estatus']);
                $estadoDropi  = self::ESTADOS[$claveEstatus] ?? null;
                if ($estadoDropi === null && $claveEstatus !== 'novedad' && $claveEstatus !== '') {
                    $res['estatus_desconocidos'][$o['estatus']] = ($res['estatus_desconocidos'][$o['estatus']] ?? 0) + 1;
                }

                $ex = $existentes[$suf] ?? null;
                $plan = $this->planear($o, $d, $ex, $estadoDropi);

                $fila['estado'] = $plan['estado'];
                $fila['accion'] = $plan['accion'];
                $fila['detalle'] = $plan['detalle'];

                if ($aplicar && in_array($plan['accion'], ['nuevo', 'actualiza'], true)) {
                    try {
                        $this->escribir($plan, $o['telefono'], $ex);
                    } catch (\PDOException $e) {
                        error_log('[DropiImport] orden ' . $o['id'] . ': ' . $e->getMessage());
                        $fila['accion']  = 'omitida';
                        $fila['detalle'] = 'No se pudo guardar esta orden.';
                    }
                }

                // Listas para actuar al terminar: ¿a quién falta avisarle que su pedido está en oficina?
                if ($fila['accion'] !== 'omitida') {
                    $avisado = $ex && !$plan['reiniciar_aviso'] && !empty($ex['notificado_oficina_at']);
                    if ($plan['estado'] === 'en_oficina' && !$avisado) {
                        $res['en_oficina_sin_aviso'][] = ['nombre' => $fila['nombre'], 'telefono' => $o['telefono'], 'ciudad' => $d['municipio'], 'producto' => $fila['producto']];
                    }
                    if ($d['novedad'] !== '') {
                        $res['novedades'][] = ['nombre' => $fila['nombre'], 'telefono' => $o['telefono'], 'ciudad' => $d['municipio'], 'novedad' => $d['novedad']];
                    }
                }

                $this->sumar($res, $fila);
            }

            if ($inicio) $this->db->commit();
        } catch (\Throwable $e) {
            if ($inicio && $this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        // Lo más reciente primero.
        $res['filas'] = array_reverse($res['filas']);
        $res['ubicaciones_sin_reconocer'] = array_keys($res['ubicaciones_sin_reconocer']);
        return $res;
    }

    private function sumar(array &$res, array $fila): void
    {
        $res['conteo'][$fila['accion']]++;
        $res['filas'][] = $fila;
    }

    /**
     * Lo que quedaría guardado para esta orden: campos de Seguimiento ya resueltos
     * (producto de la tienda, nombre y apellido, departamento y municipio con el
     * nombre de la lista de la tienda, tipo de entrega por transportadora).
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

        return [
            'nombre'       => $nombre,
            'apellidos'    => $apellidos,
            'producto'     => implode(' + ', array_keys($nombres)),
            'cantidad'     => $o['cantidad'] !== null ? (string)$o['cantidad'] : '',
            'precio'       => $o['total'] > 0 ? '$' . number_format($o['total'], 0, ',', '.') : '',
            'departamento' => $departamento,
            'municipio'    => $municipio,
            'ubicacion_ok' => $ubicacionOk,
            'tipo_entrega' => $tipo,
            'guia'         => $o['guia'],
            'novedad'      => $o['novedad'],
        ];
    }

    /**
     * Decide qué hacer con una orden frente al contacto que ya exista con ese teléfono.
     *
     * @return array{accion: string, estado: string, detalle: string, campos: array<string,mixed>, reiniciar_aviso: bool}
     */
    private function planear(array $o, array $d, ?array $ex, ?string $estadoDropi): array
    {
        $esNovedad = clave_color($o['estatus']) === 'novedad';
        $mismaOrden = $ex && (string)($ex['dropi_order_id'] ?? '') === $o['id'];
        $ordenDistinta = $ex && !empty($ex['dropi_order_id']) && !$mismaOrden;

        // El contacto ya tiene una orden de Dropi más nueva que esta: no se pisa con una vieja.
        if ($ordenDistinta && $o['fecha'] !== null && !empty($ex['dropi_fecha']) && $o['fecha'] < $ex['dropi_fecha']) {
            return ['accion' => 'omitida', 'estado' => (string)$ex['estado'], 'detalle' => 'Este cliente ya tiene una orden de Dropi más reciente guardada.', 'campos' => [], 'reiniciar_aviso' => false];
        }

        // Estado de Seguimiento.
        if (!$ex || $ordenDistinta) {
            // Contacto nuevo, o una orden nueva del mismo cliente: el estado viejo era de otro paquete.
            $estado = $estadoDropi ?? ($esNovedad ? 'enviado' : 'confirmado');
        } elseif ($mismaOrden) {
            // Misma orden: solo se mueve si Dropi cambió su estatus desde la última importación.
            $cambioDropi = clave_color((string)$ex['dropi_estatus']) !== clave_color($o['estatus']);
            $estado = ($cambioDropi && $estadoDropi !== null) ? $estadoDropi : (string)$ex['estado'];
        } else {
            // Contacto de WhatsApp sin orden de Dropi todavía: Dropi sabe más del envío.
            $estado = $estadoDropi ?? (string)$ex['estado'];
        }

        // Campos nuevos; lo que Dropi no trae (archivo sin productos, sin transportadora…) no borra lo que ya hay.
        $campos = ['estado' => $estado];
        foreach (['nombre', 'apellidos', 'producto', 'cantidad', 'precio', 'municipio', 'departamento', 'tipo_entrega'] as $k) {
            if ($d[$k] !== '') $campos[$k] = $d[$k];
        }
        if ($d['guia'] !== '') $campos['numero_guia'] = $d['guia'];
        $campos['dropi_order_id'] = $o['id'];
        $campos['dropi_estatus']  = mb_substr($o['estatus'], 0, 60);
        $campos['dropi_novedad']  = $d['novedad'];
        $campos['dropi_fecha']    = $o['fecha'];

        if (!$ex) {
            return ['accion' => 'nuevo', 'estado' => $estado, 'detalle' => 'Entra como ' . $this->etiqueta($estado) . '.', 'campos' => $campos, 'reiniciar_aviso' => false];
        }

        // ¿Qué cambia respecto a lo guardado?
        $cambios = [];
        if ($estado !== (string)$ex['estado']) {
            $cambios[] = 'Estado: ' . $this->etiqueta((string)$ex['estado']) . ' → ' . $this->etiqueta($estado);
        }
        if (isset($campos['numero_guia']) && $campos['numero_guia'] !== (string)$ex['numero_guia']) {
            $cambios[] = $ex['numero_guia'] === '' ? 'Guía' : 'Guía nueva';
        }
        foreach (['nombre', 'apellidos', 'producto', 'cantidad', 'precio', 'municipio', 'departamento', 'tipo_entrega'] as $k) {
            if (isset($campos[$k]) && mb_strtolower($campos[$k], 'UTF-8') !== mb_strtolower(trim((string)$ex[$k]), 'UTF-8')) {
                $cambios[] = 'Datos del cliente';
                break;
            }
        }
        if ($d['novedad'] !== (string)($ex['dropi_novedad'] ?? '')) {
            $cambios[] = $d['novedad'] !== '' ? 'Novedad' : 'Novedad resuelta';
        }
        if (!$mismaOrden) {
            array_unshift($cambios, $ordenDistinta ? 'Orden nueva de este cliente' : 'Se vincula con su orden de Dropi');
        } elseif ($o['estatus'] !== (string)$ex['dropi_estatus'] && !$cambios) {
            $cambios[] = 'Estatus de Dropi: ' . $o['estatus'];
        }

        if (!$cambios) {
            return ['accion' => 'igual', 'estado' => $estado, 'detalle' => 'Sin cambios.', 'campos' => [], 'reiniciar_aviso' => false];
        }
        return ['accion' => 'actualiza', 'estado' => $estado, 'detalle' => implode(' · ', $cambios), 'campos' => $campos, 'reiniciar_aviso' => $ordenDistinta];
    }

    private function escribir(array $plan, string $telefono, ?array $ex): void
    {
        $campos = $plan['campos'];

        if (!$ex) {
            $campos['telefono']       = $telefono;
            $campos['usuario_nombre'] = 'Importado de Dropi';
            $cols = array_keys($campos);
            $this->db->prepare(
                'INSERT INTO contactos_manuales (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
            )->execute($campos);
            return;
        }

        $sets = [];
        foreach (array_keys($campos) as $k) $sets[] = "{$k} = :{$k}";
        if ($plan['reiniciar_aviso']) $sets[] = 'notificado_oficina_at = NULL';

        $campos['_id'] = (int)$ex['id'];
        $this->db->prepare('UPDATE contactos_manuales SET ' . implode(', ', $sets) . ' WHERE id = :_id')->execute($campos);
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

    /** Contactos de Seguimiento por los últimos 8 dígitos del teléfono (mismo criterio que buscarPorTelefono). */
    private function contactosPorSufijo(): array
    {
        $out = [];
        foreach ($this->db->query('SELECT * FROM contactos_manuales ORDER BY updated_at ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $out[$this->sufijo((string)$c['telefono'])] = $c; // el más reciente gana
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
        $kc = clave_color($ciudad);
        if ($dep !== null && $kc !== '') {
            $alias = self::ALIAS_MUNICIPIO[clave_color($dep) . '|' . $kc] ?? null;
            foreach ($ub[$dep] as $m) {
                if (clave_color($m) === $kc || $m === $alias) { $mun = $m; break; }
            }
        }

        $ok = $dep !== null && $mun !== null;
        return [$dep ?? $this->titulo($departamento), $mun ?? $this->titulo($ciudad), $ok];
    }
}
