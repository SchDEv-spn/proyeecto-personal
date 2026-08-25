/**
 * main.js — Landing Dark Luxury
 * ─────────────────────────────────────────────────────────────
 * Módulos:
 *   · initTipoEntrega           — domicilio / oficina
 *   · initDepartamentoMunicipio — selects Colombia
 *   · initSlider                — slider genérico (legacy)
 *   · initCountdown             — timer con persistencia sessionStorage
 *   · initAccordion             — FAQ accordion
 *   · initGallery               — swap suave + zoom lightbox
 *   · initRecentOrders          — contador pseudo-dinámico
 *   · initScrollAnimations      — fade-up con IntersectionObserver
 *   · initStickyVisibility      — ocultar sticky al llegar al form
 *   · initLazyImages            — marcar imágenes loaded
 *   · initWaLinksIAB            — WhatsApp links en Facebook IAB
 *   · initVideoAutoplay         — play/pause por IntersectionObserver
 * ─────────────────────────────────────────────────────────────
 */

/* ══════════════════════════════════════════════════════════════
   FETCH CON TIMEOUT — evita cuelgues en conexión lenta (IAB)
   ══════════════════════════════════════════════════════════════ */
function fetchWithTimeout(url, options, ms) {
    ms = ms || 10000;
    if (!window.AbortController) return fetch(url, options);
    var ctrl = new AbortController();
    var promise = fetch(url, Object.assign({}, options, { signal: ctrl.signal }));
    var timer = setTimeout(function () { ctrl.abort(); }, ms);
    promise.then(function () { clearTimeout(timer); }, function () { clearTimeout(timer); });
    return promise;
}

/* Cada modulo se arranca aislado. Antes colgaban de una sola cadena y el
   primero que lanzara se llevaba a todos los siguientes: un fallo en la
   galeria dejaba sin ejecutar initPixelEvents(), o sea que se perdian los
   eventos del pixel de una campana que se esta pagando, y nadie se enteraba.
   Ahora un modulo roto solo se rompe a si mismo y deja el rastro en consola. */
function arrancar(nombre, fn) {
    if (typeof fn !== 'function') return;
    try {
        fn();
    } catch (err) {
        if (window.console && console.error) console.error('[landing] falló ' + nombre + ':', err);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    /* El orden importa: los dos primeros son el formulario. */
    arrancar('initTipoEntrega', initTipoEntrega);
    arrancar('initDepartamentoMunicipio', initDepartamentoMunicipio);
    arrancar('initPixelEvents', initPixelEvents);
    arrancar('initWaLinksIAB', initWaLinksIAB);
    arrancar('initSlider', initSlider);
    arrancar('initAccordion', initAccordion);
    arrancar('initGallery', initGallery);
    arrancar('initRecentOrders', initRecentOrders);
    arrancar('initScrollAnimations', initScrollAnimations);
    arrancar('initStickyVisibility', initStickyVisibility);
    arrancar('initLazyImages', initLazyImages);
    arrancar('initVideoControls', initVideoControls);
    arrancar('initVideoAutoplay', initVideoAutoplay);
    arrancar('initTickerLectura', initTickerLectura);
});


/* ══════════════════════════════════════════════════════════════
   TICKER DE TESTIMONIOS — que se pueda leer con el dedo
   El carrusel de testimonios avanza solo con una animación CSS y la
   única pausa que tenía vivía en @media (hover: hover), o sea sólo en
   escritorio. En un celular — que es de donde viene el 100% del
   tráfico — el texto se mueve sin parar y no hay forma de detenerlo:
   la prueba social más fuerte de la página era ilegible.
   Al primer toque se congela para siempre y el contenedor pasa a
   arrastrarse a mano, como el ticker de WhatsApp.
   ══════════════════════════════════════════════════════════════ */
