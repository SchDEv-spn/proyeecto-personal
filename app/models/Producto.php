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

    /**
     * Primer producto activo. Sirve de destino por defecto cuando se entra a la
     * raíz del sitio sin indicar producto.
     */
    public function obtenerPrimeroActivo(): ?array
    {
        $sql = "SELECT * FROM productos WHERE activo = 1 ORDER BY id ASC LIMIT 1";
        $stmt = $this->db->query($sql);
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
                (nombre, slug, precio_venta, precio_regular, precio_proveedor, costo_envio,
                 descuento_2da, descuento_3ra, descuento_multicantidad_activo,
                 imagen_principal, activo)
                VALUES
                (:nombre, :slug, :precio_venta, :precio_regular, :precio_proveedor, :costo_envio,
                 :descuento_2da, :descuento_3ra, :descuento_multicantidad_activo,
                 :imagen_principal, :activo)";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':nombre'           => $data['nombre'],
            ':slug'             => $data['slug'] ?? null,
            ':precio_venta'     => $data['precio_venta'],
            ':precio_regular'   => $data['precio_regular'] ?? 0,
            ':precio_proveedor' => $data['precio_proveedor'],
            ':costo_envio'      => $data['costo_envio'] ?? 0,

            ':descuento_2da'    => $data['descuento_2da'] ?? 15,
            ':descuento_3ra'    => $data['descuento_3ra'] ?? 20,
            ':descuento_multicantidad_activo' => $data['descuento_multicantidad_activo'] ?? 1,

            ':imagen_principal' => $data['imagen_principal'] ?? null,
            ':activo'           => $data['activo'] ?? 1,
        ]);
    }

    public function crearConId(array $data): int
    {
        $sql = "INSERT INTO productos
                (nombre, slug, precio_venta, precio_regular, precio_proveedor, costo_envio,
                 descuento_2da, descuento_3ra, descuento_multicantidad_activo,
                 imagen_principal, activo)
                VALUES
                (:nombre, :slug, :precio_venta, :precio_regular, :precio_proveedor, :costo_envio,
                 :descuento_2da, :descuento_3ra, :descuento_multicantidad_activo,
                 :imagen_principal, :activo)";
        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':nombre'           => $data['nombre'],
            ':slug'             => $data['slug'] ?? null,
            ':precio_venta'     => $data['precio_venta'],
            ':precio_regular'   => $data['precio_regular'] ?? 0,
            ':precio_proveedor' => $data['precio_proveedor'],
            ':costo_envio'      => $data['costo_envio'] ?? 0,

            ':descuento_2da'    => $data['descuento_2da'] ?? 15,
            ':descuento_3ra'    => $data['descuento_3ra'] ?? 20,
            ':descuento_multicantidad_activo' => $data['descuento_multicantidad_activo'] ?? 1,

            ':imagen_principal' => $data['imagen_principal'] ?? null,
            ':activo'           => $data['activo'] ?? 1,
        ]);

        if (!$ok) return 0;
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE productos
                SET nombre           = :nombre,
                    slug             = :slug,
                    precio_venta     = :precio_venta,
                    precio_regular   = :precio_regular,
                    precio_proveedor = :precio_proveedor,
                    costo_envio      = :costo_envio,
                    descuento_2da    = :descuento_2da,
                    descuento_3ra    = :descuento_3ra,
                    descuento_multicantidad_activo = :descuento_multicantidad_activo,
                    imagen_principal = :imagen_principal,
                    activo           = :activo
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':id'               => $id,
            ':nombre'           => $data['nombre'],
            ':slug'             => $data['slug'] ?? null,
            ':precio_venta'     => $data['precio_venta'],
            ':precio_regular'   => $data['precio_regular'] ?? 0,
            ':precio_proveedor' => $data['precio_proveedor'],
            ':costo_envio'      => $data['costo_envio'] ?? 0,

            ':descuento_2da'    => $data['descuento_2da'] ?? 15,
            ':descuento_3ra'    => $data['descuento_3ra'] ?? 20,
            ':descuento_multicantidad_activo' => $data['descuento_multicantidad_activo'] ?? 1,

            ':imagen_principal' => $data['imagen_principal'] ?? null,
            ':activo'           => $data['activo'] ?? 1,
        ]);
    }

    // =========================
    // COLORES POR PRODUCTO
    // =========================

    public function getColoresByProducto(int $productoId): array
    {
        $sql = "SELECT color
                FROM producto_colores
                WHERE producto_id = :pid AND activo = 1
                ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function syncColoresProducto(int $productoId, array $colores): void
    {
        $this->db->prepare("DELETE FROM producto_colores WHERE producto_id = :pid")
                 ->execute([':pid' => $productoId]);

        if (empty($colores)) return;

        $ins = $this->db->prepare(
            "INSERT INTO producto_colores (producto_id, color, activo)
             VALUES (:pid, :color, 1)"
        );

        foreach ($colores as $c) {
            $c = mb_substr(trim((string)$c), 0, 50);
            if ($c === '') continue;
            $ins->execute([':pid' => $productoId, ':color' => $c]);
        }
    }

    public function colorExisteParaProducto(int $productoId, string $color): bool
    {
        $sql = "SELECT COUNT(*) FROM producto_colores
                WHERE producto_id = :pid AND color = :color AND activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productoId, ':color' => $color]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    // =========================
    // INTEGRACIÓN DROPI
    // =========================

    public function guardarDropiProductId(int $id, ?int $dropiProductId): bool
    {
        $stmt = $this->db->prepare("UPDATE productos SET dropi_product_id = :did WHERE id = :id");
        return $stmt->execute([
            ':did' => $dropiProductId,
            ':id'  => $id,
        ]);
    }

    /**
     * Colores del producto con su variation_id de Dropi (NULL si el producto
     * es SIMPLE en Dropi o ese color todavía no se mapeó).
     */
    public function obtenerColoresConVariacionDropi(int $productoId): array
    {
        $sql = "SELECT color, dropi_variation_id
                FROM producto_colores
                WHERE producto_id = :pid AND activo = 1
                ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $productoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * $variaciones: ['Negro' => 123, 'Azul' => null, ...]. Actualiza por
     * nombre de color en vez de reinsertar, para no chocar con
     * syncColoresProducto (que borra y recrea las filas de color).
     */
    public function guardarVariacionesDropi(int $productoId, array $variaciones): void
    {
        $upd = $this->db->prepare(
            "UPDATE producto_colores SET dropi_variation_id = :vid
             WHERE producto_id = :pid AND color = :color"
        );

        foreach ($variaciones as $color => $variationId) {
            $color = trim((string)$color);
            if ($color === '') continue;

            $vid = ($variationId !== null && $variationId !== '') ? (int)$variationId : null;

            $upd->execute([
                ':vid'   => $vid,
                ':pid'   => $productoId,
                ':color' => $color,
            ]);
        }
    }
}
