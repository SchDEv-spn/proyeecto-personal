<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/tienda_mvc/public/css/dashboard.css">
    <link rel="stylesheet" href="/tienda_mvc/public/css/adminProductos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php
    $productos = $productos ?? [];
    $success   = $success ?? '';

    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';

    // KPIs
    $totalProductos = count($productos);
    $activosCount = 0;
    $utilidadAcum = 0.0;

    foreach ($productos as $p) {
        $precioVenta     = (float)($p['precio_venta'] ?? 0);
        $precioProveedor = (float)($p['precio_proveedor'] ?? 0);
        $utilidad        = $precioVenta - $precioProveedor;

        $utilidadAcum += $utilidad;
        if (!empty($p['activo'])) $activosCount++;
    }

    $inactivosCount = $totalProductos - $activosCount;
    $utilidadProm   = $totalProductos > 0 ? ($utilidadAcum / $totalProductos) : 0.0;

    // ✅ AJUSTA ESTO a tu carpeta real de imágenes (si guardas solo el nombre del archivo)
    $UPLOADS_BASE = '/tienda_mvc/public/uploads/productos/';
    ?>

    <div class="sidebar-overlay" aria-hidden="true"></div>

    <div class="app-shell">

        <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

        <main class="material-main">
            <?php
            // ====== Header variables (para el partial) ======
            $pageTitle = 'Productos';
            $pageSubtitle = 'Gestiona los productos, sus precios y sus landings';

            $showRangeFilter = false;
            $showSearch = true;

            // ✅ mantener searchProductos
            $searchInputId = 'searchProductos';
            $searchPlaceholder = 'Buscar producto, slug, estado...';

            // ✅ CTAs del header (2 botones)
            $headerCtas = [
                [
                    'href'  => '/tienda_mvc/AdminProductos/crear',
                    'label' => 'Crear producto',
                    'class' => 'btn-primary',
                    'icon'  => 'fas fa-plus',
                ]
                
            ];

            require __DIR__ . '/../partials/_header.php';
            ?>

            <section class="material-content">

                <?php if (!empty($success)): ?>
                    <div class="admin-alert-success">
                        <i class="fas fa-circle-check"></i>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <!-- KPIs -->
                <div class="stats-grid">
                    <div class="stat-card glow-green">
                        <div class="stat-info">
                            <small>Productos Totales</small>
                            <h3><?= number_format($totalProductos, 0, ',', '.') ?></h3>
                            <span class="target">Inventario registrado</span>
                        </div>
                        <i class="fas fa-boxes-stacked stat-icon"></i>
                    </div>

                    <div class="stat-card glow-blue">
                        <div class="stat-info">
                            <small>Activos</small>
                            <h3><?= number_format($activosCount, 0, ',', '.') ?></h3>
                            <span class="target">Visibles al público</span>
                        </div>
                        <i class="fas fa-circle-check stat-icon"></i>
                    </div>

                    <div class="stat-card glow-red">
                        <div class="stat-info">
                            <small>Inactivos</small>
                            <h3><?= number_format($inactivosCount, 0, ',', '.') ?></h3>
                            <span class="target pending">Ocultos / pausados</span>
                        </div>
                        <i class="fas fa-circle-xmark stat-icon"></i>
                    </div>

                    <div class="stat-card glow-purple">
                        <div class="stat-info">
                            <small>Utilidad Promedio (unitaria)</small>
                            <h3>$<?= number_format($utilidadProm, 0, ',', '.') ?></h3>
                            <span class="target">Promedio por producto</span>
                        </div>
                        <i class="fas fa-chart-line stat-icon"></i>
                    </div>
                </div>

                <!-- Cards -->
                <?php if (empty($productos)): ?>
                    <div class="empty-state">
                        <p>No hay productos creados todavía.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Listado de productos</h3>
                        </div>

                        <div class="cards-container" id="contenedorProductos">
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

                                // ===== Imagen principal (robusto para nombre / ruta / URL)
                                $imgRaw = trim((string)($p['imagen_principal'] ?? ''));
                                $imgSrc = '';

                                if ($imgRaw !== '') {
                                    // Si ya es URL completa
                                    if (preg_match('#^https?://#i', $imgRaw)) {
                                        $imgSrc = $imgRaw;
                                    } else {
                                        // Si viene con una ruta tipo /tienda_mvc/... o /public/...
                                        if ($imgRaw[0] === '/') {
                                            $imgSrc = $imgRaw;
                                        } else {
                                            // Si viene como 'uploads/productos/x.jpg' o solo 'x.jpg'
                                            if (stripos($imgRaw, 'uploads/') !== false || stripos($imgRaw, 'public/') !== false) {
                                                $imgSrc = '/tienda_mvc/' . ltrim($imgRaw, '/');
                                            } else {
                                                // Caso típico: solo nombre de archivo
                                                $imgSrc = $UPLOADS_BASE . $imgRaw;
                                            }
                                        }
                                    }
                                }
                                ?>

                                <div class="order-card" data-producto-id="<?= htmlspecialchars($p['id'] ?? '') ?>">

                                    <div class="card-header">
                                        <div>
                                            <span class="card-label">ID Producto</span>
                                            <strong>#<?= htmlspecialchars($p['id'] ?? '') ?></strong>
                                        </div>

                                        <?php if ($activo): ?>
                                            <span class="tag-estado estado-activo">Activo</span>
                                        <?php else: ?>
                                            <span class="tag-estado estado-inactivo">Inactivo</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- ✅ FOTO -->
                                    <?php if ($imgSrc !== ''): ?>
                                        <div class="product-media">
                                            <img
                                                src="<?= htmlspecialchars($imgSrc) ?>"
                                                alt="<?= htmlspecialchars($p['nombre'] ?? 'Producto') ?>"
                                                loading="lazy"
                                                onerror="this.closest('.product-media').classList.add('product-media--broken'); this.style.display='none';">
                                            <div class="product-media__fallback" aria-hidden="true">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-media product-media--placeholder" aria-hidden="true">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-section">
                                        <span class="card-label">Producto</span>
                                        <div class="card-value">
                                            <strong><?= htmlspecialchars($p['nombre'] ?? '') ?></strong><br>
                                            <small style="color:var(--muted);">
                                                Proveedor: $<?= number_format($precioProveedor, 0, ',', '.') ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="card-section">
                                        <span class="card-label">Landing / URL</span>
                                        <div class="card-value">
                                            <?php if ($slug !== ''): ?>
                                                <small style="color:var(--muted);">
                                                    Slug: <code class="code-pill"><?= htmlspecialchars($slug) ?></code>
                                                </small><br>
                                            <?php else: ?>
                                                <small style="color:var(--muted);"><em>Sin slug definido</em></small><br>
                                            <?php endif; ?>

                                            <a class="link-landing" href="<?= htmlspecialchars($landingUrl) ?>" target="_blank" rel="noopener">
                                                <i class="fas fa-up-right-from-square"></i> Ver landing
                                            </a>
                                        </div>
                                    </div>

                                    <div class="card-section" style="display:flex; justify-content:space-between;">
                                        <div>
                                            <span class="card-label">Precio Venta</span>
                                            <div class="card-value">
                                                <span class="price-tag">$<?= number_format($precioVenta, 0, ',', '.') ?></span>
                                            </div>
                                        </div>

                                        <div style="text-align:right;">
                                            <span class="card-label">Utilidad</span>
                                            <div class="card-value">
                                                <span class="profit-tag">$<?= number_format($utilidad, 0, ',', '.') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-footer" style="justify-content:flex-end;">
                                        <div class="card-actions" style="gap:10px;">
                                            <a href="/tienda_mvc/AdminProductos/editar?id=<?= htmlspecialchars($p['id']) ?>" class="btn-detail">
                                                Editar producto
                                            </a>

                                            <a href="/tienda_mvc/AdminLanding/index?producto_id=<?= htmlspecialchars($p['id']) ?>" class="btn-primary btn-primary--soft">
                                                Editar landing
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </section>
        </main>
    </div>

    <script src="/tienda_mvc/public/js/funciones.js"></script>
</body>

</html>