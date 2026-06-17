<?php

class AdminMarketingController extends Controller
{
    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/Auth/login');
            exit;
        }
    }

    private function uploadDir(): string
    {
        $dir = dirname(__DIR__, 2) . '/public/uploads/marketing/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    public function index(): void
    {
        $this->requireLogin();

        $settings = new AppSettings();
        $this->view('admin/marketing/index', [
            'tiene_claude_key'    => $settings->hasKey('claude_api_key'),
            'tiene_replicate_key' => $settings->hasKey('replicate_api_key'),
        ]);
    }

    // AJAX principal
    public function generarAnuncios(): void
    {
        $this->requireLogin();
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->_generarAnunciosCore();
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    private function _generarAnunciosCore(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $settings     = new AppSettings();
        $claudeKey    = $settings->get('claude_api_key');
        $replicateKey = $settings->get('replicate_api_key');

        if (!$claudeKey) {
            echo json_encode(['ok' => false, 'error' => 'Falta la API key de Claude']);
            return;
        }

        $nombre          = trim($_POST['nombre']          ?? '');
        $precio          = trim($_POST['precio']          ?? '');
        $caracteristicas = trim($_POST['caracteristicas'] ?? '');

        if ($nombre === '' || $precio === '') {
            echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
            return;
        }

        // ── 1. Guardar foto subida ────────────────────────────────
        $localPath = '';
        $imageUrl  = '';
        $ext       = 'jpeg';
        $imageData = '';

        if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                echo json_encode(['ok' => false, 'error' => 'Formato de imagen no válido (jpg, png, webp)']);
                return;
            }
            $filename  = 'mkt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $localPath = $this->uploadDir() . $filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $localPath)) {
                $imageUrl  = BASE_URL . '/public/uploads/marketing/' . $filename;
                $imageData = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($localPath));
            }
        }

        // ── 2. Claude analiza foto + genera copy, prompts e audiencia ─
        set_time_limit(180);

        $claudeResult = $this->callClaude($claudeKey, $nombre, $precio, $caracteristicas, $imageData);
        if (!$claudeResult['ok']) {
            echo json_encode($claudeResult);
            return;
        }

        $ads           = $claudeResult['ads'];
        $audiencia     = $claudeResult['audiencia']     ?? null;
        $imagePrompts  = $claudeResult['imagePrompts']  ?? [];

        // ── 3. Replicate → 3 imágenes (prompts de Claude) ────────
        if ($replicateKey && $imageData) {
            $stylePrompts = [
                'oscuro'   => $imagePrompts['hero']      ?? '',
                'dorado'   => $imagePrompts['lifestyle'] ?? '',
                'vibrante' => $imagePrompts['flatlay']   ?? '',
            ];

            // Filtrar temas con prompt vacío
            $stylePrompts = array_filter($stylePrompts, fn($p) => $p !== '');

            if (!empty($stylePrompts)) {
                $generatedImages = $this->callReplicateMulti($replicateKey, $imageData, $stylePrompts, $nombre);

                foreach ($ads as $i => $ad) {
                    $tema      = $ad['tema'] ?? 'oscuro';
                    $remoteUrl = $generatedImages[$tema] ?? null;
                    if ($remoteUrl && is_string($remoteUrl) && str_starts_with($remoteUrl, 'http')) {
                        $ads[$i]['imageUrl'] = $remoteUrl;
                    }
                }
            }
        }

        foreach ($ads as $i => $ad) {
            if (empty($ad['imageUrl'])) $ads[$i]['imageUrl'] = $imageUrl;
        }

        echo json_encode([
            'ok'           => true,
            'imageUrl'     => $imageUrl,
            'imagePath'    => $filename ?? '',
            'hasReplicate' => (bool)$replicateKey,
            'ads'          => $ads,
            'audiencia'    => $audiencia,
            'imagePrompts' => $imagePrompts,
            'nombre'       => $nombre,
            'precio'       => $precio,
        ]);
    }

    // AJAX: Claude mejora el prompt para una tarjeta específica
    public function sugerirPrompt(): void
    {
        $this->requireLogin();
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $settings  = new AppSettings();
            $claudeKey = $settings->get('claude_api_key');
            if (!$claudeKey) { echo json_encode(['ok' => false, 'error' => 'Sin API key Claude']); return; }

            $tipo            = trim($_POST['tipo']            ?? 'hero');   // hero | lifestyle | flatlay
            $nombre          = trim($_POST['nombre']          ?? '');
            $caracteristicas = trim($_POST['caracteristicas'] ?? '');
            $audienciaPerfil = trim($_POST['audiencia_perfil']?? '');
            $promptActual    = trim($_POST['prompt_actual']   ?? '');
            $imagePath       = trim($_POST['image_path']      ?? '');

            $tipoDesc = [
                'hero'      => 'Hero shot — el producto como protagonista absoluto sobre fondo dramático de estudio, sin personas',
                'lifestyle' => 'Lifestyle / In-use — el producto siendo usado por una persona real en un contexto aspiracional cotidiano',
                'flatlay'   => 'Flat lay editorial — el producto centrado sobre superficie premium rodeado de accesorios que conecten con el comprador',
            ][$tipo] ?? 'Hero shot';

            $refLine  = $audienciaPerfil ? "\nPúblico: {$audienciaPerfil}" : '';
            $caracLine = $caracteristicas ? "\nCaracterísticas: {$caracteristicas}" : '';
            $prevLine  = $promptActual ? "\nPrompt anterior (mejora este): {$promptActual}" : '';

            // Si hay imagen, incluirla para visión
            $content = [];
            $localPath = $this->uploadDir() . basename($imagePath);
            if ($imagePath && file_exists($localPath)) {
                $ext      = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
                $mime     = 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext);
                $b64      = base64_encode(file_get_contents($localPath));
                $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $b64]];
            }

            $promptText = <<<PROMPT
