<?php

class LandingController extends Controller
{
    /**
     * Total con descuento dinámico según producto:
     * - 1ra unidad: sin descuento
     * - 2da unidad: d2% OFF
     * - 3ra+ unidad: d3% OFF
     * Si activo != 1 => no aplica descuento multicantidad.
     */
    private function totalConDescuento(int $cantidad, float $precioUnit, int $d2, int $d3, int $activo = 1): float
    {
        if ($cantidad <= 0) return 0.0;

        $d2 = max(0, min(100, (int)$d2));
        $d3 = max(0, min(100, (int)$d3));
        $activo = (int)$activo;

        // Si está apagado, cobra normal
        if ($activo !== 1) {
            return $precioUnit * $cantidad;
        }

        if ($cantidad === 1) return $precioUnit;

        $total = 0.0;

        // 1ra sin descuento
        $total += $precioUnit;

        // 2da con d2%
        if ($cantidad >= 2) {
            $total += $precioUnit * (1 - ($d2 / 100));
        }

        // 3ra+ con d3%
        if ($cantidad >= 3) {
            $total += ($cantidad - 2) * ($precioUnit * (1 - ($d3 / 100)));
        }

        return $total;
    }

    /**
     * Total por COMBOS x2:
     * - Cada 2 unidades aplica comboPrice2
     * - Si queda 1 unidad suelta, se cobra al precio unitario (precioVenta)
     */
    private function totalPorCombo2(int $cantidad, float $precioVenta, int $comboPrice2): float
    {
        if ($cantidad <= 0) return 0.0;

        $comboPrice2 = max(0, (int)$comboPrice2);

        $combos = intdiv($cantidad, 2);
        $sobra  = $cantidad % 2;

        return ($combos * $comboPrice2) + ($sobra * $precioVenta);
    }

    public function index()
    {
        $productoId = (int)($_GET['producto_id'] ?? ($_GET['id'] ?? 1));
        if ($productoId <= 0) $productoId = 1;

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorId($productoId);

        if (!$producto) {
            $productoId = 1;
            $producto   = $productoModel->obtenerPorId($productoId);
            if (!$producto) {
                header("HTTP/1.0 404 Not Found");
                echo "Producto no encontrado";
                exit;
            }
        }

        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);

        $configModel = new LandingConfig();
        $config      = $configModel->obtenerPorProducto($productoId) ?? [];

        $productoColorModel = new ProductoColor();
        $colores = $productoColorModel->obtenerActivosPorProducto((int)$producto['id']);

        $pedidoModel      = new Pedido();
        $pedidosRecientes = $pedidoModel->contarPedidosRecientes($productoId, 30);

