<?php

/**
 * Registro de mensajes de WhatsApp armados a mano desde el compositor de
 * AdminPlantillasWa, para números que no corresponden a un pedido de la
 * landing (p. ej. clientes que cierran por WhatsApp directo con las
 * vendedoras). Los envíos ligados a un pedido de la landing no se registran
 * aquí — ese flujo ya existe desde el botón WhatsApp de cada pedido.
 */
class WaMensajeLog extends Model
{
    private static bool $tableReady = false;

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        if (self::$tableReady) return;
        self::$tableReady = true;

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS wa_mensajes_log (
                id               INT AUTO_INCREMENT PRIMARY KEY,
                telefono         VARCHAR(30)  NOT NULL,
                nombre           VARCHAR(150) NOT NULL DEFAULT '',
                estado_usado     VARCHAR(50)  NOT NULL DEFAULT '',
                usuario_nombre   VARCHAR(150) NOT NULL DEFAULT '',
                created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function registrar(string $telefono, string $nombre, string $estadoUsado, string $usuarioNombre): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO wa_mensajes_log (telefono, nombre, estado_usado, usuario_nombre)
            VALUES (:telefono, :nombre, :estado_usado, :usuario_nombre)
        ");
        return $stmt->execute([
            ':telefono'       => $telefono,
            ':nombre'         => $nombre,
            ':estado_usado'   => $estadoUsado,
            ':usuario_nombre' => $usuarioNombre,
        ]);
    }
}
