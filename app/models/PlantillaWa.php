<?php

class PlantillaWa extends Model
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
            CREATE TABLE IF NOT EXISTS plantillas_wa (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                estado     VARCHAR(50)  NOT NULL UNIQUE,
                titulo     VARCHAR(100) NOT NULL DEFAULT '',
                mensaje    TEXT         NOT NULL,
                activo     TINYINT(1)   DEFAULT 1,
                updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $count = (int) $this->db->query("SELECT COUNT(*) FROM plantillas_wa")->fetchColumn();
        if ($count === 0) {
            $this->insertDefaults();
        } else {
            $this->migrateTemplates();
        }
    }

    private function insertDefaults(): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO plantillas_wa (estado, titulo, mensaje)
            VALUES (:estado, :titulo, :mensaje)
        ");
        foreach (self::textos() as $estado => $data) {
            $stmt->execute([':estado' => $estado, ':titulo' => $data[0], ':mensaje' => $data[1]]);
        }
    }

    private static function textos(): array
    {
        return [
            'nuevo' => [
                'Recibimos tu pedido',
                "Hola {nombre} 👋\nRecibimos tu pedido de *{producto}* y ya lo estamos procesando.\n\nPronto te enviamos el número de guía para que puedas hacer seguimiento. 📦\n\n¡Gracias por tu compra! 🙏",
            ],
            'contactado' => [
                'En espera de confirmación',
                "Hola {nombre} 😊\nQuedamos atentos a tu confirmación para continuar con el pedido de *{producto}*.\n\nCualquier duda, aquí estamos. ¡Con gusto te ayudamos! 🙌",
            ],
            'confirmado' => [
                'Pedido confirmado',
                "¡Hola {nombre}! 🎉\nTu pedido de *{producto}* ha sido confirmado y ya estamos trabajando en él.\n\nPronto te estaremos enviando el número de guía. 📦\n\nBendiciones 🙏",
            ],
            'enviado' => [
                'Pedido despachado',
                "¡Buenas noticias {nombre}! 🚀\nTu pedido de *{producto}* ya fue despachado hacia {municipio}.\n\n📦 *Transportadora:* {transportadora}\n📋 *Número de guía:* #{guia}\n🔍 *Seguimiento:* {rastreo}\n\nNuestro compromiso es tu satisfacción total. Bendiciones 🙏\n\n✅ Cuando llegue, ¡nos encantaría ver una foto con tu pedido!",
            ],
            'en_oficina' => [
                'Listo para recoger',
                "¡Hola {nombre}! 📦\nTu pedido de *{producto}* ya llegó a la oficina de *Interrapidísimo* en {municipio}.\n\nPuedes pasar a recogerlo presentando:\n📋 *Número de guía:* #{guia}\n🪪 O tu número de cédula\n\n¡Te esperamos! Bendiciones 🙏",
            ],
            'entregado' => [
                '¿Cómo llegó todo?',
                "Hola {nombre} 😊\nEsperamos que tu *{producto}* haya llegado en perfectas condiciones.\n\n¿Todo llegó bien? Tu opinión es muy importante para nosotros. 🌟\n\n📸 Si tienes un momento, envíanos una foto con tu pedido. ¡La compartimos con mucho gusto!\n\n¡Gracias por confiar en nosotros! 🙏",
            ],
            'cancelado' => [
                'Pedido cancelado',
                "Hola {nombre},\nLamentamos informarte que tu pedido de *{producto}* no pudo ser procesado en esta ocasión.\n\nSi tienes alguna duda o deseas hacer un nuevo pedido, con mucho gusto te atendemos. 😊\n\n¡Esperamos verte pronto! 🙏",
            ],
        ];
    }

    private function migrateTemplates(): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO plantillas_wa (estado, titulo, mensaje)
            VALUES (:estado, :titulo, :mensaje)
            ON DUPLICATE KEY UPDATE
                titulo  = VALUES(titulo),
                mensaje = VALUES(mensaje)
        ");

        foreach (self::textos() as $estado => $data) {
            $stmt->execute([':estado' => $estado, ':titulo' => $data[0], ':mensaje' => $data[1]]);
        }
    }

    public function todas(): array
    {
        $order = "'nuevo','contactado','confirmado','enviado','en_oficina','entregado','cancelado'";
        return $this->db->query(
            "SELECT * FROM plantillas_wa ORDER BY FIELD(estado, {$order})"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function keyedByEstado(): array
    {
        $map = [];
        foreach ($this->todas() as $r) {
            $map[$r['estado']] = $r;
        }
        return $map;
    }

    public function upsert(string $estado, string $titulo, string $mensaje): bool
    {
        $st = $this->db->prepare("
            INSERT INTO plantillas_wa (estado, titulo, mensaje)
            VALUES (:estado, :titulo, :mensaje)
            ON DUPLICATE KEY UPDATE
                titulo  = VALUES(titulo),
                mensaje = VALUES(mensaje)
        ");
        return $st->execute([
            ':estado'  => $estado,
            ':titulo'  => $titulo,
            ':mensaje' => $mensaje,
        ]);
    }
}
