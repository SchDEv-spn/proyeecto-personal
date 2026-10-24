<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguimiento WhatsApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>
<body>
<?php
$plantillas    = $plantillas    ?? [];
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
$usuarioEmail  = $_SESSION['usuario_email']  ?? '';
?>

<div class="sidebar-overlay" aria-hidden="true"></div>
<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
        $pageTitle    = 'Seguimiento de WhatsApp';
        $pageSubtitle = 'Tus órdenes de Dropi y a quién avisarle hoy';
        $showRangeFilter = false;
        $showSearch      = false;
        $headerCtas = [[
            'href'  => BASE_URL . '/AdminPlantillasWa/importarDropi',
            'label' => 'Importar de Dropi',
            'class' => 'btn-detail',
            'icon'  => 'fa-file-import',
        ], [
            'href'  => BASE_URL . '/AdminPlantillasWa/clientesRiesgo',
            'label' => 'Clientes con devolución' . (!empty($contadores['clientes_riesgo']) ? ' (' . (int)$contadores['clientes_riesgo'] . ')' : ''),
            'class' => 'btn-detail',
            'icon'  => 'fa-user-slash',
        ]];
        require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <?php
            // ── Órdenes de Dropi: lo que hay que avisar hoy y el historial ──
            $c = $contadores ?? [];
            $hayOrdenes = (int)($c['todas'] ?? 0) > 0;
            $ui = $ultimaImportacion ?? null;
            $desactualizada = !$ui || (int)$ui['horas'] >= 30;
            $cuando = '';
            if ($ui) {
                $t = strtotime($ui['creado_en']);
                $cuando = date('Y-m-d', $t) === date('Y-m-d') ? 'hoy a las ' . date('H:i', $t)
                    : (date('Y-m-d', $t) === date('Y-m-d', strtotime('-1 day')) ? 'ayer a las ' . date('H:i', $t) : date('d/m/Y \a \l\a\s H:i', $t));
            }
            $tarjetas = [
                ['por_avisar', 'Por avisar hoy',      'fa-bell',                 'glow-red',    'Ya llegaron a la oficina'],
                ['esperando',  'Esperando recogida',  'fa-box-open',             'glow-gold',   null],
                ['novedad',    'Con novedad',         'fa-triangle-exclamation', 'glow-purple', 'La transportadora reportó algo'],
                ['en_camino',  'En camino',           'fa-truck',                'glow-blue',   'En preparación o despachados'],
                ['entregados', 'Entregados',          'fa-circle-check',         'glow-green',  'En tu historial'],
                ['devoluciones', 'Devoluciones',      'fa-rotate-left',          'glow-red',    null],
            ];
            ?>

            <div id="seguimientoDropi" style="margin-bottom:1rem;">
                <?php if (!$hayOrdenes): ?>
                    <div class="panel" style="padding:1.1rem 1.25rem 1.25rem;">
                        <h2 style="font-size:13px;font-weight:700;margin-bottom:.4rem;"><i class="fas fa-file-excel" aria-hidden="true"></i> Todavía no has importado órdenes de Dropi</h2>
                        <p style="font-size:12.5px;color:var(--tx-muted);margin-bottom:.85rem;">
                            Descarga tus órdenes de Dropi en Excel (15 días o más) y súbelas aquí: verás a quién avisarle que su pedido ya llegó a la oficina,
                            quién espera recogida y quién tiene novedad, y podrás consultar todo el historial.
                        </p>
                        <a href="<?= BASE_URL ?>/AdminPlantillasWa/importarDropi" class="btn-primary"><i class="fas fa-file-import"></i> Importar de Dropi</a>
                    </div>
                <?php else: ?>
                    <p style="font-size:12.5px;margin:0 0 .75rem;color:<?= $desactualizada ? 'var(--gold)' : 'var(--tx-muted)' ?>;">
                        <i class="fas <?= $desactualizada ? 'fa-triangle-exclamation' : 'fa-file-excel' ?>" aria-hidden="true"></i>
                        <?php if ($ui): ?>
                            Última importación: <strong><?= htmlspecialchars($cuando) ?></strong> · <?= (int)$ui['ordenes'] ?> órdenes.
                            <?= $desactualizada ? 'Las listas pueden estar desactualizadas.' : '' ?>
                        <?php else: ?>
                            Aún no se registra ninguna importación.
                        <?php endif; ?>
                        Dropi actualiza si el paquete llegó a la oficina hacia las 10 a. m.: importa después de esa hora.
                        <a href="<?= BASE_URL ?>/AdminPlantillasWa/importarDropi" style="font-weight:700;">Importar ahora →</a>
                    </p>

                    <?php if (!empty($sinEmparejar)): $h = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); ?>
                    <details class="panel" id="segEmparejar" open style="margin-bottom:var(--sp-4);">
                        <summary class="panel__head" style="cursor:pointer;">
                            <h2><i class="fas fa-link" aria-hidden="true"></i> Emparejar productos de Dropi con los tuyos (<span data-pend><?= count($sinEmparejar) ?></span>)</h2>
                        </summary>
                        <div class="panel__body">
                            <small class="help" style="display:block;margin-bottom:var(--sp-2)">
                                Elige cuál de tus productos es cada uno de Dropi. Las órdenes pasan a mostrar el nombre de tu producto (también las que ya importaste)
                                y las notificaciones de pedidos recientes de la landing las cuentan. Se guarda y no te lo vuelve a preguntar.
                                Si dice <b>sugerido</b>, es por el parecido del nombre: confírmalo. Si todavía no tienes ese producto, elige "No lo tengo en mi tienda".
                            </small>
                            <?php foreach ($sinEmparejar as $s): ?>
                                <div class="dropi-prod" data-dropi="<?= $h($s['id']) ?>">
                                    <div>
                                        <div class="dropi-prod__nombre"><?= $h($s['nombre']) ?></div>
                                        <div class="dropi-prod__meta">ID en Dropi <?= $h($s['id']) ?> · <?= (int)$s['ordenes'] ?> <?= (int)$s['ordenes'] === 1 ? 'orden' : 'órdenes' ?></div>
                                    </div>
                                    <div style="display:flex;gap:var(--sp-2);align-items:center;">
                                        <select class="form-select" style="flex:1;min-width:0" aria-label="Producto de tu tienda para <?= $h($s['nombre']) ?>">
                                            <option value="">Elige tu producto…</option>
                                            <?php foreach ($productosTienda as $pt): $sug = $s['sugerido'] === (int)$pt['id']; ?>
                                                <option value="<?= (int)$pt['id'] ?>" <?= $sug ? 'selected' : '' ?>>
                                                    <?= $h($pt['nombre']) ?><?= (int)$pt['activo'] ? '' : ' (inactivo)' ?><?= $sug ? ' — sugerido' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <option value="ninguno">No lo tengo en mi tienda</option>
                                        </select>
                                        <button type="button" class="btn-primary" data-act="emparejar">Guardar</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php endif; ?>

                    <div class="stats-grid stats-grid--compacta stats-grid--seis" id="segTarjetas">
                        <?php foreach ($tarjetas as [$vistaT, $titulo, $icono, $glow, $nota]): ?>
                            <div class="stat-card <?= $glow ?>" role="button" tabindex="0" data-vista="<?= $vistaT ?>">
                                <div class="stat-info">
                                    <small><?= $titulo ?></small>
                                    <h2 data-c="<?= $vistaT ?>"><?= (int)($c[$vistaT] ?? 0) ?></h2>
                                    <?php if ($vistaT === 'esperando'): ?>
                                        <span class="target"><b data-c="piden_recordatorio"><?= (int)($c['piden_recordatorio'] ?? 0) ?></b> piden recordatorio</span>
                                    <?php elseif ($vistaT === 'devoluciones'): ?>
                                        <span class="target"><b data-c="costo_devoluciones" data-fmt="pesos">$<?= number_format((float)($c['costo_devoluciones'] ?? 0), 0, ',', '.') ?></b> en fletes de regreso</span>
                                    <?php else: ?>
                                        <span class="target"><?= $nota ?></span>
                                    <?php endif; ?>
                                </div>
                                <i class="fas <?= $icono ?> stat-icon" aria-hidden="true"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="table-container" style="margin-top:1rem;">
                        <div class="table-header">
                            <h2>Órdenes de Dropi</h2>
                        </div>

                        <div class="dt-filter-bar">
                            <?php /* Las opciones de estos dos desplegables las llena el JS con lo que haya en las órdenes (y cuántas). */ ?>
                            <details class="dtf-wrap" id="segEstatusWrap">
                                <summary class="dtf-btn">
                                    <i class="fas fa-flag"></i>
                                    <span class="dtf-btn__label" id="segEstatusLabel">Estado en Dropi</span>
                                    <i class="fas fa-chevron-down dtf-btn__chev"></i>
                                </summary>
                                <div class="dtf-panel" id="segEstatusPanel">
                                    <button type="button" class="dfd-item is-active" data-valor=""><i class="fas fa-list"></i> Todos los estados</button>
                                </div>
                            </details>

                            <details class="dtf-wrap" id="segTranspWrap">
                                <summary class="dtf-btn">
                                    <i class="fas fa-truck-fast"></i>
                                    <span class="dtf-btn__label" id="segTranspLabel">Transportadora</span>
                                    <i class="fas fa-chevron-down dtf-btn__chev"></i>
                                </summary>
                                <div class="dtf-panel" id="segTranspPanel">
                                    <button type="button" class="dfd-item is-active" data-valor=""><i class="fas fa-list"></i> Todas las transportadoras</button>
                                </div>
                            </details>

                            <details class="dtf-wrap" id="segFechaWrap">
                                <summary class="dtf-btn">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span class="dtf-btn__label" id="segFechaLabel">Todo el historial</span>
                                    <i class="fas fa-chevron-down dtf-btn__chev"></i>
                                </summary>
                                <div class="dtf-panel">
                                    <button type="button" class="dfd-item is-active" data-rango="todo"><i class="fas fa-infinity"></i> Todo el historial</button>
                                    <button type="button" class="dfd-item" data-rango="15"><i class="fas fa-calendar-week"></i> Últimos 15 días</button>
                                    <button type="button" class="dfd-item" data-rango="30"><i class="fas fa-calendar-week"></i> Últimos 30 días</button>
                                    <button type="button" class="dfd-item" data-rango="mes"><i class="fas fa-calendar-alt"></i> Este mes</button>
                                    <div class="dfd-divider"></div>
                                    <div class="dfd-custom">
                                        <span class="dfd-custom__label"><i class="fas fa-sliders-h"></i> Fecha del pedido</span>
                                        <form class="dfd-custom__form" id="segFechaForm">
                                            <input type="date" id="segDesde" max="<?= date('Y-m-d') ?>" aria-label="Desde">
                                            <input type="date" id="segHasta" max="<?= date('Y-m-d') ?>" aria-label="Hasta">
                                            <button type="submit">Aplicar</button>
                                        </form>
                                    </div>
                                </div>
                            </details>

                            <button type="button" class="state-chip seg-limpiar" id="segLimpiar" hidden><i class="fas fa-xmark" aria-hidden="true"></i> Limpiar filtros</button>

                            <div class="dt-search-wrap">
                                <i class="fas fa-search"></i>
                                <input type="text" id="segBuscar" placeholder="Nombre, teléfono, guía, producto, ciudad…" autocomplete="off">
                            </div>
                        </div>

                        <div class="dt-table-wrap">
                            <table id="tablaOrdenesDropi" class="pedidos-dt">
                                <thead>
                                    <tr>
                                        <th>Cliente</th>
                                        <th>Estado</th>
                                        <th>Tiempo</th>
                                        <th>Producto</th>
                                        <th>Municipio</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script src="<?= BASE_URL ?>/public/vendor/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/public/vendor/dataTables.min.js"></script>
