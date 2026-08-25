<?php

/**
 * Cliente de la API de integraciones de Dropi (api.dropi.co/integrations).
 * No extiende Model, pero sí usa AppSettings para leer el token — mismo
 * lugar donde vive la key de Claude/Replicate, configurable desde el panel
 * en vez de tocar el .env. Contrato de la API tomado del plugin oficial
 * "Dropify" (wc-dropi-integration), que es la única referencia pública
 * disponible — Dropi no publica un swagger.
 */
class Dropi
{
    private const BASE_URL = 'https://api.dropi.co/integrations/';

    private string $token;

    public function __construct()
    {
        $token = trim((string)(new AppSettings())->get('dropi_api_token', ''));
        if ($token === '') {
            // Fallback para desarrollo local vía .env — el panel es la vía normal.
            $token = trim((string)getenv('DROPI_API_TOKEN'));
        }
        $this->token = $token;
    }

    public function configurado(): bool
    {
        return $this->token !== '';
    }

    /** El token ya resuelto (app_settings, con fallback a .env) — para
     *  cuando el payload de Dropi también necesita el token dentro del
     *  cuerpo, no solo en el header. */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * Trae el producto tal cual lo tiene Dropi (id, user_id/proveedor, stock,
     * type SIMPLE|VARIABLE, precio de catálogo, etc). Se consulta en cada
     * intento de sincronización en vez de cachear, para no mandar una orden
     * con datos de proveedor desactualizados.
     */
    public function obtenerProducto(int $dropiProductId): array
    {
        if (!$this->configurado()) {
            return ['ok' => false, 'error' => 'Falta configurar DROPI_API_TOKEN.'];
        }
        if ($dropiProductId <= 0) {
            return ['ok' => false, 'error' => 'Producto sin dropi_product_id.'];
        }

        $resp = $this->request('GET', 'products/v2/' . $dropiProductId, null);
        if (!$resp['ok']) return $resp;

        $producto = $resp['body']['objects'] ?? null;
        if (!is_array($producto)) {
            return ['ok' => false, 'error' => 'Dropi no devolvió el producto ' . $dropiProductId . '.'];
        }

        return ['ok' => true, 'producto' => $producto];
    }

    /**
     * Crea la orden en Dropi. $data ya debe traer el payload completo
     * (ver AdminPedidosController::construirPedidoDropi).
     * Devuelve ['ok' => bool, 'dropi_order_id' => ?int, 'error' => ?string].
     */
    public function crearOrden(array $data): array
    {
        if (!$this->configurado()) {
            return ['ok' => false, 'dropi_order_id' => null, 'error' => 'Falta configurar DROPI_API_TOKEN.'];
        }

        $resp = $this->request('POST', 'orders/myorders', $data);
        if (!$resp['ok']) {
            return ['ok' => false, 'dropi_order_id' => null, 'error' => $resp['error']];
        }

        $body = $resp['body'];
        if (empty($body['isSuccess'])) {
            $msg = $body['message'] ?? ($body['status'] ?? 'Dropi rechazó la orden.');
            return ['ok' => false, 'dropi_order_id' => null, 'error' => (string)$msg];
        }

        $ordenId = $body['objects']['id'] ?? null;
        return ['ok' => true, 'dropi_order_id' => $ordenId !== null ? (int)$ordenId : null, 'error' => null];
    }

    /**
     * @return array{ok:bool, body?:array, error?:string}
     */
    private function request(string $method, string $path, ?array $body): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL no disponible en este servidor'];
        }

        $url = self::BASE_URL . $path;
        $ch  = curl_init($url);

        $opts = [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json;charset=UTF-8',
                'dropi-integration-key: ' . $this->token,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        }
        curl_setopt_array($ch, $opts);

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            error_log('[Dropi] cURL error (' . $path . '): ' . $err);
            return ['ok' => false, 'error' => 'No se pudo conectar con Dropi: ' . $err];
        }

        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            error_log('[Dropi] Respuesta no-JSON (' . $path . ', HTTP ' . $http . '): ' . substr((string)$raw, 0, 500));
            return ['ok' => false, 'error' => 'Dropi respondió algo inesperado (HTTP ' . $http . ').'];
        }

        return ['ok' => true, 'body' => $decoded];
    }
}
