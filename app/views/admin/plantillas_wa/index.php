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
    <style>
        .plantilla-estado-badge {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .p-badge-nuevo      { background: var(--info-bg);  color: var(--info);  border: 1px solid var(--info-bd); }
        .p-badge-contactado { background: rgba(168,139,250,.10); color: #6D28D9; border: 1px solid rgba(168,139,250,.24); }
        .p-badge-confirmado { background: var(--ok-bg);    color: var(--ok);    border: 1px solid var(--ok-bd); }
        .p-badge-enviado    { background: var(--warn-bg);  color: var(--warn);  border: 1px solid var(--warn-bd); }
        .p-badge-en_oficina { background: var(--warn-bg);  color: var(--warn);  border: 1px solid var(--warn-bd); }
        .p-badge-recordatorio_oficina { background: var(--err-bg); color: var(--err); border: 1px solid var(--err-bd); }
        .p-badge-ultimo_aviso_oficina { background: var(--err-bg); color: var(--err); border: 1px solid var(--err-bd); font-weight: 900; }
        .p-badge-entregado  { background: var(--ok-bg);    color: var(--ok);    border: 1px solid var(--ok-bd); }
        .p-badge-cancelado  { background: var(--err-bg);   color: var(--err);   border: 1px solid var(--err-bd); }

        .plantilla-field label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--tx-muted);
            margin-bottom: 5px;
        }

        .plantilla-field input,
        .plantilla-field textarea,
        .plantilla-field select,
        .wa-buscar-row input {
            width: 100%;
            background: var(--bg-overlay);
            border: 1px solid var(--bd-default);
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13px;
            font-family: var(--font-body);
            color: var(--tx-primary);
            resize: vertical;
            transition: border-color .15s, box-shadow .15s;
            box-sizing: border-box;
            outline: none;
        }
        .plantilla-field input:focus,
        .plantilla-field textarea:focus,
        .plantilla-field select:focus,
        .wa-buscar-row input:focus {
            border-color: var(--gold-dim);
            box-shadow: 0 0 0 3px rgba(201,168,76,.10);
        }

        /* El input y los botones reciclan .plantilla-field / .btn-primary
           (mismos estilos que Productos) — aquí solo va el layout. */
        .wa-buscar-row {
            display: flex;
            gap: .6rem;
        }
        .wa-buscar-row input { flex: 1; }

        .wa-resultados {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }
        .wa-resultado-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
            background: var(--bg-overlay);
            border: 1px solid var(--bd-subtle);
            border-radius: 10px;
            padding: .7rem .9rem;
            font-size: 13px;
        }
        .wa-resultado-meta {
            display: flex;
            align-items: center;
            gap: .5rem;
            font-size: 11.5px;
            color: var(--tx-muted);
            margin-top: 3px;
        }

        .wa-manual { margin-top: 1rem; }
        .wa-manual-hint {
            font-size: 12.5px;
            color: var(--tx-muted);
            margin-bottom: .75rem;
        }
        .wa-dictado-row {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: .5rem;
        }
        .wa-dictado-estado {
            font-size: 12px;
            color: var(--tx-muted);
        }
        #waDictarBtn.is-escuchando {
            background: var(--err-bg);
            color: var(--err);
            border-color: var(--err-bd);
            animation: wa-pulso 1.1s ease-in-out infinite;
        }
        @keyframes wa-pulso {
            0%, 100% { opacity: 1; }
            50%      { opacity: .55; }
        }
        .wa-manual-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: .8rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 600px) {
            .wa-buscar-row { flex-direction: column; }
            .wa-resultado-card { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
<?php
$plantillas    = $plantillas    ?? [];
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
$usuarioEmail  = $_SESSION['usuario_email']  ?? '';

$estados = [
    'nuevo'                => 'Nuevo',
    'contactado'           => 'Contactado',
    'confirmado'           => 'Confirmado',
    'enviado'              => 'Enviado',
    'en_reparto'           => 'Hoy llega',
    'en_oficina'           => 'En oficina',
    'recordatorio_oficina' => 'Recordatorio de recogida',
    'ultimo_aviso_oficina' => 'Última oportunidad',
    'novedad'              => 'Novedad con la entrega',
    'entregado'            => 'Entregado',
    'cancelado'            => 'Cancelado',
];
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
                    <p style="font-size:12.5px;margin:0 0 .75rem;color:<?= $desactualizada ? 'var(--warn)' : 'var(--tx-muted)' ?>;">
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
                            <button type="button" class="state-chip" id="segTodas">Todas las órdenes</button>

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

            <!-- Compositor a mano: para clientes que todavía no están en Dropi -->
            <details class="wa-aparte" style="margin-bottom:1rem;">
            <summary class="btn-detail" style="cursor:pointer;margin-bottom:.75rem;">
                <i class="fab fa-whatsapp" aria-hidden="true"></i> Mensaje a mano (cliente que todavía no está en Dropi)
            </summary>
            <div class="panel" id="waComposerPanel" style="padding:1.1rem 1.25rem 1.25rem;margin-bottom:0;">
                <h2 style="font-size:13px;font-weight:700;margin-bottom:.4rem;">Enviar mensaje por WhatsApp</h2>
                <p style="font-size:12.5px;color:var(--tx-muted);margin-bottom:.85rem;">
                    Busca por teléfono: si corresponde a un pedido de la landing se autocompletan sus datos.
                    Si no, arma el mensaje a mano (por ejemplo, un cliente cerrado directo por WhatsApp).
                </p>

                <div class="wa-buscar-row">
                    <input type="tel" id="waBuscarTelefono" placeholder="Ej: 3001234567" inputmode="tel">
                    <button type="button" id="waBuscarBtn" class="btn-primary"><i class="fas fa-magnifying-glass"></i> Buscar</button>
                </div>

                <div id="waResultados" class="wa-resultados" hidden></div>

                <div id="waManual" class="wa-manual" hidden>
                    <p class="wa-manual-hint" id="waManualHint">No encontramos un pedido de la landing con ese teléfono. Arma el mensaje a mano:</p>

                    <div class="wa-dictado-row" id="waDictadoRow" hidden>
                        <button type="button" id="waDictarBtn" class="btn-primary btn-primary--soft">
                            <i class="fas fa-microphone"></i> Dictar nombre, departamento, municipio y teléfono
                        </button>
                        <span id="waDictadoEstado" class="wa-dictado-estado"></span>
                    </div>
                    <p class="wa-manual-hint" id="waDictadoAyuda" hidden>
                        Dilo en este orden: <strong>nombre y apellidos</strong> → <strong>departamento</strong> → <strong>municipio</strong> → <strong>teléfono</strong> (dígito por dígito).
                        Ej: "Jesús Mosquera, Antioquia, Medellín, tres cero cero uno dos tres cuatro cinco seis siete".
                    </p>

                    <div class="wa-manual-grid">
                        <div class="plantilla-field"><label>Nombre</label><input type="text" id="mNombre"></div>
                        <div class="plantilla-field"><label>Apellidos</label><input type="text" id="mApellidos"></div>
                        <div class="plantilla-field">
                            <label>Producto</label>
                            <select id="mProducto">
                                <option value="">— Elige un producto —</option>
                                <?php foreach ($productos as $prod): ?>
                                    <option value="<?= htmlspecialchars($prod['nombre']) ?>"
                                            data-precio="<?= htmlspecialchars((string)($prod['precio_venta'] ?? '')) ?>">
                                        <?= htmlspecialchars($prod['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="plantilla-field"><label>Cantidad</label><input type="text" id="mCantidad" value="1"></div>
                        <div class="plantilla-field"><label>Precio</label><input type="text" id="mPrecio" placeholder="$0"></div>
                        <div class="plantilla-field">
                            <label>Departamento</label>
                            <select id="mDepartamento">
                                <option value="">— Elige un departamento —</option>
                                <?php foreach (array_keys($ubicaciones) as $dep): ?>
                                    <option value="<?= htmlspecialchars($dep) ?>"><?= htmlspecialchars($dep) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="plantilla-field">
                            <label>Municipio</label>
                            <select id="mMunicipio" disabled>
                                <option value="">Primero elige el departamento</option>
                            </select>
                        </div>
                        <div class="plantilla-field">
                            <label>Tipo de entrega</label>
                            <select id="mTipoEntrega">
                                <option value="domicilio">Domicilio (Envia)</option>
                                <option value="oficina" selected>Oficina (Interrapidísimo)</option>
                            </select>
                        </div>
                        <div class="plantilla-field">
                            <label>Estado / plantilla</label>
                            <select id="mEstado">
                                <?php foreach ($estados as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="button" id="waComponerBtn" class="btn-primary"><i class="fab fa-whatsapp"></i> Componer mensaje</button>
                </div>
            </div>
            </details>

            <!-- En oficina esperando recogida (pedidos de la landing y contactos a mano; las órdenes de Dropi van arriba) -->
            <div class="panel" id="waEnOficinaPanel" style="padding:1.1rem 1.25rem 1.25rem;margin-bottom:1rem;" hidden>
                <h2 style="font-size:13px;font-weight:700;margin-bottom:.4rem;">En oficina, esperando recogida</h2>
                <p style="font-size:12.5px;color:var(--tx-muted);margin-bottom:.85rem;">
                    Pedidos reales y contactos manuales a los que ya se les avisó que están en la oficina de Interrapidísimo.
                    Interrapidísimo devuelve automáticamente los no reclamados a los 5 días hábiles.
                </p>
                <div class="dt-table-wrap">
                    <table id="tablaEnOficina" class="pedidos-dt">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Producto</th>
                                <th>Teléfono</th>
                                <th>Días esperando</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Guía de flujo de trabajo -->
            <div class="panel" style="padding:1rem 1.25rem 1rem;margin-bottom:1rem;">
                <h2 style="font-size:13px;font-weight:700;margin-bottom:.75rem;">Flujo recomendado de mensajes</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div style="background:var(--gold-ghost);border-radius:12px;padding:.85rem 1rem;border-left:3px solid var(--gold);">
                        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--gold);margin-bottom:.5rem;">📦 Envío a domicilio (Envia)</p>
                        <div style="font-size:12.5px;line-height:2;color:var(--tx-secondary);">
                            <span class="plantilla-estado-badge p-badge-nuevo" style="font-size:10px;">Nuevo</span> Recibimos tu pedido<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Enviado</span> Guía + link Envia<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Hoy llega</span> Cuando va en reparto: ten el efectivo listo<br>
                            <span class="plantilla-estado-badge p-badge-recordatorio_oficina" style="font-size:10px;">Novedad</span> Según la causa: dirección, contacto, coordinar…<br>
                            <span class="plantilla-estado-badge p-badge-entregado" style="font-size:10px;">Entregado</span> ¿Cómo llegó? + foto
                        </div>
                    </div>
                    <div style="background:var(--warn-bg);border-radius:12px;padding:.85rem 1rem;border-left:3px solid var(--warn);">
                        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--warn);margin-bottom:.5rem;">🏢 Retiro en oficina (Interrapidísimo)</p>
                        <div style="font-size:12.5px;line-height:2;color:var(--tx-secondary);">
                            <span class="plantilla-estado-badge p-badge-nuevo" style="font-size:10px;">Nuevo</span> Recibimos tu pedido<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Enviado</span> Despachado con guía<br>
                            <span class="plantilla-estado-badge p-badge-en_oficina" style="font-size:10px;">En oficina</span> Listo para recoger, con fecha límite<br>
                            <span class="plantilla-estado-badge p-badge-recordatorio_oficina" style="font-size:10px;">Recordatorio</span> Antes de que se devuelva (5 días hábiles)<br>
                            <span class="plantilla-estado-badge p-badge-ultimo_aviso_oficina" style="font-size:10px;">Última oportunidad</span> Si el recordatorio no funcionó<br>
                            <span class="plantilla-estado-badge p-badge-entregado" style="font-size:10px;">Entregado</span> ¿Cómo llegó? + foto
                        </div>
                    </div>
                </div>
                <p style="font-size:11px;color:var(--muted);margin-top:.7rem;">
                    💡 En el picker de WhatsApp, <strong>{transportadora}</strong> y <strong>{rastreo}</strong> se llenan solos según el tipo de entrega del pedido. Solo debes escribir el <strong>{guia}</strong>.
                    Interrapidísimo devuelve automáticamente los pedidos no reclamados a los 5 días hábiles — si ves uno "en oficina" que lleva varios días, usa la plantilla <strong>Recordatorio de recogida</strong>.
                </p>
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
// Compositor de mensajes: busca pedido por teléfono o arma uno a mano
(() => {
    window.__PLANTILLAS__ = <?= json_encode($plantillas, JSON_UNESCAPED_UNICODE) ?>;

    const ESTADO_LABEL  = <?= json_encode($estados, JSON_UNESCAPED_UNICODE) ?>;
    const UBICACIONES   = <?= json_encode($ubicaciones, JSON_UNESCAPED_UNICODE) ?>; // mismo dataset del formulario de la landing

    const telInput      = document.getElementById('waBuscarTelefono');
    const buscarBtn     = document.getElementById('waBuscarBtn');
    const resultados    = document.getElementById('waResultados');
    const manualBox     = document.getElementById('waManual');
    const waManualHint  = document.getElementById('waManualHint');
    const mProducto     = document.getElementById('mProducto');
    const mPrecio       = document.getElementById('mPrecio');
    const mDepartamento = document.getElementById('mDepartamento');
    const mMunicipio    = document.getElementById('mMunicipio');
    const mTipoEntrega  = document.getElementById('mTipoEntrega');

    // Al elegir un producto real de la BD, sugiere su precio de venta.
    mProducto.addEventListener('change', () => {
        const precio = mProducto.selectedOptions[0]?.dataset.precio;
        if (precio) mPrecio.value = '$' + Number(precio).toLocaleString('es-CO');
    });

    // Municipio depende del departamento elegido — mismo dataset de la landing.
    mDepartamento.addEventListener('change', () => {
        const dep = mDepartamento.value;
        const municipios = UBICACIONES[dep] || [];

        mMunicipio.innerHTML = '';
        if (!dep) {
            mMunicipio.disabled = true;
            mMunicipio.appendChild(new Option('Primero elige el departamento', ''));
            return;
        }

        mMunicipio.disabled = false;
        mMunicipio.appendChild(new Option('— Elige un municipio —', ''));
        municipios.forEach(mun => mMunicipio.appendChild(new Option(mun, mun)));
    });

    // Dictado por voz: nombre y apellidos → departamento → municipio →
    // teléfono. El orden importa — departamento antes que municipio reduce
    // la búsqueda de 1.122 municipios a solo los del departamento
    // reconocido, así se equivoca mucho menos.
    (() => {
        const dictarBtn    = document.getElementById('waDictarBtn');
        const dictadoRow   = document.getElementById('waDictadoRow');
        const dictadoEstado= document.getElementById('waDictadoEstado');
        const dictadoAyuda = document.getElementById('waDictadoAyuda');

        const SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognitionCtor) return; // sin soporte (p. ej. Firefox, Safari): el botón queda oculto

        dictadoRow.hidden  = false;
        dictadoAyuda.hidden = false;

        const PALABRAS_DIGITO = { cero:'0', uno:'1', dos:'2', tres:'3', cuatro:'4', cinco:'5', seis:'6', siete:'7', ocho:'8', nueve:'9' };

        // Rango Unicode de marcas diacríticas combinantes (U+0300–U+036F) —
        // construido con fromCharCode para no depender de cómo cada editor
        // guarde un carácter combinante suelto en el código fuente.
        const DIACRITICOS = new RegExp('[' + String.fromCharCode(0x0300) + '-' + String.fromCharCode(0x036f) + ']', 'g');
        const normalizar = (s) => s.toLowerCase()
            .normalize('NFD').replace(DIACRITICOS, '')
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        // Racha más larga de tokens numéricos (dígitos sueltos o ya escritos
        // como número) — mide por cantidad de DÍGITOS, no de palabras, por
        // si el reconocedor junta todo en un solo token.
        const extraerTelefono = (palabras) => {
            const comoDigitos = palabras.map(p => {
                if (PALABRAS_DIGITO[p] !== undefined) return PALABRAS_DIGITO[p];
                return /^\d+$/.test(p) ? p : null;
            });

            let mejor = { inicio: -1, fin: -1, digitos: '' };
            let inicio = -1, digitos = '';
            for (let i = 0; i <= comoDigitos.length; i++) {
                const d = i < comoDigitos.length ? comoDigitos[i] : null;
                if (d !== null) {
                    if (inicio === -1) inicio = i;
                    digitos += d;
                } else {
                    if (digitos.length > mejor.digitos.length) mejor = { inicio, fin: i, digitos };
                    inicio = -1; digitos = '';
                }
            }

            if (mejor.digitos.length < 7) return { telefono: '', resto: palabras };
            return { telefono: mejor.digitos, resto: [...palabras.slice(0, mejor.inicio), ...palabras.slice(mejor.fin)] };
        };

        // Busca, dentro de `palabras`, la secuencia que mejor calce con
        // alguno de `candidatos` (nombres de depto/municipio, pueden ser de
        // varias palabras como "Valle del Cauca"). Se queda con el más largo.
        const buscarCoincidencia = (palabras, candidatos) => {
            const normPalabras = palabras.map(normalizar);
            let mejor = null;
            for (const candidato of candidatos) {
                const tokensCand = normalizar(candidato).split(' ');
                for (let i = 0; i <= normPalabras.length - tokensCand.length; i++) {
                    let ok = true;
                    for (let j = 0; j < tokensCand.length; j++) {
                        if (normPalabras[i + j] !== tokensCand[j]) { ok = false; break; }
                    }
                    if (ok && (!mejor || tokensCand.length > mejor.largo)) {
                        mejor = { valor: candidato, inicio: i, largo: tokensCand.length };
                    }
                }
            }
            return mejor;
        };

        const procesarDictado = (texto) => {
            let palabras = texto.trim().split(/\s+/).filter(Boolean);

            const { telefono, resto: sinTelefono } = extraerTelefono(palabras);
            palabras = sinTelefono;

            let departamento = '';
            const depMatch = buscarCoincidencia(palabras, Object.keys(UBICACIONES));
            if (depMatch) {
                departamento = depMatch.valor;
                palabras = [...palabras.slice(0, depMatch.inicio), ...palabras.slice(depMatch.inicio + depMatch.largo)];
            }

            let municipio = '';
            if (departamento) {
                const munMatch = buscarCoincidencia(palabras, UBICACIONES[departamento] || []);
                if (munMatch) {
                    municipio = munMatch.valor;
                    palabras = [...palabras.slice(0, munMatch.inicio), ...palabras.slice(munMatch.inicio + munMatch.largo)];
                }
            }

            // Lo que sobra es el nombre — últimas 2 palabras como apellidos
            // (convención más común: uno o dos nombres, dos apellidos).
            let nombre = '', apellidos = '';
            if (palabras.length === 1)      { nombre = palabras[0]; }
            else if (palabras.length === 2) { nombre = palabras[0]; apellidos = palabras[1]; }
            else if (palabras.length > 2)   { apellidos = palabras.slice(-2).join(' '); nombre = palabras.slice(0, -2).join(' '); }

            return { nombre, apellidos, departamento, municipio, telefono };
        };

        let enCurso = null;

        dictarBtn.addEventListener('click', () => {
            if (enCurso) { enCurso.stop(); return; }

            const rec = new SpeechRecognitionCtor();
            rec.lang = 'es-CO';
            rec.interimResults = false;
            rec.maxAlternatives = 1;

            rec.onstart = () => {
                dictarBtn.classList.add('is-escuchando');
                dictadoEstado.textContent = 'Escuchando…';
            };

            rec.onresult = (e) => {
                const texto = e.results[0][0].transcript;
                const r = procesarDictado(texto);

                if (r.nombre)    document.getElementById('mNombre').value = r.nombre;
                if (r.apellidos) document.getElementById('mApellidos').value = r.apellidos;
                if (r.telefono)  telInput.value = r.telefono;

                if (r.departamento) {
                    mDepartamento.value = r.departamento;
                    mDepartamento.dispatchEvent(new Event('change'));
                    if (r.municipio) setTimeout(() => { mMunicipio.value = r.municipio; }, 0);
                }

                dictadoEstado.textContent = 'Escuché: "' + texto + '" — revisa los campos antes de componer.';
            };

            rec.onerror = (e) => {
                dictadoEstado.textContent = 'No se pudo reconocer (' + e.error + '). Intenta de nuevo.';
            };

            rec.onend = () => {
                dictarBtn.classList.remove('is-escuchando');
                enCurso = null;
            };

            enCurso = rec;
            rec.start();
        });
    })();

    let ultimosPedidos = [];

    const renderResultados = (pedidos) => {
        ultimosPedidos = pedidos;

        manualBox.hidden    = true;
        resultados.hidden   = false;
        resultados.innerHTML = pedidos.map((p, i) => `
            <div class="wa-resultado-card">
                <div>
                    <strong>${p.nombre} ${p.apellidos}</strong> — ${p.producto} (${p.cantidad})
                    <div class="wa-resultado-meta">
                        Pedido #${p.id} · ${p.fecha}
                        <span class="plantilla-estado-badge p-badge-${p.estado}">${ESTADO_LABEL[p.estado] || p.estado}</span>
                    </div>
                </div>
                <button type="button" class="btn-primary btn-primary--soft" data-idx="${i}">Usar este pedido</button>
            </div>
        `).join('');
    };

    // Guía guardada del contacto manual cargado (no hay campo propio en el
    // formulario de afuera; se lleva al picker cuando se compone el mensaje).
    let numeroGuiaContacto = '';

    // Prellena el formulario manual con un contacto ya guardado (mismo
    // teléfono, sin pedido de la landing) — nada que volver a escribir.
    const cargarContactoManual = (c) => {
        document.getElementById('mNombre').value       = c.nombre       || '';
        document.getElementById('mApellidos').value    = c.apellidos    || '';
        document.getElementById('mCantidad').value     = c.cantidad     || '1';
        mPrecio.value = c.precio || '';

        mProducto.value = c.producto || '';
        mTipoEntrega.value = c.tipoEntrega || 'domicilio';
        document.getElementById('mEstado').value = c.estado || 'nuevo';
        numeroGuiaContacto = c.numeroGuia || '';

        mDepartamento.value = c.departamento || '';
        mDepartamento.dispatchEvent(new Event('change'));
        // El municipio se llena async al disparar el 'change' de arriba —
        // se asigna después de que termine de poblar las opciones.
        setTimeout(() => { mMunicipio.value = c.municipio || ''; }, 0);
    };

    const buscar = async () => {
        const telefono = telInput.value.trim();
        if (!telefono) return;

        numeroGuiaContacto = '';
        waManualHint.textContent = 'No encontramos un pedido de la landing con ese teléfono. Arma el mensaje a mano:';
        buscarBtn.disabled = true;
        try {
            const res  = await fetch((window.BASE_URL || '') + '/AdminPlantillasWa/buscarPedido?telefono=' + encodeURIComponent(telefono));
            const json = await res.json();
            const pedidos = json.pedidos || [];

            if (pedidos.length) {
                renderResultados(pedidos);
                return;
            }

            resultados.hidden = true;
            resultados.innerHTML = '';
            manualBox.hidden = false;

            if (json.contacto) {
                cargarContactoManual(json.contacto);
                waManualHint.textContent = 'No es un pedido de la landing, pero ya tenías datos guardados de este teléfono — revisa antes de enviar:';
            }
        } catch {
            resultados.hidden = true;
            manualBox.hidden = false;
        } finally {
            buscarBtn.disabled = false;
        }
    };

    buscarBtn.addEventListener('click', buscar);
    telInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); buscar(); }
    });

    resultados.addEventListener('click', e => {
        const btn = e.target.closest('[data-idx]');
        if (!btn) return;
        const p = ultimosPedidos[Number(btn.dataset.idx)];
        if (p) window.WaPicker.open(p);
    });

    document.getElementById('waComponerBtn').addEventListener('click', () => {
        const telefono = telInput.value.trim();
        if (!telefono) { alert('Escribe el teléfono primero.'); return; }

        const nombre    = document.getElementById('mNombre').value.trim();
        const apellidos = document.getElementById('mApellidos').value.trim();
        const cantidad  = document.getElementById('mCantidad').value.trim() || '1';

        const data = {
            telefono,
            nombre,
            apellidos,
            producto:     mProducto.value.trim(),
            cantidad,
            precio:       mPrecio.value.trim(),
            municipio:    mMunicipio.value.trim(),
            departamento: mDepartamento.value.trim(),
            estado:       document.getElementById('mEstado').value,
            tipoEntrega:  mTipoEntrega.value,
            numeroGuia:   numeroGuiaContacto,
        };

        window.WaPicker.open(data, {
            onSend: (mensaje, estado, guia) => {
                const fd = new FormData();
                fd.append('telefono', telefono);
                fd.append('nombre', [nombre, apellidos].filter(Boolean).join(' '));
                fd.append('estado', estado);
                fd.append('guia', guia || '');
                fd.append('csrf_token', window.__CSRF__ || '');
                // Snapshot completo — para que la próxima búsqueda de este
                // teléfono ya traiga los datos (ver ContactoManual::upsert).
                fd.append('datosNombre',       data.nombre);
                fd.append('datosApellidos',    data.apellidos);
                fd.append('datosProducto',     data.producto);
                fd.append('datosCantidad',     data.cantidad);
                fd.append('datosPrecio',       data.precio);
                fd.append('datosMunicipio',    data.municipio);
                fd.append('datosDepartamento', data.departamento);
                fd.append('datosTipoEntrega',  data.tipoEntrega);
                fetch((window.BASE_URL || '') + '/AdminPlantillasWa/registrarEnvioManual', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'fetch' },
                }).catch(() => {});

                // Producto, precio y tipo de entrega quedan puestos para el
                // siguiente cliente (mismo producto, mucha salida a oficina).
                // Lo que sí es de cada cliente se limpia y el buscador queda
                // listo para el siguiente teléfono.
                telInput.value = '';
                document.getElementById('mNombre').value = '';
                document.getElementById('mApellidos').value = '';
                document.getElementById('mCantidad').value = '1';
                mDepartamento.value = '';
                mDepartamento.dispatchEvent(new Event('change'));
                document.getElementById('mEstado').value = 'nuevo';
                telInput.focus();
            },
        });
    });

    // Panel "En oficina, esperando recogida" — junta pedidos reales y
    // contactos manuales ya notificados, para saber a quién le toca el
    // recordatorio sin tener que acordarse ni buscar uno por uno. Misma
    // tabla (pedidos-dt + DataTable) que usa Pedidos, para que se vea y
    // se maneje igual: 10 filas por defecto, con opción de mostrar más.
    const enOficinaPanel = document.getElementById('waEnOficinaPanel');
    const enOficinaTbody = document.querySelector('#tablaEnOficina tbody');
    let   ultimosEnOficina = [];

    const cargarEnOficina = async () => {
        try {
            const res   = await fetch((window.BASE_URL || '') + '/AdminPlantillasWa/enOficina');
            const json  = await res.json();
            const items = json.items || [];
            ultimosEnOficina = items;

            if (!items.length) { enOficinaPanel.hidden = true; return; }

            enOficinaPanel.hidden = false;
            enOficinaTbody.innerHTML = items.map((it, i) => `
                <tr>
                    <td>${it.nombre} ${it.apellidos}</td>
                    <td>${it.producto}</td>
                    <td>${it.telefono}</td>
                    <td data-order="${it.diasEsperando}">
                        ${it.diasEsperando} día${it.diasEsperando === 1 ? '' : 's'}
                        ${it.diasEsperando >= 2 ? '<span class="plantilla-estado-badge p-badge-recordatorio_oficina">Toca recordatorio</span>' : ''}
                    </td>
                    <td><button type="button" class="btn-primary btn-primary--soft" data-idx="${i}">Enviar recordatorio</button></td>
                </tr>
            `).join('');

            new DataTable('#tablaEnOficina', {
                pageLength: 10,
                lengthMenu: [[10, 50, 100, 200], ['10', '50', '100', '200']],
                searching: false,
                order: [[3, 'desc']],
                autoWidth: false,
                columnDefs: [{ orderable: false, targets: [4] }],
                language: {
                    lengthMenu: 'Mostrar _MENU_ por página',
                    info:       '_TOTAL_ esperando',
                    infoEmpty:  'Sin pedidos esperando',
                    paginate:   { first: '«', last: '»', next: '›', previous: '‹' },
                },
                dom: 't<"dt-bottom"lip>',
            });
        } catch {
            enOficinaPanel.hidden = true;
        }
    };

    enOficinaTbody.addEventListener('click', e => {
        const btn = e.target.closest('[data-idx]');
        if (!btn) return;
        const it = ultimosEnOficina[Number(btn.dataset.idx)];
        if (!it) return;

        const data = { ...it, estado: 'recordatorio_oficina' };
        const opts = {};

        // Los contactos manuales no tienen id de pedido: el picker solo
        // sincroniza estado/guía automáticamente cuando hay id, así que
        // aquí hay que guardar la guía a mano igual que en el compositor.
        if (it.origen === 'manual') {
            opts.onSend = (mensaje, estado, guia) => {
                const fd = new FormData();
                fd.append('telefono', it.telefono);
                fd.append('nombre', [it.nombre, it.apellidos].filter(Boolean).join(' '));
                fd.append('estado', estado);
                fd.append('guia', guia || '');
                fd.append('csrf_token', window.__CSRF__ || '');
                fd.append('datosNombre',       it.nombre);
                fd.append('datosApellidos',    it.apellidos);
                fd.append('datosProducto',     it.producto);
                fd.append('datosCantidad',     it.cantidad);
                fd.append('datosPrecio',       it.precio);
                fd.append('datosMunicipio',    it.municipio);
                fd.append('datosDepartamento', it.departamento);
                fd.append('datosTipoEntrega',  it.tipoEntrega);
                fetch((window.BASE_URL || '') + '/AdminPlantillasWa/registrarEnvioManual', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'fetch' },
                }).catch(() => {});
            };
        }

        window.WaPicker.open(data, opts);
    });

    cargarEnOficina();
})();
</script>
<script>
// Órdenes de Dropi: lista de trabajo (por avisar, esperando recogida, novedad…) e historial.
// Tabla en modo servidor: el historial puede tener miles de filas.
(() => {
    const tabla = document.getElementById('tablaOrdenesDropi');
    if (!tabla) return; // todavía no hay órdenes importadas

    const BASE = window.BASE_URL || '';
    const tarjetas  = document.querySelectorAll('#segTarjetas [data-vista]');
    const chipTodas = document.getElementById('segTodas');
    const datos     = new Map(); // id de orden → datos para armar el mensaje
    let vista  = <?= json_encode($vistaInicial ?? 'todas') ?>;
    let buscar = '', desde = '', hasta = '', temporizador = null;

    const marcarVista = () => {
        tarjetas.forEach(t => t.classList.toggle('is-active', t.dataset.vista === vista));
        chipTodas.classList.toggle('is-active', vista === 'todas');
    };

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
            data: (d) => { d.vista = vista; d.buscar = buscar; d.desde = desde; d.hasta = hasta; },
            dataSrc: (json) => {
                datos.clear();
                Object.keys(json.datos || {}).forEach(k => datos.set(Number(k), json.datos[k]));
                pintarContadores(json.contadores);
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

    const recargar = (volverAlInicio = true) => dt.ajax.reload(null, volverAlInicio);

    // ── Tarjetas = listas ──
    const cambiarVista = (v) => { vista = v; marcarVista(); recargar(); };
    tarjetas.forEach(t => {
        t.addEventListener('click', () => cambiarVista(t.dataset.vista));
        t.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); cambiarVista(t.dataset.vista); } });
    });
    chipTodas.addEventListener('click', () => cambiarVista('todas'));
    marcarVista();

    // ── Búsqueda ──
    document.getElementById('segBuscar').addEventListener('input', (e) => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => { buscar = e.target.value.trim(); recargar(); }, 300);
    });

    // ── Fecha del pedido ──
    const fechaWrap  = document.getElementById('segFechaWrap');
    const fechaLabel = document.getElementById('segFechaLabel');
    const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const aplicarFecha = (d, h, etiqueta, activo) => {
        desde = d; hasta = h;
        fechaLabel.textContent = etiqueta;
        fechaWrap.classList.toggle('dtf-wrap--active', !!(d || h));
        fechaWrap.querySelectorAll('.dfd-item').forEach(b => b.classList.toggle('is-active', b.dataset.rango === activo));
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
