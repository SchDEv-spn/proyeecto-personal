<?php
$usuarioNombre = $usuarioNombre ?? 'Admin';
$usuarioEmail  = $usuarioEmail  ?? 'admin@tuempresa.com';

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($currentPath, '/AdminProductos/') !== false) {
    $activeNav = 'productos';
} elseif (strpos($currentPath, '/AdminPedidos/') !== false) {
    $activeNav = 'pedidos';
} elseif (strpos($currentPath, '/AdminPlantillasWa/') !== false) {
    $activeNav = 'plantillas';
} elseif (strpos($currentPath, '/AdminPerfil/') !== false) {
    $activeNav = 'perfil';
} else {
    $activeNav = '';
}
?>

<aside class="material-sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <span class="sidebar-logo__icon"><i class="fas fa-store"></i></span>
        <span class="sidebar-logo__name">FEDORA<strong>MFB</strong></span>
    </div>

    <!-- Nav -->
    <nav class="sidebar-nav">

        <span class="sidebar-section-label">Menú</span>

        <a href="<?= BASE_URL ?>/AdminPedidos/index" class="<?= $activeNav === 'pedidos' ? 'active' : '' ?>">
            <i class="fas fa-box"></i>
            <span>Pedidos</span>
        </a>

        <a href="<?= BASE_URL ?>/AdminProductos/index" class="<?= $activeNav === 'productos' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Productos</span>
        </a>

        <span class="sidebar-section-label">Herramientas</span>

        <a href="<?= BASE_URL ?>/AdminPlantillasWa/index" class="<?= $activeNav === 'plantillas' ? 'active' : '' ?>">
            <i class="fab fa-whatsapp"></i>
            <span>Plantillas WA</span>
        </a>

        <a href="<?= BASE_URL ?>/AdminPerfil/index" class="<?= $activeNav === 'perfil' ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i>
            <span>Mi Perfil</span>
        </a>

        <a href="<?= BASE_URL ?>/Auth/logout" class="sidebar-nav__logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>

    </nav>

</aside>
