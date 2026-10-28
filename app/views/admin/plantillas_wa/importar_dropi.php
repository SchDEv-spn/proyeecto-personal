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

        .dropi-aviso {
            display: flex; gap: var(--sp-3); align-items: flex-start;
            padding: var(--sp-3) var(--sp-4); border-radius: var(--r-lg);
            border: 1px solid var(--border); background: var(--surface-2);
            font-size: var(--text-sm); color: var(--tx);
        }
        .dropi-aviso i { margin-top: 3px; color: var(--tx-muted); }
        .dropi-aviso--warn { background: #FFFBEB; border-color: #FCD34D; }
        .dropi-aviso--warn i { color: #B45309; }

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
        .dropi-tabla.ocultar-iguales tr.fila-igual { display: none !important; }

        .acc {
            display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 999px;
            font-size: 11px; font-weight: 800; letter-spacing: .02em; white-space: nowrap;
        }
        .acc-nuevo     { background: var(--soft-green);  color: var(--green); }
        .acc-actualiza { background: var(--soft-blue);   color: #1D4ED8; }
        .acc-igual     { background: var(--surface-3);   color: var(--tx-muted); }
        .acc-detalle   { display: block; margin-top: 4px; font-size: var(--text-xs); color: var(--tx-muted); }

        .dropi-acciones { display: flex; flex-wrap: wrap; gap: var(--sp-3); align-items: center; }
        .dropi-barra { justify-content: flex-start; margin-top: var(--sp-5); padding: var(--sp-3) 0; }

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
    $acciones  = ['nuevo' => 'Nueva', 'actualiza' => 'Se actualiza', 'igual' => 'Sin cambios'];
    $plural = static fn(int $n, string $uno, string $varios) => $n . ' ' . ($n === 1 ? $uno : $varios);

    // Cuatro tarjetas con el mismo estilo del dashboard.
    $tarjetasConteo = static function (array $c, int $noImporta, array $titulos) use ($e): string {
        $iconos = [['fa-plus', 'glow-green'], ['fa-rotate', 'glow-blue'], ['fa-equals', 'glow-gold'], ['fa-ban', 'glow-red']];
        $valores = [(int)$c['nuevo'], (int)$c['actualiza'], (int)$c['igual'], $noImporta];
        $html = '<div class="stats-grid stats-grid--4col stats-grid--compacta">';
        foreach ($titulos as $i => $t) {
            $html .= '<div class="stat-card ' . $iconos[$i][1] . '"><div class="stat-info"><small>' . $e($t) . '</small><h2>' . $valores[$i]
                . '</h2></div><i class="fas ' . $iconos[$i][0] . ' stat-icon" aria-hidden="true"></i></div>';
        }
        return $html . '</div>';
    };
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = 'Importar de Dropi';
            $pageSubtitle = 'Sube tus órdenes de Dropi: a quién avisarle, quién espera recogida y todo el historial';
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
                                    En Dropi filtra tus órdenes por fecha (15 días o más) y descárgalas en Excel. Usa la descarga que
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
                            <li><strong>Hazlo todos los días, después de las 10 a. m.:</strong> Dropi actualiza hacia esa hora si el paquete llegó a la oficina.</li>
                            <li>Primero ves una vista previa; no se guarda nada hasta que confirmes.</li>
                            <li>Cada orden se guarda una sola vez, por su número de Dropi. Subir lo mismo otra vez, o un rango más amplio, no duplica nada: solo se actualizan el estatus y la guía.</li>
                            <li>Las que ya llegaron a la oficina quedan en <strong>Por avisar hoy</strong>, con el botón para componer el mensaje, hasta que se lo envíes al cliente.</li>
                            <li>También sirve para cargar meses anteriores: todo queda guardado en Seguimiento para consultarlo cuando quieras.</li>
                            <li>El estado sale siempre de Dropi: en procesamiento y en espera de ruta son <strong>Confirmado</strong>;
                                guía generada (la transportadora ya va por el paquete), en bodega, despachada, en reparto y novedad son <strong>Enviado</strong>; reclame en oficina es <strong>En oficina</strong>;
                                entregado, cancelado y devolución igual (las devoluciones suman lo que te cobran de flete de regreso).</li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($paso === 'previa'):
                $c = $previa['conteo'];
                $total = $c['nuevo'] + $c['actualiza'] + $c['igual'];

                // Primero lo que cambia; lo que no cambia, al final.
                $orden = ['nuevo' => 0, 'actualiza' => 1, 'igual' => 2];
                $filas = $previa['filas'];
                usort($filas, fn($a, $b) => $orden[$a['accion']] <=> $orden[$b['accion']]);
                $maxFilas = 300;
            ?>

                <form method="post" action="<?= BASE_URL ?>/AdminPlantillasWa/importarDropiConfirmar" id="formImportar">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= $e($token) ?>">

                    <div class="dropi-pasos">

                    <?= $tarjetasConteo($c, count($omitidas), ['Órdenes nuevas', 'Se actualizan', 'Sin cambios', 'No se importan']) ?>

                    <?php if (!empty($previa['estatus_desconocidos'])): ?>
                        <div class="dropi-aviso dropi-aviso--warn" role="note">
                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <div>
                                <strong>Estatus de Dropi que no conozco:</strong>
                                <?php foreach ($previa['estatus_desconocidos'] as $est => $n): ?>
                                    <?= $e($est) ?> (<?= (int)$n ?>)<?= $est !== array_key_last($previa['estatus_desconocidos']) ? ',' : '' ?>
                                <?php endforeach; ?>.
                                Esas órdenes se guardan como “Sin clasificar” y las ves en “Todas las órdenes”. Avísame y lo agrego.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($previa['ubicaciones_sin_reconocer'])): ?>
                        <div class="dropi-aviso" role="note">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <div>
                                Estos lugares no están en la lista de municipios de la tienda: <strong><?= $e(implode(', ', $previa['ubicaciones_sin_reconocer'])) ?></strong>.
                                No hay nada que hacer: se guardan como los escribe Dropi y los mensajes salen igual.
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
                                    Si dice <b>sugerido</b>, es por el parecido del nombre: confírmalo. Si el producto todavía no está en tu tienda, déjalo en
                                    “ninguno”: se usa el nombre de Dropi y después te lo recuerdo en Seguimiento.
                                </small>
                                <?php foreach ($productosDropi as $dropiId => $p): $sel = $mapa[$dropiId] ?? null; $sugerido = $sel === null ? ($sugerencias[$dropiId] ?? null) : null; ?>
                                    <div class="dropi-prod">
                                        <div>
                                            <div class="dropi-prod__nombre"><?= $e($p['nombre']) ?></div>
                                            <div class="dropi-prod__meta">ID en Dropi <?= $e($dropiId) ?> · <?= $plural((int)$p['ordenes'], 'orden', 'órdenes') ?></div>
                                        </div>
                                        <select name="producto[<?= $e($dropiId) ?>]" class="form-select"
                                                aria-label="Producto de tu tienda para <?= $e($p['nombre']) ?>">
                                            <option value="0">— Ninguno: dejo el nombre de Dropi —</option>
                                            <?php foreach ($locales as $lid => $l): ?>
                                                <option value="<?= (int)$lid ?>" <?= ($sel === $lid || $sugerido === $lid) ? 'selected' : '' ?>>
                                                    <?= $e($l['nombre']) ?><?= $l['activo'] ? '' : ' (inactivo)' ?><?= $sugerido === $lid ? ' — sugerido' : '' ?>
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
                            <?php if ($c['igual'] > 0): ?>
                                <label style="display:flex;gap:var(--sp-2);align-items:center;font-size:var(--text-sm);margin-bottom:var(--sp-3);cursor:pointer;">
                                    <input type="checkbox" id="verIguales">
                                    Mostrar también las <?= (int)$c['igual'] ?> que no cambiaron
                                </label>
                            <?php endif; ?>
                            <div class="dropi-tabla-wrap">
                                <table class="dropi-tabla ocultar-iguales" id="tablaPrevia">
                                    <thead>
                                        <tr><th>Cliente</th><th>Producto</th><th>Municipio</th><th>Estado</th><th>Qué pasa</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach (array_slice($filas, 0, $maxFilas) as $f): ?>
                                        <tr class="<?= $f['accion'] === 'igual' ? 'fila-igual' : '' ?>">
                                            <td class="cliente">
                                                <strong><?= $e($f['nombre'] !== '' ? $f['nombre'] : 'Sin nombre') ?></strong>
                                                <div class="tel"><?= $e($f['telefono']) ?></div>
                                            </td>
                                            <td data-label="Producto"><?= $e($f['producto']) ?></td>
                                            <td data-label="Municipio"><?= $e($f['ciudad']) ?></td>
                                            <td data-label="Estado">
                                                <span class="status-tag status-<?= $e($f['estado']) ?>"><?= $e($etiquetas[$f['estado']] ?? $f['estado']) ?></span>
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

                    </div>

                    <?php /* Barra pegada al borde de abajo: con cientos de órdenes el botón no queda al final de un scroll larguísimo.
                             Va fuera de la cuadrícula .dropi-pasos: un hijo de grid no puede "pegarse" más allá de su propia celda. */ ?>
                    <div class="dropi-acciones admin-form-actions dropi-barra">
                        <button type="submit" class="btn-primary" id="btnImportar">
                            <i class="fas fa-file-import"></i>
                            Importar <?= $plural($total, 'orden', 'órdenes') ?>
                            (<?= (int)$c['nuevo'] ?> <?= $c['nuevo'] === 1 ? 'nueva' : 'nuevas' ?>, <?= (int)$c['actualiza'] ?> <?= $c['actualiza'] === 1 ? 'se actualiza' : 'se actualizan' ?>)
                        </button>
                        <a href="<?= BASE_URL ?>/AdminPlantillasWa/importarDropi" class="btn-detail">Cancelar y subir otro archivo</a>
                    </div>
                </form>

            <?php elseif ($paso === 'resultado'):
                $c  = $resultado['conteo'];
                $cn = $contadores ?? [];
            ?>

                <div class="panel">
                    <div class="panel__head">
                        <h2><i class="fas fa-circle-check" aria-hidden="true"></i> Importación lista</h2>
                    </div>
                    <div class="panel__body">
                        <?= $tarjetasConteo($c, count($omitidas), ['Órdenes nuevas', 'Actualizadas', 'Sin cambios', 'No importadas']) ?>

                        <?php
                        // Qué falta avisar y de qué tipo (la cola de "Por avisar hoy").
                        $av = $cn['avisos'] ?? [];
                        $tipos = [
                            ['enviado',              'una guía por notificar', 'guías por notificar'],
                            ['en_reparto',           'uno que llega hoy',      'que llegan hoy'],
                            ['en_oficina',           'uno que llegó a la oficina', 'que llegaron a la oficina'],
                            ['novedad',              'una novedad',            'novedades'],
                            ['recordatorio_oficina', 'un recordatorio',        'recordatorios'],
                            ['ultimo_aviso_oficina', 'una última oportunidad', 'últimas oportunidades'],
                        ];
                        $partes = [];
                        foreach ($tipos as [$k, $uno, $varios]) {
                            $n = (int)($av[$k] ?? 0);
                            if ($n > 0) $partes[] = '<strong>' . ($n === 1 ? $uno : $n . ' ' . $varios) . '</strong>';
                        }
                        ?>
                        <p style="margin:var(--sp-4) 0 var(--sp-3);font-size:var(--text-sm);color:var(--tx)">
                            <?php if ((int)($cn['por_avisar'] ?? 0) > 0): ?>
                                Faltan por avisar <strong><?= $plural((int)$cn['por_avisar'], 'cliente', 'clientes') ?></strong>: <?= implode(', ', $partes) ?>.
                                Los ves agrupados por día, los más atrasados primero.
                            <?php else: ?>
                                No hay clientes pendientes de avisar: ya les escribiste a todos.
                            <?php endif; ?>
                        </p>

                        <div class="dropi-acciones">
                            <?php if ((int)($cn['por_avisar'] ?? 0) > 0): ?>
                                <a href="<?= BASE_URL ?>/AdminPlantillasWa/index?vista=por_avisar" class="btn-primary"><i class="fab fa-whatsapp"></i> Avisar ahora (<?= (int)$cn['por_avisar'] ?>)</a>
                            <?php endif; ?>
                            <?php if ((int)($cn['piden_recordatorio'] ?? 0) > 0): ?>
                                <a href="<?= BASE_URL ?>/AdminPlantillasWa/index?vista=esperando" class="btn-primary btn-primary--soft"><i class="fas fa-box-open"></i> Ver quién espera recogida</a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/AdminPlantillasWa/index" class="btn-detail">Ir a Seguimiento</a>
                            <a href="<?= BASE_URL ?>/AdminPlantillasWa/importarDropi" class="btn-detail">Importar otro archivo</a>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

            </div>
        </section>
    </main>

</div>

<script>
// Evita el doble clic: el segundo envío llegaría con la vista previa ya usada y mostraría un error.
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

    var ver = document.getElementById('verIguales');
    var tabla = document.getElementById('tablaPrevia');
    if (ver && tabla) {
        ver.addEventListener('change', function () { tabla.classList.toggle('ocultar-iguales', !ver.checked); });
    }
});
</script>

</body>
</html>
