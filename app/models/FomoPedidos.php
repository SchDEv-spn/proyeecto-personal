<?php

/**
 * Pedidos REALES recientes de un producto, para las notificaciones "Fulana de
 * Cali pidió hace 25 min" de la landing. Nada se inventa: si no hay pedidos
 * recientes, devuelve [] y la landing no muestra nada.
 *
 * Tres fuentes, las tres reales:
 *  - La landing: la tabla `pedidos` (hora = created_at).
 *  - WhatsApp: los pedidos que se registran a mano en Seguimiento. Cada mensaje
 *    de "pedido recibido" (estado nuevo) o "pedido confirmado" que se envía queda
 *    en wa_mensajes_log con su hora; los datos del cliente (producto, municipio)
 *    están en contactos_manuales, que guarda un registro por teléfono y se
 *    sobrescribe con cada seguimiento — por eso la hora sale del log y no de ahí.
 *  - Dropi: las órdenes importadas desde su exportación a Excel (DropiImport).
 *    Traen su propia hora de pedido en contactos_manuales.dropi_fecha.
 *
 * Solo se expone el nombre de pila y el municipio: nunca apellidos ni teléfono.
 */
class FomoPedidos extends Model
{
    /**
     * @return array<int, array{n: string, c: string, s: int}>  n = nombre de pila,
     *         c = municipio, s = segundos desde el pedido; el más reciente primero.
     */
    public function recientes(int $productoId, string $productoNombre, int $horas = 48, int $max = 6): array
    {
        $horas = max(1, min(168, $horas));
        $max   = max(1, min(12, $max));
        $filas = [];

        // 1) Pedidos de la landing
        try {
            $stmt = $this->db->prepare(
                "SELECT nombre, municipio, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS seg
                 FROM pedidos
                 WHERE producto_id = :pid
                   AND estado <> 'cancelado'
                   AND created_at >= NOW() - INTERVAL {$horas} HOUR
                 ORDER BY created_at DESC
                 LIMIT {$max}"
            );
            $stmt->execute([':pid' => $productoId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) $filas[] = $f;
        } catch (\Throwable $e) {
            error_log('[FomoPedidos] pedidos: ' . $e->getMessage());
        }

        // 2) Pedidos de WhatsApp registrados en Seguimiento
        $productoNombre = trim($productoNombre);
        if ($productoNombre !== '') {
            try {
                // Instanciarlos crea sus tablas si todavía no existen (instalación nueva).
                new ContactoManual();
                new WaMensajeLog();

                $stmt = $this->db->prepare(
                    "SELECT COALESCE(NULLIF(TRIM(c.nombre), ''), l.nombre) AS nombre,
                            c.municipio AS municipio,
                            TIMESTAMPDIFF(SECOND, MIN(l.created_at), NOW()) AS seg
                     FROM wa_mensajes_log l
                     JOIN contactos_manuales c ON c.telefono = l.telefono
                     WHERE l.estado_usado IN ('nuevo', 'confirmado')
                       AND l.created_at >= NOW() - INTERVAL {$horas} HOUR
                       AND c.estado <> 'cancelado'
                       AND TRIM(c.producto) = :producto
                     GROUP BY l.telefono, c.nombre, l.nombre, c.municipio
                     ORDER BY MIN(l.created_at) DESC
                     LIMIT {$max}"
                );
                $stmt->execute([':producto' => $productoNombre]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) $filas[] = $f;
            } catch (\Throwable $e) {
                error_log('[FomoPedidos] whatsapp: ' . $e->getMessage());
            }

            // 3) Órdenes traídas de la exportación de Dropi: llevan la hora real del
            //    pedido (dropi_fecha), así que no dependen de que se haya enviado un
            //    mensaje. Si el cliente también está en el log, el filtro de
            //    repetidos de abajo (mismo nombre y municipio) deja una sola.
            try {
                $stmt = $this->db->prepare(
                    "SELECT nombre, municipio, TIMESTAMPDIFF(SECOND, dropi_fecha, NOW()) AS seg
                     FROM contactos_manuales
                     WHERE dropi_fecha IS NOT NULL
                       AND dropi_fecha >= NOW() - INTERVAL {$horas} HOUR
                       AND dropi_fecha <= NOW()
                       AND estado <> 'cancelado'
                       AND TRIM(producto) = :producto
                     ORDER BY dropi_fecha DESC
                     LIMIT {$max}"
                );
                $stmt->execute([':producto' => $productoNombre]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) $filas[] = $f;
            } catch (\Throwable $e) {
                error_log('[FomoPedidos] dropi: ' . $e->getMessage());
            }
        }

        usort($filas, fn($a, $b) => (int)$a['seg'] <=> (int)$b['seg']);

        $out   = [];
        $vistos = [];
        foreach ($filas as $f) {
            $nombre = $this->nombrePila((string)($f['nombre'] ?? ''));
            if ($nombre === '') continue;
            $ciudad = mb_substr(trim((string)($f['municipio'] ?? '')), 0, 40);

            $clave = mb_strtolower($nombre . '|' . $ciudad);
            if (isset($vistos[$clave])) continue;
            $vistos[$clave] = true;

            $out[] = ['n' => $nombre, 'c' => $ciudad, 's' => max(0, (int)$f['seg'])];
            if (count($out) >= $max) break;
        }
        return $out;
    }

    /** Primera palabra del nombre, con la inicial en mayúscula ("maria jose" → "Maria"). */
    private function nombrePila(string $nombre): string
    {
        $partes = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $primera = $partes[0] ?? '';
        if ($primera === '' || !preg_match('/\p{L}/u', $primera)) return '';
        return mb_substr(mb_convert_case(mb_strtolower($primera, 'UTF-8'), MB_CASE_TITLE, 'UTF-8'), 0, 20);
    }
}
