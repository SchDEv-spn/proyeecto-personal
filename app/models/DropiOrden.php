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
    public const VISTAS = ['por_avisar', 'esperando', 'novedad', 'en_camino', 'entregados', 'todas'];

    /** Mensaje (plantilla de Seguimiento) → columna donde se anota que ya se envió. */
    private const COLUMNA_AVISO = [
        'enviado'              => 'aviso_enviado_at',
        'en_oficina'           => 'aviso_oficina_at',
        'recordatorio_oficina' => 'aviso_recordatorio_at',
        'ultimo_aviso_oficina' => 'aviso_ultimo_at',
        'entregado'            => 'aviso_entregado_at',
    ];

    /** Días en oficina desde los que toca cada recordatorio. */
    public const DIAS_RECORDATORIO = 2;
    public const DIAS_ULTIMO_AVISO = 4;

    /** Una orden que no aparece en las últimas importaciones se da por "sin actualizar". */
    private const DIAS_SIN_ACTUALIZAR = 3;

    private static bool $listo = false;

    public function __construct()
    {
        parent::__construct();
        $this->crearTablas();
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
                motivo_cancelacion    VARCHAR(255) NOT NULL DEFAULT '',
                producto              VARCHAR(190) NOT NULL DEFAULT '',
                cantidad              INT NOT NULL DEFAULT 1,
                valor                 DECIMAL(12,2) NOT NULL DEFAULT 0,
                costo_proveedor       DECIMAL(12,2) NOT NULL DEFAULT 0,
                flete                 DECIMAL(12,2) NOT NULL DEFAULT 0,
                flete_devolucion      DECIMAL(12,2) NOT NULL DEFAULT 0,
                comision              DECIMAL(12,2) NOT NULL DEFAULT 0,
                ganancia              DECIMAL(12,2) NULL,
                aviso_enviado_at      DATETIME NULL,
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
                updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    }

    /* ───────────────────────────  Qué mensaje toca enviar  ─────────────────────────── */

    /**
     * El mensaje que corresponde a esta orden ahora mismo, o null si no toca ninguno.
     * Necesita: estado, dias_estado (días en ese estado; en oficina, días en la
     * oficina) y las marcas aviso_*_at. Pura: no consulta nada.
     *
     * Oficina: primero "ya llegó"; luego el recordatorio a partir de los 2 días y la
     * última oportunidad a partir de los 4 — contados desde que llegó, no desde que se
     * avisó (Interrapidísimo devuelve a los 5 días hábiles de la llegada) y nunca el
     * mismo día que el aviso anterior.
     *
     * @return array{estado: string, etiqueta: string, urgente: bool}|null
     */
    public static function siguienteAviso(array $o, ?string $hoy = null): ?array
    {
        $hoy  = $hoy ?? date('Y-m-d');
        $dias = (int)($o['dias_estado'] ?? 0);
        $hay  = static fn($k) => !empty($o[$k]);
        $antesDeHoy = static fn($ts) => !empty($ts) && substr((string)$ts, 0, 10) < $hoy;

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
        return ['en_oficina' => 'en_oficina', 'enviado' => 'enviado', 'entregado' => 'entregado',
                'confirmado' => 'confirmado', 'cancelado' => 'cancelado'][$estado] ?? 'nuevo';
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
     *               en_camino: int, entregados: int, todas: int, primera_orden: ?string}
     */
    public function contadores(): array
    {
        $corte = $this->corteVigente();
        $vig   = $corte !== null ? 'visto_at >= ' . $this->db->quote($corte) : '1 = 1';

        $r = $this->db->query("
            SELECT COUNT(*) AS todas,
                   COALESCE(SUM(estado = 'en_oficina' AND aviso_oficina_at IS NULL AND {$vig}), 0)      AS por_avisar,
                   COALESCE(SUM(estado = 'en_oficina' AND aviso_oficina_at IS NOT NULL AND {$vig}), 0)  AS esperando,
                   COALESCE(SUM(novedad <> '' AND estado NOT IN ('entregado', 'cancelado') AND {$vig}), 0) AS novedad,
                   COALESCE(SUM(estado IN ('confirmado', 'enviado') AND {$vig}), 0)                     AS en_camino,
                   COALESCE(SUM(estado = 'entregado'), 0)                                               AS entregados,
                   MIN(fecha_pedido) AS primera_orden
            FROM dropi_ordenes
        ")->fetch(PDO::FETCH_ASSOC);

        // De los que esperan en la oficina, a cuántos ya les toca recordatorio o última oportunidad.
        $piden = 0;
        $q = $this->db->query("
            SELECT estado, aviso_oficina_at, aviso_recordatorio_at, aviso_ultimo_at,
                   DATEDIFF(CURDATE(), DATE(estatus_desde)) AS dias_estado
            FROM dropi_ordenes
            WHERE estado = 'en_oficina' AND aviso_oficina_at IS NOT NULL AND {$vig}
        ");
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $o) {
            if (self::siguienteAviso($o) !== null) $piden++;
        }

        return [
            'por_avisar'         => (int)$r['por_avisar'],
            'esperando'          => (int)$r['esperando'],
            'piden_recordatorio' => $piden,
            'novedad'            => (int)$r['novedad'],
            'en_camino'          => (int)$r['en_camino'],
            'entregados'         => (int)$r['entregados'],
            'todas'              => (int)$r['todas'],
            'primera_orden'      => $r['primera_orden'],
        ];
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
            'novedad'    => ["o.novedad <> '' AND o.estado NOT IN ('entregado', 'cancelado') AND {$vig}", 'o.ultimo_mov_at DESC, o.id DESC'],
            'en_camino'  => ["o.estado IN ('confirmado', 'enviado') AND {$vig}", 'o.fecha_pedido ASC, o.id ASC'],
            'entregados' => ["o.estado = 'entregado'", 'o.fecha_pedido DESC, o.id DESC'],
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
        foreach ($filas as &$o) $o['lineas'] = $lineas[(int)$o['id']] ?? [];
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
