document.addEventListener('DOMContentLoaded', () => {
  // ===== Guards =====
  const cfgEl = document.getElementById('landingConfig');
  if (!cfgEl) return;

  // IMPORTANTE: toma el primer form correcto
  const form = document.querySelector('form[action=(window.BASE_URL||"")+"/Landing/enviarPedido"]');
  if (!form) return;

  // OJO: busca el selector dentro del form (para evitar agarrar uno “duplicado” fuera)
  const pricingModeSelect = form.querySelector('#pricingModeSelect');
  const pricingMode = form.querySelector('#pricingMode');

  // Si no existe el selector de modo, esta landing NO usa combo UI
  if (!pricingModeSelect || !pricingMode) return;

  // ===== Config desde dataset =====
  let comboPrice2 = parseInt(cfgEl.dataset.comboPrice2 || '0', 10);
  if (!Number.isFinite(comboPrice2) || comboPrice2 <= 0) comboPrice2 = 115000;

  let COLORS = [];
  try {
    COLORS = JSON.parse(cfgEl.dataset.colors || '[]');
    if (!Array.isArray(COLORS)) COLORS = [];
  } catch (e) {
    COLORS = [];
  }

  // ===== Elementos (siempre desde el FORM) =====
  const summary = document.getElementById('orderSummary');

  const comboWrap = form.querySelector('#comboWrap');
  const individualWrap = form.querySelector('#individualWrap');

  const combosContainer = form.querySelector('#combosContainer');
  const addComboBtn = form.querySelector('#addComboBtn');

  const totalUnitsEl = form.querySelector('#totalUnits');
  const totalHidden = form.querySelector('#cantidad_total');

  let generatedItems = form.querySelector('#generatedItems');

  const indColor = form.querySelector('#indColor');
  const indQty = form.querySelector('#indQty');

  // Si no existe contenedor combos, no hay nada que hacer (evita errores)
  if (!combosContainer) return;

  // ===== Helpers =====
  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function optionsHtml() {
    return ['<option value="">Selecciona un color</option>']
      .concat(COLORS.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`))
      .join('');
  }

  function formatCOP(num) {
    try {
      return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0
      }).format(num);
    } catch (e) {
      return '$' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
  }

  function totalConDescuento(units, priceUnit, d2, d3, act) {
    if (units <= 0) return 0;
    if (act !== 1) return priceUnit * units;

    const p2 = 1 - (d2 / 100);
    const p3 = 1 - (d3 / 100);

    if (units === 1) return priceUnit;

    let total = 0;
    total += priceUnit;                  // 1ra
    total += priceUnit * p2;             // 2da
    if (units >= 3) total += priceUnit * p3 * (units - 2); // 3ra+
    return total;
  }

  function totalCombos(comboCount) {
    comboCount = Math.max(1, comboCount);
    return comboCount * comboPrice2;
  }

  function comboTemplate() {
    const combo = document.createElement('div');
    combo.className = 'combo-block';
    combo.style.marginBottom = '12px';
    combo.innerHTML = `
      <div class="color-qty-row">
        <select class="combo-color" required>${optionsHtml()}</select>
        <select class="combo-color" required>${optionsHtml()}</select>
        <button type="button" class="remove-color-qty remove-combo" aria-label="Quitar combo">
          <span class="remove-icon">×</span>
          <span class="remove-text">Quitar combo</span>
        </button>
      </div>
      <small class="help">Este bloque representa 1 combo (2 unidades).</small>
    `;
    return combo;
  }

  function getComboCount() {
    return combosContainer.querySelectorAll('.combo-block').length;
  }

  function getUnits() {
    const mode = pricingMode.value || 'combo';

    if (mode === 'combo') {
      return Math.max(1, getComboCount()) * 2;
    }

    // modo individual (si no existe indQty, NO podemos calcular bien -> fallback 1)
    if (!indQty) return 1;

    const q = parseInt(indQty.value || '1', 10);
    if (!Number.isFinite(q) || q < 1) return 1;
    return Math.min(20, q);
  }

  function updateSummary() {
    if (!summary) return;

    const units = getUnits();

    const priceUnit = parseFloat(summary.dataset.priceUnit || '0') || 0;
    const priceRegular = parseFloat(summary.dataset.priceRegular || summary.dataset.priceUnit || '0') || priceUnit;

    const d2 = parseInt(summary.dataset.d2 || '15', 10);
    const d3 = parseInt(summary.dataset.d3 || '20', 10);
    const act = parseInt(summary.dataset.act || '1', 10);

    const subtotal = priceUnit * units;

    let totalPay = 0;
    if ((pricingMode.value || 'combo') === 'combo') {
      totalPay = totalCombos(getComboCount());
    } else {
      totalPay = totalConDescuento(units, priceUnit, d2, d3, act);
    }

    const discount = Math.max(0, subtotal - totalPay);
    const ahorroTotal = Math.max(0, (priceRegular * units) - totalPay);

    const qtyEl = document.getElementById('summaryQty');
    const qtyWordEl = document.getElementById('summaryQtyWord');
    const subEl = document.getElementById('summarySubtotal');
    const disEl = document.getElementById('summaryDiscount');
    const totEl = document.getElementById('summaryTotal');
    const savEl = document.getElementById('summarySave');

    if (qtyEl) qtyEl.textContent = String(units);
    if (qtyWordEl) qtyWordEl.textContent = (units > 1) ? 'unidades' : 'unidad';

    if (subEl) subEl.textContent = formatCOP(subtotal);
    if (disEl) disEl.textContent = formatCOP(discount);
    if (totEl) totEl.textContent = formatCOP(totalPay);
    if (savEl) savEl.textContent = formatCOP(ahorroTotal);

    if (totalUnitsEl) totalUnitsEl.textContent = String(units);
    if (totalHidden) totalHidden.value = String(units);

    // Debug útil (puedes quitarlo luego)
    // console.log('[pricing] mode=', pricingMode.value, 'units=', units, 'total=', totalPay);
  }

  function setMode(mode) {
    pricingMode.value = mode;
    pricingModeSelect.value = mode;

    if (mode === 'combo') {
      if (comboWrap) comboWrap.style.display = '';
      if (individualWrap) individualWrap.style.display = 'none';

      if (getComboCount() < 1) combosContainer.appendChild(comboTemplate());
    } else {
      if (comboWrap) comboWrap.style.display = 'none';
      if (individualWrap) individualWrap.style.display = '';

      // si por alguna razón indQty no existe, avisa en consola
      if (!indQty) {
        console.warn('[pricing] No existe #indQty dentro del form. Units quedará en 1.');
      } else if (!indQty.value || indQty.value === '0') {
        indQty.value = '1';
      }
    }

    updateSummary();

    // Reforzar: algunos navegadores tardan en reflow cuando cambias display
    setTimeout(updateSummary, 0);
    setTimeout(updateSummary, 50);
  }

  // ===== Init =====
  if (getComboCount() < 1) combosContainer.appendChild(comboTemplate());
  setMode(pricingModeSelect.value || 'combo');

  // ===== Eventos =====
  pricingModeSelect.addEventListener('change', () => {
    setMode(pricingModeSelect.value);
  });

  addComboBtn?.addEventListener('click', () => {
    combosContainer.appendChild(comboTemplate());
    updateSummary();
  });

  combosContainer.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-combo');
    if (!btn) return;

    const block = btn.closest('.combo-block');
    if (!block) return;

    if (getComboCount() <= 1) {
      block.querySelectorAll('select.combo-color').forEach(s => s.value = '');
    } else {
      block.remove();
    }
    updateSummary();
  });

  combosContainer.addEventListener('change', (e) => {
    if (e.target && e.target.classList.contains('combo-color')) updateSummary();
  });

  indQty?.addEventListener('change', updateSummary);
  indColor?.addEventListener('change', updateSummary);

  // ===== Submit: generar arrays para backend =====
  form.addEventListener('submit', (e) => {
    if (!generatedItems) {
      generatedItems = document.createElement('div');
      generatedItems.id = 'generatedItems';
      form.appendChild(generatedItems);
    }

    generatedItems.innerHTML = '';

    const addItem = (color, qty) => {
      const c = (color || '').trim();
      const q = parseInt(qty || '0', 10);
      if (!c || !Number.isFinite(q) || q <= 0) return false;

      const inC = document.createElement('input');
      inC.type = 'hidden';
      inC.name = 'color_item[]';
      inC.value = c;

      const inQ = document.createElement('input');
      inQ.type = 'hidden';
      inQ.name = 'qty_item[]';
      inQ.value = String(q);

      generatedItems.appendChild(inC);
      generatedItems.appendChild(inQ);
      return true;
    };

    // Evitar duplicados legacy
    const legacyWrap = document.getElementById('colorsQtyWrap');
    if (legacyWrap) {
      const legacyInputs = legacyWrap.querySelectorAll('select[name="color_item[]"], select[name="qty_item[]"]');
      legacyInputs.forEach(el => el.disabled = true);
    }

    const mode = pricingMode.value || 'combo';
    let generatedCount = 0;

    if (mode === 'combo') {
      const blocks = combosContainer.querySelectorAll('.combo-block');
      if (!blocks.length) {
        e.preventDefault();
        alert('Debes tener al menos 1 combo.');
        return;
      }

      for (const b of blocks) {
        const selects = b.querySelectorAll('select.combo-color');
        if (selects.length !== 2) {
          e.preventDefault();
          alert('Cada combo debe tener exactamente 2 selectores de color.');
          return;
        }

        const c1 = (selects[0].value || '').trim();
        const c2 = (selects[1].value || '').trim();

        if (!c1 || !c2) {
          e.preventDefault();
          alert('Selecciona los 2 colores de cada combo.');
          return;
        }

        if (addItem(c1, 1)) generatedCount++;
        if (addItem(c2, 1)) generatedCount++;
      }

      const units = blocks.length * 2;
      if (totalHidden) totalHidden.value = String(units);

    } else {
      const c = (indColor?.value || '').trim();
      const q = parseInt(indQty?.value || '1', 10);

      if (!c) {
        e.preventDefault();
        alert('Selecciona un color para la compra individual.');
        return;
      }
      if (!Number.isFinite(q) || q < 1) {
        e.preventDefault();
        alert('Selecciona una cantidad válida.');
        return;
      }

      if (addItem(c, q)) generatedCount++;
      if (totalHidden) totalHidden.value = String(q);
    }

    if (generatedCount <= 0) {
      e.preventDefault();
      alert('No se generaron los ítems del pedido. Revisa selección de colores/cantidad.');
      return;
    }
  });

  // Primer cálculo
  updateSummary();
});
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[action=(window.BASE_URL||"")+"/Landing/enviarPedido"]');
  if (!form) return;

  const pricingMode = document.getElementById('pricingMode');
  if (!pricingMode || pricingMode.value !== 'individual') return;

  const wrap = document.getElementById('colorsQtyWrap');
  const addBtn = document.getElementById('addColorQtyBtn');
  if (!wrap || !addBtn) return;

  function recalc() {
    if (window.LandingPricingSummary && typeof window.LandingPricingSummary.updateSummary === 'function') {
      window.LandingPricingSummary.updateSummary();
    }
    document.dispatchEvent(new Event('landing:recalc'));
  }

  function getTemplateRow() {
    return wrap.querySelector('.color-qty-row') || wrap.firstElementChild;
  }

  function cloneRow() {
    const tpl = getTemplateRow();
    if (!tpl) return null;

    const row = tpl.cloneNode(true);

    const colorSel = row.querySelector('select[name="color_item[]"]');
    const qtySel = row.querySelector('select[name="qty_item[]"]');

    if (colorSel) colorSel.value = '';
    if (qtySel) qtySel.value = '1';

    const removeBtn = row.querySelector('.remove-color-qty');
    if (removeBtn) removeBtn.type = 'button';

    return row;
  }

  addBtn.addEventListener('click', (e) => {
    e.preventDefault();
    const row = cloneRow();
    if (!row) return;
    wrap.appendChild(row);
    recalc();
  });

  wrap.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-color-qty');
    if (!btn) return;

    const row = btn.closest('.color-qty-row');
    if (!row) return;

    const rows = wrap.querySelectorAll('.color-qty-row');

    if (rows.length <= 1) {
      const colorSel = row.querySelector('select[name="color_item[]"]');
      const qtySel = row.querySelector('select[name="qty_item[]"]');
      if (colorSel) colorSel.value = '';
      if (qtySel) qtySel.value = '1';
    } else {
      row.remove();
    }

    recalc();
  });

  wrap.addEventListener('change', (e) => {
    const t = e.target;
    if (!t) return;
    if (t.matches('select[name="color_item[]"], select[name="qty_item[]"]')) {
      recalc();
    }
  });

  recalc();
});
