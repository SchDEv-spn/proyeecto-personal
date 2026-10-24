<?php

/**
 * Consulta de pedidos para las vendedoras, SIN iniciar sesión: escriben el teléfono del cliente y ven en
 * qué estado va su pedido, con un mensaje listo para copiar o mandar por WhatsApp.
 *
 * Como no hay usuario ni código que teclear, la seguridad es esta (y conviene no quitarla):
 *  - La página vive en un enlace privado largo (/Consulta/ver/<clave>) que se guarda como favorito. La clave
 *    se puede cambiar desde el panel si se filtra. Con una clave equivocada la página responde 404, igual que
 *    cualquier dirección que no existe.
 *  - Solo se busca por el teléfono COMPLETO (10 dígitos), nunca por parte de un número ni por nombre, y se
 *    muestran como mucho 3 pedidos de los últimos 120 días.
 *  - Datos mínimos: estado, guía, transportadora, municipio, producto y cantidad. Nunca nombre, dirección,
 *    documento, correo ni precio; los mensajes tampoco los llevan.
 *  - Límite de consultas por dirección IP y registro de cada consulta (últimos 4 dígitos, no el número entero).
 */
class ConsultaPedidos extends Model
{
    private const CLAVE_ENLACE = 'consulta_token';
    public const DIAS_ATRAS = 120;
    public const MAX_ORDENES = 3;
    /** Consultas permitidas por IP en la ventana, y en total (por si cambian de IP). */
    public const LIMITE_POR_IP = 40;
    public const LIMITE_TOTAL = 400;
    public const VENTANA_MINUTOS = 10;

    private static bool $listo = false;

    public function __construct()
    {
        parent::__construct();
        $this->crearTablas();
    }

