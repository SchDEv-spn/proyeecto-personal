<?php

/**
 * Registro histórico de los clientes que llegaban por WhatsApp directo y se anotaban a mano en el
 * compositor de Seguimiento (ya retirado: hoy todo pedido pasa por Dropi y se importa, ver
 * DropiOrden). La tabla se conserva porque las notificaciones de pedidos recientes todavía leen esos
 * registros (ver FomoPedidos); aquí ya no se escribe nada nuevo.
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
     * Columnas añadidas después del CREATE original. Se consulta cuáles faltan
     * con un solo SHOW COLUMNS y solo se hace ALTER de esas: esta clase se
     * instancia en cada visita a la landing (notificaciones de pedidos
     * recientes) y un ALTER por columna en cada visita saldría caro. El
     * try/catch sigue tragando el 1060 (columna duplicada) por si dos
     * peticiones llegan a la vez.
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

        try {
            $existentes = array_column(
                $this->db->query('SHOW COLUMNS FROM contactos_manuales')->fetchAll(PDO::FETCH_ASSOC),
                'Field'
            );
        } catch (\PDOException $e) {
            $existentes = [];
        }

        foreach ($cols as $col => $def) {
            if (in_array($col, $existentes, true)) continue;
            try {
                $this->db->exec("ALTER TABLE contactos_manuales ADD COLUMN {$col} {$def}");
            } catch (\PDOException $e) {
                // 42S21 / 1060 = Duplicate column name → ya existe, nada que hacer.
            }
        }
    }
}
