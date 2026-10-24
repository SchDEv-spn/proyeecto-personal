<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($vendedora['nombre'], ENT_QUOTES, 'UTF-8') ?> · Vendedoras</title>
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
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? '';
    $invalidos = $invalidos ?? [];
    $repetidos = $repetidos ?? 0;

    $e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $pesos = static fn($n) => '$' . number_format((float)$n, 0, ',', '.');
    $plural = static fn(int $n, string $uno, string $varios) => number_format($n, 0, ',', '.') . ' ' . ($n === 1 ? $uno : $varios);
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fecha = static function (?string $f) use ($meses): string {
        $t = $f ? strtotime($f) : 0;
        return $t ? date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] : '—';
    };
    $etiquetas = DropiImport::ETIQUETAS;
    $claseEstado = static fn(string $s) => ['otro' => 'contactado', 'devolucion' => 'cancelado'][$s] ?? $s;
    $estadoTag = static function (array $o) use ($e, $etiquetas, $claseEstado): string {
        return '<span class="status-tag status-' . $e($claseEstado($o['estado'])) . '">' . $e($etiquetas[$o['estado']] ?? $o['estado']) . '</span>';
    };
    $cantidadTxt = static function (array $o): string {
        return !empty($o['cantidad_conocida'])
            ? (int)$o['cantidad'] . ' ud' . ((int)$o['cantidad'] === 1 ? '' : 's')
            : '<span class="dash-aviso" title="Esta orden viene de una descarga de Dropi sin productos: no sé cuántas unidades lleva. Cuenta como 1; sube la descarga con productos para confirmarlo.">¿unidades?</span>';
    };

    $id = (int)$vendedora['id'];
    $p  = $pedidos;
    $totalPagado = array_sum(array_map(fn($o) => (float)$o['comision_vendedora'], $p['pagadas']));
    $valorOk = (float)$vendedora['valor_pedido'] > 0;
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = $vendedora['nombre'];
            $pageSubtitle = $valorOk ? $pesos($vendedora['valor_pedido']) . ' por pedido entregado · el doble desde 2 unidades' : 'Falta el valor por pedido';
            $showRangeFilter = false;
            $showSearch      = false;
            $headerCtas = [['href' => BASE_URL . '/AdminVendedoras/index', 'label' => 'Vendedoras', 'class' => 'btn-detail', 'icon' => 'fa-arrow-left']];
            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content vend">

            <?= alert_success($ok ?? '') ?>
            <?= alert_error($errores ?? [], 'No se pudo') ?>

            <div class="stats-grid stats-grid--compacta stats-grid--4col">
                <div class="stat-card glow-green">
                    <div class="stat-info">
                        <small>Por pagar ahora</small>
                        <h2><?= $pesos($p['monto_por_pagar']) ?></h2>
                        <span class="target"><?= $plural(count($p['por_pagar']), 'pedido entregado', 'pedidos entregados') ?></span>
                    </div>
                    <i class="fas fa-hand-holding-dollar stat-icon" aria-hidden="true"></i>
                </div>
                <div class="stat-card glow-gold">
                    <div class="stat-info">
                        <small>En camino</small>
                        <h2><?= number_format(count($p['en_camino']), 0, ',', '.') ?></h2>
                        <span class="target">Se pagan cuando se entreguen</span>
                    </div>
                    <i class="fas fa-truck-fast stat-icon" aria-hidden="true"></i>
                </div>
                <div class="stat-card glow-red">
                    <div class="stat-info">
                        <small>Sin pago</small>
                        <h2><?= number_format(count($p['sin_pago']), 0, ',', '.') ?></h2>
                        <span class="target">Devueltos o cancelados</span>
                    </div>
                    <i class="fas fa-rotate-left stat-icon" aria-hidden="true"></i>
                </div>
                <div class="stat-card glow-blue">
                    <div class="stat-info">
                        <small>Ya pagado</small>
                        <h2><?= $pesos($totalPagado) ?></h2>
                        <span class="target"><?= $plural(count($p['pagadas']), 'pedido', 'pedidos') ?> en <?= $plural(count($historial), 'liquidación', 'liquidaciones') ?></span>
                    </div>
                    <i class="fas fa-circle-check stat-icon" aria-hidden="true"></i>
                </div>
            </div>

            <!-- ─── Agregar pedidos del cuaderno ─── -->
            <div class="panel">
                <div class="panel__head"><h2><i class="fas fa-book" aria-hidden="true"></i> Agregar pedidos de su cuaderno</h2><span class="chip">Por teléfono</span></div>
                <div class="panel__body">
                    <form method="post" action="<?= BASE_URL ?>/AdminVendedoras/revisar" class="vend-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="vendedora_id" value="<?= $id ?>">
                        <label for="vTelefonos">Teléfonos de los clientes (uno por línea)</label>
                        <textarea id="vTelefonos" name="telefonos" class="form-textarea" rows="6" placeholder="3001234567&#10;3109876543&#10;..."><?= $e($texto) ?></textarea>
                        <small class="help">Busco cada número en Dropi y te muestro el pedido y su estado antes de agregarlo. Acepto +57 y espacios.</small>
                        <div><button type="submit" class="btn-primary"><i class="fas fa-magnifying-glass" aria-hidden="true"></i> Revisar teléfonos</button></div>
                    </form>

                    <?php if ($revision !== null): ?>
                        <hr class="vend-sep">
                        <?php if ($invalidos): ?>
                            <div class="admin-alert-error" role="alert">
                                <div class="admin-alert-title">Estas líneas no parecen un celular de 10 dígitos:</div>
                                <ul><?php foreach ($invalidos as $l): ?><li><?= $e($l) ?></li><?php endforeach; ?></ul>
                            </div>
                        <?php endif; ?>
                        <?php if ($repetidos > 0): ?><p class="help"><?= $plural($repetidos, 'teléfono repetido', 'teléfonos repetidos') ?> (lo conté una sola vez).</p><?php endif; ?>

                        <?php if (!$revision): ?>
                            <p class="help">No quedó ningún teléfono válido para revisar.</p>
                        <?php else:
                            $sinPedido = array_filter($revision, fn($t) => !$t['ordenes']);
                            $hayElegibles = false;
                            foreach ($revision as $t) foreach ($t['ordenes'] as $o) if ($o['elegible']) $hayElegibles = true;
                        ?>
                            <form method="post" action="<?= BASE_URL ?>/AdminVendedoras/asignar" id="vFormAsignar">
                                <?= csrf_field() ?>
                                <input type="hidden" name="vendedora_id" value="<?= $id ?>">
                                <div class="dash-tabla-wrap">
                                    <table class="dash-tabla vend-tabla">
                                        <thead>
                                            <tr>
                                                <th scope="col"><span class="vh">Agregar</span></th>
                                                <th scope="col">Teléfono</th>
                                                <th scope="col">Pedido</th>
                                                <th scope="col">Estado</th>
                                                <th scope="col" class="num">Se pagaría</th>
                                                <th scope="col">Nota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($revision as $t): ?>
                                            <?php if (!$t['ordenes']): ?>
                                                <tr class="vend-fila--sin">
                                                    <td></td>
                                                    <th scope="row"><?= $e($t['telefono']) ?></th>
                                                    <td colspan="4" class="vend-nota">No aparece en Dropi (en los últimos <?= (int)Vendedora::DIAS_ATRAS ?> días). Revisa que el número esté bien y que ya hayas importado tus órdenes.</td>
                                                </tr>
                                                <?php continue; ?>
                                            <?php endif; ?>
                                            <?php foreach ($t['ordenes'] as $k => $o): ?>
                                                <tr class="<?= $o['elegible'] ? '' : 'vend-fila--no' ?>">
                                                    <td>
                                                        <?php if ($o['elegible']): ?>
                                                            <input type="checkbox" name="ordenes[]" value="<?= (int)$o['id'] ?>" <?= $o['marcada'] ? 'checked' : '' ?> aria-label="Agregar el pedido del <?= $e($fecha($o['fecha_pedido'])) ?> del <?= $e($t['telefono']) ?>">
                                                        <?php endif; ?>
                                                    </td>
                                                    <th scope="row"><?= $k === 0 ? $e($t['telefono']) : '' ?></th>
                                                    <td>
                                                        <?= $e($fecha($o['fecha_pedido'])) ?> · <?= $o['producto'] !== '' ? $e($o['producto']) : 'Sin producto' ?>
                                                        <small class="vend-sub"><?= $cantidadTxt($o) ?><?= $o['guia'] !== '' ? ' · Guía ' . $e($o['guia']) : '' ?></small>
                                                    </td>
                                                    <td><?= $estadoTag($o) ?><small class="vend-sub">Dropi: <?= $e($o['estatus']) ?></small></td>
                                                    <td class="num"><?= $o['comision'] !== null && $o['elegible'] ? $pesos($o['comision']) : '—' ?></td>
                                                    <td class="vend-nota">
                                                        <?php if (!$o['elegible']): ?>
                                                            <?= $e($o['motivo']) ?><?= !empty($o['liquidada_en']) ? ' el ' . $e($fecha($o['liquidada_en'])) : '' ?>
                                                        <?php elseif (!$o['marcada']): ?>
                                                            Es de hace más de <?= (int)Vendedora::DIAS_MARCADAS ?> días
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <p class="help dash-nota">
                                    Solo se pueden agregar pedidos que <b>no vinieron de la landing</b>, que no estén ya liquidados ni sean de otra vendedora.
                                    “Se pagaría” es lo que le toca si el pedido ya está entregado; los que van en camino se pagan cuando se entreguen.
                                    <?php if ($sinPedido): ?><b><?= $plural(count($sinPedido), 'teléfono no aparece', 'teléfonos no aparecen') ?></b> en Dropi.<?php endif; ?>
                                </p>
                                <?php if ($hayElegibles): ?>
                                    <div class="vend-acciones">
                                        <button type="submit" class="btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> Agregar los marcados a <?= $e($vendedora['nombre']) ?></button>
                                        <button type="button" class="btn-detail" data-marcar="#vFormAsignar" data-valor="1">Marcar todos</button>
                                        <button type="button" class="btn-detail" data-marcar="#vFormAsignar" data-valor="0">Quitar marcas</button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ─── Por pagar ─── -->
            <div class="panel">
                <div class="panel__head"><h2><i class="fas fa-hand-holding-dollar" aria-hidden="true"></i> Por pagar</h2><span class="chip">Entregados sin liquidar</span></div>
                <div class="panel__body">
                    <?php if (!$p['por_pagar']): ?>
                        <p class="help">No hay pedidos entregados por pagar. Cuando se entreguen los que van en camino, aparecen aquí.</p>
                    <?php else: ?>
                        <form method="post" action="<?= BASE_URL ?>/AdminVendedoras/liquidar" id="vFormLiquidar">
                            <?= csrf_field() ?>
                            <input type="hidden" name="vendedora_id" value="<?= $id ?>">
                            <div class="dash-tabla-wrap">
                                <table class="dash-tabla vend-tabla">
                                    <thead><tr><th scope="col"><span class="vh">Pagar</span></th><th scope="col">Pedido</th><th scope="col">Teléfono</th><th scope="col" class="num">Unidades</th><th scope="col" class="num">Comisión</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($p['por_pagar'] as $o): ?>
                                        <tr>
                                            <td><input type="checkbox" name="ordenes[]" value="<?= (int)$o['id'] ?>" data-monto="<?= (int)round($o['comision']) ?>" checked aria-label="Pagar el pedido del <?= $e($fecha($o['fecha_pedido'])) ?> de <?= $e($o['telefono']) ?>"></td>
                                            <td><?= $e($fecha($o['fecha_pedido'])) ?> · <?= $o['producto'] !== '' ? $e($o['producto']) : 'Sin producto' ?><small class="vend-sub"><?= $o['guia'] !== '' ? 'Guía ' . $e($o['guia']) : '' ?></small></td>
                                            <td><?= $e($o['telefono']) ?></td>
                                            <td class="num"><?= $cantidadTxt($o) ?></td>
                                            <td class="num"><?= $pesos($o['comision']) ?><?= $o['comision'] > (float)$vendedora['valor_pedido'] ? ' <small class="vend-sub">doble</small>' : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($p['sin_cantidad'] > 0): ?>
                                <p class="help dash-aviso"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                    <?= $plural($p['sin_cantidad'], 'pedido viene', 'pedidos vienen') ?> de una descarga de Dropi sin productos: no sé cuántas unidades llevan y los conté como 1.
                                    Si alguno llevaba 2 o más, sube la descarga “órdenes con productos” antes de liquidar y se corrige solo.
                                </p>
                            <?php endif; ?>
                            <div class="vend-acciones">
                                <input type="text" name="nota" class="form-input" maxlength="255" placeholder="Nota (opcional): semana del 20 al 26 de octubre" aria-label="Nota de la liquidación">
                                <button type="submit" class="btn-primary" id="vBtnLiquidar" <?= $valorOk ? '' : 'disabled' ?>><i class="fas fa-circle-check" aria-hidden="true"></i> Liquidar los marcados: <b id="vTotalLiquidar"><?= $pesos($p['monto_por_pagar']) ?></b></button>
                                <button type="button" class="btn-detail" data-marcar="#vFormLiquidar" data-valor="1">Marcar todos</button>
                                <button type="button" class="btn-detail" data-marcar="#vFormLiquidar" data-valor="0">Quitar marcas</button>
                            </div>
                            <?php if (!$valorOk): ?><p class="help dash-aviso">Ponle primero el valor por pedido (abajo, en “Nombre y valor”).</p><?php endif; ?>
                            <p class="help dash-nota">Al liquidar, cada pedido queda marcado como pagado con lo que se le pagó: no vuelve a aparecer ni se paga dos veces.</p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ─── En camino y sin pago ─── -->
            <?php foreach ([['en_camino', 'En camino', 'Se pagan cuando se entreguen', 'fa-truck-fast'], ['sin_pago', 'Sin pago', 'Devueltos o cancelados: no se pagan ni se cobran', 'fa-rotate-left']] as [$clave, $titulo, $chip, $icono]): ?>
                <?php if ($p[$clave]): ?>
                <div class="panel">
                    <div class="panel__head"><h2><i class="fas <?= $icono ?>" aria-hidden="true"></i> <?= $e($titulo) ?> (<?= count($p[$clave]) ?>)</h2><span class="chip"><?= $e($chip) ?></span></div>
                    <div class="panel__body">
                        <form method="post" action="<?= BASE_URL ?>/AdminVendedoras/quitar" id="vForm<?= $e($clave) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="vendedora_id" value="<?= $id ?>">
                            <div class="dash-tabla-wrap">
                                <table class="dash-tabla vend-tabla">
                                    <thead><tr><th scope="col"><span class="vh">Quitar</span></th><th scope="col">Pedido</th><th scope="col">Teléfono</th><th scope="col">Estado</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($p[$clave] as $o): ?>
                                        <tr>
                                            <td><input type="checkbox" name="ordenes[]" value="<?= (int)$o['id'] ?>" aria-label="Marcar para quitar el pedido del <?= $e($fecha($o['fecha_pedido'])) ?> de <?= $e($o['telefono']) ?>"></td>
                                            <td><?= $e($fecha($o['fecha_pedido'])) ?> · <?= $o['producto'] !== '' ? $e($o['producto']) : 'Sin producto' ?><small class="vend-sub"><?= $cantidadTxt($o) ?><?= $o['guia'] !== '' ? ' · Guía ' . $e($o['guia']) : '' ?></small></td>
                                            <td><?= $e($o['telefono']) ?></td>
                                            <td><?= $estadoTag($o) ?><small class="vend-sub">Dropi: <?= $e($o['estatus']) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="vend-acciones">
                                <button type="submit" class="btn-detail" onclick="return confirm('¿Quitar los marcados de <?= $e(addslashes($vendedora['nombre'])) ?>? Quedan sin vendedora.');"><i class="fas fa-user-minus" aria-hidden="true"></i> Quitar los marcados</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- ─── Historial ─── -->
            <div class="panel">
                <div class="panel__head"><h2><i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Liquidaciones</h2><span class="chip">Historial</span></div>
                <div class="panel__body">
                    <?php if (!$historial): ?>
                        <p class="help">Todavía no le has liquidado nada.</p>
                    <?php else: foreach ($historial as $l): ?>
                        <details class="vend-detalle">
                            <summary>
                                <b><?= $e($fecha($l['creada_en'])) ?> <?= $e(date('Y', strtotime($l['creada_en']))) ?></b> · <?= $pesos($l['total']) ?> · <?= $plural((int)$l['ordenes'], 'pedido', 'pedidos') ?>
                                <?= $l['nota'] !== '' ? ' · ' . $e($l['nota']) : '' ?>
                            </summary>
                            <div class="dash-tabla-wrap">
                                <table class="dash-tabla">
                                    <thead><tr><th scope="col">Pedido</th><th scope="col">Teléfono</th><th scope="col" class="num">Pagado</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($l['detalle'] as $o): ?>
                                        <tr><td><?= $e($fecha($o['fecha_pedido'])) ?> · <?= $o['producto'] !== '' ? $e($o['producto']) : 'Sin producto' ?></td><td><?= $e($o['telefono']) ?></td><td class="num"><?= $pesos($o['comision_vendedora']) ?></td></tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- ─── Nombre y valor ─── -->
            <div class="panel">
                <div class="panel__head"><h2><i class="fas fa-pen" aria-hidden="true"></i> Nombre y valor</h2></div>
                <div class="panel__body">
                    <form method="post" action="<?= BASE_URL ?>/AdminVendedoras/guardar" class="vend-form vend-form--fila">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div>
                            <label for="vNombre">Nombre</label>
                            <input id="vNombre" name="nombre" class="form-input" maxlength="100" required value="<?= $e($vendedora['nombre']) ?>">
                        </div>
                        <div>
                            <label for="vValor">Valor por pedido entregado</label>
                            <input id="vValor" name="valor_pedido" class="form-input" inputmode="numeric" autocomplete="off" value="<?= (int)$vendedora['valor_pedido'] ?>">
                        </div>
                        <label class="vend-check"><input type="checkbox" name="activa" value="1" <?= $vendedora['activa'] ? 'checked' : '' ?>> Activa</label>
                        <div><button type="submit" class="btn-primary"><i class="fas fa-check" aria-hidden="true"></i> Guardar</button></div>
                    </form>
                    <p class="help dash-nota">Cambiar el valor solo afecta lo que se liquide de ahora en adelante; lo ya pagado queda como se pagó.</p>
                </div>
            </div>

        </section>
    </main>
