<?php
$pedido = $pedido ?? null;

if (!$pedido) {
  echo "<div style='color:#fff;padding:1rem;'>No se encontró el pedido.</div>";
  return;
}

$estadosPosibles = ['nuevo','contactado','confirmado','enviado','entregado','cancelado'];
$estadoActual = $pedido['estado'] ?? 'nuevo';
$estadoSafe = in_array($estadoActual, $estadosPosibles, true) ? $estadoActual : 'nuevo';

// =========================
// Valores numéricos seguros
// =========================
$cantidadTotal = (int)($pedido['cantidad_total'] ?? 1);
if ($cantidadTotal < 1) $cantidadTotal = 1;

$precioUnit     = (float)($pedido['precio_venta'] ?? 0);
$proveedorUnit  = (float)($pedido['precio_proveedor'] ?? 0);

// Subtotal (referencia)
$subtotal = $precioUnit * $cantidadTotal;

// Descuento (si existe)
$descuentoTotal = (float)($pedido['descuento_total'] ?? 0);
if ($descuentoTotal < 0) $descuentoTotal = 0;

// ✅ Total cobrado REAL (misma regla que la tabla)
// prioridad: pedido.precio_total -> subtotal - descuento -> subtotal
$precioTotal = 0.0;
if (isset($pedido['precio_total'])) {
  $precioTotal = (float)$pedido['precio_total'];
}
if ($precioTotal <= 0) {
  $precioTotal = max(0, $subtotal - $descuentoTotal);
}

// ✅ Costo envío (misma regla que la tabla)
// prioridad: pedido.costo_envio -> producto_costo_envio -> 0
$costoEnvio = 0.0;
if (isset($pedido['costo_envio'])) {
  $costoEnvio = (float)$pedido['costo_envio'];
} elseif (isset($pedido['producto_costo_envio'])) {
  $costoEnvio = (float)$pedido['producto_costo_envio'];
} elseif (isset($pedido['costo_envio_total'])) {
  // compatibilidad por si antes lo llamabas así
  $costoEnvio = (float)$pedido['costo_envio_total'];
}
if ($costoEnvio < 0) $costoEnvio = 0;

// Costos proveedor total
$costoProveedorTotal = $proveedorUnit * $cantidadTotal;

// ✅ Utilidad total REAL (misma regla que la tabla)
$utilidadTotal = $precioTotal - ($costoProveedorTotal + $costoEnvio);
if (!is_finite($utilidadTotal)) $utilidadTotal = 0.0;

// ✅ Utilidad unitaria (coherente con utilidad total)
$utilidadUnit = ($cantidadTotal > 0) ? ($utilidadTotal / $cantidadTotal) : 0.0;
if (!is_finite($utilidadUnit)) $utilidadUnit = 0.0;

// =========================
// WhatsApp URL
// =========================
$telRaw    = $pedido['telefono'] ?? '';
$telLimpio = preg_replace('/\D+/', '', $telRaw);
$waUrl     = '';

if ($telLimpio !== '') {
  if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
  if (strpos($telLimpio, '57') !== 0) {
    if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
    $telLimpio = '57' . $telLimpio;
  }
  $waUrl = "https://wa.me/" . $telLimpio . "?text=" . urlencode(
    "Hola " . ($pedido['nombre'] ?? '') . ", te escribimos sobre tu pedido de " . ($pedido['producto_nombre'] ?? '') . "."
  );
}
?>

