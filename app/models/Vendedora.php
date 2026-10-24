<?php

/**
 * Vendedoras por comisión: quienes cierran ventas por WhatsApp y le pasan el pedido al dueño, que lo crea
 * en Dropi. Aquí se atribuye cada orden de Dropi a una vendedora y se calcula lo que se le paga.
 *
 * Cómo se trabaja (así lo pidió el dueño):
 *  - Ellas apuntan en un cuaderno solo el teléfono del cliente. El dueño pega esos teléfonos, el sistema
 *    busca la orden de cada uno en Dropi y la deja asignada a esa vendedora (aunque todavía vaya en camino).
 *  - Se les paga un valor fijo por UNIDAD de cada pedido, solo cuando el pedido está ENTREGADO: con dos
 *    unidades se paga doble, con tres triple, y así sucesivamente. Un pedido devuelto o cancelado no se
 *    paga, y por eso las devoluciones no se les cobran: simplemente nunca se pagaron.
 *  - La liquidación (normalmente semanal) paga lo entregado y todavía no pagado, y deja marcado en cada orden
 *    en qué liquidación se pagó y cuánto: así nada se paga dos veces.
 *  - Una orden que vino de la landing no es de una vendedora (el canal sale de DashboardDropi).
 *  - Cantidad: solo se conoce con la descarga de Dropi "con productos"; sin ella la orden cuenta como una
 *    unidad y se avisa, para no pagar de menos sin darse cuenta.
 */
class Vendedora extends Model
{
    /** De cuántos días atrás se buscan las órdenes de un teléfono. */
    public const DIAS_ATRAS = 120;
    /** Las órdenes recientes (hasta estos días) salen marcadas por defecto al agregarlas. */
    public const DIAS_MARCADAS = 60;
    public const MAX_TELEFONOS = 400;
    public const MAX_VALOR_PEDIDO = 10000000;

    private static bool $listo = false;

    public function __construct()
    {
        parent::__construct();
        new DropiOrden(); // crea dropi_ordenes y sus columnas nuevas (vendedora_id, liquidacion_id, comision_vendedora)
        $this->crearTablas();
    }

