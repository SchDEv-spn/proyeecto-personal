<?php

/**
 * LandingAnalisis — lectura del embudo con Claude.
 *
 * El panel dice DÓNDE se cae la gente; esto intenta decir POR QUÉ y qué
 * tocar. Recibe los mismos agregados que ve el administrador más la
 * configuración real de esa landing (qué secciones están encendidas,
 * descuentos, countdown) — sin ese contexto las respuestas serían consejos
 * de marketing genéricos en vez de "quita el campo X del paso 2".
 *
 * Cada análisis se guarda: la llamada cuesta dinero y no tiene sentido
 * repetirla al refrescar la página. Además así queda historial para
 * comparar lo que decía hace dos semanas con lo de ahora.
 */
class LandingAnalisis extends Model
{
    /** Con menos visitas que esto, cualquier lectura del embudo es ruido. */
    public const MIN_SESIONES = 30;

    /** Tope de análisis por hora: un clic nervioso no puede disparar la factura. */
    private const MAX_POR_HORA = 6;

    /**
     * Modelos que se pueden elegir en el panel. Lista blanca: el nombre del
     * modelo llega por POST y va directo a la API, así que no puede ser texto
     * libre. El coste es por análisis con el tamaño real de este payload
     * (~3.400 tokens de entrada, ~2.000 de salida).
     */
    public const MODELOS = [
        'claude-opus-5'   => ['nombre' => 'Opus 5',   'nota' => 'más criterio · ~$0,07'],
        'claude-sonnet-5' => ['nombre' => 'Sonnet 5', 'nota' => 'más barato · ~$0,03'],
    ];

    public const MODELO_POR_DEFECTO = 'claude-opus-5';

    /** Devuelve el modelo pedido si es uno de los permitidos, o el de por defecto. */
    public static function modeloValido(?string $modelo): string
    {
        return isset(self::MODELOS[$modelo]) ? $modelo : self::MODELO_POR_DEFECTO;
    }

