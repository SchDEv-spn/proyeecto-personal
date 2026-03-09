<?php
// Defaults
$usuarioNombre = $usuarioNombre ?? 'Admin';
$usuarioEmail  = $usuarioEmail  ?? 'admin@tuempresa.com';

// Detectar ruta actual automáticamente
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Resolver activeNav por URL
if (strpos($currentPath, '/AdminProductos/') !== false) {
    $activeNav = 'productos';
} elseif (strpos($currentPath, '/AdminPedidos/') !== false) {
    $activeNav = 'pedidos';
} else {
    $activeNav = '';
}
?>

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
        <a href="/tienda_mvc/AdminPedidos/index" class="<?= $activeNav === 'pedidos' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Pedidos
        </a>

        <a href="/tienda_mvc/AdminProductos/index" class="<?= $activeNav === 'productos' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i> Productos
        </a>

        <a href="/tienda_mvc/Auth/logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </nav>
</aside>