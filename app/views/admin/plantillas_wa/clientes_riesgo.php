<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Clientes con devolución</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>

<body>
<?php
    $clientes = $clientes ?? [];
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? '';

    $e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $pesos = static fn($n) => '$' . number_format((float)$n, 0, ',', '.');
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fecha = static function (?string $f) use ($meses): string {
        $t = $f ? strtotime($f) : 0;
        return $t ? date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] . ' ' . date('Y', $t) : '—';
    };

    $causas   = DropiOrden::CAUSAS_DEVOLUCION;
    $niveles  = ['devolvio' => 'Devolvió antes', 'reincidente' => 'Reincidente', 'marcado' => 'Marcado a mano', 'perdonado' => 'Sin culpa'];
    $clases   = ['devolvio' => 'status-contactado', 'reincidente' => 'status-cancelado', 'marcado' => 'status-cancelado', 'perdonado' => 'status-entregado'];

    $enRiesgo     = array_filter($clientes, static fn($c) => $c['nivel'] !== 'perdonado');
    $reincidentes = array_filter($clientes, static fn($c) => $c['nivel'] === 'reincidente');
    $marcados     = array_filter($clientes, static fn($c) => !empty($c['manual']));
    $totalFletes  = array_sum(array_column($enRiesgo, 'costo'));
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = 'Clientes con devolución';
            $pageSubtitle = 'Quién devolvió pedidos y cuánto te costó, para confirmar antes de despachar';
            $showRangeFilter = false;
            $showSearch      = false;
            $headerCtas = [[
                'href'  => BASE_URL . '/AdminPlantillasWa/index',
                'label' => 'Seguimiento',
                'class' => 'btn-detail',
                'icon'  => 'fa-arrow-left',
            ]];
            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <div class="stats-grid stats-grid--4col stats-grid--compacta">
                <?php foreach ([
                    ['Clientes en la lista', count($enRiesgo), 'fa-user-slash', 'glow-red', 'Con una devolución que fue culpa suya'],
                    ['Reincidentes', count($reincidentes), 'fa-repeat', 'glow-purple', 'Dos devoluciones o más'],
                    ['Marcados a mano', count($marcados), 'fa-hand', 'glow-gold', 'Los agregaste tú'],
                    ['Fletes de regreso', $pesos($totalFletes), 'fa-truck-ramp-box', 'glow-blue', 'Lo que esos clientes te costaron'],
                ] as [$titulo, $valor, $icono, $glow, $nota]): ?>
                    <div class="stat-card <?= $glow ?>">
                        <div class="stat-info">
                            <small><?= $e($titulo) ?></small>
                            <h2><?= $e($valor) ?></h2>
                            <span class="target"><?= $e($nota) ?></span>
                        </div>
                        <i class="fas <?= $icono ?> stat-icon" aria-hidden="true"></i>
                    </div>
                <?php endforeach; ?>
            </div>

            <details class="wa-aparte" style="margin:var(--sp-4) 0;">
                <summary class="btn-detail" style="cursor:pointer;"><i class="fas fa-plus" aria-hidden="true"></i> Marcar a un cliente a mano</summary>
                <div class="panel" style="margin-top:var(--sp-3);">
                    <div class="panel__body">
                        <form id="formMarcar" class="form-grid" style="display:grid;gap:var(--sp-3);grid-template-columns:repeat(auto-fit,minmax(200px,1fr));align-items:end;">
                            <div class="form-group"><label for="mcTel">Teléfono</label><input type="tel" id="mcTel" class="form-input" inputmode="tel" required placeholder="3001234567"></div>
                            <div class="form-group"><label for="mcNombre">Nombre (opcional)</label><input type="text" id="mcNombre" class="form-input" maxlength="150"></div>
                            <div class="form-group"><label for="mcNota">Por qué</label><input type="text" id="mcNota" class="form-input" maxlength="255" placeholder="Ej: rechazó en la puerta"></div>
                            <div><button type="submit" class="btn-primary"><i class="fas fa-user-slash"></i> Marcar</button></div>
                        </form>
                        <small class="help" style="display:block;margin-top:var(--sp-2)">
                            Sirve para alguien que no tiene una devolución en Dropi pero no quieres volver a despacharle sin confirmar.
                            Se reconoce aunque el número venga con +57 o con espacios.
                        </small>
                    </div>
                </div>
            </details>

            <div class="table-container">
                <div class="table-header">
                    <h2>Clientes</h2>
                </div>

                <div class="dt-filter-bar">
                    <div class="dt-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="crBuscar" placeholder="Nombre, teléfono, municipio…" autocomplete="off">
                    </div>
                </div>

                <div class="dt-table-wrap">
                    <table id="tablaClientesRiesgo" class="pedidos-dt">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Nivel</th>
                                <th>Devoluciones</th>
                                <th>Fletes</th>
                                <th>Última y causa</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clientes as $c): $m = $c['manual']; ?>
                            <tr>
                                <td>
                                    <strong><?= $e($c['nombre'] !== '' ? $c['nombre'] : 'Sin nombre') ?></strong>
                                    <small class="dt-cell-sub"><?= $e($c['telefono']) ?><?= $c['municipio'] !== '' ? ' · ' . $e($c['municipio']) : '' ?></small>
                                    <?php if ($m && $m['nota'] !== ''): ?><small class="dt-cell-sub" style="color:var(--red);font-weight:700"><?= $e($m['nota']) ?></small><?php endif; ?>
                                </td>
                                <td><span class="status-tag <?= $clases[$c['nivel']] ?>"><?= $e($niveles[$c['nivel']]) ?></span></td>
                                <td data-order="<?= (int)$c['culpa'] ?>">
                                    <strong><?= (int)$c['culpa'] ?></strong>
                                    <?php if ($c['total'] > $c['culpa']): ?><small class="dt-cell-sub"><?= (int)($c['total'] - $c['culpa']) ?> sin culpa</small><?php endif; ?>
                                </td>
                                <td data-order="<?= (int)$c['costo'] ?>"><?= $pesos($c['costo']) ?></td>
                                <td data-order="<?= (int)strtotime((string)$c['ultima']) ?>">
                                    <?= $fecha($c['ultima']) ?>
                                    <?php foreach ($c['causas'] as $causa): ?><small class="dt-cell-sub"><?= $e($causas[$causa] ?? $causas['indeterminada']) ?></small><?php endforeach; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn-detail" data-act="ver" aria-label="Ver devoluciones de <?= $e($c['nombre']) ?>">
                                        <i class="fas fa-chevron-down" aria-hidden="true"></i> Ver
                                    </button>
                                    <div class="cr-detalle" hidden>
                                        <?php foreach ($c['ordenes'] as $o): $exenta = (int)$o['devolucion_exenta'] === 1; ?>
                                            <div style="padding:var(--sp-2) 0;border-bottom:1px solid var(--border);font-size:var(--text-sm);">
                                                <strong>Pedido #<?= $e($o['dropi_id']) ?></strong> · <?= $fecha($o['fecha_pedido']) ?> · <?= $e($o['producto']) ?>
                                                <small class="dt-cell-sub">
                                                    Flete de regreso <?= $pesos($o['flete_devolucion']) ?> ·
                                                    <?= $e($causas[$o['devolucion_causa'] ?: 'indeterminada'] ?? $causas['indeterminada']) ?>
                                                    <?= $exenta ? ' · <b style="color:var(--green)">No fue culpa del cliente</b>' : '' ?>
                                                    <?= $o['devolucion_nota'] !== '' ? ' · ' . $e($o['devolucion_nota']) : '' ?>
                                                </small>
                                                <button type="button" class="btn-ghost" style="margin-top:var(--sp-2)"
                                                        data-act="<?= $exenta ? 'culpa' : 'exenta' ?>" data-id="<?= (int)$o['id'] ?>">
                                                    <?= $exenta ? 'Sí fue culpa del cliente' : 'No fue culpa del cliente' ?>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if ($m): ?>
                                            <div style="padding:var(--sp-2) 0;font-size:var(--text-sm);">
                                                <strong>Marcado a mano</strong> el <?= $fecha($m['creado_en']) ?><?= $m['nota'] !== '' ? ': ' . $e($m['nota']) : '' ?>
                                                <button type="button" class="btn-ghost" style="margin-top:var(--sp-2);display:block" data-act="quitar" data-id="<?= (int)$m['id'] ?>">Quitar la marca</button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!$clientes): ?>
                <p class="help" style="margin-top:var(--sp-4)">
                    Todavía no hay clientes con devolución. Cuando una orden de Dropi pase a devolución, el cliente aparece aquí solo.
                </p>
            <?php endif; ?>

        </section>
    </main>

