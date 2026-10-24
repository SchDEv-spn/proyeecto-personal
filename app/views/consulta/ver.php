<?php
/**
 * Consulta pública de pedidos para las vendedoras (ver ConsultaController). Pensada para el celular:
 * un campo, un botón, y por cada pedido su estado con un mensaje listo para copiar o abrir en WhatsApp.
 * No muestra nombre, dirección ni precio del cliente.
 */
$e = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$fecha = static function (?string $f) use ($meses): string {
    $t = $f ? strtotime($f) : 0;
    return $t ? date('j', $t) . ' ' . $meses[(int)date('n', $t) - 1] : '';
};
$actualizado = static function (?string $f): string {
    $t = $f ? strtotime($f) : 0;
    if (!$t) return '';
    $dias = (int)floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', $t))) / 86400);
    $hora = date('g:i a', $t);
    return $dias <= 0 ? 'hoy a las ' . $hora : ($dias === 1 ? 'ayer a las ' . $hora : 'el ' . date('d/m/Y', $t) . ' a las ' . $hora);
};
$clasesEstado = ['devolucion' => 'cancelado', 'otro' => 'contactado'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Consulta de pedidos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#1A3D2E">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?= asset_url('public/css/admin-unified.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="consulta-body">
<main class="consulta">

    <header class="consulta__cabecera">
        <h1><i class="fas fa-magnifying-glass" aria-hidden="true"></i> Consulta de pedidos</h1>
        <p>Escribe el teléfono del cliente y mira en qué va su pedido. Abajo sale un mensaje listo para mandárselo.</p>
    </header>

    <form method="post" action="<?= BASE_URL ?>/Consulta/ver/<?= $e($clave) ?>" class="consulta__form" autocomplete="off">
        <label for="consultaTel">Teléfono del cliente</label>
        <div class="consulta__campo">
            <input id="consultaTel" name="telefono" type="tel" inputmode="numeric" placeholder="3001234567" maxlength="30" required
                   value="<?= $e($telefono) ?>" aria-describedby="consultaAyuda" autofocus>
            <button type="submit" class="btn-primary"><i class="fas fa-search" aria-hidden="true"></i> Consultar</button>
        </div>
        <small id="consultaAyuda" class="help">El número completo, con los 10 dígitos.</small>
    </form>

    <div id="consultaResultados" class="consulta__resultados" aria-live="polite">
        <?php require __DIR__ . '/_resultados.php'; ?>
    </div>

    <p class="consulta__pie help">
        <?php if ($actualizado($datosAl) !== ''): ?>Datos de Dropi actualizados <?= $e($actualizado($datosAl)) ?>. <?php endif; ?>
        Si el pedido es muy reciente, puede que aún no aparezca.
    </p>
</main>

<script>
(function () {
    // Consultar sin recargar: el resultado se cambia en su sitio y el campo queda listo para otro número.
    var form = document.querySelector('.consulta__form');
    var caja = document.getElementById('consultaResultados');
    if (form && caja && window.fetch && window.FormData) {
        form.addEventListener('submit', function (ev) {
            if (ev.defaultPrevented) return;
            ev.preventDefault();
            var boton = form.querySelector('button[type="submit"]');
            var campo = form.querySelector('input[name="telefono"]');
            var textoBoton = boton ? boton.innerHTML : '';
            if (boton) { boton.disabled = true; boton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Consultando…'; }
            caja.setAttribute('aria-busy', 'true');
            window.fetch(form.getAttribute('action'), {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch', 'Accept': 'application/json' }
            }).then(function (r) {
                // 429 (muchas consultas) también trae su aviso en el JSON
                if (!r.ok && r.status !== 429) throw new Error('HTTP ' + r.status);
                return r.json();
            }).then(function (j) {
                if (typeof j.html !== 'string') throw new Error('respuesta sin resultado');
                caja.innerHTML = j.html;
                if (campo) { campo.focus(); campo.select(); }
                if (caja.scrollIntoView) caja.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }).catch(function () {
                form.submit(); // sin conexión o respuesta rara: se envía el formulario normal
            }).then(function () {
                caja.removeAttribute('aria-busy');
                if (boton) { boton.disabled = false; boton.innerHTML = textoBoton; }
            });
        });
    }

    document.addEventListener('click', function (ev) {
        var b = ev.target.closest ? ev.target.closest('[data-copiar]') : null;
        if (!b) return;
        var ta = document.getElementById(b.getAttribute('data-copiar'));
        if (!ta) return;
        var original = b.innerHTML;
        var listo = function () {
            b.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i> ¡Copiado!';
            setTimeout(function () { b.innerHTML = original; }, 2000);
        };
        var alterno = function () {
            ta.removeAttribute('readonly');
            ta.select();
            try { ta.setSelectionRange(0, 99999); document.execCommand('copy'); listo(); } catch (x) { /* queda seleccionado para copiar a mano */ }
            ta.setAttribute('readonly', '');
        };
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(ta.value).then(listo, alterno);
        else alterno();
    });
})();
</script>
</body>

</html>
