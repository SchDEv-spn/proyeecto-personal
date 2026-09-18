<?php

/**
 * Datos de clientes que llegan por WhatsApp directo (vendedoras), no por un
 * pedido de la landing. Un registro por teléfono — se sobrescribe cada vez
 * que se compone y envía un mensaje manual, así la próxima búsqueda por ese
 * mismo número ya trae los datos y el último estado informado sin haber
 * que volver a escribirlos.
 *
 * Si ese teléfono más adelante también hace un pedido real por la landing,
 * este registro no se toca — quedan los dos por separado.
 */
class ContactoManual extends Model
{
    private static bool $tableReady = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
        $this->asegurarColumnasNuevas();
    }

    private function ensureTable(): void
    {
        if (self::$tableReady) return;
        self::$tableReady = true;

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS contactos_manuales (
                id           INT AUTO_INCREMENT PRIMARY KEY,
                telefono     VARCHAR(30)  NOT NULL UNIQUE,
                nombre       VARCHAR(150) NOT NULL DEFAULT '',
                apellidos    VARCHAR(150) NOT NULL DEFAULT '',
                producto     VARCHAR(150) NOT NULL DEFAULT '',
                cantidad     VARCHAR(20)  NOT NULL DEFAULT '1',
                precio       VARCHAR(30)  NOT NULL DEFAULT '',
                municipio    VARCHAR(100) NOT NULL DEFAULT '',
                departamento VARCHAR(100) NOT NULL DEFAULT '',
                tipo_entrega VARCHAR(20)  NOT NULL DEFAULT 'domicilio',
                estado       VARCHAR(50)  NOT NULL DEFAULT '',
                usuario_nombre VARCHAR(150) NOT NULL DEFAULT '',
                updated_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Columnas añadidas después del CREATE original — mismo patrón que
     * Pedido::asegurarColumnasNuevas(). El ALTER en try/catch traga el 1060
     * (columna duplicada) si ya existe.
     */
    private static bool $columnasListas = false;
    private function asegurarColumnasNuevas(): void
    {
        if (self::$columnasListas) return;
        self::$columnasListas = true;

        $cols = [
            'numero_guia'           => "VARCHAR(50) NOT NULL DEFAULT ''",
            'notificado_oficina_at' => 'TIMESTAMP NULL',
        ];
        foreach ($cols as $col => $def) {
            try {
                $this->db->exec("ALTER TABLE contactos_manuales ADD COLUMN {$col} {$def}");
            } catch (\PDOException $e) {
                // 42S21 / 1060 = Duplicate column name → ya existe, nada que hacer.
            }
        }
    }

    public function upsert(string $telefono, array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO contactos_manuales
                (telefono, nombre, apellidos, producto, cantidad, precio, municipio, departamento, tipo_entrega, estado, usuario_nombre)
            VALUES
                (:telefono, :nombre, :apellidos, :producto, :cantidad, :precio, :municipio, :departamento, :tipo_entrega, :estado, :usuario_nombre)
            ON DUPLICATE KEY UPDATE
                nombre         = VALUES(nombre),
                apellidos      = VALUES(apellidos),
                producto       = VALUES(producto),
                cantidad       = VALUES(cantidad),
                precio         = VALUES(precio),
                municipio      = VALUES(municipio),
                departamento   = VALUES(departamento),
                tipo_entrega   = VALUES(tipo_entrega),
                estado         = VALUES(estado),
                usuario_nombre = VALUES(usuario_nombre)
        ");

        return $stmt->execute([
            ':telefono'       => $telefono,
            ':nombre'         => $data['nombre']         ?? '',
            ':apellidos'      => $data['apellidos']      ?? '',
            ':producto'       => $data['producto']       ?? '',
            ':cantidad'       => $data['cantidad']        ?? '1',
            ':precio'         => $data['precio']         ?? '',
            ':municipio'      => $data['municipio']      ?? '',
            ':departamento'   => $data['departamento']   ?? '',
            ':tipo_entrega'   => $data['tipoEntrega']    ?? 'domicilio',
            ':estado'         => $data['estado']         ?? '',
            ':usuario_nombre' => $data['usuarioNombre']  ?? '',
        ]);
    }

    /**
     * Mismo criterio que Pedido::registrarEnvioWa — guarda la guía si se
     * escribió una y, si el estado enviado es 'en_oficina', marca/refresca
     * el momento en que se le avisó al cliente.
     */
    public function registrarEnvioWa(string $telefono, string $estado, ?string $guia): bool
    {
        $sets   = [];
        $params = [':telefono' => $telefono];

        if ($guia !== null && $guia !== '') {
            $sets[] = 'numero_guia = :guia';
            $params[':guia'] = $guia;
        }
        if ($estado === 'en_oficina') {
            $sets[] = 'notificado_oficina_at = NOW()';
        }

        if (empty($sets)) return true;

        $sql = 'UPDATE contactos_manuales SET ' . implode(', ', $sets) . ' WHERE telefono = :telefono';
        return $this->db->prepare($sql)->execute($params);
    }

    /**
     * Contactos manuales en oficina a los que ya se les avisó, con los días
     * corridos desde ese aviso — mismo criterio que
     * Pedido::obtenerEnOficinaEsperando().
     */
    public function obtenerEnOficinaEsperando(): array
    {
        $sql = "SELECT *, DATEDIFF(NOW(), notificado_oficina_at) AS dias_esperando
                FROM contactos_manuales
                WHERE estado = 'en_oficina'
                  AND notificado_oficina_at IS NOT NULL
                ORDER BY notificado_oficina_at ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mismo criterio que Pedido::buscarPorTelefono — últimos 8 dígitos, para
     * no fallar por el indicativo 57 o un 0 inicial.
     */
    public function buscarPorTelefono(string $telefonoInput): ?array
    {
        $digits = preg_replace('/\D+/', '', $telefonoInput);
        if (strlen($digits) < 7) return null;

        $sufijo = substr($digits, -8);
        $telefonoLimpioSql = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono,' ',''),'-',''),'+',''),'(',''),')','')";

        $stmt = $this->db->prepare("
            SELECT * FROM contactos_manuales
            WHERE {$telefonoLimpioSql} LIKE :sufijo
            ORDER BY updated_at DESC
            LIMIT 1
        ");
        $stmt->execute([':sufijo' => '%' . $sufijo]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
