/* Facebook IAB y algunos webviews bloquean sessionStorage —
   estos helpers absorben el error silenciosamente. */
function ssGet(key) {
    try { return sessionStorage.getItem(key); } catch (e) { return null; }
}
function ssSet(key, val) {
    try { sessionStorage.setItem(key, String(val)); } catch (e) {}
}

/* ============================================================
   CARRUSEL DE TESTIMONIOS — CSS Scroll Snap nativo
   El navegador maneja el deslizamiento; el JS solo sincroniza
   los dots y maneja los clicks en cards/flechas.
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.testimonials-slider-container');
  if (!container) return;

  const slides = Array.from(container.querySelectorAll('.testimonial-slide'));
  const dots   = Array.from(document.querySelectorAll('.dot'));
  if (!slides.length) return;

  let current = 0;

  // ── Scroll centrado en un slide ──────────────────────────
  function goTo(i, smooth = true) {
    const slide = slides[i];
    if (!slide) return;
    const cRect = container.getBoundingClientRect();
    const sRect = slide.getBoundingClientRect();
    const target = container.scrollLeft
                 + (sRect.left - cRect.left)
                 - (cRect.width - sRect.width) / 2;
    container.scrollTo({ left: Math.max(0, target), behavior: smooth ? 'smooth' : 'instant' });
  }

  // ── Sincronización visual ────────────────────────────────
  function activate(i) {
    current = i;
    slides.forEach((s, idx) => s.classList.toggle('is-active', idx === i));
    dots.forEach(d => d.classList.toggle('active', d.dataset.dot === String(i)));
  }

  // ── IntersectionObserver: detecta qué slide está centrado ─
  const io = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
        const i = slides.indexOf(entry.target);
        if (i >= 0 && i !== current) activate(i);
      }
    });
  }, { root: container, threshold: 0.6 });

  slides.forEach(s => io.observe(s));

  // ── Init ─────────────────────────────────────────────────
  activate(0);
  goTo(0, false);

  // ── Dots ─────────────────────────────────────────────────
  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const i = parseInt(dot.dataset.dot, 10);
      if (Number.isFinite(i)) goTo(i);
    });
  });

  // ── Flechas (desktop) ────────────────────────────────────
  const outer = container.closest('.testimonials-slider-outer');
  outer?.querySelector('.slider-arrow--prev')?.addEventListener('click', () => goTo(Math.max(0, current - 1)));
  outer?.querySelector('.slider-arrow--next')?.addEventListener('click', () => goTo(Math.min(slides.length - 1, current + 1)));

  // ── Tocar card no activo (peek) navega a él ──────────────
  slides.forEach((slide, i) => {
    slide.addEventListener('click', () => {
      if (i !== current) goTo(i);
    });
  });
});

/* ============================================================
   MEJORA #1 — Urgencia hero: countdown sesión + viewers fake
   ============================================================ */

