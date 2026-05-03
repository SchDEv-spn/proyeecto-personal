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
    initCountdown();
    initAccordion();
    initGallery();
    initRecentOrders();
    initScrollAnimations();
    initStickyVisibility();
    initLazyImages();
    initTelInput();
});


/* ══════════════════════════════════════════════════════════════
   DEPARTAMENTO / MUNICIPIO
   ══════════════════════════════════════════════════════════════ */
function initDepartamentoMunicipio() {
    const selectDept = document.getElementById('departamento');
    const selectMun  = document.getElementById('municipio');

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
            const oldMun  = selectMun.dataset.old  || '';

            departamentos.forEach(dep => {
                const opt       = document.createElement('option');
                opt.value       = dep.departamento;
                opt.textContent = dep.departamento;
                selectDept.appendChild(opt);
            });

            if (oldDept) selectDept.value = oldDept;

            function poblarMunicipios() {
                const deptSeleccionado = selectDept.value;
                selectMun.innerHTML = '';

                if (!deptSeleccionado) {
                    const ph       = document.createElement('option');
                    ph.value       = '';
                    ph.textContent = 'Selecciona primero un departamento';
                    selectMun.appendChild(ph);
                    return;
                }

                const ph       = document.createElement('option');
                ph.value       = '';
                ph.textContent = 'Selecciona un municipio';
                selectMun.appendChild(ph);

                const depObj     = departamentos.find(d => d.departamento === deptSeleccionado);
                const municipios = depObj && Array.isArray(depObj.ciudades) ? depObj.ciudades : [];

                municipios.forEach(m => {
                    const opt       = document.createElement('option');
                    opt.value       = m;
                    opt.textContent = m;
                    selectMun.appendChild(opt);
                });
            }

            selectDept.addEventListener('change', poblarMunicipios);
            poblarMunicipios();

            if (oldMun) selectMun.value = oldMun;
        })
        .catch(err => console.error('Error cargando departamentos/municipios:', err));
}


/* ══════════════════════════════════════════════════════════════
   TIPO DE ENTREGA (domicilio / oficina)
   ══════════════════════════════════════════════════════════════ */
