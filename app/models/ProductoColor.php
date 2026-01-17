<?php

class ProductoColor extends Model
{
    public function obtenerActivosPorProducto(int $productoId): array
    {
        if ($productoId <= 0) return [];

        $sql = "SELECT color
                FROM producto_colores
                WHERE producto_id = :pid AND activo = 1
                ORDER BY id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productoId]);

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $out = [];
        foreach ($rows as $c) {
            $c = trim((string)$c);
            if ($c !== '') $out[] = $c;
        }

        return array_values(array_unique($out));
    }
}
