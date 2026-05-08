<?php
$pedido = $pedido ?? null;

if (!$pedido) {
  echo "<div style='color:#fff;padding:1rem;'>No se encontró el pedido.</div>";
  return;
}

$estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'en_oficina', 'entregado', 'cancelado'];
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

      <?php if ($telLimpio !== ''): ?>
        <button type="button" class="btn-whatsapp js-wa-open"
                data-telefono="<?= htmlspecialchars($telRaw) ?>"
                data-nombre="<?= htmlspecialchars($pedido['nombre'] ?? '') ?>"
                data-apellidos="<?= htmlspecialchars($pedido['apellidos'] ?? '') ?>"
                data-producto="<?= htmlspecialchars($pedido['producto_nombre'] ?? '') ?>"
                data-cantidad="<?= htmlspecialchars((string)$cantidadTotal) ?>"
                data-precio="<?= htmlspecialchars('$' . number_format($precioTotal, 0, ',', '.')) ?>"
                data-municipio="<?= htmlspecialchars($pedido['municipio'] ?? '') ?>"
                data-departamento="<?= htmlspecialchars($pedido['departamento'] ?? '') ?>"
                data-estado="<?= htmlspecialchars($estadoSafe) ?>"
                data-tipo-entrega="<?= htmlspecialchars($pedido['tipo_entrega'] ?? '') ?>">
          <i class="fab fa-whatsapp"></i> WhatsApp
        </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="pedido-modal-scroll">

    <div class="table-container">
      <div class="table-header">
        <h3>Estado y producto</h3>
      </div>

      <div class="detalle-grid">

        <div class="detalle-item">
          <span class="detalle-label">Estado actual</span>

          <span class="status-tag status-<?= htmlspecialchars($estadoSafe) ?> js-modal-status">
            <?= ucfirst(htmlspecialchars($estadoSafe)) ?>
          </span>

          <form action="/tienda_mvc/AdminPedidos/cambiarEstado" method="POST" class="status-form js-estado-form" style="margin-top:.75rem;">
            <?= csrf_field() ?>
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
        <h3>Cliente</h3>
      </div>

      <div class="detalle-grid">

        <div class="detalle-item">
          <span class="detalle-label">Nombre</span>
          <span class="detalle-value">
            <?= htmlspecialchars(trim(($pedido['nombre'] ?? '') . ' ' . ($pedido['apellidos'] ?? ''))) ?>
          </span>
        </div>

        <div class="detalle-item">
          <span class="detalle-label">Teléfono</span>
          <div class="detalle-value phone-edit-wrap">
            <span class="phone-display"><?= htmlspecialchars($pedido['telefono'] ?? '-') ?></span>
            <button type="button" class="btn-edit-field" title="Editar teléfono">
              <i class="fas fa-pencil-alt"></i>
            </button>
            <form class="phone-edit-form" style="display:none">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)($pedido['id'] ?? 0) ?>">
              <input type="tel" name="telefono"
                     value="<?= htmlspecialchars($pedido['telefono'] ?? '') ?>"
                     class="phone-edit-input"
                     placeholder="Ej: 3001234567">
              <button type="submit" class="btn-save-inline">Guardar</button>
              <button type="button" class="btn-cancel-inline">×</button>
            </form>
          </div>
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

      <div class="detalle-grid">

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

</div>