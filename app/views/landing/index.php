<?php
// $producto viene del controlador
$precio_venta     = (float)($producto['precio_venta'] ?? 0);
$precio_proveedor = (float)($producto['precio_proveedor'] ?? 0);

$precio_regular = (float)($producto['precio_regular'] ?? 0);
if ($precio_regular <= 0 || $precio_regular < $precio_venta) {
    $precio_regular = $precio_venta;
}

$ahorro = max(0, $precio_regular - $precio_venta);



// Config general de la landing
$config = $config ?? [];
$cfg    = $config;

// Contador de pedidos reales (últimos 30 días) con mínimo para social proof
$pedidosRecientes = max(12, (int)($pedidos_recientes ?? 0));



$comboEnabled = (int)($cfg['combo_enabled'] ?? 0);
$comboPrice2  = (int)($cfg['combo_price_2'] ?? 0);
if ($comboPrice2 <= 0) $comboPrice2 = 115000; // fallback


// ===== HERO =====
$heroTitle       = $cfg['hero_title']        ?? ($producto['nombre'] ?? 'Nombre del producto');
$heroSubtitle    = $cfg['hero_subtitle']     ?? 'Subtítulo potente que explique el beneficio principal del producto en una frase clara.';
$heroNote        = $cfg['hero_note']         ?? 'Promoción válida solo por tiempo limitado.';
$heroButtonText  = $cfg['hero_button_text']  ?? '¡Necesito el mío!';
$heroMediaType   = $cfg['hero_media_type']   ?? 'imagen';
$heroMediaPath   = $cfg['hero_media_path']
    ?? ($producto['imagen_principal'] ?? '/tienda_mvc/public/img/producto.png');

$benefitsMediaType = $cfg['benefits_media_type'] ?? 'imagen';
$porqueMediaType   = $cfg['porque_media_type']   ?? 'imagen';
// ===== BENEFICIOS =====
$benefitsTitle = $cfg['benefits_title'] ?? 'Beneficios clave para ti';

$benefits = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'benefit_' . $i;
    if (!empty($cfg[$key]) && trim($cfg[$key]) !== '') {
        $benefits[] = $cfg[$key];
    }
}
$benefitsMediaPath = $cfg['benefits_media_path'] ?? '/tienda_mvc/public/img/producto/uso-1.jpg';

// ===== GALERÍA =====
$galleryPaths = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'gallery_' . $i . '_path';
    if (!empty($cfg[$key]) && trim($cfg[$key]) !== '') {
        $galleryPaths[] = $cfg[$key];
    }
}

// ===== COUNTDOWN =====
$countdownTitle = $cfg['countdown_title'] ?? 'La promoción termina en:';
$countdownText  = $cfg['countdown_text']  ?? 'Después de que el contador llegue a cero, el precio puede volver a la normalidad.';

// ===== POR QUÉ TE ENCANTARÁ =====
$porqueTitle = $cfg['porque_title'] ?? '¿Por qué te encantará este producto?';
$porqueText  = $cfg['porque_text']
    ?? 'Aquí explicas de forma emocional y concreta qué hace que este producto sea diferente:
       qué sentirán, qué problema deja de existir, qué resultado obtienen.';

$porqueBullets = [];
foreach (['porque_bullet1', 'porque_bullet2', 'porque_bullet3'] as $key) {
    if (!empty($cfg[$key]) && trim($cfg[$key]) !== '') {
        $porqueBullets[] = $cfg[$key];
    }
}
$porqueMediaPath = $cfg['porque_media_path'] ?? '/tienda_mvc/public/img/producto/uso-1.jpg';

// ===== TESTIMONIOS =====
$test1Name  = $cfg['test1_name']       ?? 'María G.';
$test1Text  = $cfg['test1_text']       ?? 'Desde que lo uso, mi día a día es mucho más fácil. Llegó rápido y en perfecto estado.';
$test1Photo = $cfg['test1_photo_path'] ?? '/tienda_mvc/public/img/producto/uso-1.jpg';

$test2Name  = $cfg['test2_name']       ?? 'Carlos R.';
$test2Text  = $cfg['test2_text']       ?? 'Muy buena atención, me explicaron todo por WhatsApp y el producto es tal cual a las fotos.';
$test2Photo = $cfg['test2_photo_path'] ?? '/tienda_mvc/public/img/producto/uso-1.jpg';

$test3Name  = $cfg['test3_name']       ?? 'Laura P.';
$test3Text  = $cfg['test3_text']       ?? 'Lo recomiendo totalmente. Me dieron confianza con el pago contraentrega y cumplió 10/10.';
$test3Photo = $cfg['test3_photo_path'] ?? '/tienda_mvc/public/img/producto/uso-1.jpg';

// ===== TESTIMONIOS WHATSAPP (editable) =====
$waEnabled    = isset($cfg['wa_enabled']) ? (int)$cfg['wa_enabled'] : 1;
$waTitle      = $cfg['wa_title'] ?? '📱 Testimonios Reales de WhatsApp';
$waSubtitle   = $cfg['wa_subtitle'] ?? 'Capturas reales de conversaciones con nuestros clientes';
$waFooterNote = $cfg['wa_footer_note'] ?? '💡 Desliza para ver más • Capturas 100% reales de WhatsApp';

$waItems = [];
for ($i = 1; $i <= 5; $i++) {
    $waItems[] = [
        'name'  => $cfg["wa{$i}_name"] ?? '',
        'time'  => $cfg["wa{$i}_time"] ?? '',
        'text'  => $cfg["wa{$i}_text"] ?? '',
        'image' => $cfg["wa{$i}_image_path"] ?? '',
    ];
}

// ===== FAQ =====
$faq1_q = $cfg['faq1_q'] ?? '¿Cuánto tarda en llegar mi pedido?';
$faq1_a = $cfg['faq1_a'] ?? 'Los tiempos de entrega pueden variar según tu ciudad, pero normalmente tu pedido llega entre 2 y 5 días hábiles después de la confirmación.';

$faq2_q = $cfg['faq2_q'] ?? '¿Puedo pagar contraentrega?';
$faq2_a = $cfg['faq2_a'] ?? 'Sí, en la mayoría de las ciudades manejamos pago contraentrega: pagas solo cuando el mensajero te entrega el producto.';

$faq3_q = $cfg['faq3_q'] ?? '¿Qué pasa si el producto llega dañado o con problemas?';
$faq3_a = $cfg['faq3_a'] ?? 'Si el producto llega con algún defecto o no es lo que esperabas, te ayudamos con cambio o solución según nuestra política de garantía.';

$faq4_q = $cfg['faq4_q'] ?? '';
$faq4_a = $cfg['faq4_a'] ?? '';
$faq5_q = $cfg['faq5_q'] ?? '';
$faq5_a = $cfg['faq5_a'] ?? '';
$faq6_q = $cfg['faq6_q'] ?? '';
$faq6_a = $cfg['faq6_a'] ?? '';

// ===== TABLA COMPARATIVA =====
$comparisonTitle = $cfg['comparison_title'] ?? 'La diferencia que hace este producto';
$comparisonRows  = [];
for ($i = 1; $i <= 5; $i++) {
    $without = trim($cfg["comparison_{$i}_without"] ?? '');
    $with    = trim($cfg["comparison_{$i}_with"]    ?? '');
    if ($without !== '' || $with !== '') {
        $comparisonRows[] = ['without' => $without, 'with' => $with];
    }
}
$showComparison = !empty($comparisonRows);

// ===== AUTORIDAD / CREDIBILIDAD =====
$authorityEnabled    = (int)($cfg['authority_enabled']    ?? 0);
$authorityTitle      = $cfg['authority_title']      ?? '¿Por qué confiar en nosotros?';
$authorityYears      = $cfg['authority_years']      ?? '3';
$authorityDeliveries = $cfg['authority_deliveries'] ?? '5.000+';
$authorityRating     = $cfg['authority_rating']     ?? '4.9';
$authorityGuarantee  = $cfg['authority_guarantee']  ?? 'Garantía de satisfacción';

// ===== FOOTER =====
$footerText = $cfg['footer_text'] ?? ('© ' . date('Y') . ' Tu Marca. Todos los derechos reservados.');

// ===== CTAs dinámicas =====
$ctaBenefitsText       = $cfg['cta_benefits_text']
    ?? 'Ya sabes lo que hace. El siguiente paso es recibirlo en casa.';
$ctaBenefitsButton     = $cfg['cta_benefits_button'] ?? 'Quiero aprovechar la oferta';

$ctaGalleryText        = $cfg['cta_gallery_text']
    ?? 'Lo que ves es lo que llega. Sin sorpresas, sin excusas.';
$ctaGalleryButton      = $cfg['cta_gallery_button'] ?? 'Lo quiero igual que en las fotos';

$ctaPorqueText         = $cfg['cta_porque_text']
    ?? 'Miles lo recibieron. Tú eres el siguiente.';
$ctaPorqueButton       = $cfg['cta_porque_button'] ?? 'Quiero sentir ese cambio';

$ctaTestimonialsText   = $cfg['cta_testimonials_text']
    ?? 'Ellos ya lo tienen. Tu pedido tarda menos de 2 minutos.';
$ctaTestimonialsButton = $cfg['cta_testimonials_button'] ?? 'Quiero ser el próximo en recibirlo';

$ctaFaqText            = $cfg['cta_faq_text']
    ?? 'Dudas resueltas. Esto solo falta: hacer tu pedido.';
$ctaFaqButton          = $cfg['cta_faq_button'] ?? 'Sí, quiero pedirlo ahora';

