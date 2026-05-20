<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Nuevo producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


</head>

<body>
<?php
    $errores = $errores ?? [];
    $old     = $old     ?? [];

    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';

    // Colores para re-pintar el formulario si hubo error
    $coloresForm = $old['colores'] ?? [];
    if (is_string($coloresForm)) $coloresForm = array_map('trim', explode(',', $coloresForm));
    if (!is_array($coloresForm)) $coloresForm = [];
    $coloresForm = array_values(array_filter(array_map(fn($c) => trim((string)$c), $coloresForm), fn($c) => $c !== ''));
    if (empty($coloresForm)) $coloresForm = ['']; // al menos 1 input visible
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <!-- Sidebar -->
    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main material-main--simple">
        <?php
            // Header (partial)
            $pageTitle = 'Crear nuevo producto';
            $pageSubtitle = 'Define el nombre, precios y estado del producto';

            $showRangeFilter = false;
            $showSearch = false;

            $headerCtas = [
                [
                    'href'  => '<?= BASE_URL ?>/AdminProductos/index',
                    'label' => '← Volver a productos',
                    'class' => 'btn-detail',
                    'icon'  => '',
                ]
            ];

            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content">

            <?php if (!empty($errores)): ?>
                <div class="admin-alert-error">
                    <div class="admin-alert-title">
                        <i class="fas fa-triangle-exclamation"></i>
                        Revisa estos campos
                    </div>
                    <ul>
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <div class="form-card-header">
                    <h3>Información del producto</h3>
                </div>

                <div class="form-card-body">
                    <form action="<?= BASE_URL ?>/AdminProductos/guardarNuevo"
                          method="POST"
                          class="admin-form"
                          enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="form-grid">

                            <div class="form-group form-group--full">
                                <label for="nombre">Nombre del producto <span class="req">*</span></label>
                                <input type="text" id="nombre" name="nombre"
                                       value="<?= htmlspecialchars($old['nombre'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="precio_regular">Precio regular (antes) <span class="req">*</span></label>
                                <input type="number" id="precio_regular" name="precio_regular"
                                       value="<?= htmlspecialchars($old['precio_regular'] ?? '') ?>"
                                       step="100" min="0" required>
                                <small class="help">Este es el precio tachado. Debe ser >= precio de venta.</small>
                            </div>

                            <div class="form-group">
                                <label for="precio_venta">Precio de venta <span class="req">*</span></label>
                                <input type="number" id="precio_venta" name="precio_venta"
                                       value="<?= htmlspecialchars($old['precio_venta'] ?? '') ?>"
                                       step="100" min="0" required>
                                <small class="help">Ej: 199900 (sin puntos ni comas).</small>
                            </div>

                            <div class="form-group">
                                <label for="precio_proveedor">Precio proveedor <span class="req">*</span></label>
                                <input type="number" id="precio_proveedor" name="precio_proveedor"
                                       value="<?= htmlspecialchars($old['precio_proveedor'] ?? '') ?>"
                                       step="100" min="0" required>
                                <small class="help">Costo base para calcular utilidad.</small>
                            </div>

                            <div class="form-group">
                                <label for="costo_envio">Costo de envío (interno)</label>
                                <input type="number" id="costo_envio" name="costo_envio"
                                       value="<?= htmlspecialchars((string)($old['costo_envio'] ?? 0)) ?>"
                                       step="100" min="0">
                                <small class="help">Este valor NO lo ve el cliente. Se usa para calcular utilidad real.</small>
                            </div>

                            <div class="form-group">
                                <h4>
                                    <i class="fas fa-tags"></i> Estrategia de Descuento (Multicantidad)
                                </h4>

                                <div class="form-group">
                                    <label>
                                        <!-- asegura que SIEMPRE llegue algo en POST -->
                                        <input type="hidden" name="descuento_multicantidad_activo" value="0">

                                        <input type="checkbox"
                                               name="descuento_multicantidad_activo"
                                               value="1"
                                               <?= (!isset($old['descuento_multicantidad_activo']) || (int)$old['descuento_multicantidad_activo'] === 1) ? 'checked' : '' ?>>
                                        Activar descuentos por multicantidad
                                    </label>
                                    <small class="help">Si lo desactivas, el pedido cobrará precio normal aunque existan porcentajes configurados.</small>
                                </div>

                                <div class="form-group">
                                    <label for="descuento_2da">Descuento 2da unidad (%)</label>
                                    <input type="number" id="descuento_2da" name="descuento_2da"
                                           value="<?= htmlspecialchars((string)($old['descuento_2da'] ?? 15)) ?>"
                                           min="0" max="100" step="1">
                                    <small class="help">Ej: 40 para aplicar el 40% OFF a la segunda unidad.</small>
                                </div>

                                <div class="form-group">
                                    <label for="descuento_3ra">Descuento 3ra+ unidad (%)</label>
                                    <input type="number" id="descuento_3ra" name="descuento_3ra"
                                           value="<?= htmlspecialchars((string)($old['descuento_3ra'] ?? 20)) ?>"
                                           min="0" max="100" step="1">
                                    <small class="help">Ej: 50 para aplicar el 50% OFF de la tercera en adelante.</small>
                                </div>
                            </div>

                            <!-- COLORES -->
                            <div class="form-group form-group--full">
                                <label>Colores disponibles (opcional)</label>

                                <div id="colorsWrap" class="colors-wrap">
                                    <?php foreach ($coloresForm as $c): ?>
                                        <div class="color-row">
                                            <input type="text" name="colores[]" placeholder="Ej: Negro" value="<?= htmlspecialchars($c) ?>">
                                            <button type="button" class="btn-remove-color" aria-label="Quitar color">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" id="addColorBtn" class="btn-ghost btn-add-color">
                                    <i class="fas fa-plus"></i> Agregar otro color
                                </button>

                                <small class="help">
                                    Si dejas todos los colores vacíos, la landing no mostrará selector de color.
                                </small>
                            </div>

                            <div class="form-group form-group--full">
                                <label for="imagen_principal_file">Imagen principal (opcional)</label>
                                <input type="file" id="imagen_principal_file" name="imagen_principal_file" accept="image/*">
                                <small class="help">Recomendado: JPG/PNG, buena resolución.</small>
                            </div>

                            <div class="form-group form-group--full">
                                <label class="check-row">
                                    <input type="checkbox" name="activo" value="1"
                                           <?= (!isset($old['activo']) || $old['activo']) ? 'checked' : '' ?>>
                                    <span>Producto activo</span>
                                </label>
                            </div>

                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Guardar producto
                            </button>

                            <a href="<?= BASE_URL ?>/AdminProductos/index" class="btn-ghost">
                                Cancelar
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </section>
    </main>

</div><!-- /app-shell -->

<script src="<?= BASE_URL ?>/public/js/funciones.js"></script>

<!-- JS del bloque de colores -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.getElementById('colorsWrap');
    const btnAdd = document.getElementById('addColorBtn');

    function makeRow(value = '') {
        const row = document.createElement('div');
        row.className = 'color-row';
        row.innerHTML = `
            <input type="text" name="colores[]" placeholder="Ej: Azul" value="${value.replace(/"/g,'&quot;')}">
            <button type="button" class="btn-remove-color" aria-label="Quitar color">
                <i class="fas fa-times"></i>
            </button>
        `;
        return row;
    }

    btnAdd.addEventListener('click', () => {
        wrap.appendChild(makeRow(''));
    });

    wrap.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-remove-color');
        if (!btn) return;

        const row = btn.closest('.color-row');
        if (!row) return;

        // Si es la única fila, solo limpia el input (no la borra)
        const rows = wrap.querySelectorAll('.color-row');
        if (rows.length <= 1) {
            const input = row.querySelector('input[name="colores[]"]');
            if (input) input.value = '';
            return;
        }

        row.remove();
    });
});
</script>

</body>
</html>