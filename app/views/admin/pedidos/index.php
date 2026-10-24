<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/dataTables.dataTables.min.css">
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
    $dropi           = $dropi           ?? null;  // ventas reales de Dropi (null: sin sección)
    $dropi_serie     = $dropi_serie     ?? null;
    $pedidos_js      = $pedidos_js      ?? [];
    $rango_etiqueta  = $rango_etiqueta  ?? 'Fecha';
    $csv_href        = $csv_href        ?? BASE_URL . '/AdminPedidos/exportarCsv';
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

                <?php if ($dropi !== null): ?>
                    <?php require __DIR__ . '/_dropi_resumen.php'; ?>
                    <h2 class="dash-etiqueta">Landing <small>los pedidos que entran por tu página</small></h2>
                <?php endif; ?>

                <!-- =========================
                     DASHBOARD (Chart.js)
                     ========================= -->
                <section class="dash-grid" aria-label="Dashboard">
                    <article class="panel panel--kpi kpi-purple">
                        <div class="panel__head">
                            <h2>Pedidos últimos 30 días</h2>
                            <span class="chip">Actividad</span>
                        </div>
                        <div class="panel__body">
                            <canvas id="chartPedidos14" height="120"></canvas>
                        </div>
                    </article>

                    <article class="panel panel--kpi kpi-cyan">
                        <div class="panel__head">
                            <h2>Ventas últimos 30 días</h2>
                            <span class="chip">COP</span>
                        </div>
                        <div class="panel__body">
                            <canvas id="chartVentas14" height="120"></canvas>
                        </div>
                    </article>

                    <article class="panel panel--wide">
                        <div class="panel__head">
                            <h2>Distribución por estado</h2>
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
                <div class="stats-grid" id="dashTarjetas">
                    <?php require __DIR__ . '/_dash_tarjetas.php'; ?>
                </div>

                <!-- =========================
                     PEDIDOS
                     ========================= -->
                <!-- Embudo de conversión -->
                <div class="funnel-wrap">
                    <div class="panel">
                        <div class="panel__head">
                            <h2>Embudo de conversión</h2>
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
                            <h2>Pedidos Recientes</h2>
                            <a href="<?= htmlspecialchars($csv_href) ?>"
                               class="btn-csv" title="Exportar a CSV">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                        </div>

                        <?php
                        $rangoLabel  = $rango_etiqueta;
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
                                    <button type="button" class="dfd-item <?= $rango==='siempre' ?'is-active':'' ?>" data-rango="siempre" onclick="cambiarRango('siempre')"><i class="fas fa-infinity"></i> Desde siempre</button>
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
                            <?php require __DIR__ . '/_pedidos_filas.php'; ?>
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
        window.__PEDIDOS__     = <?= json_encode($pedidos_js, JSON_UNESCAPED_UNICODE) ?>;
        window.__PLANTILLAS__  = <?= json_encode($plantillas_wa, JSON_UNESCAPED_UNICODE) ?>;
        window.__RANGE_INIT__  = <?= json_encode(['hoy'=>'today','ayer'=>'yesterday','semana'=>'week','mes'=>'month','personalizado'=>'custom','siempre'=>'all'][$rango] ?? 'month') ?>;
        window.__RANGE_CUSTOM__ = <?= ($rango === 'personalizado' && $desde && $hasta)
            ? json_encode(['desde' => $desde, 'hasta' => $hasta])
            : 'null' ?>;
    </script>

    <!-- Chart.js -->
    <script src="<?= BASE_URL ?>/public/vendor/chart.umd.min.js"></script>

    <?php if ($dropi !== null): ?>
    <!-- Ventas reales de Dropi: gráficos y cambio de período/canal sin recargar -->
    <script src="<?= asset_url('public/js/dashboard-dropi.js') ?>"></script>
    <?php endif; ?>

    <!-- jQuery (requerido por DataTables CDN) -->
    <script src="<?= BASE_URL ?>/public/vendor/jquery-3.7.1.slim.min.js"></script>
    <!-- DataTables -->
    <script src="<?= BASE_URL ?>/public/vendor/dataTables.min.js"></script>
    <script>
    // ── Declaraciones hoistadas: disponibles aunque el IIFE de abajo falle ──

    var RANGOS_JS = { hoy: 'today', ayer: 'yesterday', semana: 'week', mes: 'month', personalizado: 'custom', siempre: 'all' };
    var FECHA_ISO = /^\d{4}-\d{2}-\d{2}$/;
    var cargasRango = 0;

    // Qué rango se está viendo (lo que el servidor entiende: un rango raro cuenta como "mes").
    function claveRango(params) {
        var r = params.get('rango') || 'mes';
        if (!RANGOS_JS.hasOwnProperty(r)) r = 'mes';
        var d = params.get('desde') || '', h = params.get('hasta') || '';
        if (r === 'personalizado' && (!FECHA_ISO.test(d) || !FECHA_ISO.test(h) || d > h)) r = 'mes';
        return r === 'personalizado' ? r + '|' + d + '|' + h : r;
    }
    var rangoActual = <?= json_encode($rango === 'personalizado' ? $rango . '|' . $desde . '|' . $hasta : $rango) ?>;

    // Cambiar de rango no recarga la página: el servidor entrega las tarjetas y los pedidos del rango pedido
    // (no basta filtrar en el navegador: los pedidos de meses anteriores nunca se habrían cargado) y se vuelve a
    // pintar todo. Si algo falla (sin conexión, sesión vencida), se recarga como antes.
    function cambiarRango(rango, desde, hasta) {
        var qs = 'rango=' + encodeURIComponent(rango);
        if (rango === 'personalizado' && desde && hasta) {
            qs += '&desde=' + encodeURIComponent(desde) + '&hasta=' + encodeURIComponent(hasta);
        }
        // El período y el canal de la sección de Dropi no se pierden al cambiar el filtro de la landing.
        var actual = new URLSearchParams(window.location.search);
        ['dropi_rango', 'dropi_desde', 'dropi_hasta', 'dropi_canal'].forEach(function (k) {
            if (actual.has(k)) qs += '&' + k + '=' + encodeURIComponent(actual.get(k));
        });
        var destino = '<?= BASE_URL ?>/AdminPedidos/index?' + qs;
        if (!window.fetch || !window.URL || !window.__dtTable) { window.location.href = destino; return; }
        cargarRango(destino, true);
    }

    function cargarRango(destino, apilar) {
        var url = new URL(destino, window.location.href);
        var consulta = new URLSearchParams();
        ['rango', 'desde', 'hasta'].forEach(function (k) { if (url.searchParams.has(k)) consulta.set(k, url.searchParams.get(k)); });
        var numero = ++cargasRango;
        var ocupados = [document.getElementById('dashTarjetas'), document.querySelector('.table-container')];
        ocupados.forEach(function (el) { if (el) { el.classList.add('is-cargando'); el.setAttribute('aria-busy', 'true'); } });
        window.fetch('<?= BASE_URL ?>/AdminPedidos/vistaRango?' + consulta.toString(), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (j) {
            if (numero !== cargasRango) return; // llegó otra petición después: esta ya no sirve
            if (!j || typeof j.filas !== 'string' || typeof j.tarjetas !== 'string' || !Array.isArray(j.pedidos)) throw new Error('respuesta incompleta');
            // primero la dirección: los enlaces de la sección de Dropi se arman a partir de ella
            if (apilar) window.history.pushState({ rango: 1 }, '', url.pathname + url.search + url.hash);
            pintarRango(j);
        }).catch(function () {
            if (numero === cargasRango) window.location.href = destino;
        }).then(function () {
            if (numero !== cargasRango) return;
            ocupados.forEach(function (el) { if (el) { el.classList.remove('is-cargando'); el.removeAttribute('aria-busy'); } });
        });
    }

    // Pinta lo que cambia con el rango: pedidos en memoria, tarjetas, tabla y el filtro de fecha. Los KPIs, los
    // gráficos, el embudo y el filtro de fecha de la tabla los vuelve a calcular funciones.js con 'range:change'.
    function pintarRango(j) {
        // funciones.js guarda esta misma lista, por eso se cambia por dentro y no se reemplaza
        var lista = window.__PEDIDOS__;
        lista.length = 0;
        j.pedidos.forEach(function (p) { lista.push(p); });
        window.__RANGE_CUSTOM__ = (j.rango === 'personalizado' && j.desde && j.hasta) ? { desde: j.desde, hasta: j.hasta } : null;

        var tarjetas = document.getElementById('dashTarjetas');
        if (tarjetas) tarjetas.innerHTML = j.tarjetas;

        if (window.__dtTable) window.__dtTable.destroy();
        document.querySelector('#tablaPedidos tbody').innerHTML = j.filas;
        crearTabla();

        // Como al abrir la página: sin búsqueda ni estado elegido
        var buscar = document.getElementById('dtSearchInput');
        if (buscar) buscar.value = '';
        document.querySelectorAll('#stateFilterContent .state-chip').forEach(function (c) { c.classList.toggle('is-active', c.dataset.estado === ''); });
        document.getElementById('estadoWrap').classList.remove('dtf-wrap--active');

        // Filtro de fecha: etiqueta, opción marcada, fechas y enlace del CSV
        var fecha = document.getElementById('fechaWrap');
        var etiqueta = fecha.querySelector('.dtf-btn__label');
        if (etiqueta) etiqueta.textContent = j.etiqueta;
        fecha.classList.toggle('dtf-wrap--active', j.rango !== 'mes');
        fecha.open = false;
        fecha.querySelectorAll('.dfd-item').forEach(function (b) { b.classList.toggle('is-active', b.getAttribute('data-rango') === j.rango); });
        document.getElementById('fechaDesde').value = j.desde || '';
        document.getElementById('fechaHasta').value = j.hasta || '';
        var csv = document.querySelector('.table-header .btn-csv');
        if (csv && j.csv) csv.setAttribute('href', j.csv);

        rangoActual = j.rango === 'personalizado' ? j.rango + '|' + j.desde + '|' + j.hasta : j.rango;
        var codigo = RANGOS_JS[j.rango] || 'month';
        window.__RANGE_INIT__ = codigo;
        window.__RANGE_SELECTED = codigo;
        window.dispatchEvent(new CustomEvent('range:change', { detail: { range: codigo } }));
        if (window.dropiActualizarEnlaces) window.dropiActualizarEnlaces();
    }

    // Atrás y adelante del navegador: se muestra el rango de esa dirección (sin recargar, y solo si cambió).
    window.addEventListener('popstate', function () {
        if (claveRango(new URLSearchParams(window.location.search)) !== rangoActual) cargarRango(window.location.href, false);
    });

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
    // Opciones de la tabla de pedidos (se vuelve a crear al cambiar de rango sin recargar)
    var OPCIONES_TABLA = {
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        searching: true,
        order: [[0, 'desc']],
        // autoWidth escribe anchos px inline en cada <th> y pisa el
        // table-layout:fixed del CSS responsive. El layout lo maneja el CSS.
        autoWidth: false,
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
            emptyTable:      'Sin pedidos en este período.',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        },
        dom: 't<"dt-bottom"ip>',
        drawCallback: function() {
            if (typeof initModalWidgets === 'function') initModalWidgets(document.getElementById('tablaPedidos'));
        }
    };

    function crearTabla() {
        var t = new DataTable('#tablaPedidos', OPCIONES_TABLA);
        window.__dtTable = t;
        window.__DT_PEDIDOS__ = t;
        return t;
    }

    try {
        (function () {
            crearTabla();

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
    <script src="<?= asset_url('public/js/modal-a11y.js') ?>"></script>
    <script src="<?= asset_url('public/js/funciones.js') ?>"></script>

</body>

</html>