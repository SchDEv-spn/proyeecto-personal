/**
 * landing-track.js — analítica propia de la landing
 * ─────────────────────────────────────────────────────────────
 * Responde la pregunta que Clarity no puede responder con histórico:
 * cuánta gente entró y en qué punto exacto se cayó la intención de compra.
 * Clarity sigue puesto para grabaciones y mapas de calor; esto guarda el
 * embudo en la base de datos propia (ver LandingAnalytics.php).
 *
 * Cómo manda los datos: acumula eventos en una cola y los envía en lote con
 * navigator.sendBeacon. El 87% del tráfico entra por el webview de Facebook,
 * que mata la página sin avisar al volver atrás — sendBeacon es lo único que
 * sobrevive a eso, y por lote en vez de por evento para no gastar la
 * conexión móvil en una petición por scroll.
 *
 * Nunca debe romper la landing: todo va dentro de try/catch y cualquier
 * fallo termina la analítica en silencio.
 */
(function () {
    'use strict';

    var ENDPOINT   = (window.landingTrackUrl || '');
    var INTERVALO  = 8000;   // ms entre envíos automáticos
    var MAX_COLA   = 30;     // el servidor descarta lo que pase de aquí

    if (!ENDPOINT) return;

    // ── Identificador de sesión ───────────────────────────────
    // sessionStorage y no cookie: no hace falta banner de consentimiento,
    // no cruza pestañas ni identifica a la persona, solo agrupa una visita.
    // Si el navegador lo bloquea (modo privado del webview) se queda en
    // memoria: la visita se cuenta igual mientras la pestaña viva.
    var sid = (function () {
        var generado = '';
        try {
            var buf = new Uint8Array(16);
            (window.crypto || window.msCrypto).getRandomValues(buf);
            for (var i = 0; i < buf.length; i++) {
                generado += ('0' + buf[i].toString(16)).slice(-2);
            }
        } catch (e) {
            generado = (Date.now().toString(16) + Math.random().toString(16).slice(2))
                .replace(/[^a-f0-9]/g, '0').slice(0, 32);
        }
        while (generado.length < 32) generado += '0';

        try {
            var guardado = sessionStorage.getItem('lt_sid');
            if (guardado && /^[a-f0-9]{32}$/.test(guardado)) return guardado;
            sessionStorage.setItem('lt_sid', generado);
        } catch (e) { /* almacenamiento bloqueado: sid solo en memoria */ }

        return generado;
    })();

    // ── Contexto de la visita ─────────────────────────────────
    var ua = navigator.userAgent || '';

    function navegadorReal() {
        // Los in-app browsers se comportan distinto (vídeo, teclado, scroll)
        // y aquí es donde se ve si uno de ellos convierte peor que el resto.
        if (/FBAN|FBAV|FB_IAB/i.test(ua))         return 'facebook-app';
        if (/Instagram/i.test(ua))                return 'instagram-app';
        if (/Messenger/i.test(ua))                return 'messenger-app';
        if (/(TikTok|musical_ly)/i.test(ua))      return 'tiktok-app';
        return 'navegador';
    }

    function fuente() {
        try {
            var params = new URLSearchParams(location.search);
            var utm = params.get('utm_source') || params.get('fbclid') && 'facebook-ads';
            if (utm) return String(utm).slice(0, 60);

            if (!document.referrer) return 'directo';
            var host = new URL(document.referrer).hostname.replace(/^www\./, '');
            return host === location.hostname ? 'interno' : host.slice(0, 60);
        } catch (e) {
            return 'directo';
        }
    }

    function campana() {
        try {
            return (new URLSearchParams(location.search).get('utm_campaign') || '').slice(0, 120);
        } catch (e) { return ''; }
    }

    var contexto = {
        sid:  sid,
        prod: window.landingProductId || 0,
        slug: (location.pathname.split('/producto/')[1] || 'home').split('/')[0],
        nav:  navegadorReal(),
        src:  fuente(),
        camp: campana()
    };

    // ── Cola y envío ──────────────────────────────────────────
    var cola        = [];
    var vistos      = {};          // hitos ya registrados (no repetir)
    var scrollMax   = 0;
    var inicio      = Date.now();
    var enviados    = 0;
    var agotado     = false;       // el servidor corta a los 80 eventos

    function encolar(tipo, valor, urgente) {
        if (agotado) return;

        cola.push(valor ? [tipo, valor] : [tipo]);
        if (cola.length >= MAX_COLA || urgente) enviar();
    }

    /** Un hito solo cuenta la primera vez (scroll al 50%, ver una sección…). */
    function hito(clave, tipo, valor, urgente) {
        if (vistos[clave]) return;
        vistos[clave] = true;
        encolar(tipo, valor, urgente);
    }

    function enviar() {
        if (!cola.length) return;

        var lote = {
            sid:  contexto.sid,
            prod: contexto.prod,
            slug: contexto.slug,
            nav:  contexto.nav,
            src:  contexto.src,
            camp: contexto.camp,
            sc:   scrollMax,
            dur:  Math.min(65535, Math.round((Date.now() - inicio) / 1000)),
            ev:   cola.splice(0, MAX_COLA)
        };

        enviados += lote.ev.length;
        if (enviados >= 80) agotado = true;

        try {
            var cuerpo = JSON.stringify(lote);

            // text/plain evita el preflight CORS y es lo que sendBeacon manda
            // sin pedir permiso; el servidor lee el cuerpo crudo igual.
            if (navigator.sendBeacon) {
                navigator.sendBeacon(ENDPOINT, new Blob([cuerpo], { type: 'text/plain' }));
            } else {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', ENDPOINT, true);
                xhr.setRequestHeader('Content-Type', 'text/plain');
                xhr.send(cuerpo);
            }
        } catch (e) { /* la analítica no rompe la landing */ }
    }

    // ── 1. Visita ─────────────────────────────────────────────
    encolar('vista');

    // ── 2. Interés: leyó de verdad ────────────────────────────
    // Medio scroll o 25 segundos dentro. Separa al que entró y rebotó del
    // que se quedó: sin este paso, "entró" y "tocó comprar" salen pegados
    // y no se ve dónde empieza la fuga real.
    setTimeout(function () { hito('interes', 'interes'); }, 25000);

    function alturaScroll() {
        var doc = document.documentElement;
        var total = Math.max(doc.scrollHeight, document.body.scrollHeight) - window.innerHeight;
        if (total <= 0) return 100;
        return Math.max(0, Math.min(100, Math.round((window.pageYOffset / total) * 100)));
    }

    var scrollPendiente = false;
    window.addEventListener('scroll', function () {
        if (scrollPendiente) return;
        scrollPendiente = true;

        // Un frame de margen: el scroll dispara decenas de veces por segundo
        // y en un móvil de gama baja recalcular alturas ahí se nota.
        requestAnimationFrame(function () {
            scrollPendiente = false;
            var pct = alturaScroll();
            if (pct > scrollMax) scrollMax = pct;
            if (scrollMax >= 50) hito('interes', 'interes');
        });
    }, { passive: true });

    // ── 3. Hasta qué sección llegó ────────────────────────────
    // El nombre sale de la clase de la sección, no de un data-attribute:
    // la vista tiene 2.200 líneas y añadir marcas a mano a cada bloque se
    // desincronizaría al primer cambio de orden de secciones.
    (function observarSecciones() {
        if (!window.IntersectionObserver) return;

        var secciones = document.querySelectorAll('section');
        if (!secciones.length) return;

        function nombre(el, idx) {
            var clase = (el.className || '').split(/\s+/).filter(function (c) {
                return /-section$/.test(c);
            })[0];
            var base = clase || el.id || ('bloque-' + idx);
            return ('0' + idx).slice(-2) + '-' + base.replace(/-section$/, '').slice(0, 40);
        }

        var observer = new IntersectionObserver(function (entradas) {
            entradas.forEach(function (entrada) {
                if (!entrada.isIntersecting) return;
                var etiqueta = entrada.target.getAttribute('data-track-nombre');
                hito('sec:' + etiqueta, 'seccion', etiqueta);
                observer.unobserve(entrada.target);
            });
        }, { threshold: 0.4 });

        Array.prototype.forEach.call(secciones, function (el, idx) {
            if (idx > 40) return;
            el.setAttribute('data-track-nombre', nombre(el, idx + 1));
            observer.observe(el);
        });
    })();

    // ── 4. Tocó un botón de compra ────────────────────────────
    // Urgente: es el momento de mayor intención y el que más se pierde si
    // el visitante cierra la pestaña justo después.
    document.addEventListener('click', function (e) {
        var cta = e.target.closest && e.target.closest('a[href="#form-pedido"], .btn-primary');
        if (!cta) return;
        if (cta.closest('#formPedido')) return;  // el submit ya es el paso 6

        var origen = cta.id || (cta.className || '').split(/\s+/)[1] || 'cta';
        hito('cta', 'cta', String(origen).slice(0, 40), true);
    }, true);

    // ── 5. Llegó al formulario y empezó a llenarlo ────────────
    (function observarFormulario() {
        var seccion = document.getElementById('form-pedido');
        var form    = document.getElementById('formPedido');
        if (!seccion || !form) return;

        if (window.IntersectionObserver) {
            var obs = new IntersectionObserver(function (entradas) {
                if (!entradas[0].isIntersecting) return;
                hito('form', 'formulario');
                obs.disconnect();
            }, { threshold: 0.2 });
            obs.observe(seccion);
        }

        // El servidor necesita atar el pedido con esta sesión; sin esto el
        // último paso del embudo (pedido confirmado) queda siempre en cero.
        try {
            var oculto = document.createElement('input');
            oculto.type  = 'hidden';
            oculto.name  = 'track_sid';
            oculto.value = sid;
            form.appendChild(oculto);
        } catch (e) { /* formulario intacto si algo falla */ }

        // Atribución a Facebook: sin esto ningún pedido se puede unir a un
        // anuncio ni reenviar a la Conversions API (ver AUDITORIA.md C3).
        // fbclid viene de la URL del clic; _fbp/_fbc los deja la cookie del
        // propio Pixel si ya cargó (puede no estar lista en el primer
        // pageview — por eso fbc también se reconstruye a mano desde fbclid).
        try {
            var leerCookie = function (nombre) {
                var m = document.cookie.match(new RegExp('(?:^|; )' + nombre + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : '';
            };
            var fbclid = new URLSearchParams(location.search).get('fbclid') || '';
            var fbp = leerCookie('_fbp');
            var fbc = leerCookie('_fbc') || (fbclid ? 'fb.1.' + Date.now() + '.' + fbclid : '');

            [['fbclid', fbclid], ['fbp', fbp], ['fbc', fbc]].forEach(function (par) {
                if (!par[1]) return;
                var campoOculto = document.createElement('input');
                campoOculto.type  = 'hidden';
                campoOculto.name  = par[0];
                campoOculto.value = par[1];
                form.appendChild(campoOculto);
            });
        } catch (e) { /* sin atribución, el pedido se guarda igual */ }

        // Orden de aparición de los campos = orden en que se abandonan.
        // Con eso la tabla del panel se lee de arriba abajo como el propio
        // formulario y el campo donde se corta la fila es el problema.
        var campos = form.querySelectorAll('input:not([type=hidden]), select, textarea');
        Array.prototype.forEach.call(campos, function (campo, idx) {
            if (!campo.name) return;
            var etiqueta = ('0' + (idx + 1)).slice(-2) + '-' + campo.name.replace(/\[\]$/, '').slice(0, 28);

            campo.addEventListener('change', function () {
                if (!String(campo.value || '').trim()) return;
                hito('campo:' + etiqueta, 'campo', etiqueta);
            });
        });

        // Los colores se eligen con botones, no con el select visible: sin
        // esto un pedido con color se vería como formulario nunca tocado.
        //
        // La etiqueta se saca del propio select en vez de escribirla a mano:
        // era '04-color' fija y al reordenar el formulario — el color pasó a
        // ser el primer campo — dejó de coincidir con la numeración del resto
        // y la tabla del panel se leía desordenada. Ahora sigue al DOM sola.
        var etiquetaColor = (function () {
            var sel = form.querySelector('select[name="color_item[]"]');
            if (!sel) return '01-color';
            var idx = Array.prototype.indexOf.call(campos, sel);
            return ('0' + (idx + 1)).slice(-2) + '-color';
        })();
        form.addEventListener('click', function (e) {
            var pill = e.target.closest && e.target.closest('.color-pill');
            if (pill) hito('campo:color', 'campo', etiquetaColor);
        });

        form.addEventListener('submit', function () {
            encolar('envio', '', true);
        });
    })();

    // ── 6. Errores de JS ──────────────────────────────────────
    // Mismo dato que la etiqueta js_error de Clarity, pero aquí queda
    // cruzado con el embudo: se puede ver si las sesiones con error
    // convierten peor.
    // Ruido del navegador anfitrión, no de la landing: el webview de
    // Facebook/TikTok inyecta sus propios scripts y su bridge nativo lanza
    // "Java object is gone" cuando el sistema recolecta el objeto. No es un
    // bug nuestro y no debe marcar la sesión como "con error de JS".
    // "Script error." es un error cross-origin sin datos: tampoco aporta.
    var ERROR_AJENO = /Java object is gone|Java bridge method|^Script error\.?$/i;

    var errorRegistrado = false;
    window.addEventListener('error', function (e) {
        if (errorRegistrado) return;

        var msg;
        if (e.target && e.target !== window && e.target.src) {
            msg = 'recurso: ' + String(e.target.src).split('/').pop();
        } else {
            if (ERROR_AJENO.test(e.message || '')) return;   // sin marcar el flag: deja pasar un error real posterior
            msg = (e.message || 'error') + ' @ ' + String(e.filename || '').split('/').pop() + ':' + (e.lineno || '?');
        }

        errorRegistrado = true;
        encolar('error', msg.slice(0, 64));
    }, true);

    // ── 7. Salida ─────────────────────────────────────────────
    // visibilitychange es el único evento fiable en móvil: pagehide y
    // beforeunload no siempre disparan cuando el sistema mata la pestaña.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState !== 'hidden') return;
        hito('salida', 'salida');   // el evento una sola vez…
        enviar();                   // …pero el vaciado de la cola, en cada salida
    });
    window.addEventListener('pagehide', function () { enviar(); });

    setInterval(enviar, INTERVALO);
})();