function initTickerLectura() {
    var tickers = document.querySelectorAll('.testimonios-ticker');
    if (!tickers.length) return;

    Array.prototype.forEach.call(tickers, function (ticker) {
        var track = ticker.querySelector('.testimonios-ticker__track');
        if (!track) return;

        function congelar() {
            if (ticker.classList.contains('is-leyendo')) return;

            /* La animación mueve el track con transform. Si se corta en
               seco, el track salta al origen y las tarjetas que el
               usuario estaba mirando desaparecen de golpe. Se congela
               en el sitio: se lee el desplazamiento actual, se fija
               como margen y recién ahí se quita la animación. */
            var x = new DOMMatrixReadOnly(getComputedStyle(track).transform).m41;
            ticker.classList.add('is-leyendo');
            track.style.transform = 'none';
            ticker.scrollLeft = -x;
        }

        ['pointerdown', 'touchstart', 'wheel'].forEach(function (evt) {
            ticker.addEventListener(evt, congelar, { passive: true, once: true });
        });
    });
}


/* ══════════════════════════════════════════════════════════════
   DEPARTAMENTO / MUNICIPIO
   ══════════════════════════════════════════════════════════════ */
/* Los dos selects llegan ya llenos desde PHP: el de departamentos con sus
   28 opciones y el de municipios con los 1.091 del pais repartidos en un
   <optgroup> por departamento. Asi el formulario se puede completar aunque
   este archivo no llegue a ejecutarse nunca — que es lo que pasaba con el
   fetch anterior en cuanto algo fallaba dentro del navegador de Facebook.

   Lo unico que hace el JS es guardar los grupos aparte y volver a colgar
   solo el del departamento elegido, para que el comprador no tenga que
   buscar su pueblo entre mil. Es una mejora, no un requisito. */
function initDepartamentoMunicipio() {
    var selectDept = document.getElementById('departamento');
    var selectMun  = document.getElementById('municipio');

    if (!selectDept || !selectMun) return;

    var placeholder = selectMun.querySelector('option[value=""]');

    /* Lo que el servidor dejo marcado tras un envio con errores: hay que
       leerlo ANTES de descolgar los grupos, porque al sacarlos del select
       el valor seleccionado se pierde. */
    var munInicial = selectMun.value;

    var grupos = {};
    var todos  = selectMun.querySelectorAll('optgroup');
    if (!todos.length) return; // el HTML no trae la lista: no hay nada que filtrar

    Array.prototype.forEach.call(todos, function (g) {
        grupos[g.getAttribute('data-dep') || g.label] = g;
        selectMun.removeChild(g);
    });

    function poblarMunicipios(preseleccion) {
        var dep = selectDept.value;

        var visible = selectMun.querySelector('optgroup');
        if (visible) selectMun.removeChild(visible);

        /* Mismos textos que pinta el PHP: si no coinciden, el placeholder
           cambia de redaccion solo por tocar el departamento. */
        if (placeholder) {
            placeholder.textContent = dep
                ? '— Escoge tu municipio —'
                : 'Primero elige el departamento';
        }

        if (dep && grupos[dep]) selectMun.appendChild(grupos[dep]);

        /* Asignar un valor que ya no esta en la lista deja el select en '',
           que es justo lo que queremos al cambiar de departamento. */
        selectMun.value = preseleccion || '';
    }

    selectDept.addEventListener('change', function () { poblarMunicipios(''); });
    poblarMunicipios(munInicial);

    if (selectMun.value) mostrarETA(selectDept.value, selectMun.value);

    selectMun.addEventListener('change', function () {
        mostrarETA(selectDept.value, this.value);
    });
}

function calcDeliveryDays(dept, city) {
    /* Los nombres tienen que coincidir EXACTO con los de app/data/colombia.php,
       que es de donde salen los <option>. 'Bogotá D.C.' y 'Cartagena' no
       coincidian con 'Bogotá' y 'Cartagena de Indias', asi que las dos plazas
       mas grandes del pais mostraban 3 dias en vez de 2.
       Amazonas y Vichada ya no estan en la lista de departamentos con
       cobertura, asi que tampoco pintan nada aqui. */
    const express = new Set([
        'Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Bucaramanga',
        'Pereira', 'Manizales', 'Ibagué', 'Cúcuta', 'Villavicencio',
        'Cartagena de Indias', 'Santa Marta', 'Neiva', 'Armenia', 'Pasto',
        'Montería', 'Valledupar', 'Sincelejo', 'Popayán', 'Tunja',
    ]);
    const slow = new Set(['Chocó', 'Guainía', 'Vaupés', 'Putumayo', 'Caquetá']);

    if (express.has(city)) return 2;
    if (slow.has(dept)) return 5;
    return 3;
}

