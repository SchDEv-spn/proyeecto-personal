/* =========================================================
   UX IMPROVEMENTS — editarLanding
   Enlazar AL FINAL del <body>, después de funciones.js y
   admin-landing-toc.js:
   <script src=(window.BASE_URL||"")+"/public/js/ux-improvements.js"></script>
   ========================================================= */

(function () {
  'use strict';

  /* ─────────────────────────────────────────────────────────
     Helpers
  ───────────────────────────────────────────────────────── */
  function qs(sel, ctx)  { return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }


  /* ─────────────────────────────────────────────────────────
     1. SECCIONES COLAPSABLES
     Convierte cada .section-block en un acordeón con header
     clickeable, badge de estado y chevron animado.
  ───────────────────────────────────────────────────────── */
  function initCollapsibleSections() {
    qsa('.section-block').forEach(function (section) {
      var h2 = section.querySelector(':scope > h2');
      if (!h2) return;

      // Recoge todos los hijos excepto el h2
      var bodyChildren = Array.from(section.children).filter(function (el) {
        return el !== h2;
      });

      /* -- Toggle header -- */
      var header = document.createElement('div');
      header.className = 'sec-toggle-header';
      header.setAttribute('tabindex', '0');
      header.setAttribute('role', 'button');
      header.setAttribute('aria-expanded', 'true');

      var titleEl = document.createElement('span');
      titleEl.className = 'sec-toggle-title';
      titleEl.textContent = h2.textContent.trim();

      var badge = document.createElement('span');
      badge.className = 'sec-badge';

      var chevron = document.createElement('span');
      chevron.className = 'sec-chevron sec-chevron--open';
      chevron.setAttribute('aria-hidden', 'true');

      header.appendChild(titleEl);
      header.appendChild(badge);
      header.appendChild(chevron);

      /* -- Body colapsable -- */
      var body = document.createElement('div');
      body.className = 'sec-body sec-body--open';
      bodyChildren.forEach(function (child) { body.appendChild(child); });

      /* -- Reconstruye la sección -- */
      section.innerHTML = '';
      section.appendChild(header);
      section.appendChild(body);

      /* -- Función de toggle accesible desde fuera -- */
      section._uxToggle = function (forceOpen) {
        var open = (forceOpen !== undefined)
          ? forceOpen
          : !body.classList.contains('sec-body--open');

        body.classList.toggle('sec-body--open', open);
        chevron.classList.toggle('sec-chevron--open', open);
        header.setAttribute('aria-expanded', String(open));
        setTimeout(updateExpandInfo, 30);
      };

      section._uxBody  = body;
      section._uxBadge = badge;

      /* -- Eventos -- */
      header.addEventListener('click', function () { section._uxToggle(); });
      header.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          section._uxToggle();
        }
      });
    });
  }


  /* ─────────────────────────────────────────────────────────
     2. CÁLCULO DE ESTADO DE SECCIÓN
     complete  → ≥75 % de campos rellenos (+ media si aplica)
     partial   → algo relleno pero incompleto
     empty     → nada relleno
  ───────────────────────────────────────────────────────── */
  function getSectionStatus(section) {
    var ctx = section._uxBody || section;

    // Campos de texto / número / textarea (excluye acciones)
    var textFields = qsa(
      'input[type="text"], input[type="number"], textarea',
      ctx
    ).filter(function (el) {
      return !el.closest('.admin-form-actions');
    });

    // Campos ocultos que indican media ya subida
    var mediaFields = qsa('input[type="hidden"]', ctx).filter(function (el) {
      return el.name && (
        el.name.indexOf('_path_actual') !== -1 ||
        el.name.indexOf('_image_path_actual') !== -1
      );
    });

    var filled = textFields.filter(function (el) {
      return el.value.trim() !== '';
    }).length + mediaFields.filter(function (el) {
      return el.value.trim() !== '';
    }).length;

    var total = textFields.length + mediaFields.length;

    if (total === 0) return 'empty';
    var ratio = filled / total;
    if (ratio >= 0.75) return 'complete';
    if (ratio > 0)     return 'partial';
    return 'empty';
  }

  function applyStatus(section) {
    var status = getSectionStatus(section);
    var badge  = section._uxBadge;
    if (badge) {
      var labels = { complete: 'Completo', partial: 'Incompleto', empty: 'Vacío' };
      badge.textContent = labels[status] || '';
      badge.className   = 'sec-badge sec-badge--' + status;
    }
    return status;
  }

  function updateAllStatuses() {
    var sections = qsa('.section-block');
    var counts   = { complete: 0, partial: 0, empty: 0 };

    sections.forEach(function (s) {
      var st = applyStatus(s);
      counts[st]++;
      updateTocDot(s.id, st);
    });

    updateProgress(counts.complete, sections.length);
  }


  /* ─────────────────────────────────────────────────────────
     3. BARRA DE PROGRESO
     Se inserta antes de la segunda .form-card (el form grande)
  ───────────────────────────────────────────────────────── */
  function initProgress() {
    var layout = qs('.landing-editor-main');
    if (!layout) return;

    var wrap = document.createElement('div');
    wrap.className = 'ux-progress-wrap';
    wrap.innerHTML =
      '<div class="ux-progress-bar">' +
        '<div class="ux-progress-fill" id="ux-prog-fill"></div>' +
      '</div>' +
      '<p class="ux-progress-label" id="ux-prog-label">Calculando…</p>';

    var cards = layout.querySelectorAll(':scope > .form-card');
    var target = cards.length >= 2 ? cards[1] : (cards[0] || null);
    if (target) {
      layout.insertBefore(wrap, target);
    }
  }

  function updateProgress(complete, total) {
    var fill  = document.getElementById('ux-prog-fill');
    var label = document.getElementById('ux-prog-label');
    if (!fill || !label) return;

    var pct = total > 0 ? Math.round((complete / total) * 100) : 0;
    fill.style.width = pct + '%';
    label.textContent =
      complete + ' de ' + total + ' secciones completas · ' + pct + '%';
  }


  /* ─────────────────────────────────────────────────────────
     4. BOTONES EXPANDIR / COLAPSAR TODO
     Aparecen encima del form principal junto al contador.
  ───────────────────────────────────────────────────────── */
  function initExpandCollapse() {
    var layout = qs('.landing-editor-main');
    if (!layout) return;

    var bar = document.createElement('div');
    bar.className = 'ux-expand-bar';
    bar.innerHTML =
      '<span class="ux-expand-info" id="ux-expand-info"></span>' +
      '<div class="ux-expand-btns">' +
        '<button type="button" class="ux-btn-xs" id="ux-btn-expand">Expandir todo</button>' +
        '<button type="button" class="ux-btn-xs" id="ux-btn-collapse">Colapsar todo</button>' +
      '</div>';

    var cards = layout.querySelectorAll(':scope > .form-card');
    var target = cards.length >= 2 ? cards[1] : (cards[0] || null);
    if (target) {
      layout.insertBefore(bar, target);
    }

    document.getElementById('ux-btn-expand').addEventListener('click', function () {
      qsa('.section-block').forEach(function (s) { s._uxToggle && s._uxToggle(true); });
    });

    document.getElementById('ux-btn-collapse').addEventListener('click', function () {
      qsa('.section-block').forEach(function (s) { s._uxToggle && s._uxToggle(false); });
    });
  }

  function updateExpandInfo() {
    var info = document.getElementById('ux-expand-info');
    if (!info) return;
    var open  = qsa('.sec-body--open').length;
    var total = qsa('.section-block').length;
    info.textContent = open + ' de ' + total + ' secciones abiertas';
  }


  /* ─────────────────────────────────────────────────────────
     5. CAMBIOS SIN GUARDAR
     - Pill roja "Cambios sin guardar" que aparece al editar
     - Botón Guardar se vuelve rojo con animación
     - Hint verde con hora del último guardado (sessionStorage)
  ───────────────────────────────────────────────────────── */
  function initUnsavedChanges() {
    var form    = qs('.admin-form');
    var saveBtn = qs('.btn-estado');
    if (!form || !saveBtn) return;

    var actionsRow = saveBtn.parentNode;

    /* Hint de último guardado */
    var hint = document.createElement('span');
    hint.className = 'ux-autosave-hint';
    hint.id = 'ux-autosave-hint';
    var lastSave = sessionStorage.getItem('ux_landing_save');
    if (lastSave) {
      var ago = Math.round((Date.now() - parseInt(lastSave, 10)) / 60000);
      hint.textContent = 'Guardado ' + (ago <= 1 ? 'hace un momento' : 'hace ' + ago + ' min');
    }
    actionsRow.insertBefore(hint, saveBtn);

    /* Pill de cambios pendientes */
    var pill = document.createElement('span');
    pill.id = 'ux-dirty-pill';
    pill.className = 'ux-dirty-pill';
    pill.textContent = 'Cambios sin guardar';
    actionsRow.insertBefore(pill, saveBtn);

    var dirty = false;

    function markDirty() {
      if (!dirty) {
        dirty = true;
        pill.classList.add('ux-dirty-pill--show');
        saveBtn.classList.add('btn-estado--unsaved');
        hint.textContent = '';
      }
      updateAllStatuses();
    }

    form.addEventListener('input',  markDirty);
    form.addEventListener('change', markDirty);

    /* Al enviar el form, guarda timestamp */
    form.addEventListener('submit', function () {
      sessionStorage.setItem('ux_landing_save', String(Date.now()));
    });
  }


  /* ─────────────────────────────────────────────────────────
     6. PUNTOS DE ESTADO EN EL TOC
     Inserta un puntito de color antes del texto de cada enlace.
  ───────────────────────────────────────────────────────── */
  function initTocDots() {
    qsa('#landingToc a').forEach(function (link) {
      if (link.querySelector('.toc-dot')) return;
      var dot = document.createElement('span');
      dot.className = 'toc-dot toc-dot--empty';
      link.insertBefore(dot, link.firstChild);
    });
  }

  function updateTocDot(sectionId, status) {
    var link = qs('#landingToc a[href="#' + sectionId + '"]');
    if (!link) return;
    var dot = link.querySelector('.toc-dot');
    if (!dot) return;
    dot.className = 'toc-dot toc-dot--' + status;
  }


  /* ─────────────────────────────────────────────────────────
     7. DRAG & DROP EN ZONAS DE CARGA
     Envuelve cada input[type="file"] con una zona visual
     interactiva que acepta archivos arrastrados.
     Compatible con los handlers existentes (onchange, etc.)
  ───────────────────────────────────────────────────────── */
  function getAcceptLabel(accept) {
    if (!accept) return 'Cualquier archivo';
    if (accept.indexOf('video') !== -1 && accept.indexOf('image') !== -1)
      return 'Imagen o video · Máx 10 MB';
    if (accept.indexOf('video') !== -1) return 'MP4, MOV, WEBM · Máx 10 MB';
    if (accept === 'image/gif')         return 'Solo GIF animado';
    if (accept.indexOf('image') !== -1) return 'JPG, PNG, WEBP · Máx 2 MB';
    return '';
  }

  function initDropzones() {
    qsa('input[type="file"]').forEach(function (input) {
      if (input.dataset.uxDrop) return;
      input.dataset.uxDrop = '1';

      /* Contenedor drag & drop */
      var wrap = document.createElement('div');
      wrap.className = 'ux-dropzone';

      var inner = document.createElement('div');
      inner.className = 'ux-dropzone-inner';

      function renderLabel(icon, text, hint, ok) {
        inner.innerHTML =
          '<em class="ux-dropzone-icon' + (ok ? ' ux-dropzone-icon--ok' : '') + '">' + icon + '</em>' +
          '<span class="ux-dropzone-text">' + text + '</span>' +
          '<span class="ux-dropzone-hint">' + hint + '</span>';
      }

      renderLabel('↑', 'Arrastra aquí o <u>haz clic</u>', getAcceptLabel(input.accept), false);
      wrap.appendChild(inner);

      /* Inserta el wrap en lugar del input y mueve el input dentro */
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
      input.classList.add('ux-dropzone-input');

      /* Click en la zona → abre selector de archivos */
      wrap.addEventListener('click', function (e) {
        if (e.target !== input) input.click();
      });

      /* Drag events */
      ['dragover', 'dragenter'].forEach(function (ev) {
        wrap.addEventListener(ev, function (e) {
          e.preventDefault();
          wrap.classList.add('ux-dropzone--over');
        });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        wrap.addEventListener(ev, function () {
          wrap.classList.remove('ux-dropzone--over');
        });
      });
      wrap.addEventListener('drop', function (e) {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
          try { input.files = e.dataTransfer.files; } catch (err) { /* Safari */ }
          input.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      /* Actualiza la zona al elegir archivo */
      input.addEventListener('change', function () {
        if (input.files && input.files.length) {
          var f    = input.files[0];
          var size = (f.size / 1024 / 1024).toFixed(1);
          renderLabel('✓', f.name, size + ' MB · <u>Cambiar archivo</u>', true);
          wrap.classList.add('ux-dropzone--filled');
        }
      });
    });
  }


  /* ─────────────────────────────────────────────────────────
     8. AUTO-COLAPSAR SECCIONES VACÍAS AL CARGAR
     Solo si la sección no tiene ningún campo relleno ni media.
  ───────────────────────────────────────────────────────── */
  function autoCollapseEmpty() {
    qsa('.section-block').forEach(function (s) {
      if (getSectionStatus(s) === 'empty') {
        s._uxToggle && s._uxToggle(false);
      }
    });
  }


  /* ─────────────────────────────────────────────────────────
     INIT
  ───────────────────────────────────────────────────────── */
  function init() {
    initCollapsibleSections();  // primero: transforma el DOM
    initProgress();
    initExpandCollapse();
    initTocDots();
    initDropzones();
    initUnsavedChanges();

    /* Espera un tick para que el DOM esté completamente listo */
    setTimeout(function () {
      updateAllStatuses();
      autoCollapseEmpty();
      updateExpandInfo();
    }, 60);

    /* Re-evalúa al abrir/cerrar sección (para el contador) */
    document.addEventListener('click', function (e) {
      if (e.target.closest('.sec-toggle-header')) {
        setTimeout(updateExpandInfo, 60);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
