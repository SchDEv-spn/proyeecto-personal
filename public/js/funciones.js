// =========================
// UI: Sidebar + Range filter
// =========================
(() => {
  const body = document.body;

  // ---- SIDEBAR (mobile off-canvas)
  const btnMenu = document.getElementById('btnMenu');
  const overlay = document.querySelector('.sidebar-overlay');

  const openSidebar = () => {
    body.classList.add('sidebar-open');
    if (btnMenu) btnMenu.setAttribute('aria-expanded', 'true');
    if (overlay) overlay.setAttribute('aria-hidden', 'false');
  };

  const closeSidebar = () => {
    body.classList.remove('sidebar-open');
    if (btnMenu) btnMenu.setAttribute('aria-expanded', 'false');
    if (overlay) overlay.setAttribute('aria-hidden', 'true');
  };

  const toggleSidebar = () => {
    if (body.classList.contains('sidebar-open')) closeSidebar();
    else openSidebar();
  };

  if (btnMenu) {
    btnMenu.addEventListener('click', (e) => {
      e.preventDefault();
      toggleSidebar();
    });
  }

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // Si pasas a desktop, asegúrate de cerrar estados móviles
  const mq = window.matchMedia('(min-width: 992px)');
  mq.addEventListener('change', (e) => {
    if (e.matches) closeSidebar();
  });

  // ---- RANGE FILTER (dropdown)
  const rangeWrap = document.querySelector('[data-range-filter]');
  const rangeBtn = document.getElementById('rangeBtn');
  const rangeMenu = document.getElementById('rangeMenu');
  const rangeLabel = document.getElementById('rangeLabel');

  const openRangeMenu = () => {
    if (!rangeMenu || !rangeBtn) return;
    rangeMenu.hidden = false;
    rangeBtn.setAttribute('aria-expanded', 'true');
  };

  const closeRangeMenu = () => {
    if (!rangeMenu || !rangeBtn) return;
    rangeMenu.hidden = true;
    rangeBtn.setAttribute('aria-expanded', 'false');
  };

  const toggleRangeMenu = () => {
    if (!rangeMenu) return;
    if (rangeMenu.hidden) openRangeMenu();
    else closeRangeMenu();
  };

  if (rangeBtn) {
    rangeBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleRangeMenu();
    });
  }

  if (rangeMenu) {
    rangeMenu.addEventListener('click', (e) => {
      const item = e.target.closest('.range-item');
      if (!item) return;

      // UI: label + active
      const txt = (item.textContent || '').trim();
      if (rangeLabel && txt) rangeLabel.textContent = txt;

      rangeMenu.querySelectorAll('.range-item').forEach(btn => {
        btn.classList.toggle('is-active', btn === item);
      });

      closeRangeMenu();
      window.__RANGE_SELECTED = item.dataset.range;
      window.dispatchEvent(new CustomEvent('range:change', { detail: { range: item.dataset.range } }));
    });


  }

  document.addEventListener('click', (e) => {
    if (!rangeWrap) return;
    if (rangeWrap.contains(e.target)) return;
    closeRangeMenu();
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeSidebar();
      closeRangeMenu();
    }
  });
})();

