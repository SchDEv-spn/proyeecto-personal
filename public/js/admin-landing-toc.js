// admin-landing-toc.js
(() => {
  const isDesktop = () => window.innerWidth >= 992;

  /* Offset de scroll: header fijo + barra TOC horizontal en desktop */
  const getHeaderOffset = () => {
    const header = document.querySelector('.material-header');
    const h = header ? header.getBoundingClientRect().height : 0;
    const toc = document.querySelector('.landing-editor-toc');
    const tocH = (toc && isDesktop()) ? toc.getBoundingClientRect().height : 0;
    return Math.round(h + tocH + 12);
  };

  const smoothScrollTo = (el) => {
    if (!el) return;
    const y = window.scrollY + el.getBoundingClientRect().top - getHeaderOffset();
    window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
  };

  const setActive = (id) => {
    const nav = document.getElementById('landingToc');
    if (!nav) return;
    nav.querySelectorAll('a').forEach(a => {
      a.classList.toggle('active', a.dataset.target === id);
    });
    /* En desktop: desplaza el chip activo al centro de la barra */
    if (isDesktop()) {
      const active = nav.querySelector('a.active');
      if (active) active.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
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

      /* Desktop: acordeón exclusivo — cierra todo, abre solo el seleccionado */
      if (isDesktop()) {
        document.querySelectorAll('.section-block').forEach(sec => {
          if (sec.id !== id && typeof sec._uxToggle === 'function') sec._uxToggle(false);
        });
        if (target && typeof target._uxToggle === 'function') target._uxToggle(true);
      }

      smoothScrollTo(target);
      setActive(id);
    });

    /* Enlace "Arriba" (solo existe en el TOC móvil legado) */
    document.querySelectorAll('a[href="#top"]').forEach(a => {
      a.addEventListener('click', (e) => {
        e.preventDefault();
        const top = document.getElementById('top') || document.body;
        smoothScrollTo(top);
      });
    });

    /* Botón "Ver todo / Colapsar" */
    const btnVerTodo = document.getElementById('tocVerTodo');
    if (btnVerTodo) {
      btnVerTodo.addEventListener('click', () => {
        const anyOpen = !!document.querySelector('.sec-body--open');
        document.querySelectorAll('.section-block').forEach(s => {
          if (typeof s._uxToggle === 'function') s._uxToggle(!anyOpen);
        });
        btnVerTodo.textContent = anyOpen ? 'Ver todo' : 'Colapsar';
      });
    }
  };

  const initObserver = () => {
    const sections = Array.from(document.querySelectorAll('.section-block[id]'));
    if (!sections.length || typeof IntersectionObserver !== 'function') return;

    const io = new IntersectionObserver((entries) => {
      const visible = entries
        .filter(e => e.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (visible?.target?.id) setActive(visible.target.id);
    }, {
      root: null,
      threshold: [0.10, 0.25, 0.5],
      rootMargin: `-${getHeaderOffset()}px 0px -55% 0px`
    });

    sections.forEach(s => io.observe(s));
    setActive(sections[0].id);
  };

  /* Actualiza el TOC cuando el panel CMS activa una sección */
  document.addEventListener('cms:section-activated', (e) => {
    if (e.detail?.id) setActive(e.detail.id);
  });

  document.addEventListener('DOMContentLoaded', () => {
    initClicks();
    initObserver();
  });
})();
