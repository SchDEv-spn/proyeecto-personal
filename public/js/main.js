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
 * ─────────────────────────────────────────────────────────────
 */

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
});


/* ══════════════════════════════════════════════════════════════
   DEPARTAMENTO / MUNICIPIO
   ══════════════════════════════════════════════════════════════ */
function initDepartamentoMunicipio() {
    const selectDept = document.getElementById('departamento');
    const selectMun = document.getElementById('municipio');

    if (!selectDept || !selectMun) return;

    fetch('/tienda_mvc/public/js/colombia.json')
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
   GALERÍA — swap suave + estado activo + zoom lightbox
   ══════════════════════════════════════════════════════════════ */
function initGallery() {
    const mainFigure = document.querySelector('.product-gallery__main');
    const mainImg = document.querySelector('.product-gallery__main-img');
    const thumbs = document.querySelectorAll('.product-gallery__thumb');

    if (!mainImg || !thumbs.length) return;

    // Estado inicial: primera miniatura activa
    thumbs[0].classList.add('active', 'is-active');
    const firstThumbImg = thumbs[0].querySelector('img');
    if (firstThumbImg) firstThumbImg.style.opacity = '1';

    // Swap al hacer clic en miniatura
    thumbs.forEach(thumb => {
        thumb.addEventListener('click', function () {
            // Resetear todas
            thumbs.forEach(t => {
                t.classList.remove('active', 'is-active');
                const tImg = t.querySelector('img');
                if (tImg) tImg.style.opacity = '0.7';
            });

            // Activar la seleccionada
            this.classList.add('active', 'is-active');
            const activeThumbImg = this.querySelector('img');
            if (activeThumbImg) activeThumbImg.style.opacity = '1';

            // Cambiar imagen principal — micro-transición 150ms
            const newSrc = this.dataset.src || this.getAttribute('data-src');
            if (!newSrc) return;

            mainImg.style.opacity = '0.5';
            setTimeout(() => {
                mainImg.src = newSrc;
                mainImg.style.opacity = '1';
            }, 150);
        });
    });

    // Zoom lightbox al tap en imagen principal
    if (mainFigure) {
        mainFigure.addEventListener('click', function (e) {
            // Evitar que el tap en una miniatura también dispare el zoom
            if (e.target.closest('.product-gallery__thumb')) return;
            mainFigure.classList.toggle('zoomed');
            document.body.style.overflow = mainFigure.classList.contains('zoomed')
                ? 'hidden'
                : '';
        });
    }

    // Cerrar zoom con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && mainFigure && mainFigure.classList.contains('zoomed')) {
            mainFigure.classList.remove('zoomed');
            document.body.style.overflow = '';
        }
    });

    // Marcar imágenes loaded (skeleton CSS)
    document.querySelectorAll('[data-product-gallery] img').forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', () => img.classList.add('loaded'));
        }
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
        errorsBox.innerHTML = '<ul>' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
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

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        })
            .then(r => r.json())
            .then(function (res) {
                btnSubmit.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnSpinner) btnSpinner.style.display = 'none';

                if (res.ok) {
                    form.style.display = 'none';
                    if (successBox) {
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