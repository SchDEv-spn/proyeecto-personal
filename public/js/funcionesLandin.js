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
