/**
 * clarity-tags.js — etiquetas personalizadas y captura de errores para Clarity
 * ─────────────────────────────────────────────────────────────
 * Se carga SOLO en producción, justo después del snippet de Clarity
 * (ver el bloque de analytics al final de views/landing/index.php).
 *
 * Resuelve dos vacíos del panel:
 *   1. No se podía segmentar: todas las sesiones se veían iguales, sin
 *      distinguir producto, dispositivo real ni quién terminó comprando.
 *   2. El 11% de sesiones con error de JS no decía QUÉ error era.
 *
 * Las etiquetas aparecen en Clarity como filtros bajo "Custom tags".
 * clarity() ya existe como cola antes de que cargue el tag remoto, así que
 * se puede llamar de inmediato; aun así todo va protegido con try/catch
 * porque el navegador in-app de Facebook (87% del tráfico) es impredecible.
 */
(function () {
    'use strict';

    function tag(key, value) {
        try {
            if (typeof window.clarity !== 'function') return;
            if (value === null || value === undefined || value === '') return;
            window.clarity('set', key, String(value).slice(0, 255));
        } catch (e) { /* nunca romper la landing por una etiqueta */ }
    }

    /* ── 1. Producto y precio ─────────────────────────────────
       Permite filtrar grabaciones y mapas de calor por producto,
       en vez de ver todo el tráfico mezclado en un solo promedio. */
    tag('producto', window.landingProductName);
    if (window.landingProductPrice) tag('precio', Math.round(window.landingProductPrice));

    var slug = (location.pathname.split('/producto/')[1] || '').split('/')[0];
    tag('slug', slug ? decodeURIComponent(slug) : 'home');

    /* ── 2. Navegador real ────────────────────────────────────
       Clarity reporta el in-app de Facebook como "FacebookApp", pero no
       distingue Messenger ni Instagram. Esta etiqueta sí. */
    var ua = navigator.userAgent || '';
    var entorno = 'navegador';
    if (/FBAN|FBAV|FB_IAB/i.test(ua))      entorno = 'facebook-app';
    else if (/Instagram/i.test(ua))        entorno = 'instagram-app';
    else if (/Messenger/i.test(ua))        entorno = 'messenger-app';
    else if (/(TikTok|musical_ly)/i.test(ua)) entorno = 'tiktok-app';
    tag('entorno', entorno);

    /* ── 3. Resultado del pedido ──────────────────────────────
       landingSuccess lo inyecta la vista cuando el pedido se guardó.
       Con esto se pueden ver solo las sesiones que SÍ compraron. */
    if (window.landingSuccess) tag('pedido', 'confirmado');

    var form = document.getElementById('formPedido');
    if (form) {
        form.addEventListener('submit', function () { tag('pedido', 'enviado'); });
    }

    /* ── 4. Errores de JavaScript ─────────────────────────────
       El dato clave: convierte el 11% de sesiones rotas en un filtro
       con mensaje, archivo y línea. Solo se guarda el primer error de
       cada sesión — Clarity conserva un valor por etiqueta y el primero
       suele ser la causa raíz de los siguientes. */
    var errorRegistrado = false;

    function registrarError(mensaje, archivo, linea) {
        if (errorRegistrado) return;
        errorRegistrado = true;

        var origen = (archivo || '').split('/').pop() || 'desconocido';
        tag('js_error', (mensaje || 'error sin mensaje') + ' @ ' + origen + ':' + (linea || '?'));
        tag('js_error_entorno', entorno);
    }

    window.addEventListener('error', function (e) {
        // Fallo al cargar una imagen/script: no hay e.message, pero importa igual
        if (e.target && e.target !== window && e.target.src) {
            if (errorRegistrado) return;
            errorRegistrado = true;
            tag('js_error', 'no cargó recurso: ' + String(e.target.src).split('/').pop());
            tag('js_error_entorno', entorno);
            return;
        }
        registrarError(e.message, e.filename, e.lineno);
    }, true); // captura: los errores de recursos no burbujean

    window.addEventListener('unhandledrejection', function (e) {
        var motivo = e.reason;
        var msg = (motivo && (motivo.message || motivo)) || 'promesa rechazada';
        registrarError('promesa: ' + msg, '', '');
    });
})();
