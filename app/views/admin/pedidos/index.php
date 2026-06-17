<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.min.css">
</head>

<body>
    <?php
    $total_pedidos   = $total_pedidos   ?? 0;
    $total_utilidad  = $total_utilidad  ?? 0;
    $total_venta     = $total_venta     ?? 0;
    $pedidos_nuevos  = $pedidos_nuevos  ?? 0;
    $pedidos         = $pedidos         ?? [];
    $rango           = $rango           ?? 'mes';
    $desde           = $desde           ?? null;
    $hasta           = $hasta           ?? null;
    $tendencias      = $tendencias      ?? [];
    $plantillas_wa   = $plantillas_wa   ?? [];
    $usuarioNombre   = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail    = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';
    $showRangeFilter = false; // usamos los botones Hoy/Ayer/Semana/Mes en la tabla
    $showSearch      = false; // buscador está en la sección de tabla, más cerca del contenido

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

                <div class="table-container">
                        <div class="table-header">
                            <h3>Pedidos Recientes</h3>
                            <a href="<?= BASE_URL ?>/AdminPedidos/exportarCsv?rango=<?= htmlspecialchars($rango) ?><?= $rango === 'personalizado' && $desde && $hasta ? '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta) : '' ?>"
                               class="btn-csv" title="Exportar a CSV">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                        </div>

                        <?php
                        $rangoLabels = ['hoy'=>'Hoy','ayer'=>'Ayer','semana'=>'Esta semana','mes'=>'Este mes','personalizado'=>'Rango personalizado'];
                        $rangoLabel  = $rangoLabels[$rango] ?? 'Fecha';
                        $rangoActivo = ($rango !== 'mes');
                        ?>
                        <!-- Barra de filtros unificada -->
                        <div class="dt-filter-bar">

                            <!-- Filtro: Fecha (details/summary = toggle nativo) -->
                            <details class="dtf-wrap <?= $rangoActivo ? 'dtf-wrap--active' : '' ?>" id="fechaWrap">
                                <summary class="dtf-btn">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span class="dtf-btn__label"><?= htmlspecialchars($rangoLabel) ?></span>
                                    <i class="fas fa-chevron-down dtf-btn__chev"></i>
                                </summary>
                                <div class="dtf-panel">
                                    <button type="button" class="dfd-item <?= $rango==='hoy'    ?'is-active':'' ?>" data-rango="hoy"    onclick="cambiarRango('hoy')"><i class="fas fa-sun"></i> Hoy</button>
                                    <button type="button" class="dfd-item <?= $rango==='ayer'   ?'is-active':'' ?>" data-rango="ayer"   onclick="cambiarRango('ayer')"><i class="fas fa-history"></i> Ayer</button>
                                    <button type="button" class="dfd-item <?= $rango==='semana' ?'is-active':'' ?>" data-rango="semana" onclick="cambiarRango('semana')"><i class="fas fa-calendar-week"></i> Esta semana</button>
                                    <button type="button" class="dfd-item <?= $rango==='mes'    ?'is-active':'' ?>" data-rango="mes"    onclick="cambiarRango('mes')"><i class="fas fa-calendar-alt"></i> Este mes</button>
                                    <div class="dfd-divider"></div>
                                    <div class="dfd-custom">
                                        <span class="dfd-custom__label"><i class="fas fa-sliders-h"></i> Rango personalizado</span>
                                        <form class="dfd-custom__form" onsubmit="cambiarRangoPersonalizado(this); return false;">
                                            <input type="date" id="fechaDesde" value="<?= htmlspecialchars($desde ?? '') ?>" max="<?= date('Y-m-d') ?>">
                                            <input type="date" id="fechaHasta" value="<?= htmlspecialchars($hasta ?? '') ?>" max="<?= date('Y-m-d') ?>">
                                            <button type="submit">Aplicar</button>
                                        </form>
                                    </div>
                                </div>
                            </details>

                            <!-- Filtro: Estado -->
                            <details class="dtf-wrap" id="estadoWrap">
                                <summary class="dtf-btn">
                                    <i class="fas fa-tag"></i>
                                    <span class="dtf-btn__label" id="estadoBtnLabel">Estado</span>
                                    <i class="fas fa-chevron-down dtf-btn__chev"></i>
                                </summary>
                                <div class="dtf-panel" id="estadoPanel">
                                    <div id="stateFilterContent">
                                        <button class="state-chip is-active" data-estado=""           onclick="filtrarEstado(this)">Todos</button>
                                        <button class="state-chip" data-estado="nuevo"                onclick="filtrarEstado(this)">Nuevo</button>
                                        <button class="state-chip" data-estado="contactado"           onclick="filtrarEstado(this)">Contactado</button>
                                        <button class="state-chip" data-estado="confirmado"           onclick="filtrarEstado(this)">Confirmado</button>
                                        <button class="state-chip" data-estado="enviado"              onclick="filtrarEstado(this)">Enviado</button>
                                        <button class="state-chip" data-estado="en_oficina"           onclick="filtrarEstado(this)">En oficina</button>
                                        <button class="state-chip" data-estado="entregado"            onclick="filtrarEstado(this)">Entregado</button>
                                        <button class="state-chip" data-estado="cancelado"            onclick="filtrarEstado(this)">Cancelado</button>
                                    </div>
                                </div>
                            </details>

                            <!-- Búsqueda -->
                            <div class="dt-search-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" id="dtSearchInput" placeholder="Buscar nombre, ciudad, producto…">
                            </div>

                        </div><!-- /dt-filter-bar -->

                        <div id="contenedorPedidos" class="dt-table-wrap">
                        <?php
                        $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];
                        $_meses   = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
                        $_tzBogota = new DateTimeZone('America/Bogota');
                        ?>
                        <table id="tablaPedidos" class="pedidos-dt">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Producto</th>
                                    <th>Estado</th>
                                    <th>Cambiar estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pedidos as $p): ?>
                            <?php
                                $telRaw    = $p['telefono'] ?? '';
                                $telLimpio = preg_replace('/\D+/', '', $telRaw);
                                if ($telLimpio !== '') {
                                    if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
                                    if (strpos($telLimpio, '57') !== 0) {
                                        if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
                                        $telLimpio = '57' . $telLimpio;
                                    }
                                }
                                $estadoActual  = $p['estado'] ?? 'nuevo';
                                $cantidadTotal = max(1, (int)($p['cantidad_total'] ?? 1));
                                $precioUnit    = (float)($p['precio_venta'] ?? 0);
                                $precioProvUnit= (float)($p['precio_proveedor'] ?? 0);
                                $precioTotal   = isset($p['precio_total']) ? (float)$p['precio_total'] : ($precioUnit * $cantidadTotal);
                                $costoEnvio    = (float)($p['costo_envio'] ?? $p['producto_costo_envio'] ?? 0);
                                $costoTotal    = ($precioProvUnit * $cantidadTotal) + $costoEnvio;
                                $utilidadTotal = is_finite($precioTotal - $costoTotal) ? $precioTotal - $costoTotal : 0.0;
                                $tsCreado      = !empty($p['created_at']) ? strtotime($p['created_at']) : 0;
                                $createdFmt    = '';
                                if ($tsCreado) {
                                    $_dtC = (new DateTime('@' . $tsCreado))->setTimezone($_tzBogota);
                                    $createdFmt = $_dtC->format('j') . ' ' . $_meses[(int)$_dtC->format('n') - 1] . ' ' . $_dtC->format('Y');
                                }
                            ?>
                                <tr data-pedido-id="<?= htmlspecialchars($p['id'] ?? '') ?>"
                                    data-estado="<?= htmlspecialchars($estadoActual) ?>"
                                    data-ts="<?= htmlspecialchars($p['updated_at'] ?? ($p['created_at'] ?? '')) ?>"
                                    data-created-ts="<?= $tsCreado ?>">

                                    <td data-order="<?= $tsCreado ?>">
                                        <?= $createdFmt ?: '—' ?>
                                    </td>

                                    <td>
                                        <strong><?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellidos'] ?? ''))) ?></strong>
                                        <br><small class="dt-cell-sub"><?= htmlspecialchars($p['municipio'] ?? '') ?><?= !empty($p['departamento']) ? ', ' . htmlspecialchars($p['departamento']) : '' ?></small>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($p['producto_nombre'] ?? '') ?>
                                        <small class="dt-cell-sub">(<?= $cantidadTotal ?> <?= $cantidadTotal > 1 ? 'uds' : 'ud' ?><?= !empty($p['color']) ? ' · ' . htmlspecialchars($p['color']) : '' ?>)</small>
                                    </td>

                                    <td>
                                        <span class="status-tag status-<?= htmlspecialchars($estadoActual) ?>">
                                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($estadoActual))) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <form action="<?= BASE_URL ?>/AdminPedidos/cambiarEstado" method="POST" class="status-form dt-status-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                            <select name="estado" class="form-select-sm">
                                                <?php foreach ($estadosPosibles as $est): ?>
                                                    <option value="<?= htmlspecialchars($est) ?>" <?= $estadoActual === $est ? 'selected' : '' ?>>
                                                        <?= ucfirst(str_replace('_', ' ', htmlspecialchars($est))) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-save-status btn-save-status--sm" title="Guardar estado"><i class="fas fa-check"></i></button>
                                        </form>
                                    </td>

                                    <td>
                                        <div class="dt-actions">
                                            <?php if ($telLimpio !== ''): ?>
                                                <button type="button" class="btn-icon-wa js-wa-open"
                                                        title="WhatsApp"
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
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= BASE_URL ?>/AdminPedidos/detalle?id=<?= htmlspecialchars($p['id'] ?? '') ?>"
                                               class="btn-detail btn-detail--sm js-ver-detalle"
                                               data-id="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                                <i class="fas fa-magnifying-glass"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div><!-- /contenedorPedidos -->
                    </div><!-- /table-container -->
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
        window.__PEDIDOS__     = <?= json_encode(array_map(fn($p) => [
            'id'                   => $p['id']                   ?? null,
            'estado'               => $p['estado']               ?? null,
            'created_at'           => $p['created_at']           ?? null,
            'updated_at'           => $p['updated_at']           ?? null,
            'precio_total'         => $p['precio_total']         ?? null,
            'precio_venta'         => $p['precio_venta']         ?? null,
            'precio_proveedor'     => $p['precio_proveedor']     ?? null,
            'producto_costo_envio' => $p['producto_costo_envio'] ?? null,
            'cantidad_total'       => $p['cantidad_total']       ?? null,
            'telefono'             => $p['telefono']             ?? null,
        ], $pedidos), JSON_UNESCAPED_UNICODE) ?>;
        window.__PLANTILLAS__  = <?= json_encode($plantillas_wa, JSON_UNESCAPED_UNICODE) ?>;
        window.__RANGE_INIT__  = <?= json_encode(['hoy'=>'today','ayer'=>'yesterday','semana'=>'week','mes'=>'month','personalizado'=>'custom'][$rango] ?? 'month') ?>;
        window.__RANGE_CUSTOM__ = <?= ($rango === 'personalizado' && $desde && $hasta)
            ? json_encode(['desde' => $desde, 'hasta' => $hasta])
            : 'null' ?>;
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- jQuery (requerido por DataTables CDN) -->
    <script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.min.js"></script>
    <script>
    // ── Declaraciones hoistadas: disponibles aunque el IIFE de abajo falle ──

    // Mapeo PHP-rango → JS-range
    var __RANGO_MAP__ = {hoy:'today', ayer:'yesterday', semana:'week', mes:'month', personalizado:'custom'};
    var __RANGO_LABELS__ = {hoy:'Hoy', ayer:'Ayer', semana:'Esta semana', mes:'Este mes', personalizado:'Rango personalizado'};

    function cambiarRango(rango, desde, hasta) {
        if (rango === 'personalizado') {
            window.__RANGE_CUSTOM__ = (desde && hasta) ? {desde: desde, hasta: hasta} : window.__RANGE_CUSTOM__;
        } else {
            window.__RANGE_CUSTOM__ = null;
        }

        // Actualizar botones activos
        document.querySelectorAll('.dfd-item[data-rango]').forEach(function(btn) {
            btn.classList.toggle('is-active', btn.dataset.rango === rango);
        });

        // Actualizar label del summary
        var lbl = document.querySelector('#fechaWrap summary .dtf-btn__label');
        if (lbl) lbl.textContent = __RANGO_LABELS__[rango] || rango;

        // Activar indicador visual en el botón
        var wrap = document.getElementById('fechaWrap');
        if (wrap) {
            wrap.classList.toggle('dtf-wrap--active', rango !== 'mes');
            wrap.open = false;
        }

        // Disparar el evento que funciones.js ya escucha (actualiza charts, KPIs, DT)
        var rangoJs = __RANGO_MAP__[rango] || 'month';
        window.__RANGE_SELECTED = rangoJs;
        window.dispatchEvent(new CustomEvent('range:change', {detail: {range: rangoJs}}));
    }

    function cambiarRangoPersonalizado(form) {
        var desde = document.getElementById('fechaDesde').value;
        var hasta = document.getElementById('fechaHasta').value;
        if (!desde || !hasta || desde > hasta) {
            document.getElementById('fechaHasta').focus();
            return;
        }
        cambiarRango('personalizado', desde, hasta);
    }

    function actualizarLabelEstado() {
        var chip = document.querySelector('#stateFilterContent .state-chip.is-active');
        var lbl  = document.getElementById('estadoBtnLabel');
        if (!chip || !lbl) return;
        var isAll = chip.dataset.estado === '';
        var label = chip.textContent.trim();
        if (!isAll && window.__dtTable) {
            var count = window.__dtTable.rows({ filter: 'applied' }).count();
            label += ' (' + count + ')';
        }
        lbl.textContent = label;
    }

    function filtrarEstado(chip) {
        document.querySelectorAll('#stateFilterContent .state-chip').forEach(function(c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');
        var isAll = chip.dataset.estado === '';
        document.getElementById('estadoWrap').classList.toggle('dtf-wrap--active', !isAll);
        document.getElementById('estadoWrap').open = false;
        if (window.__dtTable) {
            window.__dtTable.draw();
            actualizarLabelEstado();
        }
    }

    // ── DataTable ──
    try {
        (function () {
            var table = new DataTable('#tablaPedidos', {
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
                searching: true,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [3, 4, 5] }
                ],
                language: {
                    search:          '',
                    searchPlaceholder: 'Buscar nombre, ciudad, producto…',
                    lengthMenu:      'Mostrar _MENU_ por página',
                    info:            '_TOTAL_ pedidos',
                    infoEmpty:       'Sin pedidos',
                    infoFiltered:    '(filtrado de _MAX_)',
                    zeroRecords:     'Sin resultados para este filtro.',
                    paginate: { first: '«', last: '»', next: '›', previous: '‹' }
                },
                dom: 't<"dt-bottom"ip>',
                drawCallback: function() {
                    if (typeof initModalWidgets === 'function') initModalWidgets(document.getElementById('tablaPedidos'));
                }
            });

            window.__dtTable = table;
            window.__DT_PEDIDOS__ = table;

            // Filtro por estado: nTr primero, texto normalizado como fallback
            DataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'tablaPedidos') return true;
                var chip = document.querySelector('#stateFilterContent .state-chip.is-active');
                var estado = chip ? chip.dataset.estado : '';
                if (!estado) return true;
                var aoRow = settings.aoData[dataIndex];
                if (aoRow && aoRow.nTr) return aoRow.nTr.dataset.estado === estado;
                var txt = (data[3] || '').trim().toLowerCase().replace(/\s+/g, '_');
                return txt === estado;
            });

            // Filtro por rango de fecha usando data-created-ts del <tr>
            DataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'tablaPedidos') return true;
                var startTs = window.__RANGE_START_TS;
                var endTs   = window.__RANGE_END_TS;
                if (!startTs || !endTs) return true;
                var aoRow = settings.aoData[dataIndex];
                if (!aoRow || !aoRow.nTr) return true;
                var rowTs = parseInt(aoRow.nTr.dataset.createdTs, 10);
                if (!rowTs || isNaN(rowTs)) return true;
                return (rowTs * 1000) >= startTs && (rowTs * 1000) < endTs;
            });
        })();
    } catch (e) {
        console.error('[DataTable] Error al inicializar:', e);
    }

    // ── Search en tiempo real ──
    document.getElementById('dtSearchInput').addEventListener('input', function() {
        if (window.__dtTable) { window.__dtTable.search(this.value).draw(); actualizarLabelEstado(); }
    });

    // ── details: cerrar los demás al abrir uno ──
    document.querySelectorAll('details.dtf-wrap').forEach(function(det) {
        det.addEventListener('toggle', function() {
            if (det.open) {
                document.querySelectorAll('details.dtf-wrap').forEach(function(other) {
                    if (other !== det) other.open = false;
                });
            }
        });
    });

    // ── Cerrar al hacer click fuera ──
    document.addEventListener('click', function(e) {
        if (!e.target.closest('details.dtf-wrap')) {
            document.querySelectorAll('details.dtf-wrap').forEach(function(d) { d.open = false; });
        }
    });
    </script>

    <!-- Tus scripts -->
    <script src="<?= BASE_URL ?>/public/js/funciones.js"></script>

</body>

</html>