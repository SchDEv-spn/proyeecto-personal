<?php
// Cargar .env
foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v));
}

$token  = getenv('TELEGRAM_BOT_TOKEN');
$chatId = getenv('TELEGRAM_CHAT_ID');

echo "<pre>";
echo "Token:   " . ($token  ? substr($token, 0, 10) . '...' : '❌ NO ENCONTRADO') . "\n";
echo "Chat ID: " . ($chatId ?: '❌ NO ENCONTRADO') . "\n\n";

if (!$token || !$chatId) {
    die("❌ Faltan credenciales en .env\n");
}

$url  = "https://api.telegram.org/bot{$token}/sendMessage";
$body = json_encode([
    'chat_id'    => $chatId,
    'text'       => "✅ Prueba de conexión desde tienda_mvc\n\nSi ves este mensaje, la notificación de pedidos funcionará correctamente.",
    'parse_mode' => 'Markdown',
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "❌ Error cURL: " . $err . "\n";
} else {
    $data = json_decode($resp, true);
    if (!empty($data['ok'])) {
        echo "✅ Mensaje enviado correctamente a Telegram\n";
    } else {
        echo "❌ Error de la API de Telegram:\n";
        print_r($data);
    }
}
echo "</pre>";