function addBusinessDays(days) {
    const date = new Date();
    let added = 0;
    while (added < days) {
        date.setDate(date.getDate() + 1);
        const dow = date.getDay();
        if (dow !== 0 && dow !== 6) added++;
    }
    return date;
}

function mostrarETA(dept, city) {
    const etaWrap = document.getElementById('deliveryETA');
    const etaDate = document.getElementById('deliveryETADate');
    if (!etaWrap || !etaDate || !city) {
        if (etaWrap) etaWrap.style.display = 'none';
        return;
    }
    const days = calcDeliveryDays(dept, city);
    const date = addBusinessDays(days);
    const days_es = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    etaDate.textContent = days_es[date.getDay()] + ' ' + date.getDate() + ' de ' + months[date.getMonth()];
    etaWrap.style.display = 'block';
}


/* ══════════════════════════════════════════════════════════════
   TIPO DE ENTREGA (domicilio / oficina)
   ══════════════════════════════════════════════════════════════ */
function initTipoEntrega() {
    const radiosEntrega  = document.querySelectorAll('input[name="tipo_entrega"]');
    const grupoDireccion = document.getElementById('grupo-direccion');
    const inputDireccion = document.getElementById('direccion');
    const grupoNota      = document.getElementById('grupo-nota-entrega');

    if (!radiosEntrega.length || !grupoDireccion || !inputDireccion) return;

    function actualizarDireccion() {
        let tipoSeleccionado = '';
        Array.prototype.forEach.call(radiosEntrega, r => { if (r.checked) tipoSeleccionado = r.value; });

        /* Mientras no haya elegido nada, la direccion se queda visible: es como
           llega del servidor y es lo que necesita quien no tenga JS. Solo se
           esconde cuando dice expresamente que recoge en oficina. */
        const esOficina = tipoSeleccionado === 'oficina';

        grupoDireccion.style.display = esOficina ? 'none' : 'block';
        if (esOficina) {
            inputDireccion.removeAttribute('required');
            inputDireccion.value = '';
        } else {
            inputDireccion.setAttribute('required', 'required');
        }

        /* La nota es para el mensajero: solo tiene sentido con domicilio.
           Quien recoge en oficina no tiene a quien dejarle una indicacion. */
        if (grupoNota) grupoNota.style.display = esOficina ? 'none' : 'block';
    }

    Array.prototype.forEach.call(radiosEntrega, r => r.addEventListener('change', actualizarDireccion));
    actualizarDireccion();
}


/* ══════════════════════════════════════════════════════════════
   SLIDER GENÉRICO (legacy)
   ══════════════════════════════════════════════════════════════ */
function initSlider() {
    const slider = document.getElementById('slider');
    const slides = document.getElementById('slides');

    if (!slider || !slides) return;

    const total = slides.children.length;
    if (!total) return;

    const perSlide = 100 / total;
    slides.style.width = `${total * 100}%`;

    Array.from(slides.children).forEach(child => {
        child.style.width = `${perSlide}%`;
        child.style.flexShrink = '0';
    });

    let index = 0;

    function goToSlide(i) {
        slides.style.transform = `translateX(-${perSlide * i}%)`;
    }

    setInterval(() => {
        index = (index + 1) % total;
        goToSlide(index);
    }, 3000);
}


/* ══════════════════════════════════════════════════════════════
   ACCORDION FAQ
   Compatible con clases .open y .active del CSS nuevo
   ══════════════════════════════════════════════════════════════ */
function initAccordion() {
    const items = document.querySelectorAll('.accordion-item');
    if (!items.length) return;

    Array.prototype.forEach.call(items, item => {
        const header = item.querySelector('.accordion-header');
        const body = item.querySelector('.accordion-body');
        if (!header || !body) return;

        header.addEventListener('click', () => {
            const isOpen = item.classList.contains('open');

            // Cerrar todos
            Array.prototype.forEach.call(items, i => {
                i.classList.remove('open');
                const b = i.querySelector('.accordion-body');
                if (b) {
                    b.classList.remove('active');
                    b.style.display = 'none';
                }
            });

            // Abrir el actual si estaba cerrado
            if (!isOpen) {
                item.classList.add('open');
                body.classList.add('active');
                body.style.display = 'block';
            }
        });
    });
}


