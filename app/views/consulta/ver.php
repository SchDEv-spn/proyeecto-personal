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

    <?php if ($aviso !== ''): ?>
        <div class="consulta__aviso" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?= $e($aviso) ?></div>
    <?php endif; ?>

    <?php if ($ordenes !== null): ?>
        <?php if (!$ordenes): ?>
            <div class="consulta__aviso consulta__aviso--suave" role="status">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                No encontré pedidos con ese número en los últimos <?= (int)ConsultaPedidos::DIAS_ATRAS ?> días. Revisa que esté bien escrito;
                si el pedido es de hoy, puede que todavía no esté cargado.
            </div>
        <?php else: foreach ($ordenes as $i => $o): ?>
            <article class="consulta__pedido">
                <div class="consulta__pedido-top">
                    <strong><?= $o['producto'] !== '' ? $e($o['producto']) : 'Pedido' ?><?= $o['cantidad'] !== null && $o['cantidad'] > 1 ? ' · ' . (int)$o['cantidad'] . ' unidades' : '' ?></strong>
                    <span class="status-tag status-<?= $e($clasesEstado[$o['estado_clase']] ?? $o['estado_clase']) ?>"><?= $e($o['estado']) ?></span>
                </div>
                <p class="consulta__meta">
                    Pedido del <?= $e($fecha($o['fecha'])) ?>
                    <?= $o['guia'] !== '' ? ' · Guía <b>' . $e($o['guia']) . '</b>' : ' · Sin guía todavía' ?>
                    <?= $o['transportadora'] !== '' ? ' · ' . $e($o['transportadora']) : '' ?>
                    <?= $o['municipio'] !== '' ? ' · ' . $e($o['municipio']) : '' ?>
                </p>

                <label for="consultaMsg<?= $i ?>">Mensaje para el cliente</label>
                <textarea id="consultaMsg<?= $i ?>" class="consulta__mensaje" readonly rows="7"><?= $e($o['mensaje']) ?></textarea>
                <div class="consulta__acciones">
                    <button type="button" class="btn-primary" data-copiar="consultaMsg<?= $i ?>"><i class="far fa-copy" aria-hidden="true"></i> Copiar mensaje</button>
                    <a class="btn-detail" href="<?= $e($o['whatsapp']) ?>" <?= strpos($o['whatsapp'], 'https://') === 0 ? 'target="_blank" ' : '' ?>rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i> Abrir en WhatsApp Business</a>
                </div>
            </article>
        <?php endforeach; endif; ?>
    <?php endif; ?>

    <p class="consulta__pie help">
        <?php if ($actualizado($datosAl) !== ''): ?>Datos de Dropi actualizados <?= $e($actualizado($datosAl)) ?>. <?php endif; ?>
        Si el pedido es muy reciente, puede que aún no aparezca.
    </p>
</main>

<script>
(function () {
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
