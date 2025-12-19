<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/tienda_mvc/public/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php
    $productos = $productos ?? [];
    $success   = $success   ?? '';

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
                    <h3>Productos</h3>
                    <p>Gestiona los productos, sus precios y sus landings</p>
                </div>
            </div>

            <div class="header-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input id="searchProductos" type="text" placeholder="Buscar producto, slug, estado...">
                </div>

                <a href="/tienda_mvc/AdminProductos/crear" class="btn-primary">
                    <i class="fas fa-plus"></i> Crear producto
                </a>

                <a href="/tienda_mvc/AdminPedidos/index" class="btn-detail">
                    Ver pedidos
                </a>
            </div>
        </header>

        <section class="material-content">

            <?php if (!empty($success)): ?>
                <div class="admin-alert-success">
                    <i class="fas fa-circle-check"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if (empty($productos)): ?>
                <div class="empty-state">
                    <p>No hay productos creados todavía.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <h3>Listado de productos</h3>
                    </div>

                    <table class="material-table" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Slug / URL</th>
                                <th>Precio</th>
                                <th>Utilidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($productos as $p): ?>
                                <?php
                                $precioVenta     = (float)($p['precio_venta'] ?? 0);
                                $precioProveedor = (float)($p['precio_proveedor'] ?? 0);
                                $utilidad        = $precioVenta - $precioProveedor;

                                $slug = $p['slug'] ?? '';
                                $landingUrl = $slug !== ''
                                    ? '/tienda_mvc/producto/' . urlencode($slug)
                                    : '/tienda_mvc/Landing/index?producto_id=' . urlencode($p['id']);

                                $activo = !empty($p['activo']);
                                ?>
                                <tr data-producto-id="<?= htmlspecialchars($p['id']) ?>">
                                    <td><?= htmlspecialchars($p['id']) ?></td>

                                    <td>
                                        <strong><?= htmlspecialchars($p['nombre'] ?? '') ?></strong><br>
                                        <small style="color:var(--gray-light);">Proveedor: $<?= number_format($precioProveedor, 0, ',', '.') ?></small>
                                    </td>

                                    <td>
                                        <?php if ($slug !== ''): ?>
                                            <small style="color:var(--gray-light);">
                                                Slug: <code class="code-pill"><?= htmlspecialchars($slug) ?></code>
                                            </small><br>
                                        <?php else: ?>
                                            <small style="color:var(--gray-light);"><em>Sin slug definido</em></small><br>
                                        <?php endif; ?>

                                        <a class="link-landing" href="<?= htmlspecialchars($landingUrl) ?>" target="_blank" rel="noopener">
                                            <i class="fas fa-up-right-from-square"></i> Ver landing
                                        </a>
                                    </td>

                                    <td>
                                        <span class="price-tag">$<?= number_format($precioVenta, 0, ',', '.') ?></span>
                                    </td>

                                    <td>
                                        <span class="profit-tag">$<?= number_format($utilidad, 0, ',', '.') ?></span>
                                    </td>

                                    <td>
                                        <?php if ($activo): ?>
                                            <span class="tag-estado estado-activo">Activo</span>
                                        <?php else: ?>
                                            <span class="tag-estado estado-inactivo">Inactivo</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="acciones-col">
                                        <a href="/tienda_mvc/AdminProductos/editar?id=<?= htmlspecialchars($p['id']) ?>"
                                            class="btn-detail">
                                            Editar producto
                                        </a>

                                        <a href="/tienda_mvc/AdminLanding/index?producto_id=<?= htmlspecialchars($p['id']) ?>"
                                            class="btn-primary btn-primary--soft">
                                            Editar landing
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </section>
    </main>

    <script>


        // Búsqueda simple (no rompe backend)
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('searchProductos');
            const table = document.getElementById('tablaProductos');
            if (!input || !table) return;

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const tbody = table.querySelector('tbody');

            const filter = () => {
                const q = (input.value || '').trim().toLowerCase();

                // quitar fila "no results" si existe
                const oldEmpty = document.getElementById('noProductsRow');
                if (oldEmpty) oldEmpty.remove();

                let any = false;

                rows.forEach(r => {
                    const txt = (r.innerText || '').toLowerCase();
                    const show = txt.includes(q);
                    r.style.display = show ? '' : 'none';
                    if (show) any = true;
                });

                if (!any) {
                    const tr = document.createElement('tr');
                    tr.id = 'noProductsRow';
                    tr.innerHTML = `<td colspan="7" style="padding:1rem; text-align:center; color:var(--gray-light);">
          No se encontraron productos con ese criterio.
        </td>`;
                    tbody.appendChild(tr);
                }
            };

            input.addEventListener('input', filter);
        });
    </script>
    <script src="/tienda_mvc/public/js/funciones.js"></script>
</body>

</html>