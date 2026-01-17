<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del pedido</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/tienda_mvc/public/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<?php
$pedido = $pedido ?? null;

$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
$usuarioEmail  = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';

if (!$pedido) {
    echo "<p style='padding:2rem;color:#fff;'>No se encontró el pedido.</p>";
    exit;
}

// Estados
$estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'entregado', 'cancelado'];
$estadoActual = $pedido['estado'] ?? 'nuevo';
$estadoSafe = in_array($estadoActual, $estadosPosibles, true) ? $estadoActual : 'nuevo';

// WhatsApp URL
$telRaw    = $pedido['telefono'] ?? '';
$telLimpio = preg_replace('/\D+/', '', $telRaw);
$waUrl     = '';

if ($telLimpio !== '') {
    if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
    if (strpos($telLimpio, '57') !== 0) {
        if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
        $telLimpio = '57' . $telLimpio;
    }
    $waUrl = "https://wa.me/" . $telLimpio . "?text=" . urlencode(
        "Hola " . ($pedido['nombre'] ?? '') . ", te escribimos sobre tu pedido de " . ($pedido['producto_nombre'] ?? '') . "."
    );
}

// ==== Valores numéricos seguros (compatibles con tu BD actual) ====
$cantidadTotal = (int)($pedido['cantidad_total'] ?? 1);
if ($cantidadTotal < 1) $cantidadTotal = 1;

$precioUnit      = (float)($pedido['precio_venta'] ?? 0);        // precio unitario
$proveedorUnit   = (float)($pedido['precio_proveedor'] ?? 0);    // costo unitario proveedor
$utilidadUnit    = (float)($pedido['utilidad'] ?? ($precioUnit - $proveedorUnit));

$subtotal        = $precioUnit * $cantidadTotal;

$descuentoTotal  = (float)($pedido['descuento_total'] ?? 0);

$precioTotal     = (float)($pedido['precio_total'] ?? 0);
if ($precioTotal <= 0) $precioTotal = max(0, $subtotal - $descuentoTotal);

// costo envío interno (fallback si no existe columna)
$costoEnvio = (float)($pedido['costo_envio'] ?? ($pedido['costo_envio_total'] ?? 0));
if ($costoEnvio < 0) $costoEnvio = 0;

$costoProveedorTotal = $proveedorUnit * $cantidadTotal;

// utilidad_total (fallback si no existe / viene en 0)
$utilidadTotal = (float)($pedido['utilidad_total'] ?? 0);
if ($utilidadTotal == 0.0) {
    // Nota: solo lo calculamos como fallback visual
    $utilidadTotal = $precioTotal - $costoProveedorTotal - $costoEnvio;
}

$municipio        = $pedido['municipio'] ?? '';
$departamento     = $pedido['departamento'] ?? '';
$tipoEntrega      = $pedido['tipo_entrega'] ?? '';
$direccion        = $pedido['direccion'] ?? '';
$color            = $pedido['color'] ?? '';
?>

<!-- Sidebar -->
<aside class="material-sidebar">
    <div class="sidebar-logo">
        <h2>FEDORA ULTIMATE</h2>
    </div>

    <div class="sidebar-user">
        <img src="/tienda_mvc/public/img/admi/1.jpg?user=<?= substr($usuarioNombre, 0, 2) ?>" alt="User">
        <div>
            <h4><?= htmlspecialchars($usuarioNombre) ?></h4>
            <small><?= htmlspecialchars($usuarioEmail) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="/tienda_mvc/AdminPedidos/index" class="active">
            <i class="fas fa-box"></i> Pedidos
        </a>
        <a href="/tienda_mvc/AdminProductos/index">
            <i class="fas fa-shopping-bag"></i> Productos
        </a>
        <a href="/tienda_mvc/Auth/logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </nav>
</aside>