(function () {

    /* ---------- 1. COUNTDOWN PERSISTENTE POR SESIÓN ----------
       Dura 25 minutos desde la primera visita.
       Si el usuario recarga, retoma donde quedó.
       Si la sesión expira (cierra pestaña y vuelve después), reinicia.
    ---------------------------------------------------------- */
    const SESSION_KEY = 'lp_timer_end';

    function initCountdown() {
        // Lee data-minutes del primer elemento countdown disponible
        const sourceEl      = document.getElementById('heroCountdown')
                           || document.getElementById('countdown-timer');
        const configMinutes = sourceEl ? (parseInt(sourceEl.dataset.minutes, 10) || 25) : 25;
        const DURATION_MS   = configMinutes * 60 * 1000;

        let endTime = parseInt(ssGet(SESSION_KEY), 10);
        if (!endTime || endTime <= Date.now()) {
            endTime = Date.now() + DURATION_MS;
            ssSet(SESSION_KEY, endTime);
        }

        // Un solo interval actualiza TODOS los displays de countdown
        const displayIds = ['heroCountdown', 'countdown-timer', 'formCountdown'];

        function tick() {
            let remaining = endTime - Date.now();
            if (remaining <= 0) {
                endTime = Date.now() + DURATION_MS;
                ssSet(SESSION_KEY, endTime);
                remaining = DURATION_MS;
            }
            const m    = Math.floor(remaining / 60000);
            const s    = Math.floor((remaining % 60000) / 1000);
            const text = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            displayIds.forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.textContent = text;
            });
        }

        tick();
        setInterval(tick, 1000);
    }


    /* ---------- 2. STOCK SIMULADO CON DECAIMIENTO ----------
       Empieza en 7, puede bajar lentamente durante la visita.
       Persiste en sessionStorage para que no "suba" al navegar.
    ---------------------------------------------------------- */
    const STOCK_KEY  = 'lp_stock';

    function initStock() {
        const el = document.getElementById('stockCount');
        if (!el) return;

        const STOCK_BASE = parseInt(el.dataset.stock, 10) || 12;

        let stock = parseInt(ssGet(STOCK_KEY), 10);
        if (!stock || stock > STOCK_BASE) {
            stock = STOCK_BASE;
            ssSet(STOCK_KEY, stock);
        }

        el.textContent = stock;

        // Marcar como crítico si quedan ≤ 3
        const bar = el.closest('.urgency-stock');
        if (bar && stock <= 3) bar.classList.add('critical');

        // Bajar el stock 1 unidad después de 90–150 seg en página
        const delay = (90 + Math.floor(Math.random() * 60)) * 1000;
        setTimeout(function () {
            if (stock > 1) {
                stock--;
                ssSet(STOCK_KEY, stock);
                el.textContent = stock;
                if (bar && stock <= 3) bar.classList.add('critical');
            }
        }, delay);
    }


    /* ---------- 3. VIEWERS SIMULADOS ----------
       Número aleatorio entre 8 y 24, cambia sutilmente cada 20–40 seg.
       Muy común en tiendas como Shopify, Amazon, etc.
    ---------------------------------------------------------- */
    function initViewers() {
        const el = document.getElementById('viewersCount');
        if (!el) return;

        let current = 8 + Math.floor(Math.random() * 17); // 8–24
        el.textContent = current;

        function randomStep() {
            // sube o baja 1-2, mantiene rango 5–28
            const delta = Math.floor(Math.random() * 3) - 1; // -1, 0, +1
            current = Math.min(28, Math.max(5, current + delta));
            el.textContent = current;

            // próxima actualización: 20–40 segundos
            setTimeout(randomStep, (20 + Math.floor(Math.random() * 20)) * 1000);
        }

        // Primera actualización a los 15–25 seg
        setTimeout(randomStep, (15 + Math.floor(Math.random() * 10)) * 1000);
    }


    /* ---------- INIT ---------- */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initCountdown();
            initStock();
            initViewers();
        });
    } else {
        initCountdown();
        initStock();
        initViewers();
    }

})();

/* ============================================================
   MEJORA #3 — FOMO Notifications + Social Proof counter
   ============================================================ */

