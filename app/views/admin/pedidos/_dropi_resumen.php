<?php
/**
 * Sección "Ventas reales · Dropi" del dashboard: lo que se vendió, entregó y devolvió según las
 * órdenes de Dropi importadas en Seguimiento, de todos los canales (landing, WhatsApp, vendedoras).
 * Tiene su propio período (dropi_rango) y su propio canal (dropi_canal); se cambian recargando la
 * página y dejando el filtro de los pedidos de la landing como estaba.
 *
 * Variables: $dropi (DashboardDropi::resumen + 'tendencias') y $renderTrend (la define pedidos/index.php).
 */
$h      = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$money  = static fn($n) => '$' . number_format((float)$n, 0, ',', '.');
$pct    = static fn($x) => $x === null ? '—' : round($x * 100) . '%';
$plural = static fn(int $n, string $uno, string $varios) => number_format($n, 0, ',', '.') . ' ' . ($n === 1 ? $uno : $varios);

$importarUrl = BASE_URL . '/AdminPlantillasWa/importarDropi';
$periodo     = $dropi['periodo'] ?? ['rango' => 'mes', 'etiqueta' => 'Este mes', 'desde' => null, 'hasta' => null];
$canalSel    = $dropi['canal_sel'] ?? 'todos';

// Los enlaces conservan lo demás de la URL (el filtro de los pedidos de la landing) y vuelven a esta sección.
$baseGet = $_GET;
unset($baseGet['url'], $baseGet['dropi_rango'], $baseGet['dropi_desde'], $baseGet['dropi_hasta'], $baseGet['dropi_canal']); // 'url' lo pone el enrutador, no es un filtro
$enlace = static function (array $cambios) use ($baseGet): string {
    return BASE_URL . '/AdminPedidos/index?' . http_build_query(array_merge($baseGet, $cambios)) . '#ventas-dropi';
};
$conCanal   = $canalSel !== 'todos' ? ['dropi_canal' => $canalSel] : [];
$conPeriodo = ['dropi_rango' => $periodo['rango']] + ($periodo['rango'] === 'personalizado' ? ['dropi_desde' => $periodo['desde'], 'dropi_hasta' => $periodo['hasta']] : []);

$rangos = ['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Semana', 'mes' => 'Mes', 'mes_pasado' => 'Mes pasado', 'trimestre' => 'Trimestre', 'siempre' => 'Siempre'];
$canales = ['todos' => 'Todos los canales', 'landing' => 'Landing', 'whatsapp' => 'WhatsApp / directo'];
$nombreCanal = ['landing' => 'Landing', 'whatsapp' => 'WhatsApp / directo'];

// "hoy a las 10:05", "ayer a las 10:05" o la fecha.
$cuando = static function (?string $fecha): string {
    $ts = $fecha ? strtotime($fecha) : false;
    if (!$ts) return '';
    $dias = (int)floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', $ts))) / 86400);
    $hora = date('g:i a', $ts);
    if ($dias <= 0) return 'hoy a las ' . $hora;
    if ($dias === 1) return 'ayer a las ' . $hora;
    return 'el ' . date('d/m/Y', $ts) . ' a las ' . $hora;
};
?>
<section class="dash-dropi" id="ventas-dropi" aria-labelledby="dropiTitulo">
    <div class="dash-dropi__head">
        <div>
            <h2 id="dropiTitulo">Ventas reales · Dropi</h2>
            <p class="help">
                Todos los canales: landing, WhatsApp y tus vendedoras.
                <?php if (!empty($dropi['hay_datos'])): ?>
                    Cuenta los pedidos hechos en el período, según tu última importación (<?= $h($cuando($dropi['ultima_actualizacion'] ?? null)) ?>).
                <?php endif; ?>
            </p>
        </div>
        <div class="dash-dropi__acciones">
            <a href="<?= $importarUrl ?>" class="btn-detail"><i class="fas fa-file-import" aria-hidden="true"></i> Importar órdenes</a>
        </div>
    </div>

