<?php
/**
 * Resultado de una consulta (aviso o pedidos con su mensaje). Lo usa la página y, cuando se consulta sin recargar,
 * el controlador (ConsultaController) lo devuelve dentro de un JSON. Variables: $aviso y $ordenes.
 */
$e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fecha = static function (?string $f) use ($meses): string {
    $t = $f ? strtotime($f) : 0;
    return $t ? date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] : '';
};
$clasesEstado = ['devolucion' => 'cancelado', 'otro' => 'contactado'];
?>
<?php if ($aviso !== ''): ?>
    <div class="consulta__aviso" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?= $e($aviso) ?></div>
<?php endif; ?>

<?php if ($ordenes !== null): ?>
    <?php if (!$ordenes): ?>
        <div class="consulta__aviso consulta__aviso--suave" role="status">
            <i class="fas fa-circle-info" aria-hidden="true"></i>
            No encontré pedidos con ese número en los últimos <?= (int)ConsultaPedidos::DIAS_ATRAS ?> días. Revisa que esté bien escrito;
            si el pedido es de hoy, puede que todavía no esté cargado.
        </div>
    <?php else: foreach ($ordenes as $i => $o): ?>
        <article class="consulta__pedido">
            <div class="consulta__pedido-top">
                <strong><?= $o['producto'] !== '' ? $e($o['producto']) : 'Pedido' ?><?= $o['cantidad'] !== null && $o['cantidad'] > 1 ? ' · ' . (int)$o['cantidad'] . ' unidades' : '' ?></strong>
                <span class="status-tag status-<?= $e($clasesEstado[$o['estado_clase']] ?? $o['estado_clase']) ?>"><?= $e($o['estado']) ?></span>
            </div>
            <p class="consulta__meta">
                Pedido del <?= $e($fecha($o['fecha'])) ?>
                <?= $o['guia'] !== '' ? ' · Guía <b>' . $e($o['guia']) . '</b>' : ' · Sin guía todavía' ?>
                <?= $o['transportadora'] !== '' ? ' · ' . $e($o['transportadora']) : '' ?>
                <?= $o['municipio'] !== '' ? ' · ' . $e($o['municipio']) : '' ?>
            </p>

            <label for="consultaMsg<?= $i ?>">Mensaje para el cliente</label>
            <textarea id="consultaMsg<?= $i ?>" class="consulta__mensaje" readonly rows="7"><?= $e($o['mensaje']) ?></textarea>
            <div class="consulta__acciones">
                <button type="button" class="btn-primary" data-copiar="consultaMsg<?= $i ?>"><i class="far fa-copy" aria-hidden="true"></i> Copiar mensaje</button>
                <a class="btn-detail" href="<?= $e($o['whatsapp']) ?>" <?= strpos($o['whatsapp'], 'https://') === 0 ? 'target="_blank" ' : '' ?>rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i> Abrir en WhatsApp Business</a>
            </div>
        </article>
    <?php endforeach; endif; ?>
<?php endif; ?>

