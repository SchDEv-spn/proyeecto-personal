<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Estadísticas de la landing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1A3D2E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php
    $instalado     = $instalado     ?? false;
    $dias          = $dias          ?? 7;
    $producto_id   = $producto_id   ?? 0;
    $entorno       = $entorno       ?? 'produccion';
    $productos     = $productos     ?? [];
    $resumen       = $resumen       ?? [];
    $embudo        = $embudo        ?? [];
    $secciones     = $secciones     ?? [];
    $campos        = $campos        ?? [];
    $dispositivos  = $dispositivos  ?? [];
    $navegadores   = $navegadores   ?? [];
    $fuentes       = $fuentes       ?? [];
    $serie         = $serie         ?? [];
    $errores       = $errores       ?? [];
    $sesiones      = $sesiones      ?? [];
    $tendencias    = $tendencias    ?? [];
    $analisis      = $analisis      ?? null;
    $tiene_claude_key = $tiene_claude_key ?? false;
    $min_sesiones  = $min_sesiones  ?? 30;
    $modelos       = $modelos       ?? [];
    $modelo_elegido = $modelo_elegido ?? '';

    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? 'admin@tuempresa.com';

    $pageTitle       = 'Estadísticas de la landing';
    $pageSubtitle    = 'Cuánta gente entra y en qué punto se cae la intención de compra';
    $showRangeFilter = false;  // el rango de esta página son días, no meses
    $showSearch      = false;

    /** Enlace al mismo panel cambiando un solo filtro. */
    $filtroUrl = function (array $cambios) use ($dias, $producto_id, $entorno) {
        $q = array_merge(
            ['dias' => $dias, 'producto' => $producto_id, 'entorno' => $entorno],
            $cambios
        );
        return BASE_URL . '/AdminEstadisticas/index?' . http_build_query(array_filter(
            $q,
            fn($v) => $v !== '' && $v !== 0 && $v !== null
        ));
    };

    $minutos = function (int $seg): string {
        if ($seg < 60) return $seg . 's';
        return floor($seg / 60) . 'm ' . str_pad((string)($seg % 60), 2, '0', STR_PAD_LEFT) . 's';
    };

    /**
     * Badge de variación frente al periodo anterior. 'up' es verde y 'down'
     * rojo según si el cambio es bueno, no según si el número sube: menos
     * tiempo en página no siempre es peor, pero menos pedidos siempre lo es.
     */
    $tendencia = function (string $clave, string $sufijo = '') use ($tendencias): string {
        $t = $tendencias[$clave] ?? null;
        $extra = $sufijo !== '' ? '<span class="kpi-vs">' . htmlspecialchars($sufijo) . '</span>' : '';

        if (!$t || ($t['dir'] ?? 'flat') === 'flat') {
            return '<span class="kpi-trend flat">' . htmlspecialchars($t['label'] ?? '—') . '</span>' . $extra;
        }
        $icono = $t['dir'] === 'up' ? '↑' : '↓';
        return '<span class="kpi-trend ' . htmlspecialchars($t['dir']) . '">'
            . $icono . ' ' . htmlspecialchars($t['label']) . '</span>' . $extra;
    };
    ?>

    <div class="sidebar-overlay" aria-hidden="true"></div>

    <div class="app-shell">
        <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

        <main class="material-main">
            <?php require __DIR__ . '/../partials/_header.php'; ?>

            <section class="material-content">

                <?php $pixel_activo = $pixel_activo ?? false; ?>
                <span class="pixel-status <?= $pixel_activo ? 'pixel-status--on' : 'pixel-status--off' ?>">
                    <i class="fas fa-circle" aria-hidden="true"></i>
                    Pixel: <?= $pixel_activo ? 'activo' : 'apagado (entorno local)' ?>
                </span>

            <?php if (!$instalado): ?>

                <div class="panel">
                    <div class="panel__head"><h2>Todavía no hay datos</h2></div>
                    <div class="panel__body">
                        <p style="color:var(--tx-muted);font-size:14px;line-height:1.6">
                            Las tablas de analítica se crean solas con la primera visita a la
                            landing. Abre la landing pública una vez y vuelve aquí.
                        </p>
                        <p style="color:var(--tx-dim);font-size:13px;margin-top:12px">
                            La definición de las tablas está en <code>app/models/LandingAnalytics.php</code>.
                        </p>
                    </div>
                </div>

            <?php else: ?>

                <!-- ══ Filtros ══
                     Una sola barra: el periodo como control segmentado (son
                     siempre 5 opciones) y el producto en un desplegable. Con
                     chips, cada producto nuevo añadía otra ficha y la página
                     empezaba con un muro de botones antes del primer dato. -->
                <div class="stats-bar">
                    <div class="seg" role="group" aria-label="Periodo">
                        <?php foreach ([1 => 'Hoy', 7 => '7 días', 15 => '15 días', 30 => '30 días', 90 => '90 días'] as $d => $lbl): ?>
                            <a href="<?= htmlspecialchars($filtroUrl(['dias' => $d])) ?>"
                               class="seg__item <?= $dias === $d ? 'is-active' : '' ?>"
                               <?= $dias === $d ? 'aria-current="page"' : '' ?>><?= $lbl ?></a>
                        <?php endforeach; ?>
                    </div>

                    <div class="stats-bar__right">
                        <label class="stats-select">
                            <i class="fas fa-box" aria-hidden="true"></i>
                            <select id="filtroProducto" aria-label="Filtrar por producto">
                                <option value="<?= htmlspecialchars($filtroUrl(['producto' => 0])) ?>"
                                        <?= $producto_id === 0 ? 'selected' : '' ?>>Todos los productos</option>
                                <?php foreach ($productos as $p): ?>
                                    <option value="<?= htmlspecialchars($filtroUrl(['producto' => (int)$p['id']])) ?>"
                                            <?= $producto_id === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nombre'] ?? ('#' . $p['id'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <!-- Las visitas desde XAMPP se guardan aparte para no
                             sesgar los promedios reales; este interruptor es el
                             único sitio donde se pueden mirar. -->
                        <div class="seg seg--sm" role="group" aria-label="Origen de los datos">
                            <a href="<?= htmlspecialchars($filtroUrl(['entorno' => 'produccion'])) ?>"
                               class="seg__item <?= $entorno === 'produccion' ? 'is-active' : '' ?>">Tráfico real</a>
                            <a href="<?= htmlspecialchars($filtroUrl(['entorno' => 'local'])) ?>"
                               class="seg__item <?= $entorno === 'local' ? 'is-active' : '' ?>">Pruebas</a>
                        </div>
                    </div>
                </div>

                <?php if ($entorno === 'local'): ?>
                    <div class="stats-aviso">
                        <i class="fas fa-flask" aria-hidden="true"></i>
                        <span>Estás viendo visitas de prueba desde XAMPP, no tráfico real.</span>

                        <!-- Borrar las pruebas requería entrar a phpMyAdmin.
                             El POST lleva CSRF y el borrado filtra por entorno
                             en el modelo: desde aquí no se puede tocar el
                             tráfico real ni cambiando la petición. -->
                        <form method="POST" action="<?= BASE_URL ?>/AdminEstadisticas/limpiarPruebas"
                              onsubmit="return confirm('¿Borrar todas las visitas de prueba? El tráfico real no se toca.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="producto" value="<?= (int)$producto_id ?>">
                            <button type="submit" class="btn-aviso">
                                <i class="fas fa-broom" aria-hidden="true"></i> Borrar pruebas
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- ══ KPIs ══ -->
                <h2 class="stats-head">Resumen del periodo</h2>
                <!-- --4col: son cuatro tarjetas, y el grid base reparte en 3 o 5
                     columnas. Sin esta variante la cuarta queda sola en una
                     fila en pantallas medianas. -->
                <div class="stats-grid stats-grid--4col">
                    <div class="stat-card glow-purple">
                        <div class="stat-info">
                            <small>Visitas</small>
                            <h2><?= number_format((int)$resumen['sesiones'], 0, ',', '.') ?></h2>
                            <span class="target"><?= $tendencia('sesiones', ' vs. ' . $dias . 'd previos') ?></span>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>

                    <div class="stat-card glow-green">
                        <div class="stat-info">
                            <small>Pedidos</small>
                            <h2><?= number_format((int)$resumen['pedidos'], 0, ',', '.') ?></h2>
                            <span class="target"><?= $tendencia('pedidos', ' · ' . number_format((float)$resumen['conversion'], 2, ',', '.') . '% conv.') ?></span>
                        </div>
                        <i class="fas fa-cart-shopping stat-icon"></i>
                    </div>

                    <div class="stat-card glow-blue">
                        <div class="stat-info">
                            <!-- Rótulo corto a propósito: "Con intención de compra"
                                 se partía en dos líneas y bajaba la cifra respecto
                                 a las otras tres tarjetas. -->
                            <small>Con intención</small>
                            <h2><?= number_format((int)$resumen['con_intencion'], 0, ',', '.') ?></h2>
                            <span class="target"><?= $tendencia('intencion', ' · ' . number_format((int)$resumen['empezaron_form'], 0, ',', '.') . ' al formulario') ?></span>
                        </div>
                        <i class="fas fa-hand-pointer stat-icon"></i>
                    </div>

                    <div class="stat-card glow-gold">
                        <div class="stat-info">
                            <small>Tiempo medio</small>
                            <h2><?= $minutos((int)$resumen['dur_media']) ?></h2>
                            <span class="target"><?= $tendencia('dur_media', ' · scroll ' . (int)$resumen['scroll_medio'] . '%') ?></span>
                        </div>
                        <i class="fas fa-stopwatch stat-icon"></i>
                    </div>
                </div>

                <!-- ══ Evolución ══
                     Va con los KPIs: es el mismo resumen del periodo, pero
                     repartido por días. -->
                <div class="panel">
                    <div class="panel__head">
                        <h2>Visitas y pedidos por día</h2>
                        <span class="chip">Evolución</span>
                    </div>
                    <div class="panel__body">
                        <canvas id="chartVisitas" height="110"></canvas>
                    </div>
                </div>

                <!-- ══ Embudo ══ -->
                <h2 class="stats-head">El recorrido</h2>
                <div class="panel">
                    <div class="panel__head">
                        <h2>Dónde se cae la intención</h2>
                        <span class="chip">Embudo</span>
                    </div>
                    <div class="panel__body">
                        <?php if (empty($embudo) || (int)$resumen['sesiones'] === 0): ?>
                            <p class="stats-empty">Sin visitas registradas en este periodo.</p>
                        <?php else: ?>
                            <div class="lfunnel">
                                <div class="lfunnel-row lfunnel-row--head" aria-hidden="true">
                                    <div class="lfunnel-row__label">Paso</div>
                                    <div></div>
                                    <div class="lfunnel-row__n">Visitas</div>
                                    <div class="lfunnel-row__drop">Se pierden</div>
                                </div>

                                <?php foreach ($embudo as $paso): ?>
                                    <div class="lfunnel-row">
                                        <div class="lfunnel-row__label">
                                            <span class="lfunnel-row__step"><?= (int)$paso['paso'] ?></span>
                                            <span><?= htmlspecialchars($paso['nombre']) ?></span>
                                        </div>

                                        <!-- La barra va limpia y el número al lado: dentro del
                                             relleno oscuro había que taparlo con un halo y en
                                             los pasos cortos se salía de la barra. -->
                                        <div class="lfunnel-row__bar" role="presentation">
                                            <div class="lfunnel-row__fill" style="width: <?= max(1.5, (float)$paso['pct_total']) ?>%"></div>
                                        </div>

                                        <div class="lfunnel-row__n">
                                            <strong><?= number_format((int)$paso['sesiones'], 0, ',', '.') ?></strong>
                                            <small><?= number_format((float)$paso['pct_total'], 1, ',', '.') ?>%</small>
                                        </div>

                                        <div class="lfunnel-row__drop">
                                            <?php if ((int)$paso['paso'] > 1 && (int)$paso['abandonaron'] > 0): ?>
                                                <span class="drop-tag">
                                                    −<?= number_format((int)$paso['abandonaron'], 0, ',', '.') ?>
                                                    · <?= number_format((float)$paso['pct_caida'], 1, ',', '.') ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="drop-tag drop-tag--none">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="stats-hint">
                                La columna de la derecha es cuánta gente se perdió respecto al paso anterior.
                                El paso con el porcentaje más alto es el que hay que arreglar primero.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ══ Lectura del embudo con Claude ══
                     Va justo debajo del embudo porque es su interpretación:
                     primero ves dónde se cae la gente, después qué hacer.
                     Solo corre al pulsar el botón — la llamada cuesta dinero
                     y el panel tiene que seguir abriéndose al instante. -->
                <div class="panel panel--ia">
                    <div class="panel__head">
                        <h2>Qué dicen estos números</h2>
                        <span class="chip">Claude</span>
                    </div>
                    <div class="panel__body">

                        <?php if (!$tiene_claude_key): ?>
                            <p class="stats-empty">
                                Para leer el embudo con IA hace falta la API key de Claude.
                                Se configura en <a href="<?= BASE_URL ?>/AdminMarketing/index">Marketing IA</a>.
                            </p>
                        <?php else: ?>
                            <div class="ia-barra">
                                <p class="ia-meta" id="iaMeta">
                                    <?php if ($analisis): ?>
                                        Último análisis: <?= date('d/m/Y H:i', strtotime((string)$analisis['creado_en'])) ?>
                                        · <?= (int)$analisis['sesiones'] ?> visitas · <?= (int)$analisis['periodo_dias'] ?> días
                                        <?php $usado = $modelos[$analisis['modelo']]['nombre'] ?? $analisis['modelo']; ?>
                                        · con <?= htmlspecialchars((string)$usado) ?>
                                    <?php else: ?>
                                        Todavía no has analizado este periodo.
                                    <?php endif; ?>
                                </p>

                                <div class="ia-controles">
                                    <!-- El modelo se elige aquí y se recuerda para la
                                         próxima. Sonnet cuesta menos de la mitad; Opus
                                         tiene mejor criterio para no sacar conclusiones
                                         de muestras pequeñas, que es el riesgo de esto. -->
                                    <label class="stats-select stats-select--sm">
                                        <i class="fas fa-microchip" aria-hidden="true"></i>
                                        <select id="iaModelo" aria-label="Modelo de Claude">
                                            <?php foreach ($modelos as $id => $m): ?>
                                                <option value="<?= htmlspecialchars($id) ?>"
                                                        <?= $modelo_elegido === $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($m['nombre']) ?> · <?= htmlspecialchars($m['nota']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>

                                    <button type="button" class="btn-ia" id="btnAnalizar"
                                            data-dias="<?= (int)$dias ?>"
                                            data-producto="<?= (int)$producto_id ?>"
                                            data-entorno="<?= htmlspecialchars($entorno) ?>">
                                        <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                                        <span><?= $analisis ? 'Volver a analizar' : 'Analizar con IA' ?></span>
                                    </button>
                                </div>
                            </div>

                            <?php if ((int)$resumen['sesiones'] < $min_sesiones): ?>
                                <p class="stats-hint">
                                    Con <?= (int)$resumen['sesiones'] ?> visitas el análisis no es fiable:
                                    hacen falta al menos <?= (int)$min_sesiones ?> en el periodo para que
                                    los porcentajes signifiquen algo.
                                </p>
                            <?php endif; ?>

                            <div id="iaEstado" class="ia-estado" hidden></div>
                            <div id="iaResultado"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ══ Secciones y campos ══ -->
                <div class="stats-cols">
                    <div class="panel">
                        <div class="panel__head">
                            <h2>Hasta qué sección llegaron</h2>
                            <span class="chip">Scroll</span>
                        </div>
                        <div class="panel__body">
                            <?php if (empty($secciones)): ?>
                                <p class="stats-empty">Sin datos de secciones todavía.</p>
                            <?php else: ?>
                                <table class="stats-table">
                                    <thead><tr><th>Sección donde se quedó</th><th>Visitas</th><th></th><th>%</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($secciones as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['etiqueta']) ?></td>
                                            <td><?= number_format((int)$s['n'], 0, ',', '.') ?></td>
                                            <!-- Barra y cifra en celdas propias: juntas, la barra
                                                 se desplazaba según el ancho del porcentaje y
                                                 ninguna quedaba alineada con la de arriba. -->
                                            <td class="cel-barra">
                                                <span class="mini-bar"><span style="width: <?= (float)$s['pct'] ?>%"></span></span>
                                            </td>
                                            <td><?= number_format((float)$s['pct'], 1, ',', '.') ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__head">
                            <h2>Dónde abandonan el formulario</h2>
                            <span class="chip">Campos</span>
                        </div>
                        <div class="panel__body">
                            <?php if (empty($campos)): ?>
                                <p class="stats-empty">Nadie ha llegado a escribir en el formulario en este periodo.</p>
                            <?php else: ?>
                                <table class="stats-table">
                                    <thead><tr><th>Último campo llenado</th><th>Visitas</th><th>Pedidos</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($campos as $c): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($c['etiqueta']) ?></td>
                                            <td><?= number_format((int)$c['n'], 0, ',', '.') ?></td>
                                            <td><?= number_format((int)$c['pedidos'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <p class="stats-hint">
                                    Una fila con muchas visitas y pocos pedidos es un campo que está
                                    frenando la compra.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ══ Segmentos ══ -->
                <h2 class="stats-head">Quién entra</h2>
                <div class="stats-cols stats-cols--3">
                    <?php
                    $segmentos = [
                        ['Dispositivo',     'Aparato',   $dispositivos],
                        ['Navegador',       'App',       $navegadores],
                        ['De dónde vienen', 'Origen',    $fuentes],
                    ];
                    foreach ($segmentos as [$titulo, $columna, $filas]):
                    ?>
                        <div class="panel">
                            <div class="panel__head">
                                <h2><?= $titulo ?></h2>
                            </div>
                            <div class="panel__body">
                                <?php if (empty($filas)): ?>
                                    <p class="stats-empty">Sin datos.</p>
                                <?php else: ?>
                                    <table class="stats-table stats-table--num">
                                        <thead><tr><th><?= $columna ?></th><th>Visitas</th><th>Conv.</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($filas as $f): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)$f['etiqueta']) ?></td>
                                                <td><?= number_format((int)$f['n'], 0, ',', '.') ?></td>
                                                <td><?= number_format((float)$f['conversion'], 1, ',', '.') ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ══ Errores de JS ══ -->
                <h2 class="stats-head">Detalle</h2>
                <?php if (!empty($errores)): ?>
                <div class="panel">
                    <div class="panel__head">
                        <h2>Errores de JavaScript en la landing</h2>
                        <span class="chip chip--alerta">Calidad</span>
                    </div>
                    <div class="panel__body">
                        <table class="stats-table stats-table--num">
                            <thead><tr><th>Error</th><th>Sesiones afectadas</th></tr></thead>
                            <tbody>
                            <?php foreach ($errores as $e): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars((string)$e['mensaje']) ?></code></td>
                                    <td><?= number_format((int)$e['n'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ══ Últimas visitas ══ -->
                <div class="panel">
                    <div class="panel__head">
                        <h2>Últimas visitas</h2>
                        <div class="panel__head-acciones">
                            <span class="chip"><?= count($sesiones) ?></span>
                            <a class="chip chip--accion"
                               href="<?= htmlspecialchars(BASE_URL . '/AdminEstadisticas/exportarCsv?' . http_build_query(array_filter([
                                   'dias' => $dias, 'producto' => $producto_id, 'entorno' => $entorno,
                               ]))) ?>">
                                <i class="fas fa-file-csv" aria-hidden="true"></i> CSV
                            </a>
                        </div>
                    </div>
                    <div class="panel__body">
                        <?php if (empty($sesiones)): ?>
                            <p class="stats-empty">Sin visitas en este periodo.</p>
                        <?php else: ?>
                        <div class="stats-scroll">
                            <table class="stats-table">
                                <thead>
                                <tr>
                                    <th>Cuándo</th><th>Llegó hasta</th><th>Sección</th>
                                    <th>Campo</th><th>Tiempo</th><th>Scroll</th>
                                    <th>Dispositivo</th><th>Fuente</th><th>Pedido</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($sesiones as $s):
                                    $paso = (int)$s['paso_max'];
                                    $limpiar = fn($v) => $v === null || $v === ''
                                        ? '—'
                                        : str_replace(['-', '_'], ' ', preg_replace('/^\d+-/', '', (string)$v));
                                ?>
                                    <tr>
                                        <td><?= date('d/m H:i', strtotime((string)$s['creado_en'])) ?></td>
                                        <td>
                                            <span class="status-tag <?= $paso >= 7 ? 'status-entregado' : ($paso >= 4 ? 'status-confirmado' : 'status-nuevo') ?>">
                                                <?= htmlspecialchars(LandingAnalytics::PASOS[$paso] ?? '—') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($limpiar($s['seccion_max'])) ?></td>
                                        <td><?= htmlspecialchars($limpiar($s['campo_max'])) ?></td>
                                        <td><?= $minutos((int)$s['duracion_seg']) ?></td>
                                        <td><?= (int)$s['scroll_max'] ?>%</td>
                                        <td><?= htmlspecialchars((string)$s['dispositivo']) ?> · <?= htmlspecialchars((string)$s['navegador']) ?></td>
                                        <td><?= htmlspecialchars((string)$s['fuente']) ?></td>
                                        <td><?= $s['pedido_id'] ? '#' . (int)$s['pedido_id'] : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php endif; ?>

            </section>
        </main>
    </div>

    <style>
    /* Estilos propios de esta página. Reutilizan los tokens del sistema
       (--green-dark, --border, --sp-*) para que no se despeguen del resto
       del panel; solo el embudo y las mini-barras son nuevos aquí. */
    /* ── Barra de filtros ── */
    .stats-bar {
        display: flex; flex-wrap: wrap; align-items: center;
        justify-content: space-between; gap: var(--sp-3);
        margin-bottom: var(--sp-5); min-width: 0;
    }
    .stats-bar__right {
        display: flex; flex-wrap: wrap; align-items: center;
        gap: var(--sp-3); min-width: 0; max-width: 100%;
    }

    /* Control segmentado: una sola pieza con divisiones internas, para que
       el periodo se lea como un interruptor y no como cinco botones sueltos.
       Los cinco periodos no caben en un móvil y el bloque no encogía: se
       llevaba por delante el ancho de toda la página. Se desliza en su
       sitio, como la fila de estados de Pedidos. */
    .seg {
        display: flex; background: var(--surface);
        border: 1px solid var(--border); border-radius: 8px;
        max-width: 100%; overflow-x: auto; scrollbar-width: none;
    }
    .seg::-webkit-scrollbar { display: none; }
    .seg__item {
        flex: 0 0 auto;
        padding: 7px 14px; font-size: var(--text-sm); font-weight: 600;
        color: var(--tx-muted); text-decoration: none; white-space: nowrap;
        border-right: 1px solid var(--border); transition: background var(--t), color var(--t);
    }
    .seg__item:last-child  { border-right: none; }
    .seg__item:hover       { background: var(--surface-3); color: var(--tx); }
    .seg__item.is-active   { background: var(--green-dark); color: #fff; }
    .seg--sm .seg__item    { padding: 6px 11px; font-size: var(--text-xs); }

    .stats-select {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 0 10px; height: 36px;
        background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
    }
    .stats-select i      { color: var(--tx-dim); font-size: 12px; }
    .stats-select select {
        border: 0; background: none; outline: none; cursor: pointer;
        font: inherit; font-size: var(--text-sm); font-weight: 600; color: var(--tx);
        max-width: 220px; min-width: 0; padding: 6px 0;
    }
    @media (max-width: 560px) {
        .stats-bar, .stats-bar__right { width: 100%; }
        .stats-select { flex: 1 1 160px; }
        .stats-select select { max-width: none; width: 100%; }
    }
    .stats-select:focus-within { border-color: var(--green-mid); box-shadow: 0 0 0 3px var(--green-soft); }

    .stats-aviso {
        display: flex; align-items: center; flex-wrap: wrap; gap: 8px;
        margin-bottom: var(--sp-4); padding: 9px 14px; border-radius: 8px;
        background: #FEF9C3; color: #713F12;
        font-size: var(--text-sm); font-weight: 600;
    }

    /* Si el Pixel se apaga (.env con APP_ENV=local en producción, por
       error), hoy no se entera nadie mientras la pauta sigue gastando.
       El estado "activo" es discreto a propósito: solo debe llamar la
       atención cuando algo está mal. */
    .pixel-status {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 11px; border-radius: 999px;
        font-size: var(--text-xs); font-weight: 700;
    }
    .pixel-status i { font-size: 7px; }
    .pixel-status--on {
        background: rgba(22,163,74,.10); color: #15803d;
        border: 1px solid rgba(22,163,74,.22);
    }
    .pixel-status--off {
        background: #FEF9C3; color: #713F12;
        border: 1px solid rgba(113,63,18,.22);
    }
    .stats-aviso form { margin-left: auto; }
    .btn-aviso {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 11px; border-radius: 7px; cursor: pointer;
        background: rgba(113,63,18,.10); border: 1px solid rgba(113,63,18,.22);
        color: #713F12; font: inherit; font-size: var(--text-xs); font-weight: 700;
    }
    .btn-aviso:hover { background: rgba(113,63,18,.18); }

    .panel__head-acciones { display: flex; align-items: center; gap: var(--sp-2); }
    .chip--accion { text-decoration: none; cursor: pointer; }
    .chip--accion:hover { background: rgba(255,255,255,.28); }

    /* ── Panel de análisis con Claude ── */
    .ia-barra {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: var(--sp-3);
    }
    .ia-meta { font-size: var(--text-xs); color: var(--tx-dim); font-weight: 600; }
    .ia-controles { display: flex; align-items: center; flex-wrap: wrap; gap: var(--sp-2); }
    .stats-select--sm { height: 34px; }
    .stats-select--sm select { font-size: var(--text-xs); max-width: 260px; }
    .btn-ia {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 16px; border-radius: 9px; border: 0; cursor: pointer;
        background: var(--green-dark); color: #fff;
        font: inherit; font-size: var(--text-sm); font-weight: 700;
        transition: background var(--t), opacity var(--t);
    }
    .btn-ia:hover:not(:disabled) { background: var(--green-dark-h); }
    .btn-ia:disabled { opacity: .6; cursor: progress; }
    .btn-ia.is-cargando i { animation: spin 1s linear infinite; }

    .ia-estado {
        margin-top: var(--sp-3); padding: 10px 14px; border-radius: 8px;
        background: var(--surface-3); color: var(--tx-muted);
        font-size: var(--text-sm); font-weight: 600;
    }
    .ia-estado--error { background: var(--soft-red, #FEE2E2); color: var(--red); }

    .ia-diagnostico {
        margin-top: var(--sp-4); padding: var(--sp-4);
        border-left: 3px solid var(--green-mid); border-radius: 0 8px 8px 0;
        background: var(--surface-2);
        font-size: 15px; line-height: 1.55; color: var(--tx);
    }
    .ia-sub {
        margin: var(--sp-5) 0 var(--sp-2);
        font-size: var(--text-xs); font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: var(--tx-dim);
    }

    .ia-acciones { list-style: none; counter-reset: acc; display: flex; flex-direction: column; gap: var(--sp-3); }
    .ia-acciones li {
        counter-increment: acc; position: relative;
        padding: var(--sp-3) var(--sp-4) var(--sp-3) 42px;
        border: 1px solid var(--border); border-radius: 10px; background: var(--surface);
    }
    .ia-acciones li::before {
        content: counter(acc); position: absolute; left: 12px; top: 13px;
        display: flex; align-items: center; justify-content: center;
        width: 21px; height: 21px; border-radius: 50%;
        background: var(--green-dark); color: #fff;
        font-size: 11px; font-weight: 800;
    }
    .ia-accion__txt  { font-size: var(--text-sm); line-height: 1.5; color: var(--tx); font-weight: 600; }
    .ia-accion__meta { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 7px; }
    .ia-donde {
        font-size: 11.5px; font-weight: 700; color: var(--green-dark);
        background: var(--green-soft); padding: 2px 8px; border-radius: 999px;
    }
    .ia-tag {
        font-size: 11px; font-weight: 700; text-transform: lowercase;
        color: var(--tx-muted); background: var(--surface-3);
        padding: 2px 8px; border-radius: 999px;
    }
    .ia-tag--alto  { background: #DCFCE7; color: #166534; }
    .ia-tag--medio { background: #FEF9C3; color: #713F12; }

    .ia-hallazgos, .ia-dudas { list-style: none; display: flex; flex-direction: column; gap: var(--sp-3); }
    .ia-hallazgos li  { font-size: var(--text-sm); color: var(--tx); line-height: 1.45; }
    .ia-evidencia     { margin-top: 3px; font-size: var(--text-xs); color: var(--tx-muted); line-height: 1.5; }
    .ia-conf {
        font-size: 10.5px; font-weight: 700; padding: 1px 7px; border-radius: 999px;
        background: var(--surface-3); color: var(--tx-dim); white-space: nowrap;
    }
    .ia-conf--alta  { background: #DCFCE7; color: #166534; }
    .ia-conf--baja  { background: var(--soft-red, #FEE2E2); color: var(--red); }
    .ia-dudas li {
        position: relative; padding-left: 16px;
        font-size: var(--text-xs); color: var(--tx-muted); line-height: 1.5;
    }
    .ia-dudas li::before { content: '·'; position: absolute; left: 5px; font-weight: 800; }

    /* ── Ritmo de la página ──
       Cada grupo de paneles va bajo un rótulo. Sin ellos la página eran
       ocho bloques seguidos del mismo peso y no se sabía dónde mirar. */
    /* El rótulo respira más por arriba que por abajo, para que se lea
       pegado al grupo que encabeza y no flotando entre dos bloques.
       .material-content ya es flex column con gap: no hace falta más. */
    .stats-head {
        margin: var(--sp-3) 0 calc(var(--sp-3) * -1);
        font-size: var(--text-xs); font-weight: 800; letter-spacing: .08em;
        text-transform: uppercase; color: var(--tx-dim);
    }
    .stats-head:first-of-type { margin-top: 0; }

    /* align-items:start — sin esto las tarjetas de una fila se estiran a la
       más alta y la de menos contenido queda con un hueco muerto abajo.
       Nada de margin entre paneles: el gap del contenedor ya los separa, y
       un `.panel + .panel` empujaba hacia abajo todas las tarjetas menos la
       primera de cada fila, que es lo que las desalineaba. */
    .stats-cols {
        display: grid; grid-template-columns: 1fr;
        gap: var(--sp-4); align-items: start;
    }
    .stats-cols > .panel { min-width: 0; }
    @media (min-width: 760px)  { .stats-cols--3 { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width: 1000px) {
        .stats-cols    { grid-template-columns: 1fr 1fr; }
        .stats-cols--3 { grid-template-columns: repeat(3, 1fr); }
    }

    .chip--alerta { background: var(--soft-red, #FEE2E2); color: var(--red); border-color: transparent; }

    /* ── Embudo ──
       Cuatro columnas fijas: paso · barra · cifra · caída. Las cifras en
       columna propia y con cifras tabulares se leen en vertical de un
       vistazo; antes flotaban dentro de la barra y bailaban con cada ancho. */
    .lfunnel { display: flex; flex-direction: column; gap: 6px; }
    .lfunnel-row {
        display: grid;
        grid-template-columns: minmax(140px, 1.2fr) minmax(0, 2.4fr) 92px 104px;
        align-items: center; gap: var(--sp-3);
        font-size: var(--text-sm);
    }
    .lfunnel-row--head {
        font-size: var(--text-xs); font-weight: 700; letter-spacing: .04em;
        text-transform: uppercase; color: var(--tx-dim);
        padding-bottom: 6px; border-bottom: 1px solid var(--border); margin-bottom: 2px;
    }
    .lfunnel-row--head .lfunnel-row__n,
    .lfunnel-row--head .lfunnel-row__drop { color: var(--tx-dim); }

    .lfunnel-row__label { display: flex; align-items: center; gap: var(--sp-2); color: var(--tx); font-weight: 600; }
    .lfunnel-row__step {
        display: inline-flex; align-items: center; justify-content: center;
        width: 20px; height: 20px; border-radius: 50%; flex-shrink: 0;
        background: var(--green-soft); color: var(--green-dark);
        font-size: 11px; font-weight: 700;
    }
    .lfunnel-row__bar {
        position: relative; height: 22px; border-radius: 5px;
        background: var(--surface-3); overflow: hidden;
    }
    .lfunnel-row__fill {
        position: absolute; inset: 0 auto 0 0; border-radius: 5px;
        background: linear-gradient(90deg, var(--green-dark), var(--green-light));
    }
    .lfunnel-row__n {
        text-align: right; font-variant-numeric: tabular-nums;
        color: var(--tx); line-height: 1.25;
    }
    .lfunnel-row__n strong { font-weight: 700; font-size: 14px; }
    .lfunnel-row__n small  { display: block; font-weight: 600; color: var(--tx-dim); font-size: 11px; }
    .lfunnel-row__drop     { text-align: right; }

    .drop-tag {
        display: inline-block; padding: 3px 9px; border-radius: 999px;
        background: var(--soft-red, #FEE2E2); color: var(--red);
        font-size: 11.5px; font-weight: 700; font-variant-numeric: tabular-nums;
    }
    .drop-tag--none { background: transparent; color: var(--tx-dim); }

    /* En móvil no caben cuatro columnas: el nombre del paso y su cifra van
       arriba y la barra ocupa el ancho completo debajo. */
    @media (max-width: 720px) {
        .lfunnel-row {
            grid-template-columns: minmax(0, 1fr) auto auto;
            grid-template-areas: "label n drop" "bar bar bar";
            gap: 4px var(--sp-2); padding-bottom: 8px;
        }
        /* min-width:0 y texto que puede partirse: el nombre del paso en una
           sola línea imponía un ancho mínimo que desbordaba la pantalla. */
        .lfunnel-row__label { grid-area: label; min-width: 0; }
        .lfunnel-row__label > span { overflow-wrap: anywhere; }
        /* Las cabeceras de tabla dejan de ser nowrap aquí por el mismo motivo. */
        .stats-table th { white-space: normal; }
        .lfunnel-row__bar   { grid-area: bar; height: 16px; }
        .lfunnel-row__n     { grid-area: n; }
        .lfunnel-row__n small { display: inline; margin-left: 4px; }
        .lfunnel-row__drop  { grid-area: drop; }
        .lfunnel-row--head  { display: none; }
    }

    /* ── Tablas ──
       Todas las columnas de cifras van a la derecha y con tabular-nums:
       es lo que permite comparar dos filas sin leerlas número a número. */
    .stats-table { width: 100%; border-collapse: collapse; font-size: var(--text-sm); }
    .stats-table th {
        text-align: left; padding: 0 10px 8px; color: var(--tx-dim);
        font-size: var(--text-xs); font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    .stats-table td {
        padding: 9px 10px; border-bottom: 1px solid var(--border); color: var(--tx);
    }
    .stats-table th:not(:first-child),
    .stats-table td:not(:first-child) { text-align: right; font-variant-numeric: tabular-nums; }
    .stats-table td:first-child  { font-weight: 600; }
    .stats-table tr:last-child td { border-bottom: none; }
    .stats-table code { font-size: 12px; color: var(--tx-muted); word-break: break-word; font-weight: 400; }

    /* La tabla de visitas es de lectura, no de comparación: ahí el texto
       vuelve a la izquierda. */
    .stats-scroll { overflow-x: auto; margin: 0 calc(var(--sp-5) * -1); padding: 0 var(--sp-5); }
    .stats-scroll .stats-table { min-width: 820px; }
    .stats-scroll .stats-table td:not(:first-child),
    .stats-scroll .stats-table th:not(:first-child) { text-align: left; }
    .stats-scroll .stats-table td { white-space: nowrap; font-weight: 500; }

    .cel-barra { width: 60px; }
    .mini-bar {
        display: block; width: 54px; height: 5px; border-radius: 3px;
        background: var(--surface-3); margin-left: auto; overflow: hidden;
    }
    .mini-bar > span { display: block; height: 100%; background: var(--green-mid); }

    .kpi-vs { font-size: 10.5px; color: var(--tx-dim); margin-left: 5px; white-space: nowrap; }

    /* Cuatro tarjetas en el rango donde el grid base reparte de tres en tres:
       un 2×2 se lee mejor que tres arriba y una ancha abajo. */
    @media (min-width: 760px) and (max-width: 1279px) {
        .stats-grid--4col { grid-template-columns: repeat(2, 1fr); }
        .stats-grid--4col > .stat-card:nth-child(4) { grid-column: auto; }
    }

    .stats-empty { color: var(--tx-dim); font-size: var(--text-sm); text-align: center; padding: var(--sp-4) 0; }
    .stats-hint  { color: var(--tx-muted); font-size: var(--text-xs); margin-top: var(--sp-3); line-height: 1.5; }
    </style>

    <?php if ($instalado): ?>
    <script>
    // El filtro de producto es un <select> y cada opción lleva ya su URL:
    // así el filtro ocupa una línea aunque haya veinte productos.
    (function () {
        var sel = document.getElementById('filtroProducto');
        if (!sel) return;
        sel.addEventListener('change', function () { window.location.href = sel.value; });
    })();
    </script>

    <?php if ($tiene_claude_key): ?>
    <script>
    /* ── Análisis del embudo con Claude ──────────────────────
       El análisis guardado y el recién generado se pintan con la MISMA
       función: si cada uno tuviera su render (uno en PHP, otro en JS),
       cambiar el formato obligaría a tocar los dos y se desincronizarían. */
    (function () {
        var boton    = document.getElementById('btnAnalizar');
        var estado   = document.getElementById('iaEstado');
        var destino  = document.getElementById('iaResultado');
        var meta     = document.getElementById('iaMeta');
        if (!boton || !destino) return;

        var guardado = <?= json_encode($analisis['resultado'] ?? null, JSON_UNESCAPED_UNICODE) ?>;

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function pinta(r) {
            if (!r || !r.diagnostico) { destino.innerHTML = ''; return; }

            var html = '<p class="ia-diagnostico">' + esc(r.diagnostico) + '</p>';

            if (r.acciones && r.acciones.length) {
                html += '<h3 class="ia-sub">Qué hacer</h3><ol class="ia-acciones">';
                r.acciones.forEach(function (a) {
                    html += '<li>'
                        + '<div class="ia-accion__txt">' + esc(a.accion) + '</div>'
                        + '<div class="ia-accion__meta">'
                        + '<span class="ia-donde">' + esc(a.donde) + '</span>'
                        + '<span class="ia-tag ia-tag--' + esc(a.impacto) + '">impacto ' + esc(a.impacto) + '</span>'
                        + '<span class="ia-tag">esfuerzo ' + esc(a.esfuerzo) + '</span>'
                        + '</div></li>';
                });
                html += '</ol>';
            }

            if (r.hallazgos && r.hallazgos.length) {
                html += '<h3 class="ia-sub">En qué se basa</h3><ul class="ia-hallazgos">';
                r.hallazgos.forEach(function (h) {
                    html += '<li>'
                        + '<strong>' + esc(h.titulo) + '</strong> '
                        + '<span class="ia-conf ia-conf--' + esc(h.confianza) + '">confianza ' + esc(h.confianza) + '</span>'
                        + '<p class="ia-evidencia">' + esc(h.evidencia) + '</p>'
                        + '</li>';
                });
                html += '</ul>';
            }

            if (r.no_concluyente && r.no_concluyente.length) {
                html += '<h3 class="ia-sub">Lo que estos datos no dicen</h3><ul class="ia-dudas">';
                r.no_concluyente.forEach(function (n) { html += '<li>' + esc(n) + '</li>'; });
                html += '</ul>';
            }

            destino.innerHTML = html;
        }

        pinta(guardado);

        boton.addEventListener('click', function () {
            boton.disabled = true;
            boton.classList.add('is-cargando');
            estado.hidden = false;
            estado.className = 'ia-estado';
            estado.textContent = 'Analizando el periodo… puede tardar hasta un minuto.';

            var selModelo = document.getElementById('iaModelo');

            var cuerpo = new FormData();
            cuerpo.append('csrf_token', window.__CSRF__ || '');
            cuerpo.append('dias', boton.dataset.dias);
            cuerpo.append('producto', boton.dataset.producto);
            cuerpo.append('entorno', boton.dataset.entorno);
            cuerpo.append('modelo', selModelo ? selModelo.value : '');

            fetch('<?= BASE_URL ?>/AdminEstadisticas/analizar', {
                method: 'POST',
                body: cuerpo,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) {
                    estado.className = 'ia-estado ia-estado--error';
                    estado.textContent = d.error || 'No se pudo generar el análisis.';
                    return;
                }
                estado.hidden = true;
                pinta(d.resultado);
                if (meta) {
                    meta.textContent = 'Último análisis: ahora mismo'
                        + (d.modelo ? ' · con ' + d.modelo : '');
                }
                boton.querySelector('span').textContent = 'Volver a analizar';
            })
            .catch(function () {
                estado.className = 'ia-estado ia-estado--error';
                estado.textContent = 'Se cortó la conexión antes de terminar. Vuelve a intentarlo.';
            })
            .finally(function () {
                boton.disabled = false;
                boton.classList.remove('is-cargando');
            });
        });
    })();
    </script>
    <?php endif; ?>

    <script src="<?= BASE_URL ?>/public/vendor/chart.umd.min.js"></script>
    <script>
    (function () {
        var serie = <?= json_encode($serie) ?>;
        var lienzo = document.getElementById('chartVisitas');
        if (!lienzo || !window.Chart) return;

        new Chart(lienzo, {
            type: 'line',
            data: {
                labels: serie.map(function (d) { return d.dia.slice(8) + '/' + d.dia.slice(5, 7); }),
                datasets: [
                    {
                        label: 'Visitas',
                        data: serie.map(function (d) { return d.sesiones; }),
                        borderColor: '#1A3D2E',
                        backgroundColor: 'rgba(26,61,46,.08)',
                        fill: true, tension: .3, borderWidth: 2, pointRadius: 2
                    },
                    {
                        label: 'Pedidos',
                        data: serie.map(function (d) { return d.pedidos; }),
                        borderColor: '#52B788',
                        backgroundColor: 'rgba(82,183,136,.12)',
                        fill: true, tension: .3, borderWidth: 2, pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    </script>
    <?php endif; ?>

    <script src="<?= BASE_URL ?>/public/js/funciones.js"></script>
</body>

</html>
