<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Nuevo producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>

<body>
<?php
    $errores = $errores ?? [];
    $old     = $old     ?? [];

    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? 'admin@tuempresa.com';

    $coloresForm = $old['colores'] ?? [];
    if (is_string($coloresForm)) $coloresForm = array_map('trim', explode(',', $coloresForm));
    if (!is_array($coloresForm)) $coloresForm = [];
    $coloresForm = array_values(array_filter(array_map(fn($c) => trim((string)$c), $coloresForm), fn($c) => $c !== ''));
    if (empty($coloresForm)) $coloresForm = [''];

    $importadoDeDropi = $importadoDeDropi ?? false;
    $dropiSuggested    = $dropiSuggested ?? 0;
    $dropiVariationMap = $old['dropi_variation'] ?? [];
    $imagenUrlActual   = $old['imagen_principal_actual'] ?? '';

    // Si hubo errores, el wizard abre en paso 1 para mostrarlos
    $startStep = !empty($errores) ? 0 : 0;
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main material-main--simple">
        <?php
            $pageTitle    = 'Crear nuevo producto';
            $pageSubtitle = 'Completa los 4 pasos para registrar el producto';
            $showRangeFilter = false;
            $showSearch      = false;
            $headerCtas = [[
                'href'  => BASE_URL . '/AdminProductos/index',
                'label' => '← Volver',
                'class' => 'btn-detail',
                'icon'  => 'fa-arrow-left',
            ]];
            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <?= alert_error($errores) ?>

            <?php if ($importadoDeDropi): ?>
                <div class="admin-alert-success" style="margin-bottom:var(--sp-4)">
                    <div class="admin-alert-title"><i class="fas fa-cloud-arrow-down"></i> Datos traídos de Dropi</div>
                    <p style="margin:0">
                        Nombre, costo, foto<?= !empty($coloresForm[0]) ? ' y colores' : '' ?> ya vienen prellenados. Revísalos y define
                        vos el <strong>precio de venta</strong> —
                        <?= $dropiSuggested > 0 ? 'Dropi sugiere $' . number_format($dropiSuggested, 0, ',', '.') . ' al público.' : 'Dropi no trajo un precio sugerido.' ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="form-card wizard-card">

                <!-- ── Indicador de pasos ── -->
                <div class="wizard-header">
                    <div class="wizard-track">
                        <div class="wizard-step is-active" data-step="0">
                            <div class="ws-dot"><i class="fas fa-tag"></i></div>
                            <span class="ws-label">Básico</span>
                        </div>
                        <div class="ws-line"></div>
                        <div class="wizard-step" data-step="1">
                            <div class="ws-dot"><i class="fas fa-dollar-sign"></i></div>
                            <span class="ws-label">Precios</span>
                        </div>
                        <div class="ws-line"></div>
                        <div class="wizard-step" data-step="2">
                            <div class="ws-dot"><i class="fas fa-percent"></i></div>
                            <span class="ws-label">Descuentos</span>
                        </div>
                        <div class="ws-line"></div>
                        <div class="wizard-step" data-step="3">
                            <div class="ws-dot"><i class="fas fa-image"></i></div>
                            <span class="ws-label">Extras</span>
                        </div>
                    </div>
                </div>

                <form action="<?= BASE_URL ?>/AdminProductos/guardarNuevo"
                      method="POST"
                      id="wizardForm"
                      class="admin-form"
                      enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <!-- ══ PASO 1 — Básico ══ -->
                    <div class="wizard-panel is-active" data-panel="0">
                        <div class="wizard-panel__head">
                            <div class="wph-icon"><i class="fas fa-tag"></i></div>
                            <div class="wph-text"><h2>Información básica</h2><p>El nombre identifica el producto en toda la plataforma.</p></div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group form-group--full">
                                <label for="nombre">Nombre del producto <span class="req">*</span></label>
                                <input type="text" id="nombre" name="nombre"
                                       value="<?= htmlspecialchars($old['nombre'] ?? '') ?>"
                                       placeholder="Ej: Bolso Hobo Cuero Vegano" required>
                            </div>
                            <div class="form-group form-group--full">
                                <label class="check-row">
                                    <input type="checkbox" name="activo" value="1"
                                           <?= (!isset($old['activo']) || $old['activo']) ? 'checked' : '' ?>>
                                    <span>Producto activo (visible en landings)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ══ PASO 2 — Precios ══ -->
                    <div class="wizard-panel" data-panel="1">
                        <div class="wizard-panel__head">
                            <div class="wph-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="wph-text"><h2>Precios</h2><p>Define los precios de venta y el costo interno para calcular utilidad.</p></div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="precio_regular">Precio regular (antes) <span class="req">*</span></label>
                                <input type="number" id="precio_regular" name="precio_regular"
                                       value="<?= htmlspecialchars($old['precio_regular'] ?? '') ?>"
                                       step="100" min="0" placeholder="249900" required>
                                <small class="help">Precio tachado en la landing. Debe ser ≥ precio de venta.</small>
                            </div>
                            <div class="form-group">
                                <label for="precio_venta">Precio de venta <span class="req">*</span></label>
                                <input type="number" id="precio_venta" name="precio_venta"
                                       value="<?= htmlspecialchars($old['precio_venta'] ?? '') ?>"
                                       step="100" min="0" placeholder="199900" required>
                                <small class="help">Precio real que paga el cliente.</small>
                            </div>
                            <div class="form-group">
                                <label for="precio_proveedor">Precio proveedor <span class="req">*</span></label>
                                <input type="number" id="precio_proveedor" name="precio_proveedor"
                                       value="<?= htmlspecialchars($old['precio_proveedor'] ?? '') ?>"
                                       step="100" min="0" placeholder="80000" required>
                                <small class="help">Costo base para calcular utilidad.</small>
                            </div>
                            <div class="form-group">
                                <label for="costo_envio">Costo de envío (interno)</label>
                                <input type="number" id="costo_envio" name="costo_envio"
                                       value="<?= htmlspecialchars((string)($old['costo_envio'] ?? 0)) ?>"
                                       step="100" min="0">
                                <small class="help">No visible al cliente. Descuenta de la utilidad.</small>
                            </div>
                        </div>
                        <!-- Preview utilidad en tiempo real -->
                        <div class="wizard-kpi-bar" id="utilidadBar" style="display:none">
                            <div class="wkpi">
                                <span class="wkpi__label">Precio venta</span>
                                <strong class="wkpi__val" id="kpiVenta">—</strong>
                            </div>
                            <div class="wkpi">
                                <span class="wkpi__label">Costo total</span>
                                <strong class="wkpi__val" id="kpiCosto">—</strong>
                            </div>
                            <div class="wkpi wkpi--accent">
                                <span class="wkpi__label">Utilidad</span>
                                <strong class="wkpi__val" id="kpiUtilidad">—</strong>
                            </div>
                        </div>
                    </div>

                    <!-- ══ PASO 3 — Descuentos ══ -->
                    <div class="wizard-panel" data-panel="2">
                        <div class="wizard-panel__head">
                            <div class="wph-icon"><i class="fas fa-percent"></i></div>
                            <div class="wph-text"><h2>Estrategia de descuentos</h2><p>Configura los descuentos por cantidad para incentivar compras múltiples.</p></div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group form-group--full">
                                <input type="hidden" name="descuento_multicantidad_activo" value="0">
                                <label class="check-row">
                                    <input type="checkbox" name="descuento_multicantidad_activo" value="1"
                                           id="multiActivo"
                                           <?= (!isset($old['descuento_multicantidad_activo']) || (int)$old['descuento_multicantidad_activo'] === 1) ? 'checked' : '' ?>>
                                    <span>Activar descuentos por multicantidad</span>
                                </label>
                                <small class="help">Si lo desactivas, siempre se cobra el precio normal.</small>
                            </div>
                            <div class="form-group" id="wrap2da">
                                <label for="descuento_2da">Descuento 2ª unidad (%)</label>
                                <input type="number" id="descuento_2da" name="descuento_2da"
                                       value="<?= htmlspecialchars((string)($old['descuento_2da'] ?? 15)) ?>"
                                       min="0" max="100" step="1">
                                <small class="help">Ej: 40 → 40% OFF en la segunda unidad.</small>
                            </div>
                            <div class="form-group" id="wrap3ra">
                                <label for="descuento_3ra">Descuento 3ª+ unidad (%)</label>
                                <input type="number" id="descuento_3ra" name="descuento_3ra"
                                       value="<?= htmlspecialchars((string)($old['descuento_3ra'] ?? 20)) ?>"
                                       min="0" max="100" step="1">
                                <small class="help">Ej: 50 → 50% OFF de la tercera en adelante.</small>
                            </div>
                        </div>
                    </div>

                    <!-- ══ PASO 4 — Extras ══ -->
                    <div class="wizard-panel" data-panel="3">
                        <div class="wizard-panel__head">
                            <div class="wph-icon"><i class="fas fa-image"></i></div>
                            <div class="wph-text"><h2>Imagen y colores</h2><p>Añade la foto principal y las variantes de color disponibles.</p></div>
                        </div>
                        <div class="form-grid">

                            <!-- Imagen con preview -->
                            <div class="form-group form-group--full">
                                <label>Imagen principal (opcional)</label>
                                <div class="img-drop-zone" id="imgDropZone">
                                    <input type="file" id="imagen_principal_file"
                                           name="imagen_principal_file"
                                           accept="image/*"
                                           class="img-drop-input">
                                    <div class="img-drop-placeholder" id="imgPlaceholder">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                        <p>Arrastra la imagen aquí o <span>haz clic para seleccionar</span></p>
                                        <small>JPG · PNG · WEBP — buena resolución</small>
                                    </div>
                                    <div class="img-drop-preview" id="imgPreview">
                                        <img id="imgPreviewImg" src="" alt="Preview">
                                        <button type="button" class="img-drop-remove" id="imgRemove" title="Quitar imagen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Colores -->
                            <div class="form-group form-group--full">
                                <label>Colores disponibles (opcional)</label>
                                <div id="colorsWrap" class="colors-wrap">
                                    <?php foreach ($coloresForm as $c): ?>
                                        <div class="color-row">
                                            <input type="text" name="colores[]"
                                                   placeholder="Ej: Negro"
                                                   value="<?= htmlspecialchars($c) ?>">
                                            <button type="button" class="btn-remove-color" aria-label="Quitar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="addColorBtn" class="btn-ghost btn-add-color">
                                    <i class="fas fa-plus"></i> Agregar color
                                </button>
                                <small class="help">Si los dejas vacíos, la landing no mostrará selector de color.</small>
                            </div>

                            <!-- Dropi -->
                            <div class="form-group form-group--full">
                                <label for="dropi_product_id">ID de producto en Dropi (opcional)</label>
                                <input type="number" id="dropi_product_id" name="dropi_product_id"
                                       value="<?= htmlspecialchars($old['dropi_product_id'] ?? '') ?>" min="1"
                                       placeholder="Ej: 48213">
                                <small class="help">
                                    Vacío = este producto no se envía a Dropi al confirmar un pedido.
                                </small>
                            </div>
                            <input type="hidden" id="imagen_principal_actual" name="imagen_principal_actual"
                                   value="<?= htmlspecialchars($imagenUrlActual) ?>">

                            <?php if (!empty($dropiVariationMap)): ?>
                            <div class="form-group form-group--full">
                                <label>Variación de Dropi por color (solo si el producto es VARIABLE en Dropi)</label>
                                <div class="dropi-variations-wrap">
                                    <?php foreach ($dropiVariationMap as $colorNombre => $variationId): ?>
                                        <div class="color-row">
                                            <span class="dropi-variation-color"><?= htmlspecialchars($colorNombre) ?></span>
                                            <input type="number" min="1"
                                                   name="dropi_variation[<?= htmlspecialchars($colorNombre) ?>]"
                                                   value="<?= htmlspecialchars((string)$variationId) ?>"
                                                   placeholder="variation_id">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="help">
                                    Si renombras un color de la lista de arriba, se pierde su relación con esta
                                    variación — vuelve a escribirla aquí en ese caso.
                                </small>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- ── Navegación del wizard ── -->
                    <div class="wizard-nav">
                        <button type="button" id="wz-prev" class="btn-ghost" style="visibility:hidden">
                            <i class="fas fa-arrow-left"></i> Anterior
                        </button>
                        <span class="wizard-nav__label">Paso <strong id="wz-num">1</strong> de 4</span>
                        <div class="wizard-nav__right">
                            <button type="button" id="wz-next" class="btn-primary">
                                Siguiente <i class="fas fa-arrow-right"></i>
                            </button>
                            <button type="submit" id="wz-submit" class="btn-primary" style="display:none">
                                <i class="fas fa-save"></i> Guardar producto
                            </button>
                        </div>
                    </div>

                </form>
            </div>

        </section>
    </main>