    private function crearTablas(): void
    {
        if (self::$listo) return;
        self::$listo = true;

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS vendedoras (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                nombre       VARCHAR(100) NOT NULL,
                valor_pedido DECIMAL(12,2) NOT NULL DEFAULT 0,
                activa       TINYINT(1)   NOT NULL DEFAULT 1,
                creado_en    DATETIME     NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS liquidaciones (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                vendedora_id INT NOT NULL,
                creada_en    DATETIME NOT NULL,
                ordenes      INT NOT NULL DEFAULT 0,
                total        DECIMAL(12,2) NOT NULL DEFAULT 0,
                valor_pedido DECIMAL(12,2) NOT NULL DEFAULT 0,
                nota         VARCHAR(255) NOT NULL DEFAULT '',
                KEY idx_vendedora (vendedora_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        try {
            if (!$this->db->query("SHOW INDEX FROM dropi_ordenes WHERE Key_name = 'idx_vendedora'")->fetch()) {
                $this->db->exec('ALTER TABLE dropi_ordenes ADD INDEX idx_vendedora (vendedora_id)');
            }
        } catch (PDOException $e) {
            // otra petición lo está creando a la vez: no pasa nada
        }
    }

    /* ─────────────────────────────  Cálculo (puro)  ───────────────────────────── */

    /** Cuántas veces se paga el valor por unidad: una por unidad (1 si no se sabe cuántas lleva). */
    public static function multiplo(int $cantidad, bool $cantidadConocida): int
    {
        return $cantidadConocida ? max(1, $cantidad) : 1;
    }

    public static function comision(float $valorPedido, int $cantidad, bool $cantidadConocida): float
    {
        return round($valorPedido * self::multiplo($cantidad, $cantidadConocida), 2);
    }

    /**
     * Teléfonos de un texto pegado (uno por línea, o separados por coma o punto y coma). Acepta +57, 57 o 0057
     * delante y espacios o guiones adentro. Los que no parecen un celular colombiano (10 dígitos que empiezan
     * por 3) se devuelven aparte, tal como se escribieron, para que se vea el error de digitación.
     *
     * @return array{validos: string[], invalidos: string[], repetidos: int}
     */
    public static function parsearTelefonos(string $texto): array
    {
        $validos = [];
        $invalidos = [];
        $repetidos = 0;
        foreach (preg_split('/[\r\n,;\t]+/u', $texto) ?: [] as $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;
            $d = (string)preg_replace('/\D+/', '', $linea);
            if (strlen($d) === 14 && strpos($d, '0057') === 0) $d = substr($d, 4);
            elseif (strlen($d) === 12 && strpos($d, '57') === 0) $d = substr($d, 2);

            if (strlen($d) === 10 && $d[0] === '3') {
                if (isset($validos[$d])) { $repetidos++; continue; }
                $validos[$d] = true;
            } else {
                $invalidos[] = mb_substr($linea, 0, 40);
            }
        }
        // array_keys devuelve enteros cuando la llave es numérica: los teléfonos se quedan como texto.
        return ['validos' => array_map('strval', array_keys($validos)), 'invalidos' => $invalidos, 'repetidos' => $repetidos];
    }

    /**
     * Si una orden se puede agregar a una vendedora, y si no, por qué.
     *
     * @return array{0: bool, 1: string}
     */
    public static function elegibilidad(array $o, string $canal, int $vendedoraId): array
    {
        if ($canal === 'landing') return [false, 'Vino de la landing'];
        if (!empty($o['liquidacion_id'])) return [false, 'Ya liquidada'];
        if (!empty($o['vendedora_id']) && (int)$o['vendedora_id'] === $vendedoraId) return [false, 'Ya está con ella'];
        if (!empty($o['vendedora_id'])) return [false, 'Ya es de ' . ($o['vendedora_nombre'] ?? 'otra vendedora')];
        if (($o['estado'] ?? '') === 'cancelado') return [false, 'Cancelada: no se paga'];
        return [true, ''];
    }

    /* ─────────────────────────────  Vendedoras  ───────────────────────────── */

    /** @return array<int, array<string, mixed>> */
    public function todas(): array
    {
        return $this->db->query('SELECT id, nombre, valor_pedido, activa, creado_en FROM vendedoras ORDER BY activa DESC, nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function porId(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, nombre, valor_pedido, activa, creado_en FROM vendedoras WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Crea o actualiza una vendedora. $valorPedido son pesos enteros.
     *
     * @throws InvalidArgumentException con un mensaje para mostrar
     */
    public function guardar(?int $id, string $nombre, int $valorPedido, bool $activa = true): int
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre));
        if ($nombre === '') throw new InvalidArgumentException('Escribe el nombre de la vendedora.');
        if (mb_strlen($nombre) > 100) throw new InvalidArgumentException('El nombre es muy largo (máximo 100 letras).');
        if ($valorPedido < 0 || $valorPedido > self::MAX_VALOR_PEDIDO) throw new InvalidArgumentException('El valor por unidad no es válido.');

        if ($id === null || $id <= 0) {
            $stmt = $this->db->prepare('INSERT INTO vendedoras (nombre, valor_pedido, activa, creado_en) VALUES (:n, :v, :a, NOW())');
            $stmt->execute([':n' => $nombre, ':v' => $valorPedido, ':a' => $activa ? 1 : 0]);
            return (int)$this->db->lastInsertId();
        }
        if (!$this->porId($id)) throw new InvalidArgumentException('Esa vendedora no existe.');
        $stmt = $this->db->prepare('UPDATE vendedoras SET nombre = :n, valor_pedido = :v, activa = :a WHERE id = :id');
        $stmt->execute([':n' => $nombre, ':v' => $valorPedido, ':a' => $activa ? 1 : 0, ':id' => $id]);
        return $id;
    }

    /**
     * Lo asignado a cada vendedora, resumido para las tarjetas: cuántos pedidos y cuánto hay por pagar,
     * en camino, sin pago (devueltos o cancelados) y ya pagado.
     *
     * @return array<int, array<string, mixed>>  por id de vendedora
     */
    public function resumen(): array
    {
        $out = [];
        $valores = array_column($this->todas(), 'valor_pedido', 'id');
        $stmt = $this->db->query('
            SELECT o.vendedora_id, o.estado, o.liquidacion_id, o.cantidad, o.comision_vendedora,
                   (SELECT COUNT(*) FROM dropi_orden_lineas l WHERE l.orden_id = o.id) AS n_lineas
            FROM dropi_ordenes o WHERE o.vendedora_id IS NOT NULL
        ');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
            $vid = (int)$o['vendedora_id'];
            $r = &$out[$vid];
            $r ??= ['asignadas' => 0, 'por_pagar' => 0, 'monto_por_pagar' => 0.0, 'en_camino' => 0, 'sin_pago' => 0, 'pagadas' => 0, 'monto_pagado' => 0.0, 'sin_cantidad' => 0];
            $r['asignadas']++;
            if (!empty($o['liquidacion_id'])) {
                $r['pagadas']++;
                $r['monto_pagado'] += (float)$o['comision_vendedora'];
            } elseif ($o['estado'] === 'entregado') {
                $conocida = (int)$o['n_lineas'] > 0;
                $r['por_pagar']++;
                $r['monto_por_pagar'] += self::comision((float)($valores[$vid] ?? 0), (int)$o['cantidad'], $conocida);
                if (!$conocida) $r['sin_cantidad']++;
            } elseif ($o['estado'] === 'devolucion' || $o['estado'] === 'cancelado') {
                $r['sin_pago']++;
            } else {
                $r['en_camino']++;
            }
            unset($r);
        }
        return $out;
    }

    /* ─────────────────────────────  Agregar pedidos por teléfono  ───────────────────────────── */

    /**
     * Para cada teléfono, sus órdenes de Dropi de los últimos DIAS_ATRAS días con el estado de cada una,
     * si se pueden agregar a la vendedora (y si no, por qué) y cuánto se pagaría si ya está entregada.
     *
     * @param string[] $telefonos de 10 dígitos (ver parsearTelefonos)
     * @return array<int, array{telefono: string, ordenes: array<int, array<string, mixed>>}>
     */
    public function revisar(int $vendedoraId, array $telefonos): array
    {
        $vendedora = $this->porId($vendedoraId);
        if (!$vendedora) return [];
        $telefonos = array_slice(array_values(array_unique($telefonos)), 0, self::MAX_TELEFONOS);
        if (!$telefonos) return [];

        $desde = date('Y-m-d H:i:s', strtotime('-' . self::DIAS_ATRAS . ' days'));
        $marcarDesde = strtotime('-' . self::DIAS_MARCADAS . ' days');
        $in = implode(',', array_fill(0, count($telefonos), '?'));
        $stmt = $this->db->prepare("
            SELECT o.id, o.dropi_id, o.telefono, o.fecha_pedido, o.estado, o.estatus, o.guia, o.producto, o.cantidad, o.valor, o.municipio,
                   o.vendedora_id, o.liquidacion_id, o.comision_vendedora,
                   (SELECT COUNT(*) FROM dropi_orden_lineas l WHERE l.orden_id = o.id) AS n_lineas,
                   v.nombre AS vendedora_nombre, q.creada_en AS liquidada_en
            FROM dropi_ordenes o
            LEFT JOIN vendedoras v ON v.id = o.vendedora_id
            LEFT JOIN liquidaciones q ON q.id = o.liquidacion_id
            WHERE RIGHT(o.telefono, 10) IN ({$in}) AND o.fecha_pedido >= ?
            ORDER BY o.fecha_pedido DESC, o.id DESC
        ");
        $stmt->execute(array_merge($telefonos, [$desde]));
        $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $canales = (new DashboardDropi())->canalesDe($filas);
        $porTelefono = [];
        foreach ($filas as $o) {
            $canal = $canales[(int)$o['id']] ?? 'whatsapp';
            [$elegible, $motivo] = self::elegibilidad($o, $canal, $vendedoraId);
            $conocida = (int)$o['n_lineas'] > 0;
            $o['canal'] = $canal;
            $o['elegible'] = $elegible;
            $o['motivo'] = $motivo;
            $o['marcada'] = $elegible && strtotime((string)$o['fecha_pedido']) >= $marcarDesde;
            $o['cantidad_conocida'] = $conocida;
            $o['comision'] = $o['estado'] === 'entregado' ? self::comision((float)$vendedora['valor_pedido'], (int)$o['cantidad'], $conocida) : null;
            $porTelefono[substr(preg_replace('/\D+/', '', (string)$o['telefono']), -10)][] = $o;
        }

        $out = [];
        foreach ($telefonos as $t) $out[] = ['telefono' => $t, 'ordenes' => $porTelefono[$t] ?? []];
        return $out;
    }

    /**
     * Deja las órdenes con la vendedora. No toca las ya liquidadas, las de otra vendedora ni las canceladas.
     *
     * @param int[] $ordenIds
     * @return int cuántas quedaron asignadas
     */
    public function asignar(int $vendedoraId, array $ordenIds): int
    {
        $ids = $this->ids($ordenIds);
        if (!$ids || !$this->porId($vendedoraId)) return 0;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            UPDATE dropi_ordenes SET vendedora_id = ?
            WHERE id IN ({$in}) AND liquidacion_id IS NULL AND vendedora_id IS NULL AND estado <> 'cancelado'
        ");
        $stmt->execute(array_merge([$vendedoraId], $ids));
        return $stmt->rowCount();
    }

    /**
     * Quita de la vendedora órdenes que se le asignaron por error. No quita las ya liquidadas.
     *
     * @param int[] $ordenIds
     */
    public function quitar(int $vendedoraId, array $ordenIds): int
    {
        $ids = $this->ids($ordenIds);
        if (!$ids) return 0;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE dropi_ordenes SET vendedora_id = NULL WHERE id IN ({$in}) AND vendedora_id = ? AND liquidacion_id IS NULL");
        $stmt->execute(array_merge($ids, [$vendedoraId]));
        return $stmt->rowCount();
    }

    /* ─────────────────────────────  Lo de una vendedora  ───────────────────────────── */

    /**
     * Sus pedidos agrupados: por pagar (entregados sin liquidar), en camino, sin pago (devueltos o
     * cancelados) y pagados. Cada fila trae la comisión que le toca si se paga ahora (o la que se pagó).
     *
     * @return array{por_pagar: array, en_camino: array, sin_pago: array, pagadas: array, monto_por_pagar: float, sin_cantidad: int}
     */
    public function pedidosDe(int $vendedoraId): array
    {
        $vendedora = $this->porId($vendedoraId);
        $valor = (float)($vendedora['valor_pedido'] ?? 0);
        $stmt = $this->db->prepare('
            SELECT o.id, o.telefono, o.fecha_pedido, o.estado, o.estatus, o.guia, o.producto, o.cantidad, o.valor, o.municipio,
                   o.liquidacion_id, o.comision_vendedora,
                   (SELECT COUNT(*) FROM dropi_orden_lineas l WHERE l.orden_id = o.id) AS n_lineas,
                   q.creada_en AS liquidada_en
            FROM dropi_ordenes o
            LEFT JOIN liquidaciones q ON q.id = o.liquidacion_id
            WHERE o.vendedora_id = :v
            ORDER BY o.fecha_pedido DESC, o.id DESC
        ');
        $stmt->execute([':v' => $vendedoraId]);

        $g = ['por_pagar' => [], 'en_camino' => [], 'sin_pago' => [], 'pagadas' => [], 'monto_por_pagar' => 0.0, 'sin_cantidad' => 0];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
            $conocida = (int)$o['n_lineas'] > 0;
            $o['cantidad_conocida'] = $conocida;
            if (!empty($o['liquidacion_id'])) {
                $g['pagadas'][] = $o;
            } elseif ($o['estado'] === 'entregado') {
                $o['comision'] = self::comision($valor, (int)$o['cantidad'], $conocida);
                $g['por_pagar'][] = $o;
                $g['monto_por_pagar'] += $o['comision'];
                if (!$conocida) $g['sin_cantidad']++;
            } elseif ($o['estado'] === 'devolucion' || $o['estado'] === 'cancelado') {
                $g['sin_pago'][] = $o;
            } else {
                $g['en_camino'][] = $o;
            }
        }
        return $g;
    }

    /**
     * Paga las órdenes entregadas y sin liquidar de la vendedora (de las que se pasen). Cada orden queda
     * marcada con la liquidación y lo que se pagó, y no vuelve a aparecer como pendiente de pago.
     *
     * @param int[] $ordenIds
     * @return array{id: int, ordenes: int, total: float}|null  null si no había nada que pagar
     * @throws InvalidArgumentException si la vendedora no tiene valor por unidad
     */
    public function liquidar(int $vendedoraId, array $ordenIds, string $nota = ''): ?array
    {
        $vendedora = $this->porId($vendedoraId);
        if (!$vendedora) throw new InvalidArgumentException('Esa vendedora no existe.');
        $valor = (float)$vendedora['valor_pedido'];
        if ($valor <= 0) throw new InvalidArgumentException('Antes de liquidar, ponle a ' . $vendedora['nombre'] . ' el valor que se le paga por unidad.');

        $ids = $this->ids($ordenIds);
        if (!$ids) return null;
        $in = implode(',', array_fill(0, count($ids), '?'));

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                SELECT o.id, o.cantidad, (SELECT COUNT(*) FROM dropi_orden_lineas l WHERE l.orden_id = o.id) AS n_lineas
                FROM dropi_ordenes o
                WHERE o.id IN ({$in}) AND o.vendedora_id = ? AND o.estado = 'entregado' AND o.liquidacion_id IS NULL
                FOR UPDATE
            ");
            $stmt->execute(array_merge($ids, [$vendedoraId]));
            $pagables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$pagables) { $this->db->rollBack(); return null; }

            $this->db->prepare('INSERT INTO liquidaciones (vendedora_id, creada_en, ordenes, total, valor_pedido, nota) VALUES (?, NOW(), 0, 0, ?, ?)')
                ->execute([$vendedoraId, $valor, mb_substr(trim($nota), 0, 255)]);
            $liquidacionId = (int)$this->db->lastInsertId();

            $marcar = $this->db->prepare('UPDATE dropi_ordenes SET liquidacion_id = ?, comision_vendedora = ? WHERE id = ? AND liquidacion_id IS NULL');
            $total = 0.0;
            $n = 0;
            foreach ($pagables as $o) {
                $c = self::comision($valor, (int)$o['cantidad'], (int)$o['n_lineas'] > 0);
                $marcar->execute([$liquidacionId, $c, (int)$o['id']]);
                if ($marcar->rowCount() === 1) { $total += $c; $n++; }
            }
            $this->db->prepare('UPDATE liquidaciones SET ordenes = ?, total = ? WHERE id = ?')->execute([$n, $total, $liquidacionId]);
            $this->db->commit();
            return ['id' => $liquidacionId, 'ordenes' => $n, 'total' => $total];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Las últimas liquidaciones de la vendedora con las órdenes que pagó cada una.
     *
     * @return array<int, array<string, mixed>>
     */
    public function historial(int $vendedoraId, int $limite = 12): array
    {
        $stmt = $this->db->prepare('SELECT id, creada_en, ordenes, total, valor_pedido, nota FROM liquidaciones WHERE vendedora_id = :v ORDER BY id DESC LIMIT :l');
        $stmt->bindValue(':v', $vendedoraId, PDO::PARAM_INT);
        $stmt->bindValue(':l', max(1, min(50, $limite)), PDO::PARAM_INT);
        $stmt->execute();
        $liq = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$liq) return [];

        $ids = array_map('intval', array_column($liq, 'id'));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT o.liquidacion_id, o.telefono, o.fecha_pedido, o.producto, o.cantidad, o.comision_vendedora
            FROM dropi_ordenes o WHERE o.liquidacion_id IN ({$in}) ORDER BY o.fecha_pedido
        ");
        $stmt->execute($ids);
        $porLiq = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $o) $porLiq[(int)$o['liquidacion_id']][] = $o;
        foreach ($liq as &$l) $l['detalle'] = $porLiq[(int)$l['id']] ?? [];
        unset($l);
        return $liq;
    }

    /** @return int[] ids enteros positivos, sin repetir */
    private function ids(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
    }
}
