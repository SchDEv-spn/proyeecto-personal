<?php

/**
 * Órdenes de Dropi traídas de su exportación a Excel (ver DropiImport): UNA fila
 * por orden (clave: el ID de Dropi) y sus líneas de producto en otra tabla. Es la
 * base de Seguimiento — qué pedidos hay que avisar, cuáles esperan en la oficina,
 * cuáles tienen novedad — y quedan guardadas para consultar el historial y, más
 * adelante, alimentar el dashboard (trae valor, costo, flete, comisión, ganancia).
 *
 * Lo que la vendedora hace (avisar al cliente) no cambia el estado de la orden:
 * el estado sale siempre de Dropi. Cada aviso queda en su propia marca de tiempo
 * (aviso_*_at), así una orden sabe qué mensajes ya recibió su cliente.
 */
class DropiOrden extends Model
{
    /** Vistas de Seguimiento. */
    public const VISTAS = ['por_avisar', 'esperando', 'novedad', 'en_camino', 'entregados', 'devoluciones', 'todas'];

    /** Mensaje (plantilla de Seguimiento) → columna donde se anota que ya se envió. */
    public const COLUMNA_AVISO = [
        'enviado'              => 'aviso_enviado_at',
        'en_reparto'           => 'aviso_reparto_at',
        'en_oficina'           => 'aviso_oficina_at',
        'recordatorio_oficina' => 'aviso_recordatorio_at',
        'ultimo_aviso_oficina' => 'aviso_ultimo_at',
        'novedad'              => 'aviso_novedad_at',
        'entregado'            => 'aviso_entregado_at',
    ];

    /** Estados en los que la orden ya no tiene entrega pendiente: una novedad ahí no se atiende. */
    private const ESTADOS_CERRADOS = ['entregado', 'cancelado', 'devolucion'];

    /** Días en oficina desde los que toca cada recordatorio. */
    public const DIAS_RECORDATORIO = 2;
    public const DIAS_ULTIMO_AVISO = 4;

    /**
     * Interrapidísimo devuelve el paquete a los 5 días hábiles de que llega a la
     * oficina. La fecha que se le da al cliente es 4 días hábiles después de la llegada:
     * un día de margen, porque la llegada que registra Dropi puede estar corrida hasta
     * un día. No se cuentan festivos (así el plazo, si algo, se queda corto y no largo).
     */
    private const DIAS_HABILES_PLAZO = 4;

    /** Por qué se devolvió un pedido, tal como se muestra. */
    public const CAUSAS_DEVOLUCION = [
        'no_recogio'    => 'No recogió en la oficina',
        'pago'          => 'No tenía el dinero',
        'direccion'     => 'No se encontró la dirección',
        'contacto'      => 'No contestó',
        'coordinar'     => 'No coordinó la entrega',
        'recoger'       => 'No fue a la bodega',
        'rechazo'       => 'Rechazó el pedido',
        'indeterminada' => 'Causa sin determinar',
    ];

    /** Tipos de novedad y cómo se llaman en pantalla. */
    public const NOVEDAD_ETIQUETAS = [
        'direccion' => 'Dirección',
        'contacto'  => 'Sin contacto',
        'coordinar' => 'Coordinar entrega',
        'pago'      => 'Pago',
        'recoger'   => 'Recoger en bodega',
        'rechazo'   => 'Rechazo',
        'otra'      => 'Otra',
    ];

    /** Una orden que no aparece en las últimas importaciones se da por "sin actualizar". */
    private const DIAS_SIN_ACTUALIZAR = 3;

    private static bool $listo = false;

    /**
     * @param bool $crearTablas  false en rutas que no deben tocar el esquema (el pedido de la
     *        landing solo consulta el riesgo del cliente: si las tablas no existen todavía,
     *        las consultas lo toleran y devuelven vacío).
     */
    public function __construct(bool $crearTablas = true)
    {
        parent::__construct();
        if ($crearTablas) $this->crearTablas();
    }

