/* /tienda_mvc/public/js/pricing-summary.js
   Resumen de compra universal (combo ON / combo OFF)
*/
document.addEventListener('DOMContentLoaded', () => {
  const summary = document.getElementById('orderSummary');
  if (!summary) return;

  const form = summary.closest('form') || document.getElementById('formPedido');
  if (!form) return;

  const totalHidden = document.getElementById('cantidad_total');
  const totalUnitsEl = document.getElementById('totalUnits');

  // Puede existir o no
  const pricingMode = document.getElementById('pricingMode'); // hidden input
  const combosContainer = document.getElementById('combosContainer'); // solo en combo UI
  const indQty = document.getElementById('indQty');
  const indColor = document.getElementById('indColor');

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

  function getMode() {
    // Si existe input hidden, úsalo. Si no, asumimos individual.
    const m = (pricingMode && pricingMode.value) ? pricingMode.value : 'individual';
    return (m === 'combo') ? 'combo' : 'individual';
  }

  function getComboCount() {
    if (!combosContainer) return 0;
    return combosContainer.querySelectorAll('.combo-block').length;
  }

  function getUnitsNormal() {
    // Suma todas las cantidades del modo normal (varios colores)
    const qtySelects = form.querySelectorAll('select[name="qty_item[]"]');
    if (!qtySelects.length) {
      // Sin colores: leer directamente del hidden #cantidad_total
      const cantEl = document.getElementById('cantidad_total');
      const q = parseInt(cantEl?.value || '1', 10);
      return Math.max(1, Math.min(20, q));
    }

    let total = 0;
    qtySelects.forEach(sel => {
      if (sel.disabled) return;
      const q = parseInt(sel.value || '0', 10);
      if (Number.isFinite(q) && q > 0) total += q;
    });

    return Math.max(1, Math.min(999, total));
  }

  function getUnits() {
    const mode = getMode();

    if (mode === 'combo') {
      // Combo = 2 unidades por combo
      const cc = Math.max(1, getComboCount());
      return cc * 2;
    }

    // Si estás en la vista combo pero modo individual, usa indQty
    if (indQty && !indQty.disabled && indQty.offsetParent !== null) {
      const q = parseInt(indQty.value || '1', 10);
      if (Number.isFinite(q) && q > 0) return Math.min(20, q);
    }

    // Modo normal clásico
    return getUnitsNormal();
  }

  function updateSummary() {
    const units = getUnits();

    const priceUnit = parseFloat(summary.dataset.priceUnit || '0') || 0;
    const priceRegular = parseFloat(summary.dataset.priceRegular || summary.dataset.priceUnit || '0') || priceUnit;

    const d2 = parseInt(summary.dataset.d2 || '15', 10);
    const d3 = parseInt(summary.dataset.d3 || '20', 10);
    const act = parseInt(summary.dataset.act || '1', 10);

    const comboPrice2 = parseFloat(summary.dataset.priceCombo2 || '0') || 0;

    const subtotal = priceUnit * units;

    let totalPay = 0;
    const mode = getMode();

    if (mode === 'combo') {
      const cc = Math.max(1, getComboCount());
      const p2 = comboPrice2 > 0 ? comboPrice2 : subtotal; // fallback
      totalPay = cc * p2;
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
    if (savEl) {
      savEl.textContent = formatCOP(ahorroTotal);
      const saveRow = document.getElementById('summarySaveRow');
      if (saveRow) saveRow.style.display = ahorroTotal > 0 ? '' : 'none';
    }

    if (totalUnitsEl) totalUnitsEl.textContent = String(units);
    if (totalHidden) totalHidden.value = String(units);
  }

  // Exponer para que otros scripts lo llamen si quieren
  window.LandingPricingSummary = window.LandingPricingSummary || {};
  window.LandingPricingSummary.updateSummary = updateSummary;

  // ===== Listeners (modo normal) =====
  form.addEventListener('change', (e) => {
    const t = e.target;

    // qty_item[] modo normal
    if (t && t.matches('select[name="qty_item[]"]')) {
      updateSummary();
      return;
    }

    // modo individual dentro de combo
    if (t && (t.id === 'indQty' || t.id === 'indColor')) {
      updateSummary();
      return;
    }

    // si cambia pricing_mode hidden, recalcular
    if (t && t.id === 'pricingMode') {
      updateSummary();
    }
  });

  // ===== Listener (modo combo) vía evento custom =====
  document.addEventListener('landing:recalc', () => updateSummary());

  // Primer render
  updateSummary();
});
