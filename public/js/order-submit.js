/* /tienda_mvc/public/js/order-submit.js
   Validacion y envio del pedido.

   Vive solo, y no dentro de main.js, por una razon concreta: main.js lleva
   ademas los carruseles, la galeria, los videos, el pixel y media docena de
   cosas mas. Un unico error de sintaxis en cualquiera de ellas se lleva por
   delante el archivo entero — y con el, el envio del formulario. En el
   navegador de Facebook, que es por donde entra la pauta, ese riesgo no es
   teorico. Aqui dentro solo hay lo imprescindible para que un pedido salga.

   Y si este archivo tampoco llega a ejecutarse, no pasa nada grave: el form
   hace su POST nativo, el servidor valida igual y la vista pinta los errores.
   Por eso el HTML trae ya los departamentos y municipios y el <form> tiene su
   action de verdad.

   Sin ES2020 (?. y ??): no hay build, esto se sirve tal cual.
*/
(function () {
    var form = document.getElementById('formPedido');
    if (!form) return;

    var errorsBox = document.getElementById('stepperErrors');
    var successBox = document.getElementById('stepperSuccess');

    function valorDe(sel) {
        var el = form.querySelector(sel);
        return (el && el.value) ? el.value.trim() : '';
    }

    function validar() {
        var errors = [];

        if (!valorDe('#nombre'))    errors.push('El nombre es obligatorio.');
        if (!valorDe('#apellidos')) errors.push('Los apellidos son obligatorios.');

        var tel = valorDe('#telefono');
        if (!tel) errors.push('El número de WhatsApp es obligatorio.');
        else if (!/^3\d{9}$/.test(tel)) errors.push('Número inválido (10 dígitos, empieza en 3).');

        var modeEl = form.querySelector('#pricingMode');
        var mode = (modeEl && modeEl.value) ? modeEl.value : 'individual';

        if (mode === 'combo') {
            var blocks = form.querySelectorAll('.combo-block');
            if (!blocks.length) {
                errors.push('Debes tener al menos 1 combo.');
            } else {
                Array.prototype.forEach.call(blocks, function (b, idx) {
                    Array.prototype.forEach.call(b.querySelectorAll('select.combo-color'), function (s) {
                        if (!s.value) errors.push('Selecciona el color del combo ' + (idx + 1) + '.');
                    });
                });
            }
        } else {
            /* Toda fila de color visible tiene que tener color. Antes bastaba
               con que UNA lo tuviera: quien tocaba "quiero otro color
               diferente" y no elegia nada en la fila nueva pasaba la
               validacion del navegador y se estrellaba contra la del
               servidor con el formulario ya lleno. */
            var filas = form.querySelectorAll('.color-row');
            if (filas.length) {
                var conColor = 0;
                var vacias = 0;
                Array.prototype.forEach.call(filas, function (fila) {
                    var s = fila.querySelector('select[name="color_item[]"]');
                    if (!s) return;
                    if (s.value) conColor++; else vacias++;
                });
                if (!conColor) errors.push('Selecciona al menos un color.');
                else if (vacias) errors.push('Te faltó elegir el color en una de las filas (o quítala con “✕ Quitar”).');
            }
        }

        if (!valorDe('#departamento')) errors.push('Selecciona un departamento.');
        if (!valorDe('#municipio')) errors.push('Selecciona un municipio.');

        var entrega = form.querySelector('input[name="tipo_entrega"]:checked');
        if (!entrega) errors.push('Selecciona cómo quieres recibir tu pedido.');
        else if (entrega.value === 'domicilio' && !valorDe('#direccion')) {
            errors.push('La dirección es obligatoria para envío a domicilio.');
        }

        return errors;
    }

    function mostrarErrores(errors) {
        if (!errorsBox) return;
        if (!errors.length) { errorsBox.style.display = 'none'; errorsBox.innerHTML = ''; return; }
        var ul = document.createElement('ul');
        errors.forEach(function (e) {
            var li = document.createElement('li');
            li.textContent = e;
            ul.appendChild(li);
        });
        /* replaceChildren() es de 2020 y en un WebView viejo no existe:
           vaciar y volver a llenar funciona en cualquier motor. */
        errorsBox.innerHTML = '';
        errorsBox.appendChild(ul);
        errorsBox.style.display = 'block';
        errorsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function restaurarBoton(btnSubmit, btnText, btnSpinner) {
        if (btnSubmit) btnSubmit.disabled = false;
        if (btnText) btnText.style.display = 'inline';
        if (btnSpinner) btnSpinner.style.display = 'none';
    }

    form.addEventListener('submit', function (e) {
        /* Sin fetch no hay envio por AJAX que valga: se deja que el navegador
           haga el POST de toda la vida en vez de bloquear el pedido. */
        if (typeof window.fetch !== 'function') return;

        e.preventDefault();

        var errors = validar();
        if (errors.length) { mostrarErrores(errors); return; }
        mostrarErrores([]);

        var btnText = document.getElementById('btnSubmitText');
        var btnSpinner = document.getElementById('btnSubmitSpinner');
        var btnSubmit = document.getElementById('btnSubmit');

        if (btnSubmit) btnSubmit.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline';

        var enviar = (typeof window.fetchWithTimeout === 'function')
            ? window.fetchWithTimeout
            : function (url, opts) { return fetch(url, opts); };

        enviar(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form)
        }, 15000)
            .then(function (r) { return r.json(); })
            .then(function (res) {
                restaurarBoton(btnSubmit, btnText, btnSpinner);

                if (!res || !res.ok) {
                    mostrarErrores(res && res.errores && res.errores.length
                        ? res.errores
                        : ['No pudimos registrar tu pedido. Inténtalo de nuevo o escríbenos por WhatsApp.']);
                    return;
                }

                form.style.display = 'none';
                if (successBox) {
                    if (res.pedido_id) {
                        var numEl = document.getElementById('orderSuccessNum');
                        var valEl = document.getElementById('orderSuccessNumVal');
                        if (numEl && valEl) { valEl.textContent = '#' + res.pedido_id; numEl.style.display = ''; }
                    }
                    successBox.style.display = 'block';
                    successBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                if (typeof fbq === 'function') {
                    var valor = res.precio_total || window.landingProductPrice || 0;
                    // Lead — cliente potencial (pedido contraentrega registrado)
                    fbq('track', 'Lead', {
                        value: valor,
                        currency: 'COP',
                        content_name: window.landingProductName || ''
                    });
                    // Purchase — conversión de venta
                    fbq('track', 'Purchase', {
                        value: valor,
                        currency: 'COP',
                        content_name: window.landingProductName || '',
                        content_ids: [String(res.pedido_id || '')],
                        content_type: 'product',
                        num_items: 1
                    });
                }
            })
            .catch(function () {
                restaurarBoton(btnSubmit, btnText, btnSpinner);
                mostrarErrores(['Error de conexión. Verifica tu internet e inténtalo de nuevo.']);
            });
    });
})();
