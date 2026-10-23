<?php

class ProductoColor extends Model
{
    /**
     * Ejecuta $fn y, si falla porque producto_colores todavía no tiene la
     * columna `foto` (42S22), la crea y lo reintenta. Así un deploy no revienta
     * aunque nadie haya corrido la migración en producción, y las visitas
     * normales no pagan nada: la consulta solo se repite la primera vez. Es el
     * mismo criterio que LandingAnalytics (DDL ante el error, no por adelantado).
     *
     * @template T
     * @param callable():T $fn
     * @return T
     */
    public static function conColumnaFoto(PDO $db, callable $fn)
    {
        try {
            return $fn();
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S22') throw $e;
            try {
                $db->exec('ALTER TABLE producto_colores ADD COLUMN foto VARCHAR(255) NULL');
            } catch (PDOException $e2) {
                // 1060: otra petición la creó justo antes. Nada que hacer.
            }
            return $fn();
        }
    }

    /**
     * Foto de cada color del producto, subida al crearlo o editarlo:
     * ['Negro' => '/…/col_123.jpg', …]. Solo los colores que tienen foto.
     */
    public function obtenerFotosPorProducto(int $productoId): array
    {
        if ($productoId <= 0) return [];

        $filas = self::conColumnaFoto($this->db, function () use ($productoId) {
            $stmt = $this->db->prepare(
                "SELECT color, foto
                 FROM producto_colores
                 WHERE producto_id = :pid AND activo = 1
                 ORDER BY id ASC"
            );
            $stmt->execute([':pid' => $productoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });

        $out = [];
        foreach ($filas as $f) {
            $color = trim((string)$f['color']);
            $foto  = trim((string)($f['foto'] ?? ''));
            if ($color !== '' && $foto !== '' && !isset($out[$color])) $out[$color] = $foto;
        }
        return $out;
    }

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
