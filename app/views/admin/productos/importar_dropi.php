<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Importar de Dropi</title>
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
    $errores         = $errores         ?? [];
    $dropiId         = $dropiId         ?? '';
    $tieneDropiToken = $tieneDropiToken ?? false;

    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? 'admin@tuempresa.com';
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = 'Importar de Dropi';
            $pageSubtitle = 'Trae un producto de Dropi para empezar a venderlo';
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

            <!-- ═══════════════════════════════════════════
                 CONEXIÓN CON DROPI (token)
            ═══════════════════════════════════════════ -->
            <div class="panel" style="margin-bottom:var(--sp-5)">
                <div class="panel__head">
                    <h2><i class="fas fa-plug" aria-hidden="true"></i> Conexión con Dropi</h2>
                    <?php if ($tieneDropiToken): ?>
                        <span class="chip"><i class="fas fa-circle-check"></i> Conectado</span>
                    <?php endif; ?>
                </div>
                <div class="panel__body">
                    <?php if ($tieneDropiToken): ?>
                        <div class="dropi-token-saved" id="dropiTokenSaved">
                            <i class="fas fa-circle-check" aria-hidden="true"></i> Token de Dropi configurado
                            <button type="button" class="btn-ghost" id="btnCambiarDropiToken">Cambiar</button>
                        </div>
                    <?php endif; ?>

                    <div class="form-group form-group--full" id="dropiTokenSection"
                         style="<?= $tieneDropiToken ? 'display:none' : '' ?>">
                        <label for="dropi_api_token">Token de integración de Dropi</label>
                        <input type="password" id="dropi_api_token" class="form-input" placeholder="Pega aquí tu dropi-integration-key">
                        <small class="help">
                            Lo generás desde tu cuenta de Dropi, en la sección de Integraciones. Se guarda en tu
                            base de datos, no en el código — no viaja al repositorio.
                        </small>
                        <button type="button" class="btn-primary" id="btnGuardarDropiToken" style="margin-top:var(--sp-3)">
                            <i class="fas fa-save"></i> Guardar token
                        </button>
                        <div class="dropi-token-msg" id="dropiTokenMsg"></div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════
                 TRAER PRODUCTO POR ID
            ═══════════════════════════════════════════ -->
            <div class="panel">
                <div class="panel__head">
                    <h2><i class="fas fa-cloud-arrow-down" aria-hidden="true"></i> Traer producto de Dropi</h2>
                    <span class="chip">Por ID</span>
                </div>
                <div class="panel__body">
                    <form action="<?= BASE_URL ?>/AdminProductos/buscarDropi" method="POST" class="admin-form">
                        <?= csrf_field() ?>
                        <div class="form-grid">
                            <div class="form-group form-group--full">
                                <label for="dropi_product_id">ID del producto en Dropi <span class="req">*</span></label>
                                <input type="number" id="dropi_product_id" name="dropi_product_id"
                                       value="<?= htmlspecialchars($dropiId) ?>" min="1" required autofocus
                                       placeholder="Ej: 48213">
                                <small class="help">
                                    Lo ves al abrir el producto en Dropi: aparece en la URL de la página
                                    (ej: app.dropi.co/dashboard/products/detail/<strong>48213</strong>).
                                    Importante: el producto debe estar ya agregado a <strong>tu cuenta</strong> de
                                    Dropi ("Mis productos") — verlo en el catálogo/marketplace no alcanza, primero
                                    hacé clic en "Importar" desde app.dropi.co.
                                </small>
                            </div>
                        </div>

                        <div class="admin-form-actions" style="position:static;margin-top:var(--sp-5)">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-search"></i> Traer producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </section>
    </main>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btnCambiar = document.getElementById('btnCambiarDropiToken');
    const saved       = document.getElementById('dropiTokenSaved');
    const section     = document.getElementById('dropiTokenSection');
    if (btnCambiar) {
        btnCambiar.addEventListener('click', () => {
            saved.style.display   = 'none';
            section.style.display = '';
            document.getElementById('dropi_api_token').focus();
        });
    }

    const btnGuardar = document.getElementById('btnGuardarDropiToken');
    if (btnGuardar) {
        const msg = document.getElementById('dropiTokenMsg');
        btnGuardar.addEventListener('click', async () => {
            const input = document.getElementById('dropi_api_token');
            const token = input.value.trim();
            msg.textContent = '';
            msg.className = 'dropi-token-msg';

            if (!token) {
                msg.textContent = 'Pega el token antes de guardar.';
                msg.className = 'dropi-token-msg dropi-token-msg--error';
                return;
            }

            btnGuardar.disabled = true;
            try {
                const r = await fetch('<?= BASE_URL ?>/AdminProductos/guardarDropiToken', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ dropi_api_token: token, csrf_token: window.__CSRF__ || '' }),
                });
                const d = await r.json();
                if (d.ok) {
                    msg.textContent = 'Token guardado.';
                    msg.className = 'dropi-token-msg dropi-token-msg--ok';
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    msg.textContent = d.error || 'No se pudo guardar el token.';
                    msg.className = 'dropi-token-msg dropi-token-msg--error';
                }
            } catch {
                msg.textContent = 'Error de red al guardar el token.';
                msg.className = 'dropi-token-msg dropi-token-msg--error';
            } finally {
                btnGuardar.disabled = false;
            }
        });
    }
});
</script>

</body>
</html>
