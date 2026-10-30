<?php

/**
 * Cliente OAuth 2.1 (PKCE) contra el conector MCP de Dropi (mcp.dropi.co), para que
 * el refresco de guías (cron_dropi_mcp_sync.php) pueda llamarlo sin depender de una
 * sesión de Claude Code ni de un humano presente.
 *
 * Metadatos descubiertos (no hay documentación pública, así que quedan fijos aquí en
 * vez de hacer el descubrimiento en caliente en cada corrida):
 *   - recurso protegido:    https://mcp.dropi.co/mcp
 *   - servidor autorización: https://integrations.dropi.co
 *   - authorize:  https://oauth.dropi.co/oauth/authorize
 *   - token:      https://integrations.dropi.co/oauth/token
 *   - registro:   https://integrations.dropi.co/oauth/register  (RFC 7591, dinámico)
 *
 * Todo queda en app_settings (mismo lugar que claude_api_key / dropi_api_token):
 * dropi_mcp_client_id, dropi_mcp_client_secret, dropi_mcp_access_token,
 * dropi_mcp_refresh_token, dropi_mcp_expires_at.
 */
class DropiMcpAuth
{
    public const SERVER_URL = 'https://mcp.dropi.co/mcp';

    private const AUTHORIZE_URL = 'https://oauth.dropi.co/oauth/authorize';
    private const TOKEN_URL     = 'https://integrations.dropi.co/oauth/token';
    private const REGISTER_URL  = 'https://integrations.dropi.co/oauth/register';

    private AppSettings $settings;

    public function __construct()
    {
        $this->settings = new AppSettings();
    }

    public function conectado(): bool
    {
        return $this->settings->hasKey('dropi_mcp_refresh_token');
    }

    public function desconectar(): void
    {
        foreach (['dropi_mcp_client_id', 'dropi_mcp_client_secret', 'dropi_mcp_access_token', 'dropi_mcp_refresh_token', 'dropi_mcp_expires_at'] as $k) {
            $this->settings->set($k, null);
        }
    }

    /**
     * Da de alta esta instalación como cliente OAuth ante Dropi (una sola vez; queda
     * guardado). RFC 7591: sin credenciales previas, solo la URL de retorno.
     *
     * @return array{ok:bool, error?:string}
     */
    private function registrarCliente(string $redirectUri): array
    {
        if ($this->settings->hasKey('dropi_mcp_client_id')) return ['ok' => true];

        $resp = $this->postJson(self::REGISTER_URL, [
            'redirect_uris'              => [$redirectUri],
            'token_endpoint_auth_method' => 'none', // cliente público (PKCE); sin client_secret
            'grant_types'                => ['authorization_code', 'refresh_token'],
            'response_types'             => ['code'],
            'client_name'                => 'Tienda MVC — Seguimiento Dropi',
        ]);

        if (!$resp['ok'] || empty($resp['body']['client_id'])) {
            return ['ok' => false, 'error' => $resp['error'] ?? 'Dropi no devolvió un client_id al registrar la integración.'];
        }

        $this->settings->set('dropi_mcp_client_id', (string)$resp['body']['client_id']);
        if (!empty($resp['body']['client_secret'])) {
            $this->settings->set('dropi_mcp_client_secret', (string)$resp['body']['client_secret']);
        }
        return ['ok' => true];
    }

    /**
     * URL a la que mandar al admin para que autorice (pantalla "Autoriza Claude",
     * aquí para esta instalación). El verifier de PKCE se guarda en sesión: solo
     * sirve para este intento, lo consume callback().
     *
     * @return array{ok:bool, url?:string, error?:string}
     */
    public function urlAutorizacion(string $redirectUri): array
    {
        $reg = $this->registrarCliente($redirectUri);
        if (!$reg['ok']) return $reg;

        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $state = bin2hex(random_bytes(16));

        $_SESSION['dropi_mcp_pkce_verifier'] = $verifier;
        $_SESSION['dropi_mcp_pkce_state']    = $state;

        $qs = http_build_query([
            'response_type'         => 'code',
            'client_id'             => $this->settings->get('dropi_mcp_client_id'),
            'redirect_uri'          => $redirectUri,
            'scope'                 => 'mcp',
            'state'                 => $state,
            'code_challenge'        => $challenge,
            'code_challenge_method' => 'S256',
        ]);

        return ['ok' => true, 'url' => self::AUTHORIZE_URL . '?' . $qs];
    }

