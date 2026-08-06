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
$heroSubtitle2   = trim($cfg['hero_subtitle_2'] ?? '');
$heroSubtitle3   = trim($cfg['hero_subtitle_3'] ?? '');
$heroSubtitles   = array_filter([$heroSubtitle, $heroSubtitle2, $heroSubtitle3], fn($s) => $s !== '');
$heroNote        = $cfg['hero_note']         ?? 'Promoción válida solo por tiempo limitado.';
$heroButtonText  = $cfg['hero_button_text']  ?? '¡Necesito el mío!';
$heroMediaType   = $cfg['hero_media_type']   ?? 'imagen';
$heroMediaPath   = $cfg['hero_media_path']
    ?? ($producto['imagen_principal'] ?? BASE_URL . '/public/img/producto.png');

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
$benefitsMediaPath = $cfg['benefits_media_path'] ?? BASE_URL . '/public/img/producto/uso-1.jpg';

// ===== GALERÍA =====
$galleryPaths = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'gallery_' . $i . '_path';
    if (!empty($cfg[$key]) && trim($cfg[$key]) !== '') {
        $galleryPaths[] = $cfg[$key];
    }
}

// Variantes de color para la galería
$colorVariants = [];
if (!empty($cfg['color_variants'])) {
    $decoded = json_decode($cfg['color_variants'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $cv) {
            $name = trim($cv['name'] ?? '');
            $hex  = trim($cv['hex']  ?? '#000000');
            $imgs = array_values(array_filter(array_map('trim', $cv['images'] ?? []), fn($s) => $s !== ''));
            if ($name !== '' && !empty($imgs)) {
                $colorVariants[] = ['name' => $name, 'hex' => $hex, 'images' => $imgs];
            }
        }
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
$defaultBulletIcons = ['✨', '🎯', '🛡️'];
$porqueBulletIcons  = [];
foreach (['porque_bullet1_icon', 'porque_bullet2_icon', 'porque_bullet3_icon'] as $idx => $key) {
    $porqueBulletIcons[] = !empty($cfg[$key]) ? $cfg[$key] : $defaultBulletIcons[$idx];
}
$porqueMediaPath = $cfg['porque_media_path'] ?? BASE_URL . '/public/img/producto/uso-1.jpg';

// ===== TESTIMONIOS =====
$test1Name  = $cfg['test1_name']       ?? 'María G.';
$test1City  = $cfg['test1_city']       ?? 'Bogotá';
$test1Text  = $cfg['test1_text']       ?? 'Desde que lo uso, mi día a día es mucho más fácil. Llegó rápido y en perfecto estado.';
$test1Photo = $cfg['test1_photo_path'] ?? BASE_URL . '/public/img/producto/uso-1.jpg';

$test2Name  = $cfg['test2_name']       ?? 'Carlos R.';
$test2City  = $cfg['test2_city']       ?? 'Medellín';
$test2Text  = $cfg['test2_text']       ?? 'Muy buena atención, me explicaron todo por WhatsApp y el producto es tal cual a las fotos.';
$test2Photo = $cfg['test2_photo_path'] ?? BASE_URL . '/public/img/producto/uso-1.jpg';

$test3Name  = $cfg['test3_name']       ?? 'Laura P.';
$test3City  = $cfg['test3_city']       ?? 'Cali';
$test3Text  = $cfg['test3_text']       ?? 'Lo recomiendo totalmente. Me dieron confianza con el pago contraentrega y cumplió 10/10.';
$test3Photo = $cfg['test3_photo_path'] ?? BASE_URL . '/public/img/producto/uso-1.jpg';

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
$comparisonTitle        = $cfg['comparison_title']           ?? 'La diferencia que hace este producto';
$comparisonLabelWithout = $cfg['comparison_label_without']   ?? 'Sin el producto';
$comparisonLabelWith    = $cfg['comparison_label_with']      ?? 'Con el producto';
$comparisonImgWith  = $cfg['comparison_img_with']    ?? '';
$comparisonImgWithout = $cfg['comparison_img_without'] ?? '';
$comparisonRows  = [];
for ($i = 1; $i <= 5; $i++) {
    $without = trim($cfg["comparison_{$i}_without"] ?? '');
    $with    = trim($cfg["comparison_{$i}_with"]    ?? '');
    if ($without !== '' || $with !== '') {
        $comparisonRows[] = ['without' => $without, 'with' => $with];
    }
}
$_comparisonHasData = !empty($comparisonRows) || !empty($comparisonImgWithout) || !empty($comparisonImgWith);

// ===== AUTORIDAD / CREDIBILIDAD =====
$authorityEnabled    = (int)($cfg['authority_enabled']    ?? 1);
$authorityTitle      = $cfg['authority_title']      ?? '¿Por qué confiar en nosotros?';
$authorityYears      = $cfg['authority_years']      ?? '3';
$authorityDeliveries = $cfg['authority_deliveries'] ?? '5.000+';
$authorityRating     = $cfg['authority_rating']     ?? '4.9';
$authorityGuarantee  = $cfg['authority_guarantee']  ?? 'Garantía de satisfacción';

// ===== FOOTER =====
$footerText   = $cfg['footer_text']   ?? ('© ' . date('Y') . ' Tu Marca. Todos los derechos reservados.');
$showFooter   = (int)($cfg['show_footer'] ?? 1);

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

// ===== CTAs DE SECCIÓN — visibilidad =====
$showCtaBenefits        = (int)($cfg['show_cta_benefits']        ?? 1);
$showCtaGallery         = (int)($cfg['show_cta_gallery']         ?? 1);
$showCtaPorque          = (int)($cfg['show_cta_porque']          ?? 1);
$showCtaTestimonials    = (int)($cfg['show_cta_testimonials']    ?? 1);
$showCtaFaq             = (int)($cfg['show_cta_faq']             ?? 1);
$showCtaComoFunciona    = (int)($cfg['show_cta_como_funciona']   ?? 1);
$ctaComoFuncionaText    = $cfg['cta_como_funciona_text']   ?? 'Así de simple. ¿Listo para empezar?';
$ctaComoFuncionaButton  = $cfg['cta_como_funciona_button'] ?? 'Hacer mi pedido ahora →';
$showCtaComparison      = (int)($cfg['show_cta_comparison']      ?? 1);
$ctaComparisonButton    = $cfg['cta_comparison_button']    ?? 'Quiero experimentar la diferencia →';
$showCtaParaQuien       = (int)($cfg['show_cta_para_quien']      ?? 1);
$ctaParaQuienButton     = $cfg['cta_para_quien_button']    ?? 'Sí, es para mí →';
$showCtaWaTestimonios   = (int)($cfg['show_cta_wa_testimonios']  ?? 1);
$ctaWaTestimoniasButton = $cfg['cta_wa_testimonios_button'] ?? 'Yo también lo quiero →';

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

// ===== CARACTERÍSTICAS =====
$caractSectionTitle = $cfg['caract_section_title'] ?? 'Características del producto';
$caractItems = [];
for ($i = 1; $i <= 4; $i++) {
    if (!(int)($cfg["caract{$i}_active"] ?? 1)) continue;
    $cPath  = $cfg["caract{$i}_media_path"] ?? '';
    $cType  = $cfg["caract{$i}_media_type"] ?? 'image';
    $cTitle = $cfg["caract{$i}_title"]      ?? '';
    $cText  = $cfg["caract{$i}_text"]       ?? '';
    if ($cTitle !== '' || $cPath !== '') {
        $caractItems[] = ['media_path' => $cPath, 'media_type' => $cType, 'title' => $cTitle, 'text' => $cText];
    }
}

// ===== SECCIONES VISIBLES =====
$showBenefits         = (int)($cfg['show_benefits']        ?? 1);
$showGallery          = (int)($cfg['show_gallery']         ?? 1);
$showCaracteristicas  = (int)($cfg['show_caracteristicas'] ?? 1) && !empty($caractItems);
$showComoFunciona = (int)($cfg['show_como_funciona'] ?? 1);
$showCountdown    = (int)($cfg['show_countdown']     ?? 1);
$showPorque       = (int)($cfg['show_porque']        ?? 1);
$showParaQuien    = (int)($cfg['show_para_quien']    ?? 1);
$showTestimonios    = (int)($cfg['show_testimonios']    ?? 1);
$showWaTestimonios  = (int)($cfg['show_wa_testimonios'] ?? (int)($cfg['wa_enabled'] ?? 1));
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
$stripLeadingEmoji = fn(string $t): string => trim(preg_replace('/^[^\p{L}\p{N}]+/u', '', $t));
$heroTrust1 = $stripLeadingEmoji($cfg['hero_trust_1'] ?? 'Pago al recibir');
$heroTrust2 = $stripLeadingEmoji($cfg['hero_trust_2'] ?? 'Envío gratis');
$heroTrust3 = $stripLeadingEmoji($cfg['hero_trust_3'] ?? 'Cambios sin problema');

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
$showTrustStrip      = (int)($cfg['show_trust_strip']      ?? 1);
$showAnnouncementBar = (int)($cfg['show_announcement_bar'] ?? 1);
$showStickyBar       = (int)($cfg['show_sticky_bar']       ?? 1);
$showComparison      = (int)($cfg['show_comparison']       ?? 1) && $_comparisonHasData;
$showResumenOferta   = (int)($cfg['show_resumen_oferta']   ?? 1);
$showPriceBox        = (int)($cfg['show_price_box'] ?? 1);
$regaloImagePath     = $cfg['regalo_image_path'] ?? '';
$regaloLabel         = $cfg['regalo_label']       ?? 'Cartera a juego incluida de regalo';
$showRegalo          = (int)($cfg['show_regalo']  ?? 1) && !empty($regaloImagePath);
$showCtaSticky       = (int)($cfg['show_cta_sticky']       ?? 1);
$showWhatsappBtn     = (int)($cfg['show_whatsapp_btn']     ?? 1);
$showFomo            = (int)($cfg['show_fomo']             ?? 1);
$showExitPopup       = (int)($cfg['show_exit_popup']       ?? 1);

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
    'obsidian',
    'blanc-luxe',
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
    <meta name="description" content="<?= htmlspecialchars(mb_substr($heroSubtitle, 0, 155)) ?>">
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>

    <?php
    $ogImage = !empty($heroMediaPath) ? 'https://' . $_SERVER['HTTP_HOST'] . $heroMediaPath : '';
    $ogUrl   = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $ogDesc  = mb_substr(strip_tags($heroSubtitle), 0, 200);
    ?>
    <!-- Open Graph — Facebook retargeting y previews -->
    <meta property="og:type"        content="product">
    <meta property="og:title"       content="<?= htmlspecialchars($heroTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDesc) ?>">
    <meta property="og:url"         content="<?= htmlspecialchars($ogUrl) ?>">
    <?php if ($ogImage): ?>
    <meta property="og:image"       content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>
    <meta property="og:locale"      content="es_CO">
    <meta property="product:price:amount"   content="<?= (int)$precio_venta ?>">
    <meta property="product:price:currency" content="COP">

    <!-- Preconnect a dominios externos para reducir latencia DNS+TLS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://connect.facebook.net">
    <link rel="dns-prefetch" href="https://www.clarity.ms">

    <!-- Fuentes: cargadas como link (no @import) para no bloquear el CSS principal -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap">

    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/order-modal.css">

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

    <script src="<?= BASE_URL ?>/public/js/main.js" defer></script>
</head>


<body>


    <?php if ($showAnnouncementBar): ?>
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
    <?php endif; ?>

    <!-- HERO -->
    <header class="container hero">
        <div class="hero-text">
            <h1><?= htmlspecialchars($heroTitle) ?></h1>

            <?php foreach ($heroSubtitles as $sub): ?>
            <p class="hero-subtitle"><?= htmlspecialchars($sub) ?></p>
            <?php endforeach; ?>

        </div>

        <div class="hero-media">
            <?php if ($heroMediaType === 'video'): ?>
                <video src="<?= htmlspecialchars($heroMediaPath) ?>"
                    <?php if (!empty($cfg['hero_poster_path'])): ?>poster="<?= htmlspecialchars($cfg['hero_poster_path']) ?>"<?php endif; ?>
                    autoplay muted loop playsinline
                    preload="auto"
                    style="max-width:100%; border-radius:10px;"></video>
            <?php else: ?>
                <img src="<?= htmlspecialchars($heroMediaPath) ?>"
                    alt="Imagen del producto"
                    fetchpriority="high"
                    decoding="async">
            <?php endif; ?>

            <!-- ✅ NUEVO: Badge flotante sobre la imagen -->
            <div class="hero-image-badge">
                ⭐ <?= $heroBadgeStars ?> · <?= $heroBadgeCustomers ?>
            </div>
        </div>
    </header>

    <?php
    // Orden de secciones dinámico
    $defaultSectionOrder = ['price_box','benefits','gallery','caracteristicas','como_funciona','countdown','porque','comparison','para_quien','testimonios','wa_testimonios','faqs','garantia','regalo'];
    $savedOrder = array_filter(array_map('trim', explode(',', $cfg['section_order'] ?? '')));
    $validSaved = array_values(array_filter(array_map(function($k) use ($defaultSectionOrder) {
        return in_array($k, $defaultSectionOrder, true) ? $k : null;
    }, $savedOrder)));
    $sectionOrder = array_merge($validSaved, array_diff($defaultSectionOrder, $validSaved));
    $sections = [];
    ?>

    <main>

        <!-- PRICE BOX -->
        <?php ob_start(); if ($showPriceBox): ?>
        <section class="container price-box-section animate-fadeup">
            <div class="price-box">
                <div class="price-label">Oferta exclusiva · Solo hoy</div>
                <?php if ($ahorro > 0): ?>
                <div class="save">Ahorras $<?= number_format($ahorro, 0, ',', '.') ?></div>
                <?php endif; ?>
                <?php if ($precio_regular > $precio_venta): ?>
                <div class="old">$<?= number_format($precio_regular, 0, ',', '.') ?></div>
                <?php endif; ?>
                <div class="new">$<?= number_format($precio_venta, 0, ',', '.') ?></div>

                <a href="#form-pedido" class="btn-primary" id="heroCta">
                    <?= htmlspecialchars($heroButtonText) ?>
                </a>

                <?php if (!empty($heroNote)): ?>
                <p class="hero-note"><?= htmlspecialchars($heroNote) ?></p>
                <?php endif; ?>

                <div class="hero-countdown-inline">
                    <span class="countdown-label">Expira en</span>
                    <span id="heroCountdown" class="countdown-digits" data-minutes="<?= $countdownMinutes ?>">--:--</span>
                </div>
            </div>

            <div class="hero-trust-row">
                <span class="hero-trust-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    <?= htmlspecialchars($heroTrust1) ?>
                </span>
                <span class="hero-trust-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <?= htmlspecialchars($heroTrust2) ?>
                </span>
                <span class="hero-trust-item">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                    <?= htmlspecialchars($heroTrust3) ?>
                </span>
            </div>
        </section>
        <?php endif; $sections['price_box'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showBenefits): ?>
        <!-- BENEFICIOS — tarjetas horizontales con foto por beneficio -->
        <section class="container benefits-section">
            <div class="benefit-cards-outer">
                <h2 class="section-title"><?= htmlspecialchars($benefitsTitle) ?></h2>

                <div class="benefit-cards-list">
                    <?php
                    $defaultBenefits = [
                        'Beneficio 1 enfocado en el resultado que quiere el cliente.',
                        'Beneficio 2 que ataque su principal dolor o problema.',
                        'Beneficio 3 que resalte comodidad, rapidez o facilidad.',
                        'Beneficio 4 relacionado con garantía, soporte o confianza.',
                    ];
                    $displayBenefits = !empty($benefits) ? $benefits : $defaultBenefits;
                    foreach ($displayBenefits as $idx => $b):
                        $imgNum  = $idx + 1;
                        $imgPath = !empty($cfg['benefit_' . $imgNum . '_img']) ? $cfg['benefit_' . $imgNum . '_img'] : '';
                    ?>
                    <div class="benefit-card-h animate-fadeup">
                        <?php if ($imgPath): ?>
                        <div class="benefit-card-h__img">
                            <img src="<?= htmlspecialchars($imgPath) ?>"
                                 alt="<?= htmlspecialchars($b) ?>"
                                 loading="lazy" decoding="async">
                        </div>
                        <?php endif; ?>
                        <div class="benefit-card-h__text"><?= htmlspecialchars($b) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($showCtaBenefits): ?>
            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaBenefitsText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaBenefitsButton) ?>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; $sections['benefits'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showGallery): ?>
        <!-- GALERÍA (principal + miniaturas) -->
        <section class="container">
            <h2 class="section-title"><?= htmlspecialchars($galleryTitle) ?></h2>

            <?php
            if (!empty($colorVariants)) {
                // Con variantes: la galería muestra las imágenes del primer color
                $firstColor = $colorVariants[0];
                $mainImg    = $firstColor['images'][0] ?? '';
                $thumbImgs  = array_slice($firstColor['images'], 1, 3);
            } else {
                // Sin variantes: usa las fotos del editor (con fallback)
                $gallery = !empty($galleryPaths) ? $galleryPaths : [
                    BASE_URL . '/public/img/producto/uso-1.jpg',
                    BASE_URL . '/public/img/producto/uso-1.jpg',
                    BASE_URL . '/public/img/producto/uso-1.jpg',
                    BASE_URL . '/public/img/producto/uso-1.jpg',
                ];
                $mainImg   = $gallery[0] ?? '';
                $thumbImgs = array_slice($gallery, 1, 3);
            }
            ?>

            <?php if (!empty($colorVariants)): ?>
            <div class="gallery-color-pills" role="group" aria-label="Colores disponibles">
                <span class="gallery-color-pills__label">Color:</span>
                <?php foreach ($colorVariants as $cIdx => $cv): ?>
                <button
                    type="button"
                    class="color-pill <?= $cIdx === 0 ? 'is-active' : '' ?>"
                    data-color-idx="<?= $cIdx ?>"
                    data-color-images="<?= htmlspecialchars(json_encode($cv['images'], JSON_UNESCAPED_UNICODE)) ?>"
                    aria-label="<?= htmlspecialchars($cv['name']) ?>"
                    title="<?= htmlspecialchars($cv['name']) ?>"
                    style="--cv-color:<?= htmlspecialchars($cv['hex']) ?>">
                    <span class="color-pill__swatch" style="background:<?= htmlspecialchars($cv['hex']) ?>"></span>
                    <span class="color-pill__name"><?= htmlspecialchars($cv['name']) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

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

            <?php if (!empty($colorVariants)): ?>
            <script>
            (function () {
                var pills   = document.querySelectorAll('.color-pill');
                var gallery = document.querySelector('[data-product-gallery]');
                if (!pills.length || !gallery) return;

                function applyImages(images) {
                    var mainImg   = gallery.querySelector('.product-gallery__main-img');
                    var thumbBtns = Array.from(gallery.querySelectorAll('.product-gallery__thumb'));

                    if (mainImg && images[0]) {
                        mainImg.src = images[0];
                    }
                    thumbBtns.forEach(function (btn, i) {
                        var img = btn.querySelector('img');
                        var src = images[i + 1] || '';
                        if (!src) {
                            btn.style.display = 'none';
                            return;
                        }
                        btn.style.display = '';
                        btn.setAttribute('data-src', src);
                        if (img) img.src = src;
                    });

                    // Notificar a initGallery() que reconstruya allSrcs desde el DOM actualizado
                    // para que el swipe y el click en miniaturas usen las imágenes del color activo
                    if (typeof window.galleryRefresh === 'function') {
                        window.galleryRefresh();
                    }
                }

                pills.forEach(function (pill) {
                    pill.addEventListener('click', function () {
                        pills.forEach(function (p) { p.classList.remove('is-active'); });
                        pill.classList.add('is-active');
                        var images = JSON.parse(pill.getAttribute('data-color-images') || '[]');
                        applyImages(images);
                    });
                });
            })();
            </script>
            <?php endif; ?>

            <?php if ($showCtaGallery): ?>
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaGalleryText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaGalleryButton) ?>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; $sections['gallery'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showCaracteristicas): ?>
        <!-- CARACTERÍSTICAS — carousel de tarjetas -->
        <section class="container caract-section animate-fadeup">
            <h2 class="section-title"><?= htmlspecialchars($caractSectionTitle) ?></h2>

            <div class="caract-slider" id="caractSlider">
                <div class="caract-track" id="caractTrack">
                    <?php foreach ($caractItems as $ci => $cItem): ?>
                    <div class="caract-slide">
                        <?php if (!empty($cItem['media_path'])): ?>
                        <div class="caract-media">
                            <?php if (($cItem['media_type'] ?? 'image') === 'video'): ?>
                            <div class="caract-video-wrap">
                                <video autoplay muted loop playsinline preload="metadata">
                                    <source src="<?= htmlspecialchars($cItem['media_path']) ?>">
                                </video>
                                <div class="caract-video-tap" aria-hidden="true"></div>
                                <div class="caract-play-overlay" aria-hidden="true">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="rgba(255,255,255,0.92)" aria-hidden="true"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </div>
                                <button class="caract-vol-btn" type="button" aria-label="Silenciar / activar sonido">
                                    <svg class="caract-vol-icon caract-vol-icon--muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                                        <line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/>
                                    </svg>
                                    <svg class="caract-vol-icon caract-vol-icon--sound" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                                        <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/>
                                    </svg>
                                </button>
                            </div>
                            <?php else: ?>
                            <img src="<?= htmlspecialchars($cItem['media_path']) ?>"
                                 alt="<?= htmlspecialchars($cItem['title'] ?? '') ?>"
                                 loading="<?= $ci === 0 ? 'eager' : 'lazy' ?>" decoding="async">
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="caract-body">
                            <?php if (!empty($cItem['title'])): ?>
                            <h3 class="caract-title"><?= htmlspecialchars($cItem['title']) ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($cItem['text'])): ?>
                            <p class="caract-text"><?= htmlspecialchars($cItem['text']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (count($caractItems) > 1): ?>
            <div class="caract-hint" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12H3"/><path d="M8 7l-5 5 5 5"/><path d="M16 7l5 5-5 5"/>
                </svg>
                <span>Desliza para ver más</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12H3"/><path d="M8 7l-5 5 5 5"/><path d="M16 7l5 5-5 5"/>
                </svg>
            </div>
            <?php endif; ?>

            <script>
            (function () {
                var track = document.getElementById('caractTrack');
                var slider = document.getElementById('caractSlider');
                if (!track || !slider) return;

                /* nudge — insinúa que hay más slides, se dispara una vez al cargar */
                if (track.querySelectorAll('.caract-slide').length > 1) {
                    setTimeout(function () {
                        slider.scrollTo({ left: 44, behavior: 'smooth' });
                        setTimeout(function () {
                            slider.scrollTo({ left: 0, behavior: 'smooth' });
                        }, 520);
                    }, 900);
                }

                /* ── Controles de video: tap-to-pause + mute toggle ── */
                track.addEventListener('click', function(e) {
                    /* Mute / unmute */
                    var volBtn = e.target.closest('.caract-vol-btn');
                    if (volBtn) {
                        e.stopPropagation();
                        var isNowUnmuted = volBtn.classList.toggle('is-unmuted');
                        var volWrap = volBtn.closest('.caract-video-wrap');
                        var volVid  = volWrap && volWrap.querySelector('video');
                        if (volVid) volVid.muted = !isNowUnmuted;
                        return;
                    }
                    /* Tap to pause / play */
                    var tap = e.target.closest('.caract-video-tap');
                    if (tap) {
                        var wrap  = tap.closest('.caract-video-wrap');
                        var video = wrap && wrap.querySelector('video');
                        if (!video) return;
                        if (video.paused) {
                            video.play();
                            wrap.classList.remove('is-paused');
                        } else {
                            video.pause();
                            wrap.classList.add('is-paused');
                        }
                    }
                });
            })();
            </script>
        </section>
        <?php endif; $sections['caracteristicas'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showComoFunciona): ?>
        <?php
            $cfSvg = [
                '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2" width="6" height="4" rx="1"/><path d="M9 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2h-3"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>',
                '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12H19M19 12l-4-4M19 12l-4 4"/><rect x="2" y="7" width="4" height="10" rx="1"/></svg>',
                '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
            ];
        ?>
        <!-- CÓMO FUNCIONA -->
        <section class="container como-funciona-section">
            <h2 class="section-title"><?= htmlspecialchars($cfTitle) ?></h2>
            <div class="steps-grid">
                <div class="step-card animate-fadeup">
                    <div class="step-num" aria-hidden="true">01</div>
                    <div class="step-icon-wrap" aria-hidden="true"><?= $cfSvg[0] ?></div>
                    <h3><?= htmlspecialchars($cfStep1Title) ?></h3>
                    <p><?= htmlspecialchars($cfStep1Desc) ?></p>
                </div>
                <div class="step-arrow-sep" aria-hidden="true">→</div>
                <div class="step-card animate-fadeup">
                    <div class="step-num" aria-hidden="true">02</div>
                    <div class="step-icon-wrap" aria-hidden="true"><?= $cfSvg[1] ?></div>
                    <h3><?= htmlspecialchars($cfStep2Title) ?></h3>
                    <p><?= htmlspecialchars($cfStep2Desc) ?></p>
                </div>
                <div class="step-arrow-sep" aria-hidden="true">→</div>
                <div class="step-card animate-fadeup">
                    <div class="step-num" aria-hidden="true">03</div>
                    <div class="step-icon-wrap" aria-hidden="true"><?= $cfSvg[2] ?></div>
                    <h3><?= htmlspecialchars($cfStep3Title) ?></h3>
                    <p><?= htmlspecialchars($cfStep3Desc) ?></p>
                </div>
            </div>
            <?php if ($showCtaComoFunciona): ?>
            <div class="section-cta">
                <?php if (!empty($ctaComoFuncionaText)): ?>
                <p><?= htmlspecialchars($ctaComoFuncionaText) ?></p>
                <?php endif; ?>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaComoFuncionaButton) ?>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; $sections['como_funciona'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showCountdown): ?>
        <!-- CONTADOR PROMOCIÓN -->
        <section class="container">
            <div class="countdown">
                <h2><?= htmlspecialchars($countdownTitle) ?></h2>
                <span id="countdown-timer" data-minutes="<?= $countdownMinutes ?>">--:--</span>
                <?php if (!empty($countdownText)): ?>
                <p class="countdown-desc"><?= htmlspecialchars($countdownText) ?></p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; $sections['countdown'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showPorque): ?>
        <!-- POR QUÉ TE ENCANTARÁ — tarjeta reveal: imagen arriba, contenido abajo -->
        <section class="container porque-section">
            <div class="porque-card animate-fadeup">

                <!-- Imagen / video arriba -->
                <div class="porque-card__media">
                    <?php if ($porqueMediaType === 'video'): ?>
                        <video src="<?= htmlspecialchars($porqueMediaPath) ?>"
                               autoplay muted loop playsinline></video>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($porqueMediaPath) ?>"
                             alt="<?= htmlspecialchars($porqueTitle) ?>"
                             loading="lazy" decoding="async">
                    <?php endif; ?>
                </div>

                <!-- Contenido abajo -->
                <div class="porque-card__body">
                    <h2 class="porque-card__title"><?= htmlspecialchars($porqueTitle) ?></h2>

                    <?php if (!empty(trim($porqueText))): ?>
                    <p class="porque-card__tagline"><?= htmlspecialchars($porqueText) ?></p>
                    <?php endif; ?>

                    <div class="porque-benefits-grid">
                        <?php
                        $displayBullets = !empty($porqueBullets) ? $porqueBullets : [
                            'Resultado directo desde el primer uso',
                            'Más fácil y rápido que cualquier alternativa',
                            'Respaldado por garantía y miles de clientes',
                        ];
                        foreach ($displayBullets as $idx => $pb):
                            $icon = $porqueBulletIcons[$idx] ?? '✅';
                        ?>
                        <div class="porque-benefit-card animate-fadeup">
                            <span class="porque-benefit-card__num"><?= $idx + 1 ?></span>
                            <span class="porque-benefit-card__text"><?= htmlspecialchars($pb) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($showCtaPorque): ?>
                    <a href="#form-pedido" class="btn-primary btn-full">
                        <?= htmlspecialchars($ctaPorqueButton) ?>
                    </a>
                    <?php endif; ?>
                </div>
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
                        <span class="para-quien-card__icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="13" cy="13" r="13" fill="#4caf7d"/>
                                <path d="M7.5 13.5l4 4 7-8" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <h3>Es para ti si…</h3>
                    </div>
                    <ul class="para-quien-list">
                        <?php foreach ($paraQuienSiItems as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($showCtaParaQuien): ?>
                    <a href="#form-pedido" class="btn-primary btn-cta-section para-quien-cta">
                        <?= htmlspecialchars($ctaParaQuienButton) ?>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="para-quien-card para-quien-card--no">
                    <div class="para-quien-card__header">
                        <span class="para-quien-card__icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="13" cy="13" r="13" fill="#e05c5c"/>
                                <path d="M9 9l8 8M17 9l-8 8" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                            </svg>
                        </span>
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
        <!-- TESTIMONIOS TICKER -->
        <?php
        $productoBannerFallback = $heroMediaType === 'imagen' && !empty($heroMediaPath)
            ? $heroMediaPath
            : ($producto['imagen_principal'] ?? null);
        ?>
        <section class="testimonios-ticker-section">
            <h2 class="section-title" style="text-align:center; padding: 0 var(--space-md) var(--space-md);"><?= htmlspecialchars($testimoniosTitle) ?></h2>
            <div class="testimonios-ticker" aria-label="Testimonios de clientes">
                <div class="testimonios-ticker__track">
                    <?php
                    $tickerItems = [
                        ['name' => $test1Name, 'text' => $test1Text, 'photo' => $test1Photo],
                        ['name' => $test2Name, 'text' => $test2Text, 'photo' => $test2Photo],
                        ['name' => $test3Name, 'text' => $test3Text, 'photo' => $test3Photo],
                    ];
                    foreach ([$tickerItems, $tickerItems] as $set):
                        foreach ($set as $t):
                            if (empty($t['text'])) continue;
                    ?>
                    <article class="testimonios-ticker__card">
                        <span class="testimonios-ticker__qmark" aria-hidden="true">"</span>
                        <div class="testimonios-ticker__stars">★★★★★</div>
                        <p class="testimonios-ticker__text">"<?= htmlspecialchars($t['text']) ?>"</p>
                        <div class="testimonios-ticker__author">
                            <img class="testimonios-ticker__avatar"
                                 src="<?= htmlspecialchars($t['photo']) ?>"
                                 alt="<?= htmlspecialchars($t['name']) ?>"
                                 loading="lazy" decoding="async">
                            <span class="testimonios-ticker__name"><?= htmlspecialchars($t['name']) ?></span>
                        </div>
                    </article>
                    <?php endforeach; endforeach; ?>
                </div>
            </div>

            <?php if ($showCtaTestimonials): ?>
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaTestimonialsText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaTestimonialsButton) ?>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; $sections['testimonios'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showWaTestimonios): ?>
        <!-- TESTIMONIOS WHATSAPP -->
        <section class="wa-testimonios-section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title"><?= htmlspecialchars($waTitle) ?></h2>
                    <p class="subtitle"><?= htmlspecialchars($waSubtitle) ?></p>
                </div>
            </div>
            <?php
            $waDefaults = [
                1 => ['name' => 'María González',   'time' => '• Hace 24 horas', 'text' => '¡Llegó antes de lo esperado! La calidad superó mis expectativas completamente.'],
                2 => ['name' => 'Carlos Rodríguez', 'time' => '• Hace 3 días',    'text' => 'Ya le recomendé a 3 amigos. El servicio post-venta es excelente.'],
                3 => ['name' => 'Ana Martínez',     'time' => '• Hace 1 semana',  'text' => 'Segunda compra y vuelvo a quedar encantada. Definitivamente mi tienda de confianza.'],
                4 => ['name' => 'Pedro López',      'time' => '• Hace 2 días',    'text' => 'Envío express en 24h. ¡Increíble! Justo lo que necesitaba con urgencia.'],
                5 => ['name' => 'Laura Sánchez',    'time' => '• Hace 4 días',    'text' => 'Viralicé en mis stories. Todos preguntan dónde compré. ¡Éxito total!'],
            ];
            $waTickerItems = [];
            for ($i = 1; $i <= 5; $i++) {
                $it = $waItems[$i - 1] ?? [];
                $waTickerItems[] = [
                    'name'  => trim($it['name']  ?? '') !== '' ? $it['name']  : $waDefaults[$i]['name'],
                    'time'  => trim($it['time']  ?? '') !== '' ? $it['time']  : $waDefaults[$i]['time'],
                    'text'  => trim($it['text']  ?? '') !== '' ? $it['text']  : $waDefaults[$i]['text'],
                    'image' => trim($it['image'] ?? ''),
                ];
            }
            ?>
            <div class="wa-ticker" id="waTickerScroll" aria-label="Testimonios WhatsApp">
                <div class="wa-ticker__track">
                    <?php foreach ([$waTickerItems, $waTickerItems] as $waSet): foreach ($waSet as $waT): ?>
                    <article class="wa-ticker__card">
                        <div class="wa-ticker__imgwrap">
                            <?php if (!empty($waT['image'])): ?>
                                <img src="<?= htmlspecialchars($waT['image']) ?>" alt="Testimonio WhatsApp <?= htmlspecialchars($waT['name']) ?>" class="wa-ticker__screenshot" loading="lazy" decoding="async">
                            <?php else: ?>
                                <img src="<?= BASE_URL ?>/public/img/testimonios/1.jpeg" alt="Testimonio WhatsApp" class="wa-ticker__screenshot" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </div>
                        <div class="wa-ticker__content">
                            <div class="wa-ticker__stars" aria-label="5 estrellas">★★★★★</div>
                            <p class="wa-ticker__text">"<?= htmlspecialchars($waT['text']) ?>"</p>
                            <div class="wa-ticker__meta">
                                <strong class="wa-ticker__name"><?= htmlspecialchars($waT['name']) ?></strong>
                                <span class="wa-ticker__time"><?= htmlspecialchars($waT['time']) ?></span>
                                <span class="wa-ticker__check">&#10003; verificado</span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; endforeach; ?>
                </div>
            </div>
            <div class="wa-ticker__hint" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12H3"/><path d="M8 7l-5 5 5 5"/><path d="M16 7l5 5-5 5"/>
                </svg>
                <span>Desliza para ver más</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12H3"/><path d="M8 7l-5 5 5 5"/><path d="M16 7l5 5-5 5"/>
                </svg>
            </div>
            <div class="container">
                <?php if ($showCtaWaTestimonios): ?>
                <div class="wa-testimonios-cta">
                    <a href="#form-pedido" class="btn-primary btn-cta-section">
                        <?= htmlspecialchars($ctaWaTestimoniasButton) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <script>
        (function () {
            var el = document.getElementById('waTickerScroll');
            if (!el) return;
            setTimeout(function () {
                el.scrollTo({ left: 44, behavior: 'smooth' });
                setTimeout(function () {
                    el.scrollTo({ left: 0, behavior: 'smooth' });
                }, 520);
            }, 1200);
        })();
        </script>
        <?php endif; $sections['wa_testimonios'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showFaqs): ?>
        <!-- PREGUNTAS FRECUENTES -->
        <section class="container faqs-section">
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

            <?php if ($showCtaFaq): ?>
            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaFaqText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaFaqButton) ?>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; $sections['faqs'] = ob_get_clean(); ?>

        <!-- TABLA COMPARATIVA -->
        <?php
            $svgX     = '<svg width="18" height="18" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="13" cy="13" r="13" fill="#e05c5c"/><path d="M9 9l8 8M17 9l-8 8" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>';
            $svgCheck = '<svg width="18" height="18" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="13" cy="13" r="13" fill="#4caf7d"/><path d="M7.5 13.5l4 4 7-8" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            $stripIcon = fn($t) => trim(preg_replace('/^[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}❌✅✓✗✔☑×✘\s]+/u', '', $t));
        ?>
        <?php ob_start(); if ($showComparison): ?>
        <section class="container comparison-section">
            <h2 class="section-title"><?= htmlspecialchars($comparisonTitle) ?></h2>
            <div class="comparison-table">
                <div class="comparison-col comparison-col--without">
                    <?php if (!empty($comparisonImgWithout)): ?>
                    <img src="<?= htmlspecialchars($comparisonImgWithout) ?>"
                         alt="Sin el producto"
                         class="comparison-col__img"
                         loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="comparison-header comparison-header--without">
                        <strong><?= htmlspecialchars($comparisonLabelWithout) ?></strong>
                    </div>
                    <ul class="comparison-list">
                        <?php foreach ($comparisonRows as $row): ?>
                            <?php if ($row['without'] !== ''): ?>
                            <li><span class="comparison-row-icon" aria-hidden="true"><?= $svgX ?></span><?= htmlspecialchars($stripIcon($row['without'])) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="comparison-col comparison-col--with">
                    <?php if (!empty($comparisonImgWith)): ?>
                    <img src="<?= htmlspecialchars($comparisonImgWith) ?>"
                         alt="Con el producto"
                         class="comparison-col__img"
                         loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="comparison-header comparison-header--with">
                        <strong><?= htmlspecialchars($comparisonLabelWith) ?></strong>
                    </div>
                    <ul class="comparison-list">
                        <?php foreach ($comparisonRows as $row): ?>
                            <?php if ($row['with'] !== ''): ?>
                            <li><span class="comparison-row-icon" aria-hidden="true"><?= $svgCheck ?></span><?= htmlspecialchars($stripIcon($row['with'])) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php if ($showCtaComparison): ?>
            <div class="comparison-cta">
                <a href="#form-pedido" class="btn-primary">
                    <?= htmlspecialchars($ctaComparisonButton) ?>
                </a>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; $sections['comparison'] = ob_get_clean(); ?>

        <!-- GARANTÍA -->
        <?php ob_start(); if ($showGarantia): ?>
        <?php
            $gSvgShield = '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
            $gIcons = [
                '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
                '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
                '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>',
                '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>',
            ];
            $gItems = array_values(array_filter([$garantiaItem1, $garantiaItem2, $garantiaItem3, $garantiaItem4], fn($x) => !empty($x)));
        ?>
        <div class="garantia-banner">
            <div class="container garantia-container">
                <div class="garantia-inner">
                    <div class="garantia-seal" aria-hidden="true"><?= $gSvgShield ?></div>
                    <div class="garantia-body">
                        <h3><?= htmlspecialchars($garantiaTitle) ?></h3>
                        <?php if (!empty($garantiaDesc)): ?>
                        <p class="garantia-desc"><?= htmlspecialchars($garantiaDesc) ?></p>
                        <?php endif; ?>
                        <div class="garantia-cards-grid">
                            <?php foreach ($gItems as $i => $gItem): ?>
                            <div class="garantia-card">
                                <span class="garantia-card__icon" aria-hidden="true"><?= $gIcons[$i] ?? $gIcons[0] ?></span>
                                <span><?= htmlspecialchars($stripIcon($gItem)) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; $sections['garantia'] = ob_get_clean(); ?>

        <!-- REGALO -->
        <?php ob_start(); if ($showRegalo): ?>
        <section class="container regalo-section animate-fadeup">
            <div class="regalo-card">
                <div class="regalo-ribbon">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a5 5 0 015 5c0 1.64-.79 3.09-2 4h5a1 1 0 010 2h-1l-1 9H6l-1-9H4a1 1 0 010-2h5A5 5 0 017 7a5 5 0 015-5zm0 2a3 3 0 100 6 3 3 0 000-6z"/></svg>
                    <span>Incluye de regalo</span>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a5 5 0 015 5c0 1.64-.79 3.09-2 4h5a1 1 0 010 2h-1l-1 9H6l-1-9H4a1 1 0 010-2h5A5 5 0 017 7a5 5 0 015-5zm0 2a3 3 0 100 6 3 3 0 000-6z"/></svg>
                </div>
                <div class="regalo-img-frame">
                    <img src="<?= htmlspecialchars($regaloImagePath) ?>"
                         alt="<?= htmlspecialchars($regaloLabel) ?>"
                         class="regalo-img"
                         loading="lazy" decoding="async">
                </div>
                <div class="regalo-footer">
                    <div class="regalo-divider"><span>✦</span></div>
                    <p class="regalo-label"><?= htmlspecialchars($regaloLabel) ?></p>
                </div>
            </div>
        </section>
        <?php endif; $sections['regalo'] = ob_get_clean(); ?>

        <?php foreach ($sectionOrder as $_sec) { echo $sections[$_sec] ?? ''; } ?>

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

        <!-- TRANSPORTADORAS — movidas al pie del form de pedido -->

        <!-- RESUMEN DE OFERTA — Ancla pre-formulario -->
        <?php if ($showResumenOferta): ?>
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
        <?php endif; ?>

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
        $hasColors = !empty($colores);
        ?>
        <!-- Ancla vacía — mantiene compatibilidad con initStickyVisibility -->
        <div id="form-pedido" style="display:none;" aria-hidden="true"></div>

        <?php
        $modalProductImg = !empty($producto['imagen_principal'])
            ? $producto['imagen_principal']
            : ($heroMediaType === 'imagen' && !empty($heroMediaPath) ? $heroMediaPath : BASE_URL . '/public/img/producto.png');
        ?>
        <!-- ══════════════════════════════════════════════════════
             MODAL DE PEDIDO
        ══════════════════════════════════════════════════════════ -->
        <?php
        $micoUser   = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>';
        $micoBag    = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>';
        $micoBox    = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>';
        $micoPaint  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r="1" fill="currentColor" stroke="none"/><circle cx="17.5" cy="10.5" r="1" fill="currentColor" stroke="none"/><circle cx="8.5" cy="7.5" r="1" fill="currentColor" stroke="none"/><circle cx="6.5" cy="12.5" r="1" fill="currentColor" stroke="none"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.65-.75 1.65-1.69 0-.44-.18-.84-.44-1.12-.29-.29-.44-.65-.44-1.13A1.64 1.64 0 0114.43 16h2c3.05 0 5.55-2.5 5.55-5.55C21.96 6.01 17.46 2 12 2z"/></svg>';
        $micoPhone  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="17" r="1" fill="currentColor" stroke="none"/></svg>';
        $micoHouse  = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
        $micoStore  = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M3 9l2.45-4.9A2 2 0 017.24 3h9.52a2 2 0 011.8 1.1L21 9"/><line x1="9" y1="3" x2="9" y2="9"/><line x1="15" y1="3" x2="15" y2="9"/></svg>';
        $micoCheck  = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        $micoLock   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>';
        $micoCard   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>';
        $micoTruck  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
        $micoSwap   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>';
        ?>
        <div id="orderModal" class="order-modal-overlay" hidden
             role="dialog" aria-modal="true" aria-labelledby="orderModalProductName">
            <div class="order-modal-card" id="orderModalCard">

                <button class="order-modal-close" id="orderModalClose" aria-label="Cerrar pedido">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M2 2L14 14M14 2L2 14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                </button>

                <div class="order-modal-card__scroll" id="orderModalScroll">

                <!-- Barra del producto -->
                <div class="order-modal-product-bar">
                    <div class="order-modal-product-bar__imgwrap">
                        <img src="<?= htmlspecialchars($modalProductImg) ?>"
                             alt="<?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?>"
                             class="order-modal-product-bar__img" loading="eager">
                        <span class="order-modal-product-bar__badge" id="modalCartBadge">1</span>
                    </div>
                    <div class="order-modal-product-bar__info">
                        <p class="order-modal-product-bar__name" id="orderModalProductName">
                            <?= htmlspecialchars($producto['nombre'] ?? '') ?>
                        </p>
                        <p class="order-modal-product-bar__price" id="modalBarPrice">
                            $<?= number_format($precio_venta, 0, ',', '.') ?>
                        </p>
                    </div>
                </div>

                <!-- Trust line compacta -->
                <div class="order-modal-trust-line">
                    <?php if ($ahorro > 0): ?>
                    <span class="order-modal-trust-line__saving">Ahorras $<?= number_format($ahorro, 0, ',', '.') ?></span>
                    <span class="order-modal-trust-line__dot" aria-hidden="true">·</span>
                    <?php endif; ?>
                    <span>Envío gratis</span>
                    <span class="order-modal-trust-line__dot" aria-hidden="true">·</span>
                    <span>Pagas al recibirlo</span>
                </div>

                <!-- Título y subtítulo del formulario -->
                <?php if (!empty($formTitle)): ?>
                <h2 class="order-modal-form-title"><?= htmlspecialchars($formTitle) ?></h2>
                <?php endif; ?>
                <p class="order-modal-intro-text">
                    <?= htmlspecialchars($formSubtitle) ?>
                </p>

                <!-- Cuerpo del formulario -->
                <div class="order-modal-body">

                    <!-- Barra de progreso -->
                    <div class="modal-progress-bar"><div class="modal-progress-bar__fill" id="modalProgressFill"></div></div>

                    <!-- Indicador de progreso — siempre 3 pasos -->
                    <div class="form-stepper-indicator" id="stepperIndicator">
                <div class="stepper-node is-active" data-step="1">
                    <div class="stepper-node__circle">
                        <span class="stepper-node__num">1</span>
                        <svg class="stepper-node__check" viewBox="0 0 14 14" fill="none"><polyline points="2 7 6 11 12 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="stepper-node__label"><?= $micoUser ?> ¿Quién eres?</span>
                </div>
                <div class="stepper-connector" data-after="1"></div>
                <div class="stepper-node" data-step="2">
                    <div class="stepper-node__circle">
                        <span class="stepper-node__num">2</span>
                        <svg class="stepper-node__check" viewBox="0 0 14 14" fill="none"><polyline points="2 7 6 11 12 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="stepper-node__label"><?= $micoBag ?> ¿Qué pides?</span>
                </div>
                <div class="stepper-connector" data-after="2"></div>
                <div class="stepper-node" data-step="3">
                    <div class="stepper-node__circle">
                        <span class="stepper-node__num">3</span>
                        <svg class="stepper-node__check" viewBox="0 0 14 14" fill="none"><polyline points="2 7 6 11 12 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="stepper-node__label"><?= $micoBox ?> ¿A dónde?</span>
                </div>
            </div>

            <div id="stepperErrors" class="error" style="display:none;"></div>

            <div class="form-box">
                <form id="formPedido" action="<?= BASE_URL ?>/Landing/enviarPedido" method="POST" novalidate>
                    <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['id'] ?? 1) ?>">

                    <!-- ══════════════════════════════
                         PASO 1 — DATOS PERSONALES
                    ══════════════════════════════════ -->
                    <div class="form-step is-active" data-step="1">
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoUser ?></div>
                            <h3 class="form-step__title">¿A nombre de quién va el pedido?</h3>
                            <p class="form-step__sub">Así sabemos cómo llamarte cuando te contactemos</p>
                        </div>

                        <div class="form-group">
                            <label for="nombre" class="form-label-lg">Tu nombre</label>
                            <input type="text" id="nombre" name="nombre" required class="input-lg"
                                placeholder="Ej: María"
                                autocomplete="given-name"
                                value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="apellidos" class="form-label-lg">Tu apellido</label>
                            <input type="text" id="apellidos" name="apellidos" required class="input-lg"
                                placeholder="Ej: González"
                                autocomplete="family-name"
                                value="<?= htmlspecialchars($old['apellidos'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="telefono" class="form-label-lg"><?= $micoPhone ?> Tu número de WhatsApp</label>
                            <input type="tel" id="telefono" name="telefono" required class="input-lg"
                                placeholder="Ej: 3001234567"
                                maxlength="10" autocomplete="tel-national" inputmode="numeric"
                                value="<?= htmlspecialchars($old['telefono'] ?? '') ?>">
                            <p class="tel-hint" id="telHint">Solo 10 números · empieza por 3 · por aquí te avisamos cuando salga tu pedido</p>
                        </div>

                        <div class="form-step__nav form-step__nav--end">
                            <button type="button" class="btn-step-next btn-next-lg" data-next="2">
                                Siguiente <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════
                         PASO 2 — QUÉ QUIERE PEDIR
                    ══════════════════════════════════ -->
                    <div class="form-step" data-step="2">

                        <?php if ($hasColors): ?>
                        <!-- Con colores: pills visuales -->
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoPaint ?></div>
                            <h3 class="form-step__title">¿Cuál color te gusta?</h3>
                            <p class="form-step__sub">Toca el color que quieres pedir</p>
                        </div>

                        <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">
                        <input type="hidden" id="cantidad_total" name="cantidad_total" value="1">

                        <div id="colorRowsWrap">
                            <div class="color-row" data-row="0">
                                <p class="color-row__lbl">Elige el color:</p>
                                <div class="color-pills-wrap">
                                    <?php foreach ($colores as $c): ?>
                                    <button type="button" class="color-pill" data-color="<?= htmlspecialchars($c) ?>">
                                        <?= htmlspecialchars($c) ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                <!-- Selects ocultos — los leen backend y pricing-summary.js -->
                                <select name="color_item[]" class="color-item-sel" aria-hidden="true" tabindex="-1" style="position:absolute;opacity:0;pointer-events:none;height:0">
                                    <option value=""></option>
                                    <?php foreach ($colores as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="color-row__qty-wrap" style="display:none;">
                                    <p class="color-row__qty-lbl">¿Cuántos de ese color?</p>
                                    <div class="qty-stepper qty-stepper--big">
                                        <button type="button" class="qty-btn qty-btn--big" data-action="minus" aria-label="Menos">−</button>
                                        <span class="qty-val-big">1</span>
                                        <button type="button" class="qty-btn qty-btn--big" data-action="plus" aria-label="Más">+</button>
                                    </div>
                                </div>
                                <select name="qty_item[]" class="qty-item-sel" aria-hidden="true" tabindex="-1" style="position:absolute;opacity:0;pointer-events:none;height:0">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <button type="button" id="addColorRowBtn" class="btn-add-color-row" style="display:none;">
                            + Quiero otro color diferente
                        </button>
                        <div class="color-sel-summary" id="colorSelSummary" style="display:none;">
                            Tu selección: <strong id="colorSelText"></strong>
                        </div>

                        <?php else: ?>
                        <!-- Sin colores: selector grande de cantidad -->
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoBag ?></div>
                            <h3 class="form-step__title">¿Cuántos vas a pedir?</h3>
                            <p class="form-step__sub">Elige la cantidad que deseas recibir</p>
                        </div>

                        <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">

                        <div class="qty-hero-wrap">
                            <div class="qty-stepper qty-stepper--hero">
                                <button type="button" class="qty-btn qty-btn--hero" id="qtyMinus" aria-label="Menos">−</button>
                                <span class="qty-disp-hero" id="qtyDisplay">1</span>
                                <button type="button" class="qty-btn qty-btn--hero" id="qtyPlus" aria-label="Más">+</button>
                            </div>
                            <p class="qty-unit-lbl" id="qtyUnitLbl">unidad</p>
                            <input type="hidden" id="cantidad_total" name="cantidad_total"
                                value="<?= htmlspecialchars((string)($old['cantidad_total'] ?? 1)) ?>">
                        </div>
                        <?php endif; ?>

                        <!-- Preview de precio en tiempo real -->
                        <div class="price-preview-strip" id="pricePreviewStrip"
                            data-unit="<?= (int)$precioVenta ?>"
                            data-reg="<?= (int)$precioRegular ?>"
                            data-d2="<?= $d2 ?>" data-d3="<?= $d3 ?>" data-act="<?= $act ?>">
                            <span class="price-preview-strip__lbl">Pagarás al recibirlo:</span>
                            <span class="price-preview-strip__amt" id="pricePreviewAmt">
                                $<?= number_format($precioVenta, 0, ',', '.') ?>
                            </span>
                        </div>

                        <div class="form-step__nav">
                            <button type="button" class="btn-step-prev" data-prev="1">← Atrás</button>
                            <button type="button" class="btn-step-next btn-next-lg" data-next="3">
                                Siguiente <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════
                         PASO 3 — ENTREGA
                    ══════════════════════════════════ -->
                    <div class="form-step" data-step="3">
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoBox ?></div>
                            <h3 class="form-step__title">¿A dónde te lo enviamos?</h3>
                            <p class="form-step__sub">Último paso — ya casi tienes tu pedido</p>
                        </div>

                        <div class="form-group">
                            <label for="departamento" class="form-label-lg">¿En qué departamento vives?</label>
                            <select id="departamento" name="departamento" required class="select-lg"
                                data-old="<?= htmlspecialchars($old['departamento'] ?? '') ?>">
                                <option value="">— Escoge tu departamento —</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="municipio" class="form-label-lg">¿En qué pueblo o ciudad?</label>
                            <select id="municipio" name="municipio" required class="select-lg"
                                data-old="<?= htmlspecialchars($old['municipio'] ?? '') ?>">
                                <option value="">Primero elige el departamento</option>
                            </select>
                        </div>

                        <div id="deliveryETA" class="delivery-eta-badge" style="display:none;" aria-live="polite">
                            <?= $micoBox ?> Llega estimado el: <strong id="deliveryETADate"></strong>
                        </div>

                        <div class="form-group">
                            <p class="form-label-lg">¿Cómo quieres recibirlo?</p>
                            <div class="radio-group--cards radio-group--cards-lg">
                                <label class="radio-card radio-card--lg">
                                    <input type="radio" name="tipo_entrega" value="domicilio"
                                        <?= (!empty($old['tipo_entrega']) && $old['tipo_entrega'] === 'domicilio') ? 'checked' : '' ?>>
                                    <span class="radio-card__icon"><?= $micoHouse ?></span>
                                    <span class="radio-card__main">Me lo llevan a mi casa</span>
                                    <span class="radio-card__note">Te lo entregan en la puerta</span>
                                </label>
                                <label class="radio-card radio-card--lg">
                                    <input type="radio" name="tipo_entrega" value="oficina"
                                        <?= (!empty($old['tipo_entrega']) && $old['tipo_entrega'] === 'oficina') ? 'checked' : '' ?>>
                                    <span class="radio-card__icon"><?= $micoStore ?></span>
                                    <span class="radio-card__main">Lo recojo en la oficina</span>
                                    <span class="radio-card__note">Interrapidísimo más cercana</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" id="grupo-direccion" style="display:none;">
                            <label for="direccion" class="form-label-lg">¿Cuál es la dirección?</label>
                            <input type="text" id="direccion" name="direccion" class="input-lg"
                                placeholder="Ej: Calle 5 # 10-20, frente a la escuela"
                                autocomplete="street-address"
                                value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
                            <p class="field-hint">Escríbela bien para que el mensajero llegue sin problema</p>
                        </div>

                        <div class="form-group" id="grupo-nota-entrega" style="display:none;">
                            <label for="nota_entrega" class="form-label-lg">
                                ¿Alguna indicación para el mensajero?
                                <span class="tag-optional"> — opcional</span>
                            </label>
                            <textarea id="nota_entrega" name="nota_entrega" rows="2"
                                placeholder="Ej: Portón verde · Solo en las tardes"
                                style="resize:vertical;"><?= htmlspecialchars($old['nota_entrega'] ?? '') ?></textarea>
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
                            <h3 class="order-summary__title">Lo que vas a pagar</h3>
                            <div class="order-summary__rows">
                                <div class="order-summary__row">
                                    <span><strong id="summaryQty">1</strong> <span id="summaryQtyWord">unidad</span></span>
                                    <strong id="summarySubtotal">$0</strong>
                                </div>
                                <div class="order-summary__row">
                                    <span>Descuento</span>
                                    <strong id="summaryDiscount">$0</strong>
                                </div>
                                <div class="order-summary__row" id="summarySaveRow" style="display:none;">
                                    <span>Ahorras</span>
                                    <strong id="summarySave" style="color:var(--success,#22c55e);"></strong>
                                </div>
                                <div class="order-summary__row">
                                    <span>Envío</span>
                                    <strong class="summary-free">GRATIS</strong>
                                </div>
                                <div class="order-summary__row order-summary__row--total">
                                    <span>Total a pagar cuando llegue</span>
                                    <strong id="summaryTotal">$0</strong>
                                </div>
                            </div>
                        </div>

                        <div class="form-step__nav form-step__nav--submit">
                            <button type="button" class="btn-step-prev" data-prev="2">← Atrás</button>
                            <button type="submit" id="btnSubmit" class="btn-submit-final">
                                <span id="btnSubmitText"><?= $micoCheck ?> Confirmar mi pedido — pago $<?= number_format($precio_venta, 0, ',', '.') ?> al recibirlo</span>
                                <span id="btnSubmitSpinner" style="display:none;">Enviando tu pedido...</span>
                            </button>
                        </div>

                        <div class="form-trust-row">
                            <span><?= $micoLock ?> Datos seguros</span>
                            <span><?= $micoCard ?> Pagas al recibirlo</span>
                            <span><?= $micoTruck ?> Envío gratis</span>
                            <span><?= $micoSwap ?> Cambios sin problema</span>
                        </div>
                        <p class="form-note">Te llamamos por WhatsApp para confirmar antes de enviarlo.</p>

                        <div class="form-wa-fallback">
                            ¿Prefieres pedir por WhatsApp?
                            <a href="https://wa.me/<?= urlencode($waPhone) ?>?text=<?= urlencode('Hola, quiero hacer un pedido de ' . ($producto['nombre'] ?? 'su producto')) ?>"
                               target="_blank" rel="noopener">Escribir directamente →</a>
                        </div>
                    </div>

                </form>

                <!-- TRANSPORTADORAS BAJO EL FORM — ticker -->
                <div class="form-carriers">
                    <p class="form-carriers__label">Enviamos con:</p>
                    <div class="form-carriers__ticker">
                        <div class="form-carriers__track">
                            <?php foreach ([1,2] as $_dup): ?>
                            <img src="<?= BASE_URL ?>/public/img/transportadoras/interrapidisimo.png" alt="Interrapidísimo" height="26" loading="lazy" decoding="async">
                            <img src="<?= BASE_URL ?>/public/img/transportadoras/envia.png"           alt="Envía"           height="26" loading="lazy" decoding="async">
                            <img src="<?= BASE_URL ?>/public/img/transportadoras/coordinadora.png"    alt="Coordinadora"    height="26" loading="lazy" decoding="async">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- PANTALLA DE ÉXITO -->
                <div id="stepperSuccess" style="display:none;">
                    <div class="order-success" style="position:relative;">
                        <div class="success-confetti" aria-hidden="true">
                            <div class="confetti-p"></div><div class="confetti-p"></div>
                            <div class="confetti-p"></div><div class="confetti-p"></div>
                            <div class="confetti-p"></div><div class="confetti-p"></div>
                            <div class="confetti-p"></div><div class="confetti-p"></div>
                            <div class="confetti-p"></div><div class="confetti-p"></div>
                        </div>
                        <div class="order-success__icon-wrap">
                            <svg class="success-check-svg" width="38" height="38" viewBox="0 0 24 24" fill="none"
                                stroke="var(--success,#22c55e)" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <polyline class="success-check-path" points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <p class="order-success__label">Pedido registrado</p>
                        <div class="order-success__num" id="orderSuccessNum" style="display:none;">
                            Pedido <strong id="orderSuccessNumVal"></strong>
                        </div>
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
                        <a id="shareWaBtn"
                            href="https://wa.me/?text=<?= urlencode('¡Acabo de pedir ' . ($producto['nombre'] ?? 'este producto') . '! Te lo recomiendo 👉 ' . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : '')) ?>"
                            class="order-success__share-btn" target="_blank" rel="noopener">
                            📲 Recomendar a un amigo
                        </a>
                    </div>
                </div>
                </div><!-- /.form-box -->
                </div><!-- /.order-modal-body -->

                </div><!-- /.order-modal-card__scroll -->

                <!-- Total flotante — visible en pasos 1 y 2 -->
                <div class="modal-floating-total is-hidden" id="modalFloatingTotal">
                    <div>
                        <div class="modal-floating-total__label">Total a pagar al recibirlo</div>
                        <div class="modal-floating-total__shipping">Envío gratis incluido</div>
                    </div>
                    <span class="modal-floating-total__amount" id="modalFloatingTotalAmt">$<?= number_format($precio_venta, 0, ',', '.') ?></span>
                </div>

            </div><!-- /.order-modal-card -->
        </div><!-- /#orderModal -->

        <script>
        /* ── Stepper — 3 pasos siempre ─────────────────────────── */
        (function () {
            const form = document.getElementById('formPedido');
            if (!form) return;

            let current = 1;
            const errBox    = document.getElementById('stepperErrors');
            const indicator = document.getElementById('stepperIndicator');

            function showErrors(errs) {
                if (!errBox) return;
                if (!errs.length) { errBox.style.display = 'none'; errBox.innerHTML = ''; return; }
                errBox.innerHTML = '<ul>' + errs.map(e => '<li>' + e + '</li>').join('') + '</ul>';
                errBox.style.display = 'block';
                errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function validateStep(n) {
                const errs = [];
                if (n === 1) {
                    const nombre    = form.querySelector('#nombre');
                    const apellidos = form.querySelector('#apellidos');
                    const tel       = form.querySelector('#telefono');
                    if (!nombre?.value.trim())    errs.push('El nombre es obligatorio.');
                    if (!apellidos?.value.trim()) errs.push('El apellido es obligatorio.');
                    const tv = tel?.value.trim() ?? '';
                    if (!tv)                       errs.push('El número de WhatsApp es obligatorio.');
                    else if (!/^3\d{9}$/.test(tv)) errs.push('El número debe tener 10 dígitos y empezar en 3.');
                } else if (n === 2) {
                    const wrap = document.getElementById('colorRowsWrap');
                    if (wrap) {
                        const anyColor = Array.from(wrap.querySelectorAll('.color-item-sel')).some(s => s.value !== '');
                        if (!anyColor) errs.push('Toca el color que quieres pedir.');
                    }
                }
                return errs;
            }

            const progressFill   = document.getElementById('modalProgressFill');
            const floatingTotal  = document.getElementById('modalFloatingTotal');
            const floatingAmt    = document.getElementById('modalFloatingTotalAmt');
            const PROGRESS_MAP   = { 1: '10%', 2: '48%', 3: '82%' };

            function updateIndicator(n) {
                if (!indicator) return;
                indicator.querySelectorAll('.stepper-node').forEach(el => {
                    const s = parseInt(el.dataset.step, 10);
                    el.classList.toggle('is-active', s === n);
                    el.classList.toggle('is-done', s < n);
                });
                indicator.querySelectorAll('.stepper-connector').forEach(el => {
                    el.classList.toggle('is-done', parseInt(el.dataset.after, 10) < n);
                });
                if (progressFill) progressFill.style.width = PROGRESS_MAP[n] || '10%';
                if (floatingTotal) floatingTotal.classList.toggle('is-hidden', n === 3);
            }

            function updateFloatingTotal() {
                setTimeout(() => {
                    const summaryTotal = document.getElementById('summaryTotal');
                    const pricePreview = document.getElementById('pricePreviewAmt');
                    const src = summaryTotal?.textContent?.trim() || pricePreview?.textContent?.trim() || '';
                    if (!src) return;

                    if (floatingAmt) floatingAmt.textContent = src;

                    // Actualizar precio en la barra del producto (header del modal)
                    const barPrice = document.getElementById('modalBarPrice');
                    if (barPrice) barPrice.textContent = src;

                    // Actualizar texto del botón de confirmar (preserva el ícono SVG)
                    const submitText = document.getElementById('btnSubmitText');
                    if (submitText) {
                        // El span contiene un SVG + texto — reemplazamos solo el texto final
                        const svgEl = submitText.querySelector('svg');
                        if (svgEl) {
                            // Remover nodos de texto y mantener el SVG
                            Array.from(submitText.childNodes).forEach(n => {
                                if (n.nodeType === Node.TEXT_NODE) n.remove();
                            });
                            submitText.appendChild(document.createTextNode(' Confirmar mi pedido — pago ' + src + ' al recibirlo'));
                        } else {
                            submitText.textContent = '✓ Confirmar mi pedido — pago ' + src + ' al recibirlo';
                        }
                    }
                }, 0);
            }

            function goTo(n) {
                form.querySelectorAll('.form-step').forEach(el => {
                    el.classList.toggle('is-active', parseInt(el.dataset.step, 10) === n);
                });
                updateIndicator(n);
                current = n;
                const scroll = document.getElementById('orderModalScroll');
                if (scroll) scroll.scrollTo({ top: 0, behavior: 'smooth' });
                requestAnimationFrame(() => {
                    const step = form.querySelector(`.form-step[data-step="${n}"]`);
                    const first = step?.querySelector('input:not([type=hidden]):not([type=radio]), select');
                    first?.focus({ preventScroll: true });
                });
            }

            /* Actualizar total flotante cuando cambia el precio */
            document.addEventListener('landing:recalc', updateFloatingTotal);
            updateFloatingTotal();

            form.querySelectorAll('.btn-step-next').forEach(btn => {
                btn.addEventListener('click', () => {
                    const errs = validateStep(current);
                    if (errs.length) { showErrors(errs); return; }
                    showErrors([]);
                    goTo(parseInt(btn.dataset.next, 10));
                });
            });
            form.querySelectorAll('.btn-step-prev').forEach(btn => {
                btn.addEventListener('click', () => { showErrors([]); goTo(parseInt(btn.dataset.prev, 10)); });
            });

            /* ── Color pills (paso 2 con colores) ─────────────── */
            const colorRowsWrap = document.getElementById('colorRowsWrap');

            function syncCantidadTotal() {
                let t = 0;
                if (colorRowsWrap) {
                    colorRowsWrap.querySelectorAll('.color-row').forEach(row => {
                        const cSel = row.querySelector('.color-item-sel');
                        const qSel = row.querySelector('.qty-item-sel');
                        if (cSel?.value) t += parseInt(qSel?.value || '1', 10);
                    });
                }
                const cantEl = form.querySelector('#cantidad_total');
                if (cantEl) cantEl.value = Math.max(1, t);
                const badge = document.getElementById('modalCartBadge');
                if (badge) badge.textContent = Math.max(1, t);
                document.dispatchEvent(new Event('landing:recalc'));
            }

            function updateColorSummary() {
                const sumEl = document.getElementById('colorSelSummary');
                const txtEl = document.getElementById('colorSelText');
                if (!sumEl || !txtEl || !colorRowsWrap) return;
                const parts = [];
                colorRowsWrap.querySelectorAll('.color-row').forEach(row => {
                    const cSel = row.querySelector('.color-item-sel');
                    const qSel = row.querySelector('.qty-item-sel');
                    if (cSel?.value) parts.push(cSel.value + ' ×' + (qSel?.value || '1'));
                });
                txtEl.textContent = parts.join(' · ');
                sumEl.style.display = parts.length ? 'flex' : 'none';
            }

            function calcOrderTotal(unit, d2, d3, act, qty) {
                if (act !== 1 || qty <= 1) return unit * qty;
                let total = unit;
                if (qty >= 2) total += unit * (1 - d2 / 100);
                if (qty >= 3) total += (qty - 2) * unit * (1 - d3 / 100);
                return total;
            }

            function updateOrderSummary(qty) {
                const box = document.getElementById('orderSummary');
                if (!box) return;
                const unit = parseFloat(box.dataset.priceUnit) || 0;
                const d2   = parseInt(box.dataset.d2, 10) || 15;
                const d3   = parseInt(box.dataset.d3, 10) || 20;
                const act  = parseInt(box.dataset.act, 10) || 1;
                const subtotal = unit * qty;
                const total    = calcOrderTotal(unit, d2, d3, act, qty);
                const discount = subtotal - total;
                const fmt = n => '$' + Math.round(n).toLocaleString('es-CO');

                const qtyEl     = document.getElementById('summaryQty');
                const qtyWordEl = document.getElementById('summaryQtyWord');
                const subEl     = document.getElementById('summarySubtotal');
                const discEl    = document.getElementById('summaryDiscount');
                const saveRow   = document.getElementById('summarySaveRow');
                const saveEl    = document.getElementById('summarySave');
                const totalEl   = document.getElementById('summaryTotal');

                if (qtyEl)     qtyEl.textContent = qty;
                if (qtyWordEl) qtyWordEl.textContent = qty === 1 ? 'unidad' : 'unidades';
                if (subEl)     subEl.textContent = fmt(subtotal);
                if (discEl)    discEl.textContent = discount > 0 ? '-' + fmt(discount) : fmt(0);
                if (saveRow)   saveRow.style.display = discount > 0 ? 'flex' : 'none';
                if (saveEl)    saveEl.textContent = fmt(discount);
                if (totalEl)   totalEl.textContent = fmt(total);
            }

            function updatePricePreview(qty) {
                const strip = document.getElementById('pricePreviewStrip');
                const amtEl = document.getElementById('pricePreviewAmt');
                if (!strip || !amtEl) return;
                const unit = parseFloat(strip.dataset.unit) || 0;
                const d2   = parseInt(strip.dataset.d2, 10) || 15;
                const d3   = parseInt(strip.dataset.d3, 10) || 20;
                const act  = parseInt(strip.dataset.act, 10) || 1;
                const total = calcOrderTotal(unit, d2, d3, act, qty);
                amtEl.textContent = '$' + Math.round(total).toLocaleString('es-CO');
                updateOrderSummary(qty);
                // Notificar para que updateFloatingTotal() sincronice barra y botón submit
                document.dispatchEvent(new Event('landing:recalc'));
            }

            function initColorRow(row) {
                const pills    = row.querySelectorAll('.color-pill');
                const qtyWrap  = row.querySelector('.color-row__qty-wrap');
                const qtyValEl = row.querySelector('.qty-val-big');
                const cSel     = row.querySelector('.color-item-sel');
                const qSel     = row.querySelector('.qty-item-sel');

                pills.forEach(pill => {
                    pill.addEventListener('click', () => {
                        pills.forEach(p => p.classList.remove('is-selected'));
                        pill.classList.add('is-selected');
                        if (cSel) cSel.value = pill.dataset.color;
                        if (qtyWrap) qtyWrap.style.display = '';
                        const addBtn = document.getElementById('addColorRowBtn');
                        if (addBtn) addBtn.style.display = '';
                        updateColorSummary();
                        syncCantidadTotal();
                        updatePricePreview(parseInt(form.querySelector('#cantidad_total')?.value || '1', 10));
                    });
                });

                const btnMinus = row.querySelector('.qty-btn[data-action="minus"]');
                const btnPlus  = row.querySelector('.qty-btn[data-action="plus"]');
                if (btnMinus && btnPlus && qSel) {
                    function syncQty() {
                        const v = parseInt(qSel.value, 10);
                        if (qtyValEl) qtyValEl.textContent = v;
                        btnMinus.disabled = v <= 1;
                    }
                    btnMinus.addEventListener('click', () => {
                        const v = parseInt(qSel.value, 10);
                        if (v > 1) { qSel.value = v - 1; qSel.dispatchEvent(new Event('change',{bubbles:true})); syncQty(); updateColorSummary(); syncCantidadTotal(); updatePricePreview(parseInt(form.querySelector('#cantidad_total')?.value||'1',10)); }
                    });
                    btnPlus.addEventListener('click', () => {
                        const v = parseInt(qSel.value, 10);
                        if (v < 10) { qSel.value = v + 1; qSel.dispatchEvent(new Event('change',{bubbles:true})); syncQty(); updateColorSummary(); syncCantidadTotal(); updatePricePreview(parseInt(form.querySelector('#cantidad_total')?.value||'1',10)); }
                    });
                    syncQty();
                }

                const removeBtn = row.querySelector('.btn-remove-color-row');
                if (removeBtn) {
                    removeBtn.addEventListener('click', () => { row.remove(); updateColorSummary(); syncCantidadTotal(); });
                }
            }

            if (colorRowsWrap) {
                colorRowsWrap.querySelectorAll('.color-row').forEach(initColorRow);

                document.getElementById('addColorRowBtn')?.addEventListener('click', () => {
                    const tmpl = colorRowsWrap.querySelector('.color-row');
                    if (!tmpl) return;
                    const newRow = tmpl.cloneNode(true);
                    newRow.querySelectorAll('.color-pill').forEach(p => p.classList.remove('is-selected'));
                    const nCSel = newRow.querySelector('.color-item-sel');
                    const nQSel = newRow.querySelector('.qty-item-sel');
                    const nWrap = newRow.querySelector('.color-row__qty-wrap');
                    const nVal  = newRow.querySelector('.qty-val-big');
                    if (nCSel) nCSel.value = '';
                    if (nQSel) nQSel.value = '1';
                    if (nWrap) nWrap.style.display = 'none';
                    if (nVal)  nVal.textContent = '1';
                    const rem = document.createElement('button');
                    rem.type = 'button'; rem.className = 'btn-remove-color-row'; rem.textContent = '✕ Quitar';
                    newRow.appendChild(rem);
                    colorRowsWrap.appendChild(newRow);
                    initColorRow(newRow);
                    newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }

            /* ── Cantidad sin colores (paso 2) ─────────────────── */
            const qMinus   = document.getElementById('qtyMinus');
            const qPlus    = document.getElementById('qtyPlus');
            const qDisplay = document.getElementById('qtyDisplay');
            const qInput   = document.getElementById('cantidad_total');
            const qUnitLbl = document.getElementById('qtyUnitLbl');
            if (qMinus && qPlus && qDisplay && qInput) {
                let qty = parseInt(qInput.value, 10) || 1;
                function renderQty() {
                    qDisplay.textContent = qty;
                    qInput.value = qty;
                    if (qUnitLbl) qUnitLbl.textContent = qty === 1 ? 'unidad' : 'unidades';
                    qMinus.disabled = qty <= 1;
                    const badge = document.getElementById('modalCartBadge');
                    if (badge) badge.textContent = qty;
                    updatePricePreview(qty);
                    document.dispatchEvent(new Event('landing:recalc'));
                }
                renderQty();
                qMinus.addEventListener('click', () => { if (qty > 1)  { qty--; renderQty(); } });
                qPlus.addEventListener('click',  () => { if (qty < 10) { qty++; renderQty(); } });
            }

            updatePricePreview(1);

            /* ── Validación en tiempo real del teléfono ─────────── */
            (function () {
                const tel  = form.querySelector('#telefono');
                const hint = document.getElementById('telHint');
                if (!tel || !hint) return;
                tel.addEventListener('input', function () {
                    const v = tel.value.trim();
                    if (!v) {
                        tel.classList.remove('tel-valid', 'tel-invalid');
                        hint.className = 'tel-hint';
                        hint.textContent = 'Solo 10 números · empieza por 3 · por aquí te avisamos cuando salga tu pedido';
                        return;
                    }
                    if (/^3\d{9}$/.test(v)) {
                        tel.classList.add('tel-valid');
                        tel.classList.remove('tel-invalid');
                        hint.className = 'tel-hint ok';
                        hint.textContent = 'Número válido';
                    } else {
                        tel.classList.add('tel-invalid');
                        tel.classList.remove('tel-valid');
                        hint.className = 'tel-hint err';
                        hint.textContent = v.length < 10
                            ? `Faltan ${10 - v.length} dígito${10 - v.length !== 1 ? 's' : ''}`
                            : !v.startsWith('3')
                                ? 'Debe empezar en 3'
                                : 'Revisa el número';
                    }
                });
            })();

            /* ── Fallback para navegadores sin :has() (Facebook IAB) ─── */
            (function () {
                const radios = form.querySelectorAll('input[name="tipo_entrega"]');
                if (!radios.length) return;
                function syncCards() {
                    radios.forEach(function (r) {
                        const card = r.closest('.radio-card--lg');
                        if (!card) return;
                        if (r.checked) {
                            card.classList.add('is-checked');
                        } else {
                            card.classList.remove('is-checked');
                        }
                    });
                }
                radios.forEach(function (r) { r.addEventListener('change', syncCards); });
                syncCards();
            })();
        })();
        </script>

    <script>
    /* ── Modal de pedido — open / close ──────────────────────── */
    (function () {
        var modal    = document.getElementById('orderModal');
        var card     = document.getElementById('orderModalCard');
        var closeBtn = document.getElementById('orderModalClose');
        if (!modal || !closeBtn) return;

        function openModal() {
            modal.removeAttribute('hidden');
            document.body.classList.add('modal-open');
            var scroll = document.getElementById('orderModalScroll');
            if (scroll) scroll.scrollTop = 0;
            /* Enfocar el primer campo visible */
            var first = card ? card.querySelector('.form-step.is-active input:not([type="hidden"])') : null;
            if (first) setTimeout(function () { first.focus(); }, 80);
        }

        function closeModal() {
            modal.setAttribute('hidden', '');
            document.body.classList.remove('modal-open');
            setTimeout(function () {
                if (typeof window.dispararFomo === 'function') window.dispararFomo();
            }, 400);
        }

        /* Interceptar todos los CTAs usando event delegation
           (funciona para elementos que aparecen DESPUÉS del script, como el sticky mobile) */
        document.addEventListener('click', function (e) {
            var target = e.target.closest('a[href="#form-pedido"]');
            if (target) {
                e.preventDefault();
                openModal();
            }
        });

        closeBtn.addEventListener('click', closeModal);

        /* Cerrar al hacer clic fuera del card */
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        /* Cerrar con ESC */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hasAttribute('hidden')) closeModal();
        });

        /* Auto-abrir si el servidor devolvió éxito (recarga tras submit) */
        if (typeof window.landingSuccess !== 'undefined' && window.landingSuccess) {
            openModal();
        }

        /* Sincronizar precio del header del modal con el total del resumen */
        var summaryTotal = document.getElementById('summaryTotal');
        var modalTotal   = document.getElementById('modalTotalPrice');
        if (summaryTotal && modalTotal) {
            new MutationObserver(function () {
                modalTotal.textContent = summaryTotal.textContent;
            }).observe(summaryTotal, { childList: true, characterData: true, subtree: true });
        }
    })();
    </script>

    </main>



    <?php if ($showFooter): ?>
    <div class="footer-text">
        <?= htmlspecialchars($footerText) ?>
    </div>
    <?php endif; ?>



    <!-- espaciador para que el sticky nunca tape contenido -->
    <div class="sticky-spacer" aria-hidden="true"></div>

    <!-- CTA sticky para móviles -->
    <?php if ($showCtaSticky): ?>
    <div class="cta-sticky-mobile">
        <div class="csm-info">
            <?php if (!empty($producto['imagen_principal'])): ?>
            <img class="csm-thumb"
                 src="<?= htmlspecialchars($producto['imagen_principal']) ?>"
                 alt="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
                 loading="eager" decoding="async">
            <?php endif; ?>
            <div class="csm-meta">
                <span class="csm-name"><?= htmlspecialchars($producto['nombre'] ?? '') ?></span>
                <div class="csm-prices">
                    <span class="csm-current">$<?= number_format($precio_venta, 0, ',', '.') ?></span>
                    <?php if ($precio_regular > $precio_venta): ?>
                    <span class="csm-was">$<?= number_format($precio_regular, 0, ',', '.') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a href="#form-pedido" class="btn-primary csm-cta">
            <?= htmlspecialchars($ctaStickyMobileText) ?>
        </a>
    </div>
    <?php endif; ?>

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

    <script src="<?= BASE_URL ?>/public/js/pricing-summary.js" defer></script>
    <script src="<?= BASE_URL ?>/public/js/pricing-combo.js" defer></script>
    <script src="<?= BASE_URL ?>/public/js/funcionesLandin.js" defer></script>

    <!-- Botón WhatsApp flotante -->
    <?php if ($showWhatsappBtn): ?>
    <a href="https://wa.me/<?= urlencode($waPhone) ?>?text=Hola%2C%20me%20interesa%20el%20producto%20y%20tengo%20una%20consulta."
       class="wa-float-btn" target="_blank" rel="noopener" aria-label="Consultar por WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.126.557 4.116 1.526 5.845L.057 23.998l6.304-1.658A11.954 11.954 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.793 9.793 0 01-4.997-1.367l-.356-.212-3.745.985.993-3.644-.232-.373A9.79 9.79 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/>
        </svg>
    </a>
    <?php endif; ?>

    <!-- Contenedor FOMO notifications -->
    <?php if ($showFomo): ?>
    <div id="fomoContainer" aria-live="polite" aria-atomic="true"></div>
    <?php endif; ?>

    <!-- EXIT INTENT POPUP -->
    <?php if ($showExitPopup): ?>
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
    <?php endif; ?>

    <!-- ── Analytics al final del body para no bloquear el render ────── -->
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s){
            if(f.fbq)return;
            n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
            t=b.createElement(e);t.async=!0;t.src=v;
            s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)
        }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init','1248724310406936');
        fbq('track','PageView');
        fbq('track','ViewContent',{
            content_name: <?= json_encode($producto['nombre'] ?? 'Producto') ?>,
            content_ids:  [<?= json_encode((string)($producto['id'] ?? '')) ?>],
            content_type: 'product',
            value:        <?= json_encode((float)($producto['precio_venta'] ?? 0)) ?>,
            currency:     'COP'
        });
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=1248724310406936&ev=PageView&noscript=1">
    </noscript>

    <!-- Microsoft Clarity -->
    <?php $clarityId = 'wm68pleap5'; ?>
    <?php if ($clarityId !== ''): ?>
    <script>
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window,document,"clarity","script","<?= htmlspecialchars($clarityId) ?>");
    </script>
    <?php endif; ?>

</body>

</html>