<script src="<?= asset_url('public/js/modal-a11y.js') ?>"></script>
<script src="<?= asset_url('public/js/form-labels.js') ?>"></script>
<script src="<?= asset_url('public/js/funciones.js') ?>"></script>
<script>
// Plantillas de mensaje: las usa el compositor de WhatsApp (funciones.js) al abrir una orden.
window.__PLANTILLAS__ = <?= json_encode($plantillas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
</script>
<script>
// Órdenes de Dropi: lista de trabajo (por avisar, esperando recogida, novedad…) e historial.
// Tabla en modo servidor: el historial puede tener miles de filas.
(() => {
    const tabla = document.getElementById('tablaOrdenesDropi');
    if (!tabla) return; // todavía no hay órdenes importadas

    const BASE = window.BASE_URL || '';
    const tarjetas  = document.querySelectorAll('#segTarjetas [data-vista]');
    const btnLimpiar = document.getElementById('segLimpiar');
    const datos     = new Map(); // id de orden → datos para armar el mensaje
    let vista  = <?= json_encode($vistaInicial ?? 'todas') ?>;
    let buscar = '', desde = '', hasta = '', estatus = '', transp = '', temporizador = null;

    const marcarVista = () => {
        tarjetas.forEach(t => t.classList.toggle('is-active', t.dataset.vista === vista));
    };

    // "Limpiar filtros" solo aparece cuando hay algo filtrado: es la forma de volver a ver todas las órdenes.
    const hayFiltros = () => vista !== 'todas' || !!(estatus || transp || buscar || desde || hasta);

    const pintarContadores = (c) => {
        if (!c) return;
        document.querySelectorAll('#segTarjetas [data-c]').forEach(el => {
            if (c[el.dataset.c] === undefined) return;
            el.textContent = el.dataset.fmt === 'pesos' ? '$' + Number(c[el.dataset.c]).toLocaleString('es-CO') : c[el.dataset.c];
        });
    };

    const dt = new DataTable('#tablaOrdenesDropi', {
        serverSide: true,
        searching: false,
        ordering: false,
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        ajax: {
            url: BASE + '/AdminPlantillasWa/ordenesDropi',
            type: 'GET',
            data: (d) => { d.vista = vista; d.buscar = buscar; d.desde = desde; d.hasta = hasta; d.estatus = estatus; d.transportadora = transp; },
            dataSrc: (json) => {
                datos.clear();
                Object.keys(json.datos || {}).forEach(k => datos.set(Number(k), json.datos[k]));
                pintarContadores(json.contadores);
                pintarOpciones(json.opciones);
                return json.data || [];
            },
        },
        columns: [
            { data: 'cliente' }, { data: 'estado' }, { data: 'tiempo' },
            { data: 'producto' }, { data: 'lugar' }, { data: 'accion' },
        ],
        language: {
            lengthMenu:   'Mostrar _MENU_ por página',
            info:         '_TOTAL_ órdenes',
            infoEmpty:    'Sin órdenes',
            infoFiltered: '(de _MAX_ en esta lista)',
            infoCallback: (settings, start, end, max, total, pre) => pre.replace(/^1 órdenes/, '1 orden'),
            zeroRecords:  'No hay órdenes que coincidan.',
            emptyTable:   'No hay órdenes en esta lista.',
            paginate:     { first: '«', last: '»', next: '›', previous: '‹' },
        },
        dom: 't<"dt-bottom"lip>',
    });

    const recargar = (volverAlInicio = true) => { btnLimpiar.hidden = !hayFiltros(); return dt.ajax.reload(null, volverAlInicio); };

    // ── Tarjetas = listas ──
    const cambiarVista = (v) => { vista = v; marcarVista(); recargar(); };
    tarjetas.forEach(t => {
        t.addEventListener('click', () => cambiarVista(t.dataset.vista));
        t.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); cambiarVista(t.dataset.vista); } });
    });
    marcarVista();
    btnLimpiar.hidden = !hayFiltros();

    // ── Estado en Dropi y transportadora ──
    // Las opciones salen de las órdenes mismas, con cuántas hay de cada una dentro de lo que ya se filtró
    // (el servidor las cuenta sin aplicar el propio filtro, para poder cambiar de una a otra).
    const desplegables = {
        estatus: { wrap: document.getElementById('segEstatusWrap'), panel: document.getElementById('segEstatusPanel'), label: document.getElementById('segEstatusLabel'), vacio: 'Estado en Dropi', todos: 'Todos los estados' },
        transportadora: { wrap: document.getElementById('segTranspWrap'), panel: document.getElementById('segTranspPanel'), label: document.getElementById('segTranspLabel'), vacio: 'Transportadora', todos: 'Todas las transportadoras' },
    };
    const elegido = (clave) => clave === 'estatus' ? estatus : transp;
    const nombres = { estatus: {}, transportadora: {} }; // valor → rótulo ya visto, para la opción elegida que se queda en 0

    const itemOpcion = (icono, texto, n, valor, activo) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'dfd-item' + (activo ? ' is-active' : '');
        b.dataset.valor = valor;
        const i = document.createElement('i'); i.className = 'fas ' + icono; i.setAttribute('aria-hidden', 'true');
        const t = document.createElement('span'); t.textContent = texto;
        b.append(i, t);
        if (n !== null) { const c = document.createElement('span'); c.className = 'seg-opcion-n'; c.textContent = n; b.append(c); }
        return b;
    };

    function pintarOpciones(op) {
        if (!op) return;
        Object.keys(desplegables).forEach((clave) => {
            const d = desplegables[clave];
            const sel = elegido(clave);
            const lista = (op[clave] || []).slice();
            // La opción elegida se queda en la lista aunque en esta vista ya no tenga órdenes.
            lista.forEach(o => { nombres[clave][o.valor] = o.etiqueta; });
            if (sel && !lista.some(o => o.valor === sel)) lista.push({ valor: sel, etiqueta: nombres[clave][sel] || sel, n: 0 });
            d.panel.replaceChildren(itemOpcion('fa-list', d.todos, null, '', sel === ''));
            lista.forEach(o => d.panel.append(itemOpcion('fa-circle', o.etiqueta, o.n, o.valor, o.valor === sel)));
            const actual = lista.find(o => o.valor === sel);
            d.label.textContent = sel ? (clave === 'estatus' ? 'Estado: ' : '') + (actual ? actual.etiqueta : sel) : d.vacio;
            d.wrap.classList.toggle('dtf-wrap--active', sel !== '');
        });
    }

    Object.keys(desplegables).forEach((clave) => {
        const d = desplegables[clave];
        d.panel.addEventListener('click', (e) => {
            const b = e.target.closest('.dfd-item');
            if (!b) return;
            if (clave === 'estatus') estatus = b.dataset.valor; else transp = b.dataset.valor;
            d.wrap.open = false;
            recargar();
        });
    });

    // Un desplegable abierto se cierra al hacer clic fuera.
    document.addEventListener('click', (e) => {
        document.querySelectorAll('#segEstatusWrap[open], #segTranspWrap[open], #segFechaWrap[open]').forEach((w) => { if (!w.contains(e.target)) w.open = false; });
    });

    // ── Búsqueda ──
    document.getElementById('segBuscar').addEventListener('input', (e) => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => { buscar = e.target.value.trim(); recargar(); }, 300);
    });

    // ── Fecha del pedido ──
    const fechaWrap  = document.getElementById('segFechaWrap');
    const fechaLabel = document.getElementById('segFechaLabel');
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const ponerFecha = (d, h, etiqueta, activo) => {
        desde = d; hasta = h;
        fechaLabel.textContent = etiqueta;
        fechaWrap.classList.toggle('dtf-wrap--active', !!(d || h));
        fechaWrap.querySelectorAll('.dfd-item').forEach(b => b.classList.toggle('is-active', b.dataset.rango === activo));
    };
    const aplicarFecha = (d, h, etiqueta, activo) => {
        ponerFecha(d, h, etiqueta, activo);
        fechaWrap.open = false;
        recargar();
    };
    fechaWrap.querySelectorAll('.dfd-item').forEach(b => b.addEventListener('click', () => {
        const hoy = new Date();
        const r = b.dataset.rango;
        if (r === 'todo') return aplicarFecha('', '', 'Todo el historial', 'todo');
        if (r === 'mes')  return aplicarFecha(iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), iso(hoy), 'Este mes', 'mes');
        const ini = new Date(hoy); ini.setDate(ini.getDate() - Number(r));
        aplicarFecha(iso(ini), iso(hoy), 'Últimos ' + r + ' días', r);
    }));
    document.getElementById('segFechaForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const d = document.getElementById('segDesde').value, h = document.getElementById('segHasta').value;
        if (!d && !h) return;
        if (d && h && d > h) { document.getElementById('segHasta').focus(); return; }
        aplicarFecha(d, h, (d || '…') + ' → ' + (h || '…'), '');
    });

    // ── Limpiar filtros: vuelve a "todas las órdenes", sin estado, transportadora, búsqueda ni fecha ──
    btnLimpiar.addEventListener('click', () => {
        vista = 'todas'; estatus = ''; transp = ''; buscar = '';
        document.getElementById('segBuscar').value = '';
        document.getElementById('segDesde').value = '';
        document.getElementById('segHasta').value = '';
        ponerFecha('', '', 'Todo el historial', 'todo');
        marcarVista();
        recargar();
    });

    // ── Acciones de cada fila ──
    const registrar = (d, estado, guia, soloMarcar) => {
        const fd = new FormData();
        fd.append('orden_id', d.ordenId);
        fd.append('estado', estado);
        fd.append('guia', guia || '');
        if (soloMarcar) fd.append('solo_marcar', '1');
        fd.append('csrf_token', window.__CSRF__ || '');
        return fetch(BASE + '/AdminPlantillasWa/registrarAvisoDropi', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
            .then(r => r.json())
            .then(j => {
                if (!j.ok) { alert(j.error || 'No se pudo guardar el aviso.'); return; }
                pintarContadores(j.contadores);
                recargar(false);
            })
            .catch(() => alert('No se pudo guardar el aviso. Revisa tu conexión.'));
    };

    // ── Emparejar un producto de Dropi con uno de la tienda ──
    const panelEmparejar = document.getElementById('segEmparejar');
    if (panelEmparejar) {
        panelEmparejar.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-act="emparejar"]');
            if (!btn) return;
            const fila = btn.closest('.dropi-prod');
            const sel  = fila.querySelector('select');
            if (!sel.value) { sel.focus(); alert('Elige tu producto, o "No lo tengo en mi tienda".'); return; }

            const fd = new FormData();
            fd.append('dropi_id', fila.dataset.dropi);
            fd.append('producto_id', sel.value);
            fd.append('csrf_token', window.__CSRF__ || '');
            btn.disabled = true;
            fetch(BASE + '/AdminPlantillasWa/emparejarProducto', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
                .then(r => r.json())
                .then(j => {
                    if (!j.ok) { alert(j.error || 'No se pudo guardar.'); btn.disabled = false; return; }
                    fila.remove();
                    if (j.pendientes > 0) panelEmparejar.querySelector('[data-pend]').textContent = j.pendientes;
                    else panelEmparejar.remove();
                    recargar(false);   // la tabla muestra ya el nombre de tu producto
                })
                .catch(() => { alert('No se pudo guardar. Revisa tu conexión.'); btn.disabled = false; });
        });
    }

    tabla.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-act]');
        if (!btn) return;
        const d = datos.get(Number(btn.dataset.id));
        if (!d) return;

        if (btn.dataset.act === 'ya') {
            if (confirm('¿Marcar que ya le avisaste a este cliente por otro medio?')) registrar(d, d.siguiente, '', true);
            return;
        }
        // Sin "id": el compositor lo usa para pedidos de la landing y aquí no aplica.
        window.WaPicker.open({ ...d }, {
            onSend: (mensaje, estado, guia) => { registrar(d, estado, guia, false); },
        });
    });
})();
</script>
</body>
</html>
