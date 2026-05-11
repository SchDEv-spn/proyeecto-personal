<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantillas WhatsApp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/tienda_mvc/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .plantillas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.25rem;
            margin-top: 1.5rem;
        }

        .plantilla-card {
            background: var(--bg-elevated);
            border-radius: 16px;
            border: 1px solid var(--bd-subtle);
            padding: 1.25rem 1.4rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: .9rem;
            transition: border-color .2s, box-shadow .2s;
        }
        .plantilla-card:hover {
            border-color: var(--bd-default);
            box-shadow: 0 6px 24px rgba(0,0,0,.45);
        }

        .plantilla-card-head {
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .plantilla-estado-badge {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .p-badge-nuevo      { background: var(--info-bg);  color: var(--info);  border: 1px solid var(--info-bd); }
        .p-badge-contactado { background: rgba(168,139,250,.10); color: #a78bfa; border: 1px solid rgba(168,139,250,.24); }
        .p-badge-confirmado { background: var(--ok-bg);    color: var(--ok);    border: 1px solid var(--ok-bd); }
        .p-badge-enviado    { background: var(--warn-bg);  color: var(--warn);  border: 1px solid var(--warn-bd); }
        .p-badge-en_oficina { background: var(--warn-bg);  color: var(--warn);  border: 1px solid var(--warn-bd); }
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
        .plantilla-field textarea {
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
        .plantilla-field textarea:focus {
            border-color: var(--gold-dim);
            box-shadow: 0 0 0 3px rgba(201,168,76,.10);
        }

        .vars-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 2px;
        }

        .var-chip {
            background: var(--gold-ghost);
            color: var(--gold);
            border: 1px solid rgba(201,168,76,.22);
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            font-family: ui-monospace, "SF Mono", monospace;
            padding: 2px 8px;
            cursor: pointer;
            transition: background .15s;
            user-select: none;
        }
        .var-chip:hover { background: rgba(201,168,76,.14); }

        .vars-hint {
            font-size: 11px;
            color: var(--tx-muted);
            margin-bottom: 5px;
        }

        .plantillas-footer {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--bd-subtle);
        }

        .btn-guardar-plantillas {
            background: var(--gold);
            color: var(--tx-inverse);
            border: none;
            border-radius: 12px;
            padding: 11px 28px;
            font-size: 13px;
            font-weight: 700;
            font-family: var(--font-body);
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(201,168,76,.30);
            transition: filter .15s, transform .15s;
        }
        .btn-guardar-plantillas:hover   { filter: brightness(1.10); transform: translateY(-1px); }
        .btn-guardar-plantillas:active  { transform: scale(.97); filter: none; }
        .btn-guardar-plantillas:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; }

        .saved-banner {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: var(--ok-bg);
            color: var(--ok);
            border: 1px solid var(--ok-bd);
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .preview-box {
            background: var(--bg-overlay);
            border: 1px dashed var(--bd-default);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            color: var(--tx-secondary);
            white-space: pre-wrap;
            min-height: 48px;
            line-height: 1.5;
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
