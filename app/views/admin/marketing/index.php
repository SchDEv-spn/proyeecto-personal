<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Marketing IA — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Marketing IA page ───────────────────────────────────── */
        .mkt-wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 0 60px;
        }

        .mkt-header {
            margin-bottom: 28px;
        }
        .mkt-header h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-primary, #f0e8d6);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 4px;
        }
        .mkt-header h1 i { color: var(--gold, #c8a85b); font-size: 1.3rem; }
        .mkt-header p {
            color: var(--text-secondary, #a89070);
            font-size: 14px;
            margin: 0;
        }

        /* ── Form card ────────────────────────────────────────────── */
        .mkt-form-card {
            background: var(--surface, #1e1a14);
            border: 1px solid var(--border, rgba(200,168,91,0.2));
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .mkt-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        @media (max-width: 620px) {
            .mkt-form-grid { grid-template-columns: 1fr; }
        }

        .mkt-field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary, #a89070);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }
        .mkt-field input,
        .mkt-field textarea {
            width: 100%;
            background: var(--bg-input, rgba(255,255,255,0.05));
            border: 1.5px solid var(--border, rgba(200,168,91,0.2));
            border-radius: 10px;
            color: var(--text-primary, #f0e8d6);
            font-size: 15px;
            padding: 11px 14px;
            outline: none;
            font-family: inherit;
            transition: border-color 0.18s;
            box-sizing: border-box;
        }
        .mkt-field input:focus,
        .mkt-field textarea:focus {
            border-color: var(--gold, #c8a85b);
        }
        .mkt-field textarea { resize: vertical; min-height: 72px; }

        /* ── Upload zona ──────────────────────────────────────────── */
        .mkt-upload-zone {
            border: 2px dashed var(--border, rgba(200,168,91,0.25));
            border-radius: 14px;
            padding: 32px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            background: var(--bg-input, rgba(255,255,255,0.03));
            margin-bottom: 16px;
            position: relative;
        }
        .mkt-upload-zone:hover,
        .mkt-upload-zone.drag-over {
            border-color: var(--gold, #c8a85b);
            background: rgba(200,168,91,0.06);
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
            font-size: 2.4rem;
            color: var(--gold, #c8a85b);
            opacity: 0.7;
            margin-bottom: 10px;
        }
        .mkt-upload-zone__label {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary, #f0e8d6);
            margin-bottom: 4px;
        }
        .mkt-upload-zone__hint {
            font-size: 12px;
            color: var(--text-secondary, #a89070);
        }
        .mkt-upload-preview {
            display: none;
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            max-height: 200px;
        }
        .mkt-upload-preview img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
            border-radius: 12px;
        }
        .mkt-upload-preview__remove {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.6);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        /* ── Botón generar ────────────────────────────────────────── */
        .btn-generar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--gold, #c8a85b), #e2b96a);
            color: #1a1208;
            font-size: 15px;
            font-weight: 800;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            letter-spacing: 0.03em;
            transition: opacity 0.18s, transform 0.1s;
            margin-top: 4px;
        }
        .btn-generar:hover:not(:disabled) { opacity: 0.92; transform: translateY(-1px); }
        .btn-generar:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .btn-generar .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0,0,0,0.25);
            border-top-color: #1a1208;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        .btn-generar.loading .spinner { display: block; }
        .btn-generar.loading .btn-icon { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Error banner ─────────────────────────────────────────── */
        .mkt-error {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.35);
            border-radius: 10px;
            padding: 12px 16px;
            color: #fca5a5;
            font-size: 14px;
            margin-top: 12px;
            display: none;
        }

        /* ── Resultados ───────────────────────────────────────────── */
        .mkt-results {
            display: none;
        }
        .mkt-results__title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary, #f0e8d6);
            margin: 0 0 16px;
        }
        .mkt-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 780px) {
            .mkt-cards { grid-template-columns: 1fr; max-width: 380px; }
        }

        /* ── Ad card ──────────────────────────────────────────────── */
        .mkt-ad-card {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border, rgba(200,168,91,0.2));
            background: var(--surface, #1e1a14);
        }
        .mkt-ad-card__canvas-wrap {
            position: relative;
            aspect-ratio: 1 / 1;
        }
        .mkt-ad-card canvas {
            width: 100%;
            height: 100%;
            display: block;
        }
        .mkt-ad-card__footer {
            padding: 12px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .mkt-ad-card__tema {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary, #a89070);
        }
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 8px;
            background: var(--gold, #c8a85b);
            color: #1a1208;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .btn-download:hover { opacity: 0.85; }

        /* ── No key notice ────────────────────────────────────────── */
        .mkt-no-key {
            background: rgba(200,168,91,0.08);
            border: 1px solid var(--gold-border, rgba(200,168,91,0.25));
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: var(--text-secondary, #a89070);
            font-size: 14px;
        }
        .mkt-no-key a {
            color: var(--gold, #c8a85b);
            font-weight: 600;
        }

        /* ── Tips row ─────────────────────────────────────────────── */
        .mkt-tips {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .mkt-tip {
            background: rgba(200,168,91,0.07);
            border: 1px solid var(--border, rgba(200,168,91,0.15));
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: var(--text-secondary, #a89070);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .mkt-tip i { color: var(--gold, #c8a85b); }
    </style>
</head>
<body>
<?php
$usuarioNombre   = $_SESSION['usuario_nombre'] ?? 'Admin';
$usuarioEmail    = $_SESSION['usuario_email']  ?? '';
$tiene_claude_key    = $tiene_claude_key    ?? false;
$tiene_replicate_key = $tiene_replicate_key ?? false;
?>

<div class="sidebar-overlay" aria-hidden="true"></div>
<div class="app-shell">

    <?php include __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="main-content">
        <div class="topbar">
            <button class="topbar-menu-btn" aria-label="Menú">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-title">Marketing IA</div>
            <div class="topbar-user">
                <span><?= htmlspecialchars($usuarioNombre) ?></span>
                <i class="fas fa-user-circle" style="color:var(--gold,#c8a85b)"></i>
            </div>
        </div>

        <div class="content-area">
            <div class="mkt-wrap">

                <div class="mkt-header">
                    <h1><i class="fas fa-bullhorn"></i> Generador de Anuncios IA</h1>
                    <p>Sube la foto de tu producto, ingresa el precio y la IA genera 3 creativos listos para publicar.</p>
                </div>

                <?php if (!$tiene_claude_key): ?>
                <div class="mkt-no-key">
                    <i class="fas fa-key" style="font-size:1.5rem;color:var(--gold,#c8a85b);display:block;margin-bottom:8px"></i>
                    Necesitas configurar tu <strong>API key de Claude</strong> para usar esta herramienta.<br>
                    <a href="<?= BASE_URL ?>/AdminLanding/index">Ir al editor de Landing → Configuración IA</a>
                </div>
                <?php else: ?>

                <!-- Formulario -->
                <div class="mkt-form-card">
                    <div id="mktUploadZone" class="mkt-upload-zone">
                        <input type="file" id="mktFoto" accept="image/jpeg,image/png,image/webp">
                        <div class="mkt-upload-zone__icon"><i class="fas fa-image"></i></div>
                        <div class="mkt-upload-zone__label">Sube la foto del producto</div>
                        <div class="mkt-upload-zone__hint">JPG, PNG o WebP — arrastra aquí o haz clic</div>
                    </div>
                    <div class="mkt-upload-preview" id="mktPreviewWrap">
                        <img id="mktPreviewImg" src="" alt="preview">
                        <button type="button" class="mkt-upload-preview__remove" id="mktRemoveFoto" title="Quitar foto">
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
                        <label>Contexto adicional <span style="opacity:.5;font-weight:400">(opcional)</span></label>
                        <textarea id="mktContexto" placeholder="Ej: Para hombres elegantes. Envío gratis. Edición limitada."></textarea>
                    </div>

                    <button type="button" class="btn-generar" id="btnGenerar">
                        <span class="btn-icon">✨</span>
                        <span class="spinner"></span>
                        Generar anuncios con IA
                    </button>

                    <div class="mkt-error" id="mktError"></div>

                    <div class="mkt-tips">
                        <div class="mkt-tip"><i class="fas fa-lightbulb"></i> Foto con fondo limpio = mejores resultados</div>
                        <div class="mkt-tip"><i class="fas fa-download"></i> Descarga cada anuncio como PNG listo para subir</div>
                        <div class="mkt-tip"><i class="fas fa-redo"></i> Puedes generar varias veces para variantes distintas</div>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="mkt-results" id="mktResults">
                    <p class="mkt-results__title">✅ Anuncios generados — elige el que más te guste</p>
                    <div class="mkt-cards" id="mktCards"></div>
                </div>

                <?php endif; ?>
            </div>
        </div><!-- /.content-area -->
    </main>
</div><!-- /.app-shell -->

<script src="<?= BASE_URL ?>/public/js/admin-sidebar.js"></script>
<script>
(function () {
    const BASE = '<?= BASE_URL ?>';

    const fotoInput    = document.getElementById('mktFoto');
    const uploadZone   = document.getElementById('mktUploadZone');
    const previewWrap  = document.getElementById('mktPreviewWrap');
    const previewImg   = document.getElementById('mktPreviewImg');
    const removeBtn    = document.getElementById('mktRemoveFoto');
    const btnGenerar   = document.getElementById('btnGenerar');
    const errorEl      = document.getElementById('mktError');
    const resultsEl    = document.getElementById('mktResults');
    const cardsEl      = document.getElementById('mktCards');

    if (!btnGenerar) return; // no claude key

    // ── Upload preview ──────────────────────────────────────────
    function showPreview(file) {
        const url = URL.createObjectURL(file);
        previewImg.src = url;
        previewWrap.style.display = 'block';
        uploadZone.style.display  = 'none';
    }
    function clearFoto() {
        fotoInput.value      = '';
        previewImg.src       = '';
        previewWrap.style.display = 'none';
        uploadZone.style.display  = '';
    }

    fotoInput.addEventListener('change', () => {
        if (fotoInput.files[0]) showPreview(fotoInput.files[0]);
    });
    removeBtn.addEventListener('click', clearFoto);

    // Drag & drop
    uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
    uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const file = e.dataTransfer?.files?.[0];
        if (file && file.type.startsWith('image/')) {
            const dt = new DataTransfer();
            dt.items.add(file);
            fotoInput.files = dt.files;
            showPreview(file);
        }
    });

    // ── Generar ────────────────────────────────────────────────
    btnGenerar.addEventListener('click', async () => {
        const nombre   = document.getElementById('mktNombre').value.trim();
        const precio   = document.getElementById('mktPrecio').value.trim();
        const contexto = document.getElementById('mktContexto').value.trim();

        errorEl.style.display = 'none';

        if (!nombre) { showError('Ingresa el nombre del producto'); return; }
        if (!precio)  { showError('Ingresa el precio'); return; }
        if (!fotoInput.files[0]) { showError('Sube una foto del producto'); return; }

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
            const data = await res.json();

            if (!data.ok) { showError(data.error || 'Error al generar'); return; }

            await renderResults(data);
        } catch (err) {
            showError('Error de conexión. Intenta de nuevo.');
        } finally {
            btnGenerar.disabled = false;
            btnGenerar.classList.remove('loading');
        }
    });

    function showError(msg) {
        errorEl.textContent    = msg;
        errorEl.style.display  = 'block';
    }

    // ── Canvas rendering ────────────────────────────────────────
    async function renderResults({ imageUrl, ads, nombre, precio }) {
        cardsEl.innerHTML = '';

        // Cargar imagen
        const img = await loadImage(imageUrl || previewImg.src);

        const temaLabels = { oscuro: 'Estilo Oscuro', dorado: 'Estilo Dorado', vibrante: 'Estilo Vibrante' };

        for (const ad of ads) {
            const card = document.createElement('div');
            card.className = 'mkt-ad-card';

            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'mkt-ad-card__canvas-wrap';

            const canvas = document.createElement('canvas');
            canvas.width  = 1080;
            canvas.height = 1080;

            drawAd(canvas, img, ad, precio);

            canvasWrap.appendChild(canvas);

            const footer = document.createElement('div');
            footer.className = 'mkt-ad-card__footer';
            footer.innerHTML = `
                <span class="mkt-ad-card__tema">${temaLabels[ad.tema] || ad.tema}</span>
                <button class="btn-download" data-tema="${ad.tema}">
                    <i class="fas fa-download"></i> Descargar
                </button>`;

            footer.querySelector('.btn-download').addEventListener('click', () => {
                const link  = document.createElement('a');
                link.download = `anuncio-${ad.tema}-${Date.now()}.png`;
                link.href     = canvas.toDataURL('image/png');
                link.click();
            });

            card.appendChild(canvasWrap);
            card.appendChild(footer);
            cardsEl.appendChild(card);
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
            img.src     = src;
        });
    }

    function drawAd(canvas, img, { headline, body, cta, tema }, precio) {
        const ctx = canvas.getContext('2d');
        const W = canvas.width;
        const H = canvas.height;

        // ── Paletas por tema ─────────────────────────────────────
        const palettes = {
            oscuro: {
                gradFrom: 'rgba(10,8,5,0)',
                gradTo:   'rgba(10,8,5,0.92)',
                headline: '#f0e8d6',
                body:     'rgba(240,232,214,0.78)',
                priceBg:  'rgba(200,168,91,0.95)',
                priceText:'#1a1208',
                ctaBg:    '#c8a85b',
                ctaText:  '#1a1208',
                badge:    '#c8a85b',
            },
            dorado: {
                gradFrom: 'rgba(26,18,8,0)',
                gradTo:   'rgba(26,18,8,0.88)',
                headline: '#f5dfa0',
                body:     'rgba(245,223,160,0.8)',
                priceBg:  'rgba(255,255,255,0.93)',
                priceText:'#1a1208',
                ctaBg:    '#e2b96a',
                ctaText:  '#1a1208',
                badge:    '#e2b96a',
            },
            vibrante: {
                gradFrom: 'rgba(15,5,40,0)',
                gradTo:   'rgba(15,5,40,0.90)',
                headline: '#ffffff',
                body:     'rgba(255,255,255,0.82)',
                priceBg:  'rgba(139,92,246,0.95)',
                priceText:'#ffffff',
                ctaBg:    '#8b5cf6',
                ctaText:  '#ffffff',
                badge:    '#a78bfa',
            },
        };
        const p = palettes[tema] || palettes.oscuro;

        // 1. Foto de fondo (cover)
        const scale  = Math.max(W / img.width, H / img.height);
        const sw     = img.width  * scale;
        const sh     = img.height * scale;
        const sx     = (W - sw) / 2;
        const sy     = (H - sh) / 2;
        ctx.drawImage(img, sx, sy, sw, sh);

        // 2. Gradiente overlay (ocupa 65% inferior)
        const grad = ctx.createLinearGradient(0, H * 0.32, 0, H);
        grad.addColorStop(0, p.gradFrom);
        grad.addColorStop(1, p.gradTo);
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, W, H);

        // 3. Precio — badge superior derecho
        const priceText = '$' + precio.replace(/\./g, '.').trim() + ' COP';
        ctx.font = 'bold 36px Inter, sans-serif';
        const ptw = ctx.measureText(priceText).width;
        const badgePad = 20;
        const badgeW = ptw + badgePad * 2;
        const badgeH = 56;
        const badgeX = W - badgeW - 32;
        const badgeY = 32;
        roundRect(ctx, badgeX, badgeY, badgeW, badgeH, 14, p.priceBg);
        ctx.fillStyle = p.priceText;
        ctx.textAlign  = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(priceText, badgeX + badgeW / 2, badgeY + badgeH / 2);

        // 4. Headline
        ctx.textAlign    = 'left';
        ctx.textBaseline = 'alphabetic';
        ctx.fillStyle    = p.headline;
        ctx.font         = 'bold 68px Inter, sans-serif';
        const headlineY  = H - 230;
        wrapText(ctx, headline.toUpperCase(), 52, headlineY, W - 104, 80);

        // 5. Body
        ctx.fillStyle = p.body;
        ctx.font      = '400 38px Inter, sans-serif';
        const bodyLines = wrapText(ctx, body, 52, headlineY + 100, W - 104, 48, true);
        const bodyBottomY = headlineY + 100 + bodyLines * 48;

        // 6. CTA pill
        const ctaFont = 'bold 32px Inter, sans-serif';
        ctx.font = ctaFont;
        const ctaW = ctx.measureText(cta).width + 64;
        const ctaH = 66;
        const ctaX = 52;
        const ctaY = bodyBottomY + 28;
        roundRect(ctx, ctaX, ctaY, ctaW, ctaH, 33, p.ctaBg);
        ctx.fillStyle    = p.ctaText;
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'middle';
        ctx.font         = ctaFont;
        ctx.fillText(cta, ctaX + ctaW / 2, ctaY + ctaH / 2);

        // 7. Marca de agua discreta
        ctx.fillStyle    = 'rgba(255,255,255,0.22)';
        ctx.font         = '22px Inter, sans-serif';
        ctx.textAlign    = 'right';
        ctx.textBaseline = 'alphabetic';
        ctx.fillText('fedoramfb.com', W - 32, H - 24);
    }

    function roundRect(ctx, x, y, w, h, r, fill) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
        ctx.fillStyle = fill;
        ctx.fill();
    }

    // Dibuja texto con wrapping; retorna líneas dibujadas
    function wrapText(ctx, text, x, y, maxW, lineH, returnOnly = false) {
        const words = text.split(' ');
        let line  = '';
        let lines = 0;
        for (const word of words) {
            const test = line ? line + ' ' + word : word;
            if (ctx.measureText(test).width > maxW && line) {
                if (!returnOnly) ctx.fillText(line, x, y + lines * lineH);
                lines++;
                line = word;
            } else {
                line = test;
            }
        }
        if (line) {
            if (!returnOnly) ctx.fillText(line, x, y + lines * lineH);
            lines++;
        }
        return lines;
    }

})();
</script>
</body>
</html>
