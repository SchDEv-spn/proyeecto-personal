<?php

/**
 * "Actualizar ahora" de Seguimiento (y cron_dropi_mcp_sync.php): trae de Dropi, por su
 * conector MCP, las órdenes de los últimos 30 días y
 *   - a las que YA están guardadas les pone al día estatus y guía (DropiOrden::
 *     actualizarEstadoDesdeMcp — solo esos dos campos, nunca novedad/dinero/departamento);
 *   - a las NUEVAS las crea con lo que el MCP trae (DropiImport::ordenDesdeMcp + procesar,
 *     las mismas reglas del Excel: estado, avisos ya enviados, producto de la tienda…).
 * Así no hace falta subir el Excel todos los días. El Excel sigue siendo la fuente completa:
 * trae lo que el MCP no (texto de la novedad, último movimiento, costo/flete/comisión/
 * ganancia, departamento) y al subirlo completa estas órdenes sin duplicarlas.
 *
 * Llama a Dropi directo (DropiMcpCliente), sin un modelo de lenguaje en medio: los nombres
 * y teléfonos de los clientes llegan tal cual.
 */
class DropiMcpSync
{
    /** Los mismos ~30 días que se descargan del Excel: cualquier orden con entrega pendiente cabe. */
    private const DIAS_VENTANA = 30;

    private const POR_PAGINA = 100;
    private const MAX_PAGINAS = 20;

    /**
     * Tope de órdenes nuevas por corrida: cada una es una consulta get_order (~0,6 s) y el botón corre dentro
     * de una petición web con su límite de tiempo. El resto entra en la siguiente corrida.
     */
    private const MAX_NUEVAS = 30;

    /**
     * @return array{ok: bool, error?: string, total?: int, nuevas?: int, actualizadas?: int, iguales?: int, omitidas?: int, pendientes?: int, sin_clasificar?: array<string,int>}
     */
    public function ejecutar(?DropiMcpCliente $cliente = null): array
    {
        set_time_limit(280);

        if ($cliente === null) {
            $tok = (new DropiMcpAuth())->tokenVigente();
            if (!$tok['ok']) return ['ok' => false, 'error' => $tok['error']];
            $cliente = new DropiMcpCliente($tok['token']);
        }

        $lista = $this->traerOrdenes($cliente);
        if (!$lista['ok']) return $lista;
        $filas = $lista['ordenes'];

        $ordenModel = new DropiOrden();
        $existentes = $ordenModel->idsExistentes(array_keys($filas));

        $conteo = ['actualizadas' => 0, 'iguales' => 0, 'omitidas' => 0];
        $nuevasFilas = [];
        foreach ($filas as $id => $f) {
            if (isset($existentes[$id])) {
                $r = $ordenModel->actualizarEstadoDesdeMcp($id, (string)($f['status'] ?? ''), (string)($f['tracking_code'] ?? ''));
                $conteo[$r === 'actualizada' ? 'actualizadas' : ($r === 'igual' ? 'iguales' : 'omitidas')]++;
            } else {
                $nuevasFilas[$id] = $f;
            }
        }

        $nuevas = $this->crearNuevas($cliente, $nuevasFilas, $conteo['omitidas']);
        if (!$nuevas['ok']) return $nuevas;

        $total = count($filas);
        if ($total > 0) {
            $ordenModel->registrarImportacion('cron-mcp', 'via-mcp', $total, $nuevas['creadas'], $conteo['actualizadas'], $conteo['iguales']);
        }

        // Sin vista previa (como la del Excel), los estatus que Seguimiento no conoce se avisan aquí: esas órdenes
        // quedan "Sin clasificar" y fuera de las listas de avisos hasta que se decida qué estado les toca.
        $sinClasificar = [];
        foreach ($filas as $f) {
            $st = mb_strtoupper(trim((string)($f['status'] ?? '')), 'UTF-8');
            if (DropiImport::estatusSinClasificar($st)) $sinClasificar[$st] = ($sinClasificar[$st] ?? 0) + 1;
        }

        return ['ok' => true, 'total' => $total, 'nuevas' => $nuevas['creadas'], 'pendientes' => $nuevas['pendientes'], 'sin_clasificar' => $sinClasificar] + $conteo;
    }

    /**
     * Todas las órdenes de la ventana, páginas de 100 hasta agotarlas.
     *
     * @return array{ok: bool, error?: string, ordenes?: array<string, array<string,string>>}  indexadas por ID de Dropi
     */
    private function traerOrdenes(DropiMcpCliente $cliente): array
    {
        $desde = date('Y-m-d', strtotime('-' . self::DIAS_VENTANA . ' days'));
        $hasta = date('Y-m-d', strtotime('+1 day')); // mañana: no depender de la zona horaria del servidor

        $ordenes = [];
        for ($pagina = 0; $pagina < self::MAX_PAGINAS; $pagina++) {
            $r = $cliente->llamar('list_orders', [
                'from' => $desde, 'until' => $hasta,
                'result_number' => self::POR_PAGINA, 'start' => $pagina * self::POR_PAGINA,
            ]);
            if (!$r['ok']) return $r;

            $items = (array)($r['datos']['items'] ?? []);
            foreach ($items as $it) {
                $id = (string)preg_replace('/\D+/', '', (string)($it['order_id'] ?? ''));
                if ($id !== '') $ordenes[$id] = $it;
            }
            if (count($items) < self::POR_PAGINA) break;
        }
        return ['ok' => true, 'ordenes' => $ordenes];
    }

    /**
     * Crea las órdenes que todavía no están guardadas: pide el detalle (productos) de cada una
     * y las pasa por el mismo importador del Excel. Una que no trae teléfono válido (no hay a
     * quién avisar) se cuenta como omitida; una cuyo detalle falló o pasa del tope queda
     * pendiente para la siguiente corrida.
     *
     * @param  array<string, array<string,string>> $filas
     * @return array{ok: bool, error?: string, creadas?: int, pendientes?: int}
     */
    private function crearNuevas(DropiMcpCliente $cliente, array $filas, int &$omitidas): array
    {
        if (!$filas) return ['ok' => true, 'creadas' => 0, 'pendientes' => 0];

        // Las más recientes primero: si hay que dejar algunas para la siguiente corrida, que sean las más viejas.
        uasort($filas, fn($a, $b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));

        $import = new DropiImport();
        $armadas = [];
        $pendientes = 0;
        foreach ($filas as $id => $f) {
            if (!$import->telefonoValido((string)($f['customer_phone'] ?? ''))) { $omitidas++; continue; }
            if (count($armadas) >= self::MAX_NUEVAS) { $pendientes++; continue; }

            $d = $cliente->llamar('get_order', ['id' => (string)$id]);
            if (!$d['ok']) {
                // Si ni la primera responde, es la conexión y no esa orden: que se vea el error.
                if (!$armadas && $pendientes === 0 && $this->esFalloDeConexion($d['error'] ?? '')) return $d;
                $pendientes++;
                continue;
            }

            $o = $import->ordenDesdeMcp($f, $d['datos']);
            if ($o === null) { $omitidas++; continue; }
            $armadas[] = $o;
        }

        $creadas = 0;
        if ($armadas) {
            $res = $import->procesar($armadas, true, 'via-mcp', 'cron-mcp', false);
            $creadas = (int)$res['conteo']['nuevo'];
        }
        return ['ok' => true, 'creadas' => $creadas, 'pendientes' => $pendientes];
    }

    private function esFalloDeConexion(string $error): bool
    {
        return strpos($error, 'No se pudo conectar') !== false || strpos($error, 'HTTP 401') !== false;
    }
}
