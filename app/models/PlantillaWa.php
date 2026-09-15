<?php

class PlantillaWa extends Model
{
    private static bool $tableReady = false;

    /**
     * Se sube cada vez que cambia el texto base de self::textos(). En
     * instalaciones donde la tabla ya está sembrada, refrescarSiDesactualizado()
     * reescribe las plantillas una sola vez por versión.
     *   2026-09-01: emojis reducidos a los que WhatsApp sí renderiza (📦 ✅ 🙏 😊).
     *   2026-09-14: copy orientado a reducir devoluciones — recuerda envío
     *   gratis y pago contraentrega, y agrega el monto exacto a pagar en los
     *   estados donde el cliente ya va a recibir/pagar (confirmado, enviado,
     *   en_oficina) para que no le tome por sorpresa al mensajero.
     *   2026-09-14-2: agrega la garantía de 1 año (nuevo, entregado) y una
     *   plantilla extra "recordatorio_oficina" — no es un estado del pipeline
     *   de pedidos, solo un mensaje adicional seleccionable en el picker de
     *   WhatsApp para avisar antes de que Interrapidísimo devuelva
     *   automáticamente un pedido no reclamado a los 5 días hábiles.
     *   2026-09-14-3: corrige nuevo/confirmado/enviado, que prometían "el
     *   mensajero" incluso para pedidos de oficina (nadie entrega en la
     *   puerta ahí). Usan {momento_pago}/{receptor_pago}, que se resuelven
     *   según tipo_entrega igual que {transportadora}/{rastreo}.
     *   2026-09-15: en_oficina ya avisa desde el primer mensaje que pase
     *   pronto para evitar la devolución automática — sin dar el plazo
     *   exacto todavía, eso queda para recordatorio_oficina si no pasa.
     */
    private const TEMPLATES_VERSION = '2026-09-15';

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
            $this->marcarVersion();
            return;
        }

        $this->migrateTemplates();          // agrega estados nuevos que falten
        $this->refrescarSiDesactualizado(); // propaga cambios de copy una vez por versión
    }

    /**
     * Reescribe las plantillas con el texto de self::textos(), una única vez
     * por TEMPLATES_VERSION. Sirve para propagar cambios de copy (p. ej. quitar
     * emojis que WhatsApp no renderiza) a instalaciones ya sembradas.
     *
     * Sí sobrescribe plantillas editadas a mano: es intencional. Sólo corre si
     * la versión se puede persistir en app_settings; si esa tabla no existe, no
     * toca nada (evita reescribir en cada request).
     */
    private function refrescarSiDesactualizado(): void
    {
        $settings = new AppSettings();
        if ($settings->get('plantillas_wa_version') === self::TEMPLATES_VERSION) return;

        $settings->set('plantillas_wa_version', self::TEMPLATES_VERSION);
        if ($settings->get('plantillas_wa_version') !== self::TEMPLATES_VERSION) return;

        $stmt = $this->db->prepare(
            "UPDATE plantillas_wa SET titulo = :titulo, mensaje = :mensaje WHERE estado = :estado"
        );
        foreach (self::textos() as $estado => $data) {
            $stmt->execute([':estado' => $estado, ':titulo' => $data[0], ':mensaje' => $data[1]]);
        }
    }

    private function marcarVersion(): void
    {
        (new AppSettings())->set('plantillas_wa_version', self::TEMPLATES_VERSION);
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
        // Sólo se usan 4 emojis, todos de Emoji 1.0 (2015) y con render garantizado
        // en cualquier versión de WhatsApp: 📦 ✅ 🙏 😊
        //
        // El copy recuerda en cada etapa que el envío es gratis y que se paga
        // contraentrega (los dos argumentos que ya usa la landing para generar
        // confianza), y muestra el monto exacto a pagar en los estados donde
        // el cliente está por recibir el pedido — para que nadie se sorprenda
        // con el cobro y rechace el paquete.
        return [
            'nuevo' => [
                'Recibimos tu pedido',
                "Hola {nombre} 😊\nRecibimos tu pedido de *{producto}* y ya lo estamos procesando.\n\nRecuerda: el envío es *gratis* 📦, pagas *contraentrega* — solo pagas cuando {momento_pago}, sin adelantos — y tienes *garantía de 1 año* por defectos de fabricación.\n\nPronto te enviamos el número de guía. ¡Gracias por tu compra! 🙏",
            ],
            'contactado' => [
                'En espera de confirmación',
                "Hola {nombre} 😊\nQuedamos atentos a tu confirmación para continuar con el pedido de *{producto}*.\n\nNo arriesgas nada: envío *gratis* y pago *contraentrega*, solo pagas cuando lo tengas en tus manos.\n\n¿Confirmamos y lo despachamos hoy mismo?",
            ],
            'confirmado' => [
                'Pedido confirmado',
                "¡Hola {nombre}! ✅\nTu pedido de *{producto}* ha sido confirmado y ya estamos trabajando en él.\n\nTotal a pagar {receptor_pago}: *{precio}* (envío gratis incluido, sin cobros adicionales).\n\nPronto te estaremos enviando el número de guía. 📦\n\nBendiciones 🙏",
            ],
            'enviado' => [
                'Pedido despachado',
                "¡Buenas noticias {nombre}! 📦\nTu pedido de *{producto}* ya fue despachado hacia {municipio}.\n\n*Transportadora:* {transportadora}\n*Número de guía:* #{guia}\n*Seguimiento:* {rastreo}\n\nRecuerda que pagas *contraentrega*: ten listo *{precio}* en efectivo cuando {momento_pago}, así evitamos demoras.\n\nBendiciones 🙏\n\n✅ Cuando llegue, ¡nos encantaría ver una foto con tu pedido!",
            ],
            'en_oficina' => [
                'Listo para recoger',
                "¡Hola {nombre}! 📦\nTu pedido de *{producto}* ya llegó a la oficina de *Interrapidísimo* en {municipio}.\n\nPuedes pasar a recogerlo presentando:\n*Número de guía:* #{guia}\nO tu número de cédula\n\nTe recomendamos pasar cuanto antes para evitar que se genere una devolución automática.\n\nRecuerda que pagas *{precio}* contraentrega directo en la oficina, sin cobros adicionales.\n\n¡Te esperamos! Bendiciones 🙏",
            ],
            'entregado' => [
                '¿Cómo llegó todo?',
                "Hola {nombre} 😊\nEsperamos que tu *{producto}* haya llegado en perfectas condiciones.\n\n¿Todo llegó bien? Tu opinión es muy importante para nosotros.\n\nSi tienes un momento, envíanos una foto con tu pedido. ¡La compartimos con mucho gusto!\n\nRecuerda que cuentas con *garantía de 1 año* por defectos de fabricación — cualquier cosa, aquí estamos. ¡Gracias por confiar en nosotros! 🙏",
            ],
            'cancelado' => [
                'Pedido cancelado',
                "Hola {nombre},\nLamentamos informarte que tu pedido de *{producto}* no pudo ser procesado en esta ocasión.\n\nSi tienes alguna duda o deseas hacer un nuevo pedido, con mucho gusto te atendemos. Recuerda que el envío es gratis y pagas contraentrega. 😊\n\n¡Esperamos verte pronto! 🙏",
            ],
            'recordatorio_oficina' => [
                'Recordatorio de recogida',
                "¡Hola {nombre}! 📦\nTu pedido de *{producto}* sigue esperando en la oficina de *Interrapidísimo* en {municipio}.\n\n*Importante:* si no lo recoges pronto, la transportadora lo devuelve automáticamente a los *5 días hábiles* y perderías tu compra.\n\nPara recogerlo solo necesitas:\n*Número de guía:* #{guia}\nO tu número de cédula\n\nRecuerda que pagas *{precio}* contraentrega, sin cobros adicionales. ¡Te esperamos! 🙏",
            ],
        ];
    }

    // Solo agrega estados que todavía no existan en la tabla (p.ej. si en el
    // futuro se agrega un estado nuevo a self::textos()). NUNCA debe tocar
    // filas ya existentes: sobrescribía en cada request cualquier plantilla
    // que el admin hubiera personalizado, con INSERT ... ON DUPLICATE KEY
    // UPDATE incondicional — el admin nunca podía guardar un cambio real.
    private function migrateTemplates(): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO plantillas_wa (estado, titulo, mensaje)
            VALUES (:estado, :titulo, :mensaje)
        ");

        foreach (self::textos() as $estado => $data) {
            $stmt->execute([':estado' => $estado, ':titulo' => $data[0], ':mensaje' => $data[1]]);
        }
    }

    public function todas(): array
    {
        $order = "'nuevo','contactado','confirmado','enviado','en_oficina','recordatorio_oficina','entregado','cancelado'";
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
}
