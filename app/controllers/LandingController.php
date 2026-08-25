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
        return total_con_descuento($cantidad, $precioUnit, $d2, $d3, $activo);
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

    /**
     * session_start() manda Cache-Control: no-store por el cache_limiter
     * "nocache" (el de por defecto), y eso le impide al navegador restaurar
     * la página desde el bfcache al volver de WhatsApp o del hilo de
     * Facebook — la recarga entera y vuelve a disparar PageView/ViewContent.
     * Solo se pisa aquí, no globalmente: el resto del sitio (admin, auth)
     * conserva la protección por defecto contra el botón atrás.
     */
    private function permitirBfcache(): void
    {
        header_remove('Pragma');
        header_remove('Expires');
        header('Cache-Control: no-cache, must-revalidate');
    }

    public function index()
    {
        $this->permitirBfcache();

        $productoModel = new Producto();

        $productoId = (int)($_GET['producto_id'] ?? ($_GET['id'] ?? 0));
        $producto   = $productoId > 0 ? $productoModel->obtenerPorId($productoId) : null;

        // Sin producto indicado (o inexistente): llevar al primer producto activo
        // en lugar de asumir un id fijo, que puede no existir.
        if (!$producto) {
            $producto = $productoModel->obtenerPrimeroActivo();
            if (!$producto) {
                $this->notFound('Todavía no hay productos publicados.');
            }
            if (!empty($producto['slug'])) {
                header("Location: " . BASE_URL . "/producto/" . rawurlencode($producto['slug']), true, 302);
                exit;
            }
            $productoId = (int)$producto['id'];
        }

        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);
        $successPedido = $_SESSION['success_pedido'] ?? null;
        unset($_SESSION['success_pedido']);

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
            'success_pedido'    => $successPedido,
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

        // Atribución de Facebook (fbclid/_fbp/_fbc): los pone landing-track.js
        // vía JS, así que en el POST nativo sin JS simplemente llegan vacíos.
        // Recortados por si alguien postea directo al endpoint sin pasar por el form.
        $fbclid = mb_substr(trim($_POST['fbclid'] ?? ''), 0, 255);
        $fbp    = mb_substr(trim($_POST['fbp']    ?? ''), 0, 255);
        $fbc    = mb_substr(trim($_POST['fbc']    ?? ''), 0, 255);

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
            'color_item'       => $colorItems,
            'qty_item'         => $qtyItems,
            'pricing_mode'     => $pricingMode,
            'combo_enabled'    => $comboEnabled,
            'combo_price_2'    => $comboPrice2,
        ];

        /* Limites de las columnas de `pedidos`. Sin esto, un texto mas largo
           que la columna no era un error de validacion sino una PDOException
           sin capturar: 500 con el cuerpo vacio, el JSON del navegador
           reventaba y el comprador terminaba leyendo "Error de conexion,
           verifica tu internet" por un pedido que murio en la base de datos.
           Una direccion larga de verdad cabe de sobra en 255. */
        $limites = [
            'nombre'       => ['valor' => $nombre,       'max' => 100, 'msg' => 'El nombre es demasiado largo.'],
            'apellidos'    => ['valor' => $apellidos,    'max' => 100, 'msg' => 'Los apellidos son demasiado largos.'],
            'telefono'     => ['valor' => $telefono,     'max' => 30,  'msg' => 'El número de WhatsApp es demasiado largo.'],
            'departamento' => ['valor' => $departamento, 'max' => 100, 'msg' => 'El departamento no es válido.'],
            'municipio'    => ['valor' => $municipio,    'max' => 100, 'msg' => 'El municipio no es válido.'],
            'direccion'    => ['valor' => $direccion,    'max' => 255, 'msg' => 'La dirección es demasiado larga (máximo 255 caracteres).'],
            'nota_entrega' => ['valor' => $notaEntrega,  'max' => 500, 'msg' => 'La indicación para el mensajero es demasiado larga (máximo 500 caracteres).'],
        ];

        // Validaciones base
        $errores = [];
        if ($nombre === '')       $errores[] = "El nombre es obligatorio.";
        if ($apellidos === '')    $errores[] = "Los apellidos son obligatorios.";

        foreach ($limites as $campo) {
            if (mb_strlen($campo['valor']) > $campo['max']) $errores[] = $campo['msg'];
        }

        // ── Teléfono: no vacío + formato colombiano (10 dígitos, empieza en 3) ──
        if ($telefono === '') {
            $errores[] = "El número de WhatsApp es obligatorio.";
        } elseif (!preg_match('/^3\d{9}$/', $telefono)) {
            $errores[] = "Ingresa un número de WhatsApp válido (10 dígitos, empieza en 3).";
        }

        if ($departamento === '') $errores[] = "Selecciona un departamento.";
        if ($municipio === '')    $errores[] = "Selecciona un municipio.";

        /* tipo_entrega es un ENUM en la base. Antes solo se comprobaba que no
           llegara vacio, asi que cualquier otro valor pasaba la validacion y
           reventaba en el INSERT. La lista blanca hace de esto un error
           normal del formulario y no un 500. */
        if ($tipoEntrega === '') {
            $errores[] = "Selecciona cómo quieres recibir tu pedido.";
        } elseif (!in_array($tipoEntrega, ['domicilio', 'oficina'], true)) {
            $errores[] = "La forma de entrega seleccionada no es válida.";
        }

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
            /* La nota es para el mensajero: solo existe si hay a quien
               entregársela. Igual que direccion, se descarta en el servidor
               y no solo se oculta en el formulario, para que un POST directo
               no la cuele en un pedido de recogida en oficina. */
            'nota_entrega'     => ($tipoEntrega === 'domicilio' && $notaEntrega !== '') ? $notaEntrega : null,

            // unitarios
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'utilidad'         => $utilidadUnit,

            // totales
            'descuento_total'  => $descuentoTotal,
            'precio_total'     => $precioTotal,
            'utilidad_total'   => $utilidadTotal,

            'estado'           => 'nuevo',

            'fbclid'           => $fbclid,
            'fbp'              => $fbp,
            'fbc'              => $fbc,
        ];

        /* Red de seguridad del guardado. Las validaciones de arriba cubren lo
           que sabemos que puede venir mal del formulario, pero si algo mas
           falla en la base — la conexion, un cambio de esquema, un deadlock —
           no puede escaparse como excepcion: el 500 llega al navegador con el
           cuerpo vacio, response.json() revienta y el comprador lee un
           "revisa tu internet" que no tiene nada que ver con lo que paso.
           Aqui se registra el error de verdad y se responde con un mensaje
           honesto que ademas le deja la salida por WhatsApp. */
        $pedidoId = 0;
        try {
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
        } catch (Throwable $e) {
            error_log('[Pedido] No se pudo guardar: ' . $e->getMessage());

            $errorGuardado = 'No pudimos registrar tu pedido en este momento. '
                . 'Inténtalo de nuevo en un minuto o escríbenos por WhatsApp y lo tomamos nosotros.';

            if ($esAjax) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'errores' => [$errorGuardado]]);
                exit;
            }

            $this->view('landing/index', [
                'producto' => $producto,
                'colores'  => $coloresPermitidos,
                'errores'  => [$errorGuardado],
                'old'      => $old,
                'success'  => '',
                'config'   => $config,
            ]);
            return;
        }

        // Cerrar el embudo de analítica: el último paso se marca desde el
        // servidor, no desde el navegador, porque el JS puede no llegar a
        // ejecutarse tras el envío (redirección, pestaña cerrada, webview
        // de Facebook matando la página).
        if ($pedidoId > 0 && !empty($_POST['track_sid'])) {
            (new LandingAnalytics())->marcarPedido((string)$_POST['track_sid'], $pedidoId);
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

        // Conversions API — mismo eventID que el navegador (order-submit.js)
        // para que Facebook deduplique en vez de contar la venta dos veces.
        // pixel_id puede guardarse como '' (campo del admin en blanco) y no
        // como NULL, así que ?? no cubriría ese caso — igual que $val() en
        // la vista pública.
        $pixelIdCfg = trim((string)($config['pixel_id'] ?? ''));
        $this->enviarPurchaseCapi([
            'pedido_id'      => $pedidoId,
            'nombre'         => $nombre,
            'apellidos'      => $apellidos,
            'telefono'       => $telefono,
            'municipio'      => $municipio,
            'departamento'   => $departamento,
            'producto_id'    => $productoId,
            'precio_total'   => $precioTotal,
            'cantidad_total' => $cantidadTotal,
            'fbp'            => $fbp,
            'fbc'            => $fbc,
            'pixel_id'       => $pixelIdCfg !== '' ? $pixelIdCfg : fb_pixel_id(),
        ]);

        // Mismo criterio para TikTok — event_id compartido con ttq.track()
        // del navegador (index.php / order-submit.js) para deduplicar.
        $tiktokPixelIdCfg = trim((string)($config['tiktok_pixel_id'] ?? ''));
        $this->enviarPurchaseTiktokCapi([
            'pedido_id'       => $pedidoId,
            'telefono'        => $telefono,
            'producto_id'     => $productoId,
            'producto_nombre' => $producto['nombre'] ?? '',
            'precio_total'    => $precioTotal,
            'cantidad_total'  => $cantidadTotal,
            'pixel_id'        => $tiktokPixelIdCfg !== '' ? $tiktokPixelIdCfg : tiktok_pixel_id(),
        ]);

        if ($esAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok'          => true,
                'pedido_id'   => $pedidoId,
                'precio_total'=> $precioTotal,
                'cantidad_total' => $cantidadTotal,
                'mensaje'     => 'Tu pedido se ha registrado correctamente. En breve un asesor te contactará por WhatsApp.',
            ]);
            exit;
        }

        /* El ancla importa: la pantalla de confirmacion vive donde estaba el
           formulario, a pantalla y media de scroll. Sin #form-pedido el
           comprador aterriza arriba del todo y no ve que su pedido entro.
           Sin JS no hay fetch que dispare Lead/Purchase en el navegador, así
           que la vista los dispara ella misma leyendo success_pedido
           (ver window.landingSuccessPedido en la vista). */
        $_SESSION['success'] = "Tu pedido se ha registrado correctamente.";
        $_SESSION['success_pedido'] = [
            'pedido_id'      => $pedidoId,
            'precio_total'   => $precioTotal,
            'cantidad_total' => $cantidadTotal,
        ];
        $destino = !empty($producto['slug'])
            ? BASE_URL . '/producto/' . rawurlencode($producto['slug'])
            : BASE_URL . '/Landing/index?producto_id=' . $productoId;
        header("Location: " . $destino . "#form-pedido");
        exit;
    }

    /**
     * Envía el evento Purchase server-side a la Conversions API de Facebook,
     * con el mismo event_id que usa el pixel del navegador (order-submit.js:
     * 'pedido_' + id) para que Facebook deduplique en vez de contar dos
     * conversiones por la misma venta.
     *
     * Sin esto, cualquier Purchase que el navegador no llegue a disparar
     * (webview de Facebook matando la página, JS roto, ad blocker) es una
     * venta invisible para Facebook — que optimiza la pauta con lo que le
     * llega. Nunca debe romper ni retrasar la confirmación del pedido: si
     * falla algo aquí, el pedido ya está guardado y el comprador ya vio su
     * confirmación.
     *
     * Necesita un access token de la Conversions API del pixel (Events
     * Manager → pixel → Configuración → Conversions API → Generar token de
     * acceso). Ver AUDITORIA.md C3 para cómo activarlo.
     */
    private function enviarPurchaseCapi(array $d): void
    {
        if (es_entorno_local()) return; // igual que el pixel del navegador: no ensuciar datos reales

        $token = trim((string)(new AppSettings())->get('fb_capi_token', ''));
        if ($token === '') $token = trim((string)getenv('FB_CAPI_TOKEN')); // respaldo: tienda_config.php / .env
        if ($token === '') return; // no configurado todavía: no-op silencioso

        try {
            $cantidad = max(1, (int)($d['cantidad_total'] ?? 1));
            $valor    = (float)($d['precio_total'] ?? 0);

            $userData = ['country' => [hash('sha256', 'co')]];
            if (!empty($d['telefono']))     $userData['ph'] = [hash('sha256', '57' . preg_replace('/\D/', '', $d['telefono']))];
            if (!empty($d['nombre']))       $userData['fn'] = [hash('sha256', mb_strtolower(trim($d['nombre'])))];
            if (!empty($d['apellidos']))    $userData['ln'] = [hash('sha256', mb_strtolower(trim($d['apellidos'])))];
            if (!empty($d['municipio']))    $userData['ct'] = [hash('sha256', mb_strtolower(trim($d['municipio'])))];
            if (!empty($d['departamento'])) $userData['st'] = [hash('sha256', mb_strtolower(trim($d['departamento'])))];
            if (!empty($d['fbp']))          $userData['fbp'] = $d['fbp'];
            if (!empty($d['fbc']))          $userData['fbc'] = $d['fbc'];
            if (!empty($_SERVER['REMOTE_ADDR']))     $userData['client_ip_address'] = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['HTTP_USER_AGENT'])) $userData['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            $payload = [
                'data' => [[
                    'event_name'       => 'Purchase',
                    'event_time'       => time(),
                    'event_id'         => 'pedido_' . $d['pedido_id'],
                    'action_source'    => 'website',
                    'event_source_url' => (!empty($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . BASE_URL . '/producto/' . rawurlencode((string)($d['producto_id'] ?? '')),
                    'user_data'        => $userData,
                    'custom_data'      => [
                        'value'        => $valor,
                        'currency'     => 'COP',
                        'content_ids'  => [(string)($d['producto_id'] ?? '')],
                        'content_type' => 'product',
                        'num_items'    => $cantidad,
                        'contents'     => [[
                            'id'         => (string)($d['producto_id'] ?? ''),
                            'quantity'   => $cantidad,
                            'item_price' => $cantidad ? ($valor / $cantidad) : $valor,
                        ]],
                    ],
                ]],
            ];

            if (!function_exists('curl_init')) {
                error_log('[FB CAPI] cURL no disponible en este servidor');
                return;
            }

            $pixelId = trim((string)($d['pixel_id'] ?? '')) ?: fb_pixel_id();
            $url = 'https://graph.facebook.com/v19.0/' . $pixelId . '/events?access_token=' . urlencode($token);
            $ch  = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log('[FB CAPI] cURL error: ' . $err);
            } else {
                $decoded = json_decode($resp, true);
                if (empty($decoded['events_received'])) {
                    error_log('[FB CAPI] API error: ' . $resp);
                }
            }
        } catch (Throwable $e) {
            error_log('[FB CAPI] Excepción: ' . $e->getMessage());
        }
    }

    /**
     * Envía el evento CompletePayment server-side a la Events API de TikTok,
     * con el mismo event_id que usa el pixel del navegador ('pedido_' + id,
     * ver ttq.track('CompletePayment', ..., {event_id: ...}) en index.php y
     * order-submit.js) para que TikTok deduplique en vez de contar la venta
     * dos veces. Mismo criterio que enviarPurchaseCapi(): nunca debe romper
     * ni retrasar la confirmación del pedido.
     *
     * Necesita un access token de la Events API del pixel (TikTok Events
     * Manager → pixel → Configuración → API de eventos → Generar token).
     */
    private function enviarPurchaseTiktokCapi(array $d): void
    {
        if (es_entorno_local()) return; // igual que el pixel del navegador: no ensuciar datos reales

        $token = trim((string)(new AppSettings())->get('tiktok_capi_token', ''));
        if ($token === '') $token = trim((string)getenv('TIKTOK_CAPI_TOKEN')); // respaldo: tienda_config.php / .env
        if ($token === '') return; // no configurado todavía: no-op silencioso

        try {
            $cantidad = max(1, (int)($d['cantidad_total'] ?? 1));
            $valor    = (float)($d['precio_total'] ?? 0);

            $user = ['external_id' => [hash('sha256', (string)$d['pedido_id'])]];
            if (!empty($d['telefono'])) {
                $telE164 = '+57' . preg_replace('/\D/', '', $d['telefono']);
                $user['phone'] = [hash('sha256', $telE164)];
            }
            if (!empty($_SERVER['REMOTE_ADDR']))     $user['ip'] = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['HTTP_USER_AGENT'])) $user['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

            $pixelId = trim((string)($d['pixel_id'] ?? '')) ?: tiktok_pixel_id();

            $payload = [
                'event_source'    => 'web',
                'event_source_id' => $pixelId,
                'data' => [[
                    'event'      => 'CompletePayment',
                    'event_time' => time(),
                    'event_id'   => 'pedido_' . $d['pedido_id'],
                    'user'       => $user,
                    'properties' => [
                        'contents' => [[
                            'content_id'   => (string)($d['producto_id'] ?? ''),
                            'content_type' => 'product',
                            'content_name' => $d['producto_nombre'] ?? '',
                            'quantity'     => $cantidad,
                            'price'        => $cantidad ? ($valor / $cantidad) : $valor,
                        ]],
                        'currency' => 'COP',
                        'value'    => $valor,
                    ],
                    'page' => [
                        'url' => (!empty($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . BASE_URL . '/producto/' . rawurlencode((string)($d['producto_id'] ?? '')),
                    ],
                ]],
            ];

            if (!function_exists('curl_init')) {
                error_log('[TikTok CAPI] cURL no disponible en este servidor');
                return;
            }

            $ch = curl_init('https://business-api.tiktok.com/open_api/v1.3/event/track/');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Access-Token: ' . $token],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($err) {
                error_log('[TikTok CAPI] cURL error: ' . $err);
            } else {
                $decoded = json_decode($resp, true);
                if (!isset($decoded['code']) || (int)$decoded['code'] !== 0) {
                    error_log('[TikTok CAPI] API error: ' . $resp);
                }
            }
        } catch (Throwable $e) {
            error_log('[TikTok CAPI] Excepción: ' . $e->getMessage());
        }
    }

    private function notificarTelegram(array $d): void
    {
        $settings = new AppSettings();
        $token  = trim((string)$settings->get('telegram_bot_token', '')) ?: trim((string)getenv('TELEGRAM_BOT_TOKEN'));
        $chatId = trim((string)$settings->get('telegram_chat_id',  '')) ?: trim((string)getenv('TELEGRAM_CHAT_ID'));

        if (!$token || !$chatId) {
            error_log('[Telegram] Token o chat_id no configurados (panel ni .env)');
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
                /* La notificacion sale antes de responderle al comprador, asi
                   que su espera es este timeout. 5s es demasiado para algo que
                   no afecta al pedido: si Telegram no contesta en 2s, el aviso
                   se pierde y queda en el log, pero la compra no se frena. */
                CURLOPT_TIMEOUT        => 2,
                CURLOPT_CONNECTTIMEOUT => 2,
                /* Verificar el certificado: no hay razon para hablar con la API
                   de Telegram sin comprobar con quien se esta hablando. */
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
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
        $this->permitirBfcache();

        $slug = trim((string)$slug);
        if ($slug === '') {
            header("Location: " . BASE_URL . "/");
            exit;
        }

        $productoModel = new Producto();
        $producto      = $productoModel->obtenerPorSlug($slug);

        if (!$producto) {
            $this->notFound('El producto que buscas ya no está disponible.');
        }

        $productoId = (int)$producto['id'];

        $configModel = new LandingConfig();
        $config      = $configModel->obtenerPorProducto($productoId) ?? [];

        $success = $_SESSION['success'] ?? '';
        unset($_SESSION['success']);
        $successPedido = $_SESSION['success_pedido'] ?? null;
        unset($_SESSION['success_pedido']);

        $productoColorModel = new ProductoColor();
        $colores = $productoColorModel->obtenerActivosPorProducto($productoId);

        $pedidoModel      = new Pedido();
        $pedidosRecientes = $pedidoModel->contarPedidosRecientes($productoId, 30);

        $this->view('landing/index', [
            'producto'          => $producto,
            'colores'           => $colores,
            'config'            => $config,
            'success'           => $success,
            'success_pedido'    => $successPedido,
            'errores'           => [],
            'old'               => [],
            'pedidos_recientes' => $pedidosRecientes,
        ]);
    }

    /**
     * Endpoint de analítica: recibe los lotes de eventos de la landing.
     *
     * Lo llama public/js/landing-track.js con navigator.sendBeacon, que solo
     * dispara y olvida: no lee la respuesta ni reintenta. Por eso responde
     * siempre 204 y nunca un error — un fallo aquí no puede convertirse en
     * un error visible ni en un reintento del navegador.
     *
     * Sin CSRF a propósito: es un endpoint público de escritura acotada
     * (tipos en lista blanca, tope de eventos por sesión, campos recortados)
     * y el token de sesión no aporta nada contra el único abuso posible,
     * que es inflar las visitas.
     */
    public function track(): void
    {
        http_response_code(204);
        header('Content-Type: text/plain');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        // sendBeacon manda el cuerpo como Blob: no hay $_POST, se lee crudo.
        // El tope de 16 KB evita que un cliente roto llene la memoria.
        $crudo = file_get_contents('php://input', false, null, 0, 16384);
        if ($crudo === false || $crudo === '') return;

        $payload = json_decode($crudo, true);
        if (!is_array($payload)) return;

        $payload['env'] = es_entorno_local() ? 'local' : 'produccion';

        $analytics = new LandingAnalytics();
        $analytics->registrar($payload, (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

        // Limpieza oportunista del detalle antiguo: sin cron en el hosting,
        // una de cada 200 peticiones paga el coste de mantener la tabla a raya.
        if (random_int(1, 200) === 1) $analytics->purgarEventosViejos();
    }
}
