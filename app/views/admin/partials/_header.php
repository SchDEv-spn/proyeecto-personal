<?php
$usuarioNombre   = $usuarioNombre ?? 'Admin';
$pageTitle       = $pageTitle ?? "¡Hola, {$usuarioNombre}!";
$pageSubtitle    = $pageSubtitle ?? 'Revisa y gestiona los pedidos de hoy';

$showRangeFilter = $showRangeFilter ?? true;
$showSearch      = $showSearch ?? true;

$searchInputId = $searchInputId ?? 'searchPedidos';
$searchPlaceholder = $searchPlaceholder ?? 'Buscar por cliente, teléfono, ciudad, producto, estado, ID...';

$headerCtas = $headerCtas ?? [];
if (!empty($headerCta) && empty($headerCtas)) {
    $headerCtas = [$headerCta];
}
?>

<script>window.__CSRF__ = '<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>';</script>
<style>
/* === Picker de plantillas WhatsApp === */
.wa-picker-overlay {
  position: fixed; inset: 0; z-index: 10001;
  background: rgba(18,24,40,.45);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
  animation: _wa-fadein .18s ease;
}
@keyframes _wa-fadein { from { opacity:0; } to { opacity:1; } }

.wa-picker-card {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 20px 60px rgba(18,24,40,.22);
  width: 100%; max-width: 460px;
  overflow: hidden;
  animation: _wa-slidein .2s ease;
}
@keyframes _wa-slidein { from { transform:translateY(18px); opacity:0; } to { transform:none; opacity:1; } }