/* ══════════════════════════════════════════════════════════════
   GALERÍA — swap + estado activo + swipe
   ══════════════════════════════════════════════════════════════ */
function initGallery() {
    const mainFigure = document.querySelector('.product-gallery__main');
    const mainImg    = document.querySelector('.product-gallery__main-img');
    const thumbs     = Array.from(document.querySelectorAll('.product-gallery__thumb'));

    if (!mainImg || !thumbs.length) return;

    // Modelo: la foto principal y cada miniatura son "casillas" — al elegir
    // una miniatura, intercambia su imagen con la que está en la casilla
    // principal (en vez de solo pisar la principal y perder esa foto).
    // Así las 4 fotos siguen siendo accesibles sin importar cuántas veces
    // se cambie de foto.
    let swipePointer = 0; // cicla 0..N-1 para saber qué miniatura toca en el próximo swipe

    function swapWithThumb(thumb) {
        const thumbImgEl = thumb.querySelector('img');
        if (!thumbImgEl) return;

        const mainSrc  = mainImg.src;
        const thumbSrc = thumbImgEl.getAttribute('src') || thumbImgEl.src;
        if (!thumbSrc || thumbSrc === mainSrc) return;

        mainImg.src = thumbSrc;
        thumbImgEl.src = mainSrc;
        thumb.dataset.src = mainSrc;
        thumb.setAttribute('data-src', mainSrc);

        stopThumbPulse();
    }

    // Permite que código externo (pills de color) reinicie el puntero de swipe
    // tras swap de DOM (p.ej. al cambiar de color)
    window.galleryRefresh = function () {
        swipePointer = 0;
    };

    // ── Pulse en miniaturas para invitar al usuario ────────────
    function stopThumbPulse() {
        thumbs.forEach(t => t.classList.remove('thumb-pulse'));
    }
    // Arrancar el pulse después de 1.2 s y detenerlo al primer clic
    setTimeout(() => {
        thumbs.forEach(t => t.classList.add('thumb-pulse'));
    }, 1200);

    // Click en miniatura
    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => swapWithThumb(thumb));
    });

    // ── Swipe en imagen principal ──────────────────────────────
    let swipeX = 0, swipeY = 0;

    mainFigure.addEventListener('touchstart', e => {
        swipeX = e.touches[0].clientX;
        swipeY = e.touches[0].clientY;
    }, { passive: true });

    mainFigure.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - swipeX;
        const dy = e.changedTouches[0].clientY - swipeY;
        if (Math.abs(dx) < 40 || Math.abs(dy) > Math.abs(dx) || !thumbs.length) return;
        swapWithThumb(thumbs[swipePointer]);
        swipePointer = (swipePointer + (dx < 0 ? 1 : -1) + thumbs.length) % thumbs.length;
    }, { passive: true });

    // Marcar imágenes loaded (skeleton CSS)
    Array.prototype.forEach.call(document.querySelectorAll('[data-product-gallery] img'), img => {
        if (img.complete) img.classList.add('loaded');
        else img.addEventListener('load', () => img.classList.add('loaded'));
    });
}


/* ══════════════════════════════════════════════════════════════
   PEDIDOS RECIENTES — número pseudo-dinámico
   Base aleatoria por sesión · sube 1 cada 45–90 s · tope 60
   ══════════════════════════════════════════════════════════════ */
function initRecentOrders() {
    const el = document.getElementById('recentOrdersCount');
    if (!el) return;

    const base = 28;
    const variance = Math.floor(Math.random() * 12); // 0–11
    let count = base + variance;

    el.textContent = count;

    function bump() {
        if (count < 60) {
            count++;
            el.textContent = count;
        }
        setTimeout(bump, 45000 + Math.random() * 45000);
    }

    setTimeout(bump, 45000 + Math.random() * 45000);
}


