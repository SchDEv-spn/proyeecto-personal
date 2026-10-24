<?php

/**
 * Ventas REALES para el dashboard, tomadas de las órdenes de Dropi que se importan en
 * Seguimiento (tabla dropi_ordenes). El dashboard de la landing solo ve los pedidos que
 * entran por la landing; aquí entra todo lo que se escribió en Dropi, venga de donde venga
 * (la landing, WhatsApp, las vendedoras).
 *
 * Reglas que conviene no romper:
 *  - Una orden de Dropi se cuenta en el período de su FECHA DE PEDIDO ("de lo que se vendió
 *    en estas fechas, qué se entregó y qué se devolvió"); las recientes siguen en camino.
 *  - Canal: una orden es "landing" si corresponde a un pedido de la landing, y "whatsapp"
 *    (WhatsApp o venta directa) si no. Se reconoce por el ID de Dropi que dejó la API
 *    (pedidos.dropi_order_id) o, si no, por el mismo teléfono (últimos 8 dígitos) y fechas
 *    cercanas. Cada pedido de la landing reclama A LO SUMO UNA orden de Dropi: si el mismo
 *    cliente vuelve a comprar por WhatsApp, esa segunda orden no se le atribuye a la landing.
 *  - La ganancia es la que calcula Dropi y solo existe en las órdenes entregadas. La ganancia
 *    neta le resta el flete de regreso de las devoluciones, que le cobran al dueño.
 *  - Las canceladas no cuentan como órdenes ni como venta.
 *  - Si falla la lectura (tabla ausente, etc.) el dashboard sigue sin esta sección.
 */
class DashboardDropi extends Model
{
    /** La orden de Dropi se escribe después del pedido de la landing: hasta 14 días. */
    private const VENTANA_ADELANTE = 14 * 86400;
    /** …y se deja 1 día hacia atrás por la diferencia de horas entre servidor y archivo. */
    private const VENTANA_ATRAS = 86400;

    private const MAX_PRODUCTOS = 8;

    private const ABREVIATURAS_MES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * Resumen del período [inicio, fin) y, si se pasa, del período anterior (solo totales,
     * para las tendencias).
     *
     * @return array|null  null si no se pudo leer; ['hay_datos' => false] si todavía no se importó nada.
     */
    public function resumen(DateTime $inicio, DateTime $fin, ?DateTime $prevInicio = null, ?DateTime $prevFin = null): ?array
    {
        try {
            $hay = $this->db->query('SELECT COUNT(*) AS n, MAX(visto_at) AS ultima FROM dropi_ordenes')->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') return ['hay_datos' => false]; // la tabla se crea en la primera importación
            throw $e;
        }
        if ((int)$hay['n'] === 0) return ['hay_datos' => false];

        $ordenes = $this->ordenes($inicio, $fin);
        $pedidos = $this->pedidosLanding(
            (clone $inicio)->modify('-' . (self::VENTANA_ADELANTE / 86400) . ' days'),
            (clone $fin)->modify('+1 day')
        );
        $res = self::agregar($ordenes, self::clasificarCanales($ordenes, $pedidos));
        $res['hay_datos'] = true;
        $res['total_ordenes_importadas'] = (int)$hay['n'];
        $res['ultima_actualizacion'] = $hay['ultima'];

        $res['anterior'] = null;
        if ($prevInicio !== null && $prevFin !== null && $prevInicio < $prevFin) {
            $res['anterior'] = self::agregar($this->ordenes($prevInicio, $prevFin), null)['total'];
        }
        return $res;
    }

    /**
     * Órdenes por día (sin canceladas) de los últimos $dias días, separadas por canal,
     * para el gráfico. Incluye los días sin órdenes.
     *
     * @return array{dias: string[], fechas: string[], landing: int[], whatsapp: int[]}
     */
    public function serieDiaria(int $dias = 30, ?DateTime $hoy = null): array
    {
        $dias = max(7, min(90, $dias));
        $hoy  = $hoy ? (clone $hoy) : new DateTime('now', new DateTimeZone('America/Bogota'));
        $fin    = (clone $hoy)->setTime(0, 0, 0)->modify('+1 day');
        $inicio = (clone $fin)->modify("-{$dias} days");

        $ordenes = $this->ordenes($inicio, $fin);
        $canales = self::clasificarCanales($ordenes, $this->pedidosLanding(
            (clone $inicio)->modify('-' . (self::VENTANA_ADELANTE / 86400) . ' days'),
            (clone $fin)->modify('+1 day')
        ));
        return self::serie($ordenes, $canales, $inicio, $dias);
    }

    /* ─────────────────────────────  Lectura  ───────────────────────────── */

