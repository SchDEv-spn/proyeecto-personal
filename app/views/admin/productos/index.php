<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin-unified.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/dataTables.dataTables.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
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
    $UPLOADS_BASE = BASE_URL . '/public/uploads/productos/';
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
                    'href'  => BASE_URL . '/AdminProductos/crear',
                    'label' => 'Crear producto',
                    'class' => 'btn-primary',
                    'icon'  => 'fas fa-plus',
                ],
                [
                    'href'  => BASE_URL . '/AdminProductos/importarDropi',
                    'label' => 'Importar de Dropi',
                    'class' => 'btn-detail',
                    'icon'  => 'fas fa-cloud-arrow-down',
                ],
            ];

            require __DIR__ . '/../partials/_header.php';
            ?>

            <section class="material-content">

                <?= alert_success($success) ?>

                <!-- KPIs -->
                <div class="stats-grid stats-grid--4col">
                    <div class="stat-card glow-green">
                        <div class="stat-info">
                            <small>Productos Totales</small>
                            <h2><?= number_format($totalProductos, 0, ',', '.') ?></h2>
                            <span class="target">Inventario registrado</span>
                        </div>
                        <i class="fas fa-boxes-stacked stat-icon"></i>
                    </div>

                    <div class="stat-card glow-blue">
                        <div class="stat-info">
                            <small>Activos</small>
                            <h2><?= number_format($activosCount, 0, ',', '.') ?></h2>
                            <span class="target">Visibles al público</span>
                        </div>
                        <i class="fas fa-circle-check stat-icon"></i>
                    </div>

                    <div class="stat-card glow-red">
                        <div class="stat-info">
                            <small>Inactivos</small>
                            <h2><?= number_format($inactivosCount, 0, ',', '.') ?></h2>
                            <span class="target pending">Ocultos / pausados</span>
                        </div>
                        <i class="fas fa-circle-xmark stat-icon"></i>
                    </div>

                    <div class="stat-card glow-purple">
                        <div class="stat-info">
                            <small>Utilidad Promedio (unitaria)</small>
                            <h2>$<?= number_format($utilidadProm, 0, ',', '.') ?></h2>
                            <span class="target">Promedio por producto</span>
                        </div>
                        <i class="fas fa-chart-line stat-icon"></i>
                    </div>
                </div>

                <!-- Tabla productos -->
                <?php if (empty($productos)): ?>
                    <div class="empty-state">
                        <p>No hay productos creados todavía.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h2>Listado de productos</h2>
                        </div>
                        <div class="dt-table-wrap">
                            <table id="tablaProductos" class="pedidos-dt" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Imagen</th>
                                        <th>Producto</th>
                                        <th>Precio venta</th>
                                        <th>Utilidad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($productos as $p):
                                    $precioVenta     = (float)($p['precio_venta'] ?? 0);
                                    $precioProveedor = (float)($p['precio_proveedor'] ?? 0);
                                    $utilidad        = $precioVenta - $precioProveedor;
                                    $slug            = $p['slug'] ?? '';
                                    $activo          = !empty($p['activo']);
                                    $landingUrl      = $slug !== ''
                                        ? BASE_URL . '/producto/' . urlencode($slug)
                                        : BASE_URL . '/Landing/index?producto_id=' . urlencode($p['id']);
                                    $landingUrlAbs   = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . $landingUrl;

                                    $nombreConfirm = htmlspecialchars(addslashes($p['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');

                                    $imgRaw = trim((string)($p['imagen_principal'] ?? ''));
                                    $imgSrc = '';
                                    if ($imgRaw !== '') {
                                        if (preg_match('#^https?://#i', $imgRaw)) {
                                            $imgSrc = $imgRaw;
                                        } elseif ($imgRaw[0] === '/') {
                                            $imgSrc = $imgRaw;
                                        } elseif (stripos($imgRaw, 'uploads/') !== false || stripos($imgRaw, 'public/') !== false) {
                                            $imgSrc = BASE_URL . '/' . ltrim($imgRaw, '/');
                                        } else {
                                            $imgSrc = $UPLOADS_BASE . $imgRaw;
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($imgSrc !== ''): ?>
                                                <img src="<?= htmlspecialchars($imgSrc) ?>"
                                                     alt="<?= htmlspecialchars($p['nombre'] ?? '') ?>"
                                                     loading="lazy"
                                                     class="prod-thumb"
                                                     onerror="this.replaceWith(document.createElement('span'))">
                                            <?php else: ?>
                                                <span class="prod-thumb prod-thumb--empty"><i class="fas fa-image"></i></span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <strong><?= htmlspecialchars($p['nombre'] ?? '') ?></strong>
                                            <?php if ($slug !== ''): ?>
                                                <br><code class="code-pill"><?= htmlspecialchars($slug) ?></code>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="price-tag">$<?= number_format($precioVenta, 0, ',', '.') ?></span>
                                            <br><small class="dt-cell-sub">Prov: $<?= number_format($precioProveedor, 0, ',', '.') ?></small>
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

                                        <td>
                                            <div class="dt-actions">
                                                <button type="button"
                                                   class="btn-detail btn-detail--sm js-copy"
                                                   data-copy="<?= htmlspecialchars($landingUrlAbs) ?>"
                                                   title="Copiar enlace para anuncios (ej. Facebook Ads)">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <a href="<?= htmlspecialchars($landingUrl) ?>"
                                                   target="_blank" rel="noopener"
                                                   class="btn-detail btn-detail--sm"
                                                   title="Ver landing">
                                                    <i class="fas fa-up-right-from-square"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/AdminProductos/editar?id=<?= htmlspecialchars($p['id']) ?>"
                                                   class="btn-detail btn-detail--sm"
                                                   title="Editar producto">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/AdminLanding/index?producto_id=<?= htmlspecialchars($p['id']) ?>"
                                                   class="btn-save-status btn-save-status--sm"
                                                   title="Editar landing">
                                                    <i class="fas fa-layer-group"></i>
                                                </a>
                                                <form method="POST"
                                                      action="<?= BASE_URL ?>/AdminProductos/eliminar"
                                                      style="display:inline"
                                                      onsubmit="return confirm('¿Eliminar &quot;<?= $nombreConfirm ?>&quot;? Esto borra también su landing. Esta acción no se puede deshacer.');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                                                    <button type="submit"
                                                            class="btn-detail btn-detail--sm"
                                                            style="background:var(--red)"
                                                            title="Eliminar producto (borra también su landing)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            </section>
        </main>
    </div>

    <!-- jQuery (requerido por DataTables CDN) -->
    <script src="<?= BASE_URL ?>/public/vendor/jquery-3.7.1.slim.min.js"></script>
    <!-- DataTables -->
    <script src="<?= BASE_URL ?>/public/vendor/dataTables.min.js"></script>
    <script src="<?= BASE_URL ?>/public/js/modal-a11y.js"></script>
    <script src="<?= BASE_URL ?>/public/js/funciones.js"></script>
    <script>
    (function () {
        var table = new DataTable('#tablaProductos', {
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [0, 5] }
            ],
            language: {
                search: '', searchPlaceholder: 'Buscar…',
                info: '_TOTAL_ productos', infoEmpty: 'Sin productos',
                infoFiltered: '(filtrado de _MAX_)',
                zeroRecords: 'Sin resultados.',
                emptyTable: 'Sin productos registrados.',
                paginate: { first:'«', last:'»', next:'›', previous:'‹' }
            },
            dom: 't<"dt-bottom"ip>'
        });

        var searchInput = document.getElementById('searchProductos');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                table.search(this.value).draw();
            });
        }
    })();
    </script>
</body>

</html>