/* ══════════════════════════════════════════════════════════════
   ANIMACIONES DE ENTRADA (IntersectionObserver)
   Fade-up escalonado al hacer scroll sobre elementos clave
   ══════════════════════════════════════════════════════════════ */
function initScrollAnimations() {
    if (!('IntersectionObserver' in window)) return;

    const targets = document.querySelectorAll(
        '.benefit-item, .testimonial, .trust-strip__item, .section-title, .why-list li'
    );

    if (!targets.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeup');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -32px 0px' });

    Array.prototype.forEach.call(targets, el => {
        el.style.opacity = '0';
        observer.observe(el);
    });
}


/* ══════════════════════════════════════════════════════════════
   CTA STICKY — se oculta automáticamente cuando el form
   está visible en pantalla (usuario ya llegó al objetivo)
   ══════════════════════════════════════════════════════════════ */
function initStickyVisibility() {
    const sticky = document.querySelector('.cta-sticky-mobile');
    const form = document.getElementById('form-pedido');

    if (!sticky || !form) return;

    sticky.style.transition = 'transform 0.35s ease, opacity 0.35s ease';

    const observer = new IntersectionObserver((entries) => {
        const visible = entries[0].isIntersecting;
        sticky.style.transform = visible ? 'translateY(100%)' : 'translateY(0)';
        sticky.style.opacity = visible ? '0' : '1';

        /* Con la barra escondida, el espaciador que le reserva sitio deja un
           hueco vacío al final de la página. La clase lo colapsa. */
        document.body.classList.toggle('sticky-cta-oculto', visible);
    }, { threshold: 0.1 });

    observer.observe(form);
}


/* ══════════════════════════════════════════════════════════════
   LAZY LOADING — quita el skeleton CSS cuando la imagen carga
   ══════════════════════════════════════════════════════════════ */
function initLazyImages() {
    Array.prototype.forEach.call(document.querySelectorAll('img[loading="lazy"]'), img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', () => img.classList.add('loaded'));
        }
    });
}


/* initTelInput() vivia aqui y solo hacia dano: pisaba lo que el HTML ya
   declara bien en el campo de WhatsApp.
     autocomplete="tel-national" -> "tel"     inputmode="numeric" -> "tel"
   "tel" hace que el navegador autocomplete el numero internacional entero
   (+573001234567), que revienta contra la propia validacion ^3\d{9}$ del
   formulario y del servidor; "tel-national" es justo el que devuelve los 10
   digitos que se piden. El teclado de "numeric" tampoco trae + * # de sobra.
   El HTML manda: aqui no hay nada que arreglar en caliente. */

/* ══════════════════════════════════════════════════════════════
   FACEBOOK IAB — WhatsApp links
   El Facebook In-App Browser (Android) bloquea wa.me impidiendo
   que abra WhatsApp. Usamos intent:// en Android y whatsapp://
   en iOS para saltarnos esa intercepción.
   ══════════════════════════════════════════════════════════════ */
function initWaLinksIAB() {
    var ua = navigator.userAgent;
    if (!/FBAN|FBAV|FB_IAB|FB4A|FBIOS/i.test(ua)) return;

    var isAndroid = /Android/i.test(ua);

    Array.prototype.forEach.call(document.querySelectorAll('a[href*="wa.me"]'), function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var href = link.href;
            var m = href.match(/wa\.me\/([^?#]+)(?:\?text=([^#]*))?/);
            if (!m) { window.open(href, '_blank'); return; }

            var phone = m[1].replace(/\D/g, '');
            var text  = m[2] ? decodeURIComponent(m[2]) : '';

            if (isAndroid) {
                // intent:// le indica al OS de Android que abra el intent
                // directamente en WhatsApp, saltando la intercepción del IAB
                var intentUri = 'intent://send?phone=' + phone +
                    (text ? '&text=' + encodeURIComponent(text) : '') +
                    '#Intent;scheme=https;package=com.whatsapp;' +
                    'S.browser_fallback_url=' + encodeURIComponent('https://wa.me/' + phone + (text ? '?text=' + encodeURIComponent(text) : '')) + ';' +
                    'end';
                window.location.href = intentUri;
            } else {
                // iOS: a diferencia del intent:// de Android, whatsapp://
                // no trae un fallback nativo — si WhatsApp no está
                // instalado, el sistema muestra un error y ahí se acaba.
                // Si la página sigue visible pasado un instante es que no
                // hubo con qué abrirlo: se manda a wa.me, que sí funciona
                // siempre (WhatsApp Web o la ficha de la App Store).
                var waUrl = 'https://wa.me/' + phone + (text ? '?text=' + encodeURIComponent(text) : '');
                var yaSeFue = false;
                document.addEventListener('visibilitychange', function marcarSalida() {
                    if (document.hidden) yaSeFue = true;
                    document.removeEventListener('visibilitychange', marcarSalida);
                });
                setTimeout(function () {
                    if (!yaSeFue) window.location.href = waUrl;
                }, 1500);
                window.location.href = 'whatsapp://send?phone=' + phone +
                    (text ? '&text=' + encodeURIComponent(text) : '');
            }
        });
    });
}