    private function crearTablas(): void
    {
        if (self::$listo) return;
        self::$listo = true;
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS consulta_registros (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                creado_en  DATETIME NOT NULL,
                ip_hash    CHAR(64) NOT NULL,
                tel_fin    CHAR(4)  NOT NULL DEFAULT '',
                resultados SMALLINT NOT NULL DEFAULT 0,
                KEY idx_ip (ip_hash, creado_en),
                KEY idx_fecha (creado_en)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /* ─────────────────────────────  Enlace privado  ───────────────────────────── */

    /** La clave del enlace; se crea la primera vez que se pide. */
    public function clave(): string
    {
        $ajustes = new AppSettings();
        $clave = (string)$ajustes->get(self::CLAVE_ENLACE, '');
        if (!preg_match('/^[a-f0-9]{32}$/', $clave)) $clave = $this->rotarClave();
        return $clave;
    }

    /** Cambia la clave: el enlace anterior deja de servir. */
    public function rotarClave(): string
    {
        $clave = bin2hex(random_bytes(16));
        (new AppSettings())->set(self::CLAVE_ENLACE, $clave);
        return $clave;
    }

    public function claveValida(string $clave): bool
    {
        $real = (string)(new AppSettings())->get(self::CLAVE_ENLACE, '');
        return $real !== '' && preg_match('/^[a-f0-9]{32}$/', $clave) === 1 && hash_equals($real, $clave);
    }

    /* ─────────────────────────────  Límite y registro  ───────────────────────────── */

    private static function huella(string $ip): string
    {
        return hash('sha256', 'consulta|' . $ip);
    }

    /** true si todavía puede consultar; false si se pasó del límite (no se cuenta el intento bloqueado). */
    public function permitido(string $ip): bool
    {
        $desde = date('Y-m-d H:i:s', strtotime('-' . self::VENTANA_MINUTOS . ' minutes'));
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM consulta_registros WHERE ip_hash = :h AND creado_en >= :d');
        $stmt->execute([':h' => self::huella($ip), ':d' => $desde]);
        if ((int)$stmt->fetchColumn() >= self::LIMITE_POR_IP) return false;

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM consulta_registros WHERE creado_en >= :d');
        $stmt->execute([':d' => $desde]);
        return (int)$stmt->fetchColumn() < self::LIMITE_TOTAL;
    }

    public function registrar(string $ip, string $telefono, int $resultados): void
    {
        $this->db->prepare('INSERT INTO consulta_registros (creado_en, ip_hash, tel_fin, resultados) VALUES (NOW(), ?, ?, ?)')
            ->execute([self::huella($ip), substr(preg_replace('/\D+/', '', $telefono), -4), max(0, $resultados)]);
        if (random_int(1, 50) === 1) { // limpieza ocasional: no se guarda más de 30 días
            $this->db->exec('DELETE FROM consulta_registros WHERE creado_en < NOW() - INTERVAL 30 DAY');
        }
    }

    /** @return array<int, array{creado_en: string, tel_fin: string, resultados: int}> */
    public function ultimas(int $cuantas = 15): array
    {
        $stmt = $this->db->prepare('SELECT creado_en, tel_fin, resultados FROM consulta_registros ORDER BY id DESC LIMIT :n');
        $stmt->bindValue(':n', max(1, min(100, $cuantas)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function consultasHoy(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM consulta_registros WHERE creado_en >= CURDATE()")->fetchColumn();
    }

    /* ─────────────────────────────  Búsqueda  ───────────────────────────── */

    /**
     * Teléfono completo de 10 dígitos (acepta +57, 57 o 0057 delante), o null si no sirve.
     */
    public static function normalizarTelefono(string $texto): ?string
    {
        $d = (string)preg_replace('/\D+/', '', $texto);
        if (strlen($d) === 14 && strpos($d, '0057') === 0) $d = substr($d, 4);
        elseif (strlen($d) === 12 && strpos($d, '57') === 0) $d = substr($d, 2);
        return (strlen($d) === 10 && $d[0] === '3') ? $d : null;
    }

    /**
     * Pedidos recientes de ese teléfono (los más nuevos primero). Solo las columnas que la página usa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscar(string $telefono10): array
    {
        $stmt = $this->db->prepare('
            SELECT o.fecha_pedido, o.estado, o.estatus, o.estatus_desde, o.guia, o.transportadora, o.tipo_entrega, o.municipio,
                   o.producto, o.cantidad, o.novedad, o.novedad_tipo,
                   (SELECT COUNT(*) FROM dropi_orden_lineas l WHERE l.orden_id = o.id) AS n_lineas
            FROM dropi_ordenes o
            WHERE RIGHT(o.telefono, 10) = :t AND o.fecha_pedido >= :desde
            ORDER BY o.fecha_pedido DESC, o.id DESC
            LIMIT ' . (int)self::MAX_ORDENES
        );
        $stmt->execute([':t' => $telefono10, ':desde' => date('Y-m-d H:i:s', strtotime('-' . self::DIAS_ATRAS . ' days'))]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Fecha de la última importación de Dropi, para decir qué tan al día están los datos. */
    public function datosAl(): ?string
    {
        try {
            $f = $this->db->query('SELECT MAX(visto_at) FROM dropi_ordenes')->fetchColumn();
            return $f ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /* ─────────────────────────────  Textos (puros)  ───────────────────────────── */

    /**
     * Enlace para abrir el chat con el cliente en WhatsApp BUSINESS (las vendedoras lo usan, y un enlace
     * normal de wa.me abre el WhatsApp personal si el teléfono tiene los dos).
     *  - Android: enlace "intent" de Chrome que pide la app com.whatsapp.w4b; si no está instalada, cae en wa.me.
     *  - iPhone: el esquema de WhatsApp Business.
     *  - Otros (computador): el enlace normal de wa.me, que abre WhatsApp Web o la app de escritorio.
     * Nadie puede forzar la app desde una página web en un computador; en el celular sí.
     */
    public static function enlaceWhatsApp(string $telefono10, string $mensaje, string $userAgent = ''): string
    {
        $numero = '57' . preg_replace('/\D+/', '', $telefono10);
        $texto  = rawurlencode($mensaje);
        $web    = 'https://wa.me/' . $numero . '?text=' . $texto;

        if (stripos($userAgent, 'Android') !== false) {
            return 'intent://send?phone=' . $numero . '&text=' . $texto
                . '#Intent;scheme=whatsapp;package=com.whatsapp.w4b;S.browser_fallback_url=' . rawurlencode($web) . ';end';
        }
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return 'whatsapp-business://send?phone=' . $numero . '&text=' . $texto;
        }
        return $web;
    }

    /** El estado en palabras sencillas, para la vendedora. */
    public static function estadoLegible(array $o): string
    {
        $dias = '';
        if (($o['estado'] ?? '') === 'en_oficina' && !empty($o['estatus_desde'])) {
            $n = (int)floor((strtotime(date('Y-m-d')) - strtotime(substr((string)$o['estatus_desde'], 0, 10))) / 86400);
            $dias = $n <= 0 ? ' · llegó hoy' : ' · lleva ' . $n . ' día' . ($n === 1 ? '' : 's');
        }
        if (($o['estado'] ?? '') === 'enviado' && trim((string)($o['novedad'] ?? '')) !== '') return 'Con novedad en la entrega';
        switch ($o['estado'] ?? '') {
            case 'confirmado': return 'Confirmado, se está preparando el envío';
            case 'enviado':    return 'En camino';
            case 'en_oficina': return 'Esperando que lo recojan en la oficina' . $dias;
            case 'entregado':  return 'Entregado';
            case 'devolucion': return 'En devolución';
            case 'cancelado':  return 'Cancelado';
            default:           return 'En la transportadora: ' . ucfirst(mb_strtolower((string)($o['estatus'] ?? ''), 'UTF-8'));
        }
    }

    /**
     * El mensaje listo para mandarle al cliente según el estado de su pedido. Lleva guía, transportadora y
     * municipio (que la vendedora ya conoce), pero no el nombre, la dirección ni el precio.
     */
    public static function mensaje(array $o): string
    {
        $producto = trim((string)($o['producto'] ?? ''));
        $pedido   = $producto !== '' ? 'tu pedido de *' . $producto . '*' : 'tu pedido';
        $guia     = trim((string)($o['guia'] ?? ''));
        $municipio = trim((string)($o['municipio'] ?? ''));
        $info     = DropiOrden::transportadoraInfo((string)($o['transportadora'] ?? ''));
        $transp   = $info['nombre'] ?? 'la transportadora';
        $rastreo  = $info['rastreo'] ?? '';
        $oficina  = ($o['tipo_entrega'] ?? '') !== 'domicilio';
        $lineaGuia = $guia !== '' ? "\n\n*Número de guía:* {$guia}" : '';
        $lineaRastreo = ($guia !== '' && $rastreo !== '') ? "\n*Seguimiento:* {$rastreo}" : '';

        $estado = (string)($o['estado'] ?? '');
        if ($estado === 'enviado' && trim((string)($o['novedad'] ?? '')) !== '') {
            $t = DropiOrden::novedadTextos((string)($o['novedad_tipo'] ?? '') ?: 'otra', $municipio, '');
            return "Hola 😊 {$pedido}" . ($guia !== '' ? " (guía {$guia})" : '') . " tiene una novedad con la entrega. " . $t['motivo'] . ' ' . $t['peticion'];
        }

        switch ($estado) {
            case 'confirmado':
                return "Hola 😊 {$pedido} ya está confirmado y lo estamos alistando para enviarlo. Apenas salga te paso el número de guía.";
            case 'enviado':
                $llegada = $oficina
                    ? 'Cuando llegue a la oficina' . ($municipio !== '' ? " de {$municipio}" : '') . ' te escribo para que lo recojas; no hace falta que vayas antes.'
                    : 'Estate pendiente del teléfono: el mensajero te llamará para entregártelo.';
                return "Hola 😊 {$pedido} ya salió y va en camino con {$transp}." . ($guia !== '' ? $lineaGuia . $lineaRastreo : "\n\nApenas aparezca el número de guía te lo paso.") . "\n\n{$llegada}";
            case 'en_oficina':
                $limite = DropiOrden::fechaLimite($o['estatus_desde'] ?? null);
                return "Hola 😊 {$pedido} ya está en la oficina de {$transp}" . ($municipio !== '' ? " en {$municipio}" : '') . '.' . $lineaGuia
                    . "\n\n" . ($limite !== '' ? "Tienes hasta {$limite} para recogerlo; después la transportadora lo devuelve." : 'Pasa a recogerlo cuanto antes: pasados unos días la transportadora lo devuelve.')
                    . "\n\nRecuerda que el pago es contraentrega, en efectivo. Pasa con tu cédula o el número de guía.";
            case 'entregado':
                return "Hola 😊 {$pedido} aparece como entregado" . ($guia !== '' ? " (guía {$guia})" : '') . '. ¿Ya lo tienes contigo? Si hay algún problema, cuéntame.';
            case 'devolucion':
                return "Hola 😊 {$pedido}" . ($guia !== '' ? " (guía {$guia})" : '') . ' aparece en devolución hacia nuestra bodega. ¿Quieres que lo volvamos a enviar? Cuéntame.';
            case 'cancelado':
                return "Hola 😊 {$pedido} aparece cancelado. Si todavía lo quieres, cuéntame y lo volvemos a hacer.";
            default:
                return "Hola 😊 {$pedido} va con la guía " . ($guia !== '' ? $guia : '(aún sin número)') . " de {$transp}. Su estado en la transportadora es: "
                    . ucfirst(mb_strtolower((string)($o['estatus'] ?? 'sin dato'), 'UTF-8')) . '.';
        }
    }
}
