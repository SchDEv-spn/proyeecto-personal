<?php

/**
 * Refresco rápido de Seguimiento vía el conector MCP de Dropi: le pide a la API de
 * Claude que llame list_orders en Dropi y devuelva estatus + guía de las órdenes
 * recientes, y los aplica con DropiOrden::actualizarEstadoDesdeMcp() — solo esos dos
 * campos, nunca novedad/dinero/departamento (eso sigue viniendo del Excel diario,
 * ver DropiImport). Pensado para correr seguido (cron_dropi_mcp_sync.php) y detectar
 * "llegó a oficina" / "sale hoy" sin esperar a la importación de la mañana.
 *
 * Usa el conector MCP de la API de mensajes de Claude (beta mcp-client-2025-11-20):
 * https://platform.claude.com — Anthropic resuelve las llamadas a list_orders del
 * lado del servidor, nosotros solo leemos el texto final.
 */
class DropiMcpSync
{
    private const ANTHROPIC_URL = 'https://api.anthropic.com/v1/messages';
    private const MODELO = 'claude-opus-5-5';

    /** Cuántos días hacia atrás se consultan. Las órdenes viejas ya están cerradas: el Excel diario las cubre. */
    private const DIAS_VENTANA = 5;

    /**
     * @return array{ok: bool, error?: string, total?: int, actualizadas?: int, iguales?: int, omitidas?: int}
     */
    public function ejecutar(): array
    {
        $claveClaude = (new AppSettings())->get('claude_api_key');
        if (!$claveClaude) {
            return ['ok' => false, 'error' => 'Falta configurar la API key de Claude (Marketing IA → conectar Claude).'];
        }

        $auth = new DropiMcpAuth();
        $tok = $auth->tokenVigente();
        if (!$tok['ok']) {
            return ['ok' => false, 'error' => $tok['error']];
        }

        $resp = $this->llamarClaude($claveClaude, $tok['token']);
        if (!$resp['ok']) return $resp;

        $ordenModel = new DropiOrden();
        $conteo = ['actualizadas' => 0, 'iguales' => 0, 'omitidas' => 0];
        foreach ($resp['ordenes'] as $o) {
            $id = (string)preg_replace('/\D+/', '', (string)($o['order_id'] ?? ''));
            if ($id === '') continue;

            $resultado = $ordenModel->actualizarEstadoDesdeMcp($id, (string)($o['status'] ?? ''), (string)($o['tracking_code'] ?? ''));
            $conteo[$resultado === 'actualizada' ? 'actualizadas' : ($resultado === 'igual' ? 'iguales' : 'omitidas')]++;
        }

        $total = count($resp['ordenes']);
        if ($total > 0) {
            $ordenModel->registrarImportacion('cron-mcp', 'via-mcp', $total, 0, $conteo['actualizadas'], $conteo['iguales']);
        }

        return ['ok' => true, 'total' => $total] + $conteo;
    }

    /**
     * @return array{ok: bool, error?: string, ordenes?: array<int, array{order_id:string,status:string,tracking_code:string}>}
     */
    private function llamarClaude(string $claveClaude, string $tokenMcp): array
    {
        $desde = date('Y-m-d', strtotime('-' . self::DIAS_VENTANA . ' days'));
        $hasta = date('Y-m-d');

        $instrucciones = <<<TXT
        Usá la herramienta list_orders del MCP de Dropi para traer TODAS las órdenes
        con fecha entre {$desde} y {$hasta} (parámetros from="{$desde}", until="{$hasta}").
        Pedí páginas de 100 (result_number=100) y seguí pidiendo la siguiente página
        (incrementando start) mientras la página devuelta venga llena, hasta agotarlas.

        Cuando tengas todas, respondé ÚNICA Y EXCLUSIVAMENTE con un JSON array (sin
        texto antes ni después, sin bloque de código) donde cada elemento sea
        {"order_id": "...", "status": "...", "tracking_code": "..."} tomados tal cual
        de cada orden (tracking_code "" si la orden no trae guía todavía). No agregues
        ni inventes campos. No expliques nada, no hagas resumen: solo el JSON.
        TXT;

        $payload = json_encode([
            'model'      => self::MODELO,
            'max_tokens' => 20000,
            'output_config' => ['effort' => 'low'],
            'mcp_servers' => [[
                'type'                => 'url',
                'url'                 => DropiMcpAuth::SERVER_URL,
                'name'                => 'dropi-mcp',
                'authorization_token' => $tokenMcp,
            ]],
            'tools'    => [['type' => 'mcp_toolset', 'mcp_server_name' => 'dropi-mcp']],
            'messages' => [['role' => 'user', 'content' => $instrucciones]],
        ], JSON_UNESCAPED_UNICODE);

        set_time_limit(280);

        $ch = curl_init(self::ANTHROPIC_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $claveClaude,
                'anthropic-version: 2023-06-01',
                'anthropic-beta: mcp-client-2025-11-20',
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 260,
        ]);

        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => 'No se pudo conectar con Claude: ' . $err];
        }

        $data = json_decode($raw, true);
        if ($http !== 200 || !is_array($data)) {
            error_log('[DropiMcpSync] HTTP ' . $http . ': ' . substr((string)$raw, 0, 800));
            return ['ok' => false, 'error' => is_array($data) ? ($data['error']['message'] ?? ('HTTP ' . $http)) : ('Respuesta inesperada (HTTP ' . $http . ')')];
        }

        $texto = '';
        foreach ($data['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? '') === 'text') $texto .= (string)($bloque['text'] ?? '');
        }

        $inicio = strpos($texto, '[');
        $fin = strrpos($texto, ']');
        if ($inicio === false || $fin === false || $fin < $inicio) {
            error_log('[DropiMcpSync] sin JSON en la respuesta: ' . substr($texto, 0, 500));
            return ['ok' => false, 'error' => 'Claude no devolvió la lista en el formato esperado.'];
        }

        $lista = json_decode(substr($texto, $inicio, $fin - $inicio + 1), true);
        if (!is_array($lista)) {
            return ['ok' => false, 'error' => 'No se pudo leer el JSON devuelto por Claude.'];
        }

        $ordenes = [];
        foreach ($lista as $o) {
            if (is_array($o) && !empty($o['order_id'])) $ordenes[] = $o;
        }
        return ['ok' => true, 'ordenes' => $ordenes];
    }
}
