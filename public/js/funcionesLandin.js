


document.addEventListener('DOMContentLoaded', () => {
  const track = document.getElementById('sliderTrack');
  const slides = Array.from(document.querySelectorAll('.testimonial-slide'));
  const nextBtn = document.querySelector('.next-btn');
  const prevBtn = document.querySelector('.prev-btn');
  const dots = Array.from(document.querySelectorAll('.dot'));

  if (!track || slides.length < 3 || !nextBtn || !prevBtn) return;

  const REAL_SLIDES = 5;
  let counter = 1; // 0 = clone último, 1..5 reales, 6 = clone primero

  // ===== Helpers =====
  function translateToCounter(withTransition = true) {
    const current = slides[counter];
    if (!current) return;

    // OJO: usamos offsetLeft real, evita “descuadres” por widths/padding/subpixel
    const x = current.offsetLeft;

    track.style.transition = withTransition ? 'transform 0.4s ease-in-out' : 'none';
    track.style.transform = `translate3d(${-x}px, 0, 0)`;
  }

  function normalizeToRealIndex() {
    const raw = slides[counter]?.dataset?.index ?? '';
    const real = parseInt(raw, 10);
    if (Number.isFinite(real)) return real;

    if (counter === 0) return REAL_SLIDES - 1;            // clone inicial (último)
    if (counter === slides.length - 1) return 0;          // clone final (primero)
    return 0;
  }

  function updateDots() {
    const active = normalizeToRealIndex();
    dots.forEach(d => d.classList.remove('active'));
    const dot = dots.find(d => d.dataset.dot === String(active));
    if (dot) dot.classList.add('active');
  }

  function goNext() {
    if (counter >= slides.length - 1) return;
    counter++;
    translateToCounter(true);
    updateDots();
  }

  function goPrev() {
    if (counter <= 0) return;
    counter--;
    translateToCounter(true);
    updateDots();
  }

  // ===== Init =====
  translateToCounter(false);
  updateDots();

  // ===== Eventos =====
  nextBtn.addEventListener('click', goNext);
  prevBtn.addEventListener('click', goPrev);

  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const idx = parseInt(dot.dataset.dot, 10);
      if (!Number.isFinite(idx)) return;
      counter = idx + 1; // porque 0 es clone inicial
      translateToCounter(true);
      updateDots();
    });
  });

  // Loop infinito con clones
  track.addEventListener('transitionend', () => {
    const di = slides[counter]?.dataset?.index;

    if (di === '5-clone') {
      counter = REAL_SLIDES;       // saltar al último real
      translateToCounter(false);
      updateDots();
    }

    if (di === '1-clone') {
      counter = 1;                 // saltar al primero real
      translateToCounter(false);
      updateDots();
    }
  });

  // Recalcular al resize (por tus breakpoints 100% / 50% / 33.33%)
  window.addEventListener('resize', () => {
    translateToCounter(false);
  });

  // Swipe simple móvil (opcional)
  const sliderViewport = track.closest('.testimonials-slider-container');
  let startX = 0;
  let isDown = false;

  if (sliderViewport) {
    sliderViewport.addEventListener('pointerdown', (e) => {
      isDown = true;
      startX = e.clientX;
    });

    sliderViewport.addEventListener('pointerup', (e) => {
      if (!isDown) return;
      isDown = false;

      const dx = e.clientX - startX;
      if (Math.abs(dx) > 40) {
        if (dx < 0) goNext();
        else goPrev();
      }
    });

    sliderViewport.addEventListener('pointercancel', () => {
      isDown = false;
    });
  }
});
document.addEventListener('DOMContentLoaded', () => {
    const accordionItems = document.querySelectorAll('.accordion-item');

    accordionItems.forEach(item => {
        const header = item.querySelector('.accordion-header');
        const body = item.querySelector('.accordion-body');

        header.addEventListener('click', () => {
            const isOpen = item.classList.contains('active');

            // 1. Opcional: Cerrar todos los demás acordeones antes de abrir el nuevo
            // Si prefieres que puedan estar varios abiertos, borra este bloque forEach
            accordionItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                    otherItem.querySelector('.accordion-body').style.maxHeight = null;
                }
            });

            // 2. Alternar la clase activa en el item actual
            item.classList.toggle('active');

            // 3. Controlar la altura máxima para la animación fluida
            if (!isOpen) {
                // Si lo estamos abriendo, calculamos el scrollHeight del contenido
                body.style.maxHeight = body.scrollHeight + "px";
            } else {
                // Si lo estamos cerrando, reseteamos a null
                body.style.maxHeight = null;
            }
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
    const DURATION_MS = 25 * 60 * 1000; // 25 minutos
    const SESSION_KEY = 'lp_timer_end';

    function initCountdown() {
        const el = document.getElementById('heroCountdown');
        if (!el) return;

        let endTime = parseInt(sessionStorage.getItem(SESSION_KEY), 10);
        if (!endTime || endTime <= Date.now()) {
            endTime = Date.now() + DURATION_MS;
            sessionStorage.setItem(SESSION_KEY, endTime);
        }

        function tick() {
            const remaining = endTime - Date.now();
            if (remaining <= 0) {
                // Al expirar: reinicia silenciosamente
                endTime = Date.now() + DURATION_MS;
                sessionStorage.setItem(SESSION_KEY, endTime);
            }
            const m = Math.floor(remaining / 60000);
            const s = Math.floor((remaining % 60000) / 1000);
            el.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        tick();
        setInterval(tick, 1000);
    }


    /* ---------- 2. STOCK SIMULADO CON DECAIMIENTO ----------
       Empieza en 7, puede bajar lentamente durante la visita.
       Persiste en sessionStorage para que no "suba" al navegar.
    ---------------------------------------------------------- */
    const STOCK_KEY  = 'lp_stock';
    const STOCK_BASE = 7; // ← cambia aquí si elegiste otro número

    function initStock() {
        const el = document.getElementById('stockCount');
        if (!el) return;

        let stock = parseInt(sessionStorage.getItem(STOCK_KEY), 10);
        if (!stock || stock > STOCK_BASE) {
            stock = STOCK_BASE;
            sessionStorage.setItem(STOCK_KEY, stock);
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
                sessionStorage.setItem(STOCK_KEY, stock);
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