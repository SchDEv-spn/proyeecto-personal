<?php
/**
 * TEMPORAL — solo para saber con qué IP pública sale este servidor a internet
 * (la que hay que darle a Dropi para la aprobación de IT). Borrar después de usar:
 * no hace falta dejarlo público una vez tengamos el dato.
 */
$ip = trim((string)file_get_contents('https://api.ipify.org'));
echo "La IP pública de salida de este servidor es: " . htmlspecialchars($ip) . "\n";
