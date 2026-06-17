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

        $contextoLine = $contexto !== '' ? "\nDatos clave del público/producto: {$contexto}" : '';
        $prompt = <<<PROMPT
Eres un copywriter experto en direct response marketing para e-commerce en Colombia. Tu especialidad es crear anuncios que atacan el DOLOR real del cliente y lo llevan a comprar impulsivamente.

Producto: {$nombre}
Precio: \${$precio} COP{$contextoLine}

Genera EXACTAMENTE 3 variaciones de anuncio, una por cada ángulo psicológico. Responde SOLO con JSON válido, sin texto extra.

ÁNGULO 1 — "dolor" (tema: "oscuro")
Ataca el dolor, la frustración o vergüenza que siente el cliente HOY sin el producto.
El headline debe hacer que el cliente diga "¡eso me pasa a mí!".
Ejemplo de estructura: "¿Cansado de [dolor concreto]?" o "[Situación dolorosa] tiene solución"
body: amplía el dolor 1 segundo y luego ofrece el escape
cta: acción de alivio ("Quiero salir de eso", "Sí, lo necesito ya")

ÁNGULO 2 — "urgencia" (tema: "dorado")
FOMO puro. El cliente siente que si no actúa HOY pierde algo real.
Usa escasez, tiempo limitado, demanda alta — lo que aplique.
headline: comunica la pérdida si no actúa ("Solo quedan X", "Hoy termina", "El que no pide hoy...")
body: refuerza la escasez o el privilegio de actuar ahora
cta: acción urgente ("Pídelo antes de que se acabe", "Lo quiero YA", "Reservar el mío")

ÁNGULO 3 — "transformacion" (tema: "vibrante")
Vende el "nuevo yo". Cómo se va a SENTIR, VER o VIVIR el cliente después de comprar.
headline: la versión mejorada del cliente después del producto
body: pinta el resultado concreto — emocional o social
cta: aspiración ("Quiero ser esa versión", "Empezar el cambio", "Quiero verme así")

Reglas de oro:
- headline: máx 8 palabras. Específico, no genérico. Que duela o fascine.
- body: máx 15 palabras. 1 oración. Concreto.
- cta: máx 5 palabras. Que el dedo queme al verlo.
- Tono colombiano real: cercano, directo, informal-premium. Nada de "increíble calidad" ni "el mejor".
- El precio en el copy NUNCA lo menciones (ya aparece en la imagen).

Formato JSON estricto:
[
  {"headline":"...","body":"...","cta":"...","tema":"oscuro","angulo":"dolor"},
  {"headline":"...","body":"...","cta":"...","tema":"dorado","angulo":"urgencia"},
  {"headline":"...","body":"...","cta":"...","tema":"vibrante","angulo":"transformacion"}
]
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
        unset($ch);

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