/* ══════════════════════════════════════════════════════════════
   FACEBOOK + TIKTOK PIXEL EVENTS — embudos de conversión
   ══════════════════════════════════════════════════════════════ */
function initPixelEvents() {
    var hasFbq = typeof fbq === 'function';
    var hasTtq = typeof ttq !== 'undefined' && ttq;
    if (!hasFbq && !hasTtq) return;

    var productId = String(window.landingProductId || '');
    var addToCartFired = false;
    var initiateCheckoutFired = false;

    // Scroll depth: 25 / 50 / 75% — señal de audiencia para el algoritmo de Facebook
    var depthsFired = {};
    [25, 50, 75].forEach(function (pct) { depthsFired[pct] = false; });

    function onScroll() {
        var scrolled = window.scrollY + window.innerHeight;
        var total    = document.documentElement.scrollHeight;
        var pctReached = Math.floor((scrolled / total) * 100);

        [25, 50, 75].forEach(function (pct) {
            if (!depthsFired[pct] && pctReached >= pct) {
                depthsFired[pct] = true;
                if (hasFbq) {
                    fbq('trackCustom', 'ScrollDepth', {
                        depth:        pct,
                        content_name: window.landingProductName || '',
                        value:        window.landingProductPrice || 0,
                        currency:     'COP'
                    });
                }
                if (pct === 75) {
                    window.removeEventListener('scroll', onScroll);
                }
            }
        });
    }
    window.addEventListener('scroll', onScroll, { passive: true });

    // AddToCart: elegir color o tocar +/- de cantidad — intención real,
    // a diferencia de "tocó cualquier campo" (disparaba con solo escribir
    // el nombre). Delegado en el document porque "agregar otro color"
    // clona filas nuevas con sus propios botones .color-pill/.qty-btn.
    document.addEventListener('click', function (e) {
        if (addToCartFired || !e.target.closest) return;
        if (!e.target.closest('.color-pill, .qty-btn')) return;
        addToCartFired = true;
        if (hasFbq) {
            fbq('track', 'AddToCart', {
                value: window.landingProductPrice || 0,
                currency: 'COP',
                content_name: window.landingProductName || '',
            });
        }
        if (hasTtq) {
            ttq.track('AddToCart', {
                contents: [{
                    content_id:   productId,
                    content_type: 'product',
                    content_name: window.landingProductName || ''
                }],
                value:    window.landingProductPrice || 0,
                currency: 'COP'
            });
        }
    });

    // InitiateCheckout: clic real en un CTA hacia el formulario — antes
    // disparaba solo con que la sección entrara en pantalla, y eso lo
    // produce cualquiera que llegue al final haciendo scroll, con o sin
    // intención de comprar. El submit del form ya es el paso siguiente
    // (Lead/Purchase), así que un clic dentro de #formPedido no cuenta aquí.
    document.addEventListener('click', function (e) {
        if (initiateCheckoutFired || !e.target.closest) return;
        var cta = e.target.closest('a[href="#form-pedido"], .btn-primary');
        if (!cta || cta.closest('#formPedido')) return;
        initiateCheckoutFired = true;
        if (hasFbq) {
            fbq('track', 'InitiateCheckout', {
                value: window.landingProductPrice || 0,
                currency: 'COP',
                content_name: window.landingProductName || '',
            });
        }
        if (hasTtq) {
            ttq.track('InitiateCheckout', {
                contents: [{
                    content_id:   productId,
                    content_type: 'product',
                    content_name: window.landingProductName || ''
                }],
                value:    window.landingProductPrice || 0,
                currency: 'COP'
            });
        }
    });
}