<div class="pedido-modal-wrap" data-pedido-id="<?= htmlspecialchars($pedido['id'] ?? '') ?>">

  <div class="modal-header">
    <div>
      <h3 id="pedidoModalTitle">Pedido #<?= htmlspecialchars($pedido['id'] ?? '') ?></h3>
      <p>Creado el <?= htmlspecialchars($pedido['created_at'] ?? '') ?></p>
    </div>

    <div class="detalle-acciones">
      <button type="button" class="btn-detail js-prev-pedido" aria-label="Pedido anterior">
        <i class="fas fa-chevron-left"></i> Anterior
      </button>

      <span class="js-pedido-pager" style="color: var(--gray-light); font-weight:700; font-size:.85rem;"></span>

      <button type="button" class="btn-detail js-next-pedido" aria-label="Pedido siguiente">
        Siguiente <i class="fas fa-chevron-right"></i>
      </button>

      <a href="/tienda_mvc/AdminPedidos/detalle?id=<?= htmlspecialchars($pedido['id'] ?? '') ?>" class="btn-detail">
        Abrir página
      </a>

      <?php if ($waUrl): ?>
        <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" rel="noopener" class="btn-whatsapp">
          <i class="fab fa-whatsapp"></i> WhatsApp
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-container">
    <div class="table-header">
      <h3>Estado y producto</h3>
    </div>

    <div class="detalle-grid" style="padding:1.5rem 2rem;">
      <div class="detalle-item">
        <span class="detalle-label">Estado actual</span>

        <span class="status-tag status-<?= htmlspecialchars($estadoSafe) ?> js-modal-status">
          <?= ucfirst(htmlspecialchars($estadoSafe)) ?>
        </span>

        <form action="/tienda_mvc/AdminPedidos/cambiarEstado" method="POST" class="status-form js-estado-form" style="margin-top:.75rem;">
          <input type="hidden" name="id" value="<?= htmlspecialchars($pedido['id'] ?? '') ?>">
          <input type="hidden" name="ajax" value="1">
          <select name="estado">
            <?php foreach ($estadosPosibles as $estado): ?>
              <option value="<?= htmlspecialchars($estado) ?>" <?= $estadoSafe === $estado ? 'selected' : '' ?>>
                <?= ucfirst(htmlspecialchars($estado)) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Guardar</button>
        </form>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Producto</span>
        <span class="detalle-value"><?= htmlspecialchars($pedido['producto_nombre'] ?? '-') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Cantidad total</span>
        <span class="detalle-value"><?= number_format($cantidadTotal, 0, ',', '.') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Color</span>
        <span class="detalle-value"><?= htmlspecialchars(($pedido['color'] ?? '') ?: '-') ?></span>
      </div>
    </div>
  </div>

  <div class="table-container" style="margin-top:1.25rem;">
    <div class="table-header">
      <h3>Totales del pedido</h3>
    </div>

    <div class="detalle-grid" style="padding:1.5rem 2rem;">
      <div class="detalle-item">
        <span class="detalle-label">Precio unitario</span>
        <span class="detalle-value">$<?= number_format($precioUnit, 0, ',', '.') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Subtotal</span>
        <span class="detalle-value">$<?= number_format($subtotal, 0, ',', '.') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Descuento total</span>
        <span class="detalle-value">$<?= number_format($descuentoTotal, 0, ',', '.') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Total a cobrar</span>
        <span class="detalle-value"><strong>$<?= number_format($precioTotal, 0, ',', '.') ?></strong></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Costo proveedor total</span>
        <span class="detalle-value">$<?= number_format($costoProveedorTotal, 0, ',', '.') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Costo envío interno</span>
        <span class="detalle-value">$<?= number_format($costoEnvio, 0, ',', '.') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Utilidad total</span>
        <span class="detalle-value"><strong>$<?= number_format($utilidadTotal, 0, ',', '.') ?></strong></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Utilidad (unitaria aprox)</span>
        <span class="detalle-value">$<?= number_format($utilidadUnit, 0, ',', '.') ?></span>
      </div>
    </div>
  </div>

  <div class="table-container" style="margin-top:1.25rem;">
    <div class="table-header">
      <h3>Cliente</h3>
    </div>

    <div class="detalle-grid" style="padding:1.5rem 2rem;">
      <div class="detalle-item">
        <span class="detalle-label">Nombre</span>
        <span class="detalle-value">
          <?= htmlspecialchars(trim(($pedido['nombre'] ?? '') . ' ' . ($pedido['apellidos'] ?? ''))) ?>
        </span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Teléfono</span>
        <span class="detalle-value"><?= htmlspecialchars($pedido['telefono'] ?? '-') ?></span>
      </div>

      <div class="detalle-item">
        <span class="detalle-label">Ubicación</span>
        <span class="detalle-value">
          <?= htmlspecialchars($pedido['municipio'] ?? '-') ?><br>
          <small style="color:var(--gray-light);"><?= htmlspecialchars($pedido['departamento'] ?? '-') ?></small>
        </span>
      </div>
    </div>
  </div>

  <div class="table-container" style="margin-top:1.25rem;">
    <div class="table-header">
      <h3>Envío</h3>
    </div>

    <div class="detalle-grid" style="padding:1.5rem 2rem;">
      <div class="detalle-item">
        <span class="detalle-label">Tipo entrega</span>
        <span class="detalle-value">
          <?= htmlspecialchars(($pedido['tipo_entrega'] ?? '') ? ucfirst($pedido['tipo_entrega']) : '-') ?>
        </span>
      </div>

      <?php if (!empty($pedido['direccion'])): ?>
        <div class="detalle-item detalle-item-full">
          <span class="detalle-label">Dirección</span>
          <span class="detalle-value"><?= htmlspecialchars($pedido['direccion']) ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