Eres un experto en fotografía de producto y publicidad digital.

Producto: {$nombre}{$caracLine}{$refLine}{$prevLine}

Genera UN prompt optimizado para Flux Kontext Pro (modelo img2img) para este tipo de imagen:
{$tipoDesc}

REGLAS CRÍTICAS DEL PROMPT:
1. Empieza SIEMPRE con: "Same product, preserve every detail, brand, color and shape EXACTLY."
2. Describe SOLO el entorno, iluminación, composición y contexto — NUNCA alteres el producto.
3. Sé muy específico para este producto (analiza la foto para entender qué es).
4. Termina SIEMPRE con: "Do not alter the product in any way. No text. No watermarks."
5. Máximo 80 palabras. En inglés.

Responde SOLO con el prompt en texto plano. Sin explicaciones, sin comillas, sin prefijos.
PROMPT;

            $content[] = ['type' => 'text', 'text' => $promptText];

            $payload = json_encode([
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => 300,
                'messages'   => [['role' => 'user', 'content' => $content]],
            ]);

            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-api-key: ' . $claudeKey, 'anthropic-version: 2023-06-01'],
                CURLOPT_TIMEOUT        => 30,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);

            $data   = json_decode($resp, true);
            $newPrompt = trim($data['content'][0]['text'] ?? '');
            if ($code !== 200 || !$newPrompt) {
                echo json_encode(['ok' => false, 'error' => $data['error']['message'] ?? 'Error Claude']);
                return;
            }
            echo json_encode(['ok' => true, 'prompt' => $newPrompt]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // AJAX: regenerar UNA imagen con prompt personalizado
    public function regenerarImagen(): void
    {
        $this->requireLogin();
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $settings     = new AppSettings();
            $replicateKey = $settings->get('replicate_api_key');
            $tema         = trim($_POST['tema']       ?? 'oscuro');
            $prompt       = trim($_POST['prompt']     ?? '');
            $imagePath    = trim($_POST['image_path'] ?? '');

            if (!$replicateKey) { echo json_encode(['ok' => false, 'error' => 'Sin API key Replicate']); return; }
            if (!$prompt)       { echo json_encode(['ok' => false, 'error' => 'Prompt vacío']); return; }

            $localPath = $this->uploadDir() . basename($imagePath);
            if (!file_exists($localPath)) { echo json_encode(['ok' => false, 'error' => 'Imagen original no encontrada']); return; }

            $ext       = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
            $imageData = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . base64_encode(file_get_contents($localPath));

            set_time_limit(120);
            $results = $this->callReplicateMulti($replicateKey, $imageData, [$tema => $prompt], 'producto');
            $url     = $results[$tema] ?? null;

            if ($url && is_string($url) && str_starts_with($url, 'http')) {
                echo json_encode(['ok' => true, 'imageUrl' => $url]);
            } else {
                echo json_encode(['ok' => false, 'error' => is_array($url) ? ($url['error'] ?? 'Error Replicate') : 'Sin respuesta']);
            }
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── Claude: visión + copy + prompts de imagen + audiencia ───────
    private function callClaude(string $apiKey, string $nombre, string $precio, string $caracteristicas, string $imageDataUri = ''): array
    {
        $caracLine = $caracteristicas !== '' ? "\nCaracterísticas: {$caracteristicas}" : '';

        $prompt = <<<PROMPT
Eres un experto en marketing digital, fotografía de producto y e-commerce colombiano.

Analiza la imagen del producto adjunta y los siguientes datos:
- Nombre: {$nombre}
- Precio: \${$precio} COP{$caracLine}

Con base en el análisis visual y los datos, genera un objeto JSON con estas 3 claves:

─────────────────────────────────────────
1. "audiencia" — segmentación para Facebook Ads
─────────────────────────────────────────
{
  "edad": "rango de edad principal (ej: 25-40 años)",
  "genero": "Masculino / Femenino / Ambos",
  "intereses": ["interes1","interes2","interes3","interes4","interes5"],
  "comportamientos": ["comportamiento1","comportamiento2","comportamiento3"],
  "perfil": "1 oración: quién es el comprador ideal y qué busca"
}

─────────────────────────────────────────
2. "imagePrompts" — prompts para Flux Kontext Pro (img2img)
─────────────────────────────────────────
Genera prompts específicos para ESTE producto (no genéricos).
Cada prompt debe: preservar el producto exactamente, y transformar el entorno.
{
  "hero": "prompt para hero shot dramático específico para este producto",
  "lifestyle": "prompt para foto lifestyle/in-use con persona real usando/llevando este producto específico",
  "flatlay": "prompt para flat lay editorial con accesorios que conecten con el buyer de este producto"
}
Reglas CRÍTICAS para los prompts:
- Empieza SIEMPRE con: "Same product, preserve every detail, brand, color and shape EXACTLY."
- Describe ÚNICAMENTE el entorno, iluminación, composición y contexto — NUNCA menciones cambios al producto.
- Sé muy específico al tipo de producto que ves en la foto (bolsa, reloj, ropa, etc.).
- Termina SIEMPRE con: "Do not alter the product in any way. No text. No watermarks."

─────────────────────────────────────────
3. "ads" — 3 variaciones de copy para Facebook Ads
─────────────────────────────────────────
ÁNGULO 1 — "dolor" (tema: "oscuro"): ataca la frustración/vergüenza actual sin el producto
ÁNGULO 2 — "urgencia" (tema: "dorado"): FOMO, pérdida si no actúa hoy
ÁNGULO 3 — "transformacion" (tema: "vibrante"): el nuevo yo después de comprar
Reglas: headline máx 8 palabras, body máx 15 palabras, cta máx 5 palabras.
Tono colombiano: cercano, directo, informal-premium. NUNCA menciones el precio.

─────────────────────────────────────────
Responde SOLO con JSON válido. Sin texto extra antes ni después.
─────────────────────────────────────────
{
  "audiencia": {...},
  "imagePrompts": {"hero":"...","lifestyle":"...","flatlay":"..."},
  "ads": [
    {"headline":"...","body":"...","cta":"...","tema":"oscuro","angulo":"dolor"},
    {"headline":"...","body":"...","cta":"...","tema":"dorado","angulo":"urgencia"},
    {"headline":"...","body":"...","cta":"...","tema":"vibrante","angulo":"transformacion"}
  ]
}
PROMPT;

        // Construir content con o sin imagen
        if ($imageDataUri !== '') {
            [$mimePrefix] = explode(';', ltrim($imageDataUri, 'data:'));
            $content = [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mimePrefix, 'data' => substr($imageDataUri, strpos($imageDataUri, ',') + 1)]],
                ['type' => 'text', 'text' => $prompt],
            ];
        } else {
            $content = $prompt;
        }

        $payload = json_encode([
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 1400,
            'messages'   => [['role' => 'user', 'content' => $content]],
        ]);

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
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if (!$response) return ['ok' => false, 'error' => 'Error de conexión con Claude'];

        $data = json_decode($response, true);
        if ($httpCode !== 200) {
            return ['ok' => false, 'error' => $data['error']['message'] ?? 'Error API Claude'];
        }

        $text   = $data['content'][0]['text'] ?? '';
        $parsed = json_decode($text, true);
        if (!is_array($parsed)) {
            if (preg_match('/\{[\s\S]*\}/u', $text, $m)) $parsed = json_decode($m[0], true);
        }

        if (!is_array($parsed) || empty($parsed['ads'])) {
            return ['ok' => false, 'error' => 'Claude no devolvió el formato esperado'];
        }

        return [
            'ok'           => true,
            'ads'          => $parsed['ads'],
            'audiencia'    => $parsed['audiencia']    ?? null,
            'imagePrompts' => $parsed['imagePrompts'] ?? [],
        ];
    }

    // ── Replicate: 3 llamadas en paralelo + polling paralelo ─────
    private function callReplicateMulti(string $apiKey, string $imageData, array $stylePrompts, string $nombre): array
    {
        $endpoint = 'https://api.replicate.com/v1/models/black-forest-labs/flux-kontext-pro/predictions';
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'Prefer: wait',
        ];

        // ── Fase 1: lanzar las 3 predicciones en paralelo ────────
        $mh      = curl_multi_init();
        $handles = [];

        foreach ($stylePrompts as $tema => $style) {
            $payload = json_encode([
                'input' => [
                    'prompt'           => $style . '. Product: ' . $nombre,
                    'input_image'      => $imageData,
                    'output_format'    => 'jpg',
                    'safety_tolerance' => 2,
                    'aspect_ratio'     => '1:1',
                ],
            ]);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 90,
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[$tema] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 1.0);
        } while ($running > 0);

        $results    = [];
        $pendingIds = []; // [tema => predictionId] para polling paralelo

        foreach ($handles as $tema => $ch) {
            $body     = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            unset($ch);

            if (!$body) { $results[$tema] = ['error' => 'Sin respuesta']; continue; }

            $data = json_decode($body, true);
            if ($httpCode !== 200 && $httpCode !== 201) {
                $results[$tema] = ['error' => $data['detail'] ?? $data['error'] ?? 'HTTP ' . $httpCode];
                continue;
            }

            $output = $data['output'] ?? null;
            if (is_array($output)) $output = $output[0] ?? null;

            if ($output) {
                $results[$tema] = $output; // ya resuelto ✓
            } else {
                $id = $data['id'] ?? null;
                if ($id) $pendingIds[$tema] = $id;
                else     $results[$tema]   = ['error' => 'Sin id de predicción'];
            }
        }
        curl_multi_close($mh);

        // ── Fase 2: polling paralelo para los que quedaron pending ─
        if (!empty($pendingIds)) {
            $polled = $this->pollReplicateMulti($apiKey, $pendingIds);
            foreach ($polled as $tema => $result) {
                $results[$tema] = $result;
            }
        }

        return $results;
    }

    // ── Polling paralelo con curl_multi ───────────────────────────
    private function pollReplicateMulti(string $apiKey, array $pendingIds, int $maxTries = 15): array
    {
        $results  = [];
        $pending  = $pendingIds; // [tema => id]
        $headers  = ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'];

        for ($try = 0; $try < $maxTries && !empty($pending); $try++) {
            sleep(4);

            $mh      = curl_multi_init();
            $handles = [];

            foreach ($pending as $tema => $id) {
                $ch = curl_init("https://api.replicate.com/v1/predictions/{$id}");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_TIMEOUT        => 10,
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[$tema] = $ch;
            }

            $running = null;
            do {
                curl_multi_exec($mh, $running);
                if ($running) curl_multi_select($mh, 1.0);
            } while ($running > 0);

            foreach ($handles as $tema => $ch) {
                $data   = json_decode(curl_multi_getcontent($ch) ?: '{}', true);
                curl_multi_remove_handle($mh, $ch);
                unset($ch);

                $status = $data['status'] ?? '';
                if ($status === 'succeeded') {
                    $out = $data['output'] ?? null;
                    $results[$tema] = is_array($out) ? ($out[0] ?? ['error' => 'Sin URL']) : ($out ?: ['error' => 'Sin URL']);
                    unset($pending[$tema]);
                } elseif ($status === 'failed') {
                    $results[$tema] = ['error' => $data['error'] ?? 'Generación fallida'];
                    unset($pending[$tema]);
                }
                // Si sigue 'processing'/'starting', queda en $pending para el siguiente try
            }

            curl_multi_close($mh);
        }

        // Los que aún siguen pendientes tras maxTries
        foreach ($pending as $tema => $_) {
            $results[$tema] = ['error' => 'Timeout polling'];
        }

        return $results;
    }

}
