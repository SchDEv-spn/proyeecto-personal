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

    function quitar(nodo) {
        if (nodo && nodo.parentNode) nodo.parentNode.removeChild(nodo);
    }

    function esControl(el) {
        return /^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName);
    }

    /* ── Nombre y apellido en un solo campo ───────────────────────────
       Mismas reglas que LandingController::nombreCompletoValido() y
       ::partirNombreCompleto(): si cambian aqui, cambian alli. */
    function palabras(v) {
        var t = String(v || '').replace(/^\s+|\s+$/g, '');
        return t ? t.split(/\s+/) : [];
    }

    function letras(s) {
        var m = String(s).match(/[A-Za-zÀ-ɏ]/g);
        return m ? m.length : 0;
    }

    /* Nombre Y apellido: dos palabras o mas, la primera y la ultima con 2
       letras o mas (una inicial como "G." no sirve para la guia del envio). */
    function nombreValido(v) {
        var p = palabras(v);
        return p.length >= 2 && letras(p[0]) >= 2 && letras(p[p.length - 1]) >= 2;
    }

    function partirNombre(v) {
        var p = palabras(v);
        if (p.length <= 1) return { nombre: p[0] || '', apellidos: '' };
        var corte = p.length >= 4 ? 2 : 1;
        return { nombre: p.slice(0, corte).join(' '), apellidos: p.slice(corte).join(' ') };
    }

    /* Cabe en UNA línea hasta en 360px: el aviso reemplaza la pista del campo
       al salir de él, y si ocupara dos líneas empujaría el botón de abajo en el
       instante en que se lo está tocando. El ejemplo ya está en el placeholder. */
    var MSG_NOMBRE = 'Escribe tu nombre y tu apellido';

    /* Celular en 10 digitos pelados: sin espacios ni signos, sin el prefijo
       57 (un celular colombiano nunca empieza por 5) y con tope de 10. */
    function limpiarTel(valor) {
        var d = String(valor || '').replace(/\D+/g, '');
        if (d.indexOf('0057') === 0) d = d.slice(4);
        else if (d.length > 2 && d.indexOf('57') === 0) d = d.slice(2);
        return d.slice(0, 10);
    }

    /* Cada error lleva el campo (o grupo) al que pertenece: asi el mensaje se
       pinta junto a el y el foco va al primero. El orden de la lista sigue el
       del formulario — que pides, a donde, quien eres. */
    function validar() {
        var errors = [];
        function falla(el, msg) { errors.push({ el: el, msg: msg }); }

        var modeEl = form.querySelector('#pricingMode');
        var mode = (modeEl && modeEl.value) ? modeEl.value : 'individual';

        if (mode === 'combo') {
            var blocks = form.querySelectorAll('.combo-block');
            if (!blocks.length) {
                falla(form, 'Debes tener al menos 1 combo.');
            } else {
                Array.prototype.forEach.call(blocks, function (b, idx) {
                    Array.prototype.forEach.call(b.querySelectorAll('select.combo-color'), function (s) {
                        if (!s.value) falla(s, 'Selecciona el color del combo ' + (idx + 1) + '.');
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
                var vacias = [];
                Array.prototype.forEach.call(filas, function (fila) {
                    var s = fila.querySelector('select[name="color_item[]"]');
                    if (!s) return;
                    if (s.value) conColor++; else vacias.push(fila);
                });
                if (!conColor) {
                    falla(filas[0], 'Toca el color que quieres pedir.');
                } else {
                    vacias.forEach(function (fila) {
                        falla(fila, 'Te faltó elegir el color en esta fila (o quítala con “✕ Quitar”).');
                    });
                }
            }
        }

        var dep = form.querySelector('#departamento');
        var mun = form.querySelector('#municipio');
        if (dep && !valorDe('#departamento')) falla(dep, 'Selecciona un departamento.');
        if (mun && !valorDe('#municipio')) falla(mun, 'Selecciona un municipio.');

        var entrega = form.querySelector('input[name="tipo_entrega"]:checked');
        if (!entrega) {
            var radios = form.querySelector('input[name="tipo_entrega"]');
            falla(radios ? (radios.closest('fieldset') || radios) : form, 'Selecciona cómo quieres recibir tu pedido.');
        } else if (entrega.value === 'domicilio' && !valorDe('#direccion')) {
            falla(form.querySelector('#direccion') || form, 'La dirección es obligatoria para envío a domicilio.');
        }

        var nombreEl = form.querySelector('#nombre_completo');
        if (nombreEl) {
            var nom = valorDe('#nombre_completo');
            if (!nom || !nombreValido(nom)) falla(nombreEl, MSG_NOMBRE);
        }

        var telEl = form.querySelector('#telefono');
        if (telEl) {
            var tel = limpiarTel(valorDe('#telefono'));
            if (!tel) falla(telEl, 'El número de WhatsApp es obligatorio.');
            else if (!/^3\d{9}$/.test(tel)) falla(telEl, 'Número inválido (10 dígitos, empieza en 3).');
        }

        return errors;
    }

    /* ── Errores junto al campo ───────────────────────────────────────
       Antes todo salia en una lista ARRIBA del formulario: quien fallaba
       desde el boton final subia a leerla, ningun campo se marcaba ni
       recibia el foco, y la barra de anuncios fija tapaba las primeras
       lineas. Ahora cada mensaje vive pegado a su campo y el foco va al
       primero que falla. */
    var contador = 0;
    var telMarcado = false;

    function restaurarPistaNombre() {
        var h = document.getElementById('nombreHint');
        if (h && h.hasAttribute('data-orig')) {
            h.textContent = h.getAttribute('data-orig');
            h.removeAttribute('data-orig');
            h.classList.remove('err');
        }
    }

    function marcar(el, msg) {
        /* El nombre y el telefono ya tienen una pista debajo: esa misma linea
           hace de mensaje. No se inserta nada, asi no se mueve el boton en el
           instante en que el comprador lo esta tocando. */
        if (el.id === 'nombre_completo') {
            var h = document.getElementById('nombreHint');
            if (h) {
                if (!h.hasAttribute('data-orig')) h.setAttribute('data-orig', h.textContent);
                h.textContent = msg;
                h.classList.add('err');
            }
        } else if (el.id === 'telefono') {
            var th = document.getElementById('telHint');
            if (th) {
                th.textContent = msg;
                th.className = 'tel-hint err';
                telMarcado = true;
            }
        } else {
            var p = document.createElement('p');
            p.className = 'field-error';
            p.id = 'err-' + (el.id || ('g' + (++contador)));
            p.setAttribute('role', 'alert');
            p.textContent = msg;
            if (esControl(el)) {
                el.parentNode.insertBefore(p, el.nextSibling);
                el.setAttribute('aria-describedby', p.id);
            } else {
                el.appendChild(p);
            }
        }

        if (esControl(el)) {
            el.classList.add('is-invalid');
            el.setAttribute('aria-invalid', 'true');
        } else if (el !== form) {
            el.classList.add('is-invalid-grupo');
        }
    }

    function limpiarGrupo(g) {
        if (!g) return;
        Array.prototype.slice.call(g.children).forEach(function (h) {
            if (h.classList && h.classList.contains('field-error')) quitar(h);
        });
        g.classList.remove('is-invalid-grupo');
    }

    function limpiarCampo(el) {
        if (!el || !el.classList) return;
        var d = el.getAttribute('aria-describedby');
        if (d && d.indexOf('err-') === 0) {
            quitar(document.getElementById(d));
            el.removeAttribute('aria-describedby');
        }
        el.classList.remove('is-invalid');
        el.removeAttribute('aria-invalid');
        if (el.id === 'nombre_completo') restaurarPistaNombre();
    }

    function limpiarErrores() {
        Array.prototype.forEach.call(form.querySelectorAll('.is-invalid'), limpiarCampo);
        Array.prototype.forEach.call(form.querySelectorAll('.is-invalid-grupo'), limpiarGrupo);
        Array.prototype.forEach.call(form.querySelectorAll('.field-error'), quitar);
        restaurarPistaNombre();
        if (telMarcado) {
            telMarcado = false;
            /* La pista del telefono la escribe su validacion en vivo (script de
               la vista): se le pide que la recalcule en vez de duplicar su texto. */
            var telEl = form.querySelector('#telefono');
            try { if (telEl) telEl.dispatchEvent(new Event('input', { bubbles: true })); } catch (e) { /* sin Event(): queda la ultima pista */ }
        }
    }

    function llevarA(el) {
        var foco = esControl(el) ? el : (el.querySelector('.color-pill, select, input, button') || el);
        /* Se centra el grupo entero, no su primer control: los radios de la
           entrega son invisibles (0x0) y medirlos mandaba el scroll a cualquier
           parte. Un campo suelto sí se mide a sí mismo. */
        var r = el.getBoundingClientRect();
        var alto = window.innerHeight || document.documentElement.clientHeight || 600;
        var base = window.pageYOffset || document.documentElement.scrollTop || 0;
        var y = Math.max(0, Math.round(r.top + base - alto * 0.3));
        try { window.scrollTo({ top: y, behavior: 'smooth' }); } catch (e) { window.scrollTo(0, y); }
        try { foco.focus({ preventScroll: true }); } catch (e2) { try { foco.focus(); } catch (e3) { /* sin foco: ya quedo marcado */ } }
    }

    /* Se corrige en el propio campo: el aviso de ese campo se va al tocarlo. */
    form.addEventListener('input', function (e) { limpiarCampo(e.target); });
    form.addEventListener('change', function (e) {
        limpiarCampo(e.target);
        limpiarGrupo(e.target.closest ? e.target.closest('.is-invalid-grupo') : null);
    });
    form.addEventListener('click', function (e) {
        limpiarGrupo(e.target.closest ? e.target.closest('.is-invalid-grupo') : null);
    });

    /* El apellido se echa de menos al salir del campo, no en el boton final.
       Solo si ya escribio algo: un campo vacio al pasar de largo no se regaña. */
    var nombreCampo = document.getElementById('nombre_completo');
    if (nombreCampo) {
        nombreCampo.addEventListener('blur', function () {
            var v = nombreCampo.value.trim();
            limpiarCampo(nombreCampo);
            if (v && !nombreValido(v)) marcar(nombreCampo, MSG_NOMBRE);
        });
    }

    /* La tecla "Ir"/Intro del teclado enviaba el formulario desde cualquier
       campo de texto: quien la tocaba en "Nombre y apellido" esperando pasar
       al siguiente disparaba el envio con todo lo demas vacio. Ahora Intro
       salta al campo siguiente; solo en el ultimo envia. */
    function siguienteCampo(actual) {
        var todos = form.querySelectorAll('input:not([type=hidden]):not([type=radio]):not([type=checkbox]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
        var visibles = Array.prototype.filter.call(todos, function (c) { return c.offsetParent !== null; });
        var i = visibles.indexOf(actual);
        return i >= 0 ? (visibles[i + 1] || null) : null;
    }

    form.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.keyCode !== 13) return;
        var t = e.target;
        if (!t || t.tagName !== 'INPUT') return;
        var tipo = (t.type || '').toLowerCase();
        if (tipo !== 'text' && tipo !== 'tel') return;
        var sig = siguienteCampo(t);
        if (!sig) return;           // ultimo campo: que envie
        e.preventDefault();
        sig.focus();
    });

    /* Los errores que devuelve el servidor (pedido duplicado, fallo al
       guardar, error de red) salen justo encima del boton que se acaba de
       tocar, no arriba del formulario a dos mil pixeles de distancia. */
    var cajaDelEnvio = null;
    function cajaEnvio() {
        if (cajaDelEnvio) return cajaDelEnvio;
        var nav = form.querySelector('.form-step__nav--submit');
        if (!nav) return errorsBox;
        cajaDelEnvio = document.createElement('div');
        cajaDelEnvio.className = 'error envio-errores';
        cajaDelEnvio.setAttribute('role', 'alert');
        cajaDelEnvio.style.display = 'none';
        nav.parentNode.insertBefore(cajaDelEnvio, nav);
        return cajaDelEnvio;
    }

    function mostrarErroresEnvio(errors) {
        var box = cajaEnvio();
        if (!box) return;
        if (!errors.length) { box.style.display = 'none'; box.innerHTML = ''; return; }
        var ul = document.createElement('ul');
        errors.forEach(function (e) {
            var li = document.createElement('li');
            li.textContent = e;
            ul.appendChild(li);
        });
        /* replaceChildren() es de 2020 y en un WebView viejo no existe:
           vaciar y volver a llenar funciona en cualquier motor. */
        box.innerHTML = '';
        box.appendChild(ul);
        box.style.display = 'block';
        try { box.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { box.scrollIntoView(); }
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

        limpiarErrores();
        mostrarErroresEnvio([]);
        if (errorsBox) { errorsBox.style.display = 'none'; errorsBox.innerHTML = ''; }

        var errors = validar();
        if (errors.length) {
            errors.forEach(function (x) { marcar(x.el, x.msg); });
            llevarA(errors[0].el);
            return;
        }

        /* Lo que viaja es el numero ya limpio (el servidor lo limpia igual). */
        var telCampo = form.querySelector('#telefono');
        if (telCampo) telCampo.value = limpiarTel(telCampo.value);

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
                    mostrarErroresEnvio(res && res.errores && res.errores.length
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

                // El pedido ya está confirmado en pantalla. De aquí en
                // adelante solo es pixel: en el webview de Facebook/TikTok
                // una llamada a fbq/ttq puede lanzar "Java object is gone"
                // si el bridge nativo ya se recolectó, y sin este try el
                // throw saltaba al .catch() de abajo — pintando "error de
                // conexión" sobre un pedido exitoso y reactivando el botón
                // (pedido duplicado).
                try {

                if (typeof fbq === 'function') {
                    var valor = res.precio_total || window.landingProductPrice || 0;
                    var cantidad = res.cantidad_total || 1;
                    var productId = String(window.landingProductId || '');

                    // Advanced Matching: refresca el init con los datos que el
                    // cliente ya dio en el formulario, antes de trackear Lead
                    // y Purchase — mejora el emparejamiento justo en el
                    // tráfico de in-app browser, donde las cookies valen poco.
                    // El nombre se parte igual que en el servidor
                    // (LandingController::partirNombreCompleto), así el pixel
                    // y la Conversions API mandan el mismo fn/ln.
                    var telLocal = valorDe('#telefono').replace(/\D/g, '');
                    var nombrePartido = partirNombre(valorDe('#nombre_completo'));
                    fbq('init', window.landingPixelId, {
                        fn: nombrePartido.nombre.toLowerCase(),
                        ln: nombrePartido.apellidos.toLowerCase(),
                        ph: telLocal ? '57' + telLocal : '',
                        ct: valorDe('#municipio').toLowerCase(),
                        st: valorDe('#departamento').toLowerCase(),
                        country: 'co'
                    });

                    // Lead — cliente potencial (pedido contraentrega registrado)
                    fbq('track', 'Lead', {
                        value: valor,
                        currency: 'COP',
                        content_name: window.landingProductName || ''
                    });
                    // Purchase — conversión de venta. content_ids usa el id del
                    // PRODUCTO (igual que ViewContent) para que Facebook pueda
                    // unir vista y compra; el id del pedido va en eventID, para
                    // deduplicar con el envío server-side del día que exista.
                    fbq('track', 'Purchase', {
                        value: valor,
                        currency: 'COP',
                        content_name: window.landingProductName || '',
                        content_ids: [productId],
                        content_type: 'product',
                        num_items: cantidad,
                        contents: [{
                            id: productId,
                            quantity: cantidad,
                            item_price: cantidad ? (valor / cantidad) : valor
                        }]
                    }, { eventID: 'pedido_' + (res.pedido_id || '') });
                }

                if (typeof ttq !== 'undefined' && ttq) {
                    var valorTt = res.precio_total || window.landingProductPrice || 0;
                    var cantidadTt = res.cantidad_total || 1;
                    var productIdTt = String(window.landingProductId || '');
                    var telLocalTt = valorDe('#telefono').replace(/\D/g, '');

                    ttq.identify({
                        phone_number: telLocalTt ? '+57' + telLocalTt : undefined,
                        external_id: String(res.pedido_id || '')
                    });

                    ttq.track('SubmitForm', {
                        contents: [{
                            content_id: productIdTt,
                            content_type: 'product',
                            content_name: window.landingProductName || ''
                        }],
                        value: valorTt,
                        currency: 'COP'
                    });
                    ttq.track('CompletePayment', {
                        contents: [{
                            content_id: productIdTt,
                            content_type: 'product',
                            content_name: window.landingProductName || '',
                            quantity: cantidadTt,
                            price: cantidadTt ? (valorTt / cantidadTt) : valorTt
                        }],
                        value: valorTt,
                        currency: 'COP'
                    }, { event_id: 'pedido_' + (res.pedido_id || '') });
                }

                } catch (ePixel) {
                    if (window.console && console.warn) {
                        console.warn('[pedido] el pixel post-envío falló; el pedido quedó OK:', ePixel);
                    }
                }
            })
            .catch(function () {
                restaurarBoton(btnSubmit, btnText, btnSpinner);
                mostrarErroresEnvio(['Error de conexión. Verifica tu internet e inténtalo de nuevo.']);
            });
    });
})();
