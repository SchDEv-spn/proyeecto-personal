/* /tienda_mvc/public/js/pricing-summary.js
   Motor UNICO de precio de la landing.

   Antes habia dos: este archivo y un updateOrderSummary() dentro del script
   inline de la vista. Los dos escribian #summaryTotal, #summaryDiscount y
   #summarySave, pero no calculaban lo mismo — "Ahorras" salia contra el
   precio de venta en uno y contra el precio regular en el otro, el descuento
   llevaba signo menos solo en uno, y cada uno formateaba la moneda a su
   manera. Ganaba el que corriera de ultimo. Ahora manda solo este.

   Cubre: el resumen de compra, la tira de precio del paso 2, la insignia del
   carrito y el total flotante. Todo se recalcula con el evento
   'landing:recalc', que es lo que disparan los pasos del formulario.

   Sin sintaxis ES2020 (?. y ??) a proposito: no hay build, el archivo se
   sirve tal cual, y en un WebView viejo — el navegador de Facebook, de donde
   llega la pauta — eso no degrada nada, revienta el archivo entero.
*/
document.addEventListener('DOMContentLoaded', function () {
  var summary = document.getElementById('orderSummary');
  if (!summary) return;

  var form = summary.closest('form') || document.getElementById('formPedido');
  if (!form) return;

  var totalHidden = document.getElementById('cantidad_total');
  var totalUnitsEl = document.getElementById('totalUnits');

  // Puede existir o no
  var pricingMode = document.getElementById('pricingMode'); // hidden input
  var combosContainer = document.getElementById('combosContainer'); // solo en combo UI
  var indQty = document.getElementById('indQty');

  /* Mismo formato que el resto de la pagina. Intl con style:'currency' pinta
     "$ 235.850" con un espacio que ningun otro precio de la landing tiene, y
     ese formato acababa dentro del boton de confirmar. */
  function formatCOP(num) {
    var n = Math.round(num);
    try {
      return '$' + n.toLocaleString('es-CO');
    } catch (e) {
      return '$' + String(n).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
  }

  function totalConDescuento(units, priceUnit, d2, d3, act) {
    if (units <= 0) return 0;
    if (act !== 1) return priceUnit * units;

    var p2 = 1 - (d2 / 100);
    var p3 = 1 - (d3 / 100);

    if (units === 1) return priceUnit;

    var total = 0;
    total += priceUnit;                  // 1ra
    total += priceUnit * p2;             // 2da
    if (units >= 3) total += priceUnit * p3 * (units - 2); // 3ra+
    return total;
  }

  function getMode() {
    // Si existe input hidden, úsalo. Si no, asumimos individual.
    var m = (pricingMode && pricingMode.value) ? pricingMode.value : 'individual';
    return (m === 'combo') ? 'combo' : 'individual';
  }

  function getComboCount() {
    if (!combosContainer) return 0;
    return combosContainer.querySelectorAll('.combo-block').length;
  }

  function getUnitsNormal() {
    var filas = form.querySelectorAll('.color-row');

    // Sin colores: leer directamente del hidden #cantidad_total
    if (!filas.length) {
      var cantEl = document.getElementById('cantidad_total');
      var q = parseInt((cantEl && cantEl.value) || '1', 10);
      if (!isFinite(q)) q = 1;
      return Math.max(1, Math.min(20, q));
    }

    /* Solo cuentan las filas que ya tienen un color elegido. Antes se sumaban
       TODOS los select de cantidad, con color o sin el, asi que en cuanto el
       comprador tocaba "quiero otro color diferente" aparecia una unidad
       fantasma: el resumen le cobraba una de mas y #cantidad_total se iba con
       ella. */
    var total = 0;
    Array.prototype.forEach.call(filas, function (fila) {
      var cSel = fila.querySelector('.color-item-sel');
      var qSel = fila.querySelector('select[name="qty_item[]"]');
      if (!cSel || !cSel.value || !qSel || qSel.disabled) return;
      var q = parseInt(qSel.value || '0', 10);
      if (isFinite(q) && q > 0) total += q;
    });

    return Math.max(1, Math.min(999, total));
  }

  function getUnits() {
    if (getMode() === 'combo') {
      // Combo = 2 unidades por combo
      return Math.max(1, getComboCount()) * 2;
    }

    // Si estás en la vista combo pero modo individual, usa indQty
    if (indQty && !indQty.disabled && indQty.offsetParent !== null) {
      var q = parseInt(indQty.value || '1', 10);
      if (isFinite(q) && q > 0) return Math.min(20, q);
    }

    return getUnitsNormal();
  }

  function texto(id, valor) {
    var el = document.getElementById(id);
    if (el) el.textContent = valor;
  }

  function updateSummary() {
    var units = getUnits();

    var priceUnit = parseFloat(summary.dataset.priceUnit || '0') || 0;
    var priceRegular = parseFloat(summary.dataset.priceRegular || summary.dataset.priceUnit || '0') || priceUnit;

    var d2 = parseInt(summary.dataset.d2 || '15', 10);
    var d3 = parseInt(summary.dataset.d3 || '20', 10);
    var act = parseInt(summary.dataset.act || '1', 10);

    var comboPrice2 = parseFloat(summary.dataset.priceCombo2 || '0') || 0;

    var subtotal = priceUnit * units;

    var totalPay;
    if (getMode() === 'combo') {
      var cc = Math.max(1, getComboCount());
      totalPay = cc * (comboPrice2 > 0 ? comboPrice2 : subtotal);
    } else {
      totalPay = totalConDescuento(units, priceUnit, d2, d3, act);
    }

    var discount = Math.max(0, subtotal - totalPay);
    var ahorroTotal = Math.max(0, (priceRegular * units) - totalPay);

    texto('summaryQty', String(units));
    texto('summaryQtyWord', units > 1 ? 'unidades' : 'unidad');
    texto('summarySubtotal', formatCOP(subtotal));
    texto('summaryDiscount', discount > 0 ? '-' + formatCOP(discount) : formatCOP(0));
    texto('summaryTotal', formatCOP(totalPay));
    texto('summarySave', formatCOP(ahorroTotal));

    var saveRow = document.getElementById('summarySaveRow');
    if (saveRow) saveRow.style.display = ahorroTotal > 0 ? 'flex' : 'none';

    /* Una linea de "Descuento $0" no informa de nada: solo ensucia el
       resumen justo donde el comprador comprueba lo que va a pagar. */
    var discountRow = document.getElementById('summaryDiscountRow');
    if (discountRow) discountRow.style.display = discount > 0 ? 'flex' : 'none';

    /* Que color va en la caja. Es el unico dato del pedido que el comprador
       no puede verificar despues de darle a confirmar. */
    var colorRow = document.getElementById('summaryColorRow');
    if (colorRow) {
      var partes = [];
      Array.prototype.forEach.call(form.querySelectorAll('.color-row'), function (fila) {
        var cSel = fila.querySelector('.color-item-sel');
        var qSel = fila.querySelector('select[name="qty_item[]"]');
        if (!cSel || !cSel.value) return;
        var q = parseInt((qSel && qSel.value) || '1', 10);
        partes.push(q > 1 ? cSel.value + ' ×' + q : cSel.value);
      });
      texto('summaryColor', partes.join(' · '));
      colorRow.style.display = partes.length ? 'flex' : 'none';
    }

    // Tira de precio del paso 2 y insignia del carrito: mismo numero, mismo formato.
    texto('pricePreviewAmt', formatCOP(totalPay));
    texto('modalCartBadge', String(units));
    texto('qtyUnitLbl', units > 1 ? 'unidades' : 'unidad');

    if (totalUnitsEl) totalUnitsEl.textContent = String(units);
    if (totalHidden) totalHidden.value = String(units);

    /* El total flotante, la barra del producto y el texto del boton de
       confirmar leen de aqui. Se avisa despues de escribir, no antes. */
    document.dispatchEvent(new CustomEvent('landing:precio', {
      detail: { units: units, total: totalPay, texto: formatCOP(totalPay) }
    }));
  }

  // Exponer para que otros scripts lo llamen si quieren
  window.LandingPricingSummary = window.LandingPricingSummary || {};
  window.LandingPricingSummary.updateSummary = updateSummary;
  window.LandingPricingSummary.formatCOP = formatCOP;

  // ===== Listeners (modo normal) =====
  form.addEventListener('change', function (e) {
    var t = e.target;
    if (!t) return;

    if (t.matches('select[name="qty_item[]"]') ||
        t.matches('select[name="color_item[]"]') ||
        t.id === 'indQty' || t.id === 'indColor' || t.id === 'pricingMode') {
      updateSummary();
    }
  });

  // ===== Listener (modo combo / pasos del formulario) vía evento custom =====
  document.addEventListener('landing:recalc', function () { updateSummary(); });

  // Primer render
  updateSummary();
});