// =========================
// DASH CHARTS (Chart.js)
// Usa window.__PEDIDOS__
// =========================
(() => {
  if (!window.Chart || !Array.isArray(window.__PEDIDOS__)) return;

  const pedidos = window.__PEDIDOS__;

  // -------- Helpers: fechas
  const pad2 = (n) => String(n).padStart(2, '0');

  const parseDateFlexible = (p) => {
    const raw =
      p.fecha ||
      p.fecha_pedido ||
      p.created_at ||
      p.fecha_creacion ||
      p.fecha_registro ||
      p.updated_at;

    if (!raw) return null;

    // Timestamp numérico
    if (typeof raw === 'number') {
      const d = new Date(raw);
      return isNaN(d.getTime()) ? null : d;
    }

    if (typeof raw !== 'string') return null;

    // "YYYY-MM-DD" o "YYYY-MM-DD HH:mm:ss"
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
      const iso = raw.includes(' ') ? raw.replace(' ', 'T') : raw;
      const d = new Date(iso);
      return isNaN(d.getTime()) ? null : d;
    }

    // "DD/MM/YYYY" o "DD/MM/YYYY HH:mm"
    if (/^\d{2}\/\d{2}\/\d{4}/.test(raw)) {
      const [datePart, timePart] = raw.split(' ');
      const [dd, mm, yyyy] = datePart.split('/').map(Number);
      const hh = timePart ? Number(timePart.split(':')[0]) : 0;
      const min = timePart ? Number(timePart.split(':')[1]) : 0;
      const d = new Date(yyyy, mm - 1, dd, hh, min, 0);
      return isNaN(d.getTime()) ? null : d;
    }

    // Fallback
    const d = new Date(raw);
    return isNaN(d.getTime()) ? null : d;
  };

  const startOfDay = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate());
  const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
  const formatDayLabel = (d) => `${pad2(d.getDate())}/${pad2(d.getMonth() + 1)}`;
  const keyDay = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;

  // -------- Helpers: ventas
  const calcVenta = (p) => {
    const cantidad = Math.max(1, Number(p.cantidad_total ?? 1) || 1);
    const precioTotal = Number(p.precio_total);
    if (Number.isFinite(precioTotal)) return precioTotal;

    const precioUnit = Number(p.precio_venta);
    if (Number.isFinite(precioUnit)) return precioUnit * cantidad;

    return 0;
  };


  // =========================
  // KPI: Stats cards por rango
  // =========================
  const fmtInt = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
  const fmtMoney = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

  const calcUtilidad = (p) => {
    const cantidad = Math.max(1, Number(p.cantidad_total ?? 1) || 1);

    // Venta total (igual que tus cards)
    const precioTotal = Number(p.precio_total);
    const precioUnit = Number(p.precio_venta);
    const venta = Number.isFinite(precioTotal)
      ? precioTotal
      : (Number.isFinite(precioUnit) ? (precioUnit * cantidad) : 0);

    // Costo proveedor
    const provUnit = Number(p.precio_proveedor);
    const costoProv = Number.isFinite(provUnit) ? (provUnit * cantidad) : 0;

    // Envío (misma lógica que tu PHP)
    const envioA = Number(p.costo_envio);
    const envioB = Number(p.producto_costo_envio);
    const costoEnvio = Number.isFinite(envioA) ? envioA : (Number.isFinite(envioB) ? envioB : 0);

    const utilidad = venta - (costoProv + costoEnvio);
    return Number.isFinite(utilidad) ? utilidad : 0;
  };

  const updateKpisByRange = ({ start, end }) => {
    const elPedidos = document.getElementById('kpiPedidos');
    const elNuevos = document.getElementById('kpiNuevos');
    const elVentas = document.getElementById('kpiVentas');
    const elUtilidad = document.getElementById('kpiUtilidad');

    if (!elPedidos || !elNuevos || !elVentas || !elUtilidad) return;

    const startTs = start?.getTime?.() ?? null;
    const endTs = end?.getTime?.() ?? null;
    if (!startTs || !endTs) return;

    let pedidosCount = 0;
    let nuevosCount = 0;
    let ventasSum = 0;
    let utilidadSum = 0;

    pedidos.forEach((p) => {
      const d = parseDateFlexible(p); // sin fallback
      if (!d) return;

      const ts = d.getTime();
      if (ts < startTs || ts >= endTs) return;

      pedidosCount += 1;

      const estado = String(p.estado || 'nuevo').toLowerCase().trim();
      if (estado === 'nuevo') nuevosCount += 1;

      // ✅ Cancelado NO cuenta para dinero
      if (estado !== 'cancelado') {
        ventasSum += calcVenta(p);
        utilidadSum += calcUtilidad(p);
      }
    });

    elPedidos.textContent = fmtInt.format(pedidosCount);
    elNuevos.textContent = fmtInt.format(nuevosCount);
    elVentas.textContent = '$' + fmtMoney.format(ventasSum);
    elUtilidad.textContent = '$' + fmtMoney.format(utilidadSum);
  };

  // -------- Rango (mobile-first)
  const getRangeWindow = (range) => {
    const now = new Date();
    const today = startOfDay(now);

    if (range === 'today') {
      return { start: today, end: addDays(today, 1), mode: 'day', titleA: 'Pedidos de hoy', titleB: 'Ventas de hoy' };
    }
    if (range === 'yesterday') {
      const y = addDays(today, -1);
      return { start: y, end: today, mode: 'day', titleA: 'Pedidos de ayer', titleB: 'Ventas de ayer' };
    }
    if (range === 'week') {
      const s = addDays(today, -6); // 7 días incl.
      return { start: s, end: addDays(today, 1), mode: 'days', titleA: 'Pedidos (7 días)', titleB: 'Ventas (7 días)' };
    }
    // month (default): últimos 14 (se ve bien en móvil, no se satura)
    const s14 = addDays(today, -13);
    return { start: s14, end: addDays(today, 1), mode: 'days', titleA: 'Pedidos (14 días)', titleB: 'Ventas (14 días)' };
  };

  const setPanelTitleByCanvas = (canvasId, title) => {
    const c = document.getElementById(canvasId);
    if (!c) return;
    const panel = c.closest('.panel');
    if (!panel) return;
    const h4 = panel.querySelector('.panel__head h4');
    if (h4) h4.textContent = title;
  };

  // -------- Data builders
  const buildDailySeries = ({ start, end }) => {
    const labels = [];
    const counts = [];
    const ventas = [];

    const mapCount = new Map();
    const mapVenta = new Map();

    // Pre-buckets
    for (let d = new Date(start); d < end; d = addDays(d, 1)) {
      const k = keyDay(d);
      mapCount.set(k, 0);
      mapVenta.set(k, 0);
      labels.push(formatDayLabel(d));
    }

    // Fill
    pedidos.forEach((p) => {
      const d = parseDateFlexible(p) || new Date(); // fallback
      if (d < start || d >= end) return;

      const kd = keyDay(d);
      mapCount.set(kd, (mapCount.get(kd) || 0) + 1);
      const estado = String(p.estado || 'nuevo').toLowerCase().trim();
      if (estado !== 'cancelado') {
        mapVenta.set(kd, (mapVenta.get(kd) || 0) + calcVenta(p));
      }
    });

    // Output aligned to labels
    for (let d = new Date(start); d < end; d = addDays(d, 1)) {
      const k = keyDay(d);
      counts.push(mapCount.get(k) || 0);
      ventas.push(mapVenta.get(k) || 0);
    }

    return { labels, counts, ventas };
  };

  const buildEstados = ({ start, end }) => {
    const estados = ['nuevo', 'contactado', 'confirmado', 'enviado', 'entregado', 'cancelado'];
    const map = new Map(estados.map(e => [e, 0]));

    pedidos.forEach((p) => {
      const d = parseDateFlexible(p) || new Date();
      if (d < start || d >= end) return;
      const e = String(p.estado || 'nuevo').toLowerCase();
      if (!map.has(e)) map.set(e, 0);
      map.set(e, map.get(e) + 1);
    });

    return {
      labels: Array.from(map.keys()).map(e => e.charAt(0).toUpperCase() + e.slice(1)),
      values: Array.from(map.values()),
    };
  };

  // -------- Chart instances
  const elPedidos = document.getElementById('chartPedidos14');
  const elVentas = document.getElementById('chartVentas14');
  const elEstados = document.getElementById('chartEstados');

  if (!elPedidos || !elVentas || !elEstados) return;

  const chartBaseOpts = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { intersect: false } },
    interaction: { mode: 'index', intersect: false },
    scales: {
      x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
      y: { grid: { color: 'rgba(18,24,40,.08)' }, ticks: { precision: 0 } },
    }
  };

  const moneyTicks = (value) => {
    const n = Number(value) || 0;
    if (n >= 1e6) return `${Math.round(n / 1e6)}M`;
    if (n >= 1e3) return `${Math.round(n / 1e3)}k`;
    return String(Math.round(n));
  };

  let chartPedidos, chartVentas, chartEstados;

  // =========================
  // Pedidos Recientes: filtrar cards por rango
  // =========================
  const cardsContainer = document.getElementById('contenedorPedidos');
  const orderCards = cardsContainer ? Array.from(cardsContainer.querySelectorAll('.order-card')) : [];

  // Mensaje "vacío" (solo si existe el contenedor)
  let cardsEmptyEl = null;
  if (cardsContainer) {
    cardsEmptyEl = cardsContainer.querySelector('.cards-empty');
    if (!cardsEmptyEl) {
      cardsEmptyEl = document.createElement('div');
      cardsEmptyEl.className = 'cards-empty';
      cardsEmptyEl.style.display = 'none';
      cardsEmptyEl.style.padding = '10px 6px';
      cardsEmptyEl.style.opacity = '0.85';
      cardsEmptyEl.textContent = 'No hay pedidos en este rango.';
      cardsContainer.appendChild(cardsEmptyEl);
    }
  }

  // Index id -> timestamp (una sola vez)
  const idToTs = new Map();
  pedidos.forEach((p) => {
    const id = String(p.id ?? p.ID ?? p.pedido_id ?? '');
    const d = parseDateFlexible(p); // OJO: aquí NO usamos fallback new Date()
    if (id && d) idToTs.set(id, d.getTime());
  });

  const updateCardsEmptyState = () => {
    if (!cardsContainer || !cardsEmptyEl) return;

    // Visible si NO está oculto por rango (y si tienes otros filtros, también contarán)
    const visibleCount = orderCards.filter((c) => {
      // Si tienes otro filtro por búsqueda, este conteo igual respeta su clase si también oculta por CSS
      return !c.classList.contains('is-hidden-by-range') && c.offsetParent !== null;
    }).length;

    cardsEmptyEl.style.display = visibleCount === 0 ? '' : 'none';
  };

  const filterCardsByRange = ({ start, end }) => {
    if (!cardsContainer || !orderCards.length) return;

    const startTs = start?.getTime?.() ?? null;
    const endTs = end?.getTime?.() ?? null;

    // Si no hay rango válido, no ocultamos nada
    if (!startTs || !endTs) {
      orderCards.forEach((c) => c.classList.remove('is-hidden-by-range'));
      updateCardsEmptyState();
      return;
    }

    orderCards.forEach((card) => {
      const id = String(card.getAttribute('data-pedido-id') || '');
      const ts = idToTs.get(id);

      // Si no hay fecha parseable, por defecto LO DEJAMOS VISIBLE
      // (si prefieres ocultarlo para evitar falsos positivos, cambia a "true")
      const hideWhenNoDate = false;

      if (!ts) {
        card.classList.toggle('is-hidden-by-range', hideWhenNoDate);
        return;
      }

      const ok = ts >= startTs && ts < endTs;
      card.classList.toggle('is-hidden-by-range', !ok);
    });

    updateCardsEmptyState();
  };

  const renderAll = (range = 'month') => {
    const w = getRangeWindow(range);

    // ✅ KPIs también por rango
    updateKpisByRange(w);

    // ✅ NUEVO: también filtra las cards con el mismo rango
    filterCardsByRange(w);

    setPanelTitleByCanvas('chartPedidos14', w.titleA);
    setPanelTitleByCanvas('chartVentas14', w.titleB);

    const { labels, counts, ventas } = buildDailySeries(w);
    const est = buildEstados(w);

    // Pedidos (línea)
    if (chartPedidos) chartPedidos.destroy();
    chartPedidos = new Chart(elPedidos, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data: counts,
          borderColor: '#5d68ff',
          backgroundColor: 'rgba(93,104,255,.14)',
          fill: true,
          tension: .35,
          pointRadius: 2,
          pointHoverRadius: 4,
          borderWidth: 2,
        }]
      },
      options: chartBaseOpts
    });

    // Ventas (línea)
    if (chartVentas) chartVentas.destroy();
    chartVentas = new Chart(elVentas, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data: ventas,
          borderColor: '#22d3ee',
          backgroundColor: 'rgba(34,211,238,.14)',
          fill: true,
          tension: .35,
          pointRadius: 2,
          pointHoverRadius: 4,
          borderWidth: 2,
        }]
      },
      options: {
        ...chartBaseOpts,
        scales: {
          ...chartBaseOpts.scales,
          y: {
            ...chartBaseOpts.scales.y,
            ticks: { callback: moneyTicks }
          }
        }
      }
    });

    // Estados (doughnut)
    if (chartEstados) chartEstados.destroy();
    chartEstados = new Chart(elEstados, {
      type: 'doughnut',
      data: {
        labels: est.labels,
        datasets: [{
          data: est.values,
          backgroundColor: [
            'rgba(93,104,255,.75)',  // nuevo
            'rgba(34,211,238,.70)',  // contactado
            'rgba(34,197,94,.70)',   // confirmado
            'rgba(245,158,11,.70)',  // enviado
            'rgba(16,185,129,.70)',  // entregado
            'rgba(255,59,48,.70)',   // cancelado
          ],
          borderWidth: 0,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } } }
      }
    });
  };

  // Render inicial
  renderAll('month');
  window.__RANGE_SELECTED = window.__RANGE_SELECTED || 'month';

  // Escucha cambios del rango (lo emite tu UI del dropdown)
  window.addEventListener('range:change', (e) => {
    const range = e?.detail?.range || 'month';
    renderAll(range);
  });
})();

