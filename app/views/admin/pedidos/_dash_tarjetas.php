<?php
/**
 * Las tarjetas de resumen de los pedidos de la landing (con su tendencia contra el período anterior).
 * La página las incluye y, al cambiar de rango sin recargar, el controlador las devuelve dentro de un JSON.
 */
$total_pedidos  = $total_pedidos  ?? 0;
$total_utilidad = $total_utilidad ?? 0;
$total_venta    = $total_venta    ?? 0;
$pedidos_nuevos = $pedidos_nuevos ?? 0;
$tendencias     = $tendencias     ?? [];
$renderTrend    = $renderTrend ?? function (array $t): string {
    if (!isset($t['dir']) || $t['dir'] === 'flat') {
        return '<span class="kpi-trend flat">—</span>';
    }
    $icon = $t['dir'] === 'up' ? '↑' : '↓';
    return '<span class="kpi-trend ' . htmlspecialchars($t['dir']) . '">'
        . $icon . ' ' . htmlspecialchars($t['label'] ?? '') . '</span>';
};
?>
<div class="stat-card glow-green">
    <div class="stat-info">
        <small>Pedidos Totales</small>
        <h2 id="kpiPedidos"><?= number_format($total_pedidos, 0, ',', '.') ?></h2>
        <span class="target"><?= ($renderTrend)($tendencias['pedidos'] ?? ['dir'=>'flat','label'=>'—']) ?> vs per. ant.</span>
    </div>
    <i class="fas fa-clipboard-list stat-icon"></i>
</div>

<div class="stat-card glow-red">
    <div class="stat-info">
        <small>Pedidos Nuevos</small>
        <h2 id="kpiNuevos"><?= number_format($pedidos_nuevos, 0, ',', '.') ?></h2>
        <span class="target pending">Pendientes de contacto</span>
    </div>
    <i class="fas fa-bell stat-icon"></i>
</div>

<div class="stat-card glow-purple">
    <div class="stat-info">
        <small>Ventas Totales</small>
        <h2 id="kpiVentas">$<?= number_format((float)$total_venta, 0, ',', '.') ?></h2>
        <span class="target"><?= ($renderTrend)($tendencias['ventas'] ?? ['dir'=>'flat','label'=>'—']) ?> vs per. ant.</span>
    </div>
    <i class="fas fa-dollar-sign stat-icon"></i>
</div>

<div class="stat-card glow-blue">
    <div class="stat-info">
        <small>Utilidad Acumulada</small>
        <h2 id="kpiUtilidad">$<?= number_format((float)$total_utilidad, 0, ',', '.') ?></h2>
        <span class="target"><?= ($renderTrend)($tendencias['utilidad'] ?? ['dir'=>'flat','label'=>'—']) ?> vs per. ant.</span>
    </div>
    <i class="fas fa-chart-line stat-icon"></i>
</div>

<div class="stat-card glow-gold">
    <div class="stat-info">
        <small>Ticket Promedio</small>
        <h2 id="kpiTicket">$<?= number_format($ticket_promedio ?? 0, 0, ',', '.') ?></h2>
        <span class="target">Por pedido (excl. cancelados)</span>
    </div>
    <i class="fas fa-receipt stat-icon"></i>
</div>