    /**
     * Procesa la vuelta de Dropi (código de un solo uso) y guarda los tokens.
     *
     * @return array{ok:bool, error?:string}
     */
    public function procesarCallback(string $code, string $state, string $redirectUri): array
    {
        if ($state === '' || $state !== ($_SESSION['dropi_mcp_pkce_state'] ?? null)) {
            return ['ok' => false, 'error' => 'El estado de la autorización no coincide (abrí el enlace dos veces o se venció). Intentá conectar de nuevo.'];
        }
        $verifier = (string)($_SESSION['dropi_mcp_pkce_verifier'] ?? '');
        unset($_SESSION['dropi_mcp_pkce_state'], $_SESSION['dropi_mcp_pkce_verifier']);
        if ($verifier === '') {
            return ['ok' => false, 'error' => 'Se perdió la sesión de autorización. Intentá conectar de nuevo.'];
        }

        $resp = $this->post(self::TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'client_id'     => $this->settings->get('dropi_mcp_client_id'),
            'code_verifier' => $verifier,
        ]);

        return $this->guardarTokens($resp);
    }

    /**
     * Un access_token vigente, renovándolo con el refresh_token si hace falta.
     * Es lo único que necesita cron_dropi_mcp_sync.php.
     *
     * @return array{ok:bool, token?:string, error?:string}
     */
    public function tokenVigente(): array
    {
        $token = $this->settings->get('dropi_mcp_access_token');
        $expira = (int)$this->settings->get('dropi_mcp_expires_at', '0');

        // 60s de margen: evita usar un token que vence a mitad de la llamada.
        if ($token && time() < $expira - 60) {
            return ['ok' => true, 'token' => $token];
        }

        $refresh = $this->settings->get('dropi_mcp_refresh_token');
        if (!$refresh) {
            return ['ok' => false, 'error' => 'Dropi (MCP) no está conectado — entrá al panel y conectalo.'];
        }

        $resp = $this->post(self::TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh,
            'client_id'     => $this->settings->get('dropi_mcp_client_id'),
        ]);

        $guardado = $this->guardarTokens($resp);
        if (!$guardado['ok']) return $guardado;

        return ['ok' => true, 'token' => (string)$this->settings->get('dropi_mcp_access_token')];
    }

    /** @param array{ok:bool, body?:array, error?:string} $resp */
    private function guardarTokens(array $resp): array
    {
        if (!$resp['ok'] || empty($resp['body']['access_token'])) {
            return ['ok' => false, 'error' => $resp['error'] ?? ($resp['body']['error_description'] ?? 'Dropi no devolvió un token válido.')];
        }
        $b = $resp['body'];

        $this->settings->set('dropi_mcp_access_token', (string)$b['access_token']);
        $this->settings->set('dropi_mcp_expires_at', (string)(time() + (int)($b['expires_in'] ?? 3600)));
        // Algunos servidores no reemiten refresh_token en cada refresh: si no viene, se conserva el que ya había.
        if (!empty($b['refresh_token'])) {
            $this->settings->set('dropi_mcp_refresh_token', (string)$b['refresh_token']);
        }
        return ['ok' => true];
    }

    /**
     * @param array<string,mixed> $campos
     * @param string[]            $headersExtra
     * @return array{ok:bool, body?:array, error?:string}
     */
    /** application/x-www-form-urlencoded — lo que espera /oauth/token (RFC 6749). */
    private function post(string $url, array $campos): array
    {
        return $this->enviar($url, http_build_query($campos), 'application/x-www-form-urlencoded');
    }

    /** application/json — lo que espera /oauth/register (RFC 7591): sus campos son arrays
     *  (redirect_uris, grant_types…), que no tienen una forma válida como formulario. */
    private function postJson(string $url, array $campos): array
    {
        return $this->enviar($url, json_encode($campos, JSON_UNESCAPED_SLASHES), 'application/json');
    }

    private function enviar(string $url, string $cuerpo, string $contentType): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL no disponible en este servidor.'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: ' . $contentType,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            error_log('[DropiMcpAuth] cURL (' . $url . '): ' . $err);
            return ['ok' => false, 'error' => 'No se pudo conectar con Dropi: ' . $err];
        }

        $body = json_decode((string)$raw, true);
        if (!is_array($body)) {
            error_log('[DropiMcpAuth] respuesta no-JSON (' . $url . ', HTTP ' . $http . '): ' . substr((string)$raw, 0, 500));
            return ['ok' => false, 'error' => 'Dropi respondió algo inesperado (HTTP ' . $http . ').'];
        }
        if ($http >= 400) {
            return ['ok' => false, 'error' => $body['error_description'] ?? ($body['error'] ?? ('HTTP ' . $http)), 'body' => $body];
        }

        return ['ok' => true, 'body' => $body];
    }
}
