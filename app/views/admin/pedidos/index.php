<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
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
    $rango           = $rango           ?? 'mes';
    $tendencias      = $tendencias      ?? [];
    $plantillas_wa   = $plantillas_wa   ?? [];
    $usuarioNombre   = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail    = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';

    $renderTrend = function(array $t): string {
        if (!isset($t['dir']) || $t['dir'] === 'flat') {
            return '<span class="kpi-trend flat">—</span>';
        }
        $icon = $t['dir'] === 'up' ? '↑' : '↓';
        return '<span class="kpi-trend ' . htmlspecialchars($t['dir']) . '">'
            . $icon . ' ' . htmlspecialchars($t['label'] ?? '') . '</span>';
    };
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
                            <h4>Pedidos últimos 30 días</h4>
                            <span class="chip">Actividad</span>
                        </div>
                        <div class="panel__body">
                            <canvas id="chartPedidos14" height="120"></canvas>
                        </div>
                    </article>

                    <article class="panel panel--kpi kpi-cyan">
                        <div class="panel__head">
                            <h4>Ventas últimos 30 días</h4>
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
                            <span class="target"><?= ($renderTrend)($tendencias['pedidos'] ?? ['dir'=>'flat','label'=>'—']) ?> vs per. ant.</span>
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
                            <span class="target"><?= ($renderTrend)($tendencias['ventas'] ?? ['dir'=>'flat','label'=>'—']) ?> vs per. ant.</span>
                        </div>
                        <i class="fas fa-dollar-sign stat-icon"></i>
                    </div>

                    <div class="stat-card glow-blue">
                        <div class="stat-info">
                            <small>Utilidad Acumulada</small>
                            <h3 id="kpiUtilidad">$<?= number_format((float)$total_utilidad, 0, ',', '.') ?></h3>
                            <span class="target"><?= ($renderTrend)($tendencias['utilidad'] ?? ['dir'=>'flat','label'=>'—']) ?> vs per. ant.</span>
                        </div>
                        <i class="fas fa-chart-line stat-icon"></i>
                    </div>

                    <div class="stat-card glow-gold">
                        <div class="stat-info">
                            <small>Ticket Promedio</small>
                            <h3 id="kpiTicket">$<?= number_format($ticket_promedio ?? 0, 0, ',', '.') ?></h3>
                            <span class="target">Por pedido (excl. cancelados)</span>
                        </div>
                        <i class="fas fa-receipt stat-icon"></i>
                    </div>
                </div>

                <!-- =========================
                     PEDIDOS
                     ========================= -->
                <!-- Embudo de conversión -->
                <div class="funnel-wrap">
                    <div class="panel">
                        <div class="panel__head">
                            <h4>Embudo de conversión</h4>
                            <span class="chip">Pipeline</span>
                        </div>
                        <div class="panel__body">
                            <div id="funnelBars" class="funnel-bars">
                                <p style="color:var(--muted);text-align:center;font-size:13px;padding:4px 0;">Calculando...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (empty($pedidos)): ?>
                    <div class="empty-state">
                        <p>No hay pedidos registrados todavía.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Pedidos Recientes</h3>
                            <div class="table-header-actions">
                                <a href="<?= BASE_URL ?>/AdminPedidos/exportarCsv?rango=<?= htmlspecialchars($rango) ?>"
                                   class="btn-csv" title="Exportar a CSV">
                                    <i class="fas fa-file-csv"></i> CSV
                                </a>
                            </div>
                        </div>
                        <div class="state-filter" id="stateFilter">
                            <button class="state-chip is-active" data-estado="">Todos</button>
                            <button class="state-chip" data-estado="nuevo">Nuevo</button>
                            <button class="state-chip" data-estado="contactado">Contactado</button>
                            <button class="state-chip" data-estado="confirmado">Confirmado</button>
                            <button class="state-chip" data-estado="enviado">Enviado</button>
                            <button class="state-chip" data-estado="en_oficina">En oficina</button>
                            <button class="state-chip" data-estado="entregado">Entregado</button>
                            <button class="state-chip" data-estado="cancelado">Cancelado</button>
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
                                $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];
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

                                <?php
                                $tsRef = strtotime($p['updated_at'] ?? '') ?: strtotime($p['created_at'] ?? '');
                                $timeBadge = '';
                                if ($tsRef) {
                                    $diff = time() - $tsRef;
                                    if ($diff < 60)        $timeBadge = '<1 min';
                                    elseif ($diff < 3600)  $timeBadge = round($diff/60)    . ' min';
                                    elseif ($diff < 86400) $timeBadge = round($diff/3600)  . 'h';
                                    else                   $timeBadge = round($diff/86400) . 'd';
                                }
                                ?>
                                <div class="order-card"
                                     data-pedido-id="<?= htmlspecialchars($p['id'] ?? '') ?>"
                                     data-estado="<?= htmlspecialchars($estadoActual) ?>"
                                     data-ts="<?= htmlspecialchars($p['updated_at'] ?? ($p['created_at'] ?? '')) ?>">

                                    <div class="card-header">
                                        <div>
                                            <span class="card-label">ID Pedido</span>
                                            <strong>#<?= htmlspecialchars($p['id'] ?? '') ?></strong>
                                        </div>
                                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
                                            <span class="status-tag status-<?= htmlspecialchars($estadoActual) ?>">
                                                <?= ucfirst(htmlspecialchars($estadoActual)) ?>
                                            </span>
                                            <?php if ($timeBadge): ?>
                                                <span class="time-badge"><i class="fas fa-clock"></i> <?= htmlspecialchars($timeBadge) ?></span>
                                            <?php endif; ?>
                                        </div>
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
                                        <form action="<?= BASE_URL ?>/AdminPedidos/cambiarEstado" method="POST" class="status-form">
                                            <?= csrf_field() ?>
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
                                            <?php if ($telLimpio !== ''): ?>
                                                <button type="button" class="btn-whatsapp js-wa-open"
                                                        data-telefono="<?= htmlspecialchars($telRaw) ?>"
                                                        data-nombre="<?= htmlspecialchars($p['nombre'] ?? '') ?>"
                                                        data-apellidos="<?= htmlspecialchars($p['apellidos'] ?? '') ?>"
                                                        data-producto="<?= htmlspecialchars($p['producto_nombre'] ?? '') ?>"
                                                        data-cantidad="<?= htmlspecialchars((string)$cantidadTotal) ?>"
                                                        data-precio="<?= htmlspecialchars('$' . number_format($precioTotal, 0, ',', '.')) ?>"
                                                        data-municipio="<?= htmlspecialchars($p['municipio'] ?? '') ?>"
                                                        data-departamento="<?= htmlspecialchars($p['departamento'] ?? '') ?>"
                                                        data-estado="<?= htmlspecialchars($estadoActual) ?>"
                                                        data-tipo-entrega="<?= htmlspecialchars($p['tipo_entrega'] ?? '') ?>">
                                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                                </button>
                                            <?php endif; ?>

                                            <a href="<?= BASE_URL ?>/AdminPedidos/detalle?id=<?= htmlspecialchars($p['id'] ?? '') ?>"
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

    <!-- Datos para JS -->
    <script>
        window.__PEDIDOS__     = <?= json_encode($pedidos,      JSON_UNESCAPED_UNICODE) ?>;
        window.__PLANTILLAS__  = <?= json_encode($plantillas_wa, JSON_UNESCAPED_UNICODE) ?>;
    </script>

    <!-- Librerías -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Tus scripts -->
    <script src="<?= BASE_URL ?>/public/js/funciones.js"></script>

</body>

</html>