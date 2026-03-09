<?php

class Pedido extends Model
{
    /**
     * (Fallback) Regla antigua si el controller no manda precio_total.
     * Idealmente el controller manda precio_total/descuento_total/utilidad_total.
     */
    private function totalConDescuento(int $cantidad, float $precioUnit): float
    {
        if ($cantidad <= 0) return 0.0;
        if ($cantidad === 1) return $precioUnit;

        $total = 0.0;
        $total += $precioUnit;        // 1ra
        $total += $precioUnit * 0.85; // 2da -15%
        if ($cantidad >= 3) {
            $total += $precioUnit * 0.80 * ($cantidad - 2); // 3ra+ -20%
        }
        return $total;
    }

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

        // Totales fallback (si el controller no los manda)
        $subtotal       = $precioVenta * $cantidadTotal;
        $precioTotal    = $this->totalConDescuento($cantidadTotal, $precioVenta);
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

        $sql = "INSERT INTO pedidos
            (nombre, apellidos, telefono, color, cantidad_total, departamento, municipio, tipo_entrega, direccion,
             producto_id, precio_venta, precio_proveedor, utilidad, descuento_total, precio_total, utilidad_total, estado)
            VALUES
            (:nombre, :apellidos, :telefono, :color, :cantidad_total, :departamento, :municipio, :tipo_entrega, :direccion,
             :producto_id, :precio_venta, :precio_proveedor, :utilidad, :descuento_total, :precio_total, :utilidad_total, :estado)";

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
            ':producto_id'      => $productoId,
            ':precio_venta'     => $precioVenta,
            ':precio_proveedor' => $precioProveedor,
            ':utilidad'         => $utilidadUnit,
            ':descuento_total'  => $descuentoTotal,
            ':precio_total'     => $precioTotal,
            ':utilidad_total'   => $utilidadTotal,
            ':estado'           => $estado,
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
}
