<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Configuración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
    <style>
        .config-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--sp-5);
        }
        @media (min-width: 768px) { .config-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>

<body>
<?php
    $estado = $estado ?? [];
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? 'admin@tuempresa.com';

    $tarjetas = [
        [
            'clave'       => 'fb_capi_token',
            'icono'       => 'fab fa-facebook',
            'titulo'      => 'Facebook Conversions API',
            'ayuda'       => 'Events Manager → tu pixel → Configuración → Conversions API → Generar token de acceso.',
            'placeholder' => 'EAAxxxxxxxxxxxxx...',
        ],
        [
            'clave'       => 'tiktok_capi_token',
            'icono'       => 'fab fa-tiktok',
            'titulo'      => 'TikTok Events API',
            'ayuda'       => 'TikTok Events Manager → tu pixel → Configuración → API de eventos → Generar token.',
            'placeholder' => 'Pega aquí el access token',
        ],
        [
            'clave'       => 'telegram_bot_token',
            'icono'       => 'fab fa-telegram',
            'titulo'      => 'Telegram — Bot token',
            'ayuda'       => 'Lo da @BotFather al crear el bot que manda las notificaciones de pedidos.',
            'placeholder' => '123456789:AAxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ],
        [
            'clave'       => 'telegram_chat_id',
            'icono'       => 'fas fa-comment-dots',
            'titulo'      => 'Telegram — Chat ID',
            'ayuda'       => 'El ID del chat o canal donde el bot debe avisar de cada pedido nuevo.',
            'placeholder' => 'Ej: -1001234567890',
        ],
    ];
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = 'Configuración';
            $pageSubtitle = 'Tokens de integraciones — se guardan en tu base de datos, no en el código';
            $showRangeFilter = false;
            $showSearch      = false;
            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <div class="config-grid">
            <?php foreach ($tarjetas as $t): $configurado = !empty($estado[$t['clave']]); ?>
                <div class="panel" data-config-clave="<?= htmlspecialchars($t['clave']) ?>">
                    <div class="panel__head">
                        <h2><i class="<?= htmlspecialchars($t['icono']) ?>" aria-hidden="true"></i> <?= htmlspecialchars($t['titulo']) ?></h2>
                        <?php if ($configurado): ?>
                            <span class="chip"><i class="fas fa-circle-check"></i> Conectado</span>
                        <?php endif; ?>
                    </div>
                    <div class="panel__body">
                        <?php if ($configurado): ?>
                            <div class="dropi-token-saved" data-config-saved>
                                <i class="fas fa-circle-check" aria-hidden="true"></i> Configurado
                                <button type="button" class="btn-ghost" data-config-cambiar>Cambiar</button>
                            </div>
                        <?php endif; ?>

                        <div class="form-group form-group--full" data-config-section
                             style="<?= $configurado ? 'display:none' : '' ?>">
                            <label for="<?= htmlspecialchars($t['clave']) ?>"><?= htmlspecialchars($t['titulo']) ?></label>
                            <input type="password" id="<?= htmlspecialchars($t['clave']) ?>" class="form-input"
                                   placeholder="<?= htmlspecialchars($t['placeholder']) ?>" data-config-input>
                            <small class="help"><?= htmlspecialchars($t['ayuda']) ?></small>
                            <button type="button" class="btn-primary" style="margin-top:var(--sp-3)" data-config-guardar>
                                <i class="fas fa-save"></i> Guardar
                            </button>
                            <div class="dropi-token-msg" data-config-msg></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>

        </section>
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-config-clave]').forEach((panel) => {
        const clave    = panel.getAttribute('data-config-clave');
        const saved    = panel.querySelector('[data-config-saved]');
        const section  = panel.querySelector('[data-config-section]');
        const input    = panel.querySelector('[data-config-input]');
        const btnGuardar = panel.querySelector('[data-config-guardar]');
        const btnCambiar = panel.querySelector('[data-config-cambiar]');
        const msg      = panel.querySelector('[data-config-msg]');

        if (btnCambiar) {
            btnCambiar.addEventListener('click', () => {
                saved.style.display   = 'none';
                section.style.display = '';
                input.focus();
            });
        }

        if (btnGuardar) {
            btnGuardar.addEventListener('click', async () => {
                const valor = input.value.trim();
                msg.textContent = '';
                msg.className = 'dropi-token-msg';

                if (!valor) {
                    msg.textContent = 'Ingresa un valor antes de guardar.';
                    msg.className = 'dropi-token-msg dropi-token-msg--error';
                    return;
                }

                btnGuardar.disabled = true;
                try {
                    const r = await fetch('<?= BASE_URL ?>/AdminConfiguracion/guardarToken', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ clave: clave, valor: valor, csrf_token: window.__CSRF__ || '' }),
                    });
                    const d = await r.json();
                    if (d.ok) {
                        msg.textContent = 'Guardado.';
                        msg.className = 'dropi-token-msg dropi-token-msg--ok';
                        setTimeout(() => window.location.reload(), 700);
                    } else {
                        msg.textContent = d.error || 'No se pudo guardar.';
                        msg.className = 'dropi-token-msg dropi-token-msg--error';
                    }
                } catch {
                    msg.textContent = 'Error de red al guardar.';
                    msg.className = 'dropi-token-msg dropi-token-msg--error';
                } finally {
                    btnGuardar.disabled = false;
                }
            });
        }
    });
});
</script>

</body>
</html>
