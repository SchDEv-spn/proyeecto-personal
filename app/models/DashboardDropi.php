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
 *  - Canal: todo pedido que entra por la landing es "landing", esté o no ya en Dropi; lo demás que
 *    hay en Dropi (WhatsApp, venta directa) es "whatsapp". Un pedido de la landing se reconoce en
 *    Dropi por el ID que dejó la API (pedidos.dropi_order_id) o, si no, por el mismo teléfono (últimos
 *    8 dígitos) y fechas cercanas. Cada pedido de la landing reclama A LO SUMO UNA orden de Dropi: si el
 *    mismo cliente vuelve a comprar por WhatsApp, esa segunda orden no se le atribuye a la landing.
 *  - Un pedido de la landing que todavía no está en Dropi cuenta con su propio estado (nuevo, contactado,
 *    confirmado, cancelado…): los nuevos y contactados son "por confirmar". Cuando ya está en Dropi manda
 *    el estado de Dropi, pero la fecha sigue siendo la del pedido de la landing.
V);
una($s, <<<'V'
 *  - Las canceladas no cuentan como órdenes ni como venta (sí aparecen como "creadas" en el
 *    embudo y en la distribución por estado).
V, <<<'N'
 *  - Las canceladas cuentan como órdenes (se muestran, y salen aparte como "canceladas"), pero no como
 *    venta ni como dinero por cobrar.
 *  - La ganancia es la que calcula Dropi y solo existe en las órdenes entregadas. La ganancia
 *    neta le resta el flete de regreso de las devoluciones, que le cobran al dueño.
 *  - Las canceladas no cuentan como órdenes ni como venta (sí aparecen como "creadas" en el
 *    embudo y en la distribución por estado).
 *  - El embudo de WhatsApp empieza en la orden de Dropi: los chats que no llegaron a ser
 *    orden no se ven (no hay forma de saberlo desde Dropi).
 *  - Si falla la lectura (tabla ausente, etc.) el dashboard sigue sin esta sección.
 */
class DashboardDropi extends Model
{
    public const RANGOS = ['hoy', 'ayer', 'semana', 'mes', 'mes_pasado', 'trimestre', 'siempre', 'personalizado'];
    public const CANALES = ['todos', 'landing', 'whatsapp'];

    /** La orden de Dropi se escribe después del pedido de la landing: hasta 14 días. */
    private const VENTANA_ADELANTE = 14 * 86400;
    /** …y se deja 1 día hacia atrás por la diferencia de horas entre servidor y archivo. */
    private const VENTANA_ATRAS = 86400;

    private const MAX_PRODUCTOS = 8;
    /** Hasta cuántos días se grafica por día; más allá, por semana (y por mes después de ~2 años). */
    private const MAX_DIAS_POR_DIA = 92;
    private const MAX_DIAS_POR_SEMANA = 750;

    private const ABREVIATURAS_MES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    private const ESTADOS = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'devolucion', 'otro', 'cancelado'];
    /** Lo que se ve de un pedido de la landing que todavía no está en Dropi. */
    private const ESTADOS_PEDIDO = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];

    /* ─────────────────────────────  Período  ───────────────────────────── */

    /**
     * Ventana de fechas de un rango del dashboard y la ventana anterior con la que se compara.
     * Si el período sigue en curso (el mes a medias), la anterior tiene la misma duración que lo
     * transcurrido, para no comparar un mes a medias con uno completo.
     *
     * @return array{rango: string, etiqueta: string, desde: ?string, hasta: ?string, inicio: DateTime, fin: DateTime,
     *               prev_inicio: ?DateTime, prev_fin: ?DateTime, ahora: DateTime}
     */
    public static function periodo(string $rango, ?string $desde = null, ?string $hasta = null, ?DateTime $ahora = null): array
    {
        $tz    = new DateTimeZone('America/Bogota');
        $ahora = $ahora ? (clone $ahora) : new DateTime('now', $tz);
        $hoy   = (clone $ahora)->setTime(0, 0, 0);
        if (!in_array($rango, self::RANGOS, true)) $rango = 'mes';

        $fecha = static function (?string $s) use ($tz): ?DateTime {
            if ($s === null || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return null;
            return new DateTime($s . ' 00:00:00', $tz);
        };
        $d = $rango === 'personalizado' ? $fecha($desde) : null;
        $h = $rango === 'personalizado' ? $fecha($hasta) : null;
        if ($rango === 'personalizado' && (!$d || !$h || $d > $h)) $rango = 'mes';

        $prevCompleto = null; // la ventana anterior cuando el período ya terminó y es calendario (mes, trimestre)
        switch ($rango) {
            case 'hoy':
                $inicio = $hoy; $fin = (clone $hoy)->modify('+1 day'); $etiqueta = 'Hoy'; break;
            case 'ayer':
                $fin = $hoy; $inicio = (clone $hoy)->modify('-1 day'); $etiqueta = 'Ayer'; break;
            case 'semana':
                $inicio = (clone $hoy)->modify('monday this week'); $fin = (clone $inicio)->modify('+1 week'); $etiqueta = 'Esta semana'; break;
            case 'mes_pasado':
                $fin = (clone $hoy)->modify('first day of this month'); $inicio = (clone $fin)->modify('first day of last month'); $etiqueta = 'Mes pasado';
                $prevCompleto = [(clone $inicio)->modify('first day of last month'), $inicio]; break;
            case 'trimestre':
                $mes = (int)$hoy->format('n');
                $inicio = (clone $hoy)->setDate((int)$hoy->format('Y'), $mes - (($mes - 1) % 3), 1);
                $fin = (clone $inicio)->modify('+3 months'); $etiqueta = 'Este trimestre';
                $prevCompleto = [(clone $inicio)->modify('-3 months'), $inicio]; break;
            case 'siempre':
                $inicio = new DateTime('2000-01-01 00:00:00', $tz); $fin = (clone $hoy)->modify('+1 day'); $etiqueta = 'Desde siempre'; break;
            case 'personalizado':
                $inicio = $d; $fin = (clone $h)->modify('+1 day');
                $etiqueta = $d->format('d/m/Y') . ' → ' . $h->format('d/m/Y'); break;
            case 'mes':
            default:
                $rango = 'mes';
                $inicio = (clone $hoy)->modify('first day of this month'); $fin = (clone $inicio)->modify('first day of next month'); $etiqueta = 'Este mes';
                $prevCompleto = [(clone $inicio)->modify('first day of last month'), $inicio]; break;
        }

        $prevInicio = $prevFin = null;
        if ($rango !== 'siempre') {
            if ($fin <= $ahora) {
                // El período ya terminó: se compara con el anterior completo.
                [$prevInicio, $prevFin] = $prevCompleto ?? [
                    (clone $inicio)->modify('-' . max(1, (int)round(($fin->getTimestamp() - $inicio->getTimestamp()) / 86400)) . ' days'), $inicio,
                ];
            } else {
                // En curso: lo mismo que lleva transcurrido (como mínimo un día), justo antes del inicio.
                $transcurrido = max(86400, $ahora->getTimestamp() - $inicio->getTimestamp());
                $prevFin = $inicio;
                $prevInicio = (clone $inicio)->modify('-' . (int)ceil($transcurrido) . ' seconds');
            }
        }

        return [
            'rango' => $rango, 'etiqueta' => $etiqueta,
            'desde' => $rango === 'personalizado' ? $d->format('Y-m-d') : null,
            'hasta' => $rango === 'personalizado' ? $h->format('Y-m-d') : null,
            'inicio' => $inicio, 'fin' => $fin, 'prev_inicio' => $prevInicio, 'prev_fin' => $prevFin, 'ahora' => $ahora,
        ];
    }

    /* ─────────────────────────────  Resumen  ───────────────────────────── */

    /**
     * Resumen del período. $canal ('todos', 'landing' o 'whatsapp') recorta tarjetas, tablas de
     * transportadora/producto, estados y gráficos; el desglose por canal y los embudos siempre
     * traen los dos canales, para compararlos.
     *
     * @return array|null  ['hay_datos' => false] si no hay ni órdenes de Dropi ni pedidos de la landing.
     */
    public function resumen(array $periodo, string $canal = 'todos'): ?array
    {
        if (!in_array($canal, self::CANALES, true)) $canal = 'todos';

        $dropi = $this->contarDropi();
        if ($dropi['n'] === 0 && $this->contarPedidos() === 0) return ['hay_datos' => false];

        $inicio = $periodo['inicio'];
        $fin    = $periodo['fin'];
        $hayPrev = $periodo['prev_inicio'] !== null && $periodo['prev_fin'] !== null && $periodo['prev_inicio'] < $periodo['prev_fin'];

        $entradas   = $this->entradas($inicio, $fin);
        $anteriores = $hayPrev ? $this->entradas($periodo['prev_inicio'], $periodo['prev_fin']) : [];

        $filtrar = static fn(array $es): array => $canal === 'todos' ? $es
            : array_values(array_filter($es, fn($o) => $o['canal'] === $canal));

        $res   = self::agregar($filtrar($entradas), []);   // lo que se ve según el canal elegido
        $ambos = self::agregar($entradas, []);             // comparación entre canales
        $res['canal']   = $ambos['canal'];
        $res['embudos'] = ['landing' => $ambos['canal']['landing'], 'whatsapp' => $ambos['canal']['whatsapp']];
        $res['anterior'] = $hayPrev ? self::agregar($filtrar($anteriores), [])['total'] : null;

        // "Desde siempre" arranca en la primera orden, no en el año 2000.
        $inicioSerie = $inicio;
        if ((int)$inicio->format('Y') < 2001) {
            $primera = null;
            foreach ($entradas as $o) if ($primera === null || $o['fecha_pedido'] < $primera) $primera = $o['fecha_pedido'];
            $inicioSerie = $primera !== null ? new DateTime(substr((string)$primera, 0, 10) . ' 00:00:00', $inicio->getTimezone()) : (clone $fin)->modify('-1 day');
        }
        $res['serie'] = self::serie($entradas, [], $inicioSerie, min($fin, (clone $periodo['ahora'])->modify('+1 second')));

        $res['hay_datos'] = true;
        $res['hay_dropi'] = $dropi['n'] > 0;
        $res['canal_sel'] = $canal;
        $res['periodo'] = ['rango' => $periodo['rango'], 'etiqueta' => $periodo['etiqueta'], 'desde' => $periodo['desde'], 'hasta' => $periodo['hasta']];
        $res['total_ordenes_importadas'] = $dropi['n'];
        $res['ultima_actualizacion'] = $dropi['ultima'];
        return $res;
    }

    /**
     * Canal de un grupo de órdenes ya leídas (por ejemplo las de un teléfono): busca los pedidos de la
     * landing que pueden corresponderles y aplica la misma regla del dashboard.
     *
     * @param array<int, array<string, mixed>> $ordenes filas con id, dropi_id, telefono, fecha_pedido
     * @return array<int, 'landing'|'whatsapp'>
     */
    public function canalesDe(array $ordenes): array
    {
        if (!$ordenes) return [];
        usort($ordenes, fn($a, $b) => [$a['fecha_pedido'], (int)$a['id']] <=> [$b['fecha_pedido'], (int)$b['id']]);
        try {
            $min = new DateTime((string)$ordenes[0]['fecha_pedido']);
            $max = new DateTime((string)$ordenes[count($ordenes) - 1]['fecha_pedido']);
        } catch (Exception $e) {
            return array_fill_keys(array_map('intval', array_column($ordenes, 'id')), 'whatsapp');
        }
        return self::clasificarCanales($ordenes, $this->pedidosLanding(
            (clone $min)->modify('-' . (self::VENTANA_ADELANTE / 86400) . ' days'),
            (clone $max)->modify('+2 days')
        ));
    }

    /* ─────────────────────────────  Lectura  ───────────────────────────── */

    /** @return array{n: int, ultima: ?string}  cuántas órdenes de Dropi hay (0 si la tabla todavía no existe) */
    private function contarDropi(): array
    {
        try {
            $r = $this->db->query('SELECT COUNT(*) AS n, MAX(visto_at) AS ultima FROM dropi_ordenes')->fetch(PDO::FETCH_ASSOC);
            return ['n' => (int)$r['n'], 'ultima' => $r['ultima'] ?: null];
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') return ['n' => 0, 'ultima' => null]; // la tabla se crea en la primera importación
            throw $e;
        }
    }

    private function contarPedidos(): int
    {
        try {
            return (int)$this->db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
        } catch (PDOException $e) {
            error_log('[DashboardDropi] pedidos de la landing: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Las órdenes del período (de Dropi y de la landing) ya unificadas: ver construirEntradas. Se leen
     * con margen —30 días antes y hasta 15 después— para que el cruce entre pedidos y órdenes sea el
     * mismo sin importar dónde caiga el borde del período.
     *
     * @return array<int, array<string, mixed>>
     */
    private function entradas(DateTime $desde, DateTime $hasta): array
    {
        $antes = (clone $desde)->modify('-30 days');
        return self::construirEntradas(
            $this->pedidosDelPeriodo($desde, $hasta),
            $this->ordenes($antes, (clone $hasta)->modify('+' . (self::VENTANA_ADELANTE / 86400 + 1) . ' days')),
            $this->pedidosLanding($antes, (clone $hasta)->modify('+1 day')),
            $desde,
            $hasta
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function ordenes(DateTime $inicio, DateTime $fin): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT id, dropi_id, telefono, fecha_pedido, estado, transportadora, producto, valor, ganancia, flete_devolucion
                FROM dropi_ordenes
                WHERE fecha_pedido >= :inicio AND fecha_pedido < :fin
                ORDER BY fecha_pedido, id
            ');
            $stmt->execute([':inicio' => $inicio->format('Y-m-d H:i:s'), ':fin' => $fin->format('Y-m-d H:i:s')]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($e->getCode() === '42S02') return [];
            throw $e;
        }
    }

    /** Todos los pedidos que entraron por la landing en el período, en cualquier estado (también los cancelados). */
    private function pedidosDelPeriodo(DateTime $desde, DateTime $hasta): array
    {
        try {
            $stmt = $this->db->prepare('
                SELECT p.id, p.telefono, p.created_at, p.estado, p.precio_total, pr.nombre AS producto
                FROM pedidos p
                LEFT JOIN productos pr ON pr.id = p.producto_id
                WHERE p.created_at >= :desde AND p.created_at < :hasta
                ORDER BY p.created_at, p.id
            ');
            $stmt->execute([':desde' => $desde->format('Y-m-d H:i:s'), ':hasta' => $hasta->format('Y-m-d H:i:s')]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('[DashboardDropi] pedidos del período: ' . $e->getMessage());
            return [];
        }
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
     * Qué pedido de la landing corresponde a cada orden de Dropi. Las órdenes deben venir de la más
     * antigua a la más nueva: cada una reclama el primer pedido libre que le corresponde, y un pedido
     * lo reclama una sola orden.
     *
     * @param array<int, array<string, mixed>> $ordenes filas con id, dropi_id, telefono, fecha_pedido
     * @param array<int, array<string, mixed>> $pedidos  filas con id, telefono, created_at, dropi_order_id
     * @return array<int, int>  id de pedido por id de orden (solo las órdenes que vienen de la landing)
     */
    public static function emparejar(array $ordenes, array $pedidos): array
    {
        $pares = [];
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
                $pares[(int)$o['id']] = (int)$pedidos[$porDropiId[$did]]['id'];
            }
        }

        // 2) Mismo teléfono y fechas cercanas, de la orden más antigua a la más nueva; cada pedido se usa una sola vez.
        foreach ($ordenes as $o) {
            $id = (int)$o['id'];
            if (isset($pares[$id])) continue;
            $t = strtotime((string)$o['fecha_pedido']);
            if ($t === false) continue;
            foreach ($porTelefono[DropiOrden::sufijo((string)$o['telefono'])] ?? [] as $c) {
                if (isset($usados[$c['i']])) continue;
                if ($t >= $c['ts'] - self::VENTANA_ATRAS && $t <= $c['ts'] + self::VENTANA_ADELANTE) {
                    $usados[$c['i']] = true;
                    $pares[$id] = (int)$pedidos[$c['i']]['id'];
                    break;
                }
            }
        }
        return $pares;
    }

    /**
     * Qué órdenes de Dropi vienen de la landing y cuáles no.
     *
     * @return array<int, 'landing'|'whatsapp'>  canal por id de orden
     */
    public static function clasificarCanales(array $ordenes, array $pedidos): array
    {
        $pares = self::emparejar($ordenes, $pedidos);
        $canales = [];
        foreach ($ordenes as $o) $canales[(int)$o['id']] = isset($pares[(int)$o['id']]) ? 'landing' : 'whatsapp';
        return $canales;
    }

    /** Estado con el que se cuenta un pedido de la landing que todavía no está en Dropi. */
    private static function estadoDePedido(string $estado): string
    {
        return in_array($estado, self::ESTADOS_PEDIDO, true) ? $estado : 'nuevo';
    }

    /**
     * Las órdenes del período, de todos los canales, listas para sumar:
     *  - cada pedido de la landing del período (cualquier estado): si ya está en Dropi manda el estado, el
     *    dinero y la transportadora de Dropi; si no, cuenta con su propio estado y su precio. La fecha es la
     *    del pedido, esté donde esté la orden de Dropi;
     *  - cada orden de Dropi del período que NO corresponde a un pedido de la landing: es de WhatsApp.
     *
     * @param array $pedidosPeriodo     pedidos de la landing creados en [desde, hasta), en cualquier estado
     * @param array $ordenes            órdenes de Dropi con margen alrededor del período
     * @param array $pedidosQueReclaman pedidos (sin los cancelados) con margen, para el cruce
     * @return array<int, array<string, mixed>>  id, canal, estado, fecha_pedido, valor, ganancia, flete_devolucion, transportadora, producto, en_dropi
     */
    public static function construirEntradas(array $pedidosPeriodo, array $ordenes, array $pedidosQueReclaman, DateTime $desde, DateTime $hasta): array
    {
        usort($ordenes, fn($a, $b) => [$a['fecha_pedido'], (int)$a['id']] <=> [$b['fecha_pedido'], (int)$b['id']]);
        $pares = self::emparejar($ordenes, $pedidosQueReclaman);
        $ordenDe = array_flip($pares); // pedido → orden
        $porId = [];
        foreach ($ordenes as $o) $porId[(int)$o['id']] = $o;

        $entrada = static fn(string $id, string $canal, string $estado, $fecha, array $o, string $producto, bool $enDropi): array => [
            'id' => $id, 'canal' => $canal, 'estado' => $estado, 'fecha_pedido' => (string)$fecha,
            'valor' => (float)($o['valor'] ?? 0), 'ganancia' => $o['ganancia'] ?? null, 'flete_devolucion' => (float)($o['flete_devolucion'] ?? 0),
            'transportadora' => (string)($o['transportadora'] ?? ''), 'producto' => $producto, 'en_dropi' => $enDropi,
        ];

        $entradas = [];
        foreach ($pedidosPeriodo as $p) {
            $oid = $ordenDe[(int)$p['id']] ?? null;
            $o = $oid !== null ? ($porId[$oid] ?? null) : null;
            if ($o !== null) {
                $producto = trim((string)$o['producto']) !== '' ? trim((string)$o['producto']) : trim((string)($p['producto'] ?? ''));
                $entradas[] = $entrada('p' . $p['id'], 'landing', (string)$o['estado'], $p['created_at'], $o, $producto, true);
            } else {
                $entradas[] = $entrada('p' . $p['id'], 'landing', self::estadoDePedido((string)$p['estado']), $p['created_at'],
                    ['valor' => $p['precio_total'] ?? 0], trim((string)($p['producto'] ?? '')), false);
            }
        }

        $d = $desde->format('Y-m-d H:i:s');
        $h = $hasta->format('Y-m-d H:i:s');
        foreach ($ordenes as $o) {
            if (isset($pares[(int)$o['id']])) continue; // ya cuenta con su pedido de la landing
            $f = (string)$o['fecha_pedido'];
            if ($f < $d || $f >= $h) continue;
            $entradas[] = $entrada('o' . $o['id'], 'whatsapp', (string)$o['estado'], $f, $o, trim((string)$o['producto']), true);
        }
        return $entradas;
    }
    /**
     * Totales y desgloses de una lista de órdenes. Con $canales en null no se hace el desglose por canal; con [] se hace
     * por el campo "canal" de cada orden (como las que arma construirEntradas) y con un mapa id → canal, por ese mapa.
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
            if ($canales !== null) self::sumar($porCanal[($o['canal'] ?? ($canales[(int)$o['id']] ?? 'whatsapp')) === 'landing' ? 'landing' : 'whatsapp'], $o);

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
                if ($b['ordenes'] === 0) continue;
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
     * Órdenes (con las canceladas) y ventas ($, sin ellas) por franja de tiempo y por canal. La franja se adapta al
     * largo del período: por hora si es de un día, por día hasta ~3 meses, por semana hasta ~2 años y
     * por mes después. No grafica el futuro: termina en $hasta.
     *
     * @return array{paso: string, etiquetas: string[], claves: string[],
     *               landing: array{ordenes: int[], ventas: float[]}, whatsapp: array{ordenes: int[], ventas: float[]}}
     */
    public static function serie(array $ordenes, array $canales, DateTime $inicio, DateTime $hasta): array
    {
        $dias = max(1, (int)ceil(($hasta->getTimestamp() - $inicio->getTimestamp()) / 86400));
        $paso = $dias <= 1 ? 'hora' : ($dias <= self::MAX_DIAS_POR_DIA ? 'dia' : ($dias <= self::MAX_DIAS_POR_SEMANA ? 'semana' : 'mes'));

        $clave = static function (DateTime $t) use ($paso): string {
            switch ($paso) {
                case 'hora':   return $t->format('Y-m-d H');
                case 'dia':    return $t->format('Y-m-d');
                case 'semana': return (clone $t)->modify('monday this week')->format('Y-m-d');
                default:       return $t->format('Y-m');
            }
        };
        $etiqueta = static function (DateTime $t) use ($paso): string {
            $mes = self::ABREVIATURAS_MES[(int)$t->format('n') - 1];
            switch ($paso) {
                case 'hora':   return $t->format('G') . 'h';
                case 'dia':    return $t->format('j') . ' ' . $mes;
                case 'semana': return 'sem ' . $t->format('j') . ' ' . $mes;
                default:       return $mes . ' ' . $t->format('Y');
            }
        };

        // Primera franja alineada (el lunes de esa semana, el primer día del mes…).
        $t = clone $inicio;
        if ($paso === 'semana') $t = (clone $t)->setTime(0, 0, 0)->modify('monday this week');
        if ($paso === 'mes')    $t = (clone $t)->setTime(0, 0, 0)->modify('first day of this month');
        if ($paso === 'dia')    $t->setTime(0, 0, 0);
        if ($paso === 'hora')   $t->setTime((int)$t->format('G'), 0, 0);
        $salto = ['hora' => '+1 hour', 'dia' => '+1 day', 'semana' => '+1 week', 'mes' => '+1 month'][$paso];

        $out = ['paso' => $paso, 'etiquetas' => [], 'claves' => [],
                'landing' => ['ordenes' => [], 'ventas' => []], 'whatsapp' => ['ordenes' => [], 'ventas' => []]];
        $indice = [];
        $limite = 0;
        while ($t < $hasta && $limite++ < 400) {
            $k = $clave($t);
            if (!isset($indice[$k])) {
                $indice[$k] = count($out['claves']);
                $out['claves'][] = $k;
                $out['etiquetas'][] = $etiqueta($t);
                foreach (['landing', 'whatsapp'] as $c) { $out[$c]['ordenes'][] = 0; $out[$c]['ventas'][] = 0.0; }
            }
            $t = (clone $t)->modify($salto);
        }

        foreach ($ordenes as $o) {
            try { $f = new DateTime((string)$o['fecha_pedido']); } catch (Exception $e) { continue; }
            $k = $clave($f);
            if (!isset($indice[$k])) continue;
            $c = ($o['canal'] ?? ($canales[(int)$o['id']] ?? 'whatsapp')) === 'landing' ? 'landing' : 'whatsapp';
            $out[$c]['ordenes'][$indice[$k]]++;
            if (($o['estado'] ?? '') !== 'cancelado') $out[$c]['ventas'][$indice[$k]] += (float)($o['valor'] ?? 0);
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
            'por_confirmar' => 0, 'valor_por_confirmar' => 0.0,
            'estados' => array_fill_keys(self::ESTADOS, 0),
        ];
    }

    private static function sumar(array &$b, array $o): void
    {
        $estado = (string)($o['estado'] ?? '');
        $valor  = (float)($o['valor'] ?? 0);
        $b['estados'][$estado] = ($b['estados'][$estado] ?? 0) + 1;

        $b['ordenes']++;
        if ($estado === 'cancelado') { $b['canceladas']++; return; }

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
        } elseif ($estado === 'nuevo' || $estado === 'contactado') {
            // Pedido de la landing que todavía no se confirma ni entra a Dropi.
            $b['por_confirmar']++;
            $b['valor_por_confirmar'] += $valor;
        } else {
            $b['en_proceso']++;
            $b['valor_en_proceso'] += $valor;
        }
    }

    /**
     * Agrega lo que sale de los totales: órdenes cerradas, tasas, ganancia neta y los números del embudo
     * (creadas → despachadas → entregadas, con devueltas, canceladas, por confirmar y en camino).
     */
    private static function derivados(array $b): array
    {
        $cerradas = $b['entregadas'] + $b['devoluciones'];
        $b['cerradas']         = $cerradas;
        $b['tasa_entrega']     = $cerradas > 0 ? $b['entregadas'] / $cerradas : null;
        $b['tasa_devolucion']  = $cerradas > 0 ? $b['devoluciones'] / $cerradas : null;
        $b['ganancia_neta']    = $b['ganancia'] - $b['flete_devolucion'];

        $e = $b['estados'];
        $b['creadas']     = $b['ordenes'];
        // Salieron de la bodega: todo lo que ya no está solo "confirmado" ni cancelado.
        $b['despachadas'] = $b['entregadas'] + $b['devoluciones'] + $e['enviado'] + $e['en_oficina'] + $e['otro'];
        return $b;
    }
}
