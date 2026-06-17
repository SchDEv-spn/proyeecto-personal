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

        $settings       = new AppSettings();
        $claudeKey      = $settings->get('claude_api_key');
        $replicateKey   = $settings->get('replicate_api_key');

        if (!$claudeKey) {
            echo json_encode(['ok' => false, 'error' => 'Falta la API key de Claude']);
            return;
        }

        $nombre   = trim($_POST['nombre']   ?? '');
        $precio   = trim($_POST['precio']   ?? '');
        $contexto = trim($_POST['contexto'] ?? '');

        if ($nombre === '' || $precio === '') {
            echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
            return;
        }

        // ── 1. Guardar foto subida ────────────────────────────────
        $localPath = '';
        $imageUrl  = '';
        if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                echo json_encode(['ok' => false, 'error' => 'Formato de imagen no válido (jpg, png, webp)']);
                return;
            }
            $filename  = 'mkt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $localPath = $this->uploadDir() . $filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $localPath)) {
                $imageUrl = BASE_URL . '/public/uploads/marketing/' . $filename;
            }
        }

        // ── 2. Claude → 3 copies de texto ────────────────────────
        set_time_limit(180);

        $copyResult = $this->callClaude($claudeKey, $nombre, $precio, $contexto);
        if (!$copyResult['ok']) {
            echo json_encode($copyResult);
            return;
        }
        $ads = $copyResult['ads'];

        // ── 3. Replicate → 3 imágenes estilizadas (paralelo) ─────
        if ($replicateKey && $localPath && file_exists($localPath)) {
            $imageData = 'data:image/' . ($ext ?? 'jpeg') . ';base64,' . base64_encode(file_get_contents($localPath));

            $stylePrompts = [
                'oscuro'   => "Keep the product exactly as-is. Transform background to dramatic dark studio: deep black/charcoal background, professional dramatic side lighting, sharp product focus, premium commercial product photography, no text",
                'dorado'   => "Keep the product exactly as-is. Transform background to warm luxury: soft golden hour sunlight bokeh, warm amber tones, premium lifestyle feel, elegant soft-focus background, no text",
                'vibrante' => "Keep the product exactly as-is. Transform background to vibrant modern: bold colorful gradient background, high energy pop aesthetic, vivid saturated colors, dynamic composition, no text",
            ];

            $generatedImages = $this->callReplicateMulti($replicateKey, $imageData, $stylePrompts, $nombre);

            // Descargar y guardar cada imagen generada localmente
            foreach ($ads as $i => $ad) {
                $tema = $ad['tema'] ?? 'oscuro';
                $remoteUrl = $generatedImages[$tema] ?? null;
                if ($remoteUrl && !isset($remoteUrl['error'])) {
                    $saved = $this->downloadImage($remoteUrl, 'gen_' . $tema);
                    if ($saved) {
                        $ads[$i]['imageUrl'] = BASE_URL . '/public/uploads/marketing/' . $saved;
                    }
                }
            }
        }

        // Si algún anuncio no tiene imagen generada, usa la original
        foreach ($ads as $i => $ad) {
            if (empty($ad['imageUrl'])) {
                $ads[$i]['imageUrl'] = $imageUrl;
            }
        }

        echo json_encode([
            'ok'            => true,
            'imageUrl'      => $imageUrl,
            'hasReplicate'  => (bool)($replicateKey),
            'ads'           => $ads,
            'nombre'        => $nombre,
            'precio'        => $precio,
        ]);
    }

    // ── Claude: genera 3 copies ───────────────────────────────────
    private function callClaude(string $apiKey, string $nombre, string $precio, string $contexto): array
    {
        $contextoLine = $contexto !== '' ? "\nDatos clave del público/producto: {$contexto}" : '';

        $prompt = <<<PROMPT
Eres un copywriter experto en direct response marketing para e-commerce en Colombia. Tu especialidad es crear anuncios que atacan el DOLOR real del cliente y lo llevan a comprar impulsivamente.

Producto: {$nombre}
Precio: \${$precio} COP{$contextoLine}

Genera EXACTAMENTE 3 variaciones de anuncio, una por cada ángulo psicológico. Responde SOLO con JSON válido, sin texto extra.

ÁNGULO 1 — "dolor" (tema: "oscuro")
Ataca el dolor, la frustración o vergüenza que siente el cliente HOY sin el producto.
El headline debe hacer que el cliente diga "¡eso me pasa a mí!".
Ejemplo: "¿Cansado de [dolor concreto]?" o "[Situación dolorosa] tiene solución"
body: amplía el dolor 1 segundo y luego ofrece el escape
cta: acción de alivio ("Sí, lo necesito ya", "Quiero solucionar esto")

ÁNGULO 2 — "urgencia" (tema: "dorado")
FOMO puro. El cliente siente que si no actúa HOY pierde algo real.
headline: comunica la pérdida si no actúa ("Solo quedan X", "Hoy termina", "El que no pide hoy...")
body: refuerza la escasez o el privilegio de actuar ahora
cta: acción urgente ("Lo quiero YA", "Reservar el mío", "Pídelo antes de que se acabe")

ÁNGULO 3 — "transformacion" (tema: "vibrante")
Vende el "nuevo yo". Cómo se va a SENTIR, VER o VIVIR el cliente después de comprar.
headline: la versión mejorada del cliente después del producto
body: pinta el resultado concreto — emocional o social
cta: aspiración ("Quiero verme así", "Empezar el cambio", "Quiero ser esa versión")

Reglas de oro:
- headline: máx 8 palabras. Específico. Que duela o fascine.
- body: máx 15 palabras. 1 oración. Concreto.
- cta: máx 5 palabras. Que el dedo queme al verlo.
- Tono colombiano: cercano, directo, informal-premium.
- NUNCA menciones el precio en el copy.

Formato JSON estricto (solo esto, sin texto extra):
[
  {"headline":"...","body":"...","cta":"...","tema":"oscuro","angulo":"dolor"},
  {"headline":"...","body":"...","cta":"...","tema":"dorado","angulo":"urgencia"},
  {"headline":"...","body":"...","cta":"...","tema":"vibrante","angulo":"transformacion"}
]
PROMPT;

        $payload = json_encode([
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 800,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
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
            if (preg_match('/\[[\s\S]*\]/u', $text, $m)) {
                $parsed = json_decode($m[0], true);
            }
        }

        if (!is_array($parsed) || count($parsed) < 1) {
            return ['ok' => false, 'error' => 'No se pudo parsear la respuesta de Claude'];
        }

        return ['ok' => true, 'ads' => $parsed];
    }

    // ── Replicate: 3 llamadas en paralelo con curl_multi ─────────
    private function callReplicateMulti(string $apiKey, string $imageData, array $stylePrompts, string $nombre): array
    {
        $endpoint = 'https://api.replicate.com/v1/models/black-forest-labs/flux-kontext-pro/predictions';
        $headers  = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'Prefer: wait',
        ];

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
                CURLOPT_TIMEOUT        => 120,
            ]);

            curl_multi_add_handle($mh, $ch);
            $handles[$tema] = $ch;
        }

        // Ejecutar en paralelo
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh);
        } while ($running > 0);

        $results = [];
        foreach ($handles as $tema => $ch) {
            $body     = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            unset($ch);

            if (!$body) {
                $results[$tema] = ['error' => 'Sin respuesta de Replicate'];
                continue;
            }

            $data = json_decode($body, true);
            if ($httpCode !== 200 && $httpCode !== 201) {
                $results[$tema] = ['error' => $data['detail'] ?? $data['error'] ?? 'Error Replicate'];
                continue;
            }

            $output = $data['output'] ?? null;
            if (is_array($output)) $output = $output[0] ?? null;

            if (!$output) {
                // Prediction creada pero no resuelta aún — polling
                $id = $data['id'] ?? null;
                $results[$tema] = $id ? $this->pollReplicate($apiKey, $id) : ['error' => 'Sin URL de imagen'];
            } else {
                $results[$tema] = $output;
            }
        }

        curl_multi_close($mh);
        return $results;
    }

    // ── Replicate: polling si Prefer:wait no resolvió ────────────
    private function pollReplicate(string $apiKey, string $id): string|array
    {
        for ($i = 0; $i < 20; $i++) {
            sleep(3);
            $ch = curl_init("https://api.replicate.com/v1/predictions/{$id}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $data   = json_decode(curl_exec($ch), true);
            unset($ch);
            $status = $data['status'] ?? '';
            if ($status === 'succeeded') {
                $out = $data['output'] ?? null;
                return is_array($out) ? ($out[0] ?? ['error' => 'Sin URL']) : ($out ?: ['error' => 'Sin URL']);
            }
            if ($status === 'failed') return ['error' => $data['error'] ?? 'Generación fallida'];
        }
        return ['error' => 'Timeout Replicate'];
    }

    // ── Descargar imagen generada y guardarla localmente ─────────
    private function downloadImage(string $url, string $prefix): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if (!$raw || $httpCode !== 200) return null;

        $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.jpg';
        $dest     = $this->uploadDir() . $filename;
        file_put_contents($dest, $raw);
        return $filename;
    }
}
