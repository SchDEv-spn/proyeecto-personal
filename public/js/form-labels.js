/**
 * Empareja etiquetas y campos en el editor de landing.
 *
 * El formulario tiene ~377 campos y más de 100 usan <label>Texto</label> suelto
 * junto al control, sin for/id. Visualmente se ve bien, pero un lector de
 * pantalla anuncia "campo de texto" sin decir de qué campo se trata, y los
 * <input type="file"> quedan sin nombre accesible.
 *
 * Recorre cada grupo del formulario, y si la etiqueta no apunta a ningún
 * control, la asocia con el primero que encuentre dentro del grupo.
 */
(function () {
    'use strict';

    var GRUPOS = '.admin-form-group, .form-field, .mini-card, .gallery-card';
    var CONTROLES = 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea';

    var contador = 0;
    function asegurarId(el) {
        if (!el.id) {
            el.id = 'campo-auto-' + (++contador);
        }
        return el.id;
    }

    function emparejar(raiz) {
        raiz.querySelectorAll(GRUPOS).forEach(function (grupo) {
            var etiquetas = grupo.querySelectorAll(':scope > label, :scope > div > label');
            // Un control solo puede quedar asociado a una etiqueta: sin esto,
            // dos etiquetas del mismo grupo apuntaban al mismo campo y el resto
            // se quedaba sin nombre accesible.
            var tomados = [];

            etiquetas.forEach(function (label) {
                if (label.getAttribute('for')) return;      // ya está asociada
                if (label.querySelector(CONTROLES)) return; // el control va dentro: ya es implícita

                var control = Array.prototype.find.call(
                    grupo.querySelectorAll(CONTROLES),
                    function (c) {
                        if (tomados.indexOf(c) !== -1) return false;
                        if (c.closest('label')) return false;
                        if (c.id && grupo.querySelector('label[for="' + CSS.escape(c.id) + '"]')) return false;
                        return true;
                    }
                );
                if (!control) return;

                tomados.push(control);
                label.setAttribute('for', asegurarId(control));
            });
        });

        // Red de seguridad: cualquier control que siga sin nombre accesible
        // recibe uno a partir del texto visible más cercano.
        raiz.querySelectorAll(CONTROLES).forEach(function (c) {
            if (c.getAttribute('aria-label') || c.getAttribute('aria-labelledby')) return;
            if (c.closest('label')) return;

            var propia = c.id ? document.querySelector('label[for="' + CSS.escape(c.id) + '"]') : null;
            // Una etiqueta oculta (p. ej. sustituida por una zona de arrastre)
            // no aporta nombre accesible aunque el for/id esté bien puesto.
            if (propia && propia.offsetParent !== null) return;

            var grupo = c.closest(GRUPOS);
            var label = propia || (grupo ? grupo.querySelector('label') : null);
            var texto = (label && label.textContent.trim()) || c.getAttribute('placeholder') || c.name;
            if (texto) c.setAttribute('aria-label', texto.replace(/\s+/g, ' '));
        });
    }

    function ejecutar() { emparejar(document); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ejecutar);
    } else {
        ejecutar();
    }

    // ux-improvements.js reconstruye secciones y sustituye algunos campos por
    // zonas de arrastre (ocultando su etiqueta), así que hay que repasar después.
    document.addEventListener('ux:sections-ready', ejecutar);
    window.addEventListener('load', function () { setTimeout(ejecutar, 200); });

    window.emparejarEtiquetas = emparejar;
})();
