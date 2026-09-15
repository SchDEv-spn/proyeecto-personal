<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantillas WhatsApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
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
    'en_oficina'           => 'En oficina',
    'recordatorio_oficina' => 'Recordatorio de recogida',
    'entregado'            => 'Entregado',
    'cancelado'            => 'Cancelado',
];
?>

<div class="sidebar-overlay" aria-hidden="true"></div>
<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
        $pageTitle    = 'Plantillas de WhatsApp';
        $pageSubtitle = 'Personaliza el mensaje por cada etapa del pedido';
        $showRangeFilter = false;
        $showSearch      = false;
        require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <!-- Compositor de mensajes -->
            <div class="panel" id="waComposerPanel" style="padding:1.1rem 1.25rem 1.25rem;margin-bottom:1rem;">
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
                    <p class="wa-manual-hint">No encontramos un pedido de la landing con ese teléfono. Arma el mensaje a mano:</p>
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
                                <option value="oficina">Oficina (Interrapidísimo)</option>
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

            <!-- Guía de flujo de trabajo -->
            <div class="panel" style="padding:1rem 1.25rem 1rem;margin-bottom:1rem;">
                <h2 style="font-size:13px;font-weight:700;margin-bottom:.75rem;">Flujo recomendado de mensajes</h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div style="background:var(--gold-ghost);border-radius:12px;padding:.85rem 1rem;border-left:3px solid var(--gold);">
                        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--gold);margin-bottom:.5rem;">📦 Envío a domicilio (Envia)</p>
                        <div style="font-size:12.5px;line-height:2;color:var(--tx-secondary);">
                            <span class="plantilla-estado-badge p-badge-nuevo" style="font-size:10px;">Nuevo</span> Recibimos tu pedido<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Enviado</span> Guía + link Envia<br>
                            <span class="plantilla-estado-badge p-badge-entregado" style="font-size:10px;">Entregado</span> ¿Cómo llegó? + foto
                        </div>
                    </div>
                    <div style="background:var(--warn-bg);border-radius:12px;padding:.85rem 1rem;border-left:3px solid var(--warn);">
                        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--warn);margin-bottom:.5rem;">🏢 Retiro en oficina (Interrapidísimo)</p>
                        <div style="font-size:12.5px;line-height:2;color:var(--tx-secondary);">
                            <span class="plantilla-estado-badge p-badge-nuevo" style="font-size:10px;">Nuevo</span> Recibimos tu pedido<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Enviado</span> Despachado con guía<br>
                            <span class="plantilla-estado-badge p-badge-en_oficina" style="font-size:10px;">En oficina</span> Listo para recoger<br>
                            <span class="plantilla-estado-badge p-badge-recordatorio_oficina" style="font-size:10px;">Recordatorio</span> Antes de que se devuelva (5 días hábiles)<br>
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
    const mProducto     = document.getElementById('mProducto');
    const mPrecio       = document.getElementById('mPrecio');
    const mDepartamento = document.getElementById('mDepartamento');
    const mMunicipio    = document.getElementById('mMunicipio');

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

    let ultimosPedidos = [];

    const renderResultados = (pedidos) => {
        ultimosPedidos = pedidos;

        if (!pedidos.length) {
            resultados.hidden   = true;
            resultados.innerHTML = '';
            manualBox.hidden    = false;
            return;
        }

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

    const buscar = async () => {
        const telefono = telInput.value.trim();
        if (!telefono) return;

        buscarBtn.disabled = true;
        try {
            const res  = await fetch((window.BASE_URL || '') + '/AdminPlantillasWa/buscarPedido?telefono=' + encodeURIComponent(telefono));
            const json = await res.json();
            renderResultados(json.pedidos || []);
        } catch {
            renderResultados([]);
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

        const data = {
            telefono,
            nombre,
            apellidos,
            producto:     mProducto.value.trim(),
            cantidad:     document.getElementById('mCantidad').value.trim() || '1',
            precio:       mPrecio.value.trim(),
            municipio:    mMunicipio.value.trim(),
            departamento: mDepartamento.value.trim(),
            estado:       document.getElementById('mEstado').value,
            tipoEntrega:  document.getElementById('mTipoEntrega').value,
        };

        window.WaPicker.open(data, {
            onSend: (mensaje, estado) => {
                const fd = new FormData();
                fd.append('telefono', telefono);
                fd.append('nombre', [nombre, apellidos].filter(Boolean).join(' '));
                fd.append('estado', estado);
                fd.append('csrf_token', window.__CSRF__ || '');
                fetch((window.BASE_URL || '') + '/AdminPlantillasWa/registrarEnvioManual', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'fetch' },
                }).catch(() => {});
            },
        });
    });
})();
</script>
</body>
</html>
