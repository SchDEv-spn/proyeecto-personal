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

    // AJAX: recibe foto + datos → devuelve copy IA + URL imagen guardada
    public function generarAnuncios(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $apiKey = (new AppSettings())->get('claude_api_key');
        if (!$apiKey) {
            echo json_encode(['ok' => false, 'error' => 'no_claude_key']);
            return;
        }

        $nombre   = trim($_POST['nombre']    ?? '');
        $precio   = trim($_POST['precio']    ?? '');
        $contexto = trim($_POST['contexto']  ?? '');

        if ($nombre === '' || $precio === '') {
            echo json_encode(['ok' => false, 'error' => 'Nombre y precio son requeridos']);
            return;
        }

        // Guardar imagen subida
        $imageUrl = '';
        if (!empty($_FILES['foto']['tmp_name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                echo json_encode(['ok' => false, 'error' => 'Formato de imagen no válido (jpg, png, webp)']);
                return;
            }
            $filename = 'mkt_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest     = $this->uploadDir() . $filename;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                $imageUrl = BASE_URL . '/public/uploads/marketing/' . $filename;
            }
        }

        $contextoLine = $contexto !== '' ? "\nContexto adicional: {$contexto}" : '';
        $prompt = <<<PROMPT
Eres un experto en marketing digital para e-commerce en Colombia. Genera 3 variaciones de anuncio para este producto.

Producto: {$nombre}
Precio: \${$precio} COP{$contextoLine}

Responde ÚNICAMENTE con un JSON válido, sin texto adicional, siguiendo exactamente este formato:
[
  {
    "headline": "Titular de máximo 7 palabras, impacto inmediato",
    "body": "Propuesta de valor en 1 oración, máximo 15 palabras",
    "cta": "Texto del botón de acción, máximo 5 palabras",
    "tema": "oscuro"
  },
  {
    "headline": "...",
    "body": "...",
    "cta": "...",
    "tema": "dorado"
  },
  {
    "headline": "...",
    "body": "...",
    "cta": "...",
    "tema": "vibrante"
  }
]

Reglas:
- Tono colombiano, informal pero premium
- headline genera urgencia o deseo fuerte
- body resuelve una objeción o destaca el beneficio clave
- cta es accionable (Pídelo ya, Lo quiero, Obtén el tuyo, etc.)
- Los temas son: "oscuro" (gradiente oscuro elegante), "dorado" (tono cálido dorado), "vibrante" (contraste fuerte)
- No añadas texto fuera del JSON
PROMPT;

        $result = $this->callClaude($apiKey, $prompt);
        if (!$result['ok']) {
            echo json_encode($result);
            return;
        }

        echo json_encode([
            'ok'       => true,
            'imageUrl' => $imageUrl,
            'ads'      => $result['ads'],
            'nombre'   => $nombre,
            'precio'   => $precio,
        ]);
    }

    private function callClaude(string $apiKey, string $prompt): array
    {
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
        curl_close($ch);

        if (!$response) return ['ok' => false, 'error' => 'Error de conexión con Claude'];

        $data = json_decode($response, true);
        if ($httpCode !== 200) {
            return ['ok' => false, 'error' => $data['error']['message'] ?? 'Error API'];
        }

        $text = $data['content'][0]['text'] ?? '';

        // Extraer array JSON
        $parsed = json_decode($text, true);
        if (!is_array($parsed)) {
            if (preg_match('/\[[\s\S]*\]/u', $text, $m)) {
                $parsed = json_decode($m[0], true);
            }
        }

        if (!is_array($parsed) || count($parsed) < 1) {
            return ['ok' => false, 'error' => 'No se pudo procesar la respuesta de la IA'];
        }

        return ['ok' => true, 'ads' => $parsed];
    }
}
