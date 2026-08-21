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
 *   · initTelInput              — inputmode="tel" en WhatsApp
 *   · initWaLinksIAB            — WhatsApp links en Facebook IAB
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

document.addEventListener('DOMContentLoaded', function () {
    initTipoEntrega();
    initDepartamentoMunicipio();
    initSlider();
    initAccordion();
    initGallery();
    initRecentOrders();
    initScrollAnimations();
    initStickyVisibility();
    initLazyImages();
    initTelInput();
    initPixelEvents();
    initWaLinksIAB();
    initVideoControls();
    initTickerLectura();
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

    tickers.forEach(function (ticker) {
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
function initDepartamentoMunicipio() {
    const selectDept = document.getElementById('departamento');
    const selectMun = document.getElementById('municipio');

    if (!selectDept || !selectMun) return;

    fetchWithTimeout((window.BASE_URL||'')+'/public/js/colombia.json', {}, 10000)
        .then(response => {
            if (!response.ok) throw new Error('No se pudo cargar colombia.json');
            return response.json();
        })
        .then(data => {
            const excluidos = [
                'Amazonas',
                'Guaviare',
                'Vichada',
                'San Andrés y Providencia',
                'San Andres y Providencia',
                'San Andrés Islas',
                'San Andres Islas'
            ];

            const departamentos = data.filter(d => !excluidos.includes(d.departamento));

            const oldDept = selectDept.dataset.old || '';
            const oldMun = selectMun.dataset.old || '';

            departamentos.forEach(dep => {
                const opt = document.createElement('option');
                opt.value = dep.departamento;
                opt.textContent = dep.departamento;
                selectDept.appendChild(opt);
            });

            if (oldDept) selectDept.value = oldDept;

            function poblarMunicipios() {
                const deptSeleccionado = selectDept.value;
                selectMun.innerHTML = '';

                if (!deptSeleccionado) {
                    const ph = document.createElement('option');
                    ph.value = '';
                    ph.textContent = 'Selecciona primero un departamento';
                    selectMun.appendChild(ph);
                    return;
                }

                const ph = document.createElement('option');
                ph.value = '';
                ph.textContent = 'Selecciona un municipio';
                selectMun.appendChild(ph);

                const depObj = departamentos.find(d => d.departamento === deptSeleccionado);
                const municipios = depObj && Array.isArray(depObj.ciudades) ? depObj.ciudades : [];

                municipios.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    selectMun.appendChild(opt);
                });
            }

            selectDept.addEventListener('change', poblarMunicipios);
            poblarMunicipios();

            if (oldMun) {
                selectMun.value = oldMun;
                mostrarETA(selectDept.value, oldMun);
            }

            selectMun.addEventListener('change', function () {
                mostrarETA(selectDept.value, this.value);
            });
        })
        .catch(err => console.error('Error cargando departamentos/municipios:', err));
}

function calcDeliveryDays(dept, city) {
    const express = new Set([
        'Bogotá D.C.', 'Medellín', 'Cali', 'Barranquilla', 'Bucaramanga',
        'Pereira', 'Manizales', 'Ibagué', 'Cúcuta', 'Villavicencio',
        'Cartagena', 'Santa Marta', 'Neiva', 'Armenia', 'Pasto',
        'Montería', 'Valledupar', 'Sincelejo', 'Popayán', 'Tunja',
    ]);
    const slow = new Set(['Amazonas', 'Chocó', 'Guainía', 'Vaupés', 'Vichada', 'Putumayo', 'Caquetá']);

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
        radiosEntrega.forEach(r => { if (r.checked) tipoSeleccionado = r.value; });

        const esDomicilio = tipoSeleccionado === 'domicilio';

        grupoDireccion.style.display = esDomicilio ? 'block' : 'none';
        if (esDomicilio) { inputDireccion.setAttribute('required', 'required'); } else { inputDireccion.removeAttribute('required'); }
        if (!esDomicilio) inputDireccion.value = '';

        if (grupoNota) grupoNota.style.display = esDomicilio ? 'block' : 'none';
    }

    radiosEntrega.forEach(r => r.addEventListener('change', actualizarDireccion));
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

    items.forEach(item => {
        const header = item.querySelector('.accordion-header');
        const body = item.querySelector('.accordion-body');
        if (!header || !body) return;

        header.addEventListener('click', () => {
            const isOpen = item.classList.contains('open');

            // Cerrar todos
            items.forEach(i => {
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
    document.querySelectorAll('[data-product-gallery] img').forEach(img => {
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

    targets.forEach(el => {
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
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', () => img.classList.add('loaded'));
        }
    });
}


/* ══════════════════════════════════════════════════════════════
   TEL INPUT — teclado numérico en campo WhatsApp (mobile)
   ══════════════════════════════════════════════════════════════ */
function initTelInput() {
    const tel = document.getElementById('telefono');
    if (!tel) return;
    tel.setAttribute('inputmode', 'tel');
    tel.setAttribute('autocomplete', 'tel');
}

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

    document.querySelectorAll('a[href*="wa.me"]').forEach(function (link) {
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
                // iOS: whatsapp:// funciona dentro del Facebook IAB de iOS
                window.location.href = 'whatsapp://send?phone=' + phone +
                    (text ? '&text=' + encodeURIComponent(text) : '');
            }
        });
    });
}

/* ══════════════════════════════════════════════════════════════
   FACEBOOK PIXEL EVENTS — embudos de conversión
   ══════════════════════════════════════════════════════════════ */
function initPixelEvents() {
    if (typeof fbq !== 'function') return;

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
                fbq('trackCustom', 'ScrollDepth', {
                    depth:        pct,
                    content_name: window.landingProductName || '',
                    value:        window.landingProductPrice || 0,
                    currency:     'COP'
                });
                if (pct === 75) {
                    window.removeEventListener('scroll', onScroll);
                }
            }
        });
    }
    window.addEventListener('scroll', onScroll, { passive: true });

    // AddToCart: primera vez que el usuario toca cualquier campo del form
    var form = document.getElementById('formPedido');
    if (form) {
        var firstInputs = form.querySelectorAll('input, select, textarea');
        firstInputs.forEach(function (el) {
            el.addEventListener('focus', function onFirstFocus() {
                if (addToCartFired) return;
                addToCartFired = true;
                fbq('track', 'AddToCart', {
                    value: window.landingProductPrice || 0,
                    currency: 'COP',
                    content_name: window.landingProductName || '',
                });
                firstInputs.forEach(function (inp) {
                    inp.removeEventListener('focus', onFirstFocus);
                });
            }, { once: true });
        });
    }

    // InitiateCheckout: sección del form entra en viewport
    var formSection = document.getElementById('form-pedido');
    if (formSection && 'IntersectionObserver' in window) {
        var checkoutObserver = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting && !initiateCheckoutFired) {
                initiateCheckoutFired = true;
                fbq('track', 'InitiateCheckout', {
                    value: window.landingProductPrice || 0,
                    currency: 'COP',
                    content_name: window.landingProductName || '',
                });
                checkoutObserver.disconnect();
            }
        }, { threshold: 0.3 });
        checkoutObserver.observe(formSection);
    }
}