    /** @return array<int, array<string, mixed>> */
    private function ordenes(DateTime $inicio, DateTime $fin): array
    {
        $stmt = $this->db->prepare('
            SELECT id, dropi_id, telefono, fecha_pedido, estado, transportadora, producto, valor, ganancia, flete_devolucion
            FROM dropi_ordenes
            WHERE fecha_pedido >= :inicio AND fecha_pedido < :fin
            ORDER BY fecha_pedido, id
        ');
        $stmt->execute([':inicio' => $inicio->format('Y-m-d H:i:s'), ':fin' => $fin->format('Y-m-d H:i:s')]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Pedidos de la landing que pueden corresponder a una orden de Dropi (los cancelados no: nunca se escribieron en Dropi). */
    private function pedidosLanding(DateTime $desde, DateTime $hasta): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, telefono, created_at, dropi_order_id
                FROM pedidos
                WHERE created_at >= :desde AND created_at < :hasta AND estado <> 'cancelado'
                ORDER BY created_at, id
            ");
            $stmt->execute([':desde' => $desde->format('Y-m-d H:i:s'), ':hasta' => $hasta->format('Y-m-d H:i:s')]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[DashboardDropi] pedidos de la landing: ' . $e->getMessage());
            return []; // sin poder cruzar, todo se cuenta como WhatsApp/directo en vez de romper el dashboard
        }
    }

    /* ─────────────────────────────  Cálculo (puro)  ───────────────────────────── */

    /**
     * Qué órdenes de Dropi vienen de la landing y cuáles no.
     *
     * @param array<int, array<string, mixed>> $ordenes filas con id, dropi_id, telefono, fecha_pedido
     * @param array<int, array<string, mixed>> $pedidos  filas con telefono, created_at, dropi_order_id
     * @return array<int, 'landing'|'whatsapp'>  canal por id de orden
     */
    public static function clasificarCanales(array $ordenes, array $pedidos): array
    {
        $canales = [];
        $porDropiId = [];
        $porTelefono = [];
        foreach ($pedidos as $i => $p) {
            $ts = strtotime((string)$p['created_at']);
            if ($ts === false) continue;
            $did = (string)($p['dropi_order_id'] ?? '');
            if ($did !== '' && $did !== '0') $porDropiId[$did] = $i;
            $porTelefono[DropiOrden::sufijo((string)$p['telefono'])][] = ['i' => $i, 'ts' => $ts];
        }

        $usados = [];
        // 1) Vínculo exacto: la API dejó el ID de Dropi en el pedido.
        foreach ($ordenes as $o) {
            $did = (string)($o['dropi_id'] ?? '');
            if ($did !== '' && isset($porDropiId[$did]) && !isset($usados[$porDropiId[$did]])) {
                $usados[$porDropiId[$did]] = true;
                $canales[(int)$o['id']] = 'landing';
            }
        }

        // 2) Mismo teléfono y fechas cercanas, de la orden más antigua a la más nueva; cada pedido se usa una sola vez.
        foreach ($ordenes as $o) {
            $id = (int)$o['id'];
            if (isset($canales[$id])) continue;
            $canales[$id] = 'whatsapp';
            $t = strtotime((string)$o['fecha_pedido']);
            if ($t === false) continue;
            foreach ($porTelefono[DropiOrden::sufijo((string)$o['telefono'])] ?? [] as $c) {
                if (isset($usados[$c['i']])) continue;
                if ($t >= $c['ts'] - self::VENTANA_ATRAS && $t <= $c['ts'] + self::VENTANA_ADELANTE) {
                    $usados[$c['i']] = true;
                    $canales[$id] = 'landing';
                    break;
                }
            }
        }
        return $canales;
    }

    /**
     * Totales y desgloses de una lista de órdenes. Con $canales en null no se hace el desglose por canal.
     *
     * @return array{total: array, canal: array, transportadora: array, producto: array}
     */
    public static function agregar(array $ordenes, ?array $canales): array
    {
        $total = self::vacio();
        $porCanal = ['landing' => self::vacio(), 'whatsapp' => self::vacio()];
        $porTransp = [];
        $porProducto = [];

        foreach ($ordenes as $o) {
            self::sumar($total, $o);
            if ($canales !== null) self::sumar($porCanal[$canales[(int)$o['id']] ?? 'whatsapp'], $o);

            $t = self::nombreTransportadora((string)($o['transportadora'] ?? ''));
            $porTransp[$t] ??= self::vacio();
            self::sumar($porTransp[$t], $o);

            $p = trim((string)($o['producto'] ?? '')) !== '' ? trim((string)$o['producto']) : 'Sin producto';
            $porProducto[$p] ??= self::vacio();
            self::sumar($porProducto[$p], $o);
        }

        $lista = static function (array $grupos): array {
            $out = [];
            foreach ($grupos as $nombre => $b) {
                if ($b['ordenes'] === 0 && $b['canceladas'] === 0) continue;
                $out[] = ['nombre' => (string)$nombre] + self::derivados($b);
            }
            usort($out, fn($a, $b) => [$b['ordenes'], $b['ganancia_neta']] <=> [$a['ordenes'], $a['ganancia_neta']]);
            return $out;
        };

        $productos = $lista($porProducto);
        return [
            'total'          => self::derivados($total),
            'canal'          => $canales !== null ? ['landing' => self::derivados($porCanal['landing']), 'whatsapp' => self::derivados($porCanal['whatsapp'])] : [],
            'transportadora' => $lista($porTransp),
            'producto'       => array_slice($productos, 0, self::MAX_PRODUCTOS),
            'productos_extra' => max(0, count($productos) - self::MAX_PRODUCTOS),
        ];
    }

    /**
     * Órdenes por día (sin canceladas) por canal, con todos los días del período.
     *
     * @return array{dias: string[], fechas: string[], landing: int[], whatsapp: int[]}
     */
    public static function serie(array $ordenes, array $canales, DateTime $inicio, int $dias): array
    {
        $out = ['dias' => [], 'fechas' => [], 'landing' => [], 'whatsapp' => []];
        $indice = [];
        $d = (clone $inicio)->setTime(0, 0, 0);
        for ($i = 0; $i < $dias; $i++) {
            $iso = $d->format('Y-m-d');
            $indice[$iso] = $i;
            $out['fechas'][] = $iso;
            $out['dias'][]   = $d->format('j') . ' ' . self::ABREVIATURAS_MES[(int)$d->format('n') - 1];
            $out['landing'][] = 0;
            $out['whatsapp'][] = 0;
            $d->modify('+1 day');
        }
        foreach ($ordenes as $o) {
            if (($o['estado'] ?? '') === 'cancelado') continue;
            $iso = substr((string)$o['fecha_pedido'], 0, 10);
            if (!isset($indice[$iso])) continue;
            $out[($canales[(int)$o['id']] ?? 'whatsapp') === 'landing' ? 'landing' : 'whatsapp'][$indice[$iso]]++;
        }
        return $out;
    }

    private static function nombreTransportadora(string $t): string
    {
        if (trim($t) === '') return 'Sin dato';
        $info = DropiOrden::transportadoraInfo($t);
        return $info['nombre'] ?? (mb_strtoupper(mb_substr($t, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($t, 1, null, 'UTF-8'), 'UTF-8'));
    }

    private static function vacio(): array
    {
        return [
            'ordenes' => 0, 'canceladas' => 0, 'vendido' => 0.0,
            'entregadas' => 0, 'valor_entregado' => 0.0, 'ganancia' => 0.0, 'entregadas_sin_ganancia' => 0,
            'devoluciones' => 0, 'valor_devuelto' => 0.0, 'flete_devolucion' => 0.0,
            'en_proceso' => 0, 'valor_en_proceso' => 0.0,
        ];
    }

    private static function sumar(array &$b, array $o): void
    {
        $estado = (string)($o['estado'] ?? '');
        $valor  = (float)($o['valor'] ?? 0);

        if ($estado === 'cancelado') { $b['canceladas']++; return; }

        $b['ordenes']++;
        $b['vendido'] += $valor;
        if ($estado === 'entregado') {
            $b['entregadas']++;
            $b['valor_entregado'] += $valor;
            if (($o['ganancia'] ?? null) === null || $o['ganancia'] === '') $b['entregadas_sin_ganancia']++;
            else $b['ganancia'] += (float)$o['ganancia'];
        } elseif ($estado === 'devolucion') {
            $b['devoluciones']++;
            $b['valor_devuelto'] += $valor;
            $b['flete_devolucion'] += (float)($o['flete_devolucion'] ?? 0);
        } else {
            $b['en_proceso']++;
            $b['valor_en_proceso'] += $valor;
        }
    }

    /** Agrega lo que se calcula a partir de los totales: órdenes cerradas, tasas y ganancia neta. */
    private static function derivados(array $b): array
    {
        $cerradas = $b['entregadas'] + $b['devoluciones'];
        $b['cerradas']         = $cerradas;
        $b['tasa_entrega']     = $cerradas > 0 ? $b['entregadas'] / $cerradas : null;
        $b['tasa_devolucion']  = $cerradas > 0 ? $b['devoluciones'] / $cerradas : null;
        $b['ganancia_neta']    = $b['ganancia'] - $b['flete_devolucion'];
        return $b;
    }
}
