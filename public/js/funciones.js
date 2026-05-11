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
    // month (default): últimos 30 días
    const s30 = addDays(today, -29);
    return { start: s30, end: addDays(today, 1), mode: 'days', titleA: 'Pedidos (30 días)', titleB: 'Ventas (30 días)' };
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

    // Embudo de conversión
    if (typeof renderFunnel === 'function') renderFunnel(w);

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

  // -------- Embudo de conversión
  const renderFunnel = ({ start, end }) => {
    const funnelEl = document.getElementById('funnelBars');
    if (!funnelEl) return;

    const counts = { nuevo: 0, contactado: 0, confirmado: 0, enviado: 0, entregado: 0, cancelado: 0 };
    pedidos.forEach((p) => {
      const d = parseDateFlexible(p);
      if (!d || d < start || d >= end) return;
      const e = String(p.estado || 'nuevo').toLowerCase();
      if (e in counts) counts[e]++;
    });

    const total = Object.values(counts).reduce((a, b) => a + b, 0);
    if (total === 0) {
      funnelEl.innerHTML = '<p style="color:var(--muted);text-align:center;font-size:13px;padding:8px 0;">Sin pedidos en este período.</p>';
      return;
    }

    const steps = [
      { key: 'nuevo',      label: 'Nuevo',      cls: 'f-nuevo' },
      { key: 'contactado', label: 'Contactado', cls: 'f-contactado' },
      { key: 'confirmado', label: 'Confirmado', cls: 'f-confirmado' },
      { key: 'enviado',    label: 'Enviado',    cls: 'f-enviado' },
      { key: 'entregado',  label: 'Entregado',  cls: 'f-entregado' },
      { key: 'cancelado',  label: 'Cancelado',  cls: 'f-cancelado' },
    ];

    funnelEl.innerHTML = steps.map((s) => {
      const count = counts[s.key] || 0;
      const pct = Math.round(count / total * 100);
      return `
        <div class="funnel-row">
          <span class="funnel-label">${s.label}</span>
          <div class="funnel-bar-track">
            <div class="funnel-bar-fill ${s.cls}" style="width:${pct}%"></div>
          </div>
          <span class="funnel-pct">${pct}%</span>
          <span class="funnel-count">${count}</span>
        </div>`;
    }).join('');
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

// =========================
// PEDIDOS: Filtro por estado
// =========================
(() => {
  const stateFilter = document.getElementById('stateFilter');
  const container   = document.getElementById('contenedorPedidos');
  if (!stateFilter || !container) return;

  const cards = Array.from(container.querySelectorAll('.order-card'));

  const updateEmptyState = () => {
    const emptyEl = container.querySelector('.cards-empty');
    if (!emptyEl) return;
    const visible = cards.filter(c =>
      !c.classList.contains('is-hidden-by-state') &&
      !c.classList.contains('is-hidden-by-range') &&
      !c.classList.contains('is-hidden-by-search')
    ).length;
    emptyEl.style.display = visible === 0 ? '' : 'none';
  };

  stateFilter.addEventListener('click', (e) => {
    const chip = e.target.closest('.state-chip');
    if (!chip) return;

    stateFilter.querySelectorAll('.state-chip').forEach(c => c.classList.remove('is-active'));
    chip.classList.add('is-active');

    const estado = chip.dataset.estado || '';

    cards.forEach(card => {
      const hide = estado !== '' && card.dataset.estado !== estado;
      card.classList.toggle('is-hidden-by-state', hide);
    });

    updateEmptyState();
  });
})();

// =========================
// MODAL: Editar teléfono inline
// =========================
(() => {
  document.addEventListener('click', (e) => {
    // Abrir modo edición
    const editBtn = e.target.closest('.btn-edit-field');
    if (editBtn) {
      const wrap = editBtn.closest('.phone-edit-wrap');
      if (!wrap) return;
      wrap.querySelector('.phone-display').style.display = 'none';
      editBtn.style.display = 'none';
      const form = wrap.querySelector('.phone-edit-form');
      form.style.display = 'flex';
      form.querySelector('.phone-edit-input')?.focus();
      return;
    }

    // Cancelar edición
    const cancelBtn = e.target.closest('.btn-cancel-inline');
    if (cancelBtn) {
      const form = cancelBtn.closest('.phone-edit-form');
      const wrap = form?.closest('.phone-edit-wrap');
      if (!wrap) return;
      form.style.display = 'none';
      wrap.querySelector('.phone-display').style.display = '';
      wrap.querySelector('.btn-edit-field').style.display = '';
    }
  });

  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('.phone-edit-form');
    if (!form) return;
    e.preventDefault();

    const id       = form.querySelector('input[name="id"]')?.value || '';
    const telefono = form.querySelector('input[name="telefono"]')?.value?.trim() || '';
    const wrap     = form.closest('.phone-edit-wrap');
    const display  = wrap?.querySelector('.phone-display');
    const saveBtn  = form.querySelector('.btn-save-inline');

    if (!id || !telefono) return;

    const oldText = saveBtn?.textContent;
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '...'; }

    try {
      const fd = new FormData();
      fd.append('id', id);
      fd.append('telefono', telefono);
      fd.append('csrf_token', window.__CSRF__ || '');

      const res  = await fetch('/tienda_mvc/AdminPedidos/actualizarTelefono', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'fetch' },
      });

      const json = await res.json();

      if (json.ok) {
        if (display) display.textContent = json.telefono;
        form.style.display = 'none';
        if (display) display.style.display = '';
        const btn = wrap?.querySelector('.btn-edit-field');
        if (btn) btn.style.display = '';

        // Actualizar __PEDIDOS__ en memoria
        if (Array.isArray(window.__PEDIDOS__) && id) {
          const p = window.__PEDIDOS__.find(x => String(x.id ?? '') === String(id));
          if (p) p.telefono = json.telefono;
        }
      } else {
        alert('Error: ' + (json.error || 'No se pudo guardar'));
      }
    } catch {
      alert('Error de conexión al guardar el teléfono.');
    } finally {
      if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = oldText || 'Guardar'; }
    }
  }, true);
})();

