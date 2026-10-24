<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Vendedoras</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>

<body>
<?php
    $vendedoras    = $vendedoras ?? [];
    $resumen       = $resumen ?? [];
    $ultimas       = $ultimas ?? [];
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? '';

    $e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $pesos = static fn($n) => '$' . number_format((float)$n, 0, ',', '.');
    $plural = static fn(int $n, string $uno, string $varios) => number_format($n, 0, ',', '.') . ' ' . ($n === 1 ? $uno : $varios);
    $vacio = ['asignadas' => 0, 'por_pagar' => 0, 'monto_por_pagar' => 0, 'en_camino' => 0, 'sin_pago' => 0, 'pagadas' => 0, 'monto_pagado' => 0, 'sin_cantidad' => 0];
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = 'Vendedoras';
            $pageSubtitle = 'Agrega los pedidos de su cuaderno, mira cuáles ya se entregaron y liquida su comisión';
            $showRangeFilter = false;
            $showSearch      = false;
            $headerCtas = [];
            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content vend">

            <div id="vendAvisos" class="vend-avisos" aria-live="polite"><?= alert_success($ok ?? '') ?><?= alert_error($errores ?? [], 'No se pudo') ?></div>

            <div class="vend-grid">
                <?php foreach ($vendedoras as $v): $r = $resumen[(int)$v['id']] ?? $vacio; ?>
                    <a class="panel vend-card<?= $v['activa'] ? '' : ' vend-card--inactiva' ?>" href="<?= BASE_URL ?>/AdminVendedoras/ver/<?= (int)$v['id'] ?>">
                        <div class="panel__head">
                            <h2><i class="fas fa-user-tie" aria-hidden="true"></i> <?= $e($v['nombre']) ?></h2>
                            <span class="chip"><?= $v['activa'] ? ((float)$v['valor_pedido'] > 0 ? $pesos($v['valor_pedido']) . ' por unidad' : 'Sin valor por unidad') : 'Inactiva' ?></span>
                        </div>
                        <div class="panel__body">
                            <div class="vend-card__fila vend-card__fila--grande">
                                <span>Por pagar</span>
                                <b><?= $pesos($r['monto_por_pagar']) ?></b>
                            </div>
                            <div class="vend-card__fila"><span>Entregados sin pagar</span><b><?= number_format($r['por_pagar'], 0, ',', '.') ?></b></div>
                            <div class="vend-card__fila"><span>En camino</span><b><?= number_format($r['en_camino'], 0, ',', '.') ?></b></div>
                            <div class="vend-card__fila"><span>Devueltos o cancelados (sin pago)</span><b><?= number_format($r['sin_pago'], 0, ',', '.') ?></b></div>
                            <div class="vend-card__fila"><span>Ya pagado</span><b><?= $pesos($r['monto_pagado']) ?></b></div>
                            <?php if ($r['sin_cantidad'] > 0): ?>
                                <p class="help dash-aviso"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> <?= $plural($r['sin_cantidad'], 'pedido sin cantidad confirmada', 'pedidos sin cantidad confirmada') ?> (se cuenta como 1 unidad).</p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>

                <div class="panel vend-card vend-card--nueva">
                    <div class="panel__head"><h2><i class="fas fa-plus" aria-hidden="true"></i> Nueva vendedora</h2></div>
                    <div class="panel__body">
                        <form method="post" action="<?= BASE_URL ?>/AdminVendedoras/guardar" class="vend-form">
                            <?= csrf_field() ?>
                            <label for="vNombre">Nombre</label>
                            <input id="vNombre" name="nombre" class="form-input" maxlength="100" required placeholder="Vendedora 1 — Nombre">
                            <label for="vValor">Valor por unidad entregada</label>
                            <input id="vValor" name="valor_pedido" class="form-input" inputmode="numeric" placeholder="Ej.: 5000" autocomplete="off">
                            <small class="help">Se le paga ese valor por cada unidad de un pedido entregado: con 2 unidades se paga doble, con 3 triple, y así.</small>
                            <button type="submit" class="btn-primary"><i class="fas fa-check" aria-hidden="true"></i> Crear vendedora</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__head"><h2><i class="fas fa-link" aria-hidden="true"></i> Enlace de consulta para las vendedoras</h2><span class="chip">Sin contraseña</span></div>
                <div class="panel__body">
                    <p class="help" style="margin-bottom:var(--sp-3)">
                        Con este enlace ellas consultan el estado de un pedido escribiendo solo el teléfono del cliente, sin entrar a tu plataforma.
                        Guárdalo como favorito y mándaselo por WhatsApp. <b>No lo publiques:</b> quien lo tenga puede consultar cualquier teléfono.
                        Si se filtra, cámbialo y el anterior deja de funcionar.
                    </p>
                    <div class="vend-enlace">
                        <input type="text" id="vEnlace" class="form-input" readonly value="<?= $e($enlace) ?>" aria-label="Enlace de consulta">
                        <button type="button" class="btn-primary" id="vCopiar"><i class="far fa-copy" aria-hidden="true"></i> Copiar</button>
                        <a class="btn-detail" id="vAbrirEnlace" href="<?= $e($enlace) ?>" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square" aria-hidden="true"></i> Abrir</a>
                        <form method="post" id="vFormEnlace" action="<?= BASE_URL ?>/AdminVendedoras/cambiarEnlace" onsubmit="return confirm('¿Cambiar el enlace? El que tienen guardado dejará de funcionar y tendrás que mandarles el nuevo.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn-detail"><i class="fas fa-rotate" aria-hidden="true"></i> Cambiar enlace</button>
                        </form>
                    </div>
                    <p class="help dash-nota">
                        Hoy se hicieron <b><?= number_format((int)($consultasHoy ?? 0), 0, ',', '.') ?></b> consultas.
                        Muestra el estado, la guía, la transportadora, el municipio y el producto; nunca el nombre, la dirección ni el precio del cliente.
                    </p>

                    <?php if ($ultimas): ?>
                        <details class="vend-detalle">
                            <summary>Últimas consultas</summary>
                            <div class="dash-tabla-wrap">
                                <table class="dash-tabla">
                                    <thead><tr><th scope="col">Cuándo</th><th scope="col">Teléfono (últimos 4)</th><th scope="col" class="num">Pedidos encontrados</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($ultimas as $u): ?>
                                        <tr>
                                            <td><?= $e(date('d/m/Y g:i a', strtotime($u['creado_en']))) ?></td>
                                            <td>… <?= $e($u['tel_fin']) ?></td>
                                            <td class="num"><?= (int)$u['resultados'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </div>

        </section>
    </main>
</div>

<script>
(function () {
    var b = document.getElementById('vCopiar'), i = document.getElementById('vEnlace');
    if (!b || !i) return;
    b.addEventListener('click', function () {
        var original = b.innerHTML;
        var listo = function () { b.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> ¡Copiado!'; setTimeout(function () { b.innerHTML = original; }, 2000); };
        i.select();
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(i.value).then(listo, function () { try { document.execCommand('copy'); listo(); } catch (x) {} });
        else { try { document.execCommand('copy'); listo(); } catch (x) {} }
    });
})();
</script>
<script>
(function () {
    // Cambiar el enlace sin recargar: el campo y el botón "Abrir" se actualizan en su sitio.
    var f = document.getElementById('vFormEnlace');
    var avisos = document.getElementById('vendAvisos');
    if (!f || !window.fetch || !window.FormData) return;
    f.addEventListener('submit', function (ev) {
        if (ev.defaultPrevented) return; // el "¿Cambiar el enlace?" se canceló
        ev.preventDefault();
        var boton = f.querySelector('button[type="submit"]');
        var textoBoton = boton ? boton.innerHTML : '';
        if (boton) { boton.disabled = true; boton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Un momento…'; }
        window.fetch(f.getAttribute('action'), {
            method: 'POST', body: new FormData(f), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (j) {
            if (!j.ok || !j.enlace) throw new Error('respuesta sin enlace');
            document.getElementById('vEnlace').value = j.enlace;
            document.getElementById('vAbrirEnlace').setAttribute('href', j.enlace);
            if (avisos) {
                avisos.innerHTML = '<div class="admin-alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><span></span></div>';
                var d = avisos.firstChild;
                d.querySelector('span').textContent = j.mensaje || 'Enlace cambiado.';
                setTimeout(function () { d.classList.add('is-hiding'); setTimeout(function () { if (d.parentNode) d.parentNode.removeChild(d); }, 400); }, 3000);
            }
            if (boton) { boton.disabled = false; boton.innerHTML = textoBoton; }
        }).catch(function () {
            f.submit(); // si algo falla se envía el formulario normal
        });
    });
})();
</script>
<script src="<?= asset_url('public/js/modal-a11y.js') ?>"></script>
</body>

</html>
