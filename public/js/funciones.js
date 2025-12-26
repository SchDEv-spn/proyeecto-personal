// admin-pedidos-ui.js
(() => {
  class AdminPedidosUI {
    constructor() {
      this.intervalMs = 30000;
      this.ultimoContador = 0;

      // Audio
      this.sonidoNotificacion = null;
      this.usarBeep = true;
      this.audioCtx = null;
      this.audioUnlocked = false;

      // Tabla / búsqueda
      this.dt = null;
      this.dtMode = null;
      this.rowsCache = [];
      this.mqDesktop = window.matchMedia('(min-width: 769px)');

      // Refs
      this.inputSearch = null;
      this.tableEl = null;

      // Modal nav state
      this._modalDetalleInit = false;
      this._modalCurrentId = null;
      this._modalPrevId = null;
      this._modalNextId = null;

      this.init();
    }

    init() {
      this.agregarEstilosNeonCompatibles();

      this.cacheRefs();

      // ✅ IMPORTANTE: el menú hamburguesa se maneja SOLO en admin-ui.js (archivo separado)
      // NO llamar a initMenuHamburguesa() aquí

      this.initTablaBusquedaYDataTables();

      this.obtenerContadorInicial();
      this.setupAudioUnlock();
      this.probarRutasSonido();

      // Modal detalle + navegación
      this.initModalDetallePedidos();

      // Indicador y polling
      this.mostrarIndicador();
      setInterval(() => this.actualizarContador(), this.intervalMs);

      setTimeout(() => this.actualizarContador(), 2000);

      console.log('🚀 AdminPedidosUI inicializado');
    }

    cacheRefs() {
      this.inputSearch = document.getElementById('searchPedidos') || null;
      this.tableEl = document.getElementById('tablaPedidos') || null;

      if (this.tableEl) {
        this.rowsCache = Array.from(this.tableEl.querySelectorAll('tbody tr'));
      }
    }

    // =========================
    // TABLA: BUSQUEDA + DATATABLES
    // =========================
    initTablaBusquedaYDataTables() {
      if (!this.tableEl) return;

      if (this.inputSearch) {
        this.inputSearch.addEventListener('input', () => {
          const q = this.inputSearch.value || '';
          if (this.dt) this.dtSearch(q);
          else this.filtroManual(q);
        });
      }

      const handle = () => {
        if (this.mqDesktop.matches) {
          this.initDataTables();
        } else {
          this.destroyDataTables();
          if (this.inputSearch) this.filtroManual(this.inputSearch.value || '');
        }
      };

      handle();
      this.mqDesktop.addEventListener('change', handle);
    }

    initDataTables() {
      if (this.dt) return;

      const hasDT2 = typeof window.DataTable === 'function';
      const hasJQ = typeof window.jQuery !== 'undefined';
      const hasDT1 = hasJQ && window.jQuery.fn && typeof window.jQuery.fn.DataTable === 'function';

      if (!hasDT2 && !hasDT1) return;

      this.limpiarHighlights();

      const options = {
        dom: 'rtip',
        pageLength: 10,
        paging: true,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [7, 8, 9] }]
      };

      if (hasDT2) {
        this.dtMode = 'dt2';
        this.dt = new window.DataTable(this.tableEl, options);
      } else {
        this.dtMode = 'dt1';
        this.dt = window.jQuery(this.tableEl).DataTable(options);
      }

      if (this.inputSearch && this.inputSearch.value) {
        this.dtSearch(this.inputSearch.value);
      }
    }

    destroyDataTables() {
      if (!this.dt) return;

      try {
        this.dt.destroy();
      } catch (e) {
        console.log('⚠️ Error destruyendo DataTables:', e);
      } finally {
        this.dt = null;
        this.dtMode = null;
      }
    }

    dtSearch(q) {
      try {
        this.dt.search(q).draw();
      } catch (e) {
        this.destroyDataTables();
        this.filtroManual(q);
      }
    }

    // ---- Filtro manual ----
    filtroManual(q) {
      if (!this.tableEl) return;

      const query = (q || '').trim().toLowerCase();
      const tbody = this.tableEl.querySelector('tbody');
      if (!tbody) return;

      const oldEmpty = document.getElementById('noResultsRow');
      if (oldEmpty) oldEmpty.remove();

      let anyVisible = false;

      this.rowsCache.forEach((row) => {
        this.resetRowHighlights(row);

        const text = (row.innerText || '').toLowerCase();
        const show = text.includes(query);

        row.style.display = show ? '' : 'none';

        if (show) {
          anyVisible = true;
          if (query) this.highlightRow(row, q);
        }
      });

      if (!anyVisible) {
        const empty = document.createElement('tr');
        empty.id = 'noResultsRow';
        empty.innerHTML = `
          <td colspan="10" style="padding:1rem; text-align:center; color:var(--gray-light);">
            No se encontraron pedidos con ese criterio.
          </td>
        `;
        tbody.appendChild(empty);
      }
    }

    highlightRow(row, q) {
      const escapeHtml = (str) =>
        String(str).replace(/[&<>"']/g, (m) => ({
          '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[m]));

      const query = (q || '').trim();
      if (!query) return;

      const walker = document.createTreeWalker(row, NodeFilter.SHOW_TEXT, {
        acceptNode: (node) => {
          const parent = node.parentElement;
          if (!parent) return NodeFilter.FILTER_REJECT;
          if (parent.closest('a, button, select, option, input, form, script, style')) {
            return NodeFilter.FILTER_REJECT;
          }
          if (!node.nodeValue || !node.nodeValue.trim()) return NodeFilter.FILTER_REJECT;
          return NodeFilter.FILTER_ACCEPT;
        }
      });

      const nodes = [];
      while (walker.nextNode()) nodes.push(walker.currentNode);

      const qLower = query.toLowerCase();

      nodes.forEach((node) => {
        const text = node.nodeValue;
        const idx = text.toLowerCase().indexOf(qLower);
        if (idx === -1) return;

        const before = text.slice(0, idx);
        const match = text.slice(idx, idx + query.length);
        const after = text.slice(idx + query.length);

        const span = document.createElement('span');
        span.innerHTML = `${escapeHtml(before)}<mark class="__hl">${escapeHtml(match)}</mark>${escapeHtml(after)}`;
        node.replaceWith(span);
      });
    }

    resetRowHighlights(row) {
      row.querySelectorAll('mark.__hl').forEach((m) => {
        const text = document.createTextNode(m.textContent);
        m.replaceWith(text);
      });
    }

    limpiarHighlights() {
      if (!this.tableEl) return;
      this.tableEl.querySelectorAll('mark.__hl').forEach((m) => {
        const text = document.createTextNode(m.textContent);
        m.replaceWith(text);
      });
    }

    // =========================
    // CONTADOR + NOTIFICACION
    // =========================
    obtenerContadorInicial() {
      const el = this.getPedidosNuevosEl();
      if (!el) return;
      this.ultimoContador = this.parseNumber(el.textContent);
    }

    async actualizarContador() {
      try {
        const response = await fetch('/tienda_mvc/AdminPedidos/contadores', { cache: 'no-store' });
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const data = await response.json();
        const nuevoContador = Number(data?.pedidos_nuevos || 0);

        this.actualizarUI(nuevoContador);

        if (nuevoContador > this.ultimoContador) {
          const nuevos = nuevoContador - this.ultimoContador;
          this.reproducirSonido();
          this.mostrarNotificacionNeon(nuevos);
        }

        this.ultimoContador = nuevoContador;
      } catch (error) {
        console.log('⚠️ Error actualizando contadores:', error);
      }
    }

    actualizarUI(nuevoContador) {
      const el = this.getPedidosNuevosEl();
      if (!el) return;

      el.textContent = String(nuevoContador);
      el.classList.add('actualizado-neon');
      setTimeout(() => el.classList.remove('actualizado-neon'), 900);
    }

    getPedidosNuevosEl() {
      const byId = document.getElementById('pedidosNuevosCount');
      if (byId) return byId;

      const card = document.querySelector('.stat-card.glow-red .stat-info h3');
      if (card) return card;

      return document.querySelector('.summary-item:nth-child(2) .summary-value');
    }

    irAPedidosNuevos() {
      if (!this.tableEl) return;

      if (this.dt && this.inputSearch) {
        this.dtSearch('nuevo');
        setTimeout(() => this.scrollPrimerNuevo(), 100);
        return;
      }

      this.scrollPrimerNuevo();
    }

    scrollPrimerNuevo() {
      const primero = this.tableEl.querySelector('.status-tag.status-nuevo');
      if (!primero) return;

      const fila = primero.closest('tr');
      if (!fila) return;

      fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
      fila.classList.add('destacado-neon');
      setTimeout(() => fila.classList.remove('destacado-neon'), 1800);
    }

    // =========================
    // NOTIFICACION NEON
    // =========================
    mostrarNotificacionNeon(cantidad) {
      const notificacion = document.createElement('div');
      notificacion.className = 'notificacion-neon';
      notificacion.innerHTML = `
        <div class="notificacion-borde"></div>
        <div class="notificacion-contenido-neon">
          <div class="notificacion-icono-neon">
            <span class="neon-text">📦</span>
          </div>
          <div class="notificacion-texto-neon">
            <div class="notificacion-titulo-neon">+${cantidad}</div>
            <div class="notificacion-subtitulo-neon">nuevo${cantidad > 1 ? 's' : ''}</div>
          </div>
          <button class="notificacion-cerrar-neon" aria-label="Cerrar">&times;</button>
        </div>
        <div class="notificacion-progreso-neon"></div>
      `;

      document.body.appendChild(notificacion);

      setTimeout(() => notificacion.classList.add('visible-neon'), 10);
      this.iniciarBarraProgresoNeon(notificacion);

      notificacion.querySelector('.notificacion-cerrar-neon')?.addEventListener('click', (e) => {
        e.stopPropagation();
        this.cerrarNotificacionNeon(notificacion);
      });

      notificacion.addEventListener('click', (e) => {
        if (!e.target.classList.contains('notificacion-cerrar-neon')) {
          this.irAPedidosNuevos();
          this.cerrarNotificacionNeon(notificacion);
        }
      });

      setTimeout(() => this.cerrarNotificacionNeon(notificacion), 5000);
    }

    iniciarBarraProgresoNeon(notificacion) {
      const barra = notificacion.querySelector('.notificacion-progreso-neon');
      if (!barra) return;

      barra.style.width = '100%';
      barra.style.transition = 'width 5s linear';
      setTimeout(() => { barra.style.width = '0%'; }, 10);
    }

    cerrarNotificacionNeon(notificacion) {
      notificacion.classList.remove('visible-neon');
      setTimeout(() => {
        if (notificacion?.parentNode) notificacion.parentNode.removeChild(notificacion);
      }, 250);
    }

    mostrarIndicador() {
      const card = document.querySelector('.stat-card.glow-red');
      if (!card) return;

      card.classList.add('card-indicador-neon');

      const indicador = document.createElement('div');
      indicador.className = 'indicador-actualizacion-neon';
      indicador.title = 'Actualiza cada 30 segundos';
      card.appendChild(indicador);

      setInterval(() => {
        indicador.classList.add('activo-neon');
        setTimeout(() => indicador.classList.remove('activo-neon'), 900);
      }, this.intervalMs);
    }

    // =========================
    // AUDIO
    // =========================
    setupAudioUnlock() {
      const unlock = async () => {
        if (this.audioUnlocked) return;
        this.audioUnlocked = true;

        try {
          const Ctx = window.AudioContext || window.webkitAudioContext;
          if (Ctx) {
            this.audioCtx = new Ctx();
            if (this.audioCtx.state === 'suspended') await this.audioCtx.resume();
          }
        } catch (_) { }

        try {
          if (this.sonidoNotificacion && this.sonidoNotificacion.src) {
            this.sonidoNotificacion.muted = true;
            await this.sonidoNotificacion.play();
            this.sonidoNotificacion.pause();
            this.sonidoNotificacion.currentTime = 0;
            this.sonidoNotificacion.muted = false;
          }
        } catch (_) { }
      };

      window.addEventListener('pointerdown', unlock, { once: true });
      window.addEventListener('keydown', unlock, { once: true });
    }

    async probarRutasSonido() {
      const rutas = [
        '/tienda_mvc/public/sounds/notification.mp3',
        '/tienda_mvc/public/sounds/notificacion.mp3',
        '/tienda_mvc/public/sounds/alert.mp3',
        '/tienda_mvc/sounds/notification.mp3'
      ];

      this.sonidoNotificacion = new Audio();
      this.sonidoNotificacion.volume = 0.3;

      for (const ruta of rutas) {
        try {
          const r = await fetch(ruta, { method: 'GET', cache: 'no-store' });
          if (!r.ok) continue;

          this.sonidoNotificacion.src = ruta;
          this.usarBeep = false;
          console.log(`🔊 Sonido configurado: ${ruta}`);
          return;
        } catch (_) { }
      }

      this.usarBeep = true;
      console.log('🔊 No se encontró mp3. Usando beep de respaldo.');
    }

    reproducirSonido() {
      if (!this.usarBeep && this.sonidoNotificacion && this.sonidoNotificacion.src) {
        try {
          this.sonidoNotificacion.currentTime = 0;
          this.sonidoNotificacion.play().catch(() => this.beep(600, 0.22, 0.18));
          return;
        } catch (_) {
          this.beep(600, 0.22, 0.18);
          return;
        }
      }
      this.beep(800, 0.28, 0.20);
    }

    beep(freq = 700, duration = 0.25, volume = 0.2) {
      try {
        const Ctx = window.AudioContext || window.webkitAudioContext;
        const ctx = this.audioCtx || (Ctx ? new Ctx() : null);
        if (!ctx) return;

        const osc = ctx.createOscillator();
        const gain = ctx.createGain();

        osc.type = 'sine';
        osc.frequency.value = freq;

        gain.gain.setValueAtTime(0, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(volume, ctx.currentTime + 0.03);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);

        osc.connect(gain);
        gain.connect(ctx.destination);

        osc.start();
        osc.stop(ctx.currentTime + duration);

        this.audioCtx = ctx;
      } catch (_) { }
    }

    parseNumber(text) {
      const s = String(text || '').replace(/[^\d]/g, '');
      return s ? parseInt(s, 10) : 0;
    }

    // =========================
    // ESTILOS (NEON)
    // =========================
    agregarEstilosNeonCompatibles() {
      const estilos = `
        :root{
          --neon-primary: var(--accent);
          --neon-secondary: var(--primary);
          --neon-accent: var(--secondary);
          --surface: rgba(15, 23, 42, 0.92);
          --text-secondary: var(--gray-light);
          --radius-md: var(--border-radius);
          --radius-sm: 12px;
          --transition-fast: 0.2s ease;
          --gradient-1: linear-gradient(90deg, var(--accent), var(--primary));
          --gradient-2: linear-gradient(90deg, var(--primary), var(--accent));
        }

        mark.__hl{
          background: rgba(249,115,22,0.25);
          color: var(--light);
          padding: 0 .15rem;
          border-radius: 6px;
        }

        .notificacion-neon{
          position: fixed;
          top: 24px;
          right: 24px;
          z-index: 10000;
          transform: translateX(120%) scale(0.95);
          opacity: 0;
          transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
          max-width: 260px;
          width: 100%;
        }
        .notificacion-neon.visible-neon{
          transform: translateX(0) scale(1);
          opacity: 1;
        }
        .notificacion-borde{
          position: absolute;
          top: -1px; left: -1px; right: -1px; bottom: -1px;
          background: linear-gradient(45deg, var(--neon-primary) 0%, var(--neon-secondary) 50%, var(--neon-accent) 100%);
          border-radius: var(--radius-md);
          z-index: -1;
          opacity: 0.55;
          filter: blur(2px);
        }
        .notificacion-contenido-neon{
          background: var(--surface);
          border-radius: var(--radius-md);
          padding: 0.9rem 1rem;
          border: 1px solid rgba(255,255,255,0.10);
          display: flex;
          align-items: center;
          gap: 0.8rem;
          position: relative;
          backdrop-filter: blur(10px);
          cursor: pointer;
        }
        .notificacion-icono-neon{
          width: 40px; height: 40px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: rgba(249,115,22,0.10);
          border-radius: var(--radius-sm);
          border: 1px solid rgba(249,115,22,0.20);
          animation: pulse-neon 2s ease infinite;
        }
        @keyframes pulse-neon{
          0%,100%{ box-shadow: 0 0 6px rgba(249,115,22,0.25); }
          50%{ box-shadow: 0 0 16px rgba(139,92,246,0.35); }
        }
        .neon-text{
          background: var(--gradient-1);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          font-size: 20px;
        }
        .notificacion-texto-neon{ flex: 1; }
        .notificacion-titulo-neon{
          font-size: 1.45rem;
          font-weight: 800;
          background: var(--gradient-2);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          line-height: 1;
          margin-bottom: 2px;
        }
        .notificacion-subtitulo-neon{
          font-size: 0.78rem;
          color: var(--text-secondary);
          text-transform: uppercase;
          letter-spacing: 1px;
          font-weight: 600;
        }
        .notificacion-cerrar-neon{
          background: transparent;
          border: 1px solid rgba(255,255,255,0.10);
          color: var(--text-secondary);
          width: 28px; height: 28px;
          border-radius: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          cursor: pointer;
          font-size: 18px;
          transition: all var(--transition-fast);
          padding: 0;
        }
        .notificacion-cerrar-neon:hover{
          color: var(--neon-primary);
          border-color: var(--neon-primary);
          background: rgba(249,115,22,0.10);
        }
        .notificacion-progreso-neon{
          position: absolute;
          bottom: 0;
          left: 0;
          height: 2px;
          background: var(--gradient-2);
          border-radius: 0 0 var(--radius-md) var(--radius-md);
          z-index: 2;
        }

        .actualizado-neon{
          color: var(--accent-light) !important;
          font-weight: 900;
          text-shadow: 0 0 10px rgba(249,115,22,0.25);
          transition: all 0.3s ease;
        }

        .card-indicador-neon{ position: relative; }
        .indicador-actualizacion-neon{
          position: absolute;
          top: 14px;
          right: 14px;
          width: 7px;
          height: 7px;
          background: var(--accent);
          border-radius: 50%;
          opacity: 0.55;
          transition: all var(--transition-fast);
        }
        .indicador-actualizacion-neon.activo-neon{
          background: var(--secondary);
          opacity: 1;
          transform: scale(1.35);
          box-shadow: 0 0 12px rgba(16,185,129,0.45);
        }

        .destacado-neon{
          background: rgba(249,115,22,0.06) !important;
          border-left: 2px solid var(--accent) !important;
          animation: highlight-neon 1s ease;
        }
        @keyframes highlight-neon{
          0%{ background: rgba(249,115,22,0.18); }
          100%{ background: rgba(249,115,22,0.06); }
        }

        @media (max-width: 640px){
          .notificacion-neon{
            top: 16px;
            right: 16px;
            left: 16px;
            max-width: none;
          }
        }
      `;

      const style = document.createElement('style');
      style.textContent = estilos;
      document.head.appendChild(style);
    }

    // =========================
    // MODAL DETALLE + NAV
    // =========================
    initModalDetallePedidos() {
      if (this._modalDetalleInit) return;
      this._modalDetalleInit = true;

      const modal = document.getElementById('pedidoModal');
      const body = document.getElementById('pedidoModalBody');
      const btnClose = document.getElementById('pedidoModalClose');

      if (!modal || !body) return;

      const open = () => {
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
      };

      const close = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
      };

      if (btnClose) btnClose.addEventListener('click', close);

      modal.addEventListener('click', (e) => {
        const card = modal.querySelector('.modal-card');
        if (card && !card.contains(e.target)) close();
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
      });

      const esc = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
        return String(value).replace(/["\\]/g, '\\$&');
      };

      const getIdsEnVista = () => {
        // Si DataTables está activo, respetar filtro/orden aplicado
        if (this.dt && typeof this.dt.rows === 'function') {
          try {
            const nodes = this.dt.rows({ search: 'applied', order: 'applied' }).nodes();
            const arr = Array.from(nodes || []);
            const ids = arr
              .map(tr => tr?.dataset?.pedidoId)
              .filter(Boolean)
              .map(String);
            if (ids.length) return ids;
          } catch (_) { }
        }

        // Fallback DOM (respeta display:none del filtro manual)
        const rows = Array.from(document.querySelectorAll('#tablaPedidos tbody tr[data-pedido-id]'))
          .filter(tr => tr.style.display !== 'none');
        return rows.map(tr => String(tr.dataset.pedidoId));
      };

      const actualizarNav = (currentId) => {
        const ids = getIdsEnVista();
        const idx = ids.indexOf(String(currentId));

        const btnPrev = body.querySelector('.js-prev-pedido');
        const btnNext = body.querySelector('.js-next-pedido');
        const pager = body.querySelector('.js-pedido-pager');

        const prevId = idx > 0 ? ids[idx - 1] : null;
        const nextId = (idx >= 0 && idx < ids.length - 1) ? ids[idx + 1] : null;

        if (btnPrev) btnPrev.disabled = !prevId;
        if (btnNext) btnNext.disabled = !nextId;

        if (pager && idx >= 0) pager.textContent = `${idx + 1} / ${ids.length}`;
        if (pager && idx < 0) pager.textContent = '';

        this._modalCurrentId = String(currentId);
        this._modalPrevId = prevId;
        this._modalNextId = nextId;
      };

      const hookEstadoForm = () => {
        const form = body.querySelector('.js-estado-form');
        if (!form) return;

        form.addEventListener('submit', async (ev) => {
          ev.preventDefault();

          const fd = new FormData(form);
          fd.set('ajax', '1');

          const r = await fetch(form.action, { method: 'POST', body: fd });
          const data = await r.json().catch(() => null);
          if (!data || !data.ok) return;

          const nuevoEstado = String(fd.get('estado') || 'nuevo');

          // Tag modal
          const tag = body.querySelector('.js-modal-status');
          if (tag) {
            tag.className = `status-tag status-${nuevoEstado} js-modal-status`;
            tag.textContent = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);
          }

          // Fila tabla
          const pedidoId = String(fd.get('id') || '');
          const row = document.querySelector(`tr[data-pedido-id="${esc(pedidoId)}"]`);
          if (row) {
            const statusTag = row.querySelector('.status-tag');
            if (statusTag) {
              statusTag.className = `status-tag status-${nuevoEstado}`;
              statusTag.textContent = nuevoEstado.charAt(0).toUpperCase() + nuevoEstado.slice(1);
            }

            const select = row.querySelector('form.status-form select[name="estado"]');
            if (select) select.value = nuevoEstado;
          }

          try {
            if (this.dt && typeof this.dt.rows === 'function') {
              this.dt.rows().invalidate().draw(false);
            }
          } catch (_) { }
        });
      };

      const cargarPedido = async (id) => {
        body.innerHTML = `
          <div class="modal-loading">
            <div class="modal-spinner"></div>
            <p>Cargando detalle...</p>
          </div>
        `;

        open();

        try {
          const url = new URL(`/tienda_mvc/AdminPedidos/detalle?id=${encodeURIComponent(id)}`, window.location.origin);
          url.searchParams.set('partial', '1');

          const res = await fetch(url.toString(), { cache: 'no-store' });
          if (!res.ok) throw new Error('HTTP ' + res.status);

          body.innerHTML = await res.text();

          hookEstadoForm();

          const wrap = body.querySelector('.pedido-modal-wrap');
          const realId = wrap?.dataset?.pedidoId ? String(wrap.dataset.pedidoId) : String(id);

          actualizarNav(realId);
        } catch (err) {
          body.innerHTML = `
            <div style="padding:1rem;color:var(--gray-light);">
              No se pudo cargar el detalle.
              <div style="margin-top:.75rem;">
                <a class="btn-detail" href="/tienda_mvc/AdminPedidos/detalle?id=${String(id)}">Abrir página</a>
              </div>
            </div>
          `;
          console.log('⚠️ Error modal detalle:', err);
        }
      };

      // Abrir modal desde "Ver detalle"
      document.addEventListener('click', async (e) => {
        const link = e.target.closest('.js-ver-detalle');
        if (!link) return;

        e.preventDefault();

        const id =
          link.dataset.id ||
          (() => {
            try {
              const u = new URL(link.getAttribute('href'), window.location.origin);
              return u.searchParams.get('id');
            } catch (_) { return null; }
          })();

        if (!id) return;
        await cargarPedido(id);
      });

      // Navegación anterior / siguiente
      modal.addEventListener('click', async (e) => {
        const prev = e.target.closest('.js-prev-pedido');
        const next = e.target.closest('.js-next-pedido');
        if (!prev && !next) return;

        e.preventDefault();
        e.stopPropagation();

        const targetId = prev ? this._modalPrevId : this._modalNextId;
        if (!targetId) return;

        await cargarPedido(targetId);
      });
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    window.adminPedidosUI = new AdminPedidosUI();
  });
})();
// admin-ui-sidebar.js  (ANTI-FLASH + preferencia en desktop)
(() => {
  const KEY = 'admin_sidebar_collapsed';
  const mqMobile = window.matchMedia('(max-width: 768px)');

  // 1) Aplicar estado lo más temprano posible (antes de render completo)
  const applyCollapsedEarly = () => {
    try {
      // En móvil NO colapsamos (solo off-canvas)
      if (mqMobile.matches) return;

      const saved = localStorage.getItem(KEY) === '1';

      // Si body aún no existe, usamos <html> como “bandera”
      const root = document.documentElement;
      if (!document.body) {
        root.classList.toggle('sidebar-collapsed', saved);
        root.classList.add('no-transitions');
        return;
      }

      document.body.classList.toggle('sidebar-collapsed', saved);
      document.body.classList.add('no-transitions');
    } catch (_) {}
  };

  applyCollapsedEarly();

  const initSidebar = () => {
    const btn = document.getElementById('btnMenu');
    const sidebar = document.querySelector('.material-sidebar');
    if (!btn || !sidebar) return;

    // Evitar doble binding
    if (btn.dataset.sidebarBound === '1') return;
    btn.dataset.sidebarBound = '1';

    // Si aplicamos clase temprano en <html>, la movemos a <body>
    try {
      const root = document.documentElement;
      if (root.classList.contains('sidebar-collapsed')) {
        document.body.classList.add('sidebar-collapsed');
        root.classList.remove('sidebar-collapsed');
      }
      if (root.classList.contains('no-transitions')) {
        document.body.classList.add('no-transitions');
        root.classList.remove('no-transitions');
      }
    } catch (_) {}

    // Tooltips para cuando esté colapsado
    document.querySelectorAll('.sidebar-nav a').forEach(a => {
      const label = (a.textContent || '').replace(/\s+/g, ' ').trim();
      if (label) a.setAttribute('title', label);
    });

    const setBtnIcon = () => {
      const icon = btn.querySelector('i');
      if (!icon) return;

      if (mqMobile.matches) {
        icon.className = 'fas fa-bars';
        btn.setAttribute('aria-label', 'Abrir menú');
        btn.removeAttribute('aria-expanded');
        return;
      }

      const collapsed = document.body.classList.contains('sidebar-collapsed');
      icon.className = collapsed ? 'fas fa-angles-right' : 'fas fa-angles-left';
      btn.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Colapsar menú');
      btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    };

    const applyMode = () => {
      sidebar.classList.remove('active');

      if (mqMobile.matches) {
        document.body.classList.remove('sidebar-collapsed');
        setBtnIcon();
        return;
      }

      const saved = localStorage.getItem(KEY) === '1';
      document.body.classList.toggle('sidebar-collapsed', saved);
      setBtnIcon();
    };

    // Botón: móvil => abrir/cerrar | desktop => colapsar/expandir
    btn.addEventListener('click', (e) => {
      e.stopPropagation();

      if (mqMobile.matches) {
        sidebar.classList.toggle('active');
        return;
      }

      const collapsed = document.body.classList.toggle('sidebar-collapsed');
      localStorage.setItem(KEY, collapsed ? '1' : '0');
      setBtnIcon();
    });

    // Click fuera (solo móvil)
    document.addEventListener('click', (e) => {
      if (!mqMobile.matches) return;

      const clickInsideSidebar = sidebar.contains(e.target);
      const clickOnButton = btn.contains(e.target);

      if (!clickInsideSidebar && !clickOnButton) {
        sidebar.classList.remove('active');
      }
    });

    // ESC: cerrar móvil / expandir desktop
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Escape') return;

      if (mqMobile.matches) {
        sidebar.classList.remove('active');
        return;
      }

      if (document.body.classList.contains('sidebar-collapsed')) {
        document.body.classList.remove('sidebar-collapsed');
        localStorage.setItem(KEY, '0');
        setBtnIcon();
      }
    });

    mqMobile.addEventListener('change', applyMode);

    // 2) Habilitar transiciones luego del primer frame (evita flash al cargar + DataTables)
    requestAnimationFrame(() => {
      document.body.classList.remove('no-transitions');
      setBtnIcon();
    });

    applyMode();
  };

  // DOM listo
  document.addEventListener('DOMContentLoaded', initSidebar);
})();
