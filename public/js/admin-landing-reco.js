// admin-landing-reco.js — panel "Recomendaciones de IA" en el editor de la landing.
//
// window.__RECO__ = { pendientes:[tarea], resueltas:[tarea + impacto_medido?], ultimo:{creado_en,sesiones} }
// (backlog persistente: LandingRecoTareas).
//
// El drawer tiene dos secciones: pendientes (Ir / Aplicar / Hecho / Descartar) y
// cambios recientes (con la conversión antes vs después). También atiende el
// deep-link ?ir=sec-xxx que trae el enlace "Abrir en el editor" de Estadísticas.
(() => {
  const panel    = document.getElementById('recoPanel');
  const backdrop = document.getElementById('recoPanelBackdrop');
  const body     = document.getElementById('recoPanelBody');
  const foot     = document.getElementById('recoPanelFoot');
  const closeBtn = document.getElementById('recoPanelClose');
  if (!panel || !body) return;

  const RECO = window.__RECO__ || {};
  const BASE = window.BASE_URL || '';
  const CSRF = window.__CSRF__ || '';
  const PID  = Number(document.querySelector('input[name="producto_id"]') ?
    document.querySelector('input[name="producto_id"]').value : 0) || 0;

  const IMPACTO_ORDEN = { alto: 0, medio: 1, bajo: 2 };
  const ES_SECCION = /^sec-[a-z-]+$/;

  const esc = (s) => String(s == null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  const pct = (n) => Number(n || 0).toFixed(1).replace('.', ',') + '%';

  // ── Salto + flash a una sección del editor ──────────────────
  function irASeccion(id) {
    if (!ES_SECCION.test(id || '')) return;
    const tocLink = document.querySelector('#landingToc a[data-target="' + id + '"]');
    if (tocLink) {
      tocLink.click();
    } else {
      const el = document.getElementById(id);
      if (!el) return;
      if (typeof el._uxToggle === 'function') el._uxToggle(true);
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    const target = document.getElementById(id);
    if (target) {
      target.classList.remove('section-block--flash');
      void target.offsetWidth;
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
  function badge(delta) {
    document.querySelectorAll('.btn-reco-trigger').forEach((a) => {
      const nodo = Array.from(a.childNodes).find((c) => c.nodeType === 3 && c.textContent.trim());
      if (!nodo) return;
      const m = nodo.textContent.match(/\((\d+)\)/);
      const n = Math.max(0, (m ? parseInt(m[1], 10) : contarPendientes()) + delta);
      nodo.textContent = n > 0 ? 'Sugerencias IA (' + n + ')' : 'Sugerencias IA';
    });
  }
  function contarPendientes() {
    return body.querySelectorAll('.reco-item[data-pend="1"]').length;
  }

  // ── Llamadas al backend ───────────────────────────────────
  function post(accion, tareaId) {
    return fetch(BASE + '/AdminLanding/' + accion, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ tarea_id: tareaId, producto_id: PID, estado: '', csrf_token: CSRF })
    }).then((r) => r.json()).catch(() => ({ ok: false }));
  }
  function postEstado(tareaId, estado) {
    return fetch(BASE + '/AdminLanding/marcarRecomendacion', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ tarea_id: tareaId, producto_id: PID, estado: estado, csrf_token: CSRF })
    }).then((r) => r.json()).catch(() => ({ ok: false }));
  }

  // ── Render de una tarea pendiente ─────────────────────────
  function itemPendiente(t) {
    const irBtn = ES_SECCION.test(t.seccion_id || '')
      ? '<button type="button" class="reco-btn reco-btn--ir" data-ir="' + esc(t.seccion_id) + '">Ir a la sección</button>'
      : '';
    const aplicarBtn = t.cambio_campo
      ? '<button type="button" class="reco-btn reco-btn--aplicar" data-apply>Aplicar</button>'
      : '';
    const veces = (t.veces_sugerida > 1)
      ? '<span class="reco-tag">sugerida ' + t.veces_sugerida + '&times;</span>'
      : '';
    return '<div class="reco-item" data-id="' + t.id + '" data-pend="1">'
      + '<div class="reco-item__txt">' + esc(t.accion) + '</div>'
      + (t.donde ? '<div class="reco-item__donde">' + esc(t.donde) + '</div>' : '')
      + '<div class="reco-item__meta">'
      +   '<span class="reco-tag reco-tag--' + esc(t.impacto) + '">impacto ' + esc(t.impacto) + '</span>'
      +   '<span class="reco-tag">esfuerzo ' + esc(t.esfuerzo) + '</span>' + veces
      + '</div>'
      + '<div class="reco-item__acciones">' + irBtn + aplicarBtn
      +   '<button type="button" class="reco-btn" data-done>Hecho</button>'
      +   '<button type="button" class="reco-btn" data-skip>Descartar</button>'
      + '</div></div>';
  }

  // ── Render de una tarea resuelta ──────────────────────────
  function lineaImpacto(im) {
    if (!im) return '';
    if (im.estado === 'midiendo')  return '<div class="reco-impacto reco-impacto--wait">midiendo… ' + esc(im.nota) + '</div>';
    if (im.estado === 'sin_datos') return '<div class="reco-impacto reco-impacto--wait">' + esc(im.nota) + '</div>';
    const cls = im.delta > 0.2 ? 'up' : (im.delta < -0.2 ? 'down' : 'flat');
    const signo = im.delta > 0 ? '+' : '';
    return '<div class="reco-impacto reco-impacto--' + cls + '">'
      + 'conversión ' + pct(im.antes) + ' &rarr; ' + pct(im.despues)
      + ' · ' + signo + im.delta.toFixed(1).replace('.', ',') + ' pts</div>';
  }
  function itemResuelta(t) {
    const fecha = t.resuelta_en ? String(t.resuelta_en).slice(0, 10) : '';
    const chip = { hecha: 'hecha', aplicada: 'aplicada', descartada: 'descartada' }[t.estado] || t.estado;
    const veces = (t.veces_sugerida > 1)
      ? '<div class="reco-item__donde">la IA la volvió a sugerir (' + t.veces_sugerida + '&times;)</div>' : '';
    const deshacer = (t.estado === 'aplicada')
      ? '<div class="reco-item__acciones"><button type="button" class="reco-btn" data-undo data-id="' + t.id + '">Deshacer</button></div>' : '';
    return '<div class="reco-item reco-item--done" data-id="' + t.id + '">'
      + '<div class="reco-item__txt">' + esc(t.accion) + '</div>'
      + '<div class="reco-item__meta">'
      +   '<span class="reco-tag reco-tag--done">' + esc(chip) + '</span>'
      +   (fecha ? '<span class="reco-tag">' + esc(fecha) + '</span>' : '')
      + '</div>' + veces + lineaImpacto(t.impacto_medido) + deshacer
      + '</div>';
  }

  // ── Render completo ──────────────────────────────────────
  function render() {
    const pend = (RECO.pendientes || []).slice().sort(
      (a, b) => (IMPACTO_ORDEN[a.impacto] != null ? IMPACTO_ORDEN[a.impacto] : 3)
              - (IMPACTO_ORDEN[b.impacto] != null ? IMPACTO_ORDEN[b.impacto] : 3));
    const res = RECO.resueltas || [];

    let html = '';

    if (!RECO.ultimo && !pend.length && !res.length) {
      html = '<p class="reco-empty">Todavía no hay análisis de esta landing.<br>'
        + 'Genéralo en <a href="' + BASE + '/AdminEstadisticas/index">Estadísticas → Analizar con IA</a>.</p>';
    } else {
      html += '<div class="reco-sec">Pendientes</div>';
      html += pend.length
        ? pend.map(itemPendiente).join('')
        : '<p class="reco-empty">✓ Sin recomendaciones pendientes.</p>';

      if (res.length) {
        html += '<details class="reco-sec-det"><summary class="reco-sec">Cambios recientes ('
          + res.length + ')</summary>' + res.map(itemResuelta).join('') + '</details>';
      }
    }
    body.innerHTML = html;

    const u = RECO.ultimo;
    foot.innerHTML = u
      ? 'Análisis del ' + esc(String(u.creado_en).slice(0, 16).replace('T', ' '))
        + ' · ' + esc(u.sesiones || 0) + ' visitas · '
        + '<a href="' + BASE + '/AdminEstadisticas/index">ver completo en Estadísticas</a>'
      : '';
  }

  // ── Interacción ─────────────────────────────────────────
  body.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    const item = btn.closest('.reco-item');
    if (!item) return;
    const id = item.dataset.id;

    if (btn.dataset.ir != null) { irASeccion(btn.dataset.ir); close(); return; }

    if (btn.hasAttribute('data-done') || btn.hasAttribute('data-skip')) {
      const estado = btn.hasAttribute('data-done') ? 'hecha' : 'descartada';
      btn.disabled = true;
      postEstado(id, estado).then((d) => {
        if (!d.ok) { btn.disabled = false; return; }
        item.remove();
        badge(-1);
      });
      return;
    }

    if (btn.hasAttribute('data-apply')) {
      btn.disabled = true;
      btn.textContent = 'Aplicando…';
      post('aplicarRecomendacion', id).then((d) => {
        if (!d.ok) { btn.disabled = false; btn.textContent = 'Aplicar'; alert(d.error || 'No se pudo aplicar'); return; }
        item.dataset.pend = '0';
        item.classList.add('reco-item--aplicado');
        item.querySelector('.reco-item__acciones').innerHTML =
          '<span class="reco-ok">✓ Aplicado — ' + esc(d.label) + ': ' + esc(d.aplicado) + '</span>'
          + '<button type="button" class="reco-btn" data-undo data-id="' + id + '">Deshacer</button>';
        badge(-1);
      });
      return;
    }

    if (btn.hasAttribute('data-undo')) {
      btn.disabled = true;
      post('deshacerRecomendacion', id).then((d) => {
        if (!d.ok) { btn.disabled = false; alert(d.error || 'No se pudo deshacer'); return; }
        // Recarga: la tarea volvió a pendiente, es lo más simple y fiable.
        location.reload();
      });
      return;
    }
  });

  render();

  // ── Deep-link ?ir=sec-xxx desde Estadísticas ────────────
  const irParam = new URLSearchParams(location.search).get('ir');
  if (ES_SECCION.test(irParam || '')) setTimeout(() => irASeccion(irParam), 450);
})();