    private function crearTablas(): void
    {
        if (self::$listo) return;
        self::$listo = true;

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dropi_ordenes (
                id                    INT AUTO_INCREMENT PRIMARY KEY,
                dropi_id              BIGINT UNSIGNED NOT NULL,
                fecha_pedido          DATETIME NULL,
                nombre                VARCHAR(150) NOT NULL DEFAULT '',
                apellidos             VARCHAR(150) NOT NULL DEFAULT '',
                telefono              VARCHAR(20)  NOT NULL DEFAULT '',
                guia                  VARCHAR(50)  NOT NULL DEFAULT '',
                transportadora        VARCHAR(40)  NOT NULL DEFAULT '',
                tipo_entrega          VARCHAR(10)  NOT NULL DEFAULT '',
                departamento          VARCHAR(100) NOT NULL DEFAULT '',
                municipio             VARCHAR(100) NOT NULL DEFAULT '',
                ubicacion_ok          TINYINT(1)   NOT NULL DEFAULT 1,
                estatus               VARCHAR(60)  NOT NULL DEFAULT '',
                estado                VARCHAR(20)  NOT NULL DEFAULT 'otro',
                estatus_desde         DATETIME NULL,
                ultimo_mov            VARCHAR(120) NOT NULL DEFAULT '',
                ultimo_mov_at         DATETIME NULL,
                novedad               VARCHAR(255) NOT NULL DEFAULT '',
                novedad_tipo          VARCHAR(20)  NOT NULL DEFAULT '',
                motivo_cancelacion    VARCHAR(255) NOT NULL DEFAULT '',
                devolucion_causa      VARCHAR(20)  NOT NULL DEFAULT '',
                devolucion_exenta     TINYINT(1)   NOT NULL DEFAULT 0,
                devolucion_nota       VARCHAR(255) NOT NULL DEFAULT '',
                producto              VARCHAR(190) NOT NULL DEFAULT '',
                cantidad              INT NOT NULL DEFAULT 1,
                valor                 DECIMAL(12,2) NOT NULL DEFAULT 0,
                costo_proveedor       DECIMAL(12,2) NOT NULL DEFAULT 0,
                flete                 DECIMAL(12,2) NOT NULL DEFAULT 0,
                flete_devolucion      DECIMAL(12,2) NOT NULL DEFAULT 0,
                comision              DECIMAL(12,2) NOT NULL DEFAULT 0,
                ganancia              DECIMAL(12,2) NULL,
                aviso_enviado_at      DATETIME NULL,
                aviso_reparto_at      DATETIME NULL,
                aviso_novedad_at      DATETIME NULL,
                aviso_oficina_at      DATETIME NULL,
                aviso_recordatorio_at DATETIME NULL,
                aviso_ultimo_at       DATETIME NULL,
                aviso_entregado_at    DATETIME NULL,
                primera_vez_at        DATETIME NOT NULL,
                visto_at              DATETIME NOT NULL,
                UNIQUE KEY uq_dropi_id (dropi_id),
                KEY idx_telefono (telefono),
                KEY idx_estado (estado),
                KEY idx_fecha (fecha_pedido)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dropi_orden_lineas (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                orden_id         INT NOT NULL,
                dropi_product_id BIGINT UNSIGNED NULL,
                producto         VARCHAR(190) NOT NULL DEFAULT '',
                producto_id      INT NULL,
                variacion        VARCHAR(100) NOT NULL DEFAULT '',
                cantidad         INT NOT NULL DEFAULT 1,
                total            DECIMAL(12,2) NOT NULL DEFAULT 0,
                costo_proveedor  DECIMAL(12,2) NOT NULL DEFAULT 0,
                KEY idx_orden (orden_id),
                KEY idx_producto (producto_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dropi_productos (
                dropi_product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                dropi_nombre     VARCHAR(190) NOT NULL DEFAULT '',
                producto_id      INT NULL,
                sin_equivalente  TINYINT(1) NOT NULL DEFAULT 0,
                updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS clientes_riesgo (
                id        INT AUTO_INCREMENT PRIMARY KEY,
                sufijo    CHAR(8)      NOT NULL,
                telefono  VARCHAR(20)  NOT NULL DEFAULT '',
                nombre    VARCHAR(150) NOT NULL DEFAULT '',
                nota      VARCHAR(255) NOT NULL DEFAULT '',
                creado_en DATETIME     NOT NULL,
                UNIQUE KEY uq_sufijo (sufijo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS dropi_importaciones (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                creado_en    DATETIME NOT NULL,
                usuario      VARCHAR(150) NOT NULL DEFAULT '',
                archivo      VARCHAR(120) NOT NULL DEFAULT '',
                ordenes      INT NOT NULL DEFAULT 0,
                nuevas       INT NOT NULL DEFAULT 0,
                actualizadas INT NOT NULL DEFAULT 0,
                sin_cambios  INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->agregarColumnasNuevas();
    }

    /**
     * Columnas que se añadieron después de crear la tabla por primera vez (en una
     * instalación que ya la tiene, CREATE TABLE IF NOT EXISTS no las agrega). Un solo
     * SHOW COLUMNS y ALTER solo de las que faltan, como ContactoManual.
     */
    private function agregarColumnasNuevas(): void
    {
        $nuevas = [
            'novedad_tipo'     => "VARCHAR(20) NOT NULL DEFAULT '' AFTER novedad",
            'aviso_reparto_at' => 'DATETIME NULL AFTER aviso_enviado_at',
            'aviso_novedad_at' => 'DATETIME NULL AFTER aviso_reparto_at',
            'devolucion_causa'  => "VARCHAR(20) NOT NULL DEFAULT ''",
            'devolucion_exenta' => 'TINYINT(1) NOT NULL DEFAULT 0',
            'devolucion_nota'   => "VARCHAR(255) NOT NULL DEFAULT ''",
        ];

        try {
            $existentes = array_column($this->db->query('SHOW COLUMNS FROM dropi_ordenes')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (\PDOException $e) {
            return;
        }

        foreach ($nuevas as $col => $def) {
            if (in_array($col, $existentes, true)) continue;
            try {
                $this->db->exec("ALTER TABLE dropi_ordenes ADD COLUMN {$col} {$def}");
            } catch (\PDOException $e) {
                // 1060 = la columna ya existe (otra petición la acaba de añadir).
            }
        }

        // dropi_productos: "no lo tengo en mi tienda" (para que el aviso de emparejar no insista).
        try {
            $mapa = array_column($this->db->query('SHOW COLUMNS FROM dropi_productos')->fetchAll(PDO::FETCH_ASSOC), 'Field');
            if (!in_array('sin_equivalente', $mapa, true)) {
                $this->db->exec('ALTER TABLE dropi_productos ADD COLUMN sin_equivalente TINYINT(1) NOT NULL DEFAULT 0 AFTER producto_id');
            }
        } catch (\PDOException $e) {
            // la tabla se acaba de crear con la columna, o la agregó otra petición
        }
    }

    /* ─────────────────  Productos de Dropi ↔ productos de la tienda  ───────────────── */

    /**
     * Los productos de la tienda para elegir, con los activos primero.
     *
     * @return array<int, array{id: int|string, nombre: string, slug: string, activo: int|string}>
     */
    public function productosTienda(): array
    {
        return $this->db->query("SELECT id, nombre, COALESCE(slug, '') AS slug, activo FROM productos ORDER BY activo DESC, nombre")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Productos de Dropi que llegaron en alguna orden y todavía no se emparejan con uno de la
     * tienda (y que el dueño no marcó como "no lo tengo en mi tienda").
     *
     * @return array<int, array{id: string, nombre: string, ordenes: int}>
     */
    public function productosSinEmparejar(): array
    {
        $filas = $this->db->query("
            SELECT l.dropi_product_id AS id, MAX(l.producto) AS nombre, COUNT(DISTINCT l.orden_id) AS ordenes
            FROM dropi_orden_lineas l
            LEFT JOIN dropi_productos m ON m.dropi_product_id = l.dropi_product_id
            WHERE l.dropi_product_id IS NOT NULL AND l.producto_id IS NULL AND COALESCE(m.sin_equivalente, 0) = 0
            GROUP BY l.dropi_product_id
            ORDER BY ordenes DESC, nombre
        ")->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static fn($r) => ['id' => (string)$r['id'], 'nombre' => (string)$r['nombre'], 'ordenes' => (int)$r['ordenes']], $filas);
    }

    /**
     * Dice a qué producto de la tienda corresponde un producto de Dropi (o que no lo tiene) y lo
     * aplica de una vez a todas las órdenes ya importadas —sus líneas y el nombre que se muestra—
     * además de guardarlo para las próximas importaciones. No toca productos.dropi_product_id:
     * con él puesto, los pedidos de la landing se enviarían solos a Dropi.
     *
     * @param int|null $productoId  producto de la tienda; null = "no lo tengo en mi tienda"
     * @return bool  false si ese producto de Dropi no tiene órdenes o el producto de la tienda no existe
     */
    public function emparejarProducto(string $dropiId, ?int $productoId): bool
    {
        $dropiId = (string)preg_replace('/\D+/', '', $dropiId);
        if ($dropiId === '') return false;

        $stmt = $this->db->prepare('SELECT COUNT(*), MAX(producto) FROM dropi_orden_lineas WHERE dropi_product_id = ?');
        $stmt->execute([$dropiId]);
        [$lineas, $nombre] = $stmt->fetch(PDO::FETCH_NUM);
        if ((int)$lineas === 0) return false;

        if ($productoId !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM productos WHERE id = ?');
            $stmt->execute([$productoId]);
            if (!$stmt->fetchColumn()) return false;
        }

        $propia = !$this->db->inTransaction();
        if ($propia) $this->db->beginTransaction();
        try {
            $this->db->prepare("
                INSERT INTO dropi_productos (dropi_product_id, dropi_nombre, producto_id, sin_equivalente)
                VALUES (:id, :nombre, :pid, :sin)
                ON DUPLICATE KEY UPDATE dropi_nombre = VALUES(dropi_nombre), producto_id = VALUES(producto_id), sin_equivalente = VALUES(sin_equivalente)
            ")->execute([':id' => $dropiId, ':nombre' => mb_substr((string)$nombre, 0, 190), ':pid' => $productoId, ':sin' => $productoId === null ? 1 : 0]);

            $this->db->prepare('UPDATE dropi_orden_lineas SET producto_id = :pid WHERE dropi_product_id = :id')
                ->execute([':pid' => $productoId, ':id' => $dropiId]);

            // El nombre que se muestra en cada orden: el de la tienda si hay emparejamiento, si no el de Dropi.
            $this->db->prepare("
                UPDATE dropi_ordenes o
                SET o.producto = COALESCE(LEFT((
                        SELECT GROUP_CONCAT(DISTINCT COALESCE(p.nombre, l.producto) SEPARATOR ' + ')
                        FROM dropi_orden_lineas l LEFT JOIN productos p ON p.id = l.producto_id
                        WHERE l.orden_id = o.id
                    ), 190), o.producto)
                WHERE o.id IN (SELECT orden_id FROM dropi_orden_lineas WHERE dropi_product_id = :id)
            ")->execute([':id' => $dropiId]);

            if ($propia) $this->db->commit();
        } catch (\Throwable $e) {
            if ($propia && $this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * El producto de la tienda que más se parece a uno de Dropi, o null si no hay uno claro. Compara
     * las palabras del nombre de Dropi con las del nombre Y de la dirección web de cada producto
     * ("WEIXIER TURBULAR TM" ↔ "Bolso en Cuero", cuya dirección es bolso-turbular), y solo cuentan
     * las palabras que identifican a UN producto de la tienda: "bolso", "reloj" o "cuero" sirven para
     * varios y no deciden nada. Si hay empate no se adivina. Es una sugerencia: el dueño confirma.
     *
     * @param array<int, array{id: int|string, nombre: string, slug?: string}> $tienda
     */
    public static function sugerirProducto(string $nombreDropi, array $tienda): ?int
    {
        $palabras = static function (string $texto): array {
            $out = [];
            foreach (explode(' ', self::textoPlano($texto)) as $p) {
                // "B0LSO" → "bolso": un cero dentro de una palabra casi siempre es una "o" mal escrita
                if (preg_match('/[a-z]/', $p) && strpos($p, '0') !== false) $p = str_replace('0', 'o', $p);
                if (strlen($p) >= 4 && !ctype_digit($p)) $out[$p] = true;
            }
            return array_keys($out);
        };

        $porProducto = [];
        $frecuencia  = [];
        foreach ($tienda as $p) {
            $ps = $palabras($p['nombre'] . ' ' . str_replace('-', ' ', (string)($p['slug'] ?? '')));
            $porProducto[(int)$p['id']] = $ps;
            foreach ($ps as $w) $frecuencia[$w] = ($frecuencia[$w] ?? 0) + 1;
        }

        $dropi = $palabras($nombreDropi);
        $puntos = [];
        foreach ($porProducto as $id => $ps) {
            foreach (array_intersect($dropi, $ps) as $w) {
                if ($frecuencia[$w] === 1) $puntos[$id] = ($puntos[$id] ?? 0) + 1;
            }
        }
        if (!$puntos) return null;

        arsort($puntos);
        $valores = array_values($puntos);
        if (count($valores) > 1 && $valores[0] === $valores[1]) return null;
        return (int)array_key_first($puntos);
    }

    /* ───────────────────────────  Qué mensaje toca enviar  ─────────────────────────── */

    /**
     * El mensaje que corresponde a esta orden ahora mismo, o null si no toca ninguno.
     * Necesita: estado, dias_estado (días en ese estado; en oficina, días en la
     * oficina) y las marcas aviso_*_at. Pura: no consulta nada.
     *
     * Una novedad abierta (la transportadora reportó un problema) pasa por delante de
     * todo: es lo que hace que el paquete se devuelva. Se vuelve a pedir si la novedad
     * cambia (el importador borra aviso_novedad_at cuando cambia su texto).
     *
     * Oficina: primero "ya llegó"; luego el recordatorio a partir de los 2 días y la
     * última oportunidad a partir de los 4 — contados desde que llegó, no desde que se
     * avisó (Interrapidísimo devuelve a los 5 días hábiles de la llegada) y nunca el
     * mismo día que el aviso anterior.
     *
     * En camino: si va en reparto y no se le ha dicho que hoy llega, eso; si no, la guía.
     *
     * @return array{estado: string, etiqueta: string, urgente: bool}|null
     */
    public static function siguienteAviso(array $o, ?string $hoy = null): ?array
    {
        $hoy  = $hoy ?? date('Y-m-d');
        $dias = (int)($o['dias_estado'] ?? 0);
        $hay  = static fn($k) => !empty($o[$k]);
        $antesDeHoy = static fn($ts) => !empty($ts) && substr((string)$ts, 0, 10) < $hoy;

        if (trim((string)($o['novedad'] ?? '')) !== '' && !in_array($o['estado'] ?? '', self::ESTADOS_CERRADOS, true) && !$hay('aviso_novedad_at')) {
            return ['estado' => 'novedad', 'etiqueta' => 'Escribir por la novedad', 'urgente' => true];
        }

        switch ($o['estado'] ?? '') {
            case 'en_oficina':
                if (!$hay('aviso_oficina_at')) {
                    return ['estado' => 'en_oficina', 'etiqueta' => 'Avisar que ya llegó', 'urgente' => true];
                }
                $previo = max((string)($o['aviso_oficina_at'] ?? ''), (string)($o['aviso_recordatorio_at'] ?? ''));
                if ($dias >= self::DIAS_ULTIMO_AVISO && !$hay('aviso_ultimo_at') && $antesDeHoy($previo)) {
                    return ['estado' => 'ultimo_aviso_oficina', 'etiqueta' => 'Última oportunidad', 'urgente' => true];
                }
                if ($dias >= self::DIAS_RECORDATORIO && !$hay('aviso_recordatorio_at') && !$hay('aviso_ultimo_at')
                    && $antesDeHoy($o['aviso_oficina_at'] ?? null)) {
                    return ['estado' => 'recordatorio_oficina', 'etiqueta' => 'Enviar recordatorio', 'urgente' => true];
                }
                return null;

            case 'enviado':
                if (clave_color((string)($o['estatus'] ?? '')) === 'enreparto' && !$hay('aviso_reparto_at')) {
                    return ['estado' => 'en_reparto', 'etiqueta' => 'Avisar que llega hoy', 'urgente' => true];
                }
                return $hay('aviso_enviado_at') ? null
                    : ['estado' => 'enviado', 'etiqueta' => 'Enviar la guía', 'urgente' => false];

            case 'entregado':
                // Solo las recién entregadas: al cargar meses viejos no debe pedirse foto de todo.
                return (!$hay('aviso_entregado_at') && $dias <= 7)
                    ? ['estado' => 'entregado', 'etiqueta' => 'Pedir foto', 'urgente' => false]
                    : null;
        }
        return null;
    }

    /** Plantilla que se abre por defecto cuando no hay un aviso pendiente. */
    public static function plantillaPorDefecto(string $estado): string
    {
        // Una devolución abre "En espera de confirmación": sirve para retomar la venta si el cliente la quiere.
        return ['en_oficina' => 'en_oficina', 'enviado' => 'enviado', 'entregado' => 'entregado',
                'confirmado' => 'confirmado', 'cancelado' => 'cancelado', 'devolucion' => 'contactado'][$estado] ?? 'nuevo';
    }

    /**
     * Hasta cuándo puede recoger el cliente su pedido en la oficina, dicho como se lo
     * diría una persona: "hoy", "mañana" o "el jueves 29 de octubre". Vacío si no se
     * sabe desde cuándo está ahí.
     *
     * @param string|null $desde  cuándo llegó a la oficina (fecha de DropiOrden.estatus_desde)
     */
    public static function fechaLimite(?string $desde, ?string $hoy = null): string
    {
        if (empty($desde)) return '';

        $hoyTs = strtotime(($hoy ?? date('Y-m-d')) . ' 00:00:00');
        $limite = strtotime(substr($desde, 0, 10) . ' 00:00:00');
        if ($limite === false || $hoyTs === false) return '';

        for ($habiles = 0; $habiles < self::DIAS_HABILES_PLAZO;) {
            $limite = strtotime('+1 day', $limite);
            if ((int)date('N', $limite) < 6) $habiles++;   // lunes a viernes
        }

        $faltan = (int)round(($limite - $hoyTs) / 86400);
        if ($faltan <= 0) return 'hoy';   // ya vencido o vence hoy: que corra
        if ($faltan === 1) return 'mañana';

        $dias  = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
        $meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio',
                  8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
        return 'el ' . $dias[(int)date('N', $limite)] . ' ' . (int)date('j', $limite) . ' de ' . $meses[(int)date('n', $limite)];
    }

    /* ─────────────────────────────  Clientes con devolución  ───────────────────────────── */

    /**
     * Por qué se devolvió un pedido, deducido en el momento en que pasa a "devolución":
     * si estaba en la oficina es que no lo recogió; si tenía una novedad abierta, esa
     * es la causa (no tenía el dinero, la dirección no existía…); y si no, lo que diga
     * el texto de Dropi. Sin pistas queda "indeterminada" (el dueño puede decidir si
     * fue culpa del cliente con "no fue culpa del cliente").
     */
    public static function devolucionCausa(?string $estadoAnterior, string $novedadTipoAnterior, string $texto = ''): string
    {
        if ($estadoAnterior === 'en_oficina') return 'no_recogio';

        if (preg_match('/\b(no reclam|no recog|no retir|sin reclamar|sin recoger)/', self::textoPlano($texto))) return 'no_recogio';

        $utiles = ['pago', 'direccion', 'contacto', 'coordinar', 'recoger', 'rechazo'];
        if (in_array($novedadTipoAnterior, $utiles, true)) return $novedadTipoAnterior;

        $tipo = $texto !== '' ? self::novedadTipo($texto) : '';
        return in_array($tipo, $utiles, true) ? $tipo : 'indeterminada';
    }

    /** Últimos 8 dígitos de un teléfono: con eso se reconoce al mismo cliente aunque lo hayan escrito con +57, espacios o 0 inicial. */
    public static function sufijo(string $telefono): string
    {
        return substr((string)preg_replace('/\D+/', '', $telefono), -8);
    }

    /**
     * Qué se sabe de la reputación de estos teléfonos: devoluciones que SÍ fueron culpa
     * del cliente (las que el dueño no marcó como exentas), lo que costaron en fletes
     * de regreso y si alguien lo marcó a mano. Solo devuelve a quien tiene algo.
     *
     * @param  string[] $telefonos
     * @return array<string, array{devoluciones: int, costo: float, ultima: ?string, causa: string, manual: bool, nota: string, nivel: string}>
     *         indexado por los últimos 8 dígitos. nivel: devolvio (1 vez), reincidente (2 o más) o marcado (a mano).
     */
    public function riesgoPorTelefonos(array $telefonos): array
    {
        $sufijos = [];
        foreach ($telefonos as $t) {
            $s = self::sufijo((string)$t);
            if (strlen($s) >= 7) $sufijos[$s] = true;
        }
        if (!$sufijos) return [];

        $out = [];
        foreach (array_chunk(array_keys($sufijos), 500) as $lote) {
            $in = implode(',', array_fill(0, count($lote), '?'));

            // Una consulta que falla porque la tabla aún no existe es normal: no hay nada que mostrar.
            try {
                $stmt = $this->db->prepare("
                    SELECT RIGHT(telefono, 8) AS suf, COUNT(*) AS n, COALESCE(SUM(flete_devolucion), 0) AS costo,
                           MAX(estatus_desde) AS ultima,
                           SUBSTRING_INDEX(GROUP_CONCAT(devolucion_causa ORDER BY estatus_desde DESC SEPARATOR ','), ',', 1) AS causa
                    FROM dropi_ordenes
                    WHERE estado = 'devolucion' AND devolucion_exenta = 0 AND RIGHT(telefono, 8) IN ({$in})
                    GROUP BY RIGHT(telefono, 8)
                ");
                $stmt->execute($lote);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $out[$r['suf']] = ['devoluciones' => (int)$r['n'], 'costo' => (float)$r['costo'], 'ultima' => $r['ultima'],
                                       'causa' => (string)$r['causa'], 'manual' => false, 'nota' => ''];
                }
            } catch (\PDOException $e) {
                if ($e->getCode() !== '42S02') error_log('[DropiOrden] riesgo: ' . $e->getMessage());
            }

            try {
                $stmt = $this->db->prepare("SELECT sufijo, nota FROM clientes_riesgo WHERE sufijo IN ({$in})");
                $stmt->execute($lote);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $out[$r['sufijo']] = ($out[$r['sufijo']] ?? ['devoluciones' => 0, 'costo' => 0.0, 'ultima' => null, 'causa' => '']) + ['manual' => true, 'nota' => ''];
                    $out[$r['sufijo']]['manual'] = true;
                    $out[$r['sufijo']]['nota']   = (string)$r['nota'];
                }
            } catch (\PDOException $e) {
                if ($e->getCode() !== '42S02') error_log('[DropiOrden] riesgo manual: ' . $e->getMessage());
            }
        }

        foreach ($out as &$r) {
            $r['nivel'] = $r['manual'] ? 'marcado' : ($r['devoluciones'] >= 2 ? 'reincidente' : 'devolvio');
        }
        unset($r);
        return $out;
    }

    /** "Devolvió 1 vez · $15.000", "Reincidente: 2 devoluciones · $30.000", "Marcado a mano: nota". */
    public static function riesgoEtiqueta(array $r): string
    {
        $costo = ($r['costo'] ?? 0) > 0 ? ' · $' . number_format((float)$r['costo'], 0, ',', '.') : '';
        $n = (int)($r['devoluciones'] ?? 0);
        $nota = trim((string)($r['nota'] ?? ''));

        if (!empty($r['manual']) && $n === 0) return 'Marcado a mano' . ($nota !== '' ? ': ' . $nota : '');

        $texto = $n === 1 ? 'Devolvió 1 vez' : "Reincidente: {$n} devoluciones";
        return (!empty($r['manual']) ? 'Marcado a mano · ' : '') . $texto . $costo;
    }

    /**
     * Todos los clientes con alguna devolución (con o sin culpa) y los marcados a mano, para
     * la pantalla "Clientes con devolución", con el detalle de cada devolución.
     *
     * @return array<int, array<string, mixed>>
     */
    public function clientesRiesgo(): array
    {
        $clientes = [];

        $q = $this->db->query("
            SELECT id, dropi_id, telefono, nombre, apellidos, municipio, producto, fecha_pedido, estatus_desde,
                   flete_devolucion, valor, devolucion_causa, devolucion_exenta, devolucion_nota
            FROM dropi_ordenes WHERE estado = 'devolucion' ORDER BY estatus_desde DESC, id DESC
        ");
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $o) {
            $s = self::sufijo((string)$o['telefono']);
            // Las filas vienen de la devolución más reciente a la más vieja: la primera da el nombre y el teléfono.
            $clientes[$s] ??= ['sufijo' => $s, 'telefono' => $o['telefono'], 'nombre' => trim($o['nombre'] . ' ' . $o['apellidos']),
                               'municipio' => $o['municipio'], 'ordenes' => [], 'manual' => null];
            $clientes[$s]['ordenes'][] = $o;
        }

        foreach ($this->db->query('SELECT id, sufijo, telefono, nombre, nota, creado_en FROM clientes_riesgo')->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $clientes[$m['sufijo']] ??= ['sufijo' => $m['sufijo'], 'telefono' => $m['telefono'], 'nombre' => $m['nombre'],
                                         'municipio' => '', 'ordenes' => [], 'manual' => null];
            $clientes[$m['sufijo']]['manual'] = $m;
            if ($clientes[$m['sufijo']]['nombre'] === '') $clientes[$m['sufijo']]['nombre'] = $m['nombre'];
        }

        foreach ($clientes as &$c) {
            $culpa = array_filter($c['ordenes'], static fn($o) => !(int)$o['devolucion_exenta']);
            $c['total']   = count($c['ordenes']);
            $c['culpa']   = count($culpa);
            $c['costo']   = (float)array_sum(array_column($culpa, 'flete_devolucion'));
            $c['ultima']  = $c['ordenes'] ? $c['ordenes'][0]['estatus_desde'] : null;
            $c['causas']  = array_values(array_unique(array_filter(array_column($culpa, 'devolucion_causa'))));
            $c['nivel']   = $c['manual'] ? 'marcado' : ($c['culpa'] >= 2 ? 'reincidente' : ($c['culpa'] === 1 ? 'devolvio' : 'perdonado'));
        }
        unset($c);

        $peso = ['marcado' => 0, 'reincidente' => 0, 'devolvio' => 1, 'perdonado' => 2];
        usort($clientes, static fn($a, $b) => [$peso[$a['nivel']], -(int)strtotime((string)$a['ultima'])] <=> [$peso[$b['nivel']], -(int)strtotime((string)$b['ultima'])]);
        return array_values($clientes);
    }

    /** El dueño decide si una devolución fue culpa del cliente (exenta = no lo fue) y deja una nota. */
    public function marcarDevolucion(int $ordenId, bool $exenta, string $nota = ''): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM dropi_ordenes WHERE id = :id AND estado = 'devolucion'");
        $stmt->execute([':id' => $ordenId]);
        if (!$stmt->fetchColumn()) return false;

        return $this->db->prepare('UPDATE dropi_ordenes SET devolucion_exenta = :e, devolucion_nota = :n WHERE id = :id')
            ->execute([':e' => $exenta ? 1 : 0, ':n' => mb_substr(trim($nota), 0, 255), ':id' => $ordenId]);
    }

    /** Marca a mano a un cliente (alguien que rechazó en la puerta, que insultó…), sin necesidad de una devolución. */
    public function agregarClienteRiesgo(string $telefono, string $nombre, string $nota): bool
    {
        $digitos = (string)preg_replace('/\D+/', '', $telefono);
        if (preg_match('/^(?:00)?57(3\d{9})$/', $digitos, $m)) $digitos = $m[1];
        if (strlen($digitos) < 7) return false;

        return $this->db->prepare("
            INSERT INTO clientes_riesgo (sufijo, telefono, nombre, nota, creado_en)
            VALUES (:s, :t, :n, :nota, NOW())
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), nota = VALUES(nota)
        ")->execute([':s' => self::sufijo($digitos), ':t' => $digitos, ':n' => mb_substr(trim($nombre), 0, 150), ':nota' => mb_substr(trim($nota), 0, 255)]);
    }

    public function quitarClienteRiesgo(int $id): bool
    {
        return $this->db->prepare('DELETE FROM clientes_riesgo WHERE id = :id')->execute([':id' => $id]);
    }

    /* ─────────────────────────────  Novedades de la transportadora  ───────────────────────────── */

    /**
     * Qué tipo de problema es una novedad, por el texto de la transportadora ("DIRECCION
     * DESTINATARIO NO EXISTE", "COORDINAR LA ENTREGA"…). Con eso el mensaje al cliente
     * pide lo que de verdad hace falta (confirmar la dirección, decir cuándo estará…).
     * El orden importa: lo del dinero y la dirección se reconoce antes que lo demás.
     *
     * @return string  una clave de NOVEDAD_ETIQUETAS, o '' si el texto está vacío
     */
    public static function novedadTipo(string $texto): string
    {
        $t = self::textoPlano($texto);
        if ($t === '') return '';

        $reglas = [
            'pago'      => '/\b(plata|dinero|efectivo|sin fondos|no tenia para|no alcanz)/',
            'contacto'  => '/\b(ausente|no contesta|no responde|no atiende|sin contacto|telefono|cerrad|nadie (atiende|recibe)|(cliente|destinatario) no (se encuentra|se encontraba|esta))/',
            'direccion' => '/\b(direcc|ubicar|ubicacion|no existe|incomplet|errad|nomenclatura|sin referencia|zona rural|dificil acceso)/',
            'recoger'   => '/\b(bodega|receptoria|recoger|reclamar|oficina)/',
            'coordinar' => '/\b(coordin|reprogram|agend|cita)/',
            'rechazo'   => '/\b(rechaz|no quiere|no desea|no acept|desiste|cancel)/',
        ];
        foreach ($reglas as $tipo => $patron) {
            if (preg_match($patron, $t)) return $tipo;
        }
        return 'otra';
    }

    /** Minúsculas, sin tildes y con los signos convertidos en espacios: "DIRECCIÓN NO-EXISTE." → "direccion no existe". */
    private static function textoPlano(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return trim((string)preg_replace('/[^a-z0-9]+/', ' ', $s));
    }

    /**
     * Las dos frases que arman el mensaje de novedad según el tipo: qué pasó y qué le
     * pedimos al cliente. No se supone cómo son las direcciones de la gente: se pide
     * "dirección completa y una referencia para llegar", sin ejemplos urbanos.
     *
     * @return array{motivo: string, peticion: string}
     */
    public static function novedadTextos(string $tipo, string $municipio = '', string $precio = ''): array
    {
        $cobro = $precio !== '' ? " Recuerda que pagas *{$precio}* en efectivo." : '';
        $aqui  = $municipio !== '' ? " en {$municipio}" : '';

        switch ($tipo) {
            case 'direccion':
                return ['motivo' => 'La transportadora no logró ubicar la dirección de entrega.',
                        'peticion' => '¿Nos confirmas tu dirección completa y alguna referencia para llegar hasta ti? Con eso reprogramamos la entrega.'];
            case 'contacto':
                return ['motivo' => 'La transportadora intentó entregarlo y no pudo comunicarse contigo.',
                        'peticion' => '¿Nos dices en qué horario estás disponible y si este es el mejor número para llamarte?'];
            case 'coordinar':
                return ['motivo' => 'La transportadora necesita coordinar contigo la entrega.',
                        'peticion' => '¿Qué día y a qué hora te queda bien recibirlo? Respóndenos y lo coordinamos.'];
            case 'pago':
                return ['motivo' => 'El mensajero llegó pero no se pudo completar el pago contraentrega.',
                        'peticion' => "¿Cuándo puedes recibirlo con el efectivo listo? Dinos el día y lo reprogramamos.{$cobro}"];
            case 'recoger':
                return ['motivo' => "Tu pedido quedó para recoger en la bodega u oficina de la transportadora{$aqui}.",
                        'peticion' => "Pasa a recogerlo con tu cédula o el número de guía.{$cobro} ¿Cuándo vas a poder ir?"];
            case 'rechazo':
                return ['motivo' => 'Nos reportaron que el pedido no fue recibido.',
                        'peticion' => '¿Nos cuentas qué pasó? Si todavía lo quieres, lo volvemos a enviar.'];
            default:
                return ['motivo' => 'La transportadora nos reportó una novedad con la entrega.',
                        'peticion' => '¿Nos ayudas respondiendo este mensaje? Cuéntanos cuándo estás disponible y cómo te pueden ubicar mejor.'];
        }
    }

    /* ───────────────────────────────  Consultas  ─────────────────────────────── */

    /** Fecha desde la cual una orden sin verse en las importaciones se da por vieja (null si no hay órdenes). */
    private function corteVigente(): ?string
    {
        $ultima = $this->db->query('SELECT MAX(visto_at) FROM dropi_ordenes')->fetchColumn();
        return $ultima ? date('Y-m-d H:i:s', strtotime($ultima . ' -' . self::DIAS_SIN_ACTUALIZAR . ' days')) : null;
    }

    /**
     * Cuántas órdenes hay en cada vista (las tarjetas de Seguimiento).
     *
     * @return array{por_avisar: int, esperando: int, piden_recordatorio: int, novedad: int,
     *               en_camino: int, entregados: int, devoluciones: int, costo_devoluciones: float,
     *               todas: int, primera_orden: ?string}
     */
    public function contadores(): array
    {
        $corte = $this->corteVigente();
        $vig   = $corte !== null ? 'visto_at >= ' . $this->db->quote($corte) : '1 = 1';

        $r = $this->db->query("
            SELECT COUNT(*) AS todas,
                   COALESCE(SUM(estado = 'en_oficina' AND aviso_oficina_at IS NULL AND {$vig}), 0)      AS por_avisar,
                   COALESCE(SUM(estado = 'en_oficina' AND aviso_oficina_at IS NOT NULL AND {$vig}), 0)  AS esperando,
                   COALESCE(SUM(novedad <> '' AND estado NOT IN ('entregado', 'cancelado', 'devolucion') AND {$vig}), 0) AS novedad,
                   COALESCE(SUM(estado IN ('confirmado', 'enviado') AND {$vig}), 0)                     AS en_camino,
                   COALESCE(SUM(estado = 'entregado'), 0)                                               AS entregados,
                   COALESCE(SUM(estado = 'devolucion'), 0)                                              AS devoluciones,
                   COALESCE(SUM(CASE WHEN estado = 'devolucion' THEN flete_devolucion ELSE 0 END), 0)   AS costo_devoluciones,
                   MIN(fecha_pedido) AS primera_orden
            FROM dropi_ordenes
        ")->fetch(PDO::FETCH_ASSOC);

        // De los que esperan en la oficina, a cuántos ya les toca recordatorio o última oportunidad.
        $piden = 0;
        $q = $this->db->query("
            SELECT estado, estatus, novedad, aviso_oficina_at, aviso_recordatorio_at, aviso_ultimo_at, aviso_novedad_at,
                   DATEDIFF(CURDATE(), DATE(estatus_desde)) AS dias_estado
            FROM dropi_ordenes
            WHERE estado = 'en_oficina' AND aviso_oficina_at IS NOT NULL AND {$vig}
        ");
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $o) {
            $toca = self::siguienteAviso($o);
            if ($toca !== null && in_array($toca['estado'], ['recordatorio_oficina', 'ultimo_aviso_oficina'], true)) $piden++;
        }

        return [
            'por_avisar'         => (int)$r['por_avisar'],
            'esperando'          => (int)$r['esperando'],
            'piden_recordatorio' => $piden,
            'novedad'            => (int)$r['novedad'],
            'en_camino'          => (int)$r['en_camino'],
            'entregados'         => (int)$r['entregados'],
            'devoluciones'       => (int)$r['devoluciones'],
            'costo_devoluciones' => (float)$r['costo_devoluciones'],
            'clientes_riesgo'    => $this->contarClientesRiesgo(),
            'todas'              => (int)$r['todas'],
            'primera_orden'      => $r['primera_orden'],
        ];
    }

    /** Clientes distintos con una devolución que sí fue culpa suya, más los marcados a mano. */
    private function contarClientesRiesgo(): int
    {
        return (int)$this->db->query("
            SELECT COUNT(*) FROM (
                SELECT RIGHT(telefono, 8) AS s FROM dropi_ordenes WHERE estado = 'devolucion' AND devolucion_exenta = 0
                UNION
                SELECT sufijo FROM clientes_riesgo
            ) t
        ")->fetchColumn();
    }

    /**
     * Una página de órdenes de una vista, con búsqueda y rango de fechas (de pedido).
     *
     * @param array{vista?: string, buscar?: string, desde?: string, hasta?: string} $f
     * @return array{total: int, filtrado: int, filas: array<int, array>}
     */
    public function listar(array $f, int $desde, int $cuantas): array
    {
        $vista = in_array($f['vista'] ?? '', self::VISTAS, true) ? $f['vista'] : 'todas';
        $corte = $this->corteVigente();
        $vig   = $corte !== null ? 'o.visto_at >= ' . $this->db->quote($corte) : '1 = 1';

        [$base, $orden] = [
            'por_avisar' => ["o.estado = 'en_oficina' AND o.aviso_oficina_at IS NULL AND {$vig}", 'o.estatus_desde ASC, o.id ASC'],
            'esperando'  => ["o.estado = 'en_oficina' AND o.aviso_oficina_at IS NOT NULL AND {$vig}", 'o.estatus_desde ASC, o.id ASC'],
            'novedad'    => ["o.novedad <> '' AND o.estado NOT IN ('entregado', 'cancelado', 'devolucion') AND {$vig}", 'o.ultimo_mov_at DESC, o.id DESC'],
            'en_camino'  => ["o.estado IN ('confirmado', 'enviado') AND {$vig}", 'o.fecha_pedido ASC, o.id ASC'],
            'entregados' => ["o.estado = 'entregado'", 'o.fecha_pedido DESC, o.id DESC'],
            'devoluciones' => ["o.estado = 'devolucion'", 'o.estatus_desde DESC, o.id DESC'],
            'todas'      => ['1 = 1', 'o.fecha_pedido DESC, o.id DESC'],
        ][$vista];

        $where  = [];
        $params = [];

        $terminos = preg_split('/\s+/u', trim((string)($f['buscar'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach (array_slice($terminos, 0, 6) as $i => $t) {
            // El ID de Dropi se compara aparte: convertirlo a texto dentro del CONCAT_WS mezcla collations.
            $where[] = "(CONCAT_WS(' ', o.nombre, o.apellidos, o.telefono, o.guia, o.producto, o.municipio, o.departamento, o.estatus) LIKE :b{$i} OR o.dropi_id = :i{$i})";
            $params[":b{$i}"] = '%' . addcslashes($t, '%_\\') . '%';
            $params[":i{$i}"] = ctype_digit($t) && strlen($t) <= 18 ? $t : '0';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($f['desde'] ?? ''))) {
            $where[] = 'o.fecha_pedido >= :desde';
            $params[':desde'] = $f['desde'] . ' 00:00:00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($f['hasta'] ?? ''))) {
            $where[] = 'o.fecha_pedido <= :hasta';
            $params[':hasta'] = $f['hasta'] . ' 23:59:59';
        }

        $condicion = $base . ($where ? ' AND ' . implode(' AND ', $where) : '');

        $total = (int)$this->db->query("SELECT COUNT(*) FROM dropi_ordenes o WHERE {$base}")->fetchColumn();
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM dropi_ordenes o WHERE {$condicion}");
        $stmt->execute($params);
        $filtrado = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT o.*,
                   DATEDIFF(CURDATE(), DATE(o.estatus_desde)) AS dias_estado,
                   DATEDIFF(CURDATE(), DATE(o.fecha_pedido))  AS dias_pedido,
                   " . ($corte !== null ? "(o.visto_at < " . $this->db->quote($corte) . ")" : '0') . " AS sin_actualizar
            FROM dropi_ordenes o
            WHERE {$condicion}
            ORDER BY {$orden}
            LIMIT :desde_fila, :cuantas
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':desde_fila', max(0, $desde), PDO::PARAM_INT);
        $stmt->bindValue(':cuantas', max(1, min(200, $cuantas)), PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Producto y variación (color) de cada orden de la página, en una sola consulta.
        $lineas = [];
        if ($filas) {
            $ids = implode(',', array_map('intval', array_column($filas, 'id')));
            // El nombre del producto de la tienda si ya se eligió cuál es; si no, el de Dropi.
            foreach ($this->db->query("
                SELECT l.orden_id, COALESCE(p.nombre, l.producto) AS producto, l.variacion, l.cantidad
                FROM dropi_orden_lineas l
                LEFT JOIN productos p ON p.id = l.producto_id
                WHERE l.orden_id IN ({$ids})
                ORDER BY l.id
            ")->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $lineas[(int)$l['orden_id']][] = $l;
            }
        }
        $riesgos = $this->riesgoPorTelefonos(array_column($filas, 'telefono'));
        foreach ($filas as &$o) {
            $o['lineas'] = $lineas[(int)$o['id']] ?? [];
            $o['riesgo'] = $riesgos[self::sufijo((string)$o['telefono'])] ?? null;
        }
        unset($o);

        return ['total' => $total, 'filtrado' => $filtrado, 'filas' => $filas];
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM dropi_ordenes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $o = $stmt->fetch(PDO::FETCH_ASSOC);
        return $o ?: null;
    }

    /* ───────────────────────────────  Avisos  ─────────────────────────────── */

    /**
     * Anota que el cliente de esta orden ya recibió un mensaje (o que la vendedora
     * lo avisó por otro medio), y guarda la guía si se escribió una nueva.
     *
     * @return bool  false si la orden no existe
     */
    public function registrarAviso(int $id, string $estado, string $guia = ''): bool
    {
        if (!$this->porId($id)) return false;

        $sets = [];
        $params = [':id' => $id];
        if (isset(self::COLUMNA_AVISO[$estado])) {
            $sets[] = self::COLUMNA_AVISO[$estado] . ' = NOW()';
        }
        $guia = mb_substr((string)preg_replace('/[^A-Za-z0-9\-]/', '', $guia), 0, 50);
        if ($guia !== '') {
            $sets[] = 'guia = :guia';
            $params[':guia'] = $guia;
        }
        if (!$sets) return true;

        return $this->db->prepare('UPDATE dropi_ordenes SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
    }

    /* ─────────────────────────────  Importaciones  ───────────────────────────── */

    public function registrarImportacion(string $usuario, string $archivo, int $ordenes, int $nuevas, int $actualizadas, int $sinCambios): void
    {
        $this->db->prepare("
            INSERT INTO dropi_importaciones (creado_en, usuario, archivo, ordenes, nuevas, actualizadas, sin_cambios)
            VALUES (NOW(), :u, :a, :o, :n, :ac, :s)
        ")->execute([
            ':u' => mb_substr($usuario, 0, 150), ':a' => mb_substr($archivo, 0, 120),
            ':o' => $ordenes, ':n' => $nuevas, ':ac' => $actualizadas, ':s' => $sinCambios,
        ]);
    }

    /** @return array{creado_en: string, usuario: string, archivo: string, ordenes: int, horas: int}|null */
    public function ultimaImportacion(): ?array
    {
        $r = $this->db->query("
            SELECT creado_en, usuario, archivo, ordenes, TIMESTAMPDIFF(HOUR, creado_en, NOW()) AS horas
            FROM dropi_importaciones ORDER BY id DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }
}
