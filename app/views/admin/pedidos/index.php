<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS base -->
    <link rel="stylesheet" href="/tienda_mvc/public/css/dashboard.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- DataTables (lo dejas si más adelante lo usas) -->
    <!-- <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.min.css"> -->
</head>

<body>
    <?php
    $total_pedidos   = $total_pedidos   ?? 0;
    $total_utilidad  = $total_utilidad  ?? 0;
    $total_venta     = $total_venta     ?? 0;
    $pedidos_nuevos  = $pedidos_nuevos  ?? 0;
    $pedidos         = $pedidos         ?? [];
    $usuarioNombre   = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail    = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';
    ?>

    <!-- Overlay sidebar (mobile) -->
    <div class="sidebar-overlay" aria-hidden="true"></div>

    <div class="app-shell">

        <!-- Sidebar -->
        <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="material-main">
            <?php require __DIR__ . '/../partials/_header.php'; ?>

            <section class="material-content">

                <!-- =========================
                     DASHBOARD (Chart.js)
                     ========================= -->
                <section class="dash-grid" aria-label="Dashboard">
                    <article class="panel panel--kpi kpi-purple">
                        <div class="panel__head">
                            <h4>Pedidos últimos 14 días</h4>
                            <span class="chip">Actividad</span>
                        </div>
                        <div class="panel__body">
                            <canvas id="chartPedidos14" height="120"></canvas>
                        </div>
                    </article>

                    <article class="panel panel--kpi kpi-cyan">
                        <div class="panel__head">
                            <h4>Ventas últimos 14 días</h4>
                            <span class="chip">COP</span>
                        </div>
                        <div class="panel__body">
                            <canvas id="chartVentas14" height="120"></canvas>
                        </div>
                    </article>

                    <article class="panel panel--wide">
                        <div class="panel__head">
                            <h4>Distribución por estado</h4>
                            <span class="chip">Resumen</span>
                        </div>
                        <div class="panel__body panel__body--center">
                            <canvas id="chartEstados" height="160"></canvas>
                        </div>
                    </article>
                </section>

                <!-- =========================
                     TARJETAS DE RESUMEN
                     ========================= -->
                <div class="stats-grid">
                    <div class="stat-card glow-green">
                        <div class="stat-info">
                            <small>Pedidos Totales</small>
                            <h3 id="kpiPedidos"><?= number_format($total_pedidos, 0, ',', '.') ?></h3>
                            <span class="target">100% objetivo alcanzado</span>
                        </div>
                        <i class="fas fa-clipboard-list stat-icon"></i>
                    </div>

                    <div class="stat-card glow-red">
                        <div class="stat-info">
                            <small>Pedidos Nuevos</small>
                           <h3 id="kpiNuevos"><?= number_format($pedidos_nuevos, 0, ',', '.') ?></h3>
                            <span class="target pending">Pendientes de contacto</span>
                        </div>
                        <i class="fas fa-bell stat-icon"></i>
                    </div>

                    <div class="stat-card glow-purple">
                        <div class="stat-info">
                            <small>Ventas Totales</small>
                           <h3 id="kpiVentas">$<?= number_format((float)$total_venta, 0, ',', '.') ?></h3>
                            <span class="target">87% objetivo alcanzado</span>
                        </div>
                        <i class="fas fa-dollar-sign stat-icon"></i>
                    </div>

                    <div class="stat-card glow-blue">
                        <div class="stat-info">
                            <small>Utilidad Acumulada</small>
                           <h3 id="kpiUtilidad">$<?= number_format((float)$total_utilidad, 0, ',', '.') ?></h3>
                            <span class="target">Margen bruto excelente</span>
                        </div>
                        <i class="fas fa-chart-line stat-icon"></i>
                    </div>
                </div>

                <!-- =========================
                     PEDIDOS
                     ========================= -->
                <?php if (empty($pedidos)): ?>
                    <div class="empty-state">
                        <p>No hay pedidos registrados todavía.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Pedidos Recientes</h3>
                        </div>

                        <div class="cards-container" id="contenedorPedidos">
                            <?php foreach ($pedidos as $p): ?>
                                <?php
                                // --- SE MANTIENE TU LÓGICA DE PROCESAMIENTO ---
                                $telRaw    = $p['telefono'] ?? '';
                                $telLimpio = preg_replace('/\D+/', '', $telRaw);
                                $waUrl     = '';

                                if ($telLimpio !== '') {
                                    if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
                                    if (strpos($telLimpio, '57') !== 0) {
                                        if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
                                        $telLimpio = '57' . $telLimpio;
                                    }
                                    $waUrl = "https://wa.me/" . $telLimpio . "?text=" . urlencode(
                                        "Hola " . ($p['nombre'] ?? '') . ", te escribimos sobre tu pedido de " . ($p['producto_nombre'] ?? '') . "."
                                    );
                                }

                                $estadoActual = $p['estado'] ?? 'nuevo';
                                $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'entregado', 'cancelado'];
                                $cantidadTotal = (int)($p['cantidad_total'] ?? 1);
                                if ($cantidadTotal < 1) $cantidadTotal = 1;

                                $precioUnit = (float)($p['precio_venta'] ?? 0);
                                $precioProvUnit = (float)($p['precio_proveedor'] ?? 0);
                                $precioTotal = isset($p['precio_total']) ? (float)$p['precio_total'] : ($precioUnit * $cantidadTotal);

                                $costoEnvio = 0.0;
                                if (isset($p['costo_envio'])) {
                                    $costoEnvio = (float)$p['costo_envio'];
                                } elseif (isset($p['producto_costo_envio'])) {
                                    $costoEnvio = (float)$p['producto_costo_envio'];
                                }
                                $costoTotal = ($precioProvUnit * $cantidadTotal) + $costoEnvio;
                                $utilidadTotal = $precioTotal - $costoTotal;
                                if (!is_finite($utilidadTotal)) $utilidadTotal = 0.0;
                                ?>

                                <div class="order-card" data-pedido-id="<?= htmlspecialchars($p['id'] ?? '') ?>">

                                    <div class="card-header">
                                        <div>
                                            <span class="card-label">ID Pedido</span>
                                            <strong>#<?= htmlspecialchars($p['id'] ?? '') ?></strong>
                                        </div>
                                        <span class="status-tag status-<?= htmlspecialchars($estadoActual) ?>">
                                            <?= ucfirst(htmlspecialchars($estadoActual)) ?>
                                        </span>
                                    </div>

                                    <div class="card-section">
                                        <span class="card-label">Cliente y Ubicación</span>
                                        <div class="card-value">
                                            <strong><?= htmlspecialchars(($p['nombre'] ?? '') . ' ' . ($p['apellidos'] ?? '')) ?></strong><br>
                                            <small><?= htmlspecialchars($p['municipio'] ?? '') ?>, <?= htmlspecialchars($p['departamento'] ?? '') ?></small>
                                        </div>
                                    </div>

                                    <div class="card-section">
                                        <span class="card-label">Producto</span>
                                        <div class="card-value">
                                            <?= htmlspecialchars($p['producto_nombre'] ?? '') ?>
                                            (<?= (string)$cantidadTotal ?> <?= $cantidadTotal > 1 ? 'uds' : 'ud' ?>)
                                            <?php if (!empty($p['color'])): ?>
                                                • <small><?= htmlspecialchars($p['color']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card-section" style="display: flex; justify-content: space-between;">
                                        <div>
                                            <span class="card-label">Precio Total</span>
                                            <div class="card-value"><strong>$<?= number_format($precioTotal, 0, ',', '.') ?></strong></div>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="card-label">Utilidad</span>
                                            <div class="card-value profit-tag">$<?= number_format($utilidadTotal, 0, ',', '.') ?></div>
                                        </div>
                                    </div>

                                    <div class="card-footer">
                                        <form action="/tienda_mvc/AdminPedidos/cambiarEstado" method="POST" class="status-form">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                            <select name="estado" class="form-select-sm">
                                                <?php foreach ($estadosPosibles as $estado): ?>
                                                    <option value="<?= htmlspecialchars($estado) ?>" <?= $estadoActual === $estado ? 'selected' : '' ?>>
                                                        <?= ucfirst(htmlspecialchars($estado)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-save-status">Actualizar Estado</button>
                                        </form>

                                        <div class="card-actions">
                                            <?php if ($waUrl): ?>
                                                <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" class="btn-whatsapp">
                                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                                </a>
                                            <?php endif; ?>

                                            <a href="/tienda_mvc/AdminPedidos/detalle?id=<?= htmlspecialchars($p['id'] ?? '') ?>"
                                                class="btn-detail js-ver-detalle"
                                                data-id="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                                Detalles
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
            </section>
        </main>

        <!-- Modal Detalle Pedido -->
        <div id="pedidoDetalle" data-pedido-detalle>
            <div class="modal-overlay" id="pedidoModal" aria-hidden="true">
                <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="pedidoModalTitle">

                    <!-- ✅ Barra superior fija SOLO para la X -->
                    <div class="modal-topbar" aria-hidden="true">
                        <button class="modal-close" type="button" id="pedidoModalClose" aria-label="Cerrar">
                            &times;
                        </button>
                    </div>

                    <!-- ✅ Aquí SIEMPRE se reemplaza el contenido (partial / loading) -->
                    <div class="modal-body" id="pedidoModalBody">
                        <div class="modal-loading">
                            <div class="modal-spinner"></div>
                            <p>Cargando detalle...</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div><!-- /app-shell -->

    <!-- Pasar pedidos a JS (sin backend) -->
    <script>
        window.__PEDIDOS__ = <?= json_encode($pedidos, JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <!-- Librerías -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Tus scripts -->
    <script src="/tienda_mvc/public/js/funciones.js"></script>

</body>

</html>