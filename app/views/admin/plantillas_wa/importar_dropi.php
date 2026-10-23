<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Importar de Dropi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
    <style>
        .dropi-pasos { display: grid; gap: var(--sp-5); }

        .dropi-kpis { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--sp-3); }
        @media (min-width: 640px) { .dropi-kpis { grid-template-columns: repeat(4, 1fr); } }
        .dropi-kpi {
            background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-xl);
            padding: var(--sp-3) var(--sp-4); box-shadow: var(--shadow-sm);
        }
        .dropi-kpi strong { display: block; font-size: 1.6rem; line-height: 1.1; font-weight: 800; color: var(--tx); }
        .dropi-kpi span   { font-size: var(--text-xs); color: var(--tx-muted); font-weight: 600; }

        .dropi-aviso {
            display: flex; gap: var(--sp-3); align-items: flex-start;
            padding: var(--sp-3) var(--sp-4); border-radius: var(--r-lg);
            border: 1px solid var(--border); background: var(--surface-2);
            font-size: var(--text-sm); color: var(--tx);
        }
        .dropi-aviso i { margin-top: 3px; color: var(--tx-muted); }
        .dropi-aviso--warn { background: #FFFBEB; border-color: #FCD34D; }
        .dropi-aviso--warn i { color: #B45309; }

        .dropi-prod {
            display: grid; grid-template-columns: 1fr; gap: var(--sp-2);
            padding: var(--sp-3) 0; border-bottom: 1px solid var(--border);
        }
        .dropi-prod:last-child { border-bottom: none; }
        @media (min-width: 720px) { .dropi-prod { grid-template-columns: 1fr minmax(240px, 340px); align-items: center; gap: var(--sp-4); } }
        .dropi-prod__nombre { font-weight: 700; font-size: var(--text-sm); overflow-wrap: anywhere; }
        .dropi-prod__meta   { font-size: var(--text-xs); color: var(--tx-muted); }

        .dropi-tabla-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .dropi-tabla { width: 100%; border-collapse: collapse; font-size: var(--text-sm); }
        .dropi-tabla th {
            text-align: left; font-size: 11px; letter-spacing: .06em; text-transform: uppercase;
            color: var(--tx-muted); font-weight: 700; padding: var(--sp-2) var(--sp-3);
            border-bottom: 1px solid var(--border-strong); white-space: nowrap;
        }
        .dropi-tabla td { padding: var(--sp-3); border-bottom: 1px solid var(--border); vertical-align: top; }
        .dropi-tabla tr:last-child td { border-bottom: none; }
        .dropi-tabla .tel { color: var(--tx-muted); font-size: var(--text-xs); }
        .dropi-tabla .dropi-st { font-size: 11px; color: var(--tx-muted); display: block; margin-top: 3px; }

        .acc {
            display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 800; letter-spacing: .02em; white-space: nowrap;
        }
        .acc-nuevo     { background: var(--soft-green);  color: var(--green); }
        .acc-actualiza { background: var(--soft-blue);   color: #1D4ED8; }
        .acc-igual     { background: var(--surface-3);   color: var(--tx-muted); }
        .acc-omitida   { background: var(--soft-orange); color: #92400E; }
        .acc-detalle   { display: block; margin-top: 4px; font-size: var(--text-xs); color: var(--tx-muted); }

        .dropi-acciones { display: flex; flex-wrap: wrap; gap: var(--sp-3); align-items: center; }
        .dropi-acciones .btn-primary[disabled] { opacity: .55; cursor: not-allowed; box-shadow: none; }

        /* En celular cada fila se vuelve una tarjeta: una tabla ancha obligaría a deslizar de lado. */
        @media (max-width: 640px) {
            .dropi-tabla thead { display: none; }
            .dropi-tabla, .dropi-tabla tbody, .dropi-tabla tr, .dropi-tabla td { display: block; width: 100%; }
            .dropi-tabla tr { padding: var(--sp-3) 0; border-bottom: 1px solid var(--border); }
            .dropi-tabla tr:last-child { border-bottom: none; }
            .dropi-tabla td { border: none; padding: 2px var(--sp-1); }
            .dropi-tabla td[data-label]::before {
                content: attr(data-label) ': '; font-size: 11px; font-weight: 700; color: var(--tx-muted);
                text-transform: uppercase; letter-spacing: .05em;
            }
            .dropi-tabla td.cliente::before { content: none; }
            .dropi-tabla td.cliente { font-size: var(--text-base, 1rem); }
        }
    </style>
</head>

<body>
<?php
    $paso    = $paso ?? 'subir';
    $errores = $errores ?? [];
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? '';

    $e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $etiquetas = DropiImport::ETIQUETAS;
    $acciones  = ['nuevo' => 'Nuevo', 'actualiza' => 'Se actualiza', 'igual' => 'Sin cambios', 'omitida' => 'No se importa'];
    $plural = static fn(int $n, string $uno, string $varios) => $n . ' ' . ($n === 1 ? $uno : $varios);
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = 'Importar de Dropi';
            $pageSubtitle = 'Pasa a Seguimiento las órdenes que ya montaste en Dropi, con su estado y su guía';
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

            <?= alert_error($errores) ?>

            <div class="dropi-pasos">

            <?php if ($paso === 'subir'): ?>

                <div class="panel">
                    <div class="panel__head">
                        <h2><i class="fas fa-file-excel" aria-hidden="true"></i> Sube la descarga de Dropi</h2>
                    </div>
                    <div class="panel__body">
                        <form method="post" enctype="multipart/form-data" id="formSubir"
                              action="<?= BASE_URL ?>/AdminPlantillasWa/importarDropiVista">
                            <?= csrf_field() ?>
                            <div class="form-group form-group--full">
                                <label for="archivo">Archivo Excel (.xlsx)</label>
                                <input type="file" id="archivo" name="archivo" class="form-input"
                                       accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                                <small class="help">
                                    En Dropi filtra tus órdenes por fecha y descárgalas en Excel. Usa la descarga que
                                    incluye los productos: es la que trae producto y cantidad.
                                </small>
                            </div>
                            <button type="submit" class="btn-primary" style="margin-top:var(--sp-4)" id="btnSubir">
                                <i class="fas fa-magnifying-glass"></i> Revisar archivo
                            </button>
                        </form>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel__head"><h2><i class="fas fa-circle-info" aria-hidden="true"></i> Cómo funciona</h2></div>
                    <div class="panel__body">
                        <ul style="margin:0;padding-left:1.1rem;display:grid;gap:var(--sp-2);font-size:var(--text-sm);color:var(--tx)">
                            <li>Primero ves una vista previa; no se guarda nada hasta que confirmes.</li>
                            <li>Cada cliente queda una sola vez, por teléfono. Si tiene dos órdenes, se queda la más reciente.</li>
                            <li>Puedes subir el mismo archivo otra vez, o uno con más fechas: lo que ya está no se duplica, solo se actualiza.</li>
                            <li>Los estados se pasan solos: guía generada, en procesamiento y en espera de ruta quedan <strong>Confirmado</strong>;
                                en bodega, despachada y en reparto quedan <strong>Enviado</strong>; reclame en oficina queda <strong>En oficina</strong>;
                                entregado y cancelado igual.</li>
                            <li>Si Dropi no cambió el estatus de una orden desde la última vez, el estado que pusiste a mano en Seguimiento se respeta.</li>
                            <li>A nadie se le marca como “avisado”: eso solo pasa cuando envías el mensaje desde Seguimiento.</li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($paso === 'previa'):
                $c = $previa['conteo'];
                $porImportar = $c['nuevo'] + $c['actualiza'];

                // Primero lo que cambia; lo que no cambia, al final.
                $orden = ['nuevo' => 0, 'actualiza' => 1, 'omitida' => 2, 'igual' => 3];
                $filas = $previa['filas'];
                usort($filas, fn($a, $b) => $orden[$a['accion']] <=> $orden[$b['accion']]);
                $maxFilas = 200;
            ?>

                <form method="post" action="<?= BASE_URL ?>/AdminPlantillasWa/importarDropiConfirmar" id="formImportar" class="dropi-pasos">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= $e($token) ?>">

                    <div class="dropi-kpis">
                        <div class="dropi-kpi"><strong><?= (int)$c['nuevo'] ?></strong><span>Clientes nuevos</span></div>
                        <div class="dropi-kpi"><strong><?= (int)$c['actualiza'] ?></strong><span>Se actualizan</span></div>
                        <div class="dropi-kpi"><strong><?= (int)$c['igual'] ?></strong><span>Sin cambios</span></div>
                        <div class="dropi-kpi"><strong><?= (int)$c['omitida'] + count($omitidas) ?></strong><span>No se importan</span></div>
                    </div>

                    <?php if (!empty($previa['estatus_desconocidos'])): ?>
                        <div class="dropi-aviso dropi-aviso--warn" role="note">
                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <div>
                                <strong>Estatus de Dropi que no conozco:</strong>
                                <?php foreach ($previa['estatus_desconocidos'] as $est => $n): ?>
                                    <?= $e($est) ?> (<?= (int)$n ?>)<?= $est !== array_key_last($previa['estatus_desconocidos']) ? ',' : '' ?>
                                <?php endforeach; ?>.
                                A esos pedidos no les cambio el estado (los nuevos entran como Confirmado). Avísame y lo agrego.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($previa['ubicaciones_sin_reconocer'])): ?>
                        <div class="dropi-aviso" role="note">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <div>
                                No reconocí este municipio en la lista de la tienda: <strong><?= $e(implode(', ', $previa['ubicaciones_sin_reconocer'])) ?></strong>.
                                Se guarda como lo escribe Dropi, pero en Seguimiento tendrás que elegirlo a mano al componer el mensaje.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($previa['coinciden_landing'])): ?>
                        <div class="dropi-aviso" role="note">
                            <i class="fas fa-store" aria-hidden="true"></i>
                            <div>
                                <?= $plural((int)$previa['coinciden_landing'], 'cliente de este archivo también tiene', 'clientes de este archivo también tienen') ?>
                                un pedido de la landing. Los pedidos de la landing no se tocan.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($omitidas)): ?>
                        <div class="dropi-aviso dropi-aviso--warn" role="note">
                            <i class="fas fa-phone-slash" aria-hidden="true"></i>
                            <div>
                                <?= $plural(count($omitidas), 'orden no se importa', 'órdenes no se importan') ?> porque no traen un teléfono válido
                                (ID de Dropi: <?= $e(implode(', ', array_column($omitidas, 'id'))) ?>).
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($productosDropi)): ?>
                        <div class="panel">
                            <div class="panel__head">
                                <h2><i class="fas fa-link" aria-hidden="true"></i> ¿Qué producto de tu tienda es cada uno de Dropi?</h2>
                            </div>
                            <div class="panel__body">
                                <small class="help" style="display:block;margin-bottom:var(--sp-2)">
                                    Con esto las órdenes entran con el nombre de tu producto (así lo reconocen los mensajes y las
                                    notificaciones de pedidos recientes). Se guarda y no te lo vuelvo a preguntar.
                                    Si el producto todavía no está en tu tienda, déjalo en “ninguno”: se usa el nombre de Dropi.
                                </small>
                                <?php foreach ($productosDropi as $dropiId => $p): $sel = $mapa[$dropiId] ?? null; ?>
                                    <div class="dropi-prod">
                                        <div>
                                            <div class="dropi-prod__nombre"><?= $e($p['nombre']) ?></div>
                                            <div class="dropi-prod__meta">ID en Dropi <?= $e($dropiId) ?> · <?= $plural((int)$p['ordenes'], 'orden', 'órdenes') ?></div>
                                        </div>
                                        <select name="producto[<?= $e($dropiId) ?>]" class="form-select"
                                                aria-label="Producto de tu tienda para <?= $e($p['nombre']) ?>">
                                            <option value="0">— Ninguno: dejo el nombre de Dropi —</option>
                                            <?php foreach ($locales as $lid => $l): ?>
                                                <option value="<?= (int)$lid ?>" <?= $sel === $lid ? 'selected' : '' ?>>
                                                    <?= $e($l['nombre']) ?><?= $l['activo'] ? '' : ' (inactivo)' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="panel">
                        <div class="panel__head">
                            <h2><i class="fas fa-list-check" aria-hidden="true"></i> Vista previa · <?= $e($nombreArchivo) ?></h2>
                        </div>
                        <div class="panel__body">
                            <div class="dropi-tabla-wrap">
                                <table class="dropi-tabla">
                                    <thead>
                                        <tr><th>Cliente</th><th>Producto</th><th>Municipio</th><th>Estado en Seguimiento</th><th>Qué pasa</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach (array_slice($filas, 0, $maxFilas) as $f): ?>
                                        <tr>
                                            <td class="cliente">
                                                <strong><?= $e($f['nombre'] !== '' ? $f['nombre'] : 'Sin nombre') ?></strong>
                                                <div class="tel"><?= $e($f['telefono']) ?></div>
                                            </td>
                                            <td data-label="Producto"><?= $e($f['producto']) ?></td>
                                            <td data-label="Municipio"><?= $e($f['ciudad']) ?></td>
                                            <td data-label="Estado">
                                                <?php if ($f['estado'] !== ''): ?>
                                                    <span class="status-tag status-<?= $e($f['estado']) ?>"><?= $e($etiquetas[$f['estado']] ?? $f['estado']) ?></span>
                                                <?php else: ?>—<?php endif; ?>
                                                <span class="dropi-st">Dropi: <?= $e($f['estatus']) ?></span>
                                            </td>
                                            <td>
                                                <span class="acc acc-<?= $e($f['accion']) ?>"><?= $e($acciones[$f['accion']] ?? $f['accion']) ?></span>
                                                <span class="acc-detalle"><?= $e($f['detalle']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($filas) > $maxFilas): ?>
                                <p class="help" style="margin-top:var(--sp-3)">
                                    Se muestran las primeras <?= $maxFilas ?> de <?= count($filas) ?>; se importan todas.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dropi-acciones">
                        <button type="submit" class="btn-primary" id="btnImportar" <?= $porImportar === 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-file-import"></i>
                            <?= $porImportar === 0 ? 'No hay nada nuevo para importar' : 'Importar ' . $plural($porImportar, 'orden', 'órdenes') ?>
                        </button>
                        <a href="<?= BASE_URL ?>/AdminPlantillasWa/importarDropi" class="btn-detail">Cancelar y subir otro archivo</a>
                    </div>
                </form>

            <?php elseif ($paso === 'resultado'):
                $c = $resultado['conteo'];
            ?>

                <div class="panel">
                    <div class="panel__head">
                        <h2><i class="fas fa-circle-check" aria-hidden="true"></i> Importación lista</h2>
                    </div>
                    <div class="panel__body">
                        <div class="dropi-kpis">
                            <div class="dropi-kpi"><strong><?= (int)$c['nuevo'] ?></strong><span>Clientes nuevos</span></div>
                            <div class="dropi-kpi"><strong><?= (int)$c['actualiza'] ?></strong><span>Actualizados</span></div>
                            <div class="dropi-kpi"><strong><?= (int)$c['igual'] ?></strong><span>Sin cambios</span></div>
                            <div class="dropi-kpi"><strong><?= (int)$c['omitida'] + count($omitidas) ?></strong><span>No importados</span></div>
                        </div>
                        <div class="dropi-acciones" style="margin-top:var(--sp-4)">
                            <a href="<?= BASE_URL ?>/AdminPlantillasWa/index" class="btn-primary"><i class="fab fa-whatsapp"></i> Ir a Seguimiento</a>
                            <a href="<?= BASE_URL ?>/AdminPlantillasWa/importarDropi" class="btn-detail">Importar otro archivo</a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($resultado['en_oficina_sin_aviso'])): ?>
                    <div class="panel">
                        <div class="panel__head">
                            <h2><i class="fas fa-box-open" aria-hidden="true"></i>
                                En oficina y sin aviso (<?= count($resultado['en_oficina_sin_aviso']) ?>)</h2>
                        </div>
                        <div class="panel__body">
                            <small class="help" style="display:block;margin-bottom:var(--sp-2)">
                                Dropi dice que estos pedidos ya están por reclamar en la oficina y en Seguimiento no hay aviso registrado.
                                Búscalos por teléfono en Seguimiento y envíales el mensaje de “en oficina”.
                            </small>
                            <div class="dropi-tabla-wrap">
                                <table class="dropi-tabla">
                                    <thead><tr><th>Cliente</th><th>Teléfono</th><th>Producto</th><th>Municipio</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($resultado['en_oficina_sin_aviso'] as $r): ?>
                                        <tr>
                                            <td class="cliente"><strong><?= $e($r['nombre']) ?></strong></td>
                                            <td data-label="Teléfono"><?= $e($r['telefono']) ?></td>
                                            <td data-label="Producto"><?= $e($r['producto']) ?></td>
                                            <td data-label="Municipio"><?= $e($r['ciudad']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($resultado['novedades'])): ?>
                    <div class="panel">
                        <div class="panel__head">
                            <h2><i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                                Con novedad abierta (<?= count($resultado['novedades']) ?>)</h2>
                        </div>
                        <div class="panel__body">
                            <small class="help" style="display:block;margin-bottom:var(--sp-2)">
                                La transportadora reportó un problema con la entrega y en Dropi todavía no está resuelto.
                            </small>
                            <div class="dropi-tabla-wrap">
                                <table class="dropi-tabla">
                                    <thead><tr><th>Cliente</th><th>Teléfono</th><th>Municipio</th><th>Novedad</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($resultado['novedades'] as $r): ?>
                                        <tr>
                                            <td class="cliente"><strong><?= $e($r['nombre']) ?></strong></td>
                                            <td data-label="Teléfono"><?= $e($r['telefono']) ?></td>
                                            <td data-label="Municipio"><?= $e($r['ciudad']) ?></td>
                                            <td data-label="Novedad"><?= $e($r['novedad']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            </div>
        </section>
    </main>

</div>

<script>
// Evita el doble clic: una importación a medias dos veces seguidas no daña nada (no duplica),
// pero el segundo envío llegaría con la vista previa ya vencida y mostraría un error.
document.addEventListener('DOMContentLoaded', function () {
    [['formSubir', 'btnSubir', 'Revisando…'], ['formImportar', 'btnImportar', 'Importando…']].forEach(function (p) {
        var form = document.getElementById(p[0]);
        var btn  = document.getElementById(p[1]);
        if (!form || !btn) return;
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.textContent = p[2];
        });
    });
});
</script>

</body>
</html>