// =========================
// WHATSAPP: Picker de plantillas
// =========================
(() => {
  const ESTADOS = ['nuevo','contactado','confirmado','enviado','en_oficina','entregado','cancelado'];
  const LABEL   = { nuevo:'Nuevo', contactado:'Contactado', confirmado:'Confirmado',
                    enviado:'Enviado', en_oficina:'En oficina', entregado:'Entregado', cancelado:'Cancelado' };

  const buildWaUrl = (telefono, mensaje) => {
    let tel = telefono.replace(/\D/g, '');
    if (tel.startsWith('00')) tel = tel.slice(2);
    if (!tel.startsWith('57')) {
      if (tel.length === 11 && tel[0] === '0') tel = tel.slice(1);
      tel = '57' + tel;
    }
    return `https://wa.me/${tel}?text=${encodeURIComponent(mensaje)}`;
  };

  const getTransportadora = (tipoEntrega) =>
    (tipoEntrega || '').toLowerCase() === 'domicilio' ? 'Envia' : 'Interrapidísimo';

  const getRastreoUrl = (tipoEntrega) =>
    (tipoEntrega || '').toLowerCase() === 'domicilio'
      ? 'https://envia.com/rastreo/'
      : 'https://www.interrapidisimo.com/rastreo/';

  const resolveMsg = (template, data) =>
    template
      .replace(/{nombre}/g,         data.nombre                        || '')
      .replace(/{apellidos}/g,       data.apellidos                     || '')
      .replace(/{producto}/g,        data.producto                      || '')
      .replace(/{cantidad}/g,        data.cantidad                      || '1')
      .replace(/{precio}/g,          data.precio                        || '')
      .replace(/{municipio}/g,       data.municipio                     || '')
      .replace(/{departamento}/g,    data.departamento                  || '')
      .replace(/{transportadora}/g,  getTransportadora(data.tipoEntrega))
      .replace(/{rastreo}/g,         getRastreoUrl(data.tipoEntrega));
      // {guia} NO se reemplaza — el admin lo completa manualmente

  const getTemplate = (estado) => {
    const plantillas = window.__PLANTILLAS__ || {};
    return plantillas[estado]?.mensaje || '';
  };

  let pickerEl = null;

  const closePicker = () => {
    pickerEl?.remove();
    pickerEl = null;
  };

  const openPicker = (data) => {
    closePicker();

    const overlay = document.createElement('div');
    overlay.className = 'wa-picker-overlay';

    const tabsHtml = ESTADOS.map(e => `
      <button class="wa-tab${e === data.estado ? ' is-active' : ''}" data-e="${e}" type="button">
        ${LABEL[e]}
      </button>`).join('');

    const initialMsg = resolveMsg(getTemplate(data.estado), data);

    overlay.innerHTML = `
      <div class="wa-picker-card" role="dialog" aria-modal="true" aria-label="Mensaje WhatsApp">
        <div class="wa-picker-head">
          <strong>📱 Mensaje por WhatsApp</strong>
          <button class="wa-picker-close" type="button" aria-label="Cerrar">&times;</button>
        </div>
        <div class="wa-picker-tabs">${tabsHtml}</div>
        <div class="wa-picker-body">
          <label>Mensaje (editable)</label>
          <textarea id="waMsgTA">${initialMsg}</textarea>
        </div>
        <div class="wa-picker-foot">
          <a class="btn-wa-send" id="waSendBtn" href="#" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i> Abrir WhatsApp
          </a>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);
    pickerEl = overlay;

    const ta      = overlay.querySelector('#waMsgTA');
    const sendBtn = overlay.querySelector('#waSendBtn');

    const updateSendUrl = () => {
      sendBtn.href = buildWaUrl(data.telefono, ta.value);
    };

    updateSendUrl();
    ta.addEventListener('input', updateSendUrl);

    // Tabs
    overlay.querySelectorAll('.wa-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        overlay.querySelectorAll('.wa-tab').forEach(t => t.classList.remove('is-active'));
        tab.classList.add('is-active');
        ta.value = resolveMsg(getTemplate(tab.dataset.e), data);
        updateSendUrl();
      });
    });

    // Cerrar
    overlay.querySelector('.wa-picker-close').addEventListener('click', closePicker);
    overlay.addEventListener('click', e => { if (e.target === overlay) closePicker(); });

    // Cerrar con ESC
    const onKey = e => { if (e.key === 'Escape') { closePicker(); window.removeEventListener('keydown', onKey); } };
    window.addEventListener('keydown', onKey);

    // Foco: si hay {guia} seleccionarlo para que el admin lo reemplace de inmediato
    setTimeout(() => {
      const guiaIdx = ta.value.indexOf('{guia}');
      if (guiaIdx !== -1) {
        ta.focus();
        ta.setSelectionRange(guiaIdx, guiaIdx + 6);
      } else {
        ta.focus();
      }
    }, 60);
  };

  // Interceptar todos los botones .js-wa-open
  document.addEventListener('click', e => {
    const btn = e.target.closest('.js-wa-open');
    if (!btn) return;
    e.preventDefault();

    openPicker({
      telefono:     btn.dataset.telefono     || '',
      nombre:       btn.dataset.nombre       || '',
      apellidos:    btn.dataset.apellidos    || '',
      producto:     btn.dataset.producto     || '',
      cantidad:     btn.dataset.cantidad     || '1',
      precio:       btn.dataset.precio       || '',
      municipio:    btn.dataset.municipio    || '',
      departamento: btn.dataset.departamento || '',
      estado:       btn.dataset.estado       || 'nuevo',
      tipoEntrega:  btn.dataset.tipoEntrega  || '',
    });
  });
})();

// =========================
// PEDIDOS: Campana de notificaciones (estilo Facebook)
// =========================
(() => {
  const POLL_MS      = 30_000;
  const ENDPOINT     = '/tienda_mvc/AdminPedidos/contadores';
  const PEDIDOS_URL  = '/tienda_mvc/AdminPedidos/index';

  // ---- Elementos del DOM
  const bellBtn  = document.getElementById('notifBell');
  const badge    = document.getElementById('notifBadge');
  const dropdown = document.getElementById('notifDropdown');
  const list     = document.getElementById('notifList');
  const clearBtn = document.getElementById('notifClear');

  if (!bellBtn || !badge || !dropdown || !list) return;

  // ---- Estado
  const isOnPedidosPage = !!document.getElementById('contenedorPedidos');

  // -1 = aún no inicializado. El primer poll establece la base sin disparar alertas.
  let lastKnownCount = -1;

  let notifications = [];   // { id, name, product, location, time, cardEl }
  let panelOpen     = false;
  let unreadCount   = 0;    // notifs added since panel was last opened

  // IDs marcados como vistos — persiste en localStorage para no re-aparecer
  const SEEN_KEY   = 'tienda_notif_seen';
  const getSeenIds = () => {
    try { return new Set(JSON.parse(localStorage.getItem(SEEN_KEY) || '[]')); }
    catch { return new Set(); }
  };
  const markSeen = (...ids) => {
    const s = getSeenIds();
    ids.forEach(id => s.add(String(id)));
    try { localStorage.setItem(SEEN_KEY, JSON.stringify([...s].slice(-500))); } catch {}
  };

  // ---- Badge (solo cuenta los no vistos desde la última apertura del panel)
  const updateBadge = () => {
    badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
    badge.style.display = unreadCount > 0 ? 'flex' : 'none';
  };

  // ---- Extrae datos legibles de una tarjeta DOM
  const extractData = (card) => {
    const secs    = card.querySelectorAll('.card-section');
    const client  = secs[0];
    const product = secs[1];
    return {
      id:       String(card.dataset.pedidoId || ''),
      name:     client?.querySelector('strong')?.textContent?.trim()          || 'Cliente',
      location: client?.querySelector('small')?.textContent?.trim()           || '',
      product:  product?.querySelector('.card-value')?.childNodes[0]?.textContent?.trim() || '',
      time:     card.querySelector('.time-badge')?.textContent?.replace(/\s+/g,' ').trim() || 'ahora',
    };
  };

  // ---- Renderiza la lista del panel
  const renderList = () => {
    if (!notifications.length) {
      list.innerHTML = '<li class="notif-item-empty">Sin notificaciones nuevas.</li>';
      return;
    }
    list.innerHTML = '';
    notifications.forEach(notif => {
      const li = document.createElement('li');
      li.className = 'notif-item';
      li.dataset.nid = notif.id;
      li.innerHTML = `
        <div class="notif-item-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="notif-item-body">
          <strong class="notif-item-name">${notif.name}</strong>
          <span class="notif-item-product">${notif.product || 'Pedido nuevo'}</span>
          <span class="notif-item-meta">${notif.location}${notif.time ? ' · ' + notif.time : ''}</span>
        </div>
        <div class="notif-item-dot"></div>
      `;
      li.addEventListener('click', () => handleItemClick(notif));
      list.appendChild(li);
    });
  };

  // ---- Panel open / close
  const openPanel = () => {
    panelOpen = true;
    dropdown.hidden = false;
    dropdown.setAttribute('aria-hidden', 'false');
    bellBtn.setAttribute('aria-expanded', 'true');
    // Marcar todos como vistos al abrir: badge → 0, persiste en localStorage
    markSeen(...notifications.map(n => n.id));
    unreadCount = 0;
    updateBadge();
    renderList();
  };

  const closePanel = () => {
    panelOpen = false;
    dropdown.hidden = true;
    dropdown.setAttribute('aria-hidden', 'true');
    bellBtn.setAttribute('aria-expanded', 'false');
  };

  bellBtn.addEventListener('click', e => {
    e.stopPropagation();
    if (panelOpen) closePanel(); else openPanel();
  });

  document.addEventListener('click', e => {
    if (!panelOpen) return;
    if (!document.getElementById('notifWrap')?.contains(e.target)) closePanel();
  });

  window.addEventListener('keydown', e => {
    if (e.key === 'Escape' && panelOpen) closePanel();
  });

  clearBtn?.addEventListener('click', () => {
    markSeen(...notifications.map(n => n.id));
    notifications = [];
    unreadCount   = 0;
    updateBadge();
    renderList();
    closePanel();
  });

  // ---- Inyectar / resaltar tarjeta en el listado
  const injectCard = (card) => {
    const container = document.getElementById('contenedorPedidos');
    if (!container) return;
    card.classList.add('is-new-arrival');
    const emptyEl = container.querySelector('.cards-empty');
    if (emptyEl) emptyEl.style.display = 'none';
    container.prepend(card);
    markSeen(card.dataset.pedidoId);   // evitar que el mismo ID vuelva a disparar
    setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 80);
  };

  const highlightCard = (card) => {
    card.classList.remove('is-new-arrival');
    void card.offsetWidth;
    card.classList.add('is-new-arrival');
    setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'center' }), 80);
  };

  // ---- Click en un item del panel
  const handleItemClick = (notif) => {
    closePanel();

    if (!isOnPedidosPage) {
      window.location.href = PEDIDOS_URL;
      return;
    }

    const existing = document.querySelector(
      `.order-card[data-pedido-id="${CSS.escape(notif.id)}"]`
    );
    if (existing) {
      highlightCard(existing);
    } else if (notif.cardEl) {
      injectCard(notif.cardEl);
    } else {
      document.getElementById('contenedorPedidos')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    markSeen(notif.id);
    notifications = notifications.filter(n => n.id !== notif.id);
    unreadCount = Math.max(0, unreadCount - 1);
    updateBadge();
  };

  // ---- Fetch nuevas tarjetas desde el servidor
  // Devuelve tarjetas cuyo ID NO está en seenIds (pedidos genuinamente nuevos)
  const fetchNewCards = async () => {
    try {
      const res = await fetch(PEDIDOS_URL, { cache: 'no-store' });
      if (!res.ok) return [];
      const doc     = new DOMParser().parseFromString(await res.text(), 'text/html');
      const seenIds = getSeenIds();
      return Array.from(doc.querySelectorAll('.order-card[data-pedido-id]'))
        .filter(c => !seenIds.has(String(c.dataset.pedidoId)));
    } catch { return []; }
  };

  // ---- Notificación del navegador
  document.addEventListener('click', () => {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  }, { once: true });

  const sendBrowserNotif = (diff) => {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    const n = new Notification('Tienda — Nuevo pedido', {
      body: diff === 1 ? 'Se recibió 1 nuevo pedido.' : `Se recibieron ${diff} nuevos pedidos.`,
      icon: '/tienda_mvc/public/img/logo.png',
    });
    n.onclick = () => { window.focus(); openPanel(); };
  };

  // ---- Animación de la campana
  const ringBell = () => {
    bellBtn.classList.remove('notif-bell--ring');
    void bellBtn.offsetWidth;
    bellBtn.classList.add('notif-bell--ring');
    bellBtn.addEventListener('animationend', () => bellBtn.classList.remove('notif-bell--ring'),
      { once: true });
  };

  // ---- Poll
  const poll = async () => {
    try {
      const res = await fetch(ENDPOINT, {
        headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) return;

      const current = Number((await res.json()).pedidos_nuevos ?? 0);

      if (lastKnownCount === -1) {
        lastKnownCount = current;
        // Marcar como "ya vistos" todos los pedidos que existen AHORA,
        // para que solo los que lleguen DESPUÉS se cuenten como nuevos.
        if (current > 0) {
          const baseCards = await fetchNewCards();   // seenIds aún vacío → devuelve todos
          markSeen(...baseCards.map(c => c.dataset.pedidoId));
        }
        return;
      }

      if (current > lastKnownCount) {
        const diff  = current - lastKnownCount;
        const cards = await fetchNewCards();   // ya filtrado por seenIds internamente
        let added   = 0;

        // Agregar a la lista (ignorar duplicados)
        cards.forEach(card => {
          const data = extractData(card);
          if (!notifications.find(n => n.id === data.id)) {
            notifications.push({ ...data, cardEl: card });
            added++;
          }
        });

        // Si el count subió pero no hay cartas en el rango visible, agregar genérico
        if (cards.length === 0) {
          for (let i = 0; i < diff; i++) {
            notifications.push({
              id: `g-${Date.now()}-${i}`, name: 'Nuevo pedido',
              product: '', location: '', time: 'ahora', cardEl: null,
            });
            added++;
          }
        }

        if (added > 0) {
          unreadCount += added;
          updateBadge();
          if (panelOpen) renderList();
          ringBell();
          sendBrowserNotif(added);
        }
      }

      lastKnownCount = current;
    } catch { /* red no disponible */ }
  };

  updateBadge();
  // Primer poll rápido (1s) para fijar la base. Luego cada 30s.
  setTimeout(poll, 1_000);
  setInterval(poll, POLL_MS);
})();
