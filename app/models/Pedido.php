<?php

class Pedido extends Model
{
    public function crearConId(array $data): int
    {
        $nombre       = trim((string)($data['nombre'] ?? ''));
        $apellidos    = trim((string)($data['apellidos'] ?? ''));
        $telefono     = trim((string)($data['telefono'] ?? ''));
        $color        = trim((string)($data['color'] ?? ''));
        $departamento = trim((string)($data['departamento'] ?? ''));
        $municipio    = trim((string)($data['municipio'] ?? ''));
        $tipoEntrega  = trim((string)($data['tipo_entrega'] ?? ''));
        $direccion    = $data['direccion'] ?? null;

        $productoId      = (int)($data['producto_id'] ?? 0);
        $precioVenta     = (float)($data['precio_venta'] ?? 0);
        $precioProveedor = (float)($data['precio_proveedor'] ?? 0);

        $cantidadTotal = (int)($data['cantidad_total'] ?? 1);
        if ($cantidadTotal < 1) $cantidadTotal = 1;
        if ($cantidadTotal > 20) $cantidadTotal = 20;

        $utilidadUnit = isset($data['utilidad'])
            ? (float)$data['utilidad']
            : ($precioVenta - $precioProveedor);

        // Totales fallback (si el controller no los manda). Usa los descuentos
        // reales del producto, no valores fijos.
        $subtotal       = $precioVenta * $cantidadTotal;
        $precioTotal    = total_con_descuento(
            $cantidadTotal,
            $precioVenta,
            (int)($data['descuento_2da'] ?? 15),
            (int)($data['descuento_3ra'] ?? 20),
            (int)($data['descuento_multicantidad_activo'] ?? 1)
        );
        $descuentoTotal = max(0, $subtotal - $precioTotal);

        // Nota: aquí NO incluimos envío porque pedidos no tiene costo_envio.
        // El controller sí lo calcula y lo manda en utilidad_total (recomendado).
        $utilidadTotal = $precioTotal - ($precioProveedor * $cantidadTotal);

        // Si vienen desde controller, respetarlos
        if (isset($data['precio_total']))    $precioTotal = (float)$data['precio_total'];
        if (isset($data['descuento_total'])) $descuentoTotal = (float)$data['descuento_total'];
        if (isset($data['utilidad_total']))  $utilidadTotal = (float)$data['utilidad_total'];

        $estado = trim((string)($data['estado'] ?? 'nuevo'));
        if ($estado === '') $estado = 'nuevo';

        if ($tipoEntrega !== 'domicilio') {
            $direccion = null;
        } else {
            $direccion = trim((string)$direccion);
            if ($direccion === '') $direccion = null;
        }

        if ($color !== '') {
            $color = mb_substr($color, 0, 50);
        }

        $notaEntrega = isset($data['nota_entrega']) ? trim((string)$data['nota_entrega']) : null;
        if ($notaEntrega === '') $notaEntrega = null;

        // Atribución de Facebook — sin esto un pedido no se puede unir a un
        // anuncio ni reenviar a la Conversions API (ver AUDITORIA.md C3).
        $fbclid = trim((string)($data['fbclid'] ?? ''));
        $fbp    = trim((string)($data['fbp']    ?? ''));
        $fbc    = trim((string)($data['fbc']    ?? ''));

        $sql = "INSERT INTO pedidos
            (nombre, apellidos, telefono, color, cantidad_total, departamento, municipio, tipo_entrega, direccion, nota_entrega,
             producto_id, precio_venta, precio_proveedor, utilidad, descuento_total, precio_total, utilidad_total, estado,
             fbclid, fbp, fbc)
            VALUES
            (:nombre, :apellidos, :telefono, :color, :cantidad_total, :departamento, :municipio, :tipo_entrega, :direccion, :nota_entrega,
             :producto_id, :precio_venta, :precio_proveedor, :utilidad, :descuento_total, :precio_total, :utilidad_total, :estado,
             :fbclid, :fbp, :fbc)";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':nombre'           => $nombre,
            ':apellidos'        => $apellidos,
            ':telefono'         => $telefono,
            ':color'            => $color,
            ':cantidad_total'   => $cantidadTotal,
            ':departamento'     => $departamento,
            ':municipio'        => $municipio,
            ':tipo_entrega'     => $tipoEntrega,
            ':direccion'        => $direccion,
            ':nota_entrega'     => $notaEntrega,
            ':producto_id'      => $productoId,
            ':precio_venta'     => $precioVenta,
            ':precio_proveedor' => $precioProveedor,
            ':utilidad'         => $utilidadUnit,
            ':descuento_total'  => $descuentoTotal,
            ':precio_total'     => $precioTotal,
            ':utilidad_total'   => $utilidadTotal,
            ':estado'           => $estado,
            ':fbclid'           => $fbclid !== '' ? $fbclid : null,
            ':fbp'              => $fbp    !== '' ? $fbp    : null,
            ':fbc'              => $fbc    !== '' ? $fbc    : null,
        ]);

        if (!$ok) return 0;
        return (int)$this->db->lastInsertId();
    }

    public function crear($data): bool
    {
        return $this->crearConId((array)$data) > 0;
    }

    public function obtenerTodos($limit = 200): array
    {
        $sql = "SELECT
                    p.*,
                    pr.nombre AS producto_nombre,
                    pr.costo_envio AS producto_costo_envio
                FROM pedidos p
                INNER JOIN productos pr ON p.producto_id = pr.id
                ORDER BY p.created_at DESC
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorRango(string $inicio, string $fin, int $limit = 300): array
    {
        $sql = "SELECT
                p.*,
                pr.nombre AS producto_nombre,
                pr.costo_envio AS producto_costo_envio
            FROM pedidos p
            INNER JOIN productos pr ON p.producto_id = pr.id
            WHERE p.created_at >= :inicio
              AND p.created_at <  :fin
            ORDER BY p.created_at DESC
            LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':inicio', $inicio, PDO::PARAM_STR);
        $stmt->bindValue(':fin',    $fin,    PDO::PARAM_STR);
        $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function obtenerPorId($id): ?array
    {
        $sql = "SELECT
                    p.*,
                    pr.nombre AS producto_nombre,
                    pr.costo_envio AS producto_costo_envio
                FROM pedidos p
                INNER JOIN productos pr ON p.producto_id = pr.id
                WHERE p.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => (int)$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function actualizarEstado($id, $estado): bool
    {
        $sql = "UPDATE pedidos SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado' => (string)$estado,
            ':id'     => (int)$id,
        ]);
    }

    public function actualizarTelefono(int $id, string $telefono): bool
    {
        $sql = "UPDATE pedidos SET telefono = :telefono WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':telefono' => $telefono,
            ':id'       => $id,
        ]);
    }

    public function syncColoresPedido(int $pedidoId, array $items): void
    {
        if ($pedidoId <= 0) return;

        $this->db->prepare("DELETE FROM pedido_colores WHERE pedido_id = :pid")
            ->execute([':pid' => $pedidoId]);

        $ins = $this->db->prepare(
            "INSERT INTO pedido_colores (pedido_id, color, cantidad)
             VALUES (:pid, :color, :cantidad)"
        );

        foreach ($items as $color => $cantidad) {
            $color = trim((string)$color);
            $cantidad = (int)$cantidad;

            if ($color === '' || $cantidad <= 0) continue;

            $ins->execute([
                ':pid'      => $pedidoId,
                ':color'    => mb_substr($color, 0, 50),
                ':cantidad' => $cantidad,
            ]);
        }
    }
    /**
     * Verifica si ya existe un pedido para el mismo teléfono + producto
     * dentro de los últimos $minutos minutos.
     * Úsalo en el controlador antes de guardar para evitar duplicados.
     */
    public function contarNuevos(): int {
        return (int)$this->db
            ->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'nuevo'")
            ->fetchColumn();
    }

    public function existePedidoReciente(string $telefono, int $productoId, int $minutos = 15): bool
    {
        $sql = "SELECT COUNT(*) FROM pedidos
            WHERE telefono    = :telefono
              AND producto_id = :producto_id
              AND created_at  >= NOW() - INTERVAL :minutos MINUTE";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':telefono'    => trim($telefono),
            ':producto_id' => $productoId,
            ':minutos'     => $minutos,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Reclama el derecho a sincronizar este pedido con Dropi. Es un UPDATE
     * condicional (no un SELECT + IF en PHP) a propósito: dos peticiones
     * casi simultáneas (doble clic, reintento de red) sí pueden llegar
     * aquí a la vez, y solo una debe ganar la carrera.
     */
    public function reclamarSincronizacionDropi(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE pedidos SET dropi_syncing = 1
             WHERE id = :id AND dropi_order_id IS NULL AND dropi_syncing = 0"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function liberarSincronizacionDropi(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE pedidos SET dropi_syncing = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function guardarDropiResultado(int $id, ?int $dropiOrderId, ?string $error): bool
    {
        $sql = "UPDATE pedidos SET dropi_order_id = :oid, dropi_sync_error = :err WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':oid' => $dropiOrderId,
            ':err' => $error !== null ? mb_substr($error, 0, 255) : null,
            ':id'  => $id,
        ]);
    }

    public function contarPedidosRecientes(int $productoId, int $dias = 30): int
    {
        $sql = "SELECT COUNT(*) FROM pedidos
                WHERE producto_id = :producto_id
                  AND created_at >= NOW() - INTERVAL :dias DAY";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':producto_id' => $productoId,
            ':dias'        => $dias,
        ]);

        return (int)$stmt->fetchColumn();
    }
}
