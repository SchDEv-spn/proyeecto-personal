<?php
/**
 * Las filas de la tabla "Pedidos Recientes". La página las incluye y, al cambiar de rango sin recargar,
 * el controlador las devuelve dentro de un JSON. Variables: $pedidos y $riesgos.
 */
$estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];
$_meses    = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
$_tzBogota = new DateTimeZone('America/Bogota');
?>
<?php foreach ($pedidos as $p): ?>
<?php
    $telRaw    = $p['telefono'] ?? '';
    $telLimpio = preg_replace('/\D+/', '', $telRaw);
    if ($telLimpio !== '') {
        if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
        if (strpos($telLimpio, '57') !== 0) {
            if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
            $telLimpio = '57' . $telLimpio;
        }
    }
    $estadoActual  = $p['estado'] ?? 'nuevo';
    $cantidadTotal = max(1, (int)($p['cantidad_total'] ?? 1));
    $precioUnit    = (float)($p['precio_venta'] ?? 0);
    $precioProvUnit= (float)($p['precio_proveedor'] ?? 0);
    $precioTotal   = isset($p['precio_total']) ? (float)$p['precio_total'] : ($precioUnit * $cantidadTotal);
    $costoEnvio    = (float)($p['costo_envio'] ?? $p['producto_costo_envio'] ?? 0);
    $costoTotal    = ($precioProvUnit * $cantidadTotal) + $costoEnvio;
    $utilidadTotal = is_finite($precioTotal - $costoTotal) ? $precioTotal - $costoTotal : 0.0;
    $tsCreado      = !empty($p['created_at']) ? strtotime($p['created_at']) : 0;
    $createdFmt    = '';
    if ($tsCreado) {
        $_dtC = (new DateTime('@' . $tsCreado))->setTimezone($_tzBogota);
        $createdFmt = $_dtC->format('j') . ' ' . $_meses[(int)$_dtC->format('n') - 1] . ' ' . $_dtC->format('Y');
    }
?>
    <tr data-pedido-id="<?= htmlspecialchars($p['id'] ?? '') ?>"
        data-estado="<?= htmlspecialchars($estadoActual) ?>"
        data-ts="<?= htmlspecialchars($p['updated_at'] ?? ($p['created_at'] ?? '')) ?>"
        data-created-ts="<?= $tsCreado ?>">

        <td data-order="<?= $tsCreado ?>">
            <?= $createdFmt ?: '—' ?>
        </td>

        <td>
            <strong><?= htmlspecialchars(trim(($p['nombre'] ?? '') . ' ' . ($p['apellidos'] ?? ''))) ?></strong>
            <br><small class="dt-cell-sub"><?= htmlspecialchars($p['municipio'] ?? '') ?><?= !empty($p['departamento']) ? ', ' . htmlspecialchars($p['departamento']) : '' ?></small>
            <?= riesgo_cliente_chip(($riesgos ?? [])[DropiOrden::sufijo((string)$telRaw)] ?? null) ?>
        </td>

        <td>
            <?= htmlspecialchars($p['producto_nombre'] ?? '') ?>
            <small class="dt-cell-sub">(<?= $cantidadTotal ?> <?= $cantidadTotal > 1 ? 'uds' : 'ud' ?><?= !empty($p['color']) ? ' · ' . htmlspecialchars($p['color']) : '' ?>)</small>
        </td>

        <td>
            <span class="status-tag status-<?= htmlspecialchars($estadoActual) ?>">
                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($estadoActual))) ?>
            </span>
            <?php
                $dropiOrderId  = $p['dropi_order_id'] ?? null;
                $dropiError    = $p['dropi_sync_error'] ?? null;
            ?>
            <div class="dropi-badge" data-dropi-badge>
                <?php if (!empty($dropiOrderId)): ?>
                    <small class="dropi-badge--ok" title="Orden creada en Dropi">
                        <i class="fas fa-check-circle"></i> Dropi #<?= htmlspecialchars((string)$dropiOrderId) ?>
                    </small>
                <?php elseif (!empty($dropiError)): ?>
                    <small class="dropi-badge--error" title="<?= htmlspecialchars($dropiError) ?>">
                        <i class="fas fa-triangle-exclamation"></i> Dropi: error
                    </small>
                <?php endif; ?>
            </div>
        </td>

        <td>
            <form action="<?= BASE_URL ?>/AdminPedidos/cambiarEstado" method="POST" class="status-form dt-status-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
                <select name="estado" class="form-select-sm">
                    <?php foreach ($estadosPosibles as $est): ?>
                        <option value="<?= htmlspecialchars($est) ?>" <?= $estadoActual === $est ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($est))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-save-status btn-save-status--sm" title="Guardar estado"><i class="fas fa-check"></i></button>
            </form>
        </td>

        <td>
            <div class="dt-actions">
                <?php if ($telLimpio !== ''): ?>
                    <button type="button" class="btn-icon-wa js-wa-open"
                            title="WhatsApp"
                            data-id="<?= htmlspecialchars((string)($p['id'] ?? '')) ?>"
                            data-telefono="<?= htmlspecialchars($telRaw) ?>"
                            data-nombre="<?= htmlspecialchars($p['nombre'] ?? '') ?>"
                            data-apellidos="<?= htmlspecialchars($p['apellidos'] ?? '') ?>"
                            data-producto="<?= htmlspecialchars($p['producto_nombre'] ?? '') ?>"
                            data-cantidad="<?= htmlspecialchars((string)$cantidadTotal) ?>"
                            data-precio="<?= htmlspecialchars('$' . number_format($precioTotal, 0, ',', '.')) ?>"
                            data-municipio="<?= htmlspecialchars($p['municipio'] ?? '') ?>"
                            data-departamento="<?= htmlspecialchars($p['departamento'] ?? '') ?>"
                            data-estado="<?= htmlspecialchars($estadoActual) ?>"
                            data-tipo-entrega="<?= htmlspecialchars($p['tipo_entrega'] ?? '') ?>"
                            data-numero-guia="<?= htmlspecialchars($p['numero_guia'] ?? '') ?>">
                        <i class="fab fa-whatsapp"></i>
                    </button>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/AdminPedidos/detalle?id=<?= htmlspecialchars($p['id'] ?? '') ?>"
                   class="btn-detail btn-detail--sm js-ver-detalle"
                   data-id="<?= htmlspecialchars($p['id'] ?? '') ?>"
                   aria-label="Ver detalle del pedido #<?= htmlspecialchars($p['id'] ?? '') ?>">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