.wa-picker-head {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px 14px;
  border-bottom: 1px solid rgba(18,24,40,.07);
}
.wa-picker-head strong { font-size: 15px; }
.wa-picker-close {
  background: none; border: none; font-size: 20px; cursor: pointer;
  color: #9ca3af; line-height: 1; padding: 0;
}
.wa-picker-close:hover { color: #374151; }

.wa-picker-tabs {
  display: flex; gap: 4px; padding: 12px 20px 0; flex-wrap: wrap;
}
.wa-tab {
  font-size: 11px; font-weight: 700; padding: 4px 10px;
  border-radius: 20px; border: none; cursor: pointer;
  background: rgba(18,24,40,.06); color: #6b7280;
  transition: background .15s, color .15s;
}
.wa-tab.is-active         { background: #5d68ff; color: #fff; }

.wa-picker-body { padding: 14px 20px 18px; }

.wa-picker-body label {
  display: block; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .05em;
  color: #9ca3af; margin-bottom: 6px;
}
.wa-picker-body textarea {
  width: 100%; box-sizing: border-box;
  background: rgba(18,24,40,.04);
  border: 1px solid rgba(18,24,40,.1);
  border-radius: 10px; padding: 10px 13px;
  font-size: 13.5px; font-family: inherit;
  resize: vertical; min-height: 110px;
  transition: border-color .15s;
}
.wa-picker-body textarea:focus {
  outline: none; border-color: #5d68ff;
  box-shadow: 0 0 0 3px rgba(93,104,255,.12);
}

.wa-picker-foot {
  display: flex; gap: 10px; align-items: center;
  padding: 0 20px 18px;
}
.btn-wa-send {
  display: flex; align-items: center; gap: 8px;
  background: #25d366; color: #fff;
  border: none; border-radius: 12px;
  padding: 11px 22px; font-size: 14px; font-weight: 700;
  cursor: pointer; transition: opacity .2s;
  text-decoration: none;
}
.btn-wa-send:hover { opacity: .9; }
.btn-wa-send i { font-size: 16px; }

/* === Campana de notificaciones === */
.notif-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.notif-bell {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: rgba(93,104,255,.12);
  color: #5d68ff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;
  transition: background .2s, transform .15s;
  flex-shrink: 0;
}
.notif-bell:hover  { background: rgba(93,104,255,.22); }
.notif-bell:active { transform: scale(.92); }

.notif-badge {
  position: absolute;
  top: -3px; right: -3px;
  background: #ef4444;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: none;           /* JS lo muestra */
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid #fff;
  line-height: 1;
  pointer-events: none;
}

/* Panel dropdown */
.notif-dropdown {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  width: 320px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 16px 48px rgba(18,24,40,.18);
  border: 1px solid rgba(93,104,255,.13);
  z-index: 9998;
  overflow: hidden;
}

.notif-dropdown-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px 12px;
  border-bottom: 1px solid rgba(18,24,40,.07);
  font-weight: 700;
  font-size: 14px;
  color: #1e1b4b;
}

.notif-clear {
  background: none;
  border: none;
  color: #5d68ff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.notif-clear:hover { text-decoration: underline; }

.notif-list {
  list-style: none;
  margin: 0;
  padding: 0;
  max-height: 340px;
  overflow-y: auto;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(18,24,40,.05);
  cursor: pointer;
  transition: background .15s;
  position: relative;
}
.notif-item:hover        { background: rgba(93,104,255,.05); }
.notif-item:last-child   { border-bottom: none; }

.notif-item-icon {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: rgba(93,104,255,.12);
  color: #5d68ff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 14px;
}

.notif-item-body  { flex: 1; min-width: 0; }

.notif-item-name {
  display: block;
  font-size: 13px;
  font-weight: 700;
  color: #1e1b4b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notif-item-product {
  display: block;
  font-size: 12px;
  color: #4b5563;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 1px;
}

.notif-item-meta {
  display: block;
  font-size: 11px;
  color: #9ca3af;
  margin-top: 2px;
}

.notif-item-dot {
  width: 9px; height: 9px;
  border-radius: 50%;
  background: #5d68ff;
  flex-shrink: 0;
  margin-top: 5px;
}

.notif-item-empty {
  padding: 24px 16px;
  text-align: center;
  color: #9ca3af;
  font-size: 13px;
}

.notif-dropdown-foot {
  display: block;
  text-align: center;
  padding: 12px 16px;
  border-top: 1px solid rgba(18,24,40,.07);
  font-size: 13px;
  font-weight: 600;
  color: #5d68ff;
  text-decoration: none;
}
.notif-dropdown-foot:hover { background: rgba(93,104,255,.05); }

/* Animación campana */
@keyframes notif-ring {
  0%,100% { transform: rotate(0); }
  15%      { transform: rotate(18deg); }
  30%      { transform: rotate(-14deg); }
  45%      { transform: rotate(10deg); }
  60%      { transform: rotate(-7deg); }
  75%      { transform: rotate(4deg); }
}
.notif-bell--ring i { animation: notif-ring .85s ease; }

/* Tarjeta nueva inyectada */
@keyframes _new-pulse {
  0%,100% { box-shadow: 0 0 0 0   rgba(93,104,255,.6); }
  50%      { box-shadow: 0 0 0 10px rgba(93,104,255,0); }
}
.is-new-arrival {
  border: 2px solid #5d68ff !important;
  animation: _new-pulse 1.8s ease infinite;
  position: relative;
}
.is-new-arrival::before {
  content: '✨ NUEVO';
  position: absolute;
  top: -1px; left: -1px;
  background: #5d68ff;
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .07em;
  padding: 2px 10px;
  border-radius: 4px 0 6px 0;
  z-index: 1;
}
</style>

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

        <!-- Campana de notificaciones -->
        <div class="notif-wrap" id="notifWrap">
            <button class="notif-bell" id="notifBell" type="button"
                    aria-label="Notificaciones" aria-expanded="false"
                    aria-controls="notifDropdown">
                <i class="fas fa-bell"></i>
                <span class="notif-badge" id="notifBadge" aria-live="polite">0</span>
            </button>

            <div class="notif-dropdown" id="notifDropdown" aria-hidden="true" hidden>
                <div class="notif-dropdown-head">
                    <span>Notificaciones</span>
                    <button class="notif-clear" id="notifClear" type="button">Marcar leídas</button>
                </div>
                <ul class="notif-list" id="notifList" role="list">
                    <li class="notif-item-empty">Sin notificaciones nuevas.</li>
                </ul>
                <a href="/tienda_mvc/AdminPedidos/index" class="notif-dropdown-foot">
                    Ver todos los pedidos →
                </a>
            </div>
        </div>

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