        $this->view('landing/index', [
            'producto'          => $producto,
            'colores'           => $colores,
            'errores'           => [],
            'old'               => [],
            'success'           => $success,
            'config'            => $config,
            'pedidos_recientes' => $pedidosRecientes,
        ]);
    }

    public function enviarPedido()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/Landing/index");
            exit;
        }

        // ✅ Detectar AJAX — debe ir AQUÍ, antes de cualquier uso
        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            
        $productoId = (int)($_POST['producto_id'] ?? 1);
        if ($productoId <= 0) $productoId = 1;

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorId($productoId);

        if (!$producto) {
            header("HTTP/1.0 404 Not Found");
            echo "Producto no encontrado";
            exit;
        }

        $configModel = new LandingConfig();
        $config      = $configModel->obtenerPorProducto($productoId) ?? [];

        $productoColorModel = new ProductoColor();
        $coloresPermitidos  = $productoColorModel->obtenerActivosPorProducto($productoId);

        // ===== Config combos =====
        $comboEnabled = (int)($config['combo_enabled'] ?? 0);
        $comboPrice2  = (int)($config['combo_price_2'] ?? 0);
        if ($comboPrice2 <= 0) $comboPrice2 = 115000; // fallback sano

        // ===== Modo (combo / individual) =====
        $pricingMode = trim((string)($_POST['pricing_mode'] ?? ''));
        if ($pricingMode === '') {
            // si no viene, default: si combo está activo => combo, si no => individual
            $pricingMode = ($comboEnabled === 1) ? 'combo' : 'individual';
        }
        if (!in_array($pricingMode, ['combo', 'individual'], true)) {
            $pricingMode = ($comboEnabled === 1) ? 'combo' : 'individual';
        }
        // Si el admin apagó combo, forzamos individual
        if ($comboEnabled !== 1) {
            $pricingMode = 'individual';
        }

        // Campos base
        $nombre       = trim($_POST['nombre']       ?? '');
        $apellidos    = trim($_POST['apellidos']    ?? '');
        $telefono     = trim($_POST['telefono']     ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $municipio    = trim($_POST['municipio']    ?? '');
        $tipoEntrega  = trim($_POST['tipo_entrega'] ?? '');
        $direccion    = trim($_POST['direccion']    ?? '');
        $notaEntrega  = trim($_POST['nota_entrega'] ?? '');

        $confirmPurchase = isset($_POST['confirm_purchase']) && $_POST['confirm_purchase'] == '1';

        // Arrays color/cantidad (estos son los que tu JS genera SIEMPRE)
        $colorItems = $_POST['color_item'] ?? [];
        $qtyItems   = $_POST['qty_item']   ?? [];
        if (!is_array($colorItems)) $colorItems = [];
        if (!is_array($qtyItems))   $qtyItems = [];

        $old = [
            'nombre'           => $nombre,
            'apellidos'        => $apellidos,
            'telefono'         => $telefono,
            'departamento'     => $departamento,
            'municipio'        => $municipio,
            'tipo_entrega'     => $tipoEntrega,
            'direccion'        => $direccion,
            'nota_entrega'     => $notaEntrega,
            'confirm_purchase' => $confirmPurchase ? 1 : 0,
            'color_item'       => $colorItems,
            'qty_item'         => $qtyItems,
            'pricing_mode'     => $pricingMode,
            'combo_enabled'    => $comboEnabled,
            'combo_price_2'    => $comboPrice2,
        ];

        // Validaciones base
        $errores = [];
        if ($nombre === '')       $errores[] = "El nombre es obligatorio.";
        if ($apellidos === '')    $errores[] = "Los apellidos son obligatorios.";

        // ── Teléfono: no vacío + formato colombiano (10 dígitos, empieza en 3) ──
        if ($telefono === '') {
            $errores[] = "El número de WhatsApp es obligatorio.";
        } elseif (!preg_match('/^3\d{9}$/', $telefono)) {
            $errores[] = "Ingresa un número de WhatsApp válido (10 dígitos, empieza en 3).";
        }

        if ($departamento === '') $errores[] = "Selecciona un departamento.";
        if ($municipio === '')    $errores[] = "Selecciona un municipio.";
        if ($tipoEntrega === '')  $errores[] = "Selecciona cómo quieres recibir tu pedido.";
        if ($tipoEntrega === 'domicilio' && $direccion === '') {
            $errores[] = "La dirección es obligatoria para envío a domicilio.";
        }
        // ── Guard anti-duplicados: mismo teléfono + mismo producto en últimos 15 min ──
        if (empty($errores)) {
            $pedidoModel = new Pedido();
            if ($pedidoModel->existePedidoReciente($telefono, $productoId, 15)) {
                $errores[] = "Ya registramos un pedido con este número hace menos de 15 minutos. Si tienes dudas, escríbenos por WhatsApp.";
            }
        }

        // Procesar colores/cantidades si el producto tiene colores
        $items = []; // color => cantidad
        if (!empty($coloresPermitidos)) {
            $max = max(count($colorItems), count($qtyItems));
            if ($max < 1) {
                $errores[] = "Debes seleccionar al menos un color.";
            } else {
                for ($i = 0; $i < $max; $i++) {
                    $c = trim((string)($colorItems[$i] ?? ''));
                    $q = (int)($qtyItems[$i] ?? 0);

                    if ($c === '' || $q <= 0) {
                        $errores[] = "Selecciona color y cantidad en todas las filas.";
                        break;
                    }

                    if (!in_array($c, $coloresPermitidos, true)) {
                        $errores[] = "Selecciona un color válido.";
                        break;
                    }

                    if ($q < 1 || $q > 5) {
                        $errores[] = "La cantidad por color debe estar entre 1 y 5.";
                        break;
                    }

                    $items[$c] = ($items[$c] ?? 0) + $q;
                }
            }
        }

        // Cantidad total
        if (!empty($items)) {
            $cantidadTotal = array_sum($items);
        } else {
            $cantidadTotal = (int)($_POST['cantidad_total'] ?? 1);
        }
        if ($cantidadTotal < 1) $cantidadTotal = 1;
        if ($cantidadTotal > 20) $cantidadTotal = 20;

        $old['cantidad_total'] = $cantidadTotal;

        // Resumen para pedidos.color (varchar 50)
        $colorResumen = '';
        if (!empty($items)) {
            $parts = [];
            foreach ($items as $c => $q) $parts[] = $c . " x" . (int)$q;
            $colorResumen = mb_substr(implode(', ', $parts), 0, 50);
        }

        if (!empty($errores)) {
            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'errores' => array_values($errores)]);
                exit;
            }
            $this->view('landing/index', [
                'producto' => $producto,
                'colores'  => $coloresPermitidos,
                'errores'  => $errores,
                'old'      => $old,
                'success'  => '',
                'config'   => $config,
            ]);
            return;
        }

        // ===== PRECIOS Y COSTOS =====
        $precioVenta     = (float)($producto['precio_venta'] ?? 0);
        $precioProveedor = (float)($producto['precio_proveedor'] ?? 0);
        $costoEnvio      = (float)($producto['costo_envio'] ?? 0);
        if ($costoEnvio < 0) $costoEnvio = 0;

        // ===== DESCUENTOS DINÁMICOS (desde producto) =====
        $d2  = (int)($producto['descuento_2da'] ?? 15);
        $d3  = (int)($producto['descuento_3ra'] ?? 20);
        $act = (int)($producto['descuento_multicantidad_activo'] ?? 1);

        // ===== Total a cobrar según modo =====
        $subtotal = $precioVenta * $cantidadTotal;

        if ($pricingMode === 'combo') {
            // Combo x2 (NO usa d2/d3)
            $precioTotal = $this->totalPorCombo2($cantidadTotal, $precioVenta, $comboPrice2);
        } else {
            // Individual (usa descuento multicantidad del producto)
            $precioTotal = $this->totalConDescuento($cantidadTotal, $precioVenta, $d2, $d3, $act);
        }

        $descuentoTotal = max(0, $subtotal - $precioTotal);

        // costos reales: proveedor * cantidad + envío (una sola vez)
        $costoTotal = ($precioProveedor * $cantidadTotal) + $costoEnvio;

        // utilidad unitaria (sin envío, solo referencia)
        $utilidadUnit = $precioVenta - $precioProveedor;

        // utilidad total real (incluye envío)
        $utilidadTotal = $precioTotal - $costoTotal;

        // Guardar pedido
        $pedidoModel = new Pedido();

        $pedidoData = [
            'producto_id'      => $productoId,
            'nombre'           => $nombre,
            'apellidos'        => $apellidos,
            'telefono'         => $telefono,
            'color'            => $colorResumen,
            'cantidad_total'   => $cantidadTotal,
            'departamento'     => $departamento,
            'municipio'        => $municipio,
            'tipo_entrega'     => $tipoEntrega,
            'direccion'        => ($tipoEntrega === 'domicilio') ? $direccion : null,
            'nota_entrega'     => $notaEntrega ?: null,

            // unitarios
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'utilidad'         => $utilidadUnit,

            // totales
            'descuento_total'  => $descuentoTotal,
            'precio_total'     => $precioTotal,
            'utilidad_total'   => $utilidadTotal,

            'estado'           => 'nuevo',
        ];

        $pedidoId = 0;
        if (method_exists($pedidoModel, 'crearConId')) {
            $pedidoId = (int)$pedidoModel->crearConId($pedidoData);
        } else {
            $pedidoModel->crear($pedidoData);
        }

        // Guardar detalle en pedido_colores
        if ($pedidoId > 0 && !empty($items)) {
            $pedidoColorModel = new PedidoColor();
            $pedidoColorModel->sync($pedidoId, $items);
        }

        // Notificación Telegram
        $this->notificarTelegram([
            'pedido_id'   => $pedidoId,
            'nombre'      => $nombre . ' ' . $apellidos,
            'telefono'    => $telefono,
            'municipio'   => $municipio,
            'departamento'=> $departamento,
            'color'       => $colorResumen,
            'cantidad'    => $cantidadTotal,
            'precio_total'=> $precioTotal,
            'tipo_entrega'=> $tipoEntrega,
            'direccion'   => $direccion,
            'nota_entrega'=> $notaEntrega,
            'producto'    => $producto['nombre'] ?? '',
        ]);

        if ($esAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'          => true,
                'pedido_id'   => $pedidoId,
                'precio_total'=> $precioTotal,
                'mensaje'     => 'Tu pedido se ha registrado correctamente. En breve un asesor te contactará por WhatsApp.',
            ]);
            exit;
        }

        $_SESSION['success'] = "Tu pedido se ha registrado correctamente.";
        header("Location: " . BASE_URL . "/Landing/index?producto_id=" . $productoId);
        exit;
    }

    private function notificarTelegram(array $d): void
    {
        $token  = getenv('TELEGRAM_BOT_TOKEN');
        $chatId = getenv('TELEGRAM_CHAT_ID');

        if (!$token || !$chatId) {
            error_log('[Telegram] Token o chat_id no encontrados en .env');
            return;
        }

        $entrega = $d['tipo_entrega'] === 'domicilio'
            ? "Domicilio: " . ($d['direccion'] ?: '—')
            : "Recoge en oficina";

        $precio = '$' . number_format((float)$d['precio_total'], 0, ',', '.');

        $lineas = [
            "🛒 *Nuevo Pedido #" . $d['pedido_id'] . "*",
            "",
            "👤 " . $d['nombre'],
            "📱 " . $d['telefono'],
            "📍 " . $d['municipio'] . ", " . $d['departamento'],
            "",
            "🛍️ " . $d['producto'],
            "🎨 " . ($d['color'] ?: "Sin color"),
            "📦 Unidades: " . $d['cantidad'],
            "💰 Total: " . $precio,
            "",
            "🚚 " . $entrega,
        ];
        if (!empty($d['nota_entrega'])) {
            $lineas[] = "📝 Nota: " . $d['nota_entrega'];
        }
        $texto = implode("\n", $lineas);

        $url  = "https://api.telegram.org/bot{$token}/sendMessage";
        $body = json_encode(['chat_id' => $chatId, 'text' => $texto, 'parse_mode' => 'Markdown']);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log('[Telegram] cURL error: ' . $err);
            } else {
                $decoded = json_decode($resp, true);
                if (empty($decoded['ok'])) {
                    error_log('[Telegram] API error: ' . $resp);
                }
            }
        } else {
            error_log('[Telegram] cURL no disponible en este servidor');
        }
    }

    public function verPorSlug($slug)
    {
        $slug = trim((string)$slug);
        if ($slug === '') {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorSlug($slug);

        if (!$producto) {
            header("HTTP/1.0 404 Not Found");
            echo "Producto no encontrado";
            exit;
        }

        $productoId = (int)$producto['id'];

        $configModel = new LandingConfig();
        $config      = $configModel->obtenerPorProducto($productoId) ?? [];

        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);

        $productoColorModel = new ProductoColor();
        $colores = $productoColorModel->obtenerActivosPorProducto($productoId);

        $pedidoModel      = new Pedido();
        $pedidosRecientes = $pedidoModel->contarPedidosRecientes($productoId, 30);

        $this->view('landing/index', [
            'producto'          => $producto,
            'colores'           => $colores,
            'config'            => $config,
            'success'           => $success,
            'errores'           => [],
            'old'               => [],
            'pedidos_recientes' => $pedidosRecientes,
        ]);
    }
}