</div>

<script src="<?= BASE_URL ?>/public/vendor/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/public/vendor/dataTables.min.js"></script>
<script>
(() => {
    const BASE = window.BASE_URL || '';
    const tabla = document.getElementById('tablaClientesRiesgo');

    const dt = new DataTable('#tablaClientesRiesgo', {
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
        searching: true,
        order: [],
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: [5] }],
        language: {
            lengthMenu:  'Mostrar _MENU_ por página',
            info:        '_TOTAL_ clientes',
            infoEmpty:   'Sin clientes',
            infoFiltered:'(de _MAX_)',
            zeroRecords: 'No hay clientes que coincidan.',
            emptyTable:  'Todavía no hay clientes con devolución.',
            paginate:    { first: '«', last: '»', next: '›', previous: '‹' },
        },
        dom: 't<"dt-bottom"lip>',
    });

    document.getElementById('crBuscar').addEventListener('input', (e) => dt.search(e.target.value).draw());

    const enviar = (accion, datos) => {
        const fd = new FormData();
        Object.keys(datos).forEach(k => fd.append(k, datos[k]));
        fd.append('csrf_token', window.__CSRF__ || '');
        return fetch(BASE + '/AdminPlantillasWa/' + accion, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
            .then(r => r.json())
            .then(j => { if (!j.ok) { alert(j.error || 'No se pudo guardar.'); return; } window.location.reload(); })
            .catch(() => alert('No se pudo guardar. Revisa tu conexión.'));
    };

    tabla.addEventListener('click', (ev) => {
        const btn = ev.target.closest('[data-act]');
        if (!btn) return;
        const act = btn.dataset.act;

        if (act === 'ver') {
            const fila = dt.row(btn.closest('tr'));
            if (fila.child.isShown()) { fila.child.hide(); return; }
            fila.child('<div style="padding:var(--sp-3) var(--sp-4)">' + btn.closest('td').querySelector('.cr-detalle').innerHTML + '</div>').show();
            return;
        }
        if (act === 'exenta') {
            const nota = window.prompt('¿Por qué no fue culpa del cliente? (opcional)', '');
            if (nota === null) return;
            enviar('marcarDevolucion', { orden_id: btn.dataset.id, exenta: 1, nota: nota });
        } else if (act === 'culpa') {
            enviar('marcarDevolucion', { orden_id: btn.dataset.id, exenta: 0, nota: '' });
        } else if (act === 'quitar') {
            if (confirm('¿Quitar la marca de este cliente?')) enviar('quitarClienteRiesgo', { id: btn.dataset.id });
        }
    });

    document.getElementById('formMarcar').addEventListener('submit', (ev) => {
        ev.preventDefault();
        enviar('agregarClienteRiesgo', {
            telefono: document.getElementById('mcTel').value.trim(),
            nombre:   document.getElementById('mcNombre').value.trim(),
            nota:     document.getElementById('mcNota').value.trim(),
        });
    });
})();
</script>

</body>
</html>
