<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin - Pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/tienda_mvc/public/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.5/css/dataTables.dataTables.min.css">

</head>

<body>
    <?php
    $total_pedidos   = $total_pedidos   ?? 0;
    $total_utilidad  = $total_utilidad  ?? 0;
    $total_venta     = $total_venta     ?? 0;
    $pedidos_nuevos  = $pedidos_nuevos  ?? 0;
    $pedidos         = $pedidos         ?? [];
    $usuarioNombre   = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail    = $_SESSION['usuario_email'] ?? 'admin@tuempresa.com';
    ?>

    <!-- Sidebar -->
    <aside class="material-sidebar">
        <div class="sidebar-logo">
            <h2>FEDORA ULTIMATE</h2>
        </div>
        <div class="sidebar-user">
            <img src="/tienda_mvc/public/img/admi/1.jpg?user=<?= substr($usuarioNombre, 0, 2) ?>" alt="User">
            <div>
                <h4><?= htmlspecialchars($usuarioNombre) ?></h4>
                <small><?= htmlspecialchars($usuarioEmail) ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="/tienda_mvc/AdminPedidos/index" class="active">
                <i class="fas fa-box"></i> Pedidos
            </a>
            <a href="/tienda_mvc/AdminProductos/index">
                <i class="fas fa-shopping-bag"></i> Productos
            </a>
            <a href="/tienda_mvc/Auth/logout">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="material-main">
        <header class="material-header">
            <div class="header-greeting">
                <h3>¡Hola, <?= htmlspecialchars($usuarioNombre) ?>!</h3>
                <p>Revisa y gestiona los pedidos de hoy</p>
            </div>
            <div class="header-actions">
                <button class="btn-menu" id="btnMenu" aria-label="Abrir menú">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input id="searchPedidos" type="text" placeholder="Buscar por cliente, teléfono, ciudad, producto, estado, ID...">
                </div>

            </div>

        </header>

        <section class="material-content">
            <!-- Tarjetas de resumen con glow -->
            <div class="stats-grid">
                <div class="stat-card glow-green">
                    <div class="stat-info">
                        <small>Pedidos Totales</small>
                        <h3><?= number_format($total_pedidos, 0, ',', '.') ?></h3>
                        <span class="target">100% objetivo alcanzado</span>
                    </div>
                    <i class="fas fa-clipboard-list stat-icon"></i>
                </div>

                <div class="stat-card glow-red">
                    <div class="stat-info">
                        <small>Pedidos Nuevos</small>
                        <h3><?= number_format($pedidos_nuevos, 0, ',', '.') ?></h3>
                        <span class="target pending">Pendientes de contacto</span>
                    </div>
                    <i class="fas fa-bell stat-icon"></i>
                </div>

                <div class="stat-card glow-purple">
                    <div class="stat-info">
                        <small>Ventas Totales</small>
                        <h3>$<?= number_format($total_venta, 0, ',', '.') ?></h3>
                        <span class="target">87% objetivo alcanzado</span>
                    </div>
                    <i class="fas fa-dollar-sign stat-icon"></i>
                </div>

                <div class="stat-card glow-blue">
                    <div class="stat-info">
                        <small>Utilidad Acumulada</small>
                        <h3>$<?= number_format($total_utilidad, 0, ',', '.') ?></h3>
                        <span class="target">Margen bruto excelente</span>
                    </div>
                    <i class="fas fa-chart-line stat-icon"></i>
                </div>
            </div>

            <!-- Tabla de pedidos -->
            <?php if (empty($pedidos)): ?>
                <div class="empty-state">
                    <p>No hay pedidos registrados todavía.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <h3>Pedidos Recientes</h3>
                    </div>

                    <table class="material-table" id="tablaPedidos">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Ubicación</th>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Utilidad</th>
                                <th>Estado</th>
                                <th>Contacto</th>
                                <th>Acción</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($pedidos as $p): ?>
                                <?php
                                $telRaw    = $p['telefono'] ?? '';
                                $telLimpio = preg_replace('/\D+/', '', $telRaw);
                                $waUrl     = '';

                                if ($telLimpio !== '') {
                                    if (strpos($telLimpio, '00') === 0) $telLimpio = substr($telLimpio, 2);
                                    if (strpos($telLimpio, '57') !== 0) {
                                        if (strlen($telLimpio) === 11 && $telLimpio[0] === '0') $telLimpio = substr($telLimpio, 1);
                                        $telLimpio = '57' . $telLimpio;
                                    }
                                    $waUrl = "https://wa.me/" . $telLimpio . "?text=" . urlencode(
                                        "Hola " . ($p['nombre'] ?? '') . ", te escribimos sobre tu pedido de " . ($p['producto_nombre'] ?? '') . "."
                                    );
                                }

                                $estadoActual = $p['estado'] ?? 'nuevo';
                                $estadosPosibles = ['nuevo', 'contactado', 'confirmado', 'enviado', 'entregado', 'cancelado'];
                                ?>

                                <tr data-pedido-id="<?= htmlspecialchars($p['id'] ?? '') ?>">

                                    <td data-label="ID"><?= htmlspecialchars($p['id'] ?? '') ?></td>

                                    <td data-label="Fecha"><?= htmlspecialchars($p['created_at'] ?? '') ?></td>

                                    <td data-label="Cliente">
                                        <strong><?= htmlspecialchars(($p['nombre'] ?? '') . ' ' . ($p['apellidos'] ?? '')) ?></strong><br>
                                        <small><?= htmlspecialchars($p['telefono'] ?? '') ?></small>
                                    </td>

                                    <td data-label="Ubicación">
                                        <?= htmlspecialchars($p['municipio'] ?? '') ?><br>
                                        <small><?= htmlspecialchars($p['departamento'] ?? '') ?></small>
                                    </td>

                                    <td data-label="Producto"><?= htmlspecialchars($p['producto_nombre'] ?? '') ?></td>

                                    <td data-label="Precio">
                                        $<?= number_format((float)($p['precio_venta'] ?? 0), 0, ',', '.') ?>
                                    </td>

                                    <td data-label="Utilidad">
                                        <span class="profit-tag">
                                            $<?= number_format((float)($p['utilidad'] ?? 0), 0, ',', '.') ?>
                                        </span>
                                    </td>

                                    <td data-label="Estado">
                                        <span class="status-tag status-<?= htmlspecialchars($estadoActual) ?>">
                                            <?= ucfirst(htmlspecialchars($estadoActual)) ?>
                                        </span>

                                        <form action="/tienda_mvc/AdminPedidos/cambiarEstado" method="POST" class="status-form">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                            <select name="estado">
                                                <?php foreach ($estadosPosibles as $estado): ?>
                                                    <option value="<?= htmlspecialchars($estado) ?>" <?= $estadoActual === $estado ? 'selected' : '' ?>>
                                                        <?= ucfirst(htmlspecialchars($estado)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit">Guardar</button>
                                        </form>
                                    </td>

                                    <td data-label="Contacto">
                                        <?php if ($waUrl): ?>
                                            <a href="<?= htmlspecialchars($waUrl) ?>" target="_blank" class="btn-whatsapp" rel="noopener">
                                                <i class="fab fa-whatsapp"></i> WhatsApp
                                            </a>
                                        <?php else: ?>
                                            <small>Sin teléfono</small>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Acción">
                                        <a
                                            href="/tienda_mvc/AdminPedidos/detalle?id=<?= htmlspecialchars($p['id'] ?? '') ?>"
                                            class="btn-detail js-ver-detalle"
                                            data-id="<?= htmlspecialchars($p['id'] ?? '') ?>">
                                            Ver detalle
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
    <!-- Modal Detalle Pedido -->
    <div class="modal-overlay" id="pedidoModal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="pedidoModalTitle">
            <button class="modal-close" type="button" id="pedidoModalClose" aria-label="Cerrar">&times;</button>

            <div class="modal-body" id="pedidoModalBody">
                <div class="modal-loading">
                    <div class="modal-spinner"></div>
                    <p>Cargando detalle...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="/tienda_mvc/public/js/funciones.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.5/js/dataTables.min.js"></script>

</body>

</html>