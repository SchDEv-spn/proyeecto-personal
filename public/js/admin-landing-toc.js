// admin-landing-toc.js
(() => {
  const getHeaderOffset = () => {
    const header = document.querySelector('.material-header');
    const h = header ? header.getBoundingClientRect().height : 0;
    return Math.round(h + 18);
  };

  const smoothScrollTo = (el) => {
    if (!el) return;
    const y = window.scrollY + el.getBoundingClientRect().top - getHeaderOffset();
    window.scrollTo({ top: y, behavior: 'smooth' });
  };

  const setActive = (id) => {
    const nav = document.getElementById('landingToc');
    if (!nav) return;
    nav.querySelectorAll('a').forEach(a => {
      a.classList.toggle('active', a.dataset.target === id);
    });
  };

  const initClicks = () => {
    const nav = document.getElementById('landingToc');
    if (!nav) return;

    nav.addEventListener('click', (e) => {
      const a = e.target.closest('a[data-target]');
      if (!a) return;

      e.preventDefault();
      const id = a.dataset.target;
      const target = document.getElementById(id);
      smoothScrollTo(target);
      setActive(id);
    });

    // Link "Arriba"
    document.querySelectorAll('a[href="#top"]').forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        const top = document.getElementById('top') || document.body;
        smoothScrollTo(top);
      });
    });
  };

  const initObserver = () => {
    const sections = Array.from(document.querySelectorAll('.section-block[id]'));
    if (!sections.length || typeof IntersectionObserver !== 'function') return;

    const io = new IntersectionObserver((entries) => {
      // Elegir la sección más “visible”
      const visible = entries
        .filter(e => e.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

      if (visible?.target?.id) {
        setActive(visible.target.id);
      }
    }, {
      root: null,
      threshold: [0.12, 0.2, 0.35, 0.5],
      rootMargin: `-${getHeaderOffset()}px 0px -55% 0px`
    });

    sections.forEach(s => io.observe(s));

    // Default: primer link activo
    setActive(sections[0].id);
  };

  document.addEventListener('DOMContentLoaded', () => {
    initClicks();
    initObserver();
  });
})();