<!-- Main -->
<main class="material-main">

    <!-- Header fijo -->
    <header class="material-header">
        <div class="header-greeting">
            <h3>Pedido #<?= htmlspecialchars($pedido['id'] ?? '') ?></h3>
            <p>Creado el <?= htmlspecialchars($pedido['created_at'] ?? '') ?></p>
        </div>

        <div class="header-actions">
            <button class="btn-menu" id="btnMenu" aria-label="Abrir menú">
                <i class="fas fa-bars"></i>
            </button>

            <a href="/tienda_mvc/AdminPedidos/index" class="btn-detail">← Volver</a>

            <?php if ($waUrl): ?>
                <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener" class="btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card glow-red">
            <div class="stat-info">
                <small>Estado</small>
                <h3><?= ucfirst(htmlspecialchars($estadoSafe)) ?></h3>
                <span class="target pending">Gestiona el estado</span>
            </div>
            <i class="fas fa-flag stat-icon"></i>
        </div>

        <div class="stat-card glow-purple">
            <div class="stat-info">
                <small>Total a cobrar</small>
                <h3>$<?= number_format($precioTotal, 0, ',', '.') ?></h3>
                <span class="target">Incluye descuento por cantidad</span>
            </div>
            <i class="fas fa-dollar-sign stat-icon"></i>
        </div>

        <div class="stat-card glow-blue">
            <div class="stat-info">
                <small>Utilidad total</small>
                <h3>$<?= number_format($utilidadTotal, 0, ',', '.') ?></h3>
                <span class="target">Ya contempla costos</span>
            </div>
            <i class="fas fa-chart-line stat-icon"></i>
        </div>

        <div class="stat-card glow-green">
            <div class="stat-info">
                <small>Ubicación</small>
                <h3><?= htmlspecialchars($municipio ?: '-') ?></h3>
                <span class="target"><?= htmlspecialchars($departamento ?: '-') ?></span>
            </div>
            <i class="fas fa-map-marker-alt stat-icon"></i>
        </div>
    </div>

    <section class="material-content">

        <!-- Estado / Producto -->
        <div class="table-container">
            <div class="table-header">
                <h3>Estado y producto</h3>
            </div>

            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Estado actual</span>
                    <span class="status-tag status-<?= htmlspecialchars($estadoSafe) ?>">
                        <?= ucfirst(htmlspecialchars($estadoSafe)) ?>
                    </span>

                    <form action="/tienda_mvc/AdminPedidos/cambiarEstado" method="POST" class="status-form" style="margin-top:.75rem;">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($pedido['id'] ?? '') ?>">
                        <select name="estado">
                            <?php foreach ($estadosPosibles as $estado): ?>
                                <option value="<?= htmlspecialchars($estado) ?>" <?= $estadoSafe === $estado ? 'selected' : '' ?>>
                                    <?= ucfirst(htmlspecialchars($estado)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Guardar</button>
                    </form>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Producto</span>
                    <span class="detalle-value"><?= htmlspecialchars($pedido['producto_nombre'] ?? '-') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Cantidad total</span>
                    <span class="detalle-value"><?= number_format($cantidadTotal, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Color</span>
                    <span class="detalle-value"><?= htmlspecialchars($color ?: '-') ?></span>
                </div>
            </div>
        </div>

        <!-- Totales del pedido -->
        <div class="table-container" style="margin-top:1.5rem;">
            <div class="table-header">
                <h3>Totales del pedido</h3>
            </div>

            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Precio unitario</span>
                    <span class="detalle-value">$<?= number_format($precioUnit, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Subtotal (sin descuento)</span>
                    <span class="detalle-value">$<?= number_format($subtotal, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Descuento total</span>
                    <span class="detalle-value">$<?= number_format($descuentoTotal, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Total a cobrar</span>
                    <span class="detalle-value"><strong>$<?= number_format($precioTotal, 0, ',', '.') ?></strong></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Costo proveedor (unitario)</span>
                    <span class="detalle-value">$<?= number_format($proveedorUnit, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Costo proveedor (total)</span>
                    <span class="detalle-value">$<?= number_format($costoProveedorTotal, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Costo envío interno</span>
                    <span class="detalle-value">$<?= number_format($costoEnvio, 0, ',', '.') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Utilidad total</span>
                    <span class="detalle-value"><strong>$<?= number_format($utilidadTotal, 0, ',', '.') ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Cliente -->
        <div class="table-container" style="margin-top:1.5rem;">
            <div class="table-header">
                <h3>Datos del cliente</h3>
            </div>

            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Nombre completo</span>
                    <span class="detalle-value">
                        <?= htmlspecialchars(($pedido['nombre'] ?? '') . ' ' . ($pedido['apellidos'] ?? '')) ?>
                    </span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">WhatsApp</span>
                    <span class="detalle-value"><?= htmlspecialchars($pedido['telefono'] ?? '-') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">ID pedido</span>
                    <span class="detalle-value"><?= htmlspecialchars($pedido['id'] ?? '-') ?></span>
                </div>
            </div>

            <?php if ($waUrl): ?>
                <div class="detalle-acciones">
                    <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener" class="btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> Contactar por WhatsApp
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Envío -->
        <div class="table-container" style="margin-top:1.5rem;">
            <div class="table-header">
                <h3>Envío y ubicación</h3>
            </div>

            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Departamento</span>
                    <span class="detalle-value"><?= htmlspecialchars($departamento ?: '-') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Municipio</span>
                    <span class="detalle-value"><?= htmlspecialchars($municipio ?: '-') ?></span>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Tipo de entrega</span>
                    <span class="detalle-value"><?= htmlspecialchars($tipoEntrega ? ucfirst($tipoEntrega) : '-') ?></span>
                </div>

                <?php if (!empty($direccion)): ?>
                    <div class="detalle-item detalle-item-full">
                        <span class="detalle-label">Dirección</span>
                        <span class="detalle-value"><?= htmlspecialchars($direccion) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </section>
</main>

<script src="/tienda_mvc/public/js/funciones.js"></script>
</body>
</html>
