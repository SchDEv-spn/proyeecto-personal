<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantillas WhatsApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/tienda_mvc/public/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .plantillas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem;
            margin-top: 1.5rem;
        }

        .plantilla-card {
            background: var(--surface, #fff);
            border-radius: 16px;
            border: 1px solid rgba(93,104,255,.13);
            padding: 1.25rem 1.4rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: .9rem;
            transition: box-shadow .2s;
        }
        .plantilla-card:hover { box-shadow: 0 6px 24px rgba(93,104,255,.1); }

        .plantilla-card-head {
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .plantilla-estado-badge {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .05em;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .p-badge-nuevo      { background:rgba(93,104,255,.14); color:#5d68ff; }
        .p-badge-contactado { background:rgba(34,211,238,.14); color:#0891b2; }
        .p-badge-confirmado { background:rgba(34,197,94,.14);  color:#16a34a; }
        .p-badge-enviado    { background:rgba(245,158,11,.14); color:#d97706; }
        .p-badge-en_oficina { background:rgba(245,158,11,.22); color:#b45309; }
        .p-badge-entregado  { background:rgba(16,185,129,.14); color:#059669; }
        .p-badge-cancelado  { background:rgba(255,59,48,.14);  color:#dc2626; }

        .plantilla-field label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--muted, #9ca3af);
            margin-bottom: 5px;
        }

        .plantilla-field input,
        .plantilla-field textarea {
            width: 100%;
            background: rgba(18,24,40,.04);
            border: 1px solid rgba(18,24,40,.1);
            border-radius: 10px;
            padding: 9px 12px;
            font-size: 13.5px;
            font-family: inherit;
            color: inherit;
            resize: vertical;
            transition: border-color .15s, box-shadow .15s;
            box-sizing: border-box;
        }
        .plantilla-field input:focus,
        .plantilla-field textarea:focus {
            outline: none;
            border-color: #5d68ff;
            box-shadow: 0 0 0 3px rgba(93,104,255,.12);
        }

        .vars-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 2px;
        }

        .var-chip {
            background: rgba(93,104,255,.1);
            color: #5d68ff;
            border: 1px solid rgba(93,104,255,.2);
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 700;
            font-family: monospace;
            padding: 2px 8px;
            cursor: pointer;
            transition: background .15s;
            user-select: none;
        }
        .var-chip:hover { background: rgba(93,104,255,.2); }

        .vars-hint {
            font-size: 11px;
            color: var(--muted, #9ca3af);
            margin-bottom: 5px;
        }

        .plantillas-footer {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(18,24,40,.07);
        }

        .btn-guardar-plantillas {
            background: linear-gradient(135deg,#5d68ff,#7c3aed);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 11px 28px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
        }
        .btn-guardar-plantillas:hover   { opacity: .9; }
        .btn-guardar-plantillas:active  { transform: scale(.97); }
        .btn-guardar-plantillas:disabled { opacity: .5; cursor: not-allowed; }

        .saved-banner {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: rgba(34,197,94,.12);
            color: #16a34a;
            border: 1px solid rgba(34,197,94,.25);
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .preview-box {
            background: rgba(18,24,40,.03);
            border: 1px dashed rgba(18,24,40,.12);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            color: var(--muted, #6b7280);
            white-space: pre-wrap;
            min-height: 48px;
        }

        @media (max-width: 600px) {
            .plantillas-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php
$plantillas    = $plantillas    ?? [];
$saved         = $saved         ?? false;
$usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
$usuarioEmail  = $_SESSION['usuario_email']  ?? '';

$estados = [
    'nuevo'      => 'Nuevo',
    'contactado' => 'Contactado',
    'confirmado' => 'Confirmado',
    'enviado'    => 'Enviado',
    'en_oficina' => 'En oficina',
    'entregado'  => 'Entregado',
    'cancelado'  => 'Cancelado',
];

$vars = ['{nombre}', '{apellidos}', '{producto}', '{cantidad}', '{precio}', '{municipio}', '{departamento}'];
$varsAuto  = ['{transportadora}', '{rastreo}'];   // se resuelven por tipo de entrega
$varManual = ['{guia}'];                           // el admin las completa antes de enviar

$previewData = [
    '{nombre}'       => 'Juan',
    '{apellidos}'    => 'Pérez',
    '{producto}'     => 'Fedora Clásica',
    '{cantidad}'     => '2',
    '{precio}'       => '$120.000',
    '{municipio}'    => 'Bogotá',
    '{departamento}' => 'Cundinamarca',
];

$resolvePreview = fn(string $msg): string => str_replace(
    array_keys($previewData), array_values($previewData), $msg
);
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

            <?php if ($saved): ?>
                <div class="saved-banner" style="margin-bottom:1rem;">
                    <i class="fas fa-check-circle"></i>
                    Plantillas guardadas correctamente.
                </div>
            <?php endif; ?>

            <!-- Guía de flujo de trabajo -->
            <div class="panel" style="padding:1rem 1.25rem 1rem;margin-bottom:1rem;">
                <h4 style="font-size:13px;font-weight:700;margin-bottom:.75rem;">Flujo recomendado de mensajes</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div style="background:rgba(93,104,255,.06);border-radius:12px;padding:.85rem 1rem;border-left:3px solid #5d68ff;">
                        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#5d68ff;margin-bottom:.5rem;">📦 Envío a domicilio (Envia)</p>
                        <div style="font-size:12.5px;line-height:2;color:#374151;">
                            <span class="plantilla-estado-badge p-badge-nuevo" style="font-size:10px;">Nuevo</span> Recibimos tu pedido<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Enviado</span> Guía + link Envia<br>
                            <span class="plantilla-estado-badge p-badge-entregado" style="font-size:10px;">Entregado</span> ¿Cómo llegó? + foto
                        </div>
                    </div>
                    <div style="background:rgba(245,158,11,.06);border-radius:12px;padding:.85rem 1rem;border-left:3px solid #f59e0b;">
                        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#b45309;margin-bottom:.5rem;">🏢 Retiro en oficina (Interrapidísimo)</p>
                        <div style="font-size:12.5px;line-height:2;color:#374151;">
                            <span class="plantilla-estado-badge p-badge-nuevo" style="font-size:10px;">Nuevo</span> Recibimos tu pedido<br>
                            <span class="plantilla-estado-badge p-badge-enviado" style="font-size:10px;">Enviado</span> Despachado con guía<br>
                            <span class="plantilla-estado-badge p-badge-en_oficina" style="font-size:10px;">En oficina</span> Listo para recoger<br>
                            <span class="plantilla-estado-badge p-badge-entregado" style="font-size:10px;">Entregado</span> ¿Cómo llegó? + foto
                        </div>
                    </div>
                </div>
                <p style="font-size:11px;color:var(--muted);margin-top:.7rem;">
                    💡 En el picker de WhatsApp, <strong>{transportadora}</strong> y <strong>{rastreo}</strong> se llenan solos según el tipo de entrega del pedido. Solo debes escribir el <strong>{guia}</strong>.
                </p>
            </div>

            <div class="panel" style="padding:1rem 1.25rem .9rem;">
                <p style="font-size:13px;color:var(--muted);margin-bottom:.6rem;">
                    Haz clic en una variable para insertarla en el mensaje. El botón WhatsApp de cada pedido
                    usará la plantilla del estado actual y reemplazará las variables automáticamente.
                </p>
                <div class="vars-strip">
                    <?php foreach ($vars as $v): ?>
                        <span class="var-chip" title="Clic para insertar"><?= $v ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:11px;color:var(--muted);margin:.6rem 0 .3rem;font-weight:600;">
                    Auto según tipo de entrega (domicilio → Envia · oficina → Interrapidísimo):
                </p>
                <div class="vars-strip">
                    <?php foreach ($varsAuto as $v): ?>
                        <span class="var-chip" title="Se resuelve automáticamente"><?= $v ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:11px;color:var(--muted);margin:.6rem 0 .3rem;font-weight:600;">
                    Manual — el admin completa este dato antes de enviar:
                </p>
                <div class="vars-strip">
                    <?php foreach ($varManual as $v): ?>
                        <span class="var-chip" style="border-color:rgba(245,158,11,.4);background:rgba(245,158,11,.1);color:#d97706;"
                              title="Lo completas en el picker antes de enviar"><?= $v ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <form action="/tienda_mvc/AdminPlantillasWa/guardar" method="POST" id="formPlantillas">
                <?= csrf_field() ?>

                <div class="plantillas-grid">
                    <?php foreach ($estados as $key => $label):
                        $p       = $plantillas[$key] ?? [];
                        $titulo  = $p['titulo']  ?? '';
                        $mensaje = $p['mensaje'] ?? '';
                    ?>
                    <div class="plantilla-card">
                        <div class="plantilla-card-head">
                            <span class="plantilla-estado-badge p-badge-<?= $key ?>">
                                <?= $label ?>
                            </span>
                        </div>

                        <div class="plantilla-field">
                            <label>Título (referencia interna)</label>
                            <input type="text"
                                   name="titulo_<?= $key ?>"
                                   value="<?= htmlspecialchars($titulo) ?>"
                                   placeholder="Ej: Primer contacto"
                                   maxlength="100">
                        </div>

                        <div class="plantilla-field">
                            <label>Mensaje</label>
                            <textarea name="mensaje_<?= $key ?>"
                                      rows="4"
                                      placeholder="Escribe el mensaje..."
                                      class="js-msg-textarea"
                                      data-estado="<?= $key ?>"><?= htmlspecialchars($mensaje) ?></textarea>
                        </div>

                        <div>
                            <p class="vars-hint">Vista previa:</p>
                            <div class="preview-box js-preview" data-estado="<?= $key ?>">
                                <?= htmlspecialchars($resolvePreview($mensaje)) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="plantillas-footer">
                    <button type="submit" class="btn-guardar-plantillas" id="btnGuardar">
                        <i class="fas fa-save"></i> Guardar plantillas
                    </button>
                    <span id="saveStatus" style="font-size:13px;color:var(--muted)"></span>
                </div>

            </form>
        </section>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/tienda_mvc/public/js/funciones.js"></script>
<script>
(() => {
    const PREVIEW_DATA = <?= json_encode($previewData, JSON_UNESCAPED_UNICODE) ?>;

    const resolve = (msg) => {
        let out = msg;
        for (const [k, v] of Object.entries(PREVIEW_DATA)) {
            out = out.replaceAll(k, v);
        }
        return out;
    };

    // Vista previa en tiempo real
    document.querySelectorAll('.js-msg-textarea').forEach(ta => {
        const estado  = ta.dataset.estado;
        const preview = document.querySelector(`.js-preview[data-estado="${estado}"]`);
        if (!preview) return;

        ta.addEventListener('input', () => {
            preview.textContent = resolve(ta.value);
        });
    });

    // Chips: insertar variable en el textarea enfocado
    let lastFocusedTA = null;
    document.querySelectorAll('.js-msg-textarea').forEach(ta => {
        ta.addEventListener('focus', () => { lastFocusedTA = ta; });
    });

    document.querySelectorAll('.var-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const v = chip.textContent.trim();
            if (!lastFocusedTA) return;
            const ta    = lastFocusedTA;
            const start = ta.selectionStart;
            const end   = ta.selectionEnd;
            ta.value    = ta.value.slice(0, start) + v + ta.value.slice(end);
            ta.selectionStart = ta.selectionEnd = start + v.length;
            ta.dispatchEvent(new Event('input'));
            ta.focus();
        });
    });

    // Feedback al guardar
    document.getElementById('formPlantillas').addEventListener('submit', () => {
        const btn = document.getElementById('btnGuardar');
        btn.disabled    = true;
        btn.textContent = 'Guardando...';
    });
})();
</script>
</body>
</html>
