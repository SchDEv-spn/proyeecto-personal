<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Nuevo producto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS tal cual lo mencionas -->
    <link rel="stylesheet" href="/tienda_mvc/public/css/crearProducto.css">

    <!-- Iconos (usados en el sidebar/header) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php
    $errores = $errores ?? [];
    $old     = $old     ?? [];
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';
    ?>

    <!-- Sidebar -->
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

    <!-- Main -->
    <main class="material-main material-main--simple">
        <!-- Header fijo -->
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
</body>

</html>