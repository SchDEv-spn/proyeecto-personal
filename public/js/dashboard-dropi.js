/**
 * Dashboard › "Ventas reales · Dropi": los filtros (período, canal y fechas) cambian la sección en su sitio,
 * sin recargar el dashboard. Cada filtro sigue siendo un enlace normal: si algo falla (o si el JavaScript no
 * carga) se navega a la dirección completa, como siempre.
 *
 *  - El servidor devuelve solo la sección ya armada (AdminPedidos/dropiSeccion, mismos parámetros que el enlace).
 *  - La dirección de la barra se actualiza (pushState) y el botón "atrás" vuelve al filtro anterior.
 *  - Los gráficos se dibujan desde el JSON que viene dentro de la sección (#dropiGraficos).
 */
(function () {
    'use strict';

    var VERDE = { borde: '#16a34a', relleno: 'rgba(34,197,94,.14)' };
    var MORADO = { borde: '#5d68ff', relleno: 'rgba(93,104,255,.14)' };

    function plata(v) {
        var n = Number(v) || 0;
        return n >= 1e6 ? (Math.round(n / 1e5) / 10) + 'M' : (n >= 1e3 ? Math.round(n / 1e3) + 'k' : String(Math.round(n)));
    }

    /** Órdenes y ventas por franja de tiempo (líneas, como los gráficos de la landing) y distribución por estado. */
    function graficos() {
        var datosEl = document.getElementById('dropiGraficos');
        if (!datosEl || typeof Chart === 'undefined') return;
        try {
            var d = JSON.parse(datosEl.textContent);
            var s = d.serie;

            var lineas = function (campo) {
                var out = [];
                if (d.canal !== 'whatsapp') out.push({ label: 'Landing', data: s.landing[campo], borderColor: MORADO.borde, backgroundColor: MORADO.relleno, fill: true });
                if (d.canal !== 'landing') out.push({ label: 'WhatsApp / directo', data: s.whatsapp[campo], borderColor: VERDE.borde, backgroundColor: VERDE.relleno, fill: true });
                return out.map(function (x) { x.tension = .35; x.pointRadius = 2; x.pointHoverRadius = 4; x.borderWidth = 2; return x; });
            };
            var base = function (extraY) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } }, tooltip: { intersect: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } },
                        y: Object.assign({ beginAtZero: true, grid: { color: 'rgba(18,24,40,.08)' } }, extraY)
                    }
                };
            };

            var cOrdenes = document.getElementById('chartDropiOrdenes');
            if (cOrdenes) new Chart(cOrdenes, { type: 'line', data: { labels: s.etiquetas, datasets: lineas('ordenes') }, options: base({ ticks: { precision: 0 } }) });

            var cVentas = document.getElementById('chartDropiVentas');
            if (cVentas) new Chart(cVentas, { type: 'line', data: { labels: s.etiquetas, datasets: lineas('ventas') }, options: base({ ticks: { callback: plata } }) });

            var cEstados = document.getElementById('chartDropiEstados');
            if (cEstados) {
                var e = d.estados;
                var partes = [
                    ['Entregado', e.entregado, 'rgba(16,185,129,.70)'],
                    ['En camino', (e.enviado || 0) + (e.otro || 0), 'rgba(245,158,11,.70)'],
                    ['En oficina', e.en_oficina, 'rgba(251,146,60,.70)'],
                    ['Preparando', e.confirmado, 'rgba(34,197,94,.45)'],
                    ['Por confirmar', (e.nuevo || 0) + (e.contactado || 0), 'rgba(93,104,255,.55)'],
                    ['Devolución', e.devolucion, 'rgba(176,23,23,.75)'],
                    ['Cancelado', e.cancelado, 'rgba(148,163,184,.75)']
                ].filter(function (p) { return p[1] > 0; });
                new Chart(cEstados, {
                    type: 'doughnut',
                    data: { labels: partes.map(function (p) { return p[0]; }), datasets: [{ data: partes.map(function (p) { return p[1]; }), backgroundColor: partes.map(function (p) { return p[2]; }), borderWidth: 0 }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } } }
                });
            }
        } catch (err) {
            console.error('[Dropi] gráficos:', err);
        }
    }

    function destruirGraficos(raiz) {
        if (typeof Chart === 'undefined' || !Chart.getChart) return;
        Array.prototype.forEach.call(raiz.querySelectorAll('canvas'), function (c) {
            var ch = Chart.getChart(c);
            if (ch) ch.destroy();
        });
    }

    /** Un desplegable de fechas que se abre cierra a los demás (igual que el resto del dashboard). */
    function enlazarDesplegables(raiz) {
        Array.prototype.forEach.call(raiz.querySelectorAll('details.dtf-wrap'), function (det) {
            det.addEventListener('toggle', function () {
                if (!det.open) return;
                Array.prototype.forEach.call(document.querySelectorAll('details.dtf-wrap'), function (otro) { if (otro !== det) otro.open = false; });
            });
        });
    }

    var pedido = 0; // para ignorar respuestas viejas si se hace clic varias veces seguidas
    var CLAVES = ['dropi_rango', 'dropi_desde', 'dropi_hasta', 'dropi_canal'];

    /**
     * La dirección de $href con los filtros de Dropi que trae, y lo demás (el filtro de fechas de la landing, que
     * también cambia sin recargar) como está AHORA en la barra: los enlaces de esta sección se armaron cuando la
     * página se cargó y pueden traer un filtro de la landing que ya no es el que se ve.
     */
    function conLoActual(href) {
        var destino = new URL(href, window.location.href);
        var params = new URLSearchParams(window.location.search);
        CLAVES.forEach(function (k) { params.delete(k); });
        CLAVES.forEach(function (k) { if (destino.searchParams.has(k)) params.set(k, destino.searchParams.get(k)); });
        var texto = params.toString();
        destino.search = texto ? '?' + texto : '';
        return destino;
    }

    /** Los enlaces de los filtros llevan el filtro de la landing de ahora (por si se abren en otra pestaña). */
    window.dropiActualizarEnlaces = function () {
        var enlaces = document.querySelectorAll('#ventas-dropi .dash-filtros a.state-chip');
        Array.prototype.forEach.call(enlaces, function (a) { a.setAttribute('href', conLoActual(a.getAttribute('href')).href); });
    };

    /** Pide la sección con los parámetros de $href y la cambia en su sitio. Si algo falla, navega a $href. */
    function cargar(href, apilar) {
        var seccion = document.getElementById('ventas-dropi');
        var base = seccion && seccion.getAttribute('data-fragmento');
        if (!seccion || !base || !window.fetch || !window.URL) { window.location.href = href; return; }

        var url = conLoActual(href);
        href = url.href;
        var miPedido = ++pedido;
        seccion.classList.add('is-cargando');
        seccion.setAttribute('aria-busy', 'true');

        window.fetch(base + url.search, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                if (miPedido !== pedido) return; // llegó una respuesta más nueva
                var caja = document.createElement('div');
                caja.innerHTML = html.trim();
                var nueva = caja.querySelector('#ventas-dropi');
                if (!nueva) throw new Error('respuesta sin la sección');

                var teniaFoco = seccion.contains(document.activeElement);
                destruirGraficos(seccion);
                seccion.replaceWith(nueva);
                enlazarDesplegables(nueva);
                graficos();

                var destino = url.pathname + url.search + url.hash;
                if (apilar && destino !== window.location.pathname + window.location.search + window.location.hash) {
                    window.history.pushState({ dropi: 1 }, '', destino);
                }
                if (teniaFoco) {
                    var activo = nueva.querySelector('.dash-filtros .is-active, .dash-filtros a');
                    if (activo) activo.focus({ preventScroll: true });
                }
            })
            .catch(function () {
                if (miPedido === pedido) window.location.href = href;
            });
    }

    // Clic en un filtro (período o canal): es un enlace normal; con Ctrl, Mayús o clic central se abre como siempre.
    document.addEventListener('click', function (ev) {
        if (ev.defaultPrevented || ev.button !== 0 || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) return;
        var a = ev.target.closest ? ev.target.closest('#ventas-dropi .dash-filtros a.state-chip') : null;
        if (!a) return;
        ev.preventDefault();
        cargar(a.href, true);
    });

    // Fechas personalizadas: del formulario solo se toman los filtros de Dropi (lo de la landing se toma de la barra).
    document.addEventListener('submit', function (ev) {
        var f = ev.target.closest ? ev.target.closest('#ventas-dropi form.dfd-custom__form') : null;
        if (!f || ev.defaultPrevented) return;
        ev.preventDefault();
        var qs = new URLSearchParams();
        CLAVES.forEach(function (k) { var campo = f.elements[k]; if (campo && campo.value !== '') qs.set(k, campo.value); });
        var accion = new URL(f.getAttribute('action') || window.location.pathname, window.location.href);
        cargar(accion.pathname + '?' + qs.toString() + '#ventas-dropi', true);
    });

    // El botón "atrás" y "adelante" vuelven al filtro que había.
    window.addEventListener('popstate', function () {
        if (document.getElementById('ventas-dropi')) cargar(window.location.href, false);
    });

    graficos();
})();
