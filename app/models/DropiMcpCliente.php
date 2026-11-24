<?php

/**
 * Cliente mínimo del conector MCP de Dropi (https://mcp.dropi.co/mcp, transporte "HTTP
 * streamable") para que el servidor llame sus herramientas (list_orders, get_order…)
 * directo, con el token de DropiMcpAuth y SIN un modelo de lenguaje en medio: así los
 * nombres y teléfonos de los clientes llegan tal cual, sin que nadie los "copie".
 *
 * Protocolo: initialize → notifications/initialized → tools/call. La respuesta puede venir
 * como JSON o como un evento SSE; el resultado de una herramienta es texto (Dropi lo
 * entrega en formato TOON, ver parsear()) o, si el servidor lo manda, structuredContent.
 */
class DropiMcpCliente
{
    private const PROTOCOLO = '2025-06-18';

    private string $token;
    private string $url;
    private ?string $sesion = null;
    private bool $iniciado = false;
    private int $siguienteId = 1;

    public function __construct(string $token, string $url = DropiMcpAuth::SERVER_URL)
    {
        $this->token = $token;
        $this->url = $url;
    }

    /**
     * Llama una herramienta y devuelve su resultado ya convertido en array.
     *
     * @param  array<string,mixed> $args
     * @return array{ok:bool, datos?:array, error?:string}
     */
    public function llamar(string $herramienta, array $args = []): array
    {
        if (!$this->iniciado) {
            $ini = $this->iniciar();
            if (!$ini['ok']) return $ini;
        }

        $r = $this->rpc('tools/call', ['name' => $herramienta, 'arguments' => (object)$args]);
        if (!$r['ok']) return $r;

        $res = $r['resultado'];
        $texto = '';
        foreach ($res['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? '') === 'text') $texto .= (string)($bloque['text'] ?? '');
        }

        if (!empty($res['isError'])) {
            return ['ok' => false, 'error' => 'Dropi rechazó ' . $herramienta . ': ' . mb_substr(trim($texto) ?: 'sin detalle', 0, 300)];
        }
        if (isset($res['structuredContent']) && is_array($res['structuredContent'])) {
            return ['ok' => true, 'datos' => $res['structuredContent']];
        }
        return ['ok' => true, 'datos' => self::parsear($texto)];
    }

    /** @return array{ok:bool, error?:string} */
    private function iniciar(): array
    {
        $r = $this->rpc('initialize', [
            'protocolVersion' => self::PROTOCOLO,
            'capabilities'    => (object)[],
            'clientInfo'      => ['name' => 'tienda-mvc-seguimiento', 'version' => '1.0'],
        ]);
        if (!$r['ok']) return $r;

        // Aviso (sin respuesta) de que ya podemos trabajar; los servidores con sesión lo exigen.
        $this->rpc('notifications/initialized', null, false);
        $this->iniciado = true;
        return ['ok' => true];
    }

    /**
     * @param  array<string,mixed>|null $params
     * @return array{ok:bool, resultado?:array, error?:string}
     */
    private function rpc(string $metodo, ?array $params, bool $esperaRespuesta = true): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'cURL no disponible en este servidor.'];
        }

        $id = $esperaRespuesta ? $this->siguienteId++ : null;
        $mensaje = ['jsonrpc' => '2.0', 'method' => $metodo];
        if ($id !== null) $mensaje['id'] = $id;
        if ($params !== null) $mensaje['params'] = $params;

        $cabeceras = [
            'Content-Type: application/json',
            'Accept: application/json, text/event-stream',
            'Authorization: Bearer ' . $this->token,
        ];
        if ($metodo !== 'initialize') $cabeceras[] = 'MCP-Protocol-Version: ' . self::PROTOCOLO;
        if ($this->sesion !== null) $cabeceras[] = 'Mcp-Session-Id: ' . $this->sesion;

        $tipo = '';
        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($mensaje, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $cabeceras,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => function ($c, string $linea) use (&$tipo) {
                if (stripos($linea, 'mcp-session-id:') === 0) $this->sesion = trim(substr($linea, 15));
                elseif (stripos($linea, 'content-type:') === 0) $tipo = strtolower(trim(substr($linea, 13)));
                return strlen($linea);
            },
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            error_log('[DropiMcpCliente] cURL (' . $metodo . '): ' . $err);
            return ['ok' => false, 'error' => 'No se pudo conectar con Dropi: ' . $err];
        }
        if ($http === 401) {
            return ['ok' => false, 'error' => 'Dropi no aceptó la conexión (HTTP 401) — en Seguimiento desconecta y vuelve a conectar la actualización automática.'];
        }
        if ($http >= 400) {
            error_log('[DropiMcpCliente] HTTP ' . $http . ' (' . $metodo . '): ' . substr((string)$raw, 0, 500));
            return ['ok' => false, 'error' => 'Dropi respondió HTTP ' . $http . ' (' . $metodo . '): ' . mb_substr(trim((string)$raw), 0, 200)];
        }
        if (!$esperaRespuesta) return ['ok' => true];

        $msg = self::mensajeDe((string)$raw, strpos($tipo, 'text/event-stream') !== false, $id);
        if ($msg === null) {
            error_log('[DropiMcpCliente] respuesta ilegible (' . $metodo . '): ' . substr((string)$raw, 0, 500));
            return ['ok' => false, 'error' => 'Dropi respondió algo que no se pudo leer (' . $metodo . ').'];
        }
        if (isset($msg['error'])) {
            return ['ok' => false, 'error' => 'Dropi: ' . ($msg['error']['message'] ?? 'error sin detalle') . ' (' . $metodo . ')'];
        }
        return ['ok' => true, 'resultado' => (array)($msg['result'] ?? [])];
    }

    /** El mensaje JSON-RPC de la respuesta con ese id (en un SSE puede haber otros eventos antes). */
    private static function mensajeDe(string $raw, bool $sse, int $id): ?array
    {
        if (!$sse) {
            $m = json_decode($raw, true);
            return is_array($m) ? $m : null;
        }
        foreach (preg_split('/\R\R/', $raw) ?: [] as $evento) {
            $datos = '';
            foreach (preg_split('/\R/', $evento) ?: [] as $linea) {
                if (strpos($linea, 'data:') === 0) $datos .= ltrim(substr($linea, 5));
            }
            $m = json_decode($datos, true);
            if (is_array($m) && (($m['id'] ?? null) === $id)) return $m;
        }
        return null;
    }

    /* ─────────────────────────────  Formato TOON de las respuestas  ───────────────────────────── */

    /**
     * Convierte el texto de una herramienta de Dropi en un array. Cubre lo que Dropi emite:
     *   clave: valor            · clave: (y debajo, sangrado, un objeto)
     *   lista[N]{a,b,c}:        · y debajo, sangradas, N filas "v1,v2,v3" → lista de filas asociativas
     *   lista[N]: v1,v2,v3      · lista simple
     * Los valores van sin comillas o entre comillas (con \" \\ \n); todo se devuelve como texto.
     * Si el texto ya es JSON, se decodifica tal cual.
     */
    public static function parsear(string $texto): array
    {
        $texto = trim($texto);
        if ($texto !== '' && ($texto[0] === '{' || $texto[0] === '[')) {
            $j = json_decode($texto, true);
            if (is_array($j)) return $j;
        }
        $lineas = preg_split('/\R/', $texto) ?: [];
        $i = 0;
        return self::bloque($lineas, $i, 0);
    }

    private static function sangria(string $linea): int
    {
        return strlen($linea) - strlen(ltrim($linea, ' '));
    }

    /** @param string[] $lineas */
    private static function bloque(array $lineas, int &$i, int $minimo): array
    {
        $out = [];
        $n = count($lineas);
        while ($i < $n) {
            $linea = $lineas[$i];
            if (trim($linea) === '') { $i++; continue; }
            $sangria = self::sangria($linea);
            if ($sangria < $minimo) break;
            $i++;
            $c = trim($linea);

            // lista[N]{campos}:  con sus filas debajo
            if (preg_match('/^([^:\[\]{}"]+)\[\d*\]\{([^}]*)\}:$/', $c, $m)) {
                $campos = array_map('trim', explode(',', $m[2]));
                $filas = [];
                while ($i < $n && trim($lineas[$i]) !== '' && self::sangria($lineas[$i]) > $sangria) {
                    $vals = self::dividirFila(trim($lineas[$i++]));
                    $fila = [];
                    foreach ($campos as $k => $campo) $fila[$campo] = $vals[$k] ?? '';
                    $filas[] = $fila;
                }
                $out[trim($m[1])] = $filas;
                continue;
            }

            // lista[N]: v1,v2,v3   (o vacía: lista[0]:)  ·  lista[N]: y debajo "- campo: valor" por cada elemento
            if (preg_match('/^([^:\[\]{}"]+)\[\d*\]:\s*(.*)$/', $c, $m)) {
                if ($m[2] !== '') {
                    $out[trim($m[1])] = self::dividirFila($m[2]);
                } else {
                    $out[trim($m[1])] = self::elementos($lineas, $i, $sangria);
                }
                continue;
            }

            // clave: valor  /  clave:  (objeto debajo)
            if (preg_match('/^([^:]+):(?:\s+(.*))?$/', $c, $m)) {
                $clave = trim($m[1]);
                if (!isset($m[2]) || $m[2] === '') {
                    $out[$clave] = ($i < $n && trim($lineas[$i]) !== '' && self::sangria($lineas[$i]) > $sangria)
                        ? self::bloque($lineas, $i, $sangria + 1)
                        : '';
                } else {
                    $out[$clave] = self::dividirFila($m[2])[0] ?? '';
                }
            }
        }
        return $out;
    }

    /**
     * Los elementos de una lista en forma expandida:
     *   lista[2]:
     *     - campo: valor
     *       otro: valor
     *     - campo: valor
     * Dropi la usa en lugar de la tabla compacta cuando los elementos no tienen todos los mismos campos
     * (p. ej. basta una orden sin guía todavía para que toda la lista de órdenes salga así).
     * Un elemento sin "campo:" es un valor suelto ("- texto").
     *
     * @param string[] $lineas
     */
    private static function elementos(array $lineas, int &$i, int $sangriaLista): array
    {
        $n = count($lineas);
        $elementos = [];
        while ($i < $n && trim($lineas[$i]) !== '' && self::sangria($lineas[$i]) > $sangriaLista && strpos(ltrim($lineas[$i]), '- ') === 0) {
            $sangria = self::sangria($lineas[$i]);
            $contenido = substr(ltrim($lineas[$i]), 2);

            if (preg_match('/^(?:"(?:[^"\\\\]|\\\\.)*"|[^:",]+):(?:\s|$)/', $contenido)) {
                // El primer campo comparte línea con el "- "; se reescribe como un campo más del elemento
                // (sangrado 2 más que el guion, igual que los demás) y se lee el elemento como un bloque.
                $lineas[$i] = str_repeat(' ', $sangria + 2) . $contenido;
                $elementos[] = self::bloque($lineas, $i, $sangria + 1);
            } else {
                $elementos[] = self::dividirFila($contenido)[0] ?? '';
                $i++;
            }
        }
        return $elementos;
    }

    /**
     * Parte una fila por comas respetando las comillas ("a, b",c → ["a, b", "c"]); quita las
     * comillas y resuelve los escapes. Un valor sin comillas se devuelve recortado.
     *
     * @return string[]
     */
    private static function dividirFila(string $fila): array
    {
        $vals = [];
        $actual = '';
        $entre = false;
        $conComillas = false;
        $len = strlen($fila);
        for ($p = 0; $p < $len; $p++) {
            $ch = $fila[$p];
            if ($entre) {
                if ($ch === '\\' && $p + 1 < $len) {
                    $sig = $fila[++$p];
                    $actual .= ['n' => "\n", 'r' => "\r", 't' => "\t"][$sig] ?? $sig;
                } elseif ($ch === '"') {
                    $entre = false;
                } else {
                    $actual .= $ch;
                }
            } elseif ($ch === '"') {
                $entre = true;
                $conComillas = true;
                $actual = ''; // lo que hubiera antes de la comilla son solo espacios
            } elseif ($ch === ',') {
                $vals[] = $conComillas ? $actual : trim($actual);
                $actual = '';
                $conComillas = false;
            } else {
                $actual .= $ch;
            }
        }
        $vals[] = $conComillas ? $actual : trim($actual);
        return $vals;
    }
}