/* ============================================================
   FORMULARIO — el envio del pedido vive en public/js/order-submit.js
   Se saco de aqui a proposito: es lo unico de la landing que no puede
   fallar, y en este archivo compartia suerte con los carruseles, la
   galeria y los videos. Un error de sintaxis en cualquiera de ellos
   dejaba el formulario sin manejador de envio.
   ============================================================ */

/* ══════════════════════════════════════════════════════════════
   CONTROLES DE VIDEO — tap para pausar + botón de volumen
   Un solo manejador delegado en el documento para TODOS los videos
   de la landing. Antes había dos copias casi idénticas (una en el
   script del hero y otra en el del carrusel de características) y el
   video de "Por qué te encantará" se quedaba sin controles: se
   reproducía mudo y sin forma de activarle el sonido ni de pausarlo.
   Al estar delegado también cubre los videos que aparezcan después,
   como los slides del carrusel.
   ══════════════════════════════════════════════════════════════ */
function initVideoControls() {
    document.addEventListener('click', function (e) {
        if (!e.target.closest) return;

        /* Silenciar / activar sonido */
        var volBtn = e.target.closest('.caract-vol-btn');
        if (volBtn) {
            e.stopPropagation();
            e.preventDefault();
            var volWrap = volBtn.closest('.caract-video-wrap');
            var volVid  = volWrap && volWrap.querySelector('video');
            if (!volVid) return;

            var activar = !volBtn.classList.contains('is-unmuted');
            volBtn.classList.toggle('is-unmuted', activar);
            volVid.muted = !activar;

            /* Si estaba pausado, activar el sonido también lo reanuda:
               nadie sube el volumen de un video quieto. */
            if (activar && volVid.paused) {
                volVid.play().catch(function () {});
                if (volWrap) volWrap.classList.remove('is-paused');
            }
            return;
        }

        /* Tocar el video para pausar o reanudar */
        var tap = e.target.closest('.caract-video-tap');
        if (!tap) return;

        var wrap  = tap.closest('.caract-video-wrap');
        var video = wrap && wrap.querySelector('video');
        if (!video) return;

        if (video.paused) {
            video.play().catch(function () {});
            wrap.classList.remove('is-paused');
        } else {
            video.pause();
            wrap.classList.add('is-paused');
        }
    });
}

/* ══════════════════════════════════════════════════════════════
   AUTOPLAY POR VIEWPORT — hero, "por qué te encantará" y carrusel
   de características pueden traer varios <video> a la vez en la
   misma página. Con el atributo autoplay los cuatro arrancan juntos
   al cargar y en Android de gama baja se agotan los decodificadores
   de hardware: algunos simplemente no arrancan, sin error. Aquí cada
   uno arranca solo al entrar en pantalla y se pausa al salir — nunca
   más de uno o dos reproduciéndose a la vez.
   Respeta la pausa manual: si el usuario lo detuvo con el tap
   (.is-paused, ver initVideoControls), no lo reanuda solo.
   ══════════════════════════════════════════════════════════════ */
function initVideoAutoplay() {
    if (!('IntersectionObserver' in window)) return;

    var wraps = document.querySelectorAll('.caract-video-wrap');
    if (!wraps.length) return;

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            var video = entry.target.querySelector('video');
            if (!video) return;

            if (entry.isIntersecting) {
                if (!entry.target.classList.contains('is-paused')) {
                    video.play().catch(function () {});
                }
            } else {
                video.pause();
            }
        });
    }, { threshold: 0.25 });

    Array.prototype.forEach.call(wraps, function (wrap) { observer.observe(wrap); });
}
