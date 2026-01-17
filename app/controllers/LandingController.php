<?php

class LandingController extends Controller
{
    /**
     * Total con descuento:
     * - 1 unidad: sin descuento
     * - 2da unidad: 15% OFF
     * - 3ra en adelante: 20% OFF
     */
    private function totalConDescuento(int $cantidad, float $precioUnit): float
    {
        if ($cantidad <= 0) return 0.0;
        if ($cantidad === 1) return $precioUnit;

        $total = 0.0;
        $total += $precioUnit;        // 1ra sin descuento
        $total += $precioUnit * 0.85; // 2da -15%
        if ($cantidad >= 3) {
            $total += $precioUnit * 0.80 * ($cantidad - 2); // 3ra+ -20%
        }
        return $total;
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

        // Colores
        $productoColorModel = new ProductoColor();
        $colores = $productoColorModel->obtenerActivosPorProducto((int)$producto['id']);

        $this->view('landing/index', [
            'producto' => $producto,
            'colores'  => $colores,
            'errores'  => [],
            'old'      => [],
            'success'  => $success,
            'config'   => $config,
        ]);
    }

    public function enviarPedido()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/Landing/index");
            exit;
        }

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

        // Colores permitidos
        $productoColorModel = new ProductoColor();
        $coloresPermitidos  = $productoColorModel->obtenerActivosPorProducto($productoId);

        // Campos base
        $nombre       = trim($_POST['nombre']       ?? '');
        $apellidos    = trim($_POST['apellidos']    ?? '');
        $telefono     = trim($_POST['telefono']     ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $municipio    = trim($_POST['municipio']    ?? '');
        $tipoEntrega  = trim($_POST['tipo_entrega'] ?? '');
        $direccion    = trim($_POST['direccion']    ?? '');

        // Confirmación checkbox
        $confirmPurchase = isset($_POST['confirm_purchase']) && $_POST['confirm_purchase'] == '1';

        // Arrays color/cantidad
        $colorItems = $_POST['color_item'] ?? [];
        $qtyItems   = $_POST['qty_item']   ?? [];
        if (!is_array($colorItems)) $colorItems = [];
        if (!is_array($qtyItems))   $qtyItems = [];

        // Old para re-render
        $old = [
            'nombre'           => $nombre,
            'apellidos'        => $apellidos,
            'telefono'         => $telefono,
            'departamento'     => $departamento,
            'municipio'        => $municipio,
            'tipo_entrega'     => $tipoEntrega,
            'direccion'        => $direccion,
            'confirm_purchase' => $confirmPurchase ? 1 : 0,
            'color_item'       => $colorItems,
            'qty_item'         => $qtyItems,
        ];

        // Validaciones base
        $errores = [];
        if ($nombre === '')       $errores[] = "El nombre es obligatorio.";
        if ($apellidos === '')    $errores[] = "Los apellidos son obligatorios.";
        if ($telefono === '')     $errores[] = "El número de WhatsApp es obligatorio.";
        if ($departamento === '') $errores[] = "Selecciona un departamento.";
        if ($municipio === '')    $errores[] = "Selecciona un municipio.";
        if ($tipoEntrega === '')  $errores[] = "Selecciona cómo quieres recibir tu pedido.";
        if ($tipoEntrega === 'domicilio' && $direccion === '') {
            $errores[] = "La dirección es obligatoria para envío a domicilio.";
        }
        if (!$confirmPurchase) {
            $errores[] = "Debes confirmar que quieres el producto y pagarás al recibirlo.";
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
        $precioVenta     = (float)($producto['precio_venta'] ?? 0);        // unitario
        $precioProveedor = (float)($producto['precio_proveedor'] ?? 0);    // unitario
        $costoEnvio      = (float)($producto['costo_envio'] ?? 0);         // por pedido (1 sola vez)
        if ($costoEnvio < 0) $costoEnvio = 0;

        $subtotal       = $precioVenta * $cantidadTotal;
        $precioTotal    = $this->totalConDescuento($cantidadTotal, $precioVenta);
        $descuentoTotal = max(0, $subtotal - $precioTotal);

        // costos reales: proveedor * cantidad + envío (una sola vez)
        $costoTotal    = ($precioProveedor * $cantidadTotal) + $costoEnvio;

        // utilidad unitaria (sin envío)
        $utilidadUnit  = $precioVenta - $precioProveedor;

        // utilidad total real
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

            // unitarios (para referencia)
            'precio_venta'     => $precioVenta,
            'precio_proveedor' => $precioProveedor,
            'utilidad'         => $utilidadUnit,

            // totales reales
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

        $_SESSION['success'] = "Tu pedido se ha registrado correctamente. En breve un asesor te contactará por WhatsApp.";
        header("Location: /tienda_mvc/Landing/index?producto_id=" . $productoId);
        exit;
    }

    public function verPorSlug($slug)
    {
        $slug = trim((string)$slug);
        if ($slug === '') {
            header("Location: /tienda_mvc/");
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

        $this->view('landing/index', [
            'producto' => $producto,
            'colores'  => $colores,
            'config'   => $config,
            'success'  => $success,
            'errores'  => [],
            'old'      => [],
        ]);
    }
}