function initTipoEntrega() {
    const radiosEntrega  = document.querySelectorAll('input[name="tipo_entrega"]');
    const grupoDireccion = document.getElementById('grupo-direccion');
    const inputDireccion = document.getElementById('direccion');

    if (!radiosEntrega.length || !grupoDireccion || !inputDireccion) return;

    function actualizarDireccion() {
        let tipoSeleccionado = '';
        radiosEntrega.forEach(r => { if (r.checked) tipoSeleccionado = r.value; });

        if (tipoSeleccionado === 'domicilio') {
            grupoDireccion.style.display = 'block';
            inputDireccion.setAttribute('required', 'required');
        } else {
            grupoDireccion.style.display = 'none';
            inputDireccion.removeAttribute('required');
            inputDireccion.value = '';
        }
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
        child.style.width      = `${perSlide}%`;
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
   COUNTDOWN — con persistencia sessionStorage
   El timer NO se reinicia si el usuario recarga la página.
   ══════════════════════════════════════════════════════════════ */
function initCountdown() {
    const el = document.getElementById('countdown-timer');
    if (!el) return;

    const DURATION   = 59 * 60 + 59; // 59:59
    const storageKey = 'landing_countdown_end';
    let   endTime    = parseInt(sessionStorage.getItem(storageKey) || '0', 10);

    // Si no existe o ya venció, crear uno nuevo
    if (!endTime || endTime < Date.now()) {
        endTime = Date.now() + DURATION * 1000;
        sessionStorage.setItem(storageKey, endTime.toString());
    }

    function tick() {
        const diff = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
        const mm   = String(Math.floor(diff / 60)).padStart(2, '0');
        const ss   = String(diff % 60).padStart(2, '0');
        el.textContent = mm + ':' + ss;

        if (diff > 0) {
            requestAnimationFrame(tick);
        } else {
            el.textContent = '00:00';
        }
    }

    requestAnimationFrame(tick);
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
        const body   = item.querySelector('.accordion-body');
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
    const mainImg    = document.querySelector('.product-gallery__main-img');
    const thumbs     = document.querySelectorAll('.product-gallery__thumb');

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

    const base     = 28;
    const variance = Math.floor(Math.random() * 12); // 0–11
    let   count    = base + variance;

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
    const form   = document.getElementById('form-pedido');

    if (!sticky || !form) return;

    sticky.style.transition = 'transform 0.35s ease, opacity 0.35s ease';

    const observer = new IntersectionObserver((entries) => {
        const visible = entries[0].isIntersecting;
        sticky.style.transform = visible ? 'translateY(100%)' : 'translateY(0)';
        sticky.style.opacity   = visible ? '0' : '1';
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

/* ============================================================
   MEJORA #2 — Stepper + submit AJAX
   ============================================================ */

(function () {

    const form       = document.getElementById('formPedido');
    const errorsBox  = document.getElementById('stepperErrors');
    const successBox = document.getElementById('stepperSuccess');
    if (!form) return;

    /* ---------- PASOS ---------- */
    const panels     = Array.from(form.querySelectorAll('.stepper-panel'));
    const steps      = Array.from(document.querySelectorAll('.stepper-step'));
    const connectors = Array.from(document.querySelectorAll('.stepper-connector'));

    let currentStep = 1;

    function goToStep(n) {
        panels.forEach(p => p.classList.remove('active'));
        const target = form.querySelector(`.stepper-panel[data-panel="${n}"]`);
        if (target) target.classList.add('active');

        steps.forEach((s, i) => {
            const num = i + 1;
            s.classList.remove('active', 'done');
            if (num === n)  s.classList.add('active');
            if (num < n)    s.classList.add('done');
        });

        connectors.forEach((c, i) => {
            c.classList.toggle('done', i + 1 < n);
        });

        currentStep = n;

        // Scroll suave al inicio del formulario
        const formTop = document.getElementById('form-pedido');
        if (formTop) formTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------- VALIDACIÓN POR PASO ---------- */
    function validarPaso(n) {
        const errors = [];

        if (n === 1) {
            const nombre    = form.querySelector('#nombre');
            const apellidos = form.querySelector('#apellidos');
            const telefono  = form.querySelector('#telefono');

            if (!nombre?.value.trim())    errors.push('El nombre es obligatorio.');
            if (!apellidos?.value.trim()) errors.push('Los apellidos son obligatorios.');

            const tel = telefono?.value.trim() ?? '';
            if (!tel)                        errors.push('El número de WhatsApp es obligatorio.');
            else if (!/^3\d{9}$/.test(tel))  errors.push('Ingresa un número válido (10 dígitos, empieza en 3).');
        }

        if (n === 2) {
            const mode = form.querySelector('#pricingMode')?.value ?? 'individual';

            if (mode === 'combo') {
                const blocks = form.querySelectorAll('.combo-block');
                if (!blocks.length) {
                    errors.push('Debes tener al menos 1 combo.');
                } else {
                    blocks.forEach((b, idx) => {
                        const selects = b.querySelectorAll('select.combo-color');
                        selects.forEach(s => {
                            if (!s.value) errors.push(`Selecciona el color del combo ${idx + 1}.`);
                        });
                    });
                }
            } else {
                // modo normal: al menos 1 fila con color seleccionado
                const colorSelects = form.querySelectorAll('select[name="color_item[]"]');
                if (colorSelects.length) {
                    let alguno = false;
                    colorSelects.forEach(s => { if (s.value) alguno = true; });
                    if (!alguno) errors.push('Selecciona al menos un color.');
                }
            }
        }

        if (n === 3) {
            const depto   = form.querySelector('#departamento');
            const muni    = form.querySelector('#municipio');
            const entrega = form.querySelector('input[name="tipo_entrega"]:checked');
            const dir     = form.querySelector('#direccion');
            const confirm = form.querySelector('#confirmPurchase');

            if (!depto?.value)    errors.push('Selecciona un departamento.');
            if (!muni?.value)     errors.push('Selecciona un municipio.');
            if (!entrega)         errors.push('Selecciona cómo quieres recibir tu pedido.');
            if (entrega?.value === 'domicilio' && !dir?.value.trim()) {
                errors.push('La dirección es obligatoria para envío a domicilio.');
            }
            if (!confirm?.checked) errors.push('Debes confirmar que quieres el producto.');
        }

        return errors;
    }

    function mostrarErrores(errors) {
        if (!errorsBox) return;
        if (!errors.length) { errorsBox.style.display = 'none'; return; }

        errorsBox.innerHTML = '<ul>' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
        errorsBox.style.display = 'block';
        errorsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /* ---------- NAVEGACIÓN ---------- */
    document.addEventListener('click', function (e) {
        // Siguiente
        const nextBtn = e.target.closest('.stepper-next');
        if (nextBtn && form.contains(nextBtn)) {
            const nextStep = parseInt(nextBtn.dataset.next, 10);
            const errors   = validarPaso(currentStep);
            if (errors.length) { mostrarErrores(errors); return; }
            mostrarErrores([]);
            goToStep(nextStep);
            return;
        }

        // Anterior
        const prevBtn = e.target.closest('.stepper-prev');
        if (prevBtn && form.contains(prevBtn)) {
            const prevStep = parseInt(prevBtn.dataset.prev, 10);
            mostrarErrores([]);
            goToStep(prevStep);
        }
    });

    /* ---------- SUBMIT AJAX ---------- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Validar paso 3 antes de enviar
        const errors = validarPaso(3);
        if (errors.length) { mostrarErrores(errors); return; }
        mostrarErrores([]);

        // Disparar el evento submit nativo para que pricing-combo.js
        // genere los color_item[] / qty_item[] ocultos
        const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
        // El submit de pricing-combo llama e.preventDefault() si hay error,
        // así que lo dejamos correr primero con un flag
        form._ajaxSubmit = true;

        const btnText    = document.getElementById('btnSubmitText');
        const btnSpinner = document.getElementById('btnSubmitSpinner');
        const btnSubmit  = document.getElementById('btnSubmit');

        btnSubmit.disabled   = true;
        if (btnText)    btnText.style.display    = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline';

        const data = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data,
        })
        .then(r => r.json())
        .then(function (res) {
            btnSubmit.disabled = false;
            if (btnText)    btnText.style.display    = 'inline';
            if (btnSpinner) btnSpinner.style.display = 'none';

            if (res.ok) {
                // Ocultar form, mostrar éxito
                form.style.display      = 'none';
                document.querySelector('.stepper-header').style.display = 'none';
                if (successBox) successBox.style.display = 'block';
                successBox.scrollIntoView({ behavior: 'smooth', block: 'start' });

                // Pixel: Purchase
                if (typeof fbq === 'function') {
                    fbq('track', 'Purchase', {
                        value:    window.landingProductPrice || 0,
                        currency: 'COP',
                    });
                }
            } else {
                // Errores del backend
                mostrarErrores(res.errores || ['Ocurrió un error. Inténtalo de nuevo.']);
                goToStep(1); // Volver al paso 1 si hay error de validación del servidor
            }
        })
        .catch(function () {
            btnSubmit.disabled = false;
            if (btnText)    btnText.style.display    = 'inline';
            if (btnSpinner) btnSpinner.style.display = 'none';
            mostrarErrores(['Error de conexión. Verifica tu internet e inténtalo de nuevo.']);
        });
    });

    /* ---------- INIT ---------- */
    goToStep(1);

})();