<?php if (empty($dropi['hay_datos'])): ?>

    <div class="panel">
        <div class="panel__body dash-dropi__vacio">
            <i class="fas fa-box-open" aria-hidden="true"></i>
            <div>
                <strong>Todavía no hay órdenes de Dropi importadas.</strong>
                <p class="help">Descarga tus órdenes de Dropi y súbelas en Seguimiento. Aquí verás cuánto vendes, cuánto se entrega y cuánto se devuelve, vengan de la landing o de WhatsApp.</p>
            </div>
        </div>
    </div>

<?php else:
    $t  = $dropi['total'];
    $cn = $dropi['canal'];
    $tr = $dropi['tendencias'] ?? [];
    $flat = ['dir' => 'flat', 'label' => '—'];
    $serie = $dropi['serie'];
    $porPaso = ['hora' => 'hora', 'dia' => 'día', 'semana' => 'semana', 'mes' => 'mes'][$serie['paso']] ?? 'día';
?>

    <div class="dash-filtros">
        <div class="dash-filtros__grupo" role="group" aria-label="Período">
            <?php foreach ($rangos as $k => $nombre): ?>
                <a class="state-chip<?= $periodo['rango'] === $k ? ' is-active' : '' ?>" href="<?= $h($enlace(['dropi_rango' => $k] + $conCanal)) ?>"
                   <?= $periodo['rango'] === $k ? 'aria-current="true"' : '' ?>><?= $h($nombre) ?></a>
            <?php endforeach; ?>

            <details class="dtf-wrap<?= $periodo['rango'] === 'personalizado' ? ' dtf-wrap--active' : '' ?>" id="dropiFechaWrap">
                <summary class="dtf-btn">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <span class="dtf-btn__label"><?= $periodo['rango'] === 'personalizado' ? $h($periodo['etiqueta']) : 'Fechas' ?></span>
                    <i class="fas fa-chevron-down dtf-btn__chev" aria-hidden="true"></i>
                </summary>
                <div class="dtf-panel">
                    <div class="dfd-custom">
                        <span class="dfd-custom__label"><i class="fas fa-sliders-h" aria-hidden="true"></i> Fecha del pedido</span>
                        <form class="dfd-custom__form" method="get" action="<?= BASE_URL ?>/AdminPedidos/index#ventas-dropi">
                            <?php foreach ($baseGet as $nombre => $valor): if (is_array($valor)) continue; ?>
                                <input type="hidden" name="<?= $h($nombre) ?>" value="<?= $h($valor) ?>">
                            <?php endforeach; ?>
                            <?php if ($canalSel !== 'todos'): ?><input type="hidden" name="dropi_canal" value="<?= $h($canalSel) ?>"><?php endif; ?>
                            <input type="hidden" name="dropi_rango" value="personalizado">
                            <input type="date" name="dropi_desde" required max="<?= date('Y-m-d') ?>" value="<?= $h($periodo['desde'] ?? '') ?>" aria-label="Desde">
                            <input type="date" name="dropi_hasta" required max="<?= date('Y-m-d') ?>" value="<?= $h($periodo['hasta'] ?? '') ?>" aria-label="Hasta">
                            <button type="submit">Aplicar</button>
                        </form>
                    </div>
                </div>
            </details>
        </div>

        <div class="dash-filtros__grupo" role="group" aria-label="Canal">
            <?php foreach ($canales as $k => $nombre): ?>
                <a class="state-chip state-chip--canal<?= $canalSel === $k ? ' is-active' : '' ?>" href="<?= $h($enlace($conPeriodo + ($k !== 'todos' ? ['dropi_canal' => $k] : []))) ?>"
                   <?= $canalSel === $k ? 'aria-current="true"' : '' ?>><?= $h($nombre) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <p class="dash-resumen-periodo help">
        Mostrando <b><?= $h($periodo['etiqueta']) ?></b><?= $canalSel !== 'todos' ? ' · solo <b>' . $h($nombreCanal[$canalSel]) . '</b>' : '' ?>.
    </p>

    <div class="stats-grid stats-grid--compacta stats-grid--seis" id="dropiTarjetas">
        <div class="stat-card glow-green">
            <div class="stat-info">
                <small>Órdenes</small>
                <h2><?= number_format($t['ordenes'], 0, ',', '.') ?></h2>
                <?php if ($canalSel === 'todos'): ?>
                    <span class="target"><?= number_format($cn['landing']['ordenes'], 0, ',', '.') ?> landing · <?= number_format($cn['whatsapp']['ordenes'], 0, ',', '.') ?> WhatsApp</span>
                <?php else: ?>
                    <span class="target">Solo <?= $h($nombreCanal[$canalSel]) ?></span>
                <?php endif; ?>
                <span class="target"><?= ($renderTrend)($tr['ordenes'] ?? $flat) ?> vs per. ant.</span>
            </div>
            <i class="fas fa-clipboard-list stat-icon" aria-hidden="true"></i>
        </div>

        <div class="stat-card glow-purple">
            <div class="stat-info">
                <small>Entregado</small>
                <h2><?= $money($t['valor_entregado']) ?></h2>
                <span class="target"><?= $plural($t['entregadas'], 'orden entregada', 'órdenes entregadas') ?></span>
                <span class="target"><?= ($renderTrend)($tr['entregado'] ?? $flat) ?> vs per. ant.</span>
            </div>
            <i class="fas fa-circle-check stat-icon" aria-hidden="true"></i>
        </div>

        <div class="stat-card glow-blue">
            <div class="stat-info">
                <small>Ganancia neta</small>
                <h2><?= $money($t['ganancia_neta']) ?></h2>
                <span class="target"><?= $money($t['ganancia']) ?> − <?= $money($t['flete_devolucion']) ?> de fletes de regreso</span>
                <span class="target"><?= ($renderTrend)($tr['ganancia_neta'] ?? $flat) ?> vs per. ant.</span>
                <?php if ($t['entregadas_sin_ganancia'] > 0): ?>
                    <span class="target dash-aviso" title="Dropi no calculó la ganancia de esas órdenes entregadas, así que no suman aquí.">
                        <?= $plural($t['entregadas_sin_ganancia'], 'entregada sin ganancia', 'entregadas sin ganancia') ?> en Dropi
                    </span>
                <?php endif; ?>
            </div>
            <i class="fas fa-chart-line stat-icon" aria-hidden="true"></i>
        </div>

        <div class="stat-card glow-red">
            <div class="stat-info">
                <small>Devoluciones</small>
                <h2><?= number_format($t['devoluciones'], 0, ',', '.') ?></h2>
                <span class="target"><b><?= $money($t['flete_devolucion']) ?></b> en fletes de regreso</span>
                <span class="target"><?= $money($t['valor_devuelto']) ?> en producto devuelto</span>
            </div>
            <i class="fas fa-rotate-left stat-icon" aria-hidden="true"></i>
        </div>

        <div class="stat-card glow-gold">
            <div class="stat-info">
                <small>En camino</small>
                <h2><?= number_format($t['en_proceso'], 0, ',', '.') ?></h2>
                <span class="target"><?= $money($t['valor_en_proceso']) ?> por cobrar</span>
                <span class="target">Aún sin entregar ni devolver</span>
            </div>
            <i class="fas fa-truck-fast stat-icon" aria-hidden="true"></i>
        </div>

        <div class="stat-card glow-green">
            <div class="stat-info">
                <small>Tasa de entrega</small>
                <h2><?= $pct($t['tasa_entrega']) ?></h2>
                <span class="target">De <?= $plural($t['cerradas'], 'orden cerrada', 'órdenes cerradas') ?></span>
                <span class="target">Entregadas ÷ (entregadas + devueltas)</span>
            </div>
            <i class="fas fa-percent stat-icon" aria-hidden="true"></i>
        </div>
    </div>

    <section class="dash-grid" aria-label="Gráficos de Dropi">
        <article class="panel panel--kpi kpi-purple">
            <div class="panel__head"><h2>Órdenes por <?= $h($porPaso) ?></h2><span class="chip">Actividad</span></div>
            <div class="panel__body dash-grafico">
                <canvas id="chartDropiOrdenes" role="img" aria-label="Órdenes de Dropi por <?= $h($porPaso) ?>, separadas entre landing y WhatsApp"></canvas>
            </div>
        </article>

        <article class="panel panel--kpi kpi-cyan">
            <div class="panel__head"><h2>Ventas por <?= $h($porPaso) ?></h2><span class="chip">COP</span></div>
            <div class="panel__body dash-grafico">
                <canvas id="chartDropiVentas" role="img" aria-label="Ventas de Dropi por <?= $h($porPaso) ?> en pesos, separadas entre landing y WhatsApp"></canvas>
            </div>
        </article>

        <article class="panel panel--wide">
            <div class="panel__head"><h2>Distribución por estado</h2><span class="chip">Resumen</span></div>
            <div class="panel__body panel__body--center">
                <canvas id="chartDropiEstados" height="160" role="img" aria-label="Órdenes de Dropi por estado"></canvas>
            </div>
        </article>

        <script type="application/json" id="dropiGraficos"><?= json_encode([
            'serie'  => $serie,
            'canal'  => $canalSel,
            'estados' => $t['estados'],
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    </section>

    <div class="dash-embudos">
        <?php foreach ($nombreCanal as $k => $nombre):
            $c = $cn[$k];
            $base = max(0, (int)$c['creadas']);
            $principal = [['Creadas', $c['creadas'], 'f-nuevo'], ['Despachadas', $c['despachadas'], 'f-enviado'], ['Entregadas', $c['entregadas'], 'f-entregado']];
            $resto = [['Devueltas', $c['devoluciones'], 'f-cancelado'], ['Canceladas', $c['canceladas'], 'f-gris'], ['Pendientes', $c['en_proceso'], 'f-contactado']];
            $fila = static function (array $f) use ($base): string {
                $p = $base > 0 ? (int)round($f[1] / $base * 100) : 0;
                return '<div class="funnel-row"><span class="funnel-label">' . htmlspecialchars($f[0]) . '</span>'
                    . '<div class="funnel-bar-track"><div class="funnel-bar-fill ' . $f[2] . '" style="width:' . $p . '%"></div></div>'
                    . '<span class="funnel-pct">' . $p . '%</span><span class="funnel-count">' . number_format((int)$f[1], 0, ',', '.') . '</span></div>';
            };
        ?>
        <article class="panel">
            <div class="panel__head"><h2>Embudo de entrega · <?= $h($nombre) ?></h2><span class="chip"><?= $plural($base, 'orden', 'órdenes') ?></span></div>
            <div class="panel__body">
                <?php if ($base === 0): ?>
                    <p class="help dash-vacio-p">Sin órdenes de este canal en el período.</p>
                <?php else: ?>
                    <div class="funnel-bars" role="list" aria-label="Embudo de entrega de <?= $h($nombre) ?>">
                        <?php foreach ($principal as $f) echo $fila($f); ?>
                    </div>
                    <p class="help dash-embudo-resto">Y el resto de las órdenes creadas:</p>
                    <div class="funnel-bars">
                        <?php foreach ($resto as $f) echo $fila($f); ?>
                    </div>
                    <p class="help dash-nota">
                        <b>Pendientes</b> son las que aún no se entregan ni se devuelven (preparándose, en camino o esperando recogida).
                        De las ya cerradas, se entrega el <b><?= $pct($c['tasa_entrega']) ?></b>.
                    </p>
                <?php endif; ?>
                <?php if ($k === 'whatsapp'): ?>
                    <p class="help dash-nota">Empieza cuando la orden se crea en Dropi: las conversaciones que no llegaron a ser orden no se ven aquí.</p>
                <?php else: ?>
                    <p class="help dash-nota">Lo que pasa antes de Dropi (nuevo, contactado, confirmado) está en el embudo de la landing, más abajo.</p>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <div class="dash-dropi__grid">

        <article class="panel">
            <div class="panel__head"><h2>Por canal</h2><span class="chip">Landing vs WhatsApp</span></div>
            <div class="panel__body">
                <div class="dash-tabla-wrap">
                    <table class="dash-tabla">
                        <thead>
                            <tr><th scope="col">Canal</th><th scope="col" class="num">Órdenes</th><th scope="col" class="num dash-opc">Entregadas</th><th scope="col" class="num dash-opc">Devol.</th><th scope="col" class="num">Entrega</th><th scope="col" class="num">Ganancia neta</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ([['landing', 'Landing', 'fa-globe'], ['whatsapp', 'WhatsApp / directo', 'fa-whatsapp']] as [$k, $nombre, $icono]): $c = $cn[$k]; ?>
                            <tr>
                                <th scope="row"><i class="<?= $k === 'whatsapp' ? 'fab' : 'fas' ?> <?= $icono ?>" aria-hidden="true"></i> <?= $h($nombre) ?></th>
                                <td class="num"><?= number_format($c['ordenes'], 0, ',', '.') ?></td>
                                <td class="num dash-opc"><?= number_format($c['entregadas'], 0, ',', '.') ?></td>
                                <td class="num dash-opc"><?= number_format($c['devoluciones'], 0, ',', '.') ?></td>
                                <td class="num"><?= $pct($c['tasa_entrega']) ?></td>
                                <td class="num"><?= $money($c['ganancia_neta']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="help dash-nota">
                    <b>Landing</b> es una orden de Dropi que corresponde a un pedido de tu landing (mismo teléfono en esos días, o el mismo ID de Dropi).
                    El resto —lo que vendes por WhatsApp o directo— se cuenta como <b>WhatsApp / directo</b>.
                </p>
            </div>
        </article>

        <article class="panel">
            <div class="panel__head"><h2>Por transportadora</h2><span class="chip">Quién entrega y quién devuelve</span></div>
            <div class="panel__body">
                <div class="dash-tabla-wrap">
                    <table class="dash-tabla">
                        <thead>
                            <tr><th scope="col">Transportadora</th><th scope="col" class="num">Órdenes</th><th scope="col" class="num">Entrega</th><th scope="col" class="num">Devolución</th><th scope="col" class="num dash-opc">Fletes de regreso</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dropi['transportadora'] as $f): ?>
                            <tr>
                                <th scope="row"><?= $h($f['nombre']) ?></th>
                                <td class="num"><?= number_format($f['ordenes'], 0, ',', '.') ?></td>
                                <td class="num"><?= $pct($f['tasa_entrega']) ?></td>
                                <td class="num <?= ($f['tasa_devolucion'] ?? 0) >= 0.25 ? 'dash-mal' : '' ?>"><?= $pct($f['tasa_devolucion']) ?></td>
                                <td class="num dash-opc"><?= $money($f['flete_devolucion']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$dropi['transportadora']): ?><tr><td colspan="5" class="dash-vacio">Sin órdenes en este período.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="help dash-nota">Entrega y devolución se calculan sobre las órdenes ya cerradas (entregadas o devueltas). En rojo, las que devuelven una de cada cuatro o más.</p>
            </div>
        </article>

        <article class="panel">
            <div class="panel__head"><h2>Por producto</h2><span class="chip">Lo que más sale</span></div>
            <div class="panel__body">
                <?php $soloSinProducto = count($dropi['producto']) === 1 && $dropi['producto'][0]['nombre'] === 'Sin producto'; ?>
                <?php if ($soloSinProducto): ?>
                    <p class="help dash-nota" style="margin:0">
                        Estas órdenes vienen de una descarga de Dropi sin productos. Sube la descarga <b>“órdenes con productos”</b> para ver cuánto vende y devuelve cada producto.
                    </p>
                <?php else: ?>
                    <div class="dash-tabla-wrap">
                        <table class="dash-tabla">
                            <thead>
                                <tr><th scope="col">Producto</th><th scope="col" class="num">Órdenes</th><th scope="col" class="num">Entrega</th><th scope="col" class="num dash-opc">Devol.</th><th scope="col" class="num">Ganancia neta</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($dropi['producto'] as $f): ?>
                                <tr>
                                    <th scope="row"><?= $h($f['nombre']) ?></th>
                                    <td class="num"><?= number_format($f['ordenes'], 0, ',', '.') ?></td>
                                    <td class="num"><?= $pct($f['tasa_entrega']) ?></td>
                                    <td class="num dash-opc"><?= number_format($f['devoluciones'], 0, ',', '.') ?></td>
                                    <td class="num"><?= $money($f['ganancia_neta']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$dropi['producto']): ?><tr><td colspan="5" class="dash-vacio">Sin órdenes en este período.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (($dropi['productos_extra'] ?? 0) > 0): ?>
                        <p class="help dash-nota">Y <?= $plural((int)$dropi['productos_extra'], 'producto más', 'productos más') ?> con menos órdenes.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>

    </div>

<?php endif; ?>
</section>
