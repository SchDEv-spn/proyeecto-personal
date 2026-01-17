<?php

class Pedido extends Model
{
    /**
     * Regla de descuento:
     * - 1 unidad: sin descuento
     * - 2da unidad: 15% OFF
     * - 3ra en adelante: 20% OFF
     */
    private function totalConDescuento(int $cantidad, float $precioUnit): float
    {
        if ($cantidad <= 0) return 0.0;
        if ($cantidad === 1) return $precioUnit;

        $total = 0.0;

        // 1ra sin descuento
        $total += $precioUnit;

        // 2da -15%
        $total += $precioUnit * 0.85;

        // 3ra+ -20%
        if ($cantidad >= 3) {
            $total += $precioUnit * 0.80 * ($cantidad - 2);
        }

        return $total;
    }

    /**
     * Crea pedido y retorna el ID insertado.
     * Útil para luego guardar detalle en pedido_colores.
     */
    public function crearConId(array $data): int
    {
        // Base
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

        // Cantidad total
        $cantidadTotal = (int)($data['cantidad_total'] ?? 1);
        if ($cantidadTotal < 1) $cantidadTotal = 1;
        if ($cantidadTotal > 20) $cantidadTotal = 20; // límite sano

        // Utilidad unitaria (mantienes compatibilidad con tu lógica anterior)
        $utilidadUnit = isset($data['utilidad'])
            ? (float)$data['utilidad']
            : ($precioVenta - $precioProveedor);

        // Totales (si el controller no los manda, los calculamos aquí)
        $subtotal       = $precioVenta * $cantidadTotal;
        $precioTotal    = $this->totalConDescuento($cantidadTotal, $precioVenta);
        $descuentoTotal = max(0, $subtotal - $precioTotal);

        // Utilidad total real con descuento aplicado
        $utilidadTotal = $precioTotal - ($precioProveedor * $cantidadTotal);

        // Si vienen desde controller, respetarlos
        if (isset($data['precio_total']))    $precioTotal = (float)$data['precio_total'];
        if (isset($data['descuento_total'])) $descuentoTotal = (float)$data['descuento_total'];
        if (isset($data['utilidad_total']))  $utilidadTotal = (float)$data['utilidad_total'];

        // Estado
        $estado = trim((string)($data['estado'] ?? 'nuevo'));
        if ($estado === '') $estado = 'nuevo';

        // Normaliza dirección
        if ($tipoEntrega !== 'domicilio') {
            $direccion = null;
        } else {
            $direccion = trim((string)$direccion);
            if ($direccion === '') $direccion = null;
        }

        // Protege longitud del campo "color" (si tu DB es VARCHAR(50))
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

    /**
     * Compatibilidad: método antiguo que devuelve boolean.
     */
    public function crear($data): bool
    {
        return $this->crearConId((array)$data) > 0;
    }

    public function obtenerTodos($limit = 200): array
    {
        $sql = "SELECT p.*, pr.nombre AS producto_nombre
                FROM pedidos p
                INNER JOIN productos pr ON p.producto_id = pr.id
                ORDER BY p.created_at DESC
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id): ?array
    {
        $sql = "SELECT p.*, pr.nombre AS producto_nombre
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

    /**
     * Guarda detalle de colores/cantidades en pedido_colores.
     * items: ['Negro' => 2, 'Azul' => 1]
     */
    public function syncColoresPedido(int $pedidoId, array $items): void
    {
        if ($pedidoId <= 0) return;

        // Borra detalle anterior
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
