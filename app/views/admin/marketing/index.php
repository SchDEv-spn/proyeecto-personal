<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marketing IA — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#1A3D2E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Marketing IA ─────────────────────────────────────────── */
        .mkt-wrap { max-width: 980px; margin: 0 auto; }

        /* ── Zona de upload ───────────────────────────────────────── */
        .mkt-upload-zone {
            border: 2px dashed var(--border-strong);
            border-radius: var(--r-xl);
            padding: 36px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color var(--t), background var(--t);
            background: var(--surface-2);
            margin-bottom: 20px;
            position: relative;
        }
        .mkt-upload-zone:hover,
        .mkt-upload-zone.drag-over {
            border-color: var(--green-dark);
            background: var(--green-soft);
        }
        .mkt-upload-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .mkt-upload-zone__icon {
            font-size: 2rem;
            color: var(--green-dark);
            opacity: 0.5;
            margin-bottom: 8px;
        }
        .mkt-upload-zone__label {
            font-size: 15px;
            font-weight: 600;
            color: var(--tx);
            margin-bottom: 3px;
        }
        .mkt-upload-zone__hint { font-size: 12px; color: var(--tx-muted); }

        .mkt-upload-preview {
            display: none;
            position: relative;
            border-radius: var(--r-lg);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .mkt-upload-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .mkt-upload-preview__remove {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.55);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }

        /* ── Form fields ──────────────────────────────────────────── */
        .mkt-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }
        @media (max-width: 560px) { .mkt-form-grid { grid-template-columns: 1fr; } }

        .mkt-field label {
            display: block;
            font-size: var(--text-xs);
            font-weight: 700;
            color: var(--tx-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 5px;
        }
        .mkt-field input,
        .mkt-field textarea {
            width: 100%;
            background: var(--surface-2);
            border: 1.5px solid var(--border);
            border-radius: var(--r-md);
            color: var(--tx);
            font-size: var(--text-base);
            padding: 10px 13px;
            outline: none;
            font-family: var(--font);
            transition: border-color var(--t);
            box-sizing: border-box;
        }
        .mkt-field input:focus,
        .mkt-field textarea:focus { border-color: var(--green-dark); }
        .mkt-field textarea { resize: vertical; min-height: 68px; }

        /* ── Botón generar ────────────────────────────────────────── */
        .btn-generar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 24px;
            background: var(--green-dark);
            color: #fff;
            font-size: var(--text-base);
            font-weight: 700;
            border: none;
            border-radius: var(--r-md);
            cursor: pointer;
            transition: background var(--t), transform 0.1s;
            margin-top: 6px;
        }
        .btn-generar:hover:not(:disabled) { background: var(--green-dark-h); transform: translateY(-1px); }
        .btn-generar:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-generar .spinner {
            width: 15px; height: 15px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: mkt-spin 0.7s linear infinite;
            display: none;
            flex-shrink: 0;
        }
        .btn-generar.loading .spinner { display: block; }
        .btn-generar.loading .btn-icon { display: none; }
        @keyframes mkt-spin { to { transform: rotate(360deg); } }

        /* ── Error ────────────────────────────────────────────────── */
        .mkt-error {
            background: var(--soft-red);
            border: 1px solid #fca5a5;
            border-radius: var(--r-md);
            padding: 10px 14px;
            color: var(--red);
            font-size: var(--text-sm);
            margin-top: 10px;
            display: none;
        }

        /* ── Tips ─────────────────────────────────────────────────── */
        .mkt-tips {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            flex-wrap: wrap;
        }
        .mkt-tip {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--soft-green);
            border-radius: var(--r-full);
            padding: 5px 12px;
            font-size: var(--text-xs);
            color: var(--green);
            font-weight: 600;
        }
        .mkt-tip i { font-size: 10px; }

        /* ── Resultados ───────────────────────────────────────────── */
        .mkt-results { display: none; }

        .mkt-results-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .mkt-results-head h3 {
            font-size: var(--text-lg);
            font-weight: 700;
            color: var(--tx);
            margin: 0;
        }
        .mkt-results-head p {
            font-size: var(--text-sm);
            color: var(--tx-muted);
            margin: 2px 0 0;
        }

        .mkt-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }
        @media (max-width: 780px) {
            .mkt-cards {
                grid-template-columns: 1fr;
                max-width: 360px;
            }
        }

        /* ── Ad card ──────────────────────────────────────────────── */
        .mkt-ad-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xs);
            transition: box-shadow var(--t);
        }
        .mkt-ad-card:hover { box-shadow: var(--shadow-md); }

        .mkt-ad-card__canvas-wrap { aspect-ratio: 1 / 1; }
        .mkt-ad-card canvas { width: 100%; height: 100%; display: block; }

        .mkt-ad-card__footer {
            padding: 12px 14px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .mkt-ad-card__meta { min-width: 0; }
        .mkt-ad-card__angle {
            font-size: var(--text-xs);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--green-dark);
            margin-bottom: 1px;
        }
        .mkt-ad-card__tema {
            font-size: 11px;
            color: var(--tx-dim);
        }
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: var(--r-md);
            background: var(--green-dark);
            color: #fff;
            font-size: var(--text-xs);
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: background var(--t);
            flex-shrink: 0;
        }
        .btn-download:hover { background: var(--green-dark-h); }

        /* ── No key ───────────────────────────────────────────────── */
        .mkt-no-key {
            background: var(--soft-orange);
            border: 1px solid #fcd34d;
            border-radius: var(--r-xl);
            padding: 28px;
            text-align: center;
            color: var(--orange);
        }
        .mkt-no-key i { font-size: 1.8rem; margin-bottom: 10px; display: block; }
        .mkt-no-key a { color: var(--green-dark); font-weight: 700; }

        /* ── Ángulo badge ─────────────────────────────────────────── */
        .angle-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: var(--r-full);
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .angle-badge--dolor     { background: var(--soft-red);    color: var(--red); }
        .angle-badge--urgencia  { background: var(--soft-orange);  color: var(--orange); }
        .angle-badge--transformacion { background: var(--soft-purple); color: var(--purple); }
    </style>