(function () {

    /* ---------- 1. FOMO TOASTS ----------
       Muestra notificaciones bottom-left con compras recientes simuladas.
       Primera aparece entre 8-15 seg, luego cada 18-30 seg.
    ---------------------------------------------------------- */
    const FOMO_NAMES = [
        'María G.', 'Carlos R.', 'Ana M.', 'Pedro L.', 'Laura S.',
        'Jorge R.', 'Daniela T.', 'Andrés G.', 'Valentina R.', 'Sebastián V.',
        'Carolina D.', 'Camila O.', 'Felipe H.', 'Natalia C.', 'Diego B.',
        'Paola F.', 'Santiago M.', 'Juliana A.', 'Esteban N.', 'Verónica P.'
    ];

    const FOMO_CITIES = [
        'Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Bucaramanga',
        'Cartagena', 'Pereira', 'Manizales', 'Ibagué', 'Villavicencio',
        'Cúcuta', 'Santa Marta', 'Pasto', 'Neiva', 'Armenia'
    ];

    const FOMO_ACTIONS = [
        'acaba de hacer su pedido',
        'acaba de confirmar su compra',
        'acaba de realizar su pedido',
        'acaba de pedir uno',
    ];

    const FOMO_TIMES = [
        'hace un momento', 'hace 2 min', 'hace 3 min', 'hace 5 min',
        'hace 7 min', 'hace 10 min', 'hace 12 min', 'hace 15 min'
    ];

    function rand(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    function showFomoToast() {
        const container = document.getElementById('fomoContainer');
        if (!container) return;

        const name   = rand(FOMO_NAMES);
        const city   = rand(FOMO_CITIES);
        const action = rand(FOMO_ACTIONS);
        const time   = rand(FOMO_TIMES);

        const toast = document.createElement('div');
        toast.className = 'fomo-toast';
        toast.innerHTML =
            '<div class="fomo-toast__icon" aria-hidden="true">🛒</div>' +
            '<div class="fomo-toast__text">' +
            '  <strong>' + name + '</strong> de ' + city + '<br>' +
            '  ' + action +
            '  <span class="fomo-toast__time">' + time + '</span>' +
            '</div>';

        container.innerHTML = '';
        container.appendChild(toast);

        // Ocultar después de 5 segundos
        setTimeout(function () {
            toast.classList.add('hiding');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 450);
        }, 5000);

        // Siguiente notificación en 18-30 seg
        var next = (18 + Math.floor(Math.random() * 12)) * 1000;
        setTimeout(showFomoToast, next);
    }

    /* ---------- 2. SOCIAL PROOF COUNTER ----------
       Muestra un contador de pedidos cerca del formulario.
       Empieza en un número base y sube 1 cada 2-4 minutos.
    ---------------------------------------------------------- */
    function initFormOrderCount() {
        var el = document.getElementById('formOrderCount');
        if (!el) return;

        // Lee el conteo real del servidor (data-base); mínimo 12
        var base  = parseInt(el.dataset.base, 10) || 12;
        var count = base;
        el.textContent = count;

        function increment() {
            count++;
            el.textContent = count;
            var delay = (90 + Math.floor(Math.random() * 90)) * 1000;
            setTimeout(increment, delay);
        }

        // Primera subida: 90-180 segundos después
        var first = (90 + Math.floor(Math.random() * 90)) * 1000;
        setTimeout(increment, first);
    }

    /* ---------- INIT ---------- */
    function init() {
        // FOMO: primera notificación entre 8-15 seg
        var firstFomo = (8 + Math.floor(Math.random() * 7)) * 1000;
        setTimeout(showFomoToast, firstFomo);

        initFormOrderCount();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/* ============================================================
   MEJORA #4 — Sticky Price Bar
   ============================================================ */
(function () {
    function init() {
        var bar  = document.getElementById('stickyPriceBar');
        var hero = document.querySelector('.hero');
        if (!bar || !hero) return;

        // Posicionar debajo del announcement bar
        var announcementBar = document.querySelector('.announcement-bar');
        if (announcementBar) {
            bar.style.top = announcementBar.offsetHeight + 'px';
        }

        var observer = new IntersectionObserver(function (entries) {
            var heroVisible = entries[0].isIntersecting;
            bar.classList.toggle('is-visible', !heroVisible);
            bar.setAttribute('aria-hidden', heroVisible ? 'true' : 'false');
        }, { threshold: 0.1 });

        observer.observe(hero);

        // Cerrar al click en CTA
        var cta = bar.querySelector('.sticky-price-bar__cta');
        if (cta) {
            cta.addEventListener('click', function () {
                bar.classList.remove('is-visible');
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* ============================================================
   MEJORA #4 — Exit Intent Popup
   ============================================================ */
(function () {
    var SESSION_KEY_SHOWN = 'lp_exit_shown';
    var POPUP_DURATION_MS = 3 * 60 * 1000; // 3 minutos
    var popupShown = false;

    function init() {
        var popup   = document.getElementById('exitPopup');
        var backdrop= document.getElementById('exitPopupBackdrop');
        var closeBtn= document.getElementById('exitPopupClose');
        var dismiss = document.getElementById('exitPopupDismiss');
        var cta     = document.getElementById('exitPopupCta');
        var timerEl = document.getElementById('exitCountdown');

        if (!popup) return;

        // No mostrar si ya se mostró en esta sesión
        if (ssGet(SESSION_KEY_SHOWN)) return;

        function showPopup() {
            if (popupShown) return;
            popupShown = true;
            ssSet(SESSION_KEY_SHOWN, '1');
            popup.removeAttribute('hidden');
            document.body.style.overflow = 'hidden';
            startExitCountdown();
        }

        function hidePopup() {
            popup.setAttribute('hidden', '');
            document.body.style.overflow = '';
        }

        function startExitCountdown() {
            if (!timerEl) return;
            var endTime = Date.now() + POPUP_DURATION_MS;

            function tick() {
                var rem = endTime - Date.now();
                if (rem <= 0) {
                    timerEl.textContent = '00:00';
                    return;
                }
                var m = Math.floor(rem / 60000);
                var s = Math.floor((rem % 60000) / 1000);
                timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                setTimeout(tick, 1000);
            }
            tick();
        }

        // ── DESKTOP: cursor sale por arriba ──────────────────
        document.addEventListener('mouseleave', function (e) {
            if (e.clientY < 5) showPopup();
        });

        // ── MOBILE: inactividad 40 segundos (Clarity muestra salidas antes de 60s) ──
        var mobileTimer = null;
        var isMobile = window.matchMedia('(max-width: 900px)').matches;

        function resetMobileTimer() {
            clearTimeout(mobileTimer);
            mobileTimer = setTimeout(showPopup, 40000);
        }

        if (isMobile) {
            ['touchstart', 'scroll'].forEach(function (ev) {
                document.addEventListener(ev, resetMobileTimer, { passive: true });
            });
            resetMobileTimer();
        }

        // ── Cerrar ──────────────────────────────────────────
        if (backdrop) backdrop.addEventListener('click', hidePopup);
        if (closeBtn) closeBtn.addEventListener('click', hidePopup);
        if (dismiss)  dismiss.addEventListener('click', hidePopup);
        if (cta) {
            cta.addEventListener('click', function () {
                hidePopup();
                // Pequeño delay para que el scroll sea suave
                setTimeout(function () {
                    var form = document.getElementById('form-pedido');
                    if (form) form.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hidePopup();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* ============================================================
   MEJORA #4 — Antes / Después overlay reveal slider
   Usa clip-path en lugar de width para que ambas imágenes
   siempre ocupen el frame completo sin distorsión.
   ============================================================ */
(function () {
    function init() {
        var slider    = document.getElementById('adSlider');
        var divider   = document.getElementById('adDivider');
        var despuesEl = document.querySelector('.despues-img');
        if (!slider || !divider || !despuesEl) return;

        function update(val) {
            var right = (100 - val) + '%';
            despuesEl.style.clipPath = 'inset(0 ' + right + ' 0 0)';
            divider.style.left       = val + '%';
        }

        slider.addEventListener('input', function () {
            update(parseFloat(this.value));
        });

        update(50);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* ============================================================
   MEJORA #6A — Sticky mobile CTA: ocultar en hero y en form
   ============================================================ */
(function () {
    var bar = document.querySelector('.cta-sticky-mobile');
    if (!bar) return;

    var heroVisible = true;
    var formVisible = false;

    function update() {
        const shouldHide = heroVisible || formVisible;
        bar.classList.toggle('is-hidden', shouldHide);
        document.body.classList.toggle('sticky-cta-visible', !shouldHide);
    }

    var hero = document.querySelector('.hero');
    var form = document.getElementById('form-pedido');

    if (hero) {
        new IntersectionObserver(function (entries) {
            heroVisible = entries[0].isIntersecting;
            update();
        }, { threshold: 0.05 }).observe(hero);
    }

    if (form) {
        new IntersectionObserver(function (entries) {
            formVisible = entries[0].isIntersecting;
            update();
        }, { threshold: 0.05 }).observe(form);
    }

    update();
})();

/* ============================================================
   MEJORA #6C — Scroll reveal: fade+slide al entrar en viewport
   Las clases se añaden por JS para que sin JS todo sea visible.
   ============================================================ */
(function () {
    if (!('IntersectionObserver' in window)) return;

    function addReveal(selector, stagger) {
        document.querySelectorAll(selector).forEach(function (el, i) {
            el.classList.add('reveal');
            if (stagger && i > 0 && i <= 4) el.dataset.delay = i;
        });
    }

    // Secciones completas
    addReveal([
        'section.container',
        '.garantia-banner',
        '.trust-strip',
        '.comparison-section',
        '.authority-section',
        '.como-funciona-section',
        '.para-quien-section',
        '.antes-despues-section'
    ].join(','), false);

    // Cards con entrada escalonada
    addReveal('.steps-grid .step-card',            true);
    addReveal('.para-quien-grid .para-quien-card', true);
    addReveal('.authority-grid .authority-stat',   true);
    addReveal('.testimonial-card',                 true);

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

    document.querySelectorAll('.reveal').forEach(function (el) {
        observer.observe(el);
    });
})();