</div>

<script>
(function () {
    // Marcar todos / quitar marcas
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest ? ev.target.closest('[data-marcar]') : null;
        if (!b) return;
        var f = document.querySelector(b.getAttribute('data-marcar'));
        if (!f) return;
        Array.prototype.forEach.call(f.querySelectorAll('input[type="checkbox"][name="ordenes[]"]'), function (c) { c.checked = b.getAttribute('data-valor') === '1'; });
        sumar();
    });

    // El botón de liquidar muestra el total de lo marcado.
    var form = document.getElementById('vFormLiquidar');
    var total = document.getElementById('vTotalLiquidar');
    var boton = document.getElementById('vBtnLiquidar');
    function pesos(n) { return '$' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function sumar() {
        if (!form || !total) return;
        var s = 0, n = 0;
        Array.prototype.forEach.call(form.querySelectorAll('input[type="checkbox"][name="ordenes[]"]:checked'), function (c) { s += Number(c.getAttribute('data-monto')) || 0; n++; });
        total.textContent = pesos(s);
        if (boton) boton.disabled = (n === 0) || boton.getAttribute('data-bloqueado') === '1';
    }
    if (form) {
        if (boton && boton.disabled) boton.setAttribute('data-bloqueado', '1');
        form.addEventListener('change', sumar);
        form.addEventListener('submit', function (ev) {
            if (!window.confirm('¿Liquidar ' + total.textContent + ' a <?= $e(addslashes($vendedora['nombre'])) ?>? Esos pedidos quedan marcados como pagados.')) ev.preventDefault();
        });
        sumar();
    }
})();
</script>
<script src="<?= asset_url('public/js/modal-a11y.js') ?>"></script>
</body>

</html>