</head>
<body>
<?php
$usuarioNombre       = $_SESSION['usuario_nombre'] ?? 'Admin';
$usuarioEmail        = $_SESSION['usuario_email']  ?? '';
$tiene_claude_key    = $tiene_claude_key    ?? false;
$tiene_replicate_key = $tiene_replicate_key ?? false;

// Variables para _header.php
$pageTitle       = '✨ Marketing IA';
$pageSubtitle    = 'Genera creativos de anuncio listos para publicar en segundos';
$showRangeFilter = false;
$showSearch      = false;
?>

<div class="sidebar-overlay" aria-hidden="true"></div>
<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php require __DIR__ . '/../partials/_header.php'; ?>

        <section class="material-content">
            <div class="mkt-wrap">

                <?php if (!$tiene_claude_key): ?>
                <div class="mkt-no-key">
                    <i class="fas fa-key"></i>
                    <strong>Necesitas la API key de Claude</strong><br>
                    Configúrala en el editor de Landing para activar esta herramienta.<br><br>
                    <a href="<?= BASE_URL ?>/AdminLanding/index">Ir al editor → Configuración IA</a>
                </div>

                <?php else: ?>

                <!-- ═══════════════════════════════════════════
                     FORMULARIO
                ═══════════════════════════════════════════ -->
                <div class="panel" style="padding:0;overflow:hidden;">
                    <div class="panel__head" style="padding:18px 22px 14px;">
                        <h4 style="display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-camera" style="color:var(--green-dark)"></i>
                            Datos del producto
                        </h4>
                        <span class="chip">Paso 1</span>
                    </div>
                    <div class="panel__body" style="padding:0 22px 22px;">

                        <div id="mktUploadZone" class="mkt-upload-zone">
                            <input type="file" id="mktFoto" accept="image/jpeg,image/png,image/webp">
                            <div class="mkt-upload-zone__icon"><i class="fas fa-image"></i></div>
                            <div class="mkt-upload-zone__label">Arrastra la foto aquí o haz clic</div>
                            <div class="mkt-upload-zone__hint">JPG, PNG, WebP — usa foto con fondo limpio para mejor resultado</div>
                        </div>

                        <div class="mkt-upload-preview" id="mktPreviewWrap">
                            <img id="mktPreviewImg" src="" alt="preview">
                            <button type="button" class="mkt-upload-preview__remove" id="mktRemoveFoto">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="mkt-form-grid">
                            <div class="mkt-field">
                                <label>Nombre del producto</label>
                                <input type="text" id="mktNombre" placeholder="Ej: Sombrero Fedora Negro" maxlength="80">
                            </div>
                            <div class="mkt-field">
                                <label>Precio (COP)</label>
                                <input type="text" id="mktPrecio" placeholder="Ej: 115.000" maxlength="20">
                            </div>
                        </div>

                        <div class="mkt-field">
                            <label>¿Quién lo usa y para qué? <span style="font-weight:400;opacity:.6">(opcional pero potente)</span></label>
                            <textarea id="mktContexto" placeholder="Ej: Hombres entre 25-45 años que quieren verse elegantes. Envío gratis. Stock limitado a 20 unidades."></textarea>
                        </div>

                        <button type="button" class="btn-generar" id="btnGenerar">
                            <span class="btn-icon">✨</span>
                            <span class="spinner"></span>
                            Generar 3 anuncios con IA
                        </button>

                        <div class="mkt-error" id="mktError"></div>

                        <div class="mkt-tips">
                            <span class="mkt-tip"><i class="fas fa-brain"></i> Claude genera copy que ataca el dolor</span>
                            <?php if ($tiene_replicate_key): ?>
                            <span class="mkt-tip"><i class="fas fa-wand-magic-sparkles"></i> Replicate estiliza la imagen por anuncio</span>
                            <?php else: ?>
                            <span class="mkt-tip" style="background:var(--soft-orange);color:var(--orange);">
                                <i class="fas fa-image"></i> Sin key Replicate: se usa foto original
                            </span>
                            <?php endif; ?>
                            <span class="mkt-tip"><i class="fas fa-download"></i> PNG 1080×1080 listo para redes</span>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════
                     RESULTADOS
                ═══════════════════════════════════════════ -->
                <div class="mkt-results" id="mktResults">
                    <div class="mkt-results-head">
                        <div>
                            <h3>Tus anuncios listos</h3>
                            <p>3 ángulos psicológicos distintos — descarga el que más conecte con tu cliente</p>
                        </div>
                    </div>
                    <div class="mkt-cards" id="mktCards"></div>
                </div>

                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script src="<?= BASE_URL ?>/public/js/admin-sidebar.js"></script>
