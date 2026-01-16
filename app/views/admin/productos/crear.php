<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Nuevo producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/tienda_mvc/public/css/crearProducto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Estilos mínimos para el bloque de colores -->
    <style>
        .colors-wrap { display: grid; gap: 10px; margin-top: 8px; }
        .color-row { display: flex; gap: 10px; align-items: center; }
        .color-row input { flex: 1; }
        .btn-remove-color {
            border: 0; background: rgba(0,0,0,.08);
            width: 40px; height: 40px; border-radius: 10px;
            cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        }
        .btn-remove-color:hover { background: rgba(0,0,0,.14); }
        .btn-add-color { margin-top: 10px; }
    </style>
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

    <aside class="material-sidebar" aria-label="Menú admin">
        <div class="sidebar-logo">
            <h2>FEDORA ULTIMATE</h2>
        </div>

        <div class="sidebar-user">
            <img src="/tienda_mvc/public/img/admi/1.jpg?user=<?= substr($usuarioNombre, 0, 2) ?>" alt="User">
            <div class="sidebar-user-text">
                <h4><?= htmlspecialchars($usuarioNombre) ?></h4>
                <small><?= htmlspecialchars($usuarioEmail) ?></small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/tienda_mvc/AdminPedidos/index">
                <i class="fas fa-box"></i> Pedidos
            </a>
            <a href="/tienda_mvc/AdminProductos/index" class="active">
                <i class="fas fa-shopping-bag"></i> Productos
            </a>
            <a href="/tienda_mvc/Auth/logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </nav>
    </aside>

    <main class="material-main material-main--simple">
        <header class="material-header">
            <div class="header-greeting header-greeting--with-menu">
                <button class="btn-menu" id="btnMenu" aria-label="Abrir menú">
                    <i class="fas fa-bars"></i>
                </button>

                <div>
                    <h3>Crear nuevo producto</h3>
                    <p>Define el nombre, precios y estado del producto</p>
                </div>
            </div>

            <div class="header-actions">
                <a href="/tienda_mvc/AdminProductos/index" class="btn-detail">
                    ← Volver a productos
                </a>
            </div>
        </header>

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
                    <form action="/tienda_mvc/AdminProductos/guardarNuevo"
                        method="POST"
                        class="admin-form"
                        enctype="multipart/form-data">

                        <div class="form-grid">

                            <div class="form-group form-group--full">
                                <label for="nombre">Nombre del producto <span class="req">*</span></label>
                                <input type="text" id="nombre" name="nombre"
                                    value="<?= htmlspecialchars($old['nombre'] ?? '') ?>" required>
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

                            <!-- ✅ COLORES (NUEVO) -->
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

                            <a href="/tienda_mvc/AdminProductos/index" class="btn-ghost">
                                Cancelar
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </section>
    </main>

    <script src="/tienda_mvc/public/js/funciones.js"></script>

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