    private const DDL = 'CREATE TABLE IF NOT EXISTS landing_analisis (
        id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
        producto_id  INT          NULL,
        periodo_dias SMALLINT UNSIGNED NOT NULL,
        entorno      ENUM("produccion","local") NOT NULL DEFAULT "produccion",
        sesiones     INT UNSIGNED NOT NULL DEFAULT 0,
        resultado    LONGTEXT     NOT NULL,
        modelo       VARCHAR(40)  NOT NULL,
        tokens_in    INT UNSIGNED NOT NULL DEFAULT 0,
        tokens_out   INT UNSIGNED NOT NULL DEFAULT 0,
        creado_en    DATETIME     NOT NULL,
        PRIMARY KEY (id),
        KEY idx_landing_analisis_filtro (producto_id, periodo_dias, entorno, creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    // ══════════════════════════════════════════════════════════
    //  PERSISTENCIA
    // ══════════════════════════════════════════════════════════

    /** El análisis más reciente para este mismo filtro, o null. */
    public function ultimo(int $productoId, int $dias, string $entorno): ?array
    {
        $fila = $this->consultar(
            'SELECT * FROM landing_analisis
              WHERE periodo_dias = ? AND entorno = ?
                AND ((? = 0 AND producto_id IS NULL) OR producto_id = ?)
              ORDER BY creado_en DESC LIMIT 1',
            [$dias, $entorno, $productoId, $productoId]
        );

        if (!$fila) return null;

        $fila = $fila[0];
        $fila['resultado'] = json_decode($fila['resultado'], true) ?: [];
        return $fila;
    }

    public function historial(int $limite = 10): array
    {
        return $this->consultar(
            'SELECT id, producto_id, periodo_dias, entorno, sesiones, creado_en
               FROM landing_analisis ORDER BY creado_en DESC LIMIT ' . max(1, min(50, $limite)),
            []
        );
    }

    private function guardar(int $productoId, int $dias, string $entorno, int $sesiones, array $resultado, array $uso, string $modelo): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO landing_analisis
                (producto_id, periodo_dias, entorno, sesiones, resultado, modelo, tokens_in, tokens_out, creado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $productoId > 0 ? $productoId : null,
            $dias,
            $entorno,
            $sesiones,
            json_encode($resultado, JSON_UNESCAPED_UNICODE),
            $modelo,
            (int)($uso['input_tokens'] ?? 0),
            (int)($uso['output_tokens'] ?? 0),
        ]);
    }

    /** Consulta tolerante: si la tabla aún no existe, la crea y reintenta. */
    private function consultar(string $sql, array $params): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            if ((string)$e->getCode() === '42S02') {
                $this->db->exec(self::DDL);
                return [];
            }
            error_log('LandingAnalisis — ' . $e->getMessage());
            return [];
        }
    }

    private function excedeLimite(): bool
    {
        $filas = $this->consultar(
            'SELECT COUNT(*) AS n FROM landing_analisis
              WHERE creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
            []
        );
        return (int)($filas[0]['n'] ?? 0) >= self::MAX_POR_HORA;
    }

    // ══════════════════════════════════════════════════════════
    //  ANÁLISIS
    // ══════════════════════════════════════════════════════════

    /**
     * Manda los datos a Claude y guarda el resultado.
     *
     * @param array $contexto  Lo que ve el panel + producto + config de la landing
     * @return array{ok:bool, error?:string, resultado?:array, creado_en?:string}
     */
    public function analizar(string $apiKey, array $contexto, int $productoId, int $dias, string $entorno, ?string $modelo = null): array
    {
        $modelo = self::modeloValido($modelo);

        $sesiones = (int)($contexto['metricas']['resumen']['sesiones'] ?? 0);

        if ($sesiones < self::MIN_SESIONES) {
            return ['ok' => false, 'error' => 'Hacen falta al menos ' . self::MIN_SESIONES
                . ' visitas en el periodo para que el análisis signifique algo. Ahora hay ' . $sesiones . '.'];
        }

        $this->db->exec(self::DDL);   // barato y deja la tabla lista antes de gastar en la API
        if ($this->excedeLimite()) {
            return ['ok' => false, 'error' => 'Ya se hicieron ' . self::MAX_POR_HORA
                . ' análisis en la última hora. Espera un poco antes de repetir.'];
        }

        $respuesta = $this->llamarClaude($apiKey, $contexto, $modelo);
        if (!$respuesta['ok']) return $respuesta;

        // Guardar es solo caché e historial. El análisis ya está hecho y
        // pagado: si el INSERT falla (p. ej. una tabla vieja con otro
        // esquema en producción), se devuelve igual en vez de perderlo
        // con un "Error interno".
        try {
            $this->guardar($productoId, $dias, $entorno, $sesiones, $respuesta['resultado'], $respuesta['uso'], $modelo);
        } catch (\Throwable $e) {
            error_log('LandingAnalisis::guardar — no se pudo persistir: ' . $e->getMessage()
                . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }

        return [
            'ok'        => true,
            'resultado' => $respuesta['resultado'],
            'creado_en' => date('Y-m-d H:i:s'),
            'modelo'    => self::MODELOS[$modelo]['nombre'] ?? $modelo,
        ];
    }

    /**
     * Esquema de la respuesta. Se envía como structured output: la API
     * garantiza que el JSON valida contra él, en vez de pedir "responde solo
     * JSON" y rescatar el texto con una expresión regular.
     */
    private function esquema(): array
    {
        $lista = fn(array $props, array $req) => [
            'type'  => 'array',
            'items' => ['type' => 'object', 'properties' => $props, 'required' => $req, 'additionalProperties' => false],
        ];

        return [
            'type'       => 'object',
            'properties' => [
                'diagnostico' => [
                    'type'        => 'string',
                    'description' => 'Dos o tres frases: dónde está el problema principal del embudo y qué lo sugiere.',
                ],
                'hallazgos' => $lista([
                    'titulo'     => ['type' => 'string', 'description' => 'El hallazgo en una frase.'],
                    'evidencia'  => ['type' => 'string', 'description' => 'Los números concretos que lo sustentan.'],
                    'confianza'  => ['type' => 'string', 'enum' => ['alta', 'media', 'baja']],
                ], ['titulo', 'evidencia', 'confianza']),
                'acciones' => $lista([
                    'accion'   => ['type' => 'string', 'description' => 'Qué hacer, concreto y ejecutable.'],
                    'donde'    => ['type' => 'string', 'description' => 'Sección o campo exacto del editor de landing donde se toca.'],
                    'impacto'  => ['type' => 'string', 'enum' => ['alto', 'medio', 'bajo']],
                    'esfuerzo' => ['type' => 'string', 'enum' => ['alto', 'medio', 'bajo']],
                ], ['accion', 'donde', 'impacto', 'esfuerzo']),
                'no_concluyente' => [
                    'type'        => 'array',
                    'items'       => ['type' => 'string'],
                    'description' => 'Lo que NO se puede afirmar con estos datos y qué haría falta para saberlo.',
                ],
            ],
            'required'             => ['diagnostico', 'hallazgos', 'acciones', 'no_concluyente'],
            'additionalProperties' => false,
        ];
    }

    private function sistema(): string
    {
        return <<<TXT
Eres analista de conversión. Analizas la landing de una tienda colombiana que
vende contra entrega y cierra los pedidos por WhatsApp. El tráfico llega casi
todo de anuncios de Facebook, en móvil Android, y la mayoría navega dentro del
navegador integrado de Facebook, que es lento y rompe cosas.

Cómo se mide el embudo (importa para no sacar conclusiones falsas):
- El paso 7 "pedido confirmado" lo registra el servidor: es un dato duro.
- Los pasos 2 a 6 los reporta el navegador del visitante. En el navegador
  integrado de Facebook se pierden eventos cuando el sistema mata la pestaña,
  así que una caída de pocos puntos entre dos pasos puede ser medición, no
  abandono. Una caída grande sí es real.
- "Hasta qué sección llegaron" es la última sección que el visitante vio; si
  una sección está apagada en la configuración, no aparecerá nunca.

Reglas de tu respuesta:
1. Cada hallazgo cita los números que lo sustentan. Sin números no es hallazgo.
2. Marca la confianza con honestidad. Con pocas visitas en un paso, es "baja".
3. No inventes causas. Si los datos no distinguen entre dos explicaciones,
   dilo en "no_concluyente" en vez de elegir una.
4. Las acciones van ordenadas por lo que más mueve la aguja primero, y dicen
   dónde se tocan usando la configuración de la landing que te paso.
5. Máximo 5 hallazgos y 5 acciones. Prefiere pocas y buenas.
6. Escribe en español de Colombia, directo, sin relleno ni felicitaciones.
TXT;
    }

    private function llamarClaude(string $apiKey, array $contexto, string $modelo): array
    {
        $datos = json_encode($contexto, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $payload = json_encode([
            'model'      => $modelo,
            // Opus 5 y Sonnet 5 razonan de forma adaptativa cuando no se envía
            // "thinking", y ese razonamiento gasta del mismo cupo de max_tokens.
            // Con 8000 el modelo se quedaba pensando y no emitía el JSON:
            // llegaba stop_reason "max_tokens" y el cuerpo vacío se veía como
            // "formato inesperado". 20000 deja aire de sobra para pensar + la
            // respuesta estructurada (que rara vez pasa de ~2500 tokens).
            'max_tokens' => 20000,
            'system'     => $this->sistema(),
            'output_config' => ['format' => ['type' => 'json_schema', 'schema' => $this->esquema()]],
            'messages'   => [[
                'role'    => 'user',
                'content' => "Estos son los datos de la landing en el periodo analizado:\n\n"
                    . $datos
                    . "\n\nAnaliza dónde se está perdiendo la intención de compra y qué hacer al respecto.",
            ]],
        ], JSON_UNESCAPED_UNICODE);

        // El razonamiento del modelo tarda: sin este margen la petición muere
        // a mitad y el administrador ve un error de conexión que no lo es.
        set_time_limit(200);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 180,
        ]);

        $respuesta = curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errCurl   = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            return ['ok' => false, 'error' => 'No se pudo conectar con Claude: ' . $errCurl];
        }

        $data = json_decode($respuesta, true);

        if ($httpCode !== 200) {
            error_log('LandingAnalisis — HTTP ' . $httpCode . ': ' . substr($respuesta, 0, 500));
            return ['ok' => false, 'error' => $data['error']['message'] ?? ('Error de la API de Claude (HTTP ' . $httpCode . ')')];
        }

        // El primer bloque no siempre es el texto: con razonamiento activo
        // vienen bloques "thinking" delante. Hay que buscar el de tipo texto.
        $texto = '';
        foreach ($data['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? '') === 'text') {
                $texto = $bloque['text'] ?? '';
                break;
            }
        }

        $resultado = json_decode($texto, true);

        // Red de seguridad por si el structured output no viniera limpio
        // (fence ```json, texto delante): rescatar el primer objeto.
        if ((!is_array($resultado) || !isset($resultado['diagnostico']))
            && $texto !== '' && preg_match('/\{[\s\S]*\}/u', $texto, $m)) {
            $resultado = json_decode($m[0], true);
        }

        if (!is_array($resultado) || !isset($resultado['diagnostico'])) {
            // Sin esto la ruta de fallo no dejaba rastro y no se sabía por qué.
            $stop = $data['stop_reason'] ?? '';
            error_log('LandingAnalisis — respuesta no parseable. stop_reason=' . $stop
                . ' | usage=' . json_encode($data['usage'] ?? [])
                . ' | texto=' . substr($texto, 0, 800));

            if ($stop === 'max_tokens') {
                return ['ok' => false, 'error' => 'El análisis se quedó sin espacio antes de terminar. '
                    . 'Prueba con un periodo más corto o cambia el modelo a Sonnet 5.'];
            }
            if ($stop === 'refusal') {
                return ['ok' => false, 'error' => 'Claude no pudo completar este análisis ('
                    . ($data['stop_details']['explanation'] ?? 'sin detalle') . ').'];
            }
            return ['ok' => false, 'error' => 'Claude respondió en un formato inesperado.'];
        }

        return [
            'ok'        => true,
            'resultado' => $resultado,
            'uso'       => $data['usage'] ?? [],
        ];
    }
}
