/**
 * Trampa de foco para modales.
 *
 * Un modal con aria-modal="true" le dice al lector de pantalla que el resto de
 * la página no existe. Si el foco puede salir con Tab, el teclado se pasea por
 * contenido que el lector considera inexistente y que además está tapado.
 *
 * Uso:
 *   var trap = window.crearTrampaFoco(modalEl);
 *   trap.activar();     // al abrir  (guarda el foco previo)
 *   trap.desactivar();  // al cerrar (lo devuelve)
 */
(function () {
    'use strict';

    var SELECTOR_ENFOCABLES = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    function visibles(modal) {
        return Array.prototype.filter.call(
            modal.querySelectorAll(SELECTOR_ENFOCABLES),
            function (el) {
                if (el.closest('[hidden]')) return false;
                // offsetParent es null para elementos con display:none
                return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
            }
        );
    }

    window.crearTrampaFoco = function crearTrampaFoco(modal) {
        if (!modal) return { activar: function () {}, desactivar: function () {} };

        var disparador = null;
        var activa = false;

        function onKeydown(e) {
            if (e.key !== 'Tab' || !activa) return;

            var items = visibles(modal);
            if (!items.length) return;

            var primero = items[0];
            var ultimo = items[items.length - 1];

            // Si el foco se escapó del modal (p. ej. está en el body), traerlo de vuelta
            if (!modal.contains(document.activeElement)) {
                e.preventDefault();
                primero.focus();
                return;
            }

            if (e.shiftKey && document.activeElement === primero) {
                e.preventDefault();
                ultimo.focus();
            } else if (!e.shiftKey && document.activeElement === ultimo) {
                e.preventDefault();
                primero.focus();
            }
        }

        document.addEventListener('keydown', onKeydown, true);

        return {
            activar: function (elementoInicial) {
                if (activa) return;
                activa = true;
                disparador = document.activeElement;

                var destino = elementoInicial || visibles(modal)[0];
                if (destino) setTimeout(function () { destino.focus(); }, 60);
            },
            desactivar: function () {
                if (!activa) return;
                activa = false;
                if (disparador && typeof disparador.focus === 'function') {
                    disparador.focus();
                }
                disparador = null;
            }
        };
    };
})();