$ctaStickyMobileText   = $cfg['cta_sticky_mobile_text'] ?? '🔥 Aprovechar oferta hoy';

// ===== PARA QUIÉN ES =====
$paraQuienSiItems = [];
for ($i = 1; $i <= 4; $i++) {
    $k = "para_quien_si_{$i}";
    if (!empty($cfg[$k])) $paraQuienSiItems[] = $cfg[$k];
}
if (empty($paraQuienSiItems)) {
    $paraQuienSiItems = [
        'Buscas un resultado real sin complicaciones',
        'Ya probaste otras opciones y no te convencieron',
        'Quieres algo que llegue a tu puerta con envío gratis',
        'Valoras calidad respaldada por cientos de clientes',
    ];
}

$paraQuienNoItems = [];
for ($i = 1; $i <= 3; $i++) {
    $k = "para_quien_no_{$i}";
    if (!empty($cfg[$k])) $paraQuienNoItems[] = $cfg[$k];
}
if (empty($paraQuienNoItems)) {
    $paraQuienNoItems = [
        'Esperas resultados mágicos de un solo uso',
        'No estás dispuesto a recibir al mensajero',
        'Buscas únicamente el precio más bajo del mercado',
    ];
}

// ===== ANTES Y DESPUÉS =====
$antesPath   = $cfg['antes_path']          ?? '';
$despuesPath = $cfg['despues_path']        ?? '';
$antesLabel  = $cfg['antes_label']         ?? 'Antes';
$despuesLabel= $cfg['despues_label']       ?? 'Después';
$antesDespuesTitle = $cfg['antes_despues_title'] ?? 'La diferencia que notarás';
$showAntesDespues  = !empty($antesPath) && !empty($despuesPath);

// ===== SECCIONES VISIBLES =====
$showBenefits     = (int)($cfg['show_benefits']      ?? 1);
$showGallery      = (int)($cfg['show_gallery']       ?? 1);
$showAntesDespues = (int)($cfg['show_antes_despues'] ?? 1) && !empty($antesPath) && !empty($despuesPath);
$showComoFunciona = (int)($cfg['show_como_funciona'] ?? 1);
$showCountdown    = (int)($cfg['show_countdown']     ?? 1);
$showPorque       = (int)($cfg['show_porque']        ?? 1);
$showParaQuien    = (int)($cfg['show_para_quien']    ?? 1);
$showTestimonios  = (int)($cfg['show_testimonios']   ?? 1);
$showFaqs         = (int)($cfg['show_faqs']          ?? 1);

// ===== URGENCIA / WA / BADGE =====
$urgencyStock      = max(1, (int)($cfg['urgency_stock']      ?? 12));
$countdownMinutes  = max(1, (int)($cfg['countdown_minutes']  ?? 25));
$waPhone           = (string)preg_replace('/\D/', '', $cfg['wa_phone'] ?? '573023959721');
$heroBadgeStars    = htmlspecialchars($cfg['hero_badge_stars']     ?? '4.9');
$heroBadgeCustomers= htmlspecialchars($cfg['hero_badge_customers'] ?? '+3.200 clientes felices');

// ===== ANNOUNCEMENT BAR =====
$announcementItems = [];
for ($i = 1; $i <= 6; $i++) {
    $k = "announcement_item_{$i}";
    if (!empty($cfg[$k])) $announcementItems[] = $cfg[$k];
}
if (empty($announcementItems)) {
    $announcementItems = [
        '🔥 Quedan pocas unidades',
        '🚚 Envío gratis a todo el país',
        '💳 Pago contraentrega',
        '⭐ ' . $heroBadgeCustomers,
        '📦 Empaque discreto y seguro',
    ];
}

// ===== HERO TRUST ROW =====
$heroTrust1 = $cfg['hero_trust_1'] ?? '✅ Pago al recibir';
$heroTrust2 = $cfg['hero_trust_2'] ?? '🚚 Envío gratis';
$heroTrust3 = $cfg['hero_trust_3'] ?? '🔄 Cambios sin problema';

// ===== CÓMO FUNCIONA =====
$cfTitle      = $cfg['cf_title']       ?? 'Así de simple es recibirlo en casa';
$cfStep1Icon  = $cfg['cf_step1_icon']  ?? '📋';
$cfStep1Title = $cfg['cf_step1_title'] ?? 'Haz tu pedido';
$cfStep1Desc  = $cfg['cf_step1_desc']  ?? 'Llena el formulario en menos de 2 minutos. Sin registro previo ni tarjeta de crédito.';
$cfStep2Icon  = $cfg['cf_step2_icon']  ?? '📦';
$cfStep2Title = $cfg['cf_step2_title'] ?? 'Empacamos y enviamos';
$cfStep2Desc  = $cfg['cf_step2_desc']  ?? 'Al día siguiente hábil despachamos tu pedido, empacado con cuidado hacia tu puerta.';
$cfStep3Icon  = $cfg['cf_step3_icon']  ?? '🏠';
$cfStep3Title = $cfg['cf_step3_title'] ?? 'Lo recibes y pagas';
$cfStep3Desc  = $cfg['cf_step3_desc']  ?? 'El mensajero llega a tu casa. Revisas el producto y pagas solo cuando estás satisfecho.';

// ===== GARANTÍA =====
$showGarantia  = (int)($cfg['show_garantia']  ?? 1);
$garantiaTitle = $cfg['garantia_title'] ?? 'Tu compra está 100% protegida';
$garantiaDesc  = $cfg['garantia_desc']  ?? 'Si el producto llega dañado, diferente a lo descrito o simplemente no te convence, te lo solucionamos. Sin burocracia, sin excusas. Nuestra promesa es tu tranquilidad.';
$garantiaItem1 = $cfg['garantia_item1'] ?? '💳 Pagas solo cuando recibes el producto en tus manos';
$garantiaItem2 = $cfg['garantia_item2'] ?? '🚚 Envío gratis incluido a cualquier ciudad';
$garantiaItem3 = $cfg['garantia_item3'] ?? '🔄 Si llega dañado o incorrecto, lo reponemos';
$garantiaItem4 = $cfg['garantia_item4'] ?? '💬 Asesor en WhatsApp disponible para ti';

// ===== SECCIONES FIJAS (no reordenables) =====
$showTrustStrip  = (int)($cfg['show_trust_strip']  ?? 1);

// ===== FORM HEADER =====
$formTitle    = $cfg['form_title']    ?? 'Haz tu pedido — Pago al recibir';
$formSubtitle = $cfg['form_subtitle'] ?? 'Sin adelantos · El mensajero llega a tu puerta';

// ===== TÍTULOS DE SECCIÓN =====
$galleryTitle     = $cfg['gallery_title']     ?? 'Galería';
$testimoniosTitle = $cfg['testimonios_title'] ?? 'Lo que cuentan nuestros clientes';
$paraQuienTitle   = $cfg['para_quien_title']  ?? '¿Este producto es para ti?';
$faqTitle         = $cfg['faq_title']         ?? 'Preguntas frecuentes';

// Colores con fallback
$primaryColor    = $config['primary_color']    ?? '#3c7a4a';
$secondaryColor  = $config['secondary_color']  ?? '#007bff';
$accentColor     = $config['accent_color']     ?? '#730dad';
$backgroundColor = $config['background_color'] ?? '#f5f5f5';
$textColor       = $config['text_color']       ?? '#222222';

// Tema
$theme = in_array($cfg['theme'] ?? '', [
    'dark-luxury',
    'light-luxury',
    'bold-conversion',
    'minimal-clean',
    'femme-rose',
    'natural-sage',
], true) ? $cfg['theme'] : 'dark-luxury';

// Colores base (5 existentes)
$primaryColor    = $cfg['primary_color']    ?? null;
$secondaryColor  = $cfg['secondary_color']  ?? null;
$accentColor     = $cfg['accent_color']     ?? null;
$backgroundColor = $cfg['background_color'] ?? null;
$textColor       = $cfg['text_color']       ?? null;

// Colores extendidos (6 nuevos)
$colorGold       = $cfg['color_gold']       ?? null;
$colorGoldLight  = $cfg['color_gold_light'] ?? null;
$colorSuccess    = $cfg['color_success']    ?? null;
$colorCountdown  = $cfg['color_countdown']  ?? null;
$colorBgCard     = $cfg['color_bg_card']    ?? null;
$colorBorder     = $cfg['color_border']     ?? null;
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= htmlspecialchars($theme) ?>">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($heroTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/tienda_mvc/public/css/style.css">

    <?php
    // Solo emitir vars que el admin haya configurado explícitamente.
    // Si una var es null, el tema base la maneja — no sobreescribir.
    $customVars = array_filter([
        '--primary-color'    => $primaryColor,
        '--secondary-color'  => $secondaryColor,
        '--accent-color'     => $accentColor,
        '--background-color' => $backgroundColor,
        '--bg-base'          => $backgroundColor, // el tema hardcodea --bg-base, esto lo sobreescribe
        '--text-color'       => $textColor,
        '--cream'            => $textColor,       // igual para el texto base
        '--gold'             => $colorGold,
        '--gold-light'       => $colorGoldLight,
        '--success'          => $colorSuccess,
        '--countdown-color'  => $colorCountdown,
        '--bg-card'          => $colorBgCard,
        '--gold-border'      => $colorBorder,
    ], fn($v) => $v !== null && $v !== '');

    if (!empty($customVars)):
    ?>
        <style>
            [data-theme] {
                <?php foreach ($customVars as $var => $value): ?><?= $var ?>: <?= htmlspecialchars($value) ?>;
                <?php endforeach; ?>
            }
        </style>
    <?php endif; ?>

    <script src="/tienda_mvc/public/js/main.js" defer></script>

    <!-- Facebook Pixel -->
    <script>
        ! function(f, b, e, v, n, t, s) {
            if (f.fbq) return;
            n = f.fbq = function() {
                n.callMethod ?
                    n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }
        (window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1248724310406936');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1248724310406936&ev=PageView&noscript=1">
    </noscript>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof fbq === 'function') {
                fbq('track', 'ViewContent', {
                    content_name: <?= json_encode($producto['nombre'] ?? 'Producto') ?>,
                    content_ids: [<?= json_encode((string)($producto['id'] ?? '')) ?>],
                    content_type: 'product',
                    value: <?= json_encode((float)($producto['precio_venta'] ?? 0)) ?>,
                    currency: 'COP'
                });
            }
        });
    </script>

    <!-- Microsoft Clarity — heatmaps y grabaciones de sesión
         1. Ve a https://clarity.microsoft.com y crea un proyecto
         2. Copia tu Project ID y reemplaza CLARITY_ID_AQUI abajo -->
    <?php $clarityId = 'wm68pleap5'; ?>
    <?php if ($clarityId !== ''): ?>
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window,document,"clarity","script","<?= htmlspecialchars($clarityId) ?>");
    </script>
    <?php endif; ?>