<script>
(function () {
    const BASE = '<?= BASE_URL ?>';

    const fotoInput   = document.getElementById('mktFoto');
    const uploadZone  = document.getElementById('mktUploadZone');
    const previewWrap = document.getElementById('mktPreviewWrap');
    const previewImg  = document.getElementById('mktPreviewImg');
    const removeBtn   = document.getElementById('mktRemoveFoto');
    const btnGenerar  = document.getElementById('btnGenerar');
    const errorEl     = document.getElementById('mktError');
    const resultsEl   = document.getElementById('mktResults');
    const cardsEl     = document.getElementById('mktCards');

    if (!btnGenerar) return;

    /* ── Upload preview ─────────────────────────────────────────── */
    function showPreview(file) {
        previewImg.src = URL.createObjectURL(file);
        previewWrap.style.display = 'block';
        uploadZone.style.display  = 'none';
    }
    function clearFoto() {
        fotoInput.value = '';
        previewImg.src  = '';
        previewWrap.style.display = 'none';
        uploadZone.style.display  = '';
    }

    fotoInput.addEventListener('change', () => { if (fotoInput.files[0]) showPreview(fotoInput.files[0]); });
    removeBtn.addEventListener('click', clearFoto);

    uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const file = e.dataTransfer?.files?.[0];
        if (file?.type.startsWith('image/')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fotoInput.files = dt.files;
            showPreview(file);
        }
    });

    /* ── Generar ────────────────────────────────────────────────── */
    btnGenerar.addEventListener('click', async () => {
        const nombre   = document.getElementById('mktNombre').value.trim();
        const precio   = document.getElementById('mktPrecio').value.trim();
        const contexto = document.getElementById('mktContexto').value.trim();

        errorEl.style.display = 'none';
        if (!nombre)            return showError('Ingresa el nombre del producto');
        if (!precio)            return showError('Ingresa el precio');
        if (!fotoInput.files[0]) return showError('Sube una foto del producto');

        btnGenerar.disabled = true;
        btnGenerar.classList.add('loading');
        resultsEl.style.display = 'none';
        cardsEl.innerHTML = '';

        const fd = new FormData();
        fd.append('foto',     fotoInput.files[0]);
        fd.append('nombre',   nombre);
        fd.append('precio',   precio);
        fd.append('contexto', contexto);

        try {
            const res  = await fetch(BASE + '/AdminMarketing/generarAnuncios', { method: 'POST', body: fd });
            let data;
            try {
                data = await res.json();
            } catch {
                const txt = await res.text().catch(() => '(sin respuesta)');
                showError('El servidor devolvió una respuesta inesperada. Revisa los logs. Detalle: ' + txt.slice(0, 200));
                return;
            }
            if (!data.ok) return showError(data.error || 'Error al generar');
            await renderResults(data);
        } catch (err) {
            showError('Error de red: ' + (err?.message || 'intenta de nuevo'));
        } finally {
            btnGenerar.disabled = false;
            btnGenerar.classList.remove('loading');
        }
    });

    function showError(msg) {
        errorEl.textContent   = msg;
        errorEl.style.display = 'block';
    }

    /* ── Render cards ───────────────────────────────────────────── */
    async function renderResults({ imageUrl, ads, precio, hasReplicate }) {
        cardsEl.innerHTML = '';

        // Cargamos todas las imágenes (cada ad puede tener su propia URL de Replicate)
        const fallbackSrc = imageUrl || previewImg.src;

        const angleConfig = {
            dolor:         { label: 'Dolor',         icon: 'fa-heart-crack',  cls: 'angle-badge--dolor' },
            urgencia:      { label: 'Urgencia',       icon: 'fa-fire',         cls: 'angle-badge--urgencia' },
            transformacion:{ label: 'Transformación', icon: 'fa-star',         cls: 'angle-badge--transformacion' },
        };
        const temaLabels = { oscuro: 'Oscuro · IA', dorado: 'Dorado · IA', vibrante: 'Vibrante · IA' };

        for (const ad of ads) {
            const aC = angleConfig[ad.angulo] || { label: ad.angulo || '?', icon: 'fa-circle', cls: 'angle-badge--dolor' };
            const isAiImage = !!ad.imageUrl && ad.imageUrl !== imageUrl;

            // Crear placeholder mientras carga
            const card = document.createElement('div');
            card.className = 'mkt-ad-card';
            card.innerHTML = `
                <div class="mkt-ad-card__canvas-wrap" style="background:#f1f3f5;display:flex;align-items:center;justify-content:center;">
                    <span style="font-size:13px;color:#9ca3af;"><i class="fas fa-spinner fa-spin"></i> Cargando...</span>
                </div>
                <div class="mkt-ad-card__footer">
                    <div class="mkt-ad-card__meta">
                        <div class="mkt-ad-card__angle">
                            <span class="angle-badge ${aC.cls}"><i class="fas ${aC.icon}"></i> ${aC.label}</span>
                        </div>
                        <div class="mkt-ad-card__tema">${temaLabels[ad.tema] || ad.tema}${isAiImage ? ' ✨' : ''}</div>
                    </div>
                    <button class="btn-download" disabled><i class="fas fa-download"></i> PNG</button>
                </div>`;
            cardsEl.appendChild(card);

            // Cargar imagen (puede ser la generada por Replicate o la original)
            const imgSrc = ad.imageUrl || fallbackSrc;
            loadImage(imgSrc).then(img => {
                const wrap = card.querySelector('.mkt-ad-card__canvas-wrap');
                wrap.innerHTML = '';

                const canvas = document.createElement('canvas');
                canvas.width = canvas.height = 1080;
                drawAd(canvas, img, ad, precio);
                wrap.appendChild(canvas);

                const dlBtn = card.querySelector('.btn-download');
                dlBtn.disabled = false;
                dlBtn.addEventListener('click', () => {
                    const a = document.createElement('a');
                    a.download = `anuncio-${ad.angulo || ad.tema}-${Date.now()}.png`;
                    a.href = canvas.toDataURL('image/png');
                    a.click();
                });
            }).catch(() => {
                // Si falla la imagen IA, usar original como fallback
                loadImage(fallbackSrc).then(img => {
                    const wrap = card.querySelector('.mkt-ad-card__canvas-wrap');
                    wrap.innerHTML = '';
                    const canvas = document.createElement('canvas');
                    canvas.width = canvas.height = 1080;
                    drawAd(canvas, img, ad, precio);
                    wrap.appendChild(canvas);
                    const dlBtn = card.querySelector('.btn-download');
                    dlBtn.disabled = false;
                    dlBtn.addEventListener('click', () => {
                        const a = document.createElement('a');
                        a.download = `anuncio-${ad.angulo || ad.tema}-${Date.now()}.png`;
                        a.href = canvas.toDataURL('image/png');
                        a.click();
                    });
                });
            });
        }

        resultsEl.style.display = 'block';
        resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload  = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    /* ── Canvas draw ────────────────────────────────────────────── */
    function drawAd(canvas, img, { headline, body, cta, tema }, precio) {
        const ctx = canvas.getContext('2d');
        const W = canvas.width, H = canvas.height;

        const palettes = {
            oscuro: {
                overlay:  [{ stop: 0.25, color: 'rgba(8,6,3,0)' }, { stop: 1, color: 'rgba(8,6,3,0.93)' }],
                headline: '#f0e8d6',
                body:     'rgba(240,232,214,0.80)',
                priceBg:  'rgba(200,168,91,0.96)',
                priceTx:  '#1a1208',
                ctaBg:    '#c8a85b',
                ctaTx:    '#1a1208',
                wmColor:  'rgba(255,255,255,0.20)',
            },
            dorado: {
                overlay:  [{ stop: 0, color: 'rgba(26,18,8,0)' }, { stop: 1, color: 'rgba(26,18,8,0.90)' }],
                headline: '#fef3c7',
                body:     'rgba(254,243,199,0.82)',
                priceBg:  'rgba(255,255,255,0.95)',
                priceTx:  '#92400e',
                ctaBg:    '#d97706',
                ctaTx:    '#fff',
                wmColor:  'rgba(255,255,255,0.22)',
            },
            vibrante: {
                overlay:  [{ stop: 0, color: 'rgba(15,5,40,0)' }, { stop: 1, color: 'rgba(15,5,40,0.92)' }],
                headline: '#ffffff',
                body:     'rgba(255,255,255,0.84)',
                priceBg:  'rgba(139,92,246,0.96)',
                priceTx:  '#ffffff',
                ctaBg:    '#7c3aed',
                ctaTx:    '#fff',
                wmColor:  'rgba(255,255,255,0.20)',
            },
        };
        const p = palettes[tema] || palettes.oscuro;

        /* 1. Imagen de fondo (cover) */
        const scale = Math.max(W / img.width, H / img.height);
        ctx.drawImage(img, (W - img.width * scale) / 2, (H - img.height * scale) / 2, img.width * scale, img.height * scale);

        /* 2. Gradiente overlay */
        const grad = ctx.createLinearGradient(0, H * 0.20, 0, H);
        p.overlay.forEach(s => grad.addColorStop(s.stop, s.color));
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        /* 3. Badge de precio (arriba derecha) */
        ctx.font = 'bold 34px Inter,sans-serif';
        const priceStr  = '$' + String(precio).trim() + ' COP';
        const pw        = ctx.measureText(priceStr).width;
        const bpad      = 22, bh = 54, bw = pw + bpad * 2;
        const bx = W - bw - 30, by = 30;
        rrect(ctx, bx, by, bw, bh, 14, p.priceBg);
        ctx.fillStyle    = p.priceTx;
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(priceStr, bx + bw / 2, by + bh / 2);

        /* 4. Headline */
        const HL_SIZE = 66, HL_LH = 78;
        ctx.textAlign    = 'left';
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle    = p.headline;
        ctx.font         = `bold ${HL_SIZE}px Inter,sans-serif`;
        const hlY        = H - 260;
        const hlLines    = wrapText(ctx, headline.toUpperCase(), 50, hlY, W - 100, HL_LH);

        /* 5. Body */
        const BODY_SIZE = 36, BODY_LH = 46;
        ctx.fillStyle = p.body;
        ctx.font      = `400 ${BODY_SIZE}px Inter,sans-serif`;
        const bodyY   = hlY + hlLines * HL_LH + 16;
        const bLines  = wrapText(ctx, body, 50, bodyY, W - 100, BODY_LH);

        /* 6. CTA pill */
        const CTA_SIZE = 32;
        ctx.font = `bold ${CTA_SIZE}px Inter,sans-serif`;
        const ctaW   = ctx.measureText(cta).width + 60;
        const ctaH   = 64;
        const ctaX   = 50;
        const ctaY   = bodyY + bLines * BODY_LH + 28;
        rrect(ctx, ctaX, ctaY, ctaW, ctaH, 32, p.ctaBg);
        ctx.fillStyle    = p.ctaTx;
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(cta, ctaX + ctaW / 2, ctaY + ctaH / 2);

        /* 7. Marca de agua */
        ctx.fillStyle    = p.wmColor;
        ctx.font         = '22px Inter,sans-serif';
        ctx.textAlign    = 'right';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText('fedoramfb.com', W - 30, H - 22);
    }

    function rrect(ctx, x, y, w, h, r, fill) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.arcTo(x + w, y, x + w, y + h, r);
        ctx.arcTo(x + w, y + h, x, y + h, r);
        ctx.arcTo(x, y + h, x, y, r);
        ctx.arcTo(x, y, x + w, y, r);
        ctx.closePath();
        ctx.fillStyle = fill;
        ctx.fill();
    }

    function wrapText(ctx, text, x, y, maxW, lh) {
        const words = text.split(' ');
        let line = '', lines = 0;
        for (const w of words) {
            const test = line ? line + ' ' + w : w;
            if (ctx.measureText(test).width > maxW && line) {
                ctx.fillText(line, x, y + lines * lh);
                lines++; line = w;
            } else { line = test; }
        }
        if (line) { ctx.fillText(line, x, y + lines * lh); lines++; }
        return lines;
    }

})();
</script>
</body>
</html>
