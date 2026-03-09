<?php
$usuarioNombre   = $usuarioNombre ?? 'Admin';
$pageTitle       = $pageTitle ?? "¡Hola, {$usuarioNombre}!";
$pageSubtitle    = $pageSubtitle ?? 'Revisa y gestiona los pedidos de hoy';

$showRangeFilter = $showRangeFilter ?? true;
$showSearch      = $showSearch ?? true;

// ✅ NUEVO: id configurable del input
$searchInputId = $searchInputId ?? 'searchPedidos';

// Placeholder buscador
$searchPlaceholder = $searchPlaceholder ?? 'Buscar por cliente, teléfono, ciudad, producto, estado, ID...';

// ✅ NUEVO: CTAs (múltiples). Compatibilidad con $headerCta (uno solo)
$headerCtas = $headerCtas ?? [];
if (!empty($headerCta) && empty($headerCtas)) {
    $headerCtas = [$headerCta];
}
?>

<header class="material-header">
    <div class="header-greeting header-greeting--with-menu">
        <button class="btn-menu" id="btnMenu" aria-label="Abrir menú" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>

        <div>
            <h3><?= htmlspecialchars($pageTitle) ?></h3>
            <p><?= htmlspecialchars($pageSubtitle) ?></p>
        </div>
    </div>

    <div class="header-actions">

        <?php if ($showRangeFilter): ?>
            <div class="range-filter" data-range-filter>
                <button class="range-btn" id="rangeBtn" type="button" aria-expanded="false">
                    <i class="fa-regular fa-calendar"></i>
                    <span class="range-btn__label" id="rangeLabel">Este mes</span>
                    <i class="fa-solid fa-chevron-down range-btn__chev"></i>
                </button>

                <div class="range-menu" id="rangeMenu" role="menu" hidden>
                    <button type="button" class="range-item" data-range="today" role="menuitem">Hoy</button>
                    <button type="button" class="range-item" data-range="yesterday" role="menuitem">Ayer</button>
                    <button type="button" class="range-item" data-range="week" role="menuitem">Esta semana</button>
                    <button type="button" class="range-item is-active" data-range="month" role="menuitem">Este mes</button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($showSearch): ?>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input
                    id="<?= htmlspecialchars($searchInputId) ?>"
                    type="text"
                    placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
                >
            </div>
        <?php endif; ?>

        <?php if (!empty($headerCtas)): ?>
            <?php foreach ($headerCtas as $cta): ?>
                <?php
                $href  = $cta['href']  ?? '#';
                $label = $cta['label'] ?? '';
                $class = $cta['class'] ?? 'btn-detail';
                $icon  = $cta['icon']  ?? '';
                ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= htmlspecialchars($class) ?>">
                    <?php if ($icon): ?>
                        <i class="<?= htmlspecialchars($icon) ?>"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</header>