</head>


<body>

    <!-- STICKY PRICE BAR -->
    <div id="stickyPriceBar" class="sticky-price-bar" aria-hidden="true">
        <div class="sticky-price-bar__inner">
            <span class="sticky-price-bar__name"><?= htmlspecialchars(mb_substr($heroTitle, 0, 38)) ?><?= mb_strlen($heroTitle) > 38 ? '…' : '' ?></span>
            <div class="sticky-price-bar__prices">
                <?php if ($precio_regular > $precio_venta): ?>
                <span class="sticky-price-bar__old">$<?= number_format($precio_regular, 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="sticky-price-bar__new">$<?= number_format($precio_venta, 0, ',', '.') ?></span>
            </div>
            <a href="#form-pedido" class="sticky-price-bar__cta">Pedir ahora →</a>
        </div>
    </div>

    <div class="announcement-bar" role="banner" aria-label="Información de oferta">
        <div class="announcement-bar__track" aria-hidden="true">
            <?php foreach ([$announcementItems, $announcementItems] as $set): ?>
                <?php foreach ($set as $item): ?>
                    <span class="announcement-bar__item"><?= htmlspecialchars($item) ?></span>
                    <span class="announcement-bar__item"><span>·</span></span>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- HERO -->
    <!-- HERO -->
    <header class="container hero">
        <div class="hero-text">
            <h1><?= htmlspecialchars($heroTitle) ?></h1>

            <p class="hero-subtitle">
                <?= htmlspecialchars($heroSubtitle) ?>
            </p>

            <!-- ✅ NUEVO: Banda de urgencia -->
            <div class="hero-urgency-bar">
                <span class="urgency-stock">
                    🔴 <strong id="stockCount" data-stock="<?= $urgencyStock ?>"><?= $urgencyStock ?></strong> unidades disponibles
                </span>
                <span class="urgency-sep">·</span>
                <span class="urgency-viewers">
                    👁 <strong id="viewersCount">--</strong> personas viendo esto ahora
                </span>
            </div>

            <div class="price-box">
                <div class="price-label">Oferta de hoy</div>
                <div class="old">
                    Antes: $<?= number_format($precio_regular, 0, ',', '.') ?>
                </div>
                <div class="new">
                    Hoy: $<?= number_format($precio_venta, 0, ',', '.') ?>
                </div>
                <div class="save">
                    Te ahorras: $<?= number_format($ahorro, 0, ',', '.') ?>
                </div>

                <!-- ✅ NUEVO: Countdown persistente por sesión (reemplaza el badge hardcodeado) -->
                <div class="hero-countdown-inline">
                    <span class="countdown-label">⏳ Oferta expira en:</span>
                    <span id="heroCountdown" class="countdown-digits" data-minutes="<?= $countdownMinutes ?>">--:--</span>
                </div>
            </div>

            <a href="#form-pedido" class="btn-primary" id="heroCta">
                <?= htmlspecialchars($heroButtonText) ?>
            </a>

            <div class="hero-trust-row">
                <span><?= htmlspecialchars($heroTrust1) ?></span>
                <span><?= htmlspecialchars($heroTrust2) ?></span>
                <span><?= htmlspecialchars($heroTrust3) ?></span>
            </div>

            <p class="hero-note"><?= htmlspecialchars($heroNote) ?></p>
        </div>

        <div class="hero-media">
            <?php if ($heroMediaType === 'video'): ?>
                <video src="<?= htmlspecialchars($heroMediaPath) ?>"
                    controls
                    style="max-width:100%; border-radius:10px;"></video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($heroMediaPath) ?>"
                    alt="Imagen del producto">
            <?php endif; ?>

            <!-- ✅ NUEVO: Badge flotante sobre la imagen -->
            <div class="hero-image-badge">
                ⭐ <?= $heroBadgeStars ?> · <?= $heroBadgeCustomers ?>
            </div>
        </div>
    </header>

    <?php if ($showTestimonios): ?>
    <div class="social-proof-strip" role="complementary" aria-label="Opiniones de clientes">
        <?php
        $microTest = [
            ['name' => $test1Name, 'text' => $test1Text],
            ['name' => $test2Name, 'text' => $test2Text],
            ['name' => $test3Name, 'text' => $test3Text],
        ];
        foreach ($microTest as $mt):
            if (empty($mt['text'])) continue;
            $quote = mb_strlen($mt['text']) > 85
                ? mb_substr($mt['text'], 0, 85) . '…'
                : $mt['text'];
        ?>
        <div class="sp-item">
            <span class="sp-stars" aria-hidden="true">★★★★★</span>
            <span class="sp-quote">"<?= htmlspecialchars($quote) ?>"</span>
            <span class="sp-name">— <?= htmlspecialchars($mt['name']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php
    // Orden de secciones dinámico
    $defaultSectionOrder = ['benefits','gallery','antes_despues','como_funciona','countdown','porque','para_quien','testimonios','faqs'];
    $savedOrder = array_filter(array_map('trim', explode(',', $cfg['section_order'] ?? '')));
    $validSaved = array_values(array_filter(array_map(function($k) use ($defaultSectionOrder) {
        return in_array($k, $defaultSectionOrder, true) ? $k : null;
    }, $savedOrder)));
    $sectionOrder = array_merge($validSaved, array_diff($defaultSectionOrder, $validSaved));
    $sections = [];
    ?>

    <main>

        <?php ob_start(); if ($showBenefits): ?>
        <!-- BENEFICIOS + IMAGEN -->
        <section class="container benefits-section">
            <div class="two-columns">
                <div class="col">
                    <h2><?= htmlspecialchars($benefitsTitle) ?></h2>

                    <?php if (!empty($benefits)): ?>
                        <?php foreach ($benefits as $b): ?>
                            <div class="benefit-item"> <?= htmlspecialchars($b) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="benefit-item"> Beneficio 1 enfocado en el resultado que quiere el cliente.</div>
                        <div class="benefit-item"> Beneficio 2 que ataque su principal dolor o problema.</div>
                        <div class="benefit-item"> Beneficio 3 que resalte comodidad, rapidez o facilidad.</div>
                        <div class="benefit-item"> Beneficio 4 relacionado con garantía, soporte o confianza.</div>
                    <?php endif; ?>
                </div>
                <div class="col col-media">
                    <?php if ($benefitsMediaType === 'video'): ?>
                        <video
                            src="<?= htmlspecialchars($benefitsMediaPath) ?>"
                            autoplay
                            muted
                            loop
                            playsinline
                            style="width:100%; aspect-ratio:8.5/11; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border); box-shadow:var(--shadow-card);">
                        </video>
                    <?php elseif ($benefitsMediaType === 'gif'): ?>
                        <img
                            src="<?= htmlspecialchars($benefitsMediaPath) ?>"
                            alt="Beneficios del producto"
                            style="width:100%; aspect-ratio:8.5/11; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border); box-shadow:var(--shadow-card);">
                    <?php else: ?>
                        <img
                            src="<?= htmlspecialchars($benefitsMediaPath) ?>"
                            alt="Uso del producto"
                            loading="lazy"
                            decoding="async"
                            style="width:100%; aspect-ratio:8.5/11; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border); box-shadow:var(--shadow-card);">
                    <?php endif; ?>
                </div>
            </div>

            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaBenefitsText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaBenefitsButton) ?>
                </a>
            </div>
        </section>
        <?php endif; $sections['benefits'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showGallery): ?>
        <!-- GALERÍA (principal + miniaturas) -->
        <section class="container">
            <h2 class="section-title"><?= htmlspecialchars($galleryTitle) ?></h2>

            <?php
            // Si no hay imágenes configuradas, usa fallbacks (ahora con 4 elementos)
            $gallery = $galleryPaths;
            if (empty($gallery)) {
                $gallery = [
                    '/tienda_mvc/public/img/producto/uso-1.jpg',
                    '/tienda_mvc/public/img/producto/uso-1.jpg',
                    '/tienda_mvc/public/img/producto/uso-1.jpg',
                    '/tienda_mvc/public/img/producto/uso-1.jpg', // Cuarta imagen de respaldo
                ];
            }

            $mainImg   = $gallery[0] ?? '';
            // Cambiado de 2 a 3 para obtener las 3 miniaturas que acompañan a la principal
            $thumbImgs = array_slice($gallery, 1, 3);
            ?>

            <div class="product-gallery" data-product-gallery>
                <figure class="product-gallery__main">
                    <img
                        class="product-gallery__main-img"
                        src="<?= htmlspecialchars($mainImg) ?>"
                        alt="Foto principal del producto"
                        loading="lazy"
                        decoding="async">
                </figure>

                <?php if (!empty($thumbImgs)): ?>
                    <div class="product-gallery__thumbs" role="list">
                        <?php foreach ($thumbImgs as $i => $src): ?>
                            <?php if (trim($src) === '') continue; ?>
                            <button
                                type="button"
                                class="product-gallery__thumb"
                                role="listitem"
                                aria-label="Ver imagen <?= (int)($i + 2) ?>"
                                data-src="<?= htmlspecialchars($src) ?>">
                                <img
                                    src="<?= htmlspecialchars($src) ?>"
                                    alt="Miniatura <?= (int)($i + 2) ?>"
                                    loading="lazy"
                                    decoding="async">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-cta">
                <p><?= htmlspecialchars($ctaGalleryText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaGalleryButton) ?>
                </a>
            </div>
        </section>
        <?php endif; $sections['gallery'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showAntesDespues): ?>
        <!-- ANTES Y DESPUÉS -->
        <section class="container antes-despues-section">
            <h2 class="section-title"><?= htmlspecialchars($antesDespuesTitle) ?></h2>
            <div class="antes-despues-wrap" id="antesDespuesWrap">
                <div class="antes-despues-container">
                    <!-- Imagen base (ANTES) — ocupa el 100% del frame -->
                    <div class="antes-despues-img antes-img">
                        <img src="<?= htmlspecialchars($antesPath) ?>"
                             alt="<?= htmlspecialchars($antesLabel) ?>"
                             loading="lazy" decoding="async">
                    </div>

                    <!-- Imagen superior (DESPUÉS) — recortada con clip-path por JS -->
                    <div class="antes-despues-img despues-img">
                        <img src="<?= htmlspecialchars($despuesPath) ?>"
                             alt="<?= htmlspecialchars($despuesLabel) ?>"
                             loading="lazy" decoding="async">
                    </div>

                    <!-- Línea + handle -->
                    <div class="antes-despues-divider" id="adDivider" aria-hidden="true">
                        <span class="antes-despues-handle">&#8596;</span>
                    </div>

                    <!-- Etiquetas siempre visibles (hijos directos del contenedor) -->
                    <span class="antes-despues-label antes-despues-label--despues">
                        <?= htmlspecialchars($despuesLabel) ?>
                    </span>
                    <span class="antes-despues-label antes-despues-label--antes">
                        <?= htmlspecialchars($antesLabel) ?>
                    </span>

                    <!-- Input range invisible — captura mouse y touch -->
                    <input type="range" class="antes-despues-slider" id="adSlider"
                           min="0" max="100" value="50" aria-label="Comparar antes y después">
                </div>
                <p class="antes-despues-hint">← Desliza para comparar →</p>
            </div>
        </section>
        <?php endif; $sections['antes_despues'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showComoFunciona): ?>
        <!-- CÓMO FUNCIONA -->
        <section class="container como-funciona-section">
            <h2 class="section-title"><?= htmlspecialchars($cfTitle) ?></h2>
            <div class="steps-grid">
                <div class="step-card animate-fadeup">
                    <div class="step-num" aria-hidden="true">01</div>
                    <div class="step-icon-wrap"><?= htmlspecialchars($cfStep1Icon) ?></div>
                    <h3><?= htmlspecialchars($cfStep1Title) ?></h3>
                    <p><?= htmlspecialchars($cfStep1Desc) ?></p>
                </div>
                <div class="step-arrow-sep" aria-hidden="true">→</div>
                <div class="step-card animate-fadeup">
                    <div class="step-num" aria-hidden="true">02</div>
                    <div class="step-icon-wrap"><?= htmlspecialchars($cfStep2Icon) ?></div>
                    <h3><?= htmlspecialchars($cfStep2Title) ?></h3>
                    <p><?= htmlspecialchars($cfStep2Desc) ?></p>
                </div>
                <div class="step-arrow-sep" aria-hidden="true">→</div>
                <div class="step-card animate-fadeup">
                    <div class="step-num" aria-hidden="true">03</div>
                    <div class="step-icon-wrap"><?= htmlspecialchars($cfStep3Icon) ?></div>
                    <h3><?= htmlspecialchars($cfStep3Title) ?></h3>
                    <p><?= htmlspecialchars($cfStep3Desc) ?></p>
                </div>
            </div>
        </section>
        <?php endif; $sections['como_funciona'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showCountdown): ?>
        <!-- CONTADOR PROMOCIÓN -->
        <section class="container">
            <div class="countdown">
                <h2><?= htmlspecialchars($countdownTitle) ?></h2>
                <span id="countdown-timer" data-minutes="<?= $countdownMinutes ?>">--:--</span>
                <p><?= htmlspecialchars($countdownText) ?></p>
            </div>
        </section>
        <?php endif; $sections['countdown'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showPorque): ?>
        <!-- POR QUÉ TE ENCANTARÁ -->
        <section class="container">
            <h2 class="section-title"><?= htmlspecialchars($porqueTitle) ?></h2>
            <div class="two-columns">
                <div class="col">
                    <p>
                        <?= nl2br(htmlspecialchars($porqueText)) ?>
                    </p>
                    <ul class="why-list">
                        <?php if (!empty($porqueBullets)): ?>
                            <?php foreach ($porqueBullets as $pb): ?>
                                <li><?= htmlspecialchars($pb) ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>Punto fuerte 1: resultado directo o transformación.</li>
                            <li>Punto fuerte 2: algo que lo hace más fácil o rápido.</li>
                            <li>Punto fuerte 3: respaldo, garantía o confianza.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col col-media">
                    <?php if ($porqueMediaType === 'video'): ?>
                        <video
                            src="<?= htmlspecialchars($porqueMediaPath) ?>"
                            autoplay
                            muted
                            loop
                            playsinline
                            style="width:100%; aspect-ratio:8.5/11; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border); box-shadow:var(--shadow-card);">
                        </video>
                    <?php elseif ($porqueMediaType === 'gif'): ?>
                        <img
                            src="<?= htmlspecialchars($porqueMediaPath) ?>"
                            alt="Por qué te encantará"
                            style="width:100%; aspect-ratio:8.5/11; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border); box-shadow:var(--shadow-card);">
                    <?php else: ?>
                        <img
                            src="<?= htmlspecialchars($porqueMediaPath) ?>"
                            alt="Cliente feliz"
                            loading="lazy"
                            decoding="async"
                            style="width:100%; aspect-ratio:8.5/11; object-fit:cover; border-radius:var(--radius-md); border:1px solid var(--gold-border); box-shadow:var(--shadow-card);">
                    <?php endif; ?>
                </div>
            </div>

            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaPorqueText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaPorqueButton) ?>
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- TABLA COMPARATIVA -->
        <?php if ($showComparison): ?>
        <section class="container comparison-section">
            <h2 class="section-title"><?= htmlspecialchars($comparisonTitle) ?></h2>
            <div class="comparison-table">
                <div class="comparison-col comparison-col--without">
                    <div class="comparison-header comparison-header--without">
                        <span aria-hidden="true">❌</span>
                        <strong>Sin el producto</strong>
                    </div>
                    <ul class="comparison-list">
                        <?php foreach ($comparisonRows as $row): ?>
                            <?php if ($row['without'] !== ''): ?>
                            <li><?= htmlspecialchars($row['without']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="comparison-col comparison-col--with">
                    <div class="comparison-header comparison-header--with">
                        <span aria-hidden="true">✅</span>
                        <strong>Con <?= htmlspecialchars($heroTitle) ?></strong>
                    </div>
                    <ul class="comparison-list">
                        <?php foreach ($comparisonRows as $row): ?>
                            <?php if ($row['with'] !== ''): ?>
                            <li><?= htmlspecialchars($row['with']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="comparison-cta">
                <a href="#form-pedido" class="btn-primary">Quiero experimentar la diferencia →</a>
            </div>
        </section>
        <?php endif; $sections['porque'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showParaQuien): ?>
        <!-- PARA QUIÉN ES -->
        <section class="container para-quien-section">
            <h2 class="section-title"><?= htmlspecialchars($paraQuienTitle) ?></h2>
            <div class="para-quien-grid">
                <div class="para-quien-card para-quien-card--yes">
                    <div class="para-quien-card__header">
                        <span class="para-quien-card__icon" aria-hidden="true">✅</span>
                        <h3>Es para ti si…</h3>
                    </div>
                    <ul class="para-quien-list">
                        <?php foreach ($paraQuienSiItems as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="#form-pedido" class="btn-primary para-quien-cta">Sí, es para mí →</a>
                </div>
                <div class="para-quien-card para-quien-card--no">
                    <div class="para-quien-card__header">
                        <span class="para-quien-card__icon" aria-hidden="true">❌</span>
                        <h3>No es para ti si…</h3>
                    </div>
                    <ul class="para-quien-list">
                        <?php foreach ($paraQuienNoItems as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
        <?php endif; $sections['para_quien'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showTestimonios): ?>
        <!-- TESTIMONIOS -->
        <section class="container">
            <h2 class="section-title"><?= htmlspecialchars($testimoniosTitle) ?></h2>
            <div class="testimonials">
                <article class="testimonial">
                    <div class="testimonial-photo">
                        <img src="<?= htmlspecialchars($test1Photo) ?>" alt="Cliente 1">
                    </div>
                    <div class="testimonial-content">
                        <div class="testimonial-stars" aria-label="5 estrellas">★★★★★</div>
                        <h3><?= htmlspecialchars($test1Name) ?></h3>
                        <p>"<?= htmlspecialchars($test1Text) ?>"</p>
                        <span class="testimonial-verified">✅ Compra verificada</span>
                    </div>
                </article>

                <article class="testimonial">
                    <div class="testimonial-photo">
                        <img src="<?= htmlspecialchars($test2Photo) ?>" alt="Cliente 2">
                    </div>
                    <div class="testimonial-content">
                        <div class="testimonial-stars" aria-label="5 estrellas">★★★★★</div>
                        <h3><?= htmlspecialchars($test2Name) ?></h3>
                        <p>"<?= htmlspecialchars($test2Text) ?>"</p>
                        <span class="testimonial-verified">✅ Compra verificada</span>
                    </div>
                </article>

                <article class="testimonial">
                    <div class="testimonial-photo">
                        <img src="<?= htmlspecialchars($test3Photo) ?>" alt="Cliente 3">
                    </div>
                    <div class="testimonial-content">
                        <div class="testimonial-stars" aria-label="5 estrellas">★★★★★</div>
                        <h3><?= htmlspecialchars($test3Name) ?></h3>
                        <p>"<?= htmlspecialchars($test3Text) ?>"</p>
                        <span class="testimonial-verified">✅ Compra verificada</span>
                    </div>
                </article>
            </div>

            <!-- Galería de clientes satisfechos (por ahora estática) -->
            <?php if ($waEnabled === 1): ?>
                <section class="testimonials-section">
                    <div class="container">
                        <div class="section-header">
                            <h2 class="section-title"><?= htmlspecialchars($waTitle) ?></h2>
                            <p class="subtitle"><?= htmlspecialchars($waSubtitle) ?></p>
                        </div>

                        <div class="testimonials-slider-outer">

                            <button class="slider-arrow slider-arrow--prev" aria-label="Anterior">&#8249;</button>
                            <button class="slider-arrow slider-arrow--next" aria-label="Siguiente">&#8250;</button>

                            <div class="testimonials-slider-container">
                                <div class="slider-track" id="sliderTrack">

                                    <?php
                                    // Para mantener el JS estable: siempre 5 items.
                                    // Si alguno viene vacío, ponemos fallbacks mínimos.
                                    $defaults = [
                                        1 => ['name' => 'María González',   'time' => '• Hace 24 horas', 'text' => '¡Llegó antes de lo esperado! La calidad superó mis expectativas completamente.'],
                                        2 => ['name' => 'Carlos Rodríguez', 'time' => '• Hace 3 días',    'text' => 'Ya le recomendé a 3 amigos. El servicio post-venta es excelente.'],
                                        3 => ['name' => 'Ana Martínez',     'time' => '• Hace 1 semana',  'text' => 'Segunda compra y vuelvo a quedar encantada. Definitivamente mi tienda de confianza.'],
                                        4 => ['name' => 'Pedro López',      'time' => '• Hace 2 días',    'text' => 'Envío express en 24h. ¡Increíble! Justo lo que necesitaba con urgencia.'],
                                        5 => ['name' => 'Laura Sánchez',    'time' => '• Hace 4 días',    'text' => 'Viralicé en mis stories. Todos preguntan dónde compré. ¡Éxito total!'],
                                    ];

                                    // Normaliza los 5 items con defaults si faltan campos
                                    $items = [];
                                    for ($i = 1; $i <= 5; $i++) {
                                        $it = $waItems[$i - 1] ?? [];
                                        $items[$i] = [
                                            'name'  => trim($it['name']  ?? '') !== '' ? $it['name']  : $defaults[$i]['name'],
                                            'time'  => trim($it['time']  ?? '') !== '' ? $it['time']  : $defaults[$i]['time'],
                                            'text'  => trim($it['text']  ?? '') !== '' ? $it['text']  : $defaults[$i]['text'],
                                            'image' => trim($it['image'] ?? ''),
                                        ];
                                    }

                                    $renderSlide = function ($idx, $data) {
                                        $name  = $data['name'] ?? '';
                                        $time  = $data['time'] ?? '';
                                        $text  = $data['text'] ?? '';
                                        $image = $data['image'] ?? '';
                                    ?>
                                        <div class="testimonial-slide" data-index="<?= $idx - 1 ?>">
                                            <div class="whatsapp-card">
                                                <div class="img-wrapper">
                                                    <?php if (!empty($image)): ?>
                                                        <img src="<?= htmlspecialchars($image) ?>" alt="Testimonio WhatsApp <?= htmlspecialchars($name) ?>" class="whatsapp-screenshot">
                                                    <?php else: ?>
                                                        <img src="/tienda_mvc/public/img/testimonios/1.jpeg" alt="Testimonio WhatsApp" class="whatsapp-screenshot">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-content">
                                                    <div class="card-stars" aria-label="5 estrellas">★★★★★</div>
                                                    <strong><?= htmlspecialchars($name) ?></strong>
                                                    <span class="card-meta">
                                                        <?= htmlspecialchars($time) ?>
                                                        <span class="card-check">&#10003; verificado</span>
                                                    </span>
                                                    <p>"<?= htmlspecialchars($text) ?>"</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                    };

                                    for ($i = 1; $i <= 5; $i++) {
                                        $renderSlide($i, $items[$i]);
                                    }

                                    ?>

                                </div>
                            </div>
                        </div>

                        <div class="slider-pagination">
                            <span class="dot active" data-dot="0"></span>
                            <span class="dot" data-dot="1"></span>
                            <span class="dot" data-dot="2"></span>
                            <span class="dot" data-dot="3"></span>
                            <span class="dot" data-dot="4"></span>
                        </div>

                        <div class="slider-footer-note">
                            <p><?= htmlspecialchars($waFooterNote) ?></p>
                        </div>
                    </div>
                </section>
            <?php endif; ?>




            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaTestimonialsText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaTestimonialsButton) ?>
                </a>
            </div>
        </section>
        <?php endif; $sections['testimonios'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showFaqs): ?>
        <!-- PREGUNTAS FRECUENTES -->
        <section class="container">
            <h2 class="section-title"><?= htmlspecialchars($faqTitle) ?></h2>
            <div class="accordion">
                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <?= htmlspecialchars($faq1_q) ?>
                    </button>
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($faq1_a)) ?></p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <?= htmlspecialchars($faq2_q) ?>
                    </button>
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($faq2_a)) ?></p>
                    </div>
                </div>

                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <?= htmlspecialchars($faq3_q) ?>
                    </button>
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($faq3_a)) ?></p>
                    </div>
                </div>

                <?php if (!empty($faq4_q)): ?>
                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <?= htmlspecialchars($faq4_q) ?>
                    </button>
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($faq4_a)) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($faq5_q)): ?>
                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <?= htmlspecialchars($faq5_q) ?>
                    </button>
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($faq5_a)) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($faq6_q)): ?>
                <div class="accordion-item">
                    <button type="button" class="accordion-header">
                        <?= htmlspecialchars($faq6_q) ?>
                    </button>
                    <div class="accordion-body">
                        <p><?= nl2br(htmlspecialchars($faq6_a)) ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaFaqText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaFaqButton) ?>
                </a>
            </div>
        </section>
        <?php endif; $sections['faqs'] = ob_get_clean(); ?>

        <?php foreach ($sectionOrder as $_sec) { echo $sections[$_sec] ?? ''; } ?>

        <!-- GARANTÍA -->
        <?php if ($showGarantia): ?>
        <div class="garantia-banner">
            <div class="container garantia-container">
                <div class="garantia-inner">
                    <div class="garantia-seal" aria-hidden="true">🛡</div>
                    <div class="garantia-body">
                        <h3><?= htmlspecialchars($garantiaTitle) ?></h3>
                        <p><?= nl2br(htmlspecialchars($garantiaDesc)) ?></p>
                        <ul class="garantia-list">
                            <?php foreach ([$garantiaItem1, $garantiaItem2, $garantiaItem3, $garantiaItem4] as $gItem): ?>
                                <?php if (!empty($gItem)): ?><li><?= htmlspecialchars($gItem) ?></li><?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- AUTORIDAD / CREDIBILIDAD -->
        <?php if ($authorityEnabled): ?>
        <section class="authority-section">
            <div class="container">
                <h2 class="section-title"><?= htmlspecialchars($authorityTitle) ?></h2>
                <div class="authority-grid">
                    <div class="authority-stat">
                        <div class="authority-stat__num"><?= htmlspecialchars($authorityYears) ?>+</div>
                        <div class="authority-stat__label">años en el mercado</div>
                    </div>
                    <div class="authority-stat">
                        <div class="authority-stat__num"><?= htmlspecialchars($authorityDeliveries) ?></div>
                        <div class="authority-stat__label">pedidos entregados</div>
                    </div>
                    <div class="authority-stat">
                        <div class="authority-stat__num">⭐ <?= htmlspecialchars($authorityRating) ?></div>
                        <div class="authority-stat__label">calificación promedio</div>
                    </div>
                    <div class="authority-stat">
                        <div class="authority-stat__num">🛡️</div>
                        <div class="authority-stat__label"><?= htmlspecialchars($authorityGuarantee) ?></div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- TRANSPORTADORAS -->
        <?php if ($showTrustStrip): ?>
        <div class="trust-strip" role="complementary" aria-label="Transportadoras">
            <p class="trust-strip__carriers-label">Enviamos a toda Colombia con:</p>
            <div class="trust-strip__carriers">
                <img src="/tienda_mvc/public/img/transportadoras/interrapidisimo.png"
                     alt="Interrapidísimo" class="carrier-logo" loading="lazy">
                <img src="/tienda_mvc/public/img/transportadoras/envia.png"
                     alt="Envía" class="carrier-logo" loading="lazy">
                <img src="/tienda_mvc/public/img/transportadoras/coordinadora.png"
                     alt="Coordinadora" class="carrier-logo" loading="lazy">
            </div>
        </div>
        <?php endif; ?>

        <!-- RESUMEN DE OFERTA — Ancla pre-formulario -->
        <div class="offer-anchor">
            <div class="offer-anchor__product"><?= htmlspecialchars($heroTitle) ?></div>
            <div class="offer-anchor__pricing">
                <?php if ($precio_regular > $precio_venta): ?>
                <span class="offer-anchor__old">Antes $<?= number_format($precio_regular, 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="offer-anchor__new">$<?= number_format($precio_venta, 0, ',', '.') ?></span>
                <?php if ($ahorro > 0): ?>
                <span class="offer-anchor__save">Ahorras $<?= number_format($ahorro, 0, ',', '.') ?></span>
                <?php endif; ?>
            </div>
            <div class="offer-anchor__perks">
                <span>✅ Envío gratis</span>
                <span>💳 Pago al recibir</span>
                <span>🔄 Garantía de cambio</span>
            </div>
            <?php if ($urgencyStock <= 10): ?>
            <p class="offer-anchor__scarcity">
                ⚠️ Solo quedan <strong id="offerAnchorStock"><?= $urgencyStock ?></strong> unidades a este precio
            </p>
            <?php endif; ?>
        </div>

        <!-- FORMULARIO STEPPER -->
        <section class="container" id="form-pedido">
            <div class="form-section__header">
                <h2 class="form-section__title"><?= htmlspecialchars($formTitle) ?></h2>
                <p class="form-section__sub"><?= htmlspecialchars($formSubtitle) ?></p>
                <div class="form-social-proof">
                    <span class="form-social-proof__dot"></span>
                    <span id="formOrderCount" data-base="<?= $pedidosRecientes ?>">
                        <?= $pedidosRecientes ?>
                    </span>
                    pedidos en los últimos 30 días
                </div>
            </div>

            <div class="form-urgency-strip">
                <span aria-hidden="true">⏳</span>
                Tu precio especial vence en: <strong id="formCountdown">--:--</strong>
            </div>

            <?php
            $colores         = $colores ?? [];
            $precioVenta     = (float)($producto['precio_venta'] ?? 72000);
            $precioProveedor = (float)($producto['precio_proveedor'] ?? 0);
            $precioRegular   = (float)($producto['precio_regular'] ?? $precioVenta);
            if ($precioRegular <= 0 || $precioRegular < $precioVenta) $precioRegular = $precioVenta;
            $d2  = (int)($producto['descuento_2da'] ?? 15);
            $d3  = (int)($producto['descuento_3ra'] ?? 20);
            $act = (int)($producto['descuento_multicantidad_activo'] ?? 1);
            $precioCombo2 = (int)($comboPrice2 ?? 115000);
            if ($precioCombo2 <= 0) $precioCombo2 = 115000;
            $hasColors  = !empty($colores);
            $totalSteps = $hasColors ? 3 : 2;
            $stepLabels = $hasColors
                ? ['Tus datos', 'Tu pedido', 'Entrega']
                : ['Tus datos', 'Entrega'];
            ?>

            <!-- Indicador de progreso -->
            <div class="form-stepper-indicator" id="stepperIndicator">
                <?php for ($s = 1; $s <= $totalSteps; $s++): ?>
                <div class="stepper-node <?= $s === 1 ? 'is-active' : '' ?>" data-step="<?= $s ?>">
                    <div class="stepper-node__circle">
                        <span class="stepper-node__num"><?= $s ?></span>
                        <svg class="stepper-node__check" viewBox="0 0 14 14" fill="none">
                            <polyline points="2 7 6 11 12 3" stroke="currentColor" stroke-width="2.2"
                                stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="stepper-node__label"><?= $stepLabels[$s - 1] ?></span>
                </div>
                <?php if ($s < $totalSteps): ?>
                <div class="stepper-connector" data-after="<?= $s ?>"></div>
                <?php endif; ?>
                <?php endfor; ?>
            </div>

            <div id="stepperErrors" class="error" style="display:none;"></div>

            <div class="form-box">
                <form id="formPedido" action="/tienda_mvc/Landing/enviarPedido" method="POST" novalidate>
                    <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['id'] ?? 1) ?>">

                    <!-- ══ PASO 1: DATOS PERSONALES ══ -->
                    <div class="form-step is-active" data-step="1">
                        <div class="form-step__head">
                            <p class="form-step__badge">Paso 1 de <?= $totalSteps ?></p>
                            <h3 class="form-step__title">¿A quién le enviamos?</h3>
                            <p class="form-step__sub">Solo 3 datos y ya podemos empezar 👋</p>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="nombre">Nombre *</label>
                                <input type="text" id="nombre" name="nombre" required
                                    placeholder="Ej: María"
                                    value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="apellidos">Apellidos *</label>
                                <input type="text" id="apellidos" name="apellidos" required
                                    placeholder="Ej: González"
                                    value="<?= htmlspecialchars($old['apellidos'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="telefono">📱 WhatsApp *</label>
                            <input type="tel" id="telefono" name="telefono" required
                                placeholder="3001234567" maxlength="10"
                                value="<?= htmlspecialchars($old['telefono'] ?? '') ?>">
                            <small class="help">10 dígitos · empieza en 3 · confirmamos tu pedido aquí</small>
                        </div>

                        <?php if (!$hasColors): ?>
                        <!-- Selector de cantidad visible cuando no hay colores -->
                        <div class="form-group form-qty-group">
                            <label>¿Cuántas unidades quieres?</label>
                            <div class="qty-stepper">
                                <button type="button" class="qty-btn" id="qtyMinus" aria-label="Restar">−</button>
                                <span class="qty-display" id="qtyDisplay">1</span>
                                <button type="button" class="qty-btn" id="qtyPlus" aria-label="Sumar">+</button>
                            </div>
                            <small class="help">Una unidad es suficiente — siempre puedes pedir más después</small>
                            <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">
                            <input type="hidden" id="cantidad_total" name="cantidad_total"
                                value="<?= htmlspecialchars((string)($old['cantidad_total'] ?? 1)) ?>">
                        </div>
                        <?php endif; ?>

                        <div class="form-step__nav form-step__nav--end">
                            <button type="button" class="btn-step-next" data-next="2">
                                Continuar <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </div>

                    <?php if ($hasColors): ?>
                    <!-- ══ PASO 2: PRODUCTO ══ -->
                    <div class="form-step" data-step="2">
                        <div class="form-step__head">
                            <p class="form-step__badge">Paso 2 de 3 — ¡Ya casi tienes tu pedido! 🛍️</p>
                            <h3 class="form-step__title">¿Qué quieres pedir?</h3>
                            <p class="form-step__sub">Elige las cantidades y estamos listos</p>
                        </div>

                        <?php if (!empty($comboEnabled) && (int)$comboEnabled === 1): ?>
                            <div class="form-group">
                                <label for="pricingModeSelect"><strong>Elige tu oferta</strong></label>
                                <select id="pricingModeSelect" class="color-select">
                                    <option value="combo" selected>🔥 Combo x2 — $<?= number_format($precioCombo2, 0, ',', '.') ?> (Recomendado)</option>
                                    <option value="individual">1 unidad — $<?= number_format($precioVenta, 0, ',', '.') ?></option>
                                </select>
                                <input type="hidden" name="pricing_mode" id="pricingMode" value="combo">
                            </div>
                            <div id="comboWrap" class="form-group">
                                <label><strong>Selecciona los colores de tu combo</strong></label>
                                <div id="combosContainer" class="colors-qty-wrap"></div>
                                <button type="button" id="addComboBtn" class="btn-ghost add-color-btn">+ Agregar otro combo</button>
                                <small class="help total-units-note">Total unidades: <strong id="totalUnits">2</strong></small>
                            </div>
                            <div id="individualWrap" class="form-group" style="display:none;">
                                <label><strong>Compra individual</strong></label>
                                <div class="color-qty-row">
                                    <select id="indColor" class="color-select">
                                        <option value="">Selecciona un color</option>
                                        <?php foreach ($colores as $c): ?>
                                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="indQty" class="qty-select">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>"><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div id="generatedItems"></div>

                        <?php else: ?>
                            <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">
                            <?php
                            $oldColorItems = $old['color_item'] ?? [];
                            $oldQtyItems   = $old['qty_item'] ?? [];
                            if (!is_array($oldColorItems)) $oldColorItems = [];
                            if (!is_array($oldQtyItems))   $oldQtyItems = [];
                            $rowsCount = max(1, count($oldColorItems), count($oldQtyItems));
                            ?>
                            <div class="form-group">
                                <div class="color-qty-header">
                                    <span>Color</span>
                                    <span>¿Cuántos?</span>
                                    <span></span>
                                </div>
                                <div id="colorsQtyWrap" class="colors-qty-wrap">
                                    <?php for ($r = 0; $r < $rowsCount; $r++): ?>
                                        <?php
                                        $selColor = $oldColorItems[$r] ?? '';
                                        $selQty   = (int)($oldQtyItems[$r] ?? 1);
                                        if ($selQty < 1) $selQty = 1;
                                        ?>
                                        <div class="color-qty-row">
                                            <select name="color_item[]" required class="color-select">
                                                <option value="">Elige un color</option>
                                                <?php foreach ($colores as $c): ?>
                                                    <option value="<?= htmlspecialchars($c) ?>" <?= ($selColor === $c) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($c) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="qty-stepper-inline">
                                                <button type="button" class="qty-btn-inline" data-action="minus" aria-label="Restar">−</button>
                                                <span class="qty-val"><?= $selQty ?></span>
                                                <button type="button" class="qty-btn-inline" data-action="plus" aria-label="Sumar">+</button>
                                                <select name="qty_item[]" required class="qty-select-hidden" aria-hidden="true">
                                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                                        <option value="<?= $i ?>" <?= ($selQty === $i) ? 'selected' : '' ?>><?= $i ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="remove-color-qty" aria-label="Quitar">
                                                <span class="remove-icon">×</span>
                                                <span class="remove-text">Borrar</span>
                                            </button>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <button type="button" id="addColorQtyBtn" class="btn-ghost add-color-btn">+ Agregar otro color</button>
                                <small class="help total-units-note">Total unidades: <strong id="totalUnits">1</strong></small>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" id="cantidad_total" name="cantidad_total"
                            value="<?= htmlspecialchars((string)($old['cantidad_total'] ?? 1)) ?>">

                        <div class="form-step__nav">
                            <button type="button" class="btn-step-prev" data-prev="1">← Atrás</button>
                            <button type="button" class="btn-step-next" data-next="3">Continuar <span aria-hidden="true">→</span></button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ══ PASO ENTREGA (último) ══ -->
                    <?php $entregaStep = $hasColors ? 3 : 2; ?>
                    <div class="form-step" data-step="<?= $entregaStep ?>">
                        <div class="form-step__head">
                            <p class="form-step__badge">Último paso ✨ — ya vamos a terminar</p>
                            <h3 class="form-step__title">¿A dónde te lo enviamos?</h3>
                            <p class="form-step__sub">Un dato más y tu pedido está hecho</p>
                        </div>

                        <!-- Resumen de compra -->
                        <div class="order-summary" id="orderSummary"
                            data-price-unit="<?= htmlspecialchars((string)$precioVenta) ?>"
                            data-price-combo2="<?= htmlspecialchars((string)$precioCombo2) ?>"
                            data-price-regular="<?= htmlspecialchars((string)$precioRegular) ?>"
                            data-price-supplier="<?= htmlspecialchars((string)$precioProveedor) ?>"
                            data-d2="<?= htmlspecialchars((string)$d2) ?>"
                            data-d3="<?= htmlspecialchars((string)$d3) ?>"
                            data-act="<?= htmlspecialchars((string)$act) ?>">
                            <h3 class="order-summary__title">Resumen de tu compra</h3>
                            <div class="order-summary__rows">
                                <div class="order-summary__row">
                                    <span>Subtotal (<strong id="summaryQty">1</strong> <span id="summaryQtyWord">unidad</span>)</span>
                                    <strong id="summarySubtotal">$0</strong>
                                </div>
                                <div class="order-summary__row">
                                    <span>Descuento por cantidad</span>
                                    <strong id="summaryDiscount">$0</strong>
                                </div>
                                <div class="order-summary__row">
                                    <span>Envío</span>
                                    <strong class="summary-free">GRATIS</strong>
                                </div>
                                <div class="order-summary__row">
                                    <span>Ahorro total</span>
                                    <strong class="summary-save" id="summarySave">$0</strong>
                                </div>
                                <div class="order-summary__row order-summary__row--total">
                                    <span>Total a pagar al recibir</span>
                                    <strong id="summaryTotal">$0</strong>
                                </div>
                            </div>
                        </div>

                        <div class="form-row-2">
                            <div class="form-group">
                                <label for="departamento">Departamento *</label>
                                <select id="departamento" name="departamento" required
                                    data-old="<?= htmlspecialchars($old['departamento'] ?? '') ?>">
                                    <option value="">Selecciona...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="municipio">Municipio *</label>
                                <select id="municipio" name="municipio" required
                                    data-old="<?= htmlspecialchars($old['municipio'] ?? '') ?>">
                                    <option value="">Primero el depto.</option>
                                </select>
                            </div>
                        </div>

                        <div id="deliveryETA" class="delivery-eta-badge" style="display:none;" aria-live="polite">
                            📦 Estimado de llegada: <strong id="deliveryETADate"></strong>
                        </div>

                        <div class="form-group">
                            <span>¿Cómo quieres recibirlo? *</span>
                            <div class="radio-group--cards">
                                <label class="radio-card">
                                    <input type="radio" name="tipo_entrega" value="domicilio"
                                        <?= (isset($old['tipo_entrega']) && $old['tipo_entrega'] === 'domicilio') ? 'checked' : '' ?>>
                                    <span class="radio-card__icon">🏠</span>
                                    <span class="radio-card__text">A domicilio</span>
                                </label>
                                <label class="radio-card">
                                    <input type="radio" name="tipo_entrega" value="oficina"
                                        <?= (isset($old['tipo_entrega']) && $old['tipo_entrega'] === 'oficina') ? 'checked' : '' ?>>
                                    <span class="radio-card__icon">🏪</span>
                                    <span class="radio-card__text">Oficina Interrapidísimo</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" id="grupo-direccion" style="display:none;">
                            <label for="direccion">Dirección completa *</label>
                            <input type="text" id="direccion" name="direccion"
                                placeholder="Ej: Calle 45 # 23-10, Apto 301"
                                value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
                        </div>

                        <div class="form-step__nav">
                            <button type="button" class="btn-step-prev" data-prev="<?= $entregaStep - 1 ?>">← Atrás</button>
                            <button type="submit" id="btnSubmit" class="btn-submit-hero btn-submit-hero--step">
                                <span id="btnSubmitText">🏠 Quiero el mío — Pago $<?= number_format($precio_venta, 0, ',', '.') ?> al recibirlo</span>
                                <span id="btnSubmitSpinner" style="display:none;">Enviando...</span>
                            </button>
                        </div>

                        <div class="form-trust-row">
                            <span>🔒 Datos seguros</span>
                            <span>💳 Pagas al recibir</span>
                            <span>🚚 Envío gratis</span>
                            <span>🔄 Cambios sin problema</span>
                        </div>
                        <p class="form-note">Un asesor te confirmará el pedido por WhatsApp.</p>

                        <div class="form-wa-fallback">
                            ¿Prefieres pedirlo por WhatsApp?
                            <a href="https://wa.me/<?= urlencode($waPhone) ?>?text=<?= urlencode('Hola, quiero hacer un pedido de ' . ($producto['nombre'] ?? 'su producto')) ?>"
                               target="_blank" rel="noopener">
                                Escribir directamente →
                            </a>
                        </div>
                    </div>

                </form>

                <!-- PANTALLA DE ÉXITO -->
                <div id="stepperSuccess" style="display:none;">
                    <div class="order-success">
                        <div class="order-success__icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                        <p class="order-success__label">Pedido registrado</p>
                        <h2 class="order-success__title">¡Tu pedido está en camino!</h2>
                        <p class="order-success__subtitle">
                            Un asesor te contactará pronto por WhatsApp para confirmar los detalles.
                        </p>
                        <div class="order-success__steps">
                            <p class="order-success__steps-label">¿Qué sigue?</p>
                            <div class="order-success__step">
                                <span class="order-success__step-num">1</span>
                                <div><strong>Confirmación por WhatsApp</strong>
                                    <p>Te escribimos al número que registraste para confirmar tu pedido.</p>
                                </div>
                            </div>
                            <div class="order-success__step">
                                <span class="order-success__step-num">2</span>
                                <div><strong>Preparación y despacho</strong>
                                    <p>Una vez confirmado, preparamos y despachamos tu pedido.</p>
                                </div>
                            </div>
                            <div class="order-success__step">
                                <span class="order-success__step-num">3</span>
                                <div><strong>Entrega y pago</strong>
                                    <p>Recibes tu pedido en casa y pagas al mensajero. Sin adelantos.</p>
                                </div>
                            </div>
                        </div>
                        <a href="https://wa.me/<?= urlencode($waPhone) ?>?text=Hola%2C%20acabo%20de%20hacer%20un%20pedido%20y%20quiero%20confirmar%20los%20detalles."
                            class="order-success__wa-btn" target="_blank" rel="noopener">
                            Escribir por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <script>
        /* ── Stepper de formulario ─────────────────────────────────── */
        (function () {
            const form      = document.getElementById('formPedido');
            if (!form) return;

            const TOTAL     = <?= $totalSteps ?>;
            let   current   = 1;

            const errBox    = document.getElementById('stepperErrors');
            const indicator = document.getElementById('stepperIndicator');

            /* Mostrar / ocultar errores inline */
            function showErrors(errs) {
                if (!errBox) return;
                if (!errs.length) { errBox.style.display = 'none'; errBox.innerHTML = ''; return; }
                errBox.innerHTML = '<ul>' + errs.map(e => '<li>' + e + '</li>').join('') + '</ul>';
                errBox.style.display = 'block';
                errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            /* Validación por paso */
            function validateStep(n) {
                const errs = [];
                if (n === 1) {
                    const nombre    = form.querySelector('#nombre');
                    const apellidos = form.querySelector('#apellidos');
                    const tel       = form.querySelector('#telefono');
                    if (!nombre?.value.trim())    errs.push('El nombre es obligatorio.');
                    if (!apellidos?.value.trim()) errs.push('Los apellidos son obligatorios.');
                    const tv = tel?.value.trim() ?? '';
                    if (!tv)                       errs.push('El número de WhatsApp es obligatorio.');
                    else if (!/^3\d{9}$/.test(tv)) errs.push('Número inválido — debe tener 10 dígitos y empezar en 3.');
                } else if (n === 2 && TOTAL === 3) {
                    const mode = form.querySelector('#pricingMode')?.value ?? 'individual';
                    if (mode === 'combo') {
                        const blocks = form.querySelectorAll('.combo-block');
                        if (!blocks.length) errs.push('Debes tener al menos 1 combo.');
                        else blocks.forEach((b, i) => {
                            if (b.querySelector('select.combo-color:not([value])')?.value === '')
                                errs.push('Selecciona el color del combo ' + (i + 1) + '.');
                        });
                    } else {
                        let alguno = false;
                        form.querySelectorAll('select[name="color_item[]"]').forEach(s => { if (s.value) alguno = true; });
                        if (!alguno) errs.push('Selecciona al menos un color.');
                    }
                }
                /* El último paso se valida completamente en el submit de main.js */
                return errs;
            }

            /* Actualizar indicador visual */
            function updateIndicator(n) {
                if (!indicator) return;
                indicator.querySelectorAll('.stepper-node').forEach(el => {
                    const s = parseInt(el.dataset.step, 10);
                    el.classList.toggle('is-active', s === n);
                    el.classList.toggle('is-done',   s < n);
                });
                indicator.querySelectorAll('.stepper-connector').forEach(el => {
                    el.classList.toggle('is-done', parseInt(el.dataset.after, 10) < n);
                });
            }

            /* Ir a un paso */
            function goTo(n) {
                form.querySelectorAll('.form-step').forEach(el => {
                    el.classList.toggle('is-active', parseInt(el.dataset.step, 10) === n);
                });
                updateIndicator(n);
                current = n;
                const anchor = document.getElementById('form-pedido');
                if (anchor) window.scrollTo({ top: anchor.offsetTop - 72, behavior: 'smooth' });
            }

            /* Botones Siguiente */
            form.querySelectorAll('.btn-step-next').forEach(btn => {
                btn.addEventListener('click', () => {
                    const errs = validateStep(current);
                    if (errs.length) { showErrors(errs); return; }
                    showErrors([]);
                    goTo(parseInt(btn.dataset.next, 10));
                });
            });

            /* Botones Atrás */
            form.querySelectorAll('.btn-step-prev').forEach(btn => {
                btn.addEventListener('click', () => {
                    showErrors([]);
                    goTo(parseInt(btn.dataset.prev, 10));
                });
            });

            /* Selector +/− de cantidad (caso sin colores) */
            const qMinus   = document.getElementById('qtyMinus');
            const qPlus    = document.getElementById('qtyPlus');
            const qDisplay = document.getElementById('qtyDisplay');
            const qInput   = document.getElementById('cantidad_total');
            if (qMinus && qPlus && qDisplay && qInput) {
                let qty = parseInt(qInput.value, 10) || 1;
                function renderQty() {
                    qDisplay.textContent = qty;
                    qInput.value = qty;
                    qInput.dispatchEvent(new Event('change', { bubbles: true }));
                    qMinus.disabled = qty <= 1;
                }
                renderQty();
                qMinus.addEventListener('click', () => { if (qty > 1)  { qty--; renderQty(); } });
                qPlus.addEventListener('click',  () => { if (qty < 10) { qty++; renderQty(); } });
            }

            /* ── Stepper +/− inline en filas de colores ─────────── */
            function initQtyRow(row) {
                const btnMinus = row.querySelector('.qty-btn-inline[data-action="minus"]');
                const btnPlus  = row.querySelector('.qty-btn-inline[data-action="plus"]');
                const valSpan  = row.querySelector('.qty-val');
                const sel      = row.querySelector('.qty-select-hidden');
                if (!btnMinus || !btnPlus || !valSpan || !sel) return;

                function sync() {
                    valSpan.textContent = sel.value;
                    btnMinus.disabled = parseInt(sel.value, 10) <= 1;
                    btnMinus.style.opacity = parseInt(sel.value, 10) <= 1 ? '0.35' : '1';
                }

                btnMinus.addEventListener('click', () => {
                    const v = parseInt(sel.value, 10);
                    if (v > 1) {
                        sel.value = v - 1;
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                    }
                });
                btnPlus.addEventListener('click', () => {
                    const v = parseInt(sel.value, 10);
                    if (v < 10) {
                        sel.value = v + 1;
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                        sync();
                    }
                });
                sync();
            }

            /* Inicializa filas existentes */
            const colorsWrap = document.getElementById('colorsQtyWrap');
            if (colorsWrap) {
                colorsWrap.querySelectorAll('.color-qty-row').forEach(initQtyRow);

                /* Inicializa filas que pricing-combo.js inserta dinámicamente */
                new MutationObserver(mutations => {
                    mutations.forEach(m => {
                        m.addedNodes.forEach(node => {
                            if (node.nodeType === 1) {
                                if (node.classList.contains('color-qty-row')) initQtyRow(node);
                                node.querySelectorAll?.('.color-qty-row').forEach(initQtyRow);
                            }
                        });
                    });
                }).observe(colorsWrap, { childList: true, subtree: true });
            }
        })();
        </script>

    </main>



    <div class="footer-text">
        <?= htmlspecialchars($footerText) ?>
    </div>



    <!-- CTA sticky para móviles -->
    <a href="#form-pedido" class="cta-sticky-mobile" aria-label="Ir al formulario de pedido">
        <span><?= htmlspecialchars($ctaStickyMobileText) ?></span>
        <span class="sticky-price">
            $<?= number_format($precio_venta, 0, ',', '.') ?>
        </span>
    </a>

    <?php
    $success        = $success ?? '';
    $precioProducto = (float)($producto['precio_venta'] ?? 0);
    $nombreProducto = $producto['nombre'] ?? 'Producto';
    ?>
    <script>
        window.landingSuccess = <?= json_encode($success,        JSON_UNESCAPED_UNICODE) ?>;
        window.landingProductName = <?= json_encode($nombreProducto, JSON_UNESCAPED_UNICODE) ?>;
        window.landingProductPrice = <?= json_encode($precioProducto) ?>;
    </script>

    <?php
    $colores      = $colores ?? [];
    $colorsJson   = json_encode(array_values($colores), JSON_UNESCAPED_UNICODE);
    $comboEnabled = (int)($cfg['combo_enabled'] ?? 0);
    $comboPrice2  = (int)($cfg['combo_price_2'] ?? 0);
    if ($comboPrice2 <= 0) $comboPrice2 = 115000;
    ?>
    <div id="landingConfig"
        data-combo-enabled="<?= $comboEnabled ?>"
        data-combo-price2="<?= $comboPrice2 ?>"
        data-colors='<?= htmlspecialchars((string)($colorsJson ?: '[]'), ENT_QUOTES, "UTF-8") ?>'>
    </div>

    <script src="/tienda_mvc/public/js/pricing-summary.js" defer></script>
    <script src="/tienda_mvc/public/js/pricing-combo.js" defer></script>
    <script src="/tienda_mvc/public/js/funcionesLandin.js" defer></script>

    <!-- Botón WhatsApp flotante -->
    <a href="https://wa.me/<?= urlencode($waPhone) ?>?text=Hola%2C%20me%20interesa%20el%20producto%20y%20tengo%20una%20consulta."
       class="wa-float-btn" target="_blank" rel="noopener" aria-label="Consultar por WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.126.557 4.116 1.526 5.845L.057 23.998l6.304-1.658A11.954 11.954 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.793 9.793 0 01-4.997-1.367l-.356-.212-3.745.985.993-3.644-.232-.373A9.79 9.79 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/>
        </svg>
    </a>

    <!-- Contenedor FOMO notifications -->
    <div id="fomoContainer" aria-live="polite" aria-atomic="true"></div>

    <!-- EXIT INTENT POPUP -->
    <div id="exitPopup" class="exit-popup" role="dialog" aria-modal="true"
         aria-labelledby="exitPopupTitle" hidden>
        <div class="exit-popup__backdrop" id="exitPopupBackdrop"></div>
        <div class="exit-popup__box">
            <button class="exit-popup__close" id="exitPopupClose" aria-label="Cerrar">×</button>
            <div class="exit-popup__badge">🔥 ¡Espera un momento!</div>
            <h3 id="exitPopupTitle">Tu precio especial está a punto de perderse</h3>
            <p>Llevas un rato viendo este producto. Asegura tu descuento antes de que expire.</p>
            <div class="exit-popup__timer">
                <span class="exit-popup__timer-label">Oferta vence en:</span>
                <span id="exitCountdown" class="exit-popup__timer-digits">03:00</span>
            </div>
            <div class="exit-popup__prices">
                <?php if ($precio_regular > $precio_venta): ?>
                <span class="exit-popup__old">Antes $<?= number_format($precio_regular, 0, ',', '.') ?></span>
                <?php endif; ?>
                <span class="exit-popup__now">$<?= number_format($precio_venta, 0, ',', '.') ?></span>
                <?php if ($ahorro > 0): ?>
                <span class="exit-popup__save">Ahorras $<?= number_format($ahorro, 0, ',', '.') ?></span>
                <?php endif; ?>
            </div>
            <a href="#form-pedido" class="btn-primary exit-popup__cta" id="exitPopupCta">
                ✅ Quiero aprovecharlo ahora
            </a>
            <button class="exit-popup__dismiss" id="exitPopupDismiss">
                No gracias, prefiero perder el descuento
            </button>
        </div>
    </div>

</body>

</html>