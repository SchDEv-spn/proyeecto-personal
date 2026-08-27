// admin-landing-reco.js — panel "Recomendaciones de IA" en el editor de la landing.
//
// Lee el último análisis de esta landing (Estadísticas → Analizar con IA), que
// llega en window.__RECO__, y lo muestra en un drawer: cada acción con su
// impacto/esfuerzo, un botón "Ir a la sección" que salta al bloque exacto del
// editor, y "Hecho / Descartar" que se persiste en landing_analisis.acciones_estado.
//
// También atiende el deep-link ?ir=sec-xxx que trae el enlace "Abrir en el editor"
// del panel de Estadísticas.
(() => {
  const panel    = document.getElementById('recoPanel');
  const backdrop = document.getElementById('recoPanelBackdrop');
  const body     = document.getElementById('recoPanelBody');
  const foot     = document.getElementById('recoPanelFoot');
  const closeBtn = document.getElementById('recoPanelClose');
  if (!panel || !body) return;

  const RECO = window.__RECO__ || null;
  const BASE = window.BASE_URL || '';
  const CSRF = window.__CSRF__ || '';

  const IMPACTO_ORDEN = { alto: 0, medio: 1, bajo: 2 };
  const ES_SECCION = /^sec-[a-z-]+$/;

  const esc = (s) => String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  // ── Salto + flash a una sección del editor ──────────────────
  function irASeccion(id) {
    if (!ES_SECCION.test(id || '')) return;

    const tocLink = document.querySelector('#landingToc a[data-target="' + id + '"]');
    if (tocLink) {
      tocLink.click();                       // reutiliza scroll + acordeón + activo del TOC
    } else {
      const el = document.getElementById(id);
      if (!el) return;
      if (typeof el._uxToggle === 'function') el._uxToggle(true);
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const target = document.getElementById(id);
    if (target) {
      target.classList.remove('section-block--flash');
      void target.offsetWidth;               // reinicia la animación
      target.classList.add('section-block--flash');
      setTimeout(() => target.classList.remove('section-block--flash'), 1600);
    }
  }

  // ── Abrir / cerrar el drawer ───────────────────────────────
  function open() {
    const pvClose = document.getElementById('previewPanelClose');
    const pv = document.getElementById('previewPanel');
    if (pvClose && pv && pv.classList.contains('is-open')) pvClose.click();

    panel.classList.add('is-open');
    panel.setAttribute('aria-hidden', 'false');
    if (backdrop) backdrop.style.display = 'block';
  }
  function close() {
    panel.classList.remove('is-open');
    panel.setAttribute('aria-hidden', 'true');
    if (backdrop) backdrop.style.display = 'none';
  }
  window.openRecoPanel = open;

  if (closeBtn) closeBtn.addEventListener('click', close);
  if (backdrop) backdrop.addEventListener('click', close);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && panel.classList.contains('is-open')) close();
  });

  // ── Badge del botón del header ─────────────────────────────
  function actualizaBadge(n) {
    document.querySelectorAll('.btn-reco-trigger').forEach((a) => {
      const nodo = Array.from(a.childNodes).find((c) => c.nodeType === 3 && c.textContent.trim());
      if (nodo) nodo.textContent = n > 0 ? 'Sugerencias IA (' + n + ')' : 'Sugerencias IA';
    });
  }

  // ── Marcar hecha / descartada ──────────────────────────────
  function marcar(idx, estado, li) {
    if (!RECO || !RECO.id) return;
    fetch(BASE + '/AdminLanding/marcarRecomendacion', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ analisis_id: RECO.id, idx: idx, estado: estado, csrf_token: CSRF })
    })
      .then((r) => r.json())
      .then((d) => {
        if (!d || !d.ok) return;
        li.remove();
        const quedan = body.querySelectorAll('.reco-item').length;
        actualizaBadge(quedan);
        if (!quedan) render();
      })
      .catch(() => {});
  }

  // ── Render ─────────────────────────────────────────────────
  function render() {
    if (!RECO || !RECO.resultado || !Array.isArray(RECO.resultado.acciones)) {
      body.innerHTML = '<p class="reco-empty">Todavía no hay análisis de esta landing.<br>'
        + 'Genéralo en <a href="' + BASE + '/AdminEstadisticas/index">Estadísticas → Analizar con IA</a>.</p>';
      foot.innerHTML = '';
      return;
    }

    const pendientes = RECO.resultado.acciones
      .map((a, i) => Object.assign({}, a, { idx: a.idx != null ? a.idx : i }))
      .filter((a) => (a.estado || 'pendiente') === 'pendiente')
      .sort((a, b) => (IMPACTO_ORDEN[a.impacto] != null ? IMPACTO_ORDEN[a.impacto] : 3)
                    - (IMPACTO_ORDEN[b.impacto] != null ? IMPACTO_ORDEN[b.impacto] : 3));

    if (!pendientes.length) {
      body.innerHTML = '<p class="reco-empty">✓ Todo aplicado.<br>Sin recomendaciones pendientes.</p>';
    } else {
      body.innerHTML = pendientes.map((a) => {
        const irBtn = ES_SECCION.test(a.seccion_id || '')
          ? '<button type="button" class="reco-btn reco-btn--ir" data-ir="' + esc(a.seccion_id) + '">Ir a la sección</button>'
          : '';
        return '<div class="reco-item" data-idx="' + a.idx + '">'
          + '<div class="reco-item__txt">' + esc(a.accion) + '</div>'
          + (a.donde ? '<div class="reco-item__donde">' + esc(a.donde) + '</div>' : '')
          + '<div class="reco-item__meta">'
          +   '<span class="reco-tag reco-tag--' + esc(a.impacto) + '">impacto ' + esc(a.impacto) + '</span>'
          +   '<span class="reco-tag">esfuerzo ' + esc(a.esfuerzo) + '</span>'
          + '</div>'
          + '<div class="reco-item__acciones">'
          +   irBtn
          +   '<button type="button" class="reco-btn" data-done>Hecho</button>'
          +   '<button type="button" class="reco-btn" data-skip>Descartar</button>'
          + '</div>'
        + '</div>';
      }).join('');
    }

    const fecha = RECO.creado_en ? String(RECO.creado_en).slice(0, 16).replace('T', ' ') : '';
    foot.innerHTML = 'Análisis del ' + esc(fecha) + ' · ' + esc(RECO.sesiones || 0) + ' visitas · '
      + '<a href="' + BASE + '/AdminEstadisticas/index">ver completo en Estadísticas</a>';
  }

  body.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    if (btn.dataset.ir != null) { irASeccion(btn.dataset.ir); close(); return; }
    const li = btn.closest('.reco-item');
    if (!li) return;
    const idx = parseInt(li.dataset.idx, 10);
    if (btn.hasAttribute('data-done')) marcar(idx, 'hecha', li);
    if (btn.hasAttribute('data-skip')) marcar(idx, 'descartada', li);
  });

  render();

  // ── Deep-link ?ir=sec-xxx desde el panel de Estadísticas ────
  const irParam = new URLSearchParams(location.search).get('ir');
  if (ES_SECCION.test(irParam || '')) {
    // Espera a que el TOC y los acordeones estén montados.
    setTimeout(() => irASeccion(irParam), 450);
  }
})();
