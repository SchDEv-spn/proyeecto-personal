<?php
$pedido = $pedido ?? null;
if (!$pedido) {
  echo "<div style='padding:2rem;color:var(--tx-muted);text-align:center;'>No se encontró el pedido.</div>";
  return;
}

$estadosPosibles = ['nuevo','contactado','confirmado','enviado','en_oficina','entregado','cancelado'];
$estadoActual    = $pedido['estado'] ?? 'nuevo';
$estadoSafe      = in_array($estadoActual, $estadosPosibles, true) ? $estadoActual : 'nuevo';

$cantidadTotal   = max(1, (int)($pedido['cantidad_total'] ?? 1));
$precioUnit      = (float)($pedido['precio_venta']     ?? 0);
$proveedorUnit   = (float)($pedido['precio_proveedor'] ?? 0);
$subtotal        = $precioUnit * $cantidadTotal;
$descuentoTotal  = max(0, (float)($pedido['descuento_total'] ?? 0));
$precioTotal     = (float)($pedido['precio_total'] ?? 0) ?: max(0, $subtotal - $descuentoTotal);
$costoEnvio      = max(0, (float)($pedido['costo_envio'] ?? $pedido['producto_costo_envio'] ?? 0));
$costoTotal      = ($proveedorUnit * $cantidadTotal) + $costoEnvio;
$utilidadTotal   = is_finite($precioTotal - $costoTotal) ? $precioTotal - $costoTotal : 0.0;

$telRaw    = $pedido['telefono'] ?? '';
$telLimpio = preg_replace('/\D+/', '', $telRaw);
if ($telLimpio !== '') {
  if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
  if (strpos($telLimpio, '57') !== 0) {
    if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
    $telLimpio = '57' . $telLimpio;
  }
}

$_meses   = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
$_fechaTs = !empty($pedido['created_at']) ? strtotime($pedido['created_at']) : 0;
$_fechaFmt = '';
if ($_fechaTs) {
  $_dtM      = (new DateTime('@'.$_fechaTs))->setTimezone(new DateTimeZone('America/Bogota'));
  $_fechaFmt = $_dtM->format('j').' '.$_meses[(int)$_dtM->format('n')-1].'. '.$_dtM->format('Y').', '.$_dtM->format('g:i a');
}

$nombreCompleto   = trim(($pedido['nombre']??'').' '.($pedido['apellidos']??''));
$ubicacionTexto   = trim(($pedido['municipio']??'').', '.($pedido['departamento']??''), ', ');
?>

