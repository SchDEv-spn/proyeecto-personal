<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Editar producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Estilos mínimos del bloque de colores (coherentes con tu UI) -->

</head>

<body>
    <?php
    $errores  = $errores  ?? [];
    $old      = $old      ?? [];
    $producto = $producto ?? null;

    // Colores (si no llegan, queda [])
    $colores = $colores ?? [];

    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';

    $productoId = $old['id'] ?? ($producto['id'] ?? '');
    $imgActual  = $old['imagen_principal'] ?? ($producto['imagen_principal'] ?? '');
    $nombreProd = $old['nombre'] ?? ($producto['nombre'] ?? '');
    $activoVal  = $old['activo'] ?? ($producto['activo'] ?? 1);

    // Colores para re-pintar el formulario si hubo error
    $coloresForm = $old['colores'] ?? $colores;
    if (is_string($coloresForm)) $coloresForm = array_map('trim', explode(',', $coloresForm));
    if (!is_array($coloresForm)) $coloresForm = [];
    $coloresForm = array_values(array_filter(array_map(fn($c) => trim((string)$c), $coloresForm), fn($c) => $c !== ''));
    if (empty($coloresForm)) $coloresForm = ['']; // al menos 1 input visible

    // Preview robusto para imagen principal (nombre / ruta / URL)
    $UPLOADS_BASE = BASE_URL . '/public/uploads/productos/'; // ajusta si tu carpeta real es distinta
    $imgPreview = '';
    $tmp = trim((string)$imgActual);

    if ($tmp !== '') {
        if (preg_match('#^https?://#i', $tmp)) {
            $imgPreview = $tmp;
        } elseif ($tmp[0] === '/') {
            $imgPreview = $tmp;
        } else {
            if (stripos($tmp, 'uploads/') !== false || stripos($tmp, 'public/') !== false) {
                $imgPreview = BASE_URL . '/' . ltrim($tmp, '/');
            } else {
                $imgPreview = $UPLOADS_BASE . $tmp;
            }
        }
    }
    ?>

    <!-- Overlay sidebar (mobile) -->
    <div class="sidebar-overlay" aria-hidden="true"></div>

    <div class="app-shell">

        <!-- Sidebar -->
        <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

        <!-- Main -->
        <main class="material-main">

            <?php
            // Header (partials/_header.php)
            $pageTitle = 'Editar producto';

            $pageSubtitle = $producto
                ? 'Estás editando: ' . ($producto['nombre'] ?? '')
                : 'Edita la información del producto';

            $showRangeFilter = false;
            $showSearch      = false;

            $headerCta = [
                'href'  => '<?= BASE_URL ?>/AdminProductos/index',
                'label' => '← Volver a productos',
                'class' => 'btn-detail'
            ];

            require __DIR__ . '/../partials/_header.php';
            ?>

            <section class="material-content">

                <?php if (!$producto && empty($productoId)): ?>
                    <div class="admin-alert-error">
                        <div class="admin-alert-title">
                            <i class="fas fa-triangle-exclamation"></i>
                            Producto no encontrado
                        </div>
                        <p style="margin:0;color:var(--gray-light);">
                            No se encontró el producto a editar. Vuelve al listado.
                        </p>
                    </div>
                <?php endif; ?>

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
                        <form action="<?= BASE_URL ?>/AdminProductos/actualizar"
                            method="POST"
                            class="admin-form"
                            enctype="multipart/form-data">
                            <?= csrf_field() ?>

                            <input type="hidden" name="id" value="<?= htmlspecialchars($productoId) ?>">
                            <input type="hidden" name="imagen_principal_actual" value="<?= htmlspecialchars($imgActual) ?>">

                            <div class="form-grid">

                                <div class="form-group form-group--full">
                                    <label for="nombre">Nombre del producto <span class="req">*</span></label>
                                    <input type="text" id="nombre" name="nombre"
                                        value="<?= htmlspecialchars($nombreProd) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="precio_regular">Precio regular (antes) <span class="req">*</span></label>
                                    <input type="number" id="precio_regular" name="precio_regular"
                                        value="<?= htmlspecialchars($old['precio_regular'] ?? ($producto['precio_regular'] ?? '')) ?>"
                                        step="100" min="0" required>
                                    <small class="help">Este es el precio tachado. Debe ser >= precio de venta.</small>
                                </div>

                                <div class="form-group">
                                    <label for="precio_venta">Precio de venta <span class="req">*</span></label>
                                    <input type="number" id="precio_venta" name="precio_venta"
                                        value="<?= htmlspecialchars($old['precio_venta'] ?? ($producto['precio_venta'] ?? '')) ?>"
                                        step="100" min="0" required>
                                    <small class="help">Ej: 199900 (sin puntos ni comas).</small>
                                </div>

                                <div class="form-group">
                                    <label for="precio_proveedor">Precio proveedor <span class="req">*</span></label>
                                    <input type="number" id="precio_proveedor" name="precio_proveedor"
                                        value="<?= htmlspecialchars($old['precio_proveedor'] ?? ($producto['precio_proveedor'] ?? '')) ?>"
                                        step="100" min="0" required>
                                    <small class="help">Costo base para calcular utilidad.</small>
                                </div>

                                <div class="form-group">
                                    <label for="costo_envio">Costo de envío (interno)</label>
                                    <input type="number" id="costo_envio" name="costo_envio"
                                        value="<?= htmlspecialchars($old['costo_envio'] ?? ($producto['costo_envio'] ?? 0)) ?>"
                                        step="100" min="0">
                                    <small class="help">Este valor NO lo ve el cliente. Se usa para calcular utilidad real.</small>
                                </div>

                                <div class="form-group">
                                    <h4>
                                        <i class="fas fa-tags"></i> Estrategia de Descuento (Multicantidad)
                                    </h4>

                                    <div class="form-group">
                                        <label>
                                            <input type="hidden" name="descuento_multicantidad_activo" value="0">

                                            <input type="checkbox"
                                                name="descuento_multicantidad_activo"
                                                value="1"
                                                <?= ((int)($old['descuento_multicantidad_activo'] ?? ($producto['descuento_multicantidad_activo'] ?? 1)) === 1) ? 'checked' : '' ?>>
                                            Activar descuentos por multicantidad
                                        </label>

                                        <small class="help">
                                            Si lo desactivas, los pedidos cobrarán precio normal aunque existan porcentajes configurados.
                                        </small>
                                    </div>

                                    <div class="form-group">
                                        <label for="descuento_2da">Descuento 2da unidad (%)</label>
                                        <input type="number"
                                            id="descuento_2da"
                                            name="descuento_2da"
                                            value="<?= htmlspecialchars((string)($old['descuento_2da'] ?? ($producto['descuento_2da'] ?? 15))) ?>"
                                            min="0" max="100" step="1">
                                        <small class="help">Ej: 40 para aplicar el 40% OFF a la segunda unidad.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="descuento_3ra">Descuento 3ra+ unidad (%)</label>
                                        <input type="number"
                                            id="descuento_3ra"
                                            name="descuento_3ra"
                                            value="<?= htmlspecialchars((string)($old['descuento_3ra'] ?? ($producto['descuento_3ra'] ?? 20))) ?>"
                                            min="0" max="100" step="1">
                                        <small class="help">Ej: 50 para aplicar el 50% OFF desde la tercera unidad en adelante.</small>
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

                                <!-- Imagen actual -->
                                <div class="form-group form-group--full">
                                    <label>Imagen principal actual</label>

                                    <?php if (!empty($imgPreview)): ?>
                                        <div class="img-preview">
                                            <img src="<?= htmlspecialchars($imgPreview) ?>" alt="Imagen producto">
                                            <div class="img-preview-meta">
                                                <small class="help">Si subes una nueva imagen, esta será reemplazada.</small>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="img-empty">
                                            <i class="fas fa-image"></i>
                                            <span>Sin imagen asignada</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group form-group--full">
                                    <label for="imagen_principal_file">Subir nueva imagen (opcional)</label>
                                    <input type="file" id="imagen_principal_file" name="imagen_principal_file" accept="image/*">
                                    <small class="help">Recomendado: JPG/PNG, buena resolución.</small>
                                </div>

                                <div class="form-group form-group--full">
                                    <label class="check-row">
                                        <input type="checkbox" name="activo" value="1" <?= ((int)$activoVal === 1) ? 'checked' : '' ?>>
                                        <span>Producto activo</span>
                                    </label>
                                </div>

                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i> Guardar cambios
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
                    <input type="text" name="colores[]" placeholder="Ej: Azul" value="${String(value).replace(/"/g,'&quot;')}">
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