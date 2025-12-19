<?php

class Pedido extends Model
{
    public function crear($data)
    {
        $sql = "INSERT INTO pedidos
                (nombre, apellidos, telefono, color, departamento, municipio, tipo_entrega, direccion,
                 producto_id, precio_venta, precio_proveedor, utilidad)
                VALUES
                (:nombre, :apellidos, :telefono, :color, :departamento, :municipio, :tipo_entrega, :direccion,
                 :producto_id, :precio_venta, :precio_proveedor, :utilidad)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'           => $data['nombre'],
            ':apellidos'        => $data['apellidos'],
            ':telefono'         => $data['telefono'],
            ':color'            => $data['color'],
            ':departamento'     => $data['departamento'],
            ':municipio'        => $data['municipio'],
            ':tipo_entrega'     => $data['tipo_entrega'],
            ':direccion'        => $data['direccion'],
            ':producto_id'      => $data['producto_id'],
            ':precio_venta'     => $data['precio_venta'],
            ':precio_proveedor' => $data['precio_proveedor'],
            ':utilidad'         => $data['utilidad'],
        ]);
    }

    public function obtenerTodos($limit = 200)
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

    public function obtenerPorId($id)
    {
        $sql = "SELECT p.*, pr.nombre AS producto_nombre
                FROM pedidos p
                INNER JOIN productos pr ON p.producto_id = pr.id
                WHERE p.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarEstado($id, $estado)
    {
        $sql = "UPDATE pedidos SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado' => $estado,
            ':id'     => $id,
        ]);
    }
}
