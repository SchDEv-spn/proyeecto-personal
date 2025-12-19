<?php

class Producto extends Model
{
    public function obtenerTodos(): array
    {
        $sql = "SELECT * FROM productos ORDER BY id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM productos WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerPorSlug(string $slug): ?array
    {
        $sql = "SELECT * FROM productos WHERE slug = :slug LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function crear(array $data): bool
    {
        $sql = "INSERT INTO productos
                (nombre, slug, precio_venta, precio_proveedor, imagen_principal, activo)
                VALUES
                (:nombre, :slug, :precio_venta, :precio_proveedor, :imagen_principal, :activo)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'           => $data['nombre'],
            ':slug'             => $data['slug'] ?? null,
            ':precio_venta'     => $data['precio_venta'],
            ':precio_proveedor' => $data['precio_proveedor'],
            ':imagen_principal' => $data['imagen_principal'] ?? null,
            ':activo'           => $data['activo'] ?? 1,
        ]);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE productos
                SET nombre           = :nombre,
                    slug             = :slug,
                    precio_venta     = :precio_venta,
                    precio_proveedor = :precio_proveedor,
                    imagen_principal = :imagen_principal,
                    activo           = :activo
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id'               => $id,
            ':nombre'           => $data['nombre'],
            ':slug'             => $data['slug'] ?? null,
            ':precio_venta'     => $data['precio_venta'],
            ':precio_proveedor' => $data['precio_proveedor'],
            ':imagen_principal' => $data['imagen_principal'] ?? null,
            ':activo'           => $data['activo'] ?? 1,
        ]);
    }
}