</div>

<script src="<?= BASE_URL ?>/public/js/modal-a11y.js"></script>

<script src="<?= BASE_URL ?>/public/js/form-labels.js"></script>

<script src="<?= BASE_URL ?>/public/js/funciones.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Wizard ──────────────────────────────────────────
    const panels  = document.querySelectorAll('.wizard-panel');
    const steps   = document.querySelectorAll('.wizard-step');
    const lines   = document.querySelectorAll('.ws-line');
    const btnPrev = document.getElementById('wz-prev');
    const btnNext = document.getElementById('wz-next');
    const btnSub  = document.getElementById('wz-submit');
    const numLbl  = document.getElementById('wz-num');
    let current   = 0;

    function goTo(n) {
        panels[current].classList.remove('is-active');
        steps[current].classList.remove('is-active');

        if (n > current) steps[current].classList.add('is-done');
        else             steps[current].classList.remove('is-done');

        current = n;
        panels[current].classList.add('is-active');
        steps[current].classList.add('is-active');
        numLbl.textContent = current + 1;

        // líneas completadas
        lines.forEach((l, i) => l.classList.toggle('is-done', i < current));

        btnPrev.style.visibility = current === 0 ? 'hidden' : 'visible';
        btnNext.style.display    = current === panels.length - 1 ? 'none'  : '';
        btnSub.style.display     = current === panels.length - 1 ? ''      : 'none';
    }

    function validateCurrent() {
        const panel = panels[current];
        const required = panel.querySelectorAll('[required]');
        let ok = true;
        required.forEach(el => {
            el.classList.remove('input-error');
            if (!el.value.trim()) { el.classList.add('input-error'); ok = false; }
        });
        return ok;
    }

    btnNext.addEventListener('click', () => {
        if (!validateCurrent()) return;
        if (current < panels.length - 1) goTo(current + 1);
    });
    btnPrev.addEventListener('click', () => {
        if (current > 0) goTo(current - 1);
    });

    // clic en step completado navega directo
    steps.forEach((s, i) => {
        s.addEventListener('click', () => {
            if (i < current || s.classList.contains('is-done')) goTo(i);
        });
    });

    // ── Preview utilidad ────────────────────────────────
    const fmtCOP = n => '$' + Math.round(n).toLocaleString('es-CO');
    function recalc() {
        const venta    = parseFloat(document.getElementById('precio_venta').value)     || 0;
        const prov     = parseFloat(document.getElementById('precio_proveedor').value) || 0;
        const envio    = parseFloat(document.getElementById('costo_envio').value)      || 0;
        const utilidad = venta - prov - envio;
        const bar      = document.getElementById('utilidadBar');
        if (venta || prov) {
            bar.style.display = '';
            document.getElementById('kpiVenta').textContent    = fmtCOP(venta);
            document.getElementById('kpiCosto').textContent    = fmtCOP(prov + envio);
            document.getElementById('kpiUtilidad').textContent = fmtCOP(utilidad);
            document.getElementById('kpiUtilidad').style.color = utilidad >= 0 ? 'var(--green)' : 'var(--red)';
        } else {
            bar.style.display = 'none';
        }
    }
    ['precio_venta','precio_proveedor','costo_envio'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', recalc);
    });

    // ── Toggle descuentos ───────────────────────────────
    const chkMulti = document.getElementById('multiActivo');
    function toggleDesc() {
        const on = chkMulti.checked;
        ['wrap2da','wrap3ra'].forEach(id => {
            const w = document.getElementById(id);
            if (w) w.style.opacity = on ? '1' : '.4';
        });
    }
    if (chkMulti) { chkMulti.addEventListener('change', toggleDesc); toggleDesc(); }

    // ── Colores ─────────────────────────────────────────
    const colorsWrap = document.getElementById('colorsWrap');
    document.getElementById('addColorBtn').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'color-row';
        row.innerHTML = `<input type="text" name="colores[]" placeholder="Ej: Azul">
            <button type="button" class="btn-remove-color" aria-label="Quitar"><i class="fas fa-trash"></i></button>`;
        colorsWrap.appendChild(row);
        row.querySelector('input').focus();
    });
    colorsWrap.addEventListener('click', e => {
        const btn = e.target.closest('.btn-remove-color');
        if (!btn) return;
        const rows = colorsWrap.querySelectorAll('.color-row');
        if (rows.length <= 1) { colorsWrap.querySelector('input[name="colores[]"]').value = ''; return; }
        btn.closest('.color-row').remove();
    });

    // ── Imagen con preview ──────────────────────────────
    const zone       = document.getElementById('imgDropZone');
    const fileInput  = document.getElementById('imagen_principal_file');
    const placeholder= document.getElementById('imgPlaceholder');
    const preview    = document.getElementById('imgPreview');
    const previewImg = document.getElementById('imgPreviewImg');
    const btnRemove  = document.getElementById('imgRemove');
    const imgUrlField= document.getElementById('imagen_principal_actual');

    function showPreview(file) {
        if (!file || !file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        previewImg.src = url;
        placeholder.style.display = 'none';
        preview.style.display = 'flex';
        zone.classList.add('has-image');
    }
    function showPreviewUrl(url) {
        if (!url) return;
        previewImg.src = url;
        placeholder.style.display = 'none';
        preview.style.display = 'flex';
        zone.classList.add('has-image');
    }
    function clearPreview() {
        previewImg.src = '';
        fileInput.value = '';
        if (imgUrlField) imgUrlField.value = '';
        placeholder.style.display = '';
        preview.style.display = 'none';
        zone.classList.remove('has-image');
    }

    if (imgUrlField && imgUrlField.value) showPreviewUrl(imgUrlField.value);

    zone.addEventListener('click', e => {
        if (!e.target.closest('.img-drop-remove')) fileInput.click();
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) {
            if (imgUrlField) imgUrlField.value = ''; // el archivo nuevo reemplaza la foto de Dropi
            showPreview(fileInput.files[0]);
        }
    });
    btnRemove.addEventListener('click', e => { e.stopPropagation(); clearPreview(); });

    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('is-dragging'); });
    zone.addEventListener('dragleave', ()  => zone.classList.remove('is-dragging'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('is-dragging');
        const file = e.dataTransfer.files[0];
        if (file) { fileInput.files = e.dataTransfer.files; showPreview(file); }
    });

});
</script>

</body>
</html>