<div class="pedido-modal-wrap" data-pedido-id="<?= htmlspecialchars($pedido['id'] ?? '') ?>">

  <!-- HEADER -->
  <div class="modal-header">
    <div class="mh-top">
      <div class="mh-id">
        <h2 id="pedidoModalTitle">Pedido <span>#<?= htmlspecialchars($pedido['id'] ?? '') ?></span></h2>
        <span class="status-tag status-<?= htmlspecialchars($estadoSafe) ?> js-modal-status">
          <?= ucfirst(str_replace('_',' ', htmlspecialchars($estadoSafe))) ?>
        </span>
      </div>
      <?php if ($_fechaFmt): ?>
        <p class="mh-date"><?= htmlspecialchars($_fechaFmt) ?></p>
      <?php endif; ?>
    </div>
    <div class="mh-actions">
      <button type="button" class="mh-nav-btn js-prev-pedido"><i class="fas fa-chevron-left"></i> Anterior</button>
      <span class="js-pedido-pager mh-pager"></span>
      <button type="button" class="mh-nav-btn js-next-pedido">Siguiente <i class="fas fa-chevron-right"></i></button>
      <?php if ($telLimpio !== ''): ?>
      <button type="button" class="mh-wa-btn js-wa-open"
              data-telefono="<?= htmlspecialchars($telRaw) ?>"
              data-nombre="<?= htmlspecialchars($pedido['nombre'] ?? '') ?>"
              data-apellidos="<?= htmlspecialchars($pedido['apellidos'] ?? '') ?>"
              data-producto="<?= htmlspecialchars($pedido['producto_nombre'] ?? '') ?>"
              data-cantidad="<?= htmlspecialchars((string)$cantidadTotal) ?>"
              data-precio="<?= htmlspecialchars('$'.number_format($precioTotal,0,',','.')) ?>"
              data-municipio="<?= htmlspecialchars($pedido['municipio'] ?? '') ?>"
              data-departamento="<?= htmlspecialchars($pedido['departamento'] ?? '') ?>"
              data-estado="<?= htmlspecialchars($estadoSafe) ?>"
              data-tipo-entrega="<?= htmlspecialchars($pedido['tipo_entrega'] ?? '') ?>">
        <i class="fab fa-whatsapp"></i> WhatsApp
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- BODY -->
  <div class="pedido-modal-scroll">

    <!-- ESTADO Y PRODUCTO -->
    <div class="modal-section">
      <p class="modal-section__head">Estado y producto</p>
      <div class="mii-grid">

        <!-- Cambiar estado — full width -->
        <div class="mii mii--full">
          <div class="mii__icon"><i class="fas fa-tag"></i></div>
          <div class="mii__body">
            <span class="mii__label">Cambiar estado</span>
            <form action="<?= BASE_URL ?>/AdminPedidos/cambiarEstado" method="POST"
                  class="status-form js-estado-form mif-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id"   value="<?= htmlspecialchars($pedido['id'] ?? '') ?>">
              <input type="hidden" name="ajax" value="1">
              <select name="estado" class="form-select-sm">
                <?php foreach ($estadosPosibles as $est): ?>
                  <option value="<?= htmlspecialchars($est) ?>" <?= $estadoSafe===$est?'selected':'' ?>>
                    <?= ucfirst(str_replace('_',' ', htmlspecialchars($est))) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn-save-status btn-save-status--sm" title="Guardar estado"><i class="fas fa-check"></i></button>
            </form>
          </div>
        </div>

        <!-- Producto -->
        <div class="mii">
          <div class="mii__icon"><i class="fas fa-shopping-bag"></i></div>
          <div class="mii__body">
            <span class="mii__label">Producto</span>
            <span class="mii__value">
              <?= htmlspecialchars($pedido['producto_nombre'] ?? '-') ?>
              <button class="mii__copy js-copy" data-copy="<?= htmlspecialchars($pedido['producto_nombre'] ?? '') ?>" title="Copiar"><i class="fas fa-copy"></i></button>
            </span>
          </div>
        </div>

        <!-- Cantidad -->
        <div class="mii">
          <div class="mii__icon"><i class="fas fa-layer-group"></i></div>
          <div class="mii__body">
            <span class="mii__label">Cantidad</span>
            <span class="mii__value"><?= $cantidadTotal ?> <?= $cantidadTotal > 1 ? 'uds' : 'ud' ?></span>
          </div>
        </div>

        <?php if (!empty($pedido['color'])): ?>
        <div class="mii">
          <div class="mii__icon"><i class="fas fa-palette"></i></div>
          <div class="mii__body">
            <span class="mii__label">Color</span>
            <span class="mii__value"><?= htmlspecialchars($pedido['color']) ?></span>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- CLIENTE -->
    <div class="modal-section">
      <p class="modal-section__head">Cliente</p>
      <div class="mii-grid">

        <div class="mii">
          <div class="mii__icon"><i class="fas fa-user"></i></div>
          <div class="mii__body">
            <span class="mii__label">Nombre</span>
            <span class="mii__value">
              <?= htmlspecialchars($nombreCompleto) ?>
              <button class="mii__copy js-copy" data-copy="<?= htmlspecialchars($nombreCompleto) ?>" title="Copiar"><i class="fas fa-copy"></i></button>
            </span>
          </div>
        </div>

        <div class="mii">
          <div class="mii__icon"><i class="fas fa-phone"></i></div>
          <div class="mii__body phone-edit-wrap">
            <span class="mii__label">Teléfono</span>
            <span class="mii__value">
              <span class="phone-display"><?= htmlspecialchars($telRaw ?: '-') ?></span>
              <button class="mii__copy js-copy" data-copy="<?= htmlspecialchars($telRaw) ?>" title="Copiar"><i class="fas fa-copy"></i></button>
              <button type="button" class="btn-edit-field" title="Editar"><i class="fas fa-pencil-alt"></i></button>
            </span>
            <form class="phone-edit-form" style="display:none">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)($pedido['id']??0) ?>">
              <input type="tel" name="telefono" value="<?= htmlspecialchars($pedido['telefono']??'') ?>"
                     class="phone-edit-input" placeholder="3001234567">
              <button type="submit"  class="btn-save-inline">Guardar</button>
              <button type="button"  class="btn-cancel-inline">×</button>
            </form>
          </div>
        </div>

        <div class="mii">
          <div class="mii__icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="mii__body">
            <span class="mii__label">Municipio</span>
            <span class="mii__value">
              <?= htmlspecialchars($pedido['municipio'] ?? '-') ?>
              <?php if (!empty($pedido['municipio'])): ?>
              <button class="mii__copy js-copy" data-copy="<?= htmlspecialchars($ubicacionTexto) ?>" title="Copiar ciudad, depto"><i class="fas fa-copy"></i></button>
              <?php endif; ?>
            </span>
          </div>
        </div>

        <div class="mii">
          <div class="mii__icon"><i class="fas fa-map"></i></div>
          <div class="mii__body">
            <span class="mii__label">Departamento</span>
            <span class="mii__value"><?= htmlspecialchars($pedido['departamento'] ?? '-') ?></span>
          </div>
        </div>

      </div>
    </div>

    <!-- ENVÍO -->
    <?php if (!empty($pedido['tipo_entrega']) || !empty($pedido['direccion']) || !empty($pedido['nota_entrega'])): ?>
    <div class="modal-section">
      <p class="modal-section__head">Envío</p>
      <div class="mii-grid">

        <?php if (!empty($pedido['tipo_entrega'])): ?>
        <div class="mii">
          <div class="mii__icon"><i class="fas fa-truck"></i></div>
          <div class="mii__body">
            <span class="mii__label">Tipo entrega</span>
            <span class="mii__value"><?= ucfirst(htmlspecialchars($pedido['tipo_entrega'])) ?></span>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($pedido['direccion'])): ?>
        <div class="mii mii--full">
          <div class="mii__icon"><i class="fas fa-location-dot"></i></div>
          <div class="mii__body">
            <span class="mii__label">Dirección</span>
            <span class="mii__value">
              <?= htmlspecialchars($pedido['direccion']) ?>
              <button class="mii__copy js-copy" data-copy="<?= htmlspecialchars($pedido['direccion']) ?>" title="Copiar"><i class="fas fa-copy"></i></button>
            </span>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($pedido['nota_entrega'])): ?>
        <div class="mii mii--full">
          <div class="mii__icon"><i class="fas fa-note-sticky"></i></div>
          <div class="mii__body">
            <span class="mii__label">Nota mensajero</span>
            <span class="mii__value"><?= htmlspecialchars($pedido['nota_entrega']) ?></span>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>

    <!-- FINANCIERO -->
    <div class="modal-finance-strip">
      <div class="modal-finance-kpi">
        <span class="modal-finance-kpi__label">Total cobrado</span>
        <strong class="modal-finance-kpi__value">$<?= number_format($precioTotal,0,',','.') ?></strong>
      </div>
      <div class="modal-finance-divider"></div>
      <div class="modal-finance-kpi">
        <span class="modal-finance-kpi__label">Utilidad</span>
        <strong class="modal-finance-kpi__value modal-finance-kpi__value--profit">
          $<?= number_format($utilidadTotal,0,',','.') ?>
        </strong>
      </div>
      <?php if ($costoEnvio > 0): ?>
      <div class="modal-finance-divider"></div>
      <div class="modal-finance-kpi">
        <span class="modal-finance-kpi__label">Costo envío</span>
        <strong class="modal-finance-kpi__value modal-finance-kpi__value--muted">
          $<?= number_format($costoEnvio,0,',','.') ?>
        </strong>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

