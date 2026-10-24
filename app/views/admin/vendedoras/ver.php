<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($vendedora['nombre'], ENT_QUOTES, 'UTF-8') ?> · Vendedoras</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.php">
    <meta name="theme-color" content="#C9A84C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>if('serviceWorker' in navigator) navigator.serviceWorker.register('<?= BASE_URL ?>/sw.js');</script>
</head>

<body>
<?php
    $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Admin';
    $usuarioEmail  = $_SESSION['usuario_email']  ?? '';
    require __DIR__ . '/_ver_ayudas.php';
?>

<div class="sidebar-overlay" aria-hidden="true"></div>

<div class="app-shell">

    <?php require __DIR__ . '/../partials/_sidebar.php'; ?>

    <main class="material-main">
        <?php
            $pageTitle    = $titulo;
            $pageSubtitle = $subtitulo;
            $showRangeFilter = false;
            $showSearch      = false;
            $headerCtas = [['href' => BASE_URL . '/AdminVendedoras/index', 'label' => 'Vendedoras', 'class' => 'btn-detail', 'icon' => 'fa-arrow-left']];
            require __DIR__ . '/../partials/_header.php';
        ?>

        <section class="material-content vend">

            <div id="vendAvisos" class="vend-avisos" aria-live="polite"><?= alert_success($ok ?? '') ?><?= alert_error($errores ?? [], 'No se pudo') ?></div>

            <div id="vendContenido" class="vend">
                <?php require __DIR__ . '/_ver_contenido.php'; ?>
            </div>
        </section>
    </main>
</div>

<script>
(function () {
    var cont = document.getElementById('vendContenido');
    var avisos = document.getElementById('vendAvisos');

    function pesos(n) { return '$' + String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }

    // El botón de liquidar muestra el total de lo marcado.
    function sumar() {
        var form = document.getElementById('vFormLiquidar');
        var total = document.getElementById('vTotalLiquidar');
        var boton = document.getElementById('vBtnLiquidar');
        if (!form || !total) return;
        var s = 0, n = 0;
        Array.prototype.forEach.call(form.querySelectorAll('input[type="checkbox"][name="ordenes[]"]:checked'), function (c) { s += Number(c.getAttribute('data-monto')) || 0; n++; });
        total.textContent = pesos(s);
        if (boton) boton.disabled = (n === 0) || boton.getAttribute('data-bloqueado') === '1';
    }

    // Marcar todos / quitar marcas
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest ? ev.target.closest('[data-marcar]') : null;
        if (!b) return;
        var f = document.querySelector(b.getAttribute('data-marcar'));
        if (!f) return;
        Array.prototype.forEach.call(f.querySelectorAll('input[type="checkbox"][name="ordenes[]"]'), function (c) { c.checked = b.getAttribute('data-valor') === '1'; });
        sumar();
    });
    document.addEventListener('change', function (ev) { if (ev.target.closest && ev.target.closest('#vFormLiquidar')) sumar(); });

    // Confirmar antes de liquidar (fase de captura: corre antes que el envío sin recargar)
    document.addEventListener('submit', function (ev) {
        var f = ev.target.closest ? ev.target.closest('#vFormLiquidar') : null;
        if (!f) return;
        var total = document.getElementById('vTotalLiquidar');
        if (!window.confirm('¿Liquidar ' + (total ? total.textContent : '') + ' a ' + (f.getAttribute('data-nombre') || 'la vendedora') + '? Esos pedidos quedan marcados como pagados.')) ev.preventDefault();
    }, true);

    // Avisos: el de éxito es un cartel que se va solo; el de error se queda hasta la siguiente acción.
    function aviso(ok, texto) {
        if (!avisos) return;
        avisos.innerHTML = '';
        if (!texto) return;
        var d = document.createElement('div');
        if (ok) {
            d.className = 'admin-alert-success';
            d.setAttribute('role', 'status');
            d.innerHTML = '<i class="fas fa-circle-check" aria-hidden="true"></i><span></span>';
            d.querySelector('span').textContent = texto;
            avisos.appendChild(d);
            setTimeout(function () { d.classList.add('is-hiding'); setTimeout(function () { if (d.parentNode) d.parentNode.removeChild(d); }, 400); }, 3000);
        } else {
            d.className = 'admin-alert-error';
            d.setAttribute('role', 'alert');
            d.innerHTML = '<div class="admin-alert-title"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> No se pudo</div><ul><li></li></ul>';
            d.querySelector('li').textContent = texto;
            avisos.appendChild(d);
            if (d.scrollIntoView) d.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    // Todo lo que se envía dentro del contenido se hace sin recargar la página; si algo falla, se envía el formulario normal.
    document.addEventListener('submit', function (ev) {
        var f = ev.target.closest ? ev.target.closest('#vendContenido form') : null;
        if (!f || ev.defaultPrevented || !window.fetch || !window.FormData) return;
        ev.preventDefault();
        var boton = ev.submitter || f.querySelector('button[type="submit"]');
        var textoBoton = boton ? boton.innerHTML : '';
        var esRevision = /\/revisar$/.test(f.getAttribute('action') || '');
        if (boton) { boton.disabled = true; boton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Un momento…'; }
        cont.setAttribute('aria-busy', 'true');
        window.fetch(f.getAttribute('action'), {
            method: 'POST',
            body: new FormData(f),
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (j) {
            if (typeof j.html !== 'string') throw new Error('respuesta sin contenido');
            cont.innerHTML = j.html;
            sumar();
            aviso(!!j.ok, j.ok ? j.mensaje : j.error);
            var h1 = document.querySelector('.header-greeting h1'), p = document.querySelector('.header-greeting p');
            if (h1 && j.titulo) h1.textContent = j.titulo;
            if (p && j.subtitulo) p.textContent = j.subtitulo;
            if (j.titulo) document.title = j.titulo + ' · Vendedoras';
            var vista = esRevision ? document.getElementById('vendPreview') : null;
            if (vista && vista.scrollIntoView) vista.scrollIntoView({ block: 'start', behavior: 'smooth' });
            cont.removeAttribute('aria-busy');
        }).catch(function () {
            f.submit(); // sin conexión o respuesta rara: se envía el formulario normal
        });
    });

    sumar();
})();
</script>
<script src="<?= asset_url('public/js/modal-a11y.js') ?>"></script>
</body>

</html>
