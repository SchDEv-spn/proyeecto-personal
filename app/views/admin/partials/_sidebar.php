<?php
$usuarioNombre = $usuarioNombre ?? 'Admin';
$usuarioEmail  = $usuarioEmail  ?? 'admin@tuempresa.com';

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($currentPath, '/AdminProductos/') !== false) {
    $activeNav = 'productos';
} elseif (strpos($currentPath, '/AdminPedidos/') !== false) {
    $activeNav = 'pedidos';
} elseif (strpos($currentPath, '/AdminMarketing/') !== false) {
    $activeNav = 'marketing';
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
    <nav class="sidebar-nav" aria-label="Menú principal">

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

        <a href="<?= BASE_URL ?>/AdminMarketing/index" class="<?= $activeNav === 'marketing' ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Marketing IA</span>
        </a>

        <a href="<?= BASE_URL ?>/AdminPlantillasWa/index" class="<?= $activeNav === 'plantillas' ? 'active' : '' ?>">
            <i class="fab fa-whatsapp"></i>
            <span>Plantillas WA</span>
        </a>

        <a href="<?= BASE_URL ?>/AdminPerfil/index" class="<?= $activeNav === 'perfil' ? 'active' : '' ?>">
            <i class="fas fa-user-circle"></i>
            <span>Mi Perfil</span>
        </a>

        <a href="<?= BASE_URL ?>/Auth/logout" class="sidebar-nav__logout" id="logoutLink">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar sesión</span>
        </a>

    </nav>

</aside>

<script>
// Al cerrar sesión, vaciar la caché del navegador: puede contener vistas del
// panel con datos de clientes.
(function () {
    var link = document.getElementById('logoutLink');
    if (!link || !('caches' in window)) return;

    link.addEventListener('click', function (e) {
        e.preventDefault();
        var href = link.href;
        var done = false;
        var go = function () { if (!done) { done = true; window.location.href = href; } };

        setTimeout(go, 700); // no dejar al usuario esperando si algo falla
        caches.keys()
            .then(function (keys) { return Promise.all(keys.map(function (k) { return caches.delete(k); })); })
            .then(go)
            .catch(go);
    });
})();
</script>