// =========================
// PEDIDOS: Search + Modal detalle
// =========================
(() => {
  // -------- Search filter
  const input = document.getElementById('searchPedidos');
  const container = document.getElementById('contenedorPedidos');

  if (input && container) {
    const cards = Array.from(container.querySelectorAll('.order-card'));

    // Normaliza: minúsculas + sin acentos
    const normalize = (s) =>
      (s || '')
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');

    // Cache de texto por tarjeta (mejor performance)
    const cache = new Map();
    cards.forEach((card) => {
      cache.set(card, normalize(card.textContent));
    });

    // Mensaje vacío (para cuando no haya matches)
    const empty = document.createElement('div');
    empty.className = 'cards-empty';
    empty.textContent = 'No se encontraron pedidos con ese criterio.';
    empty.style.display = 'none';
    container.appendChild(empty);

    let t = null;
    const applyFilter = () => {
      const q = normalize(input.value.trim());
      let visibleCount = 0;

      cards.forEach((card) => {
        const hay = !q || cache.get(card).includes(q);
        card.classList.toggle('is-hidden-by-search', !hay); if (hay) visibleCount++;
      });

      empty.style.display = visibleCount === 0 ? '' : 'none';
    };

    input.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(applyFilter, 90); // debounce corto (mobile-friendly)
    });

    // Si quieres que al cargar ya esté “limpio”
    applyFilter();
  }

  // -------- Modal detalle (abre con .js-ver-detalle)
  const modalOverlay = document.getElementById('pedidoModal');
  const modalClose = document.getElementById('pedidoModalClose');
  const modalBody = document.getElementById('pedidoModalBody');

  if (!modalOverlay || !modalBody) return;

  // ===== Helpers Prev/Next =====
  const getCards = () => {
    // Usa el contenedor si existe (mejor), si no, busca global
    const scope = container || document;
    return Array.from(scope.querySelectorAll('.order-card[data-pedido-id]'));
  };

  const getVisibleIds = () => {
    return getCards()
      .filter(c => getComputedStyle(c).display !== 'none')
      .map(c => String(c.dataset.pedidoId || ''))
      .filter(Boolean);
  };

  const getCurrentId = () => {
    const wrap = modalBody.querySelector('.pedido-modal-wrap');
    return wrap?.dataset?.pedidoId ? String(wrap.dataset.pedidoId) : '';
  };

  const buildPartialUrl = (href) => {
    let url = href || '';
    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set('partial', '1');
      return u.toString();
    } catch {
      return url + (url.includes('?') ? '&' : '?') + 'partial=1';
    }
  };

  const getHrefForId = (id) => {
    const card = getCards().find(c => String(c.dataset.pedidoId) === String(id));
    const a = card ? card.querySelector('a.js-ver-detalle') : null;
    return a?.getAttribute('href') || `/tienda_mvc/AdminPedidos/detalle?id=${encodeURIComponent(id)}`;
  };

  const updatePager = () => {
    if (!modalOverlay.classList.contains('is-open')) return;

    const ids = getVisibleIds();
    const currentId = getCurrentId();
    const idx = ids.indexOf(currentId);

    const pager = modalBody.querySelector('.js-pedido-pager');
    const btnPrev = modalBody.querySelector('.js-prev-pedido');
    const btnNext = modalBody.querySelector('.js-next-pedido');

    if (pager) pager.textContent = (idx >= 0) ? `${idx + 1} / ${ids.length}` : '';

    if (btnPrev) btnPrev.disabled = (idx <= 0);
    if (btnNext) btnNext.disabled = (idx < 0 || idx >= ids.length - 1);
  };

  const LOADING_HTML = `
  <div class="modal-loading">
    <div class="modal-spinner"></div>
    <p>Cargando detalle...</p>
  </div>
  `;

  const sleep = (ms) => new Promise(r => setTimeout(r, ms));

  const loadPedidoById = async (id) => {
    // helper: reset scroll del contenedor correcto
    const resetScroll = () => {
      const scroller = modalBody.querySelector('.pedido-modal-scroll');
      if (scroller) scroller.scrollTop = 0;
      else modalBody.scrollTop = 0; // fallback
    };

    // 1) fade out contenido actual
    modalBody.classList.add('is-switching');
    await sleep(120);

    // 2) mostrar loader y fade in
    modalBody.innerHTML = LOADING_HTML;
    modalBody.classList.remove('is-switching');
    await sleep(80);

    // 3) fetch partial
    const href = getHrefForId(id);
    const url = buildPartialUrl(href);

    const res = await fetch(url, {
      method: 'GET',
      headers: { 'X-Requested-With': 'fetch' }
    });

    if (!res.ok) throw new Error(`HTTP ${res.status}`);

    const html = await res.text();

    // 4) fade out loader
    modalBody.classList.add('is-switching');
    await sleep(120);

    // 5) insertar html y fade in
    modalBody.innerHTML = html;
    const scroller = modalBody.querySelector('.pedido-modal-scroll');
    if (scroller) scroller.scrollTop = 0;
    modalBody.classList.remove('is-switching');

    // ✅ 5.1) reset scroll al top del contenido (debajo del header fijo)
    resetScroll();

    // 6) pager actualizado
    updatePager();
  };
  const openModal = () => {
    modalOverlay.classList.add('is-open');
    modalOverlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
  };

  const closeModal = () => {
    modalOverlay.classList.remove('is-open');
    modalOverlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('modal-open');
    // Limpia contenido (opcional, pero deja el loading listo para la próxima)
    modalBody.innerHTML = `
      <div class="modal-loading">
        <div class="modal-spinner"></div>
        <p>Cargando detalle...</p>
      </div>
    `;
  };

  // Cierra por botón
  if (modalClose) modalClose.addEventListener('click', closeModal);

  // Cierra por click fuera de la tarjeta
  modalOverlay.addEventListener('click', (e) => {
    const card = e.target.closest('.modal-card');
    if (!card) closeModal();
  });

  // ESC
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modalOverlay.classList.contains('is-open')) {
      closeModal();
    }
  });

  // Intercepta click en "Detalles"
  document.addEventListener('click', async (e) => {
    const a = e.target.closest('a.js-ver-detalle');
    if (!a) return;

    // Deja fallback natural: si falta data-id, abre normal
    const id = a.dataset.id || null;
    if (!id) return;

    e.preventDefault();

    // Construye URL con partial=1
    let url = a.getAttribute('href') || '';
    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set('partial', '1');
      url = u.toString();
    } catch {
      // Fallback si no parsea: agrega partial manual
      url += (url.includes('?') ? '&' : '?') + 'partial=1';
    }

    openModal();

    try {
      const res = await fetch(url, {
        method: 'GET',
        headers: { 'X-Requested-With': 'fetch' }
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const html = await res.text();
      modalBody.innerHTML = html;
      updatePager();
      const scroller = modalBody.querySelector('.pedido-modal-scroll');
      if (scroller) scroller.scrollTop = 0;
    } catch (err) {
      modalBody.innerHTML = `
        <div class="modal-loading">
          <p>No se pudo cargar el detalle en el modal.</p>
          <p style="margin:0;color:rgba(42,47,95,.7);font-size:12px;">
            Puedes abrirlo en otra pestaña.
          </p>
          <p style="margin-top:10px;">
            <a href="${a.getAttribute('href')}" target="_blank" rel="noopener"
              style="display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:12px;
                     border:1px solid rgba(93,104,255,.22);background:rgba(93,104,255,.12);
                     text-decoration:none;font-weight:750;color:rgba(42,47,95,.95);">
              Abrir detalle
            </a>
          </p>
        </div>
      `;
    }
  });

  // ===== Click Prev/Next dentro del modal =====
  document.addEventListener('click', async (e) => {
    if (!modalOverlay.classList.contains('is-open')) return;

    const prev = e.target.closest('.js-prev-pedido');
    const next = e.target.closest('.js-next-pedido');
    if (!prev && !next) return;

    e.preventDefault();

    const ids = getVisibleIds();
    const currentId = getCurrentId();
    const idx = ids.indexOf(currentId);
    if (idx < 0) return;

    const targetIdx = prev ? (idx - 1) : (idx + 1);
    if (targetIdx < 0 || targetIdx >= ids.length) return;

    try {
      await loadPedidoById(ids[targetIdx]);
    } catch (err) {
      // fallback: abre detalle normal del target
      const href = getHrefForId(ids[targetIdx]);
      window.open(href, '_blank', 'noopener');
    }
  });



  // ===== Guardar Estado (AJAX) desde el modal + Sync en la lista =====
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form.js-estado-form');
    if (!form) return;
    if (!modalOverlay.classList.contains('is-open')) return;

    e.preventDefault();

    const currentId = getCurrentId();
    if (!currentId) {
      // fallback duro si algo raro pasa
      form.submit();
      return;
    }

    const fd = new FormData(form);

    // asegura ajax=1
    if (!fd.has('ajax')) fd.append('ajax', '1');

    // estado seleccionado
    const estadoSel = (fd.get('estado') || '').toString().toLowerCase().trim();

    // UI optimista en el modal
    const modalTag = modalBody.querySelector('.js-modal-status');
    if (modalTag) {
      modalTag.textContent = estadoSel ? (estadoSel.charAt(0).toUpperCase() + estadoSel.slice(1)) : '';
      modalTag.className = `status-tag status-${estadoSel} js-modal-status`;
    }

    // deshabilita botón guardar mientras envía
    const btn = form.querySelector('button[type="submit"]');
    const oldBtnText = btn ? btn.textContent : '';
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Guardando...';
    }

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'fetch' }
      });

      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      // Intentar leer JSON (si el backend lo retorna). Si no, igual seguimos.
      let json = null;
      try { json = await res.clone().json(); } catch { }

      const estadoFinal = (json?.estado || estadoSel || '').toString().toLowerCase().trim();

      // 1) Modal tag final
      if (modalTag) {
        modalTag.textContent = estadoFinal ? (estadoFinal.charAt(0).toUpperCase() + estadoFinal.slice(1)) : '';
        modalTag.className = `status-tag status-${estadoFinal} js-modal-status`;
      }

      // 2) Sync en la tarjeta del listado
      const card = document.querySelector(`.order-card[data-pedido-id="${CSS.escape(String(currentId))}"]`);
      if (card) {
        const tag = card.querySelector('.status-tag');
        if (tag) {
          tag.textContent = estadoFinal ? (estadoFinal.charAt(0).toUpperCase() + estadoFinal.slice(1)) : '';
          tag.className = `status-tag status-${estadoFinal}`;
        }

        // Si hay select dentro del card, lo sincroniza
        const sel = card.querySelector('select[name="estado"]');
        if (sel) sel.value = estadoFinal;
      }

      // 3) Si tu backend NO devuelve JSON, recarga el partial actual (garantiza coherencia)
      // (Esto evita inconsistencias si el server ajusta algo)
      if (!json) {
        await loadPedidoById(currentId);
      }

    } catch (err) {
      // fallback seguro: submit normal
      form.submit();
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = oldBtnText || 'Guardar';
      }
    }
  }, true);


  // ===== Swipe mobile: izquierda/derecha para navegar pedidos =====
  (() => {
    let startX = 0, startY = 0, startT = 0;
    let tracking = false;

    const SWIPE_MIN_X = 55;   // distancia mínima horizontal
    const SWIPE_MAX_Y = 60;   // si se mueve mucho vertical, lo ignoramos
    const SWIPE_MAX_MS = 650; // tiempo máximo para considerarlo swipe

    const getModalCard = () => modalOverlay.querySelector('.modal-card');

    const goPrev = async () => {
      const ids = getVisibleIds();
      const currentId = getCurrentId();
      const idx = ids.indexOf(currentId);
      if (idx <= 0) return;
      await loadPedidoById(ids[idx - 1]);
    };

    const goNext = async () => {
      const ids = getVisibleIds();
      const currentId = getCurrentId();
      const idx = ids.indexOf(currentId);
      if (idx < 0 || idx >= ids.length - 1) return;
      await loadPedidoById(ids[idx + 1]);
    };

    const onStart = (e) => {
      if (!modalOverlay.classList.contains('is-open')) return;

      const card = getModalCard();
      if (!card) return;

      // Solo si el toque inicia dentro del card
      if (!e.target.closest('.modal-card')) return;

      const t = e.touches ? e.touches[0] : e;
      startX = t.clientX;
      startY = t.clientY;
      startT = Date.now();
      tracking = true;
    };

    const onMove = (e) => {
      if (!tracking) return;

      const t = e.touches ? e.touches[0] : e;
      const dx = t.clientX - startX;
      const dy = t.clientY - startY;

      // si el gesto es más vertical que horizontal, abandonamos
      if (Math.abs(dy) > Math.abs(dx) && Math.abs(dy) > 18) {
        tracking = false;
      }
    };

    const onEnd = async (e) => {
      if (!tracking) return;
      tracking = false;

      const t = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0] : e;
      const dx = t.clientX - startX;
      const dy = t.clientY - startY;
      const dt = Date.now() - startT;

      if (dt > SWIPE_MAX_MS) return;
      if (Math.abs(dy) > SWIPE_MAX_Y) return;
      if (Math.abs(dx) < SWIPE_MIN_X) return;

      try {
        // dx > 0 => swipe right (ir al anterior)
        // dx < 0 => swipe left  (ir al siguiente)
        if (dx > 0) await goPrev();
        else await goNext();
      } catch { }
    };

    // Touch events
    document.addEventListener('touchstart', onStart, { passive: true });
    document.addEventListener('touchmove', onMove, { passive: true });
    document.addEventListener('touchend', onEnd, { passive: true });
  })();
})();
// =========================
// PEDIDOS: Update estado (AJAX) sin recargar
// =========================
(() => {
  document.addEventListener(
    'submit',
    async (e) => {
      const form = e.target.closest('form.status-form');
      if (!form) return;

      // Evita interferir con el form del modal (si lo manejas aparte)
      if (form.classList.contains('js-estado-form')) return;

      e.preventDefault();

      const card = form.closest('.order-card');
      const btn = form.querySelector('button[type="submit"]');
      const sel = form.querySelector('select[name="estado"]');

      const oldBtnText = btn ? btn.textContent : '';
      if (btn) {
        btn.disabled = true;
        btn.textContent = 'Actualizando...';
      }

      const fd = new FormData(form);
      if (!fd.has('ajax')) fd.append('ajax', '1'); // por si tu backend lo usa

      const id = (fd.get('id') || '').toString();
      const estadoSel = (fd.get('estado') || '').toString().toLowerCase().trim();

      // UI optimista (se ve inmediato)
      if (card) {
        const tag = card.querySelector('.status-tag');
        if (tag) {
          tag.textContent = estadoSel ? (estadoSel.charAt(0).toUpperCase() + estadoSel.slice(1)) : '';
          tag.className = `status-tag status-${estadoSel}`;
        }
        if (sel) sel.value = estadoSel;
      }

      try {
        const res = await fetch(form.action, {
          method: (form.method || 'POST').toUpperCase(),
          body: fd,
          headers: { 'X-Requested-With': 'fetch' },
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        // Si el backend devuelve JSON, lo aprovechamos. Si no, seguimos con estadoSel.
        let json = null;
        try {
          json = await res.clone().json();
        } catch { }

        const estadoFinal = (json?.estado || estadoSel || '').toString().toLowerCase().trim();

        // Sync final UI
        if (card) {
          const tag = card.querySelector('.status-tag');
          if (tag) {
            tag.textContent = estadoFinal ? (estadoFinal.charAt(0).toUpperCase() + estadoFinal.slice(1)) : '';
            tag.className = `status-tag status-${estadoFinal}`;
          }
          if (sel) sel.value = estadoFinal;
        }

        // Sync en memoria (para que tus KPIs / charts puedan recalcular bien después)
        if (Array.isArray(window.__PEDIDOS__) && id) {
          const p = window.__PEDIDOS__.find(x => String(x.id ?? x.ID ?? x.pedido_id ?? '') === String(id));
          if (p) p.estado = estadoFinal;
        }

        const active = document.querySelector('#rangeMenu .range-item.is-active');
        const currentRange = active?.dataset?.range || 'month';

        window.dispatchEvent(new CustomEvent('range:change', { detail: { range: currentRange } }));




      } catch (err) {
        // Si algo falla, fallback seguro al submit normal
        form.submit();
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = oldBtnText || 'Actualizar Estado';
        }
      }
    },
    true
  );
})();