/* ============================================================
   FORMULARIO — validación única + submit AJAX
   ============================================================ */
(function () {
    const form = document.getElementById('formPedido');
    const errorsBox = document.getElementById('stepperErrors');
    const successBox = document.getElementById('stepperSuccess');
    if (!form) return;

    function validar() {
        const errors = [];

        const nombre = form.querySelector('#nombre');
        const apellidos = form.querySelector('#apellidos');
        const telefono = form.querySelector('#telefono');
        const depto = form.querySelector('#departamento');
        const muni = form.querySelector('#municipio');
        const entrega = form.querySelector('input[name="tipo_entrega"]:checked');
        const dir = form.querySelector('#direccion');

        if (!nombre?.value.trim()) errors.push('El nombre es obligatorio.');
        if (!apellidos?.value.trim()) errors.push('Los apellidos son obligatorios.');

        const tel = telefono?.value.trim() ?? '';
        if (!tel) errors.push('El número de WhatsApp es obligatorio.');
        else if (!/^3\d{9}$/.test(tel)) errors.push('Número inválido (10 dígitos, empieza en 3).');

        const mode = form.querySelector('#pricingMode')?.value ?? 'individual';
        if (mode === 'combo') {
            const blocks = form.querySelectorAll('.combo-block');
            if (!blocks.length) errors.push('Debes tener al menos 1 combo.');
            else blocks.forEach((b, idx) => {
                b.querySelectorAll('select.combo-color').forEach(s => {
                    if (!s.value) errors.push(`Selecciona el color del combo ${idx + 1}.`);
                });
            });
        } else {
            const colorSelects = form.querySelectorAll('select[name="color_item[]"]');
            if (colorSelects.length) {
                let alguno = false;
                colorSelects.forEach(s => { if (s.value) alguno = true; });
                if (!alguno) errors.push('Selecciona al menos un color.');
            }
        }

        if (!depto?.value) errors.push('Selecciona un departamento.');
        if (!muni?.value) errors.push('Selecciona un municipio.');
        if (!entrega) errors.push('Selecciona cómo quieres recibir tu pedido.');
        if (entrega?.value === 'domicilio' && !dir?.value.trim())
            errors.push('La dirección es obligatoria para envío a domicilio.');

        return errors;
    }

    function mostrarErrores(errors) {
        if (!errorsBox) return;
        if (!errors.length) { errorsBox.style.display = 'none'; return; }
        const ul = document.createElement('ul');
        errors.forEach(e => {
            const li = document.createElement('li');
            li.textContent = e;
            ul.appendChild(li);
        });
        errorsBox.replaceChildren(ul);
        errorsBox.style.display = 'block';
        errorsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const errors = validar();
        if (errors.length) { mostrarErrores(errors); return; }
        mostrarErrores([]);

        const btnText = document.getElementById('btnSubmitText');
        const btnSpinner = document.getElementById('btnSubmitSpinner');
        const btnSubmit = document.getElementById('btnSubmit');

        btnSubmit.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline';

        fetchWithTimeout(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        }, 15000)
            .then(r => r.json())
            .then(function (res) {
                btnSubmit.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnSpinner) btnSpinner.style.display = 'none';

                if (res.ok) {
                    form.style.display = 'none';
                    if (successBox) {
                        if (res.pedido_id) {
                            const numEl = document.getElementById('orderSuccessNum');
                            const valEl = document.getElementById('orderSuccessNumVal');
                            if (numEl && valEl) { valEl.textContent = '#' + res.pedido_id; numEl.style.display = ''; }
                        }
                        successBox.style.display = 'block';
                        successBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    if (typeof fbq === 'function') {
                        // ✅ Lead — cliente potencial (pedido contraentrega registrado)
                        fbq('track', 'Lead', {
                            value: res.precio_total || window.landingProductPrice || 0,
                            currency: 'COP',
                            content_name: window.landingProductName || '',
                        });
                        // ✅ Purchase — conversión de venta
                        fbq('track', 'Purchase', {
                            value: res.precio_total || window.landingProductPrice || 0,
                            currency: 'COP',
                            content_name: window.landingProductName || '',
                            content_ids: [String(res.pedido_id || '')],
                            content_type: 'product',
                            num_items: 1,
                        });
                    }
                } else {
                    mostrarErrores(res.errores && res.errores.length
                        ? res.errores
                        : ['No pudimos registrar tu pedido. Inténtalo de nuevo o escríbenos por WhatsApp.']);
                }
            })
            .catch(function () {
                btnSubmit.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnSpinner) btnSpinner.style.display = 'none';
                mostrarErrores(['Error de conexión. Verifica tu internet e inténtalo de nuevo.']);
            });
    });
})();

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
