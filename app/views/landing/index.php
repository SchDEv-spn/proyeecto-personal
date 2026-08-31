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

/* Errores y confirmacion del envio del pedido.
   Se normalizan aqui arriba porque el formulario los necesita mucho antes
   de donde estaban definidos. El controlador ya los pasaba, pero la vista
   nunca los pintaba: quien enviaba el formulario sin JavaScript veia la
   pagina recargarse con sus datos intactos y ni una palabra de que habia
   fallado — o de que su pedido si se habia guardado. */
$errores = (isset($errores) && is_array($errores)) ? $errores : [];
$success = trim((string)($success ?? ''));

/* El contador de pedidos recientes se calculaba aquí pero no se pinta en
   ninguna parte de la landing — la sección que lo mostraba se retiró y el
   cálculo se quedó huérfano. Si se quiere recuperar, el sitio es el
   .recent-orders-badge, que ya tiene estilos en style.css. */



$comboEnabled = (int)($cfg['combo_enabled'] ?? 0);
$comboPrice2  = (int)($cfg['combo_price_2'] ?? 0);
if ($comboPrice2 <= 0) $comboPrice2 = 115000; // fallback


// Un campo guardado en blanco debe comportarse como ausente: si no, la landing
// se publica con <title> y <h1> vacíos.
$val = fn($k, $default) => (isset($cfg[$k]) && trim((string)$cfg[$k]) !== '') ? $cfg[$k] : $default;

/* Quita el icono con el que venga escrito un texto del admin, sea emoji o
   viñeta. La página tiene un solo lenguaje de icono — SVG de trazo, que
   hereda el color del tema — y un emoji suelto delante de una frase lo
   rompe: Android lo pinta a color y con otra métrica.
   Ya se usaba en hero_trust, garantía y comparación; ahora también en la
   barra de anuncios y en el título de WhatsApp, que eran los dos sitios
   por donde seguían colándose.
   Se respeta lo que sí es texto: los signos de apertura ("¿Por qué…") y
   el "+" de las cifras ("+3.200 clientas"), que cambia lo que dicen. */
$stripLeadingEmoji = fn(string $t): string => trim(preg_replace('/^[^\p{L}\p{N}¿¡+$]+/u', '', $t));

// ===== HERO =====
$heroTitle       = $val('hero_title', $producto['nombre'] ?? 'Nombre del producto');
$heroSubtitle    = $val('hero_subtitle', 'Subtítulo potente que explique el beneficio principal del producto en una frase clara.');
$heroSubtitle2   = trim($cfg['hero_subtitle_2'] ?? '');
$heroSubtitle3   = trim($cfg['hero_subtitle_3'] ?? '');
$heroSubtitles   = array_filter([$heroSubtitle, $heroSubtitle2, $heroSubtitle3], fn($s) => $s !== '');
$heroNote        = $val('hero_note', 'Promoción válida solo por tiempo limitado.');
$heroButtonText  = $val('hero_button_text', '¡Necesito el mío!');
$heroMediaType   = $val('hero_media_type', 'imagen');
$heroMediaPath   = $val('hero_media_path', (!empty($producto['imagen_principal']) ? $producto['imagen_principal'] : BASE_URL . '/public/img/producto/uso-1.png'));

$benefitsMediaType = $val('benefits_media_type', 'imagen');
$porqueMediaType   = $val('porque_media_type', 'imagen');
// ===== BENEFICIOS =====
$benefitsTitle = $val('benefits_title', 'Beneficios clave para ti');

$benefits = [];
for ($i = 1; $i <= 4; $i++) {
    $key = 'benefit_' . $i;
    if (!empty($cfg[$key]) && trim($cfg[$key]) !== '') {
        $benefits[] = $cfg[$key];
    }
}
$benefitsMediaPath = $val('benefits_media_path', BASE_URL . '/public/img/producto/uso-1.png');

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
// Sin los dos puntos finales: era el único título de la página que los llevaba.
$countdownTitle = $val('countdown_title', 'La promoción termina en');
$countdownText  = $val('countdown_text', 'Después de que el contador llegue a cero, el precio puede volver a la normalidad.');

// ===== POR QUÉ TE ENCANTARÁ =====
$porqueTitle = $val('porque_title', '¿Por qué te encantará este producto?');
$porqueText  = $val('porque_text',
    'Aquí explicas de forma emocional y concreta qué hace que este producto sea diferente:
       qué sentirán, qué problema deja de existir, qué resultado obtienen.');

$porqueBullets = [];
foreach (['porque_bullet1', 'porque_bullet2', 'porque_bullet3'] as $key) {
    if (!empty($cfg[$key]) && trim($cfg[$key]) !== '') {
        $porqueBullets[] = $cfg[$key];
    }
}
/* Los campos porque_bulletN_icon del admin no se pintan en ningún sitio:
   la tarjeta de cada bullet muestra un número correlativo, no un icono.
   Se quitan del cálculo para no prometer una configuración que no existe.
   Si algún día se quieren iconos ahí, hay que añadirlos primero al HTML
   de .porque-benefit-card. */
$porqueMediaPath = $val('porque_media_path', BASE_URL . '/public/img/producto/uso-1.png');

// ===== TESTIMONIOS =====
$test1Name  = $val('test1_name', 'María G.');
$test1City  = $val('test1_city', 'Bogotá');
$test1Text  = $val('test1_text', 'Desde que lo uso, mi día a día es mucho más fácil. Llegó rápido y en perfecto estado.');
$test1Photo = $val('test1_photo_path', BASE_URL . '/public/img/producto/uso-1.png');

$test2Name  = $val('test2_name', 'Carlos R.');
$test2City  = $val('test2_city', 'Medellín');
$test2Text  = $val('test2_text', 'Muy buena atención, me explicaron todo por WhatsApp y el producto es tal cual a las fotos.');
$test2Photo = $val('test2_photo_path', BASE_URL . '/public/img/producto/uso-1.png');

$test3Name  = $val('test3_name', 'Laura P.');
$test3City  = $val('test3_city', 'Cali');
$test3Text  = $val('test3_text', 'Lo recomiendo totalmente. Me dieron confianza con el pago contraentrega y cumplió 10/10.');
$test3Photo = $val('test3_photo_path', BASE_URL . '/public/img/producto/uso-1.png');

// ===== TESTIMONIOS WHATSAPP (editable) =====
$waEnabled    = isset($cfg['wa_enabled']) ? (int)$cfg['wa_enabled'] : 1;
/* Era el único título de la página en Title Case y con emoji delante.
   Pasa a la misma voz que los otros once: frase en oración, sin icono. */
$waTitle      = $stripLeadingEmoji($val('wa_title', 'Conversaciones reales con nuestros clientes'));
$waSubtitle   = $val('wa_subtitle', 'Capturas sin retocar, tal como llegaron');
/* wa_footer_note no se pinta: la nota de "desliza para ver más" la pone
   el propio ticker (.wa-ticker__hint) con su icono de flechas. */

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
$faq1_q = $val('faq1_q', '¿Cuánto tarda en llegar mi pedido?');
$faq1_a = $val('faq1_a', 'Los tiempos de entrega pueden variar según tu ciudad, pero normalmente tu pedido llega entre 2 y 5 días hábiles después de la confirmación.');

$faq2_q = $val('faq2_q', '¿Puedo pagar contraentrega?');
$faq2_a = $val('faq2_a', 'Sí, en la mayoría de las ciudades manejamos pago contraentrega: pagas solo cuando el mensajero te entrega el producto.');

$faq3_q = $val('faq3_q', '¿Qué pasa si el producto llega dañado o con problemas?');
$faq3_a = $val('faq3_a', 'Si el producto llega con algún defecto o no es lo que esperabas, te ayudamos con cambio o solución según nuestra política de garantía.');

$faq4_q = $cfg['faq4_q'] ?? '';
$faq4_a = $cfg['faq4_a'] ?? '';
$faq5_q = $cfg['faq5_q'] ?? '';
$faq5_a = $cfg['faq5_a'] ?? '';
$faq6_q = $cfg['faq6_q'] ?? '';
$faq6_a = $cfg['faq6_a'] ?? '';

// ===== TABLA COMPARATIVA =====
$comparisonTitle        = $val('comparison_title', 'La diferencia que hace este producto');
$comparisonLabelWithout = $val('comparison_label_without', 'Sin el producto');
$comparisonLabelWith    = $val('comparison_label_with', 'Con el producto');
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
$authorityTitle      = $val('authority_title', '¿Por qué confiar en nosotros?');
$authorityYears      = $val('authority_years', '3');
$authorityDeliveries = $val('authority_deliveries', '5.000+');
$authorityRating     = $val('authority_rating', '4.9');
$authorityGuarantee  = $val('authority_guarantee', 'Garantía de satisfacción');

// ===== FOOTER =====
$footerText   = $cfg['footer_text']   ?? ('© ' . date('Y') . ' Tu Marca. Todos los derechos reservados.');
$showFooter   = (int)($cfg['show_footer'] ?? 1);

/* ===== CTAs dinámicas =====
   Todos los botones hablan en primera persona y desde el deseo de quien
   compra ("Quiero…", "Sí, …"), nunca desde la orden ("Comprar", "Hacer").
   Y ninguno lleva flecha final: antes cuatro la tenían y cinco no, así que
   al bajar por la página los botones parecían de dos familias distintas.
   Ojo: esto son los valores por defecto. Sólo se ven cuando el campo
   correspondiente está vacío en el admin. */
$ctaBenefitsText       = $val('cta_benefits_text', 'Ya sabes lo que hace. El siguiente paso es recibirlo en casa.');
$ctaBenefitsButton     = $val('cta_benefits_button', 'Quiero aprovechar la oferta');

$ctaGalleryText        = $val('cta_gallery_text', 'Lo que ves es lo que llega. Sin sorpresas, sin excusas.');
$ctaGalleryButton      = $val('cta_gallery_button', 'Lo quiero igual que en las fotos');

$ctaPorqueText         = $val('cta_porque_text', 'Miles lo recibieron. Tú eres el siguiente.');
$ctaPorqueButton       = $val('cta_porque_button', 'Quiero sentir ese cambio');

$ctaTestimonialsText   = $val('cta_testimonials_text', 'Ellos ya lo tienen. Tu pedido tarda menos de 2 minutos.');
$ctaTestimonialsButton = $val('cta_testimonials_button', 'Quiero ser el próximo en recibirlo');

$ctaFaqText            = $val('cta_faq_text', 'Dudas resueltas. Esto solo falta: hacer tu pedido.');
$ctaFaqButton          = $val('cta_faq_button', 'Sí, quiero pedirlo ahora');

$ctaStickyMobileText   = $stripLeadingEmoji($val('cta_sticky_mobile_text', 'Lo quiero ahora'));

// ===== CTAs DE SECCIÓN — visibilidad =====
$showCtaBenefits        = (int)($cfg['show_cta_benefits']        ?? 1);
$showCtaGallery         = (int)($cfg['show_cta_gallery']         ?? 1);
$showCtaPorque          = (int)($cfg['show_cta_porque']          ?? 1);
$showCtaTestimonials    = (int)($cfg['show_cta_testimonials']    ?? 1);
$showCtaFaq             = (int)($cfg['show_cta_faq']             ?? 1);
$showCtaComoFunciona    = (int)($cfg['show_cta_como_funciona']   ?? 1);
$ctaComoFuncionaText    = $val('cta_como_funciona_text', 'Así de simple. ¿Listo para empezar?');
$ctaComoFuncionaButton  = $val('cta_como_funciona_button', 'Quiero hacer mi pedido ahora');
$showCtaComparison      = (int)($cfg['show_cta_comparison']      ?? 1);
$ctaComparisonButton    = $val('cta_comparison_button', 'Quiero experimentar la diferencia');
$showCtaParaQuien       = (int)($cfg['show_cta_para_quien']      ?? 1);
$ctaParaQuienButton     = $val('cta_para_quien_button', 'Sí, es para mí');
$showCtaWaTestimonios   = (int)($cfg['show_cta_wa_testimonios']  ?? 1);
$ctaWaTestimoniasButton = $val('cta_wa_testimonios_button', 'Yo también lo quiero');

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
$caractSectionTitle = $val('caract_section_title', 'Características del producto');
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
$waPhone           = (string)preg_replace('/\D/', '', $val('wa_phone', '573023959721'));
$heroBadgeStars    = htmlspecialchars($val('hero_badge_stars', '4.9'));
$heroBadgeCustomers= htmlspecialchars($val('hero_badge_customers', '+3.200 clientes felices'));

// ===== ANNOUNCEMENT BAR =====
$announcementItems = [];
for ($i = 1; $i <= 6; $i++) {
    $k = "announcement_item_{$i}";
    if (!empty($cfg[$k])) $announcementItems[] = $stripLeadingEmoji($cfg[$k]);
}
if (empty($announcementItems)) {
    /* Sin emoji: la barra los pinta en color sobre un fondo de tema y cada
       teléfono los dibuja distinto. Aquí el ritmo lo dan las mayúsculas y
       el espaciado de letra, que ya trae el CSS. */
    $announcementItems = [
        'Quedan pocas unidades',
        'Envío gratis a todo el país',
        'Pago contraentrega',
        $heroBadgeCustomers,
        'Empaque discreto y seguro',
    ];
}

// ===== HERO TRUST ROW =====
$heroTrust1 = $stripLeadingEmoji($val('hero_trust_1', 'Pago al recibir'));
$heroTrust2 = $stripLeadingEmoji($val('hero_trust_2', 'Envío gratis'));
$heroTrust3 = $stripLeadingEmoji($val('hero_trust_3', 'Cambios sin problema'));

// ===== CÓMO FUNCIONA =====
$cfTitle      = $val('cf_title', 'Así de simple es recibirlo en casa');
/* Los campos cf_stepN_icon tampoco se pintan: los tres pasos usan el
   juego fijo $cfSvg que se define más abajo, junto al HTML. */
$cfStep1Title = $val('cf_step1_title', 'Haz tu pedido');
$cfStep1Desc  = $val('cf_step1_desc', 'Llena el formulario en menos de 2 minutos. Sin registro previo ni tarjeta de crédito.');
$cfStep2Title = $val('cf_step2_title', 'Empacamos y enviamos');
$cfStep2Desc  = $val('cf_step2_desc', 'Al día siguiente hábil despachamos tu pedido, empacado con cuidado hacia tu puerta.');
$cfStep3Title = $val('cf_step3_title', 'Lo recibes y pagas');
$cfStep3Desc  = $val('cf_step3_desc', 'El mensajero llega a tu casa. Revisas el producto y pagas solo cuando estás satisfecho.');

// ===== GARANTÍA =====
$showGarantia  = (int)($cfg['show_garantia']  ?? 1);
$garantiaTitle = $val('garantia_title', 'Tu compra está 100% protegida');
$garantiaDesc  = $val('garantia_desc', 'Si el producto llega dañado, diferente a lo descrito o simplemente no te convence, te lo solucionamos. Sin burocracia, sin excusas. Nuestra promesa es tu tranquilidad.');
/* Sin emoji delante: esta sección ya pone su propio icono SVG por tarjeta,
   y de hecho $stripIcon() borraba el emoji al pintar — o sea que estos
   caracteres nunca llegaban a verse. */
$garantiaItem1 = $val('garantia_item1', 'Pagas solo cuando recibes el producto en tus manos');
$garantiaItem2 = $val('garantia_item2', 'Envío gratis incluido a cualquier ciudad');
$garantiaItem3 = $val('garantia_item3', 'Si llega dañado o incorrecto, lo reponemos');
$garantiaItem4 = $val('garantia_item4', 'Asesor en WhatsApp disponible para ti');

// ===== SECCIONES FIJAS (no reordenables) =====
/* Las transportadoras viven en el pie del formulario (.form-carriers) y
   se muestran siempre: no hay interruptor. El antiguo show_trust_strip
   controlaba una .trust-strip que ya no existe, así que se retiró del
   editor y del controlador. La columna sigue en la tabla, sin uso. */
$showAnnouncementBar = (int)($cfg['show_announcement_bar'] ?? 1);
$showStickyBar       = (int)($cfg['show_sticky_bar']       ?? 1);
$showComparison      = (int)($cfg['show_comparison']       ?? 1) && $_comparisonHasData;
$showResumenOferta   = (int)($cfg['show_resumen_oferta']   ?? 1);
$showPriceBox        = (int)($cfg['show_price_box'] ?? 1);
$regaloImagePath     = $cfg['regalo_image_path'] ?? '';
$regaloLabel         = $val('regalo_label', 'Cartera a juego incluida de regalo');
$showRegalo          = (int)($cfg['show_regalo']  ?? 1) && !empty($regaloImagePath);
$showCtaSticky       = (int)($cfg['show_cta_sticky']       ?? 1);
$showWhatsappBtn     = (int)($cfg['show_whatsapp_btn']     ?? 1);
$showFomo            = (int)($cfg['show_fomo']             ?? 1);
$showExitPopup       = (int)($cfg['show_exit_popup']       ?? 1);

// ===== FORM HEADER =====
$formKicker   = $val('form_kicker', 'Último paso · te toma menos de 1 minuto');
$formTitle    = $val('form_title', 'Haz tu pedido — Pago al recibir');
$formSubtitle = $val('form_subtitle', 'Sin adelantos · El mensajero llega a tu puerta');

// ===== TÍTULOS DE SECCIÓN =====
/* "Galería" era la única etiqueta de catálogo entre doce títulos que
   hablan de lo que gana quien compra. */
$galleryTitle     = $val('gallery_title', 'Míralo por todos lados');
$testimoniosTitle = $val('testimonios_title', 'Lo que cuentan nuestros clientes');
$paraQuienTitle   = $val('para_quien_title', '¿Este producto es para ti?');
$faqTitle         = $val('faq_title', 'Preguntas frecuentes');

// Colores con fallback
$primaryColor    = $config['primary_color']    ?? '#3c7a4a';
$secondaryColor  = $config['secondary_color']  ?? '#007bff';
$accentColor     = $config['accent_color']     ?? '#730dad';
$backgroundColor = $config['background_color'] ?? '#f5f5f5';
$textColor       = $config['text_color']       ?? '#222222';

/* Tema — la lista válida sale de app/config/themes.php, que es la misma
   que usan el editor y el controlador. Cuando cada sitio mantenía su
   propia copia, bastaba olvidar una para que un tema dejara de
   funcionar sin dar ningún error.
   resolverTema() traduce además los slugs retirados al podar de nueve
   temas a cinco: una landing guardada como 'obsidian' se pinta con
   'relojes', su sucesor, en vez de con el tema por defecto. */
$temasValidos = LandingConfig::temasValidos();
$theme = LandingConfig::resolverTema($cfg['theme'] ?? null);

// Colores base (5 existentes)
$primaryColor    = $cfg['primary_color']    ?? null;
$secondaryColor  = $cfg['secondary_color']  ?? null;
$accentColor     = $cfg['accent_color']     ?? null;
$backgroundColor = $cfg['background_color'] ?? null;
$textColor       = $cfg['text_color']       ?? null;

/* ═══════════════════════════════════════════════════════════════
   JUEGO DE ICONOS
   Un solo set de trazo, heredando el color con currentColor, para toda
   la landing. Antes convivían estos SVG con emoji del sistema sueltos
   (⭐ 🛡️ ✅ 💳 🔄 🔥 ⚠️ 📲): en Android los pinta Noto en color y chocan
   de frente con la paleta del tema, además de verse de otro tamaño y
   otra alineación en cada teléfono.
   Vive aquí arriba porque lo usan secciones repartidas por todo el
   archivo, desde el hero hasta el popup de salida.
   ═══════════════════════════════════════════════════════════════ */
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

// Los que sustituyen a los emoji sueltos que quedaban por la página.
$micoStar   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.9 6.26 6.85.72-5.1 4.6 1.42 6.72L12 16.9l-6.07 3.4L7.35 13.6 2.25 9l6.85-.72z"/></svg>';
$micoShield = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
$micoFlame  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2s1.5 3.2-.8 5.6C9 9.9 7 11.4 7 14.2A5.2 5.2 0 0012.2 19 5 5 0 0017 14.1c0-2.4-1.4-3.6-1.4-3.6s-.5 1.6-1.7 1.9c.6-2.6-.6-5.6-1.9-7.2-.6-.8-1-2.2 0-3.2z"/></svg>';
$micoAlert  = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
$micoShare  = '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="10.5" x2="15.4" y2="6.5"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/></svg>';

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
    <meta name="theme-color" content="<?= htmlspecialchars($primaryColor ?: '#0A0A0A') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= BASE_URL ?>/public/icons/icon-192.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/public/icons/icon-192.png">
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>

    <?php
    // og:image nunca puede ser el propio vídeo del hero: Facebook no lo
    // renderiza como preview. Si el hero es vídeo, usar el poster o la
    // primera imagen de galería (con $val() porque hero_poster_path puede
    // guardarse como '' y no como NULL — ?? no cubriría ese caso).
    $ogImagePath = es_video($heroMediaPath)
        ? $val('hero_poster_path', $galleryPaths[0] ?? ($producto['imagen_principal'] ?? ''))
        : $heroMediaPath;
    $ogImage = !empty($ogImagePath) ? 'https://' . $_SERVER['HTTP_HOST'] . $ogImagePath : '';

    // og:url / canonical desde el slug limpio: la URL real trae fbclid y
    // utm_* de cada clic de anuncio, y cada visitante declararía una
    // canónica distinta.
    $canonicalPath = !empty($producto['slug'])
        ? BASE_URL . '/producto/' . rawurlencode($producto['slug'])
        : $_SERVER['REQUEST_URI'];
    $ogUrl  = 'https://' . $_SERVER['HTTP_HOST'] . $canonicalPath;
    $ogDesc = mb_substr(strip_tags($heroSubtitle), 0, 200);
    ?>
    <link rel="canonical" href="<?= htmlspecialchars($ogUrl) ?>">
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
    <?php if (!es_entorno_local()): ?>
    <link rel="dns-prefetch" href="https://connect.facebook.net">
    <link rel="dns-prefetch" href="https://www.clarity.ms">
    <?php endif; ?>

    <!-- Fuentes: cargadas como link (no @import) para no bloquear el CSS principal -->
    <!-- Sólo Montserrat. Lora era una serif y hacía que los párrafos se
         leyeran como columna de periódico en vez de como interfaz de
         producto. Quitarla además ahorra una petición de fuente entera:
         menos peso en el webview de Facebook, que es por donde entra
         todo el tráfico. -->
    <!-- Oswald para los titulares, Montserrat para el resto.
         Oswald es condensada: mete más letra en el mismo ancho, y en un
         móvil eso es la diferencia entre un titular de dos líneas y uno
         de cuatro. Es lo que da el aire compacto de los titulares que
         funcionan en anuncios. Sólo se piden los dos pesos que se usan
         (600 y 700) para no cargar la familia entera. -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Oswald:wght@500;600;700&display=swap">

    <link rel="stylesheet" href="<?= asset_url('public/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('public/css/order-modal.css') ?>">

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
    <!-- modal-a11y.js ya no se carga aquí: su trampa de foco solo la usaba el modal de pedido -->
    <script src="<?= asset_url('public/js/main.js') ?>" defer></script>
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

            <!-- Señales de confianza. Vivían al pie del price-box, en una
                 pastilla fina de una sola línea donde los tres argumentos
                 competían por el mismo renglón. Suben al hero — que es donde
                 hacen falta, antes de que el comprador decida si sigue
                 bajando — y pasan a tarjetas con el mismo formato que los
                 beneficios de "por qué te encantará".
                 El icono va dentro de un círculo, en el sitio donde aquella
                 sección pone su número: aquí no son pasos ordenados, así que
                 numerarlos sería mentir sobre lo que son. -->
            <div class="hero-trust-row">
                <span class="hero-trust-item">
                    <span class="hero-trust-item__ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </span>
                    <span class="hero-trust-item__text"><?= htmlspecialchars($heroTrust1) ?></span>
                </span>
                <span class="hero-trust-item">
                    <span class="hero-trust-item__ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </span>
                    <span class="hero-trust-item__text"><?= htmlspecialchars($heroTrust2) ?></span>
                </span>
                <span class="hero-trust-item">
                    <span class="hero-trust-item__ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                    </span>
                    <span class="hero-trust-item__text"><?= htmlspecialchars($heroTrust3) ?></span>
                </span>
            </div>

        </div>

        <div class="hero-media">
            <?php if ($heroMediaType === 'video'): ?>
                <div class="caract-video-wrap" id="heroVideoWrap">
                    <video src="<?= htmlspecialchars($heroMediaPath) ?>"
                        <?php if (!empty($cfg['hero_poster_path'])): ?>poster="<?= htmlspecialchars($cfg['hero_poster_path']) ?>"<?php endif; ?>
                        muted loop playsinline
                        preload="metadata"
                        style="max-width:100%; border-radius:10px;"></video>
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
                    <span class="caract-vol-btn__txt">Toca para escuchar</span>
                    </button>
                </div>
            <?php else: ?>
                <img src="<?= htmlspecialchars($heroMediaPath) ?>"
                    alt="Imagen del producto"
                    fetchpriority="high"
                    decoding="async">
            <?php endif; ?>

            <!-- ✅ NUEVO: Badge flotante sobre la imagen -->
            <div class="hero-image-badge">
                <span class="hero-image-badge__ico" aria-hidden="true"><?= $micoStar ?></span>
                <?= $heroBadgeStars ?> · <?= $heroBadgeCustomers ?>
            </div>
        </div>
    </header>

    <?php if ($heroMediaType === 'video'): ?>
    <script>
    (function () {
        var wrap = document.getElementById('heroVideoWrap');
        if (!wrap) return;
        var video  = wrap.querySelector('video');
        var volBtn = wrap.querySelector('.caract-vol-btn');

        function markUnmuted() {
            if (volBtn) volBtn.classList.add('is-unmuted');
        }
        function markMuted() {
            if (volBtn) volBtn.classList.remove('is-unmuted');
        }


        /* El video SIEMPRE arranca silenciado. El sonido lo activa el
           usuario con el botón de volumen, nunca solo.
           Antes se intentaba autoplay con audio y, si el navegador lo
           bloqueaba, se desilenciaba en el primer toque de la página —
           incluido el primer scroll. Eso hacía que a alguien que abre el
           anuncio desde Facebook le sonara audio de golpe al deslizar,
           que es una de las formas más rápidas de perder la visita. */
        if (video) {
            video.muted = true;
            markMuted();
            video.play().catch(function () {});
        }
    })();
    </script>
    <?php endif; ?>

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
                <!-- Orden de anclaje: antes → ahora → cuánto ahorras.
                     El pill "Ahorras $X" iba primero, antes incluso del precio
                     tachado, así que anunciaba un descuento sobre una cifra que
                     el comprador todavía no había visto. El ahorro sólo
                     significa algo después de los dos precios que lo producen.
                     .price-box es flex en columna, así que este orden del
                     marcado es literalmente el orden en pantalla. -->
                <div class="price-label">Oferta exclusiva · Solo hoy</div>
                <?php if ($precio_regular > $precio_venta): ?>
                <div class="old">$<?= number_format($precio_regular, 0, ',', '.') ?></div>
                <?php endif; ?>
                <div class="new">$<?= number_format($precio_venta, 0, ',', '.') ?></div>
                <?php if ($ahorro > 0): ?>
                <div class="save">Ahorras $<?= number_format($ahorro, 0, ',', '.') ?></div>
                <?php endif; ?>

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

        <?php endif; $sections['price_box'] = ob_get_clean(); ?>

        <?php ob_start(); if ($showBenefits): ?>
        <!-- BENEFICIOS — tarjetas horizontales con foto por beneficio -->
        <section class="container benefits-section">
            <div class="benefit-cards-outer">
                <!-- El eyebrow sustituye a la línea dorada que había bajo el
                     título: da el mismo golpe de ritmo sin el aire de
                     cabecera de revista. -->
                <span class="section-eyebrow">Por qué lo vas a querer</span>
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

                // Colores que solo tienen 1 foto: se listan como miniaturas fijas
                // (funcionan como selector rápido de color, independiente del color activo)
                $singleImgColors = [];
                foreach ($colorVariants as $cIdx => $cv) {
                    if (count($cv['images']) === 1) {
                        $singleImgColors[] = [
                            'idx'  => $cIdx,
                            'img'  => $cv['images'][0],
                            'name' => $cv['name'],
                            'hex'  => $cv['hex'],
                        ];
                    }
                }
            } else {
                // Sin variantes: usa las fotos del editor (con fallback)
                $gallery = !empty($galleryPaths) ? $galleryPaths : [
                    BASE_URL . '/public/img/producto/uso-1.png',
                    BASE_URL . '/public/img/producto/uso-1.png',
                    BASE_URL . '/public/img/producto/uso-1.png',
                    BASE_URL . '/public/img/producto/uso-1.png',
                ];
                $mainImg   = $gallery[0] ?? '';
                $thumbImgs = array_slice($gallery, 1, 3);
            }
            ?>

            <?php if (!empty($colorVariants)): ?>
            <div class="gallery-color-pills" role="group" aria-label="Colores disponibles">
                <span class="gallery-color-pills__label">Color:</span>
                <div class="gallery-color-pills__row">
                    <?php foreach ($colorVariants as $cIdx => $cv): ?>
                    <button
                        type="button"
                        class="gallery-color-pill <?= $cIdx === 0 ? 'is-active' : '' ?>"
                        data-color-idx="<?= $cIdx ?>"
                        data-color-images="<?= htmlspecialchars(json_encode($cv['images'], JSON_UNESCAPED_UNICODE)) ?>"
                        aria-pressed="<?= $cIdx === 0 ? 'true' : 'false' ?>"
                        aria-label="<?= htmlspecialchars($cv['name']) ?>"
                        title="<?= htmlspecialchars($cv['name']) ?>"
                        style="--cv-color:<?= htmlspecialchars($cv['hex']) ?>">
                        <!-- El punto muestra el color real de la variante. Antes
                             era un radio button genérico: el comprador leía el
                             nombre pero no veía el color, teniendo el dato a mano. -->
                        <span class="gallery-color-pill__swatch" aria-hidden="true"></span>
                        <span class="gallery-color-pill__name"><?= htmlspecialchars($cv['name']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
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
                    <div class="product-gallery__thumbs" role="group" aria-label="Miniaturas del producto">
                        <?php foreach ($thumbImgs as $i => $src): ?>
                            <?php if (trim($src) === '') continue; ?>
                            <button
                                type="button"
                                class="product-gallery__thumb"
                                
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

            <?php if (!empty($singleImgColors)): ?>
            <div class="gallery-color-thumbs" role="group" aria-label="Otros colores disponibles">
                <?php foreach ($singleImgColors as $sc): ?>
                <button
                    type="button"
                    class="gallery-color-thumb <?= $sc['idx'] === 0 ? 'is-active' : '' ?>"
                    
                    data-color-idx="<?= $sc['idx'] ?>"
                    aria-label="Color <?= htmlspecialchars($sc['name']) ?>"
                    title="<?= htmlspecialchars($sc['name']) ?>"
                    style="--cv-color:<?= htmlspecialchars($sc['hex']) ?>">
                    <img src="<?= htmlspecialchars($sc['img']) ?>" alt="Color <?= htmlspecialchars($sc['name']) ?>" loading="lazy" decoding="async">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($colorVariants)): ?>
            <script>
            (function () {
                var pills   = document.querySelectorAll('.gallery-color-pill');
                var thumbs  = document.querySelectorAll('.gallery-color-thumb');
                var gallery = document.querySelector('[data-product-gallery]');
                if (!gallery || (!pills.length && !thumbs.length)) return;

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

                function selectColor(idx) {
                    var pill  = document.querySelector('.gallery-color-pill[data-color-idx="' + idx + '"]');
                    var thumb = document.querySelector('.gallery-color-thumb[data-color-idx="' + idx + '"]');
                    if (!pill) return;

                    pills.forEach(function (p) { p.classList.remove('is-active'); p.setAttribute('aria-pressed', 'false'); });
                    thumbs.forEach(function (t) { t.classList.remove('is-active'); });
                    pill.classList.add('is-active');
                    pill.setAttribute('aria-pressed', 'true');
                    if (thumb) thumb.classList.add('is-active');

                    var images = JSON.parse(pill.getAttribute('data-color-images') || '[]');
                    applyImages(images);
                }

                pills.forEach(function (pill) {
                    pill.addEventListener('click', function () {
                        selectColor(pill.getAttribute('data-color-idx'));
                    });
                });
                thumbs.forEach(function (thumb) {
                    thumb.addEventListener('click', function () {
                        selectColor(thumb.getAttribute('data-color-idx'));
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
                                <video muted loop playsinline preload="metadata">
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
                                <span class="caract-vol-btn__txt">Toca para escuchar</span>
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

                /* Los controles de video (tap para pausar, botón de volumen)
                   los maneja initVideoControls() en main.js, delegado en el
                   documento para todos los videos de la landing. */
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
                        <!-- Mismo envoltorio que el hero y el carrusel: sin él este
                             video se quedaba mudo y sin forma de pausarlo ni de
                             subirle el volumen. Los controles los maneja
                             initVideoControls() en main.js. -->
                        <div class="caract-video-wrap">
                            <video src="<?= htmlspecialchars($porqueMediaPath) ?>"
                                   muted loop playsinline preload="metadata"></video>
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
                            <span class="caract-vol-btn__txt">Toca para escuchar</span>
                            </button>
                        </div>
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
                                <circle cx="13" cy="13" r="13" fill="currentColor"/>
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
                                <circle cx="13" cy="13" r="13" fill="currentColor"/>
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
            <span class="section-eyebrow">Casos reales</span>
            <h2 class="section-title"><?= htmlspecialchars($testimoniosTitle) ?></h2>
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
                        <!-- Comillas tipográficas, no las rectas del teclado. Se
                             escriben como entidad y no como carácter literal: este
                             archivo ya arrastra historial de codificación y una
                             entidad no se puede romper en un import. -->
                        <p class="testimonios-ticker__text">&ldquo;<?= htmlspecialchars($t['text']) ?>&rdquo;</p>
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
                    <span class="section-eyebrow">Prueba real</span>
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
                            <p class="wa-ticker__text">&ldquo;<?= htmlspecialchars($waT['text']) ?>&rdquo;</p>
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
            $svgX     = '<svg width="18" height="18" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="13" cy="13" r="13" fill="currentColor"/><path d="M9 9l8 8M17 9l-8 8" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>';
            $svgCheck = '<svg width="18" height="18" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="13" cy="13" r="13" fill="currentColor"/><path d="M7.5 13.5l4 4 7-8" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
            <div class="comparison-cta section-cta">
                <!-- Sin .btn-cta-section este era el único CTA de la página que
                     en móvil salía en pastilla estrecha y una talla más grande
                     que los demás: rompía la columna al bajar. -->
                <a href="#form-pedido" class="btn-primary btn-cta-section">
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
                        <!-- h2 como el resto de títulos de sección: era el único
                             que bajaba a h3 y saltaba un nivel del esquema. -->
                        <h2><?= htmlspecialchars($garantiaTitle) ?></h2>
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
                        <div class="authority-stat__num"><span class="authority-stat__ico" aria-hidden="true"><?= $micoStar ?></span><?= htmlspecialchars($authorityRating) ?></div>
                        <div class="authority-stat__label">calificación promedio</div>
                    </div>
                    <div class="authority-stat">
                        <div class="authority-stat__num authority-stat__num--ico" aria-hidden="true"><?= $micoShield ?></div>
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
                <span><?= $micoTruck ?> Envío gratis</span>
                <span><?= $micoCard ?> Pago al recibir</span>
                <span><?= $micoSwap ?> Garantía de cambio</span>
            </div>
            <?php if ($urgencyStock <= 10): ?>
            <p class="offer-anchor__scarcity">
                <?= $micoAlert ?> Solo quedan <strong id="offerAnchorStock"><?= $urgencyStock ?></strong> unidades a este precio
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

        <?php
        $modalProductImg = !empty($producto['imagen_principal'])
            ? $producto['imagen_principal']
            : ($heroMediaType === 'imagen' && !empty($heroMediaPath) ? $heroMediaPath : BASE_URL . '/public/img/producto/uso-1.png');
        ?>
        <!-- ══════════════════════════════════════════════════════
             MODAL DE PEDIDO
        ══════════════════════════════════════════════════════════ -->
                <!-- El juego de iconos vive ahora al principio del archivo, junto al
             resto de la configuracion: lo usan tambien secciones que van
             muy por encima de esta. -->

        <!-- ══════════════════════════════════════════════════════
             FORMULARIO DE PEDIDO
             Vive aquí, en el flujo de la página, NO dentro del modal.
             Con JS el modal se lo lleva prestado y esta sección se oculta;
             si el JS falla (11% de sesiones), el formulario sigue visible
             y el POST nativo funciona: el servidor valida igual.
        ══════════════════════════════════════════════════════════ -->
        <section id="form-pedido" class="pedido-section" aria-label="Formulario de pedido">
            <!-- Panel: el formulario tiene que verse como una pieza aparte del
                 resto de la landing, no como una seccion mas de texto. -->
            <div class="pedido-panel">

                <!-- Impulso, no adorno: dice en que punto esta y cuanto falta -->
                <?php if (!empty($formKicker)): ?>
                <p class="pedido-kicker">
                    <span class="pedido-kicker__punto" aria-hidden="true"></span>
                    <?= htmlspecialchars($formKicker) ?>
                </p>
                <?php endif; ?>

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

                <?php /* Aquí vivían tres pastillas — Envío GRATIS · Pagas al recibirlo ·
                         Ahorras $X — y decían exactamente lo mismo que las tres promesas
                         de dos líneas más abajo y que la fila de confianza del pie del
                         formulario. En la zona del formulario "pagas al recibir" salía
                         seis veces y "gratis" tres antes de la primera casilla: media
                         pantalla de móvil convenciendo a alguien que ya bajó a comprar.
                         Se quedan las promesas, que son las que hablan del miedo real
                         ("si no llega, no pagas"). El ahorro sigue en la barra de oferta
                         y en el resumen final. */ ?>

                <!-- Título y subtítulo del formulario -->
                <?php if (!empty($formTitle)): ?>
                <h2 class="order-modal-form-title"><?= htmlspecialchars($formTitle) ?></h2>
                <?php endif; ?>
                <p class="order-modal-intro-text">
                    <?= htmlspecialchars($formSubtitle) ?>
                </p>

                <!-- Promesa de cero riesgo — el cierre real de una venta
                     contraentrega. Son las tres cosas que el comprador se
                     pregunta justo antes de dar sus datos. Editables desde
                     landing_config el día que se agreguen los campos. -->
                <?php
                $promesas = [
                    $cfg['promesa_1'] ?? 'No pagas <strong>nada</strong> ahora',
                    $cfg['promesa_2'] ?? 'Pagas cuando <strong>lo tengas en la mano</strong>',
                    $cfg['promesa_3'] ?? 'Si no llega, <strong>no pagas</strong>',
                ];
                ?>
                <ul class="promesa-riesgo">
                    <?php foreach ($promesas as $_p): ?>
                    <li class="promesa-riesgo__item">
                        <span class="promesa-riesgo__check" aria-hidden="true"><?= $micoCheck ?></span>
                        <span><?= $_p ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
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
                    <span class="stepper-node__label"><?= $micoBag ?> ¿Qué pides?</span>
                </div>
                <div class="stepper-connector" data-after="1"></div>
                <div class="stepper-node" data-step="2">
                    <div class="stepper-node__circle">
                        <span class="stepper-node__num">2</span>
                        <svg class="stepper-node__check" viewBox="0 0 14 14" fill="none"><polyline points="2 7 6 11 12 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="stepper-node__label"><?= $micoBox ?> ¿A dónde?</span>
                </div>
                <div class="stepper-connector" data-after="2"></div>
                <div class="stepper-node" data-step="3">
                    <div class="stepper-node__circle">
                        <span class="stepper-node__num">3</span>
                        <svg class="stepper-node__check" viewBox="0 0 14 14" fill="none"><polyline points="2 7 6 11 12 3" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <span class="stepper-node__label"><?= $micoUser ?> ¿Quién eres?</span>
                </div>
            </div>

            <!-- Los errores que devuelve el servidor se pintan aqui mismo, en el
                 contenedor que el JS ya usa para los suyos. Asi el aviso es el
                 mismo haya o no JavaScript, y un envio rechazado nunca vuelve
                 en silencio. -->
            <div id="stepperErrors" class="error" style="display:<?= $errores ? 'block' : 'none' ?>;" role="alert" aria-live="assertive">
                <?php if ($errores): ?>
                <ul>
                    <?php foreach ($errores as $_err): ?>
                    <li><?= htmlspecialchars($_err) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <div class="form-box">
                <!-- Con el pedido ya guardado se esconde el formulario y se deja
                     la pantalla de exito, que vive mas abajo dentro de este mismo
                     .form-box (por eso se oculta el <form> y no el contenedor).
                     Es lo mismo que hace main.js tras el envio por AJAX. -->
                <form id="formPedido" action="<?= BASE_URL ?>/Landing/enviarPedido" method="POST" novalidate<?= $success !== '' ? ' style="display:none;"' : '' ?>>
                    <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['id'] ?? 1) ?>">

                    <!-- ══════════════════════════════
                         PASO 1 — QUÉ QUIERE PEDIR

                         El orden es deliberado: qué pides → a dónde → quién
                         eres. Antes se pedía el WhatsApp de entrada, o sea el
                         dato más caro en el momento más barato: el comprador
                         todavía no había invertido nada en el pedido. Elegir
                         color es gratis, es un toque y compromete — a partir
                         de ahí el pedido ya se siente suyo. Y deja el teléfono
                         pegado al botón, justo después de ver el total.
                    ══════════════════════════════════ -->
                    <div class="form-step is-active" data-step="1">

                        <?php if ($hasColors): ?>
                        <!-- Con colores: pills visuales -->
                        <?php /* Sin subtítulo: el título ya pregunta, y debajo venía
                                 además "Elige el color:" — tres veces la misma
                                 instrucción seguida. La pregunta vive en el
                                 encabezado del bloque y nada más. */ ?>
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoPaint ?></div>
                            <h3 class="form-step__title">¿Cuál color te gusta?</h3>
                        </div>

                        <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">
                        <input type="hidden" id="cantidad_total" name="cantidad_total" value="1">

                        <?php
                        /* Las pastillas de color son <button> que sin JavaScript no hacen
                           nada, y el <select> que de verdad viaja en el POST estaba
                           escondido con pointer-events:none y aria-hidden. Resultado:
                           quien no tuviera JS no tenia NINGUNA forma de elegir color y el
                           servidor lo exige — el pedido se rechazaba con "Selecciona color
                           y cantidad en todas las filas" y no habia manera de arreglarlo.
                           Mismo callejon sin salida que ya se tapo en departamento/municipio
                           y en la direccion.
                           Ahora los controles nativos nacen VISIBLES y es initColorRow()
                           quien los esconde al montar las pastillas: se oculta lo que
                           sobra, nunca se revela lo imprescindible.
                           Se repintan ademas los colores que el comprador ya habia elegido:
                           un envio rechazado sin JS le borraba la seleccion. */
                        $oldColores = (isset($old['color_item']) && is_array($old['color_item'])) ? $old['color_item'] : [];
                        $oldCants   = (isset($old['qty_item'])   && is_array($old['qty_item']))   ? $old['qty_item']   : [];
                        if (!$oldColores) $oldColores = [''];
                        ?>
                        <div id="colorRowsWrap">
                            <?php foreach ($oldColores as $_i => $_colorSel):
                                $_colorSel = (string)$_colorSel;
                                $_cantSel  = (int)($oldCants[$_i] ?? 1);
                                if ($_cantSel < 1 || $_cantSel > 5) $_cantSel = 1;
                            ?>
                            <div class="color-row" data-row="<?= (int)$_i ?>">
                                <div class="color-pills-wrap" role="group" aria-label="Colores disponibles" style="display:none;">
                                    <?php foreach ($colores as $c): ?>
                                    <button type="button" class="color-pill<?= $c === $_colorSel ? ' is-selected' : '' ?>"
                                        data-color="<?= htmlspecialchars($c) ?>"
                                        aria-pressed="<?= $c === $_colorSel ? 'true' : 'false' ?>">
                                        <?= htmlspecialchars($c) ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Controles nativos: los unicos que funcionan sin JS.
                                     initColorRow() los esconde en cuanto monta las pastillas. -->
                                <div class="color-row__nativo">
                                    <label class="color-row__nativo-campo">
                                        <span>Color</span>
                                        <select name="color_item[]" class="color-item-sel select-lg">
                                            <option value="">— Escoge un color —</option>
                                            <?php foreach ($colores as $c): ?>
                                            <option value="<?= htmlspecialchars($c) ?>"<?= $c === $_colorSel ? ' selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="color-row__nativo-campo">
                                        <span>Cantidad</span>
                                        <!-- El tope es 5 por color, el mismo que aplica el servidor.
                                             Llegaba hasta 10 aqui, asi que se podia armar un pedido
                                             de 6 y perderlo al final con un "la cantidad por color
                                             debe estar entre 1 y 5" despues de llenar todo. -->
                                        <select name="qty_item[]" class="qty-item-sel select-lg">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>"<?= $i === $_cantSel ? ' selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </label>
                                </div>

                                <div class="color-row__qty-wrap" style="display:none;">
                                    <p class="color-row__qty-lbl">¿Cuántos de ese color?</p>
                                    <div class="qty-stepper qty-stepper--big">
                                        <button type="button" class="qty-btn qty-btn--big" data-action="minus" aria-label="Menos">−</button>
                                        <span class="qty-val-big" aria-live="polite"><?= $_cantSel ?></span>
                                        <button type="button" class="qty-btn qty-btn--big" data-action="plus" aria-label="Más">+</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
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

                        <div class="form-step__nav form-step__nav--end">
                            <button type="button" class="btn-step-next btn-next-lg" data-next="2">
                                Siguiente <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════
                         PASO 2 — A DÓNDE
                    ══════════════════════════════════ -->
                    <div class="form-step" data-step="2">
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoBox ?></div>
                            <h3 class="form-step__title">¿A dónde te lo enviamos?</h3>
                            <p class="form-step__sub">Envío gratis a todo el país</p>
                        </div>

                        <?php
                        /* Departamentos y municipios se pintan COMPLETOS desde PHP.
                           Antes llegaban por fetch de un JSON de 192 KB y los dos
                           selects nacían vacíos: si ese fetch no completaba — y en
                           el navegador de Facebook, de donde viene la pauta, basta
                           un error de sintaxis en cualquier .js para que nada corra —
                           los dos campos quedaban sin opciones. Como el servidor los
                           exige, el formulario era imposible de enviar y el pedido se
                           perdía sin que nadie se enterara.
                           Ahora el HTML ya trae todo: sin JS el comprador escoge de
                           una lista larga agrupada por departamento y el pedido sale.
                           Con JS, initDepartamentoMunicipio() deja visible solo el
                           grupo del departamento elegido, igual que antes. */
                        $ubicaciones = require __DIR__ . '/../../data/colombia.php';
                        $oldDep = (string)($old['departamento'] ?? '');
                        $oldMun = (string)($old['municipio'] ?? '');
                        ?>
                        <?php /* Los campos llevan sustantivo corto, no pregunta: la
                                 pregunta ya la hace el título del bloque ("¿A dónde te
                                 lo enviamos?") y debajo venían otras tres seguidas.
                                 Una columna de sustantivos se escanea de un vistazo;
                                 una de preguntas hay que leerla entera. */ ?>
                        <div class="form-group">
                            <label for="departamento" class="form-label-lg">Departamento</label>
                            <select id="departamento" name="departamento" required class="select-lg"
                                autocomplete="address-level1">
                                <option value="">— Escoge tu departamento —</option>
                                <?php foreach ($ubicaciones as $_dep => $_muns): ?>
                                <option value="<?= htmlspecialchars($_dep) ?>"<?= $_dep === $oldDep ? ' selected' : '' ?>><?= htmlspecialchars($_dep) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="municipio" class="form-label-lg">Ciudad o municipio</label>
                            <select id="municipio" name="municipio" required class="select-lg"
                                autocomplete="address-level2">
                                <?php /* El mismo texto que vuelve a escribir poblarMunicipios()
                                         en main.js: si no coinciden, el placeholder cambia solo
                                         por tocar el departamento. */ ?>
                                <option value=""><?= $oldDep !== '' ? '— Escoge tu municipio —' : 'Primero elige el departamento' ?></option>
                                <?php foreach ($ubicaciones as $_dep => $_muns): ?>
                                <optgroup label="<?= htmlspecialchars($_dep) ?>" data-dep="<?= htmlspecialchars($_dep) ?>">
                                    <?php foreach ($_muns as $_mun): ?>
                                    <option value="<?= htmlspecialchars($_mun) ?>"<?= ($_dep === $oldDep && $_mun === $oldMun) ? ' selected' : '' ?>><?= htmlspecialchars($_mun) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="deliveryETA" class="delivery-eta-badge" style="display:none;" aria-live="polite">
                            <?= $micoBox ?> Llega estimado el: <strong id="deliveryETADate"></strong>
                        </div>

                        <?php /* fieldset/legend y no <p>: asi el grupo de radios tiene
                                 nombre accesible de verdad. Con el <p> suelto, un lector
                                 de pantalla anunciaba las dos tarjetas sin decir nunca
                                 de que decision formaban parte. */ ?>
                        <fieldset class="form-group form-fieldset">
                            <legend class="form-label-lg">¿Cómo quieres recibirlo?</legend>
                            <div class="radio-group--cards radio-group--cards-lg">
                                <label class="radio-card radio-card--lg">
                                    <?php /* Domicilio viene marcado por defecto. Antes no habia
                                             ninguno marcado, pero la direccion SI se veia y SI era
                                             obligatoria: el comprador la llenaba, daba a confirmar
                                             y recibia "Selecciona como quieres recibir tu pedido"
                                             por algo que nunca supo que tenia que tocar. Es ademas
                                             lo que promete toda la landing: el mensajero llega a
                                             tu puerta. Recoger en oficina sigue a un toque. */ ?>
                                    <input type="radio" name="tipo_entrega" value="domicilio"
                                        <?= (empty($old['tipo_entrega']) || $old['tipo_entrega'] === 'domicilio') ? 'checked' : '' ?>>
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
                        </fieldset>

                        <!-- Direccion y nota nacen VISIBLES. Estaban en display:none
                             esperando a que el JS las mostrara al elegir domicilio, asi
                             que sin JavaScript el comprador no tenia donde escribir su
                             direccion — y el servidor la exige para envio a domicilio:
                             otro callejon sin salida. Ahora initTipoEntrega() las
                             esconde cuando se elige recoger en oficina, que es la
                             direccion correcta de la mejora: se oculta lo que sobra,
                             no se revela lo imprescindible. -->
                        <div class="form-group" id="grupo-direccion">
                            <label for="direccion" class="form-label-lg">Dirección</label>
                            <input type="text" id="direccion" name="direccion" class="input-lg"
                                placeholder="Ej: Calle 5 # 10-20, frente a la escuela"
                                autocomplete="street-address"
                                value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
                            <p class="field-hint">Escríbela bien para que el mensajero llegue sin problema</p>
                        </div>

                        <div class="form-group" id="grupo-nota-entrega">
                            <label for="nota_entrega" class="form-label-lg">
                                Indicaciones para el mensajero
                                <span class="tag-optional"> — opcional</span>
                            </label>
                            <textarea id="nota_entrega" name="nota_entrega" rows="2"
                                placeholder="Ej: Portón verde · Solo en las tardes"
                                style="resize:vertical;"><?= htmlspecialchars($old['nota_entrega'] ?? '') ?></textarea>
                        </div>

                        <div class="form-step__nav">
                            <button type="button" class="btn-step-prev" data-prev="1">← Atrás</button>
                            <button type="button" class="btn-step-next btn-next-lg" data-next="3">
                                Siguiente <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- ══════════════════════════════
                         PASO 3 — QUIÉN ERES

                         Va al final a propósito: el teléfono es el dato que
                         más cuesta dar, y aquí llega después de que el
                         comprador ya eligió color y dirección y ya vio el
                         total. Es el punto de máxima intención de toda la
                         página.
                    ══════════════════════════════════ -->
                    <div class="form-step" data-step="3">
                        <div class="form-step__head">
                            <div class="step-emoji" aria-hidden="true"><?= $micoUser ?></div>
                            <h3 class="form-step__title">¿A nombre de quién va el pedido?</h3>
                            <p class="form-step__sub">Te escribimos por WhatsApp para confirmarlo</p>
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
                            <?php /* La pista dice el PORQUÉ, no el formato. La regla —
                                     10 dígitos, empieza en 3 — ya la enseña la validación
                                     en vivo de más abajo en el momento en que hace falta
                                     ("Faltan 3 dígitos", "Debe empezar en 3"), así que
                                     aquí sobraba y hacía la pista de dos renglones. */ ?>
                            <p class="tel-hint" id="telHint">Por aquí te avisamos cuando salga tu pedido</p>
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
                                <?php /* El resumen decía cantidad, descuento, envío y total,
                                         pero nunca QUÉ COLOR. En un producto de varios colores
                                         la confirmación tiene que nombrar lo que va en la caja:
                                         es lo único que el comprador no puede verificar
                                         después. Lo llena pricing-summary.js. */ ?>
                                <div class="order-summary__row" id="summaryColorRow" style="display:none;">
                                    <span>Color</span>
                                    <strong id="summaryColor"></strong>
                                </div>
                                <div class="order-summary__row" id="summaryDiscountRow" style="display:none;">
                                    <span>Descuento</span>
                                    <strong id="summaryDiscount">$0</strong>
                                </div>
                                <div class="order-summary__row" id="summarySaveRow" style="display:none;">
                                    <span>Ahorras</span>
                                    <strong id="summarySave" style="color:var(--positivo);"></strong>
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

                        <?php /* Solo lo que no se ha dicho ya arriba. "Pagas al recibirlo"
                                 y "Envío gratis" salían aquí por cuarta y quinta vez. */ ?>
                        <div class="form-trust-row">
                            <span><?= $micoLock ?> Datos seguros</span>
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
                            <?php /* width= fijo al ratio real de cada logo: sin el, el navegador no
                                     sabe cuanto sitio reservarle a una imagen lazy antes de cargarla
                                     y la caja nace en 0px de ancho — la tira de transportadoras se ve
                                     vacia un instante y salta al llegar cada logo. */ ?>
                            <?php foreach ([1,2] as $_dup): ?>
                            <img src="<?= BASE_URL ?>/public/img/transportadoras/interrapidisimo.png" alt="Interrapidísimo" width="127" height="26" loading="lazy" decoding="async">
                            <img src="<?= BASE_URL ?>/public/img/transportadoras/envia.png"           alt="Envía"           width="128" height="26" loading="lazy" decoding="async">
                            <img src="<?= BASE_URL ?>/public/img/transportadoras/coordinadora.png"    alt="Coordinadora"    width="85"  height="26" loading="lazy" decoding="async">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- PANTALLA DE ÉXITO -->
                <div id="stepperSuccess" style="display:<?= $success !== '' ? 'block' : 'none' ?>;">
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
                                stroke="var(--positivo)" stroke-width="2.5"
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
                            <?= $micoShare ?> Recomendar a un amigo
                        </a>
                    </div>
                </div>
                </div><!-- /.form-box -->
                </div><!-- /.order-modal-body -->
            </div><!-- /.pedido-panel -->
        </section>

        <script>
        /* ── CTA → formulario: salto instantáneo ──────────────────
           Los CTAs son anclas nativas <a href="#form-pedido">, así que
           funcionan aunque no haya JavaScript. Esto solo mejora el salto:
           el scroll suave del CSS recorre ~1.900px en un par de segundos
           y cualquier toque de la pantalla lo cancela a mitad de camino,
           dejando al comprador en tierra de nadie. Instantáneo es más
           rápido y no se puede interrumpir. */
        (function () {
            var seccion = document.getElementById('form-pedido');
            if (!seccion) return;

            document.addEventListener('click', function (e) {
                var cta = e.target.closest ? e.target.closest('a[href="#form-pedido"]') : null;
                if (!cta) return;
                e.preventDefault();

                /* scrollIntoView con behavior:'auto' NO es instantaneo: hereda el
                   scroll-behavior:smooth que style.css pone en <html>. Se apaga
                   un instante para que el salto sea inmediato y no se pueda
                   cancelar con un toque a mitad de camino. */
                var raiz = document.documentElement;
                var previo = raiz.style.scrollBehavior;
                raiz.style.scrollBehavior = 'auto';

                /* La barra de anuncios está fija arriba (65px): sin descontarla
                   el salto deja la foto y el precio del producto tapados. Se
                   mide en vivo porque su alto depende del texto y del tema. */
                var fija = document.querySelector('.announcement-bar');
                var margen = 0;
                if (fija) {
                    var cs = getComputedStyle(fija);
                    if (cs.position === 'fixed' && cs.display !== 'none') {
                        margen = Math.round(fija.getBoundingClientRect().height) + 8;
                    }
                }
                seccion.style.scrollMarginTop = margen + 'px';
                seccion.scrollIntoView({ block: 'start' });
                raiz.style.scrollBehavior = previo;

                if (history.replaceState) history.replaceState(null, '', '#form-pedido');
            });
        })();
        </script>


        <script>
        /* ── Stepper — 3 pasos siempre ───────────────────────────
           Sin sintaxis ES2020 (?. y ??) a proposito. Este bloque va inline en
           la pagina y no pasa por ningun build: en un WebView viejo — el
           navegador de Facebook, que es por donde entra la pauta — el
           encadenamiento opcional no se degrada, tira un error de parseo que
           se lleva por delante TODO el bloque, incluidos los colores y la
           cantidad. Se escribe en sintaxis que cualquier motor entiende. */
        (function () {
            var form = document.getElementById('formPedido');
            if (!form) return;

            /* El formulario se muestra de corrido, en un solo scroll: los 3
               pasos visibles y sin navegación entre ellos. El paso a paso
               tenía sentido dentro del modal, con espacio contado; en la
               página solo escondía cuánto faltaba y añadía dos toques.
               Para recuperarlo basta con volver a poner la clase js-pasos
               en .order-modal-body (el CSS y el resto del script siguen ahí). */

            var current = 1;
            var errBox    = document.getElementById('stepperErrors');
            var indicator = document.getElementById('stepperIndicator');

            function showErrors(errs) {
                if (!errBox) return;
                if (!errs.length) { errBox.style.display = 'none'; errBox.innerHTML = ''; return; }
                var ul = document.createElement('ul');
                errs.forEach(function (e) {
                    var li = document.createElement('li');
                    li.textContent = e;
                    ul.appendChild(li);
                });
                errBox.innerHTML = '';
                errBox.appendChild(ul);
                errBox.style.display = 'block';
                errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            function valorDe(sel) {
                var el = form.querySelector(sel);
                return (el && el.value) ? el.value.trim() : '';
            }

            /* Sigue el orden nuevo del formulario: 1 que pides, 2 a donde,
               3 quien eres. Solo se usa si se vuelve a encender .js-pasos. */
            function validateStep(n) {
                var errs = [];
                if (n === 1) {
                    var wrap = document.getElementById('colorRowsWrap');
                    if (wrap) {
                        var sels = wrap.querySelectorAll('.color-item-sel');
                        var alguno = false;
                        Array.prototype.forEach.call(sels, function (s) { if (s.value !== '') alguno = true; });
                        if (!alguno) errs.push('Toca el color que quieres pedir.');
                    }
                } else if (n === 2) {
                    if (!valorDe('#departamento')) errs.push('Selecciona un departamento.');
                    if (!valorDe('#municipio'))    errs.push('Selecciona un municipio.');
                    var entrega = form.querySelector('input[name="tipo_entrega"]:checked');
                    if (entrega && entrega.value === 'domicilio' && !valorDe('#direccion')) {
                        errs.push('La dirección es obligatoria para envío a domicilio.');
                    }
                } else if (n === 3) {
                    if (!valorDe('#nombre'))    errs.push('El nombre es obligatorio.');
                    if (!valorDe('#apellidos')) errs.push('El apellido es obligatorio.');
                    var tv = valorDe('#telefono');
                    if (!tv)                       errs.push('El número de WhatsApp es obligatorio.');
                    else if (!/^3\d{9}$/.test(tv)) errs.push('El número debe tener 10 dígitos y empezar en 3.');
                }
                return errs;
            }

            var progressFill   = document.getElementById('modalProgressFill');
            var floatingTotal  = document.getElementById('modalFloatingTotal');
            var floatingAmt    = document.getElementById('modalFloatingTotalAmt');
            var PROGRESS_MAP   = { 1: '10%', 2: '48%', 3: '82%' };

            function updateIndicator(n) {
                if (!indicator) return;
                Array.prototype.forEach.call(indicator.querySelectorAll('.stepper-node'), function (el) {
                    var s = parseInt(el.dataset.step, 10);
                    el.classList.toggle('is-active', s === n);
                    el.classList.toggle('is-done', s < n);
                });
                Array.prototype.forEach.call(indicator.querySelectorAll('.stepper-connector'), function (el) {
                    el.classList.toggle('is-done', parseInt(el.dataset.after, 10) < n);
                });
                if (progressFill) progressFill.style.width = PROGRESS_MAP[n] || '10%';
                if (floatingTotal) floatingTotal.classList.toggle('is-hidden', n === 3);
            }

            /* El precio lo calcula pricing-summary.js y lo anuncia con
               'landing:precio' ya formateado. Aqui solo se reparte a los
               sitios que lo muestran. Antes esto leia el textContent del
               resumen dentro de un setTimeout, confiando en que el otro
               script hubiera escrito primero. */
            function repartirTotal(texto) {
                if (!texto) return;

                if (floatingAmt) floatingAmt.textContent = texto;

                var barPrice = document.getElementById('modalBarPrice');
                if (barPrice) barPrice.textContent = texto;

                var submitText = document.getElementById('btnSubmitText');
                if (!submitText) return;

                /* El span lleva un SVG delante del texto: se cambian solo los
                   nodos de texto para no perder el icono. */
                var svgEl = submitText.querySelector('svg');
                var frase = ' Confirmar mi pedido — pago ' + texto + ' al recibirlo';
                if (svgEl) {
                    Array.prototype.slice.call(submitText.childNodes).forEach(function (n) {
                        if (n.nodeType === 3) n.parentNode.removeChild(n);
                    });
                    submitText.appendChild(document.createTextNode(frase));
                } else {
                    submitText.textContent = '✓' + frase;
                }
            }

            document.addEventListener('landing:precio', function (e) {
                repartirTotal(e.detail && e.detail.texto);
            });

            function recalcular() {
                document.dispatchEvent(new Event('landing:recalc'));
            }

            function goTo(n) {
                Array.prototype.forEach.call(form.querySelectorAll('.form-step'), function (el) {
                    el.classList.toggle('is-active', parseInt(el.dataset.step, 10) === n);
                });
                updateIndicator(n);
                current = n;
                var scroll = document.getElementById('orderModalScroll');
                if (scroll) scroll.scrollTo({ top: 0, behavior: 'smooth' });
                requestAnimationFrame(function () {
                    var step = form.querySelector('.form-step[data-step="' + n + '"]');
                    var first = step ? step.querySelector('input:not([type=hidden]):not([type=radio]), select') : null;
                    if (first) first.focus();
                });
            }

            Array.prototype.forEach.call(form.querySelectorAll('.btn-step-next'), function (btn) {
                btn.addEventListener('click', function () {
                    var errs = validateStep(current);
                    if (errs.length) { showErrors(errs); return; }
                    showErrors([]);
                    goTo(parseInt(btn.dataset.next, 10));
                });
            });
            Array.prototype.forEach.call(form.querySelectorAll('.btn-step-prev'), function (btn) {
                btn.addEventListener('click', function () { showErrors([]); goTo(parseInt(btn.dataset.prev, 10)); });
            });

            /* ── Color pills (paso 2 con colores) ─────────────── */
            var colorRowsWrap = document.getElementById('colorRowsWrap');

            function updateColorSummary() {
                var sumEl = document.getElementById('colorSelSummary');
                var txtEl = document.getElementById('colorSelText');
                if (!sumEl || !txtEl || !colorRowsWrap) return;
                var parts = [];
                Array.prototype.forEach.call(colorRowsWrap.querySelectorAll('.color-row'), function (row) {
                    var cSel = row.querySelector('.color-item-sel');
                    var qSel = row.querySelector('.qty-item-sel');
                    if (cSel && cSel.value) parts.push(cSel.value + ' ×' + ((qSel && qSel.value) || '1'));
                });
                txtEl.textContent = parts.join(' · ');
                sumEl.style.display = parts.length ? 'flex' : 'none';
            }

            /* Tope por color: el mismo 5 que aplica el servidor. */
            var MAX_POR_COLOR = 5;

            function initColorRow(row) {
                var pills    = row.querySelectorAll('.color-pill');
                var qtyWrap  = row.querySelector('.color-row__qty-wrap');
                var qtyValEl = row.querySelector('.qty-val-big');
                var cSel     = row.querySelector('.color-item-sel');
                var qSel     = row.querySelector('.qty-item-sel');

                /* Los <select> nativos vienen visibles del servidor para que el
                   pedido se pueda hacer sin JavaScript. Si este codigo corre, ya
                   hay JS: mandan las pastillas y los selects pasan a ser el
                   transporte del valor, no la interfaz. */
                var nativo    = row.querySelector('.color-row__nativo');
                var pillsWrap = row.querySelector('.color-pills-wrap');
                if (nativo)    nativo.style.display = 'none';
                if (pillsWrap) pillsWrap.style.display = '';

                Array.prototype.forEach.call(pills, function (pill) {
                    pill.addEventListener('click', function () {
                        Array.prototype.forEach.call(pills, function (p) {
                            p.classList.remove('is-selected');
                            p.setAttribute('aria-pressed', 'false');
                        });
                        pill.classList.add('is-selected');
                        pill.setAttribute('aria-pressed', 'true');
                        if (cSel) cSel.value = pill.dataset.color;
                        if (qtyWrap) qtyWrap.style.display = '';
                        var addBtn = document.getElementById('addColorRowBtn');
                        if (addBtn) addBtn.style.display = '';
                        updateColorSummary();
                        recalcular();
                    });
                });

                /* Color que ya venia elegido del servidor (envio rechazado sin
                   fetch): la fila nace con la cantidad y el boton de anadir a la
                   vista, igual que si lo acabara de tocar. */
                if (cSel && cSel.value) {
                    if (qtyWrap) qtyWrap.style.display = '';
                    var addBtnIni = document.getElementById('addColorRowBtn');
                    if (addBtnIni) addBtnIni.style.display = '';
                }

                var btnMinus = row.querySelector('.qty-btn[data-action="minus"]');
                var btnPlus  = row.querySelector('.qty-btn[data-action="plus"]');
                if (btnMinus && btnPlus && qSel) {
                    var syncQty = function () {
                        var v = parseInt(qSel.value, 10);
                        if (qtyValEl) qtyValEl.textContent = v;
                        btnMinus.disabled = v <= 1;
                        btnPlus.disabled  = v >= MAX_POR_COLOR;
                    };
                    btnMinus.addEventListener('click', function () {
                        var v = parseInt(qSel.value, 10);
                        if (v > 1) { qSel.value = v - 1; syncQty(); updateColorSummary(); recalcular(); }
                    });
                    btnPlus.addEventListener('click', function () {
                        var v = parseInt(qSel.value, 10);
                        if (v < MAX_POR_COLOR) { qSel.value = v + 1; syncQty(); updateColorSummary(); recalcular(); }
                    });
                    syncQty();
                }

                var removeBtn = row.querySelector('.btn-remove-color-row');
                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        row.parentNode.removeChild(row);
                        updateColorSummary();
                        recalcular();
                    });
                }
            }

            if (colorRowsWrap) {
                Array.prototype.forEach.call(colorRowsWrap.querySelectorAll('.color-row'), initColorRow);
                updateColorSummary();

                var addBtn = document.getElementById('addColorRowBtn');
                if (addBtn) addBtn.addEventListener('click', function () {
                    var tmpl = colorRowsWrap.querySelector('.color-row');
                    if (!tmpl) return;
                    var newRow = tmpl.cloneNode(true);
                    Array.prototype.forEach.call(newRow.querySelectorAll('.color-pill'), function (p) {
                        p.classList.remove('is-selected');
                        p.setAttribute('aria-pressed', 'false');
                    });
                    var nCSel = newRow.querySelector('.color-item-sel');
                    var nQSel = newRow.querySelector('.qty-item-sel');
                    var nWrap = newRow.querySelector('.color-row__qty-wrap');
                    var nVal  = newRow.querySelector('.qty-val-big');
                    if (nCSel) nCSel.value = '';
                    if (nQSel) nQSel.value = '1';
                    if (nWrap) nWrap.style.display = 'none';
                    if (nVal)  nVal.textContent = '1';
                    var rem = document.createElement('button');
                    rem.type = 'button'; rem.className = 'btn-remove-color-row'; rem.textContent = '✕ Quitar';
                    newRow.appendChild(rem);
                    colorRowsWrap.appendChild(newRow);
                    initColorRow(newRow);
                    newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }

            /* ── Cantidad sin colores (paso 2) ─────────────────── */
            var qMinus   = document.getElementById('qtyMinus');
            var qPlus    = document.getElementById('qtyPlus');
            var qDisplay = document.getElementById('qtyDisplay');
            var qInput   = document.getElementById('cantidad_total');
            if (qMinus && qPlus && qDisplay && qInput) {
                var qty = parseInt(qInput.value, 10) || 1;
                var renderQty = function () {
                    qDisplay.textContent = qty;
                    qInput.value = qty;
                    qMinus.disabled = qty <= 1;
                    qPlus.disabled  = qty >= 10;
                    /* El resto — insignia, tira de precio, resumen y boton —
                       lo escribe pricing-summary.js al recalcular. */
                    recalcular();
                };
                renderQty();
                qMinus.addEventListener('click', function () { if (qty > 1)  { qty--; renderQty(); } });
                qPlus.addEventListener('click',  function () { if (qty < 10) { qty++; renderQty(); } });
            }

            /* ── Validación en tiempo real del teléfono ─────────── */
            (function () {
                var tel  = form.querySelector('#telefono');
                var hint = document.getElementById('telHint');
                if (!tel || !hint) return;
                tel.addEventListener('input', function () {
                    var v = tel.value.trim();
                    if (!v) {
                        tel.classList.remove('tel-valid', 'tel-invalid');
                        hint.className = 'tel-hint';
                        hint.textContent = 'Por aquí te avisamos cuando salga tu pedido';
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
                        var faltan = 10 - v.length;
                        if (v.length < 10) {
                            hint.textContent = 'Faltan ' + faltan + ' dígito' + (faltan !== 1 ? 's' : '');
                        } else if (v.charAt(0) !== '3') {
                            hint.textContent = 'Debe empezar en 3';
                        } else {
                            hint.textContent = 'Revisa el número';
                        }
                    }
                });
            })();

            /* ── Fallback para navegadores sin :has() (Facebook IAB) ─── */
            (function () {
                var radios = form.querySelectorAll('input[name="tipo_entrega"]');
                if (!radios.length) return;
                function syncCards() {
                    Array.prototype.forEach.call(radios, function (r) {
                        var card = r.closest('.radio-card--lg');
                        if (!card) return;
                        if (r.checked) {
                            card.classList.add('is-checked');
                        } else {
                            card.classList.remove('is-checked');
                        }
                    });
                }
                Array.prototype.forEach.call(radios, function (r) { r.addEventListener('change', syncCards); });
                syncCards();
            })();
        })();
        </script>

    </main>



    <?php if ($showFooter): ?>
    <footer class="footer-text">
        <?= htmlspecialchars($footerText) ?>
    </footer>
    <?php endif; ?>


    <!-- CTA sticky para móviles -->
    <?php if ($showCtaSticky): ?>
    <!-- Espaciador: solo si la barra existe; antes reservaba 88px aunque no hubiera barra -->
    <div class="sticky-spacer" aria-hidden="true"></div>
    <aside class="cta-sticky-mobile" aria-label="Comprar ahora">
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
    </aside>
    <?php endif; ?>

    <?php
    $success        = $success ?? '';
    $successPedido  = $success_pedido ?? null;
    $precioProducto = (float)($producto['precio_venta'] ?? 0);
    $nombreProducto = $producto['nombre'] ?? 'Producto';
    $pixelId        = $val('pixel_id', fb_pixel_id());
    $tiktokPixelId  = $val('tiktok_pixel_id', tiktok_pixel_id());
    ?>
    <script>
        window.landingSuccess = <?= json_encode($success,        JSON_UNESCAPED_UNICODE) ?>;
        window.landingSuccessPedido = <?= json_encode($successPedido) ?>;
        window.landingProductName = <?= json_encode($nombreProducto, JSON_UNESCAPED_UNICODE) ?>;
        window.landingProductPrice = <?= json_encode($precioProducto) ?>;
        window.landingProductId = <?= (int)($producto['id'] ?? 0) ?>;
        window.landingTrackUrl = <?= json_encode(BASE_URL . '/Landing/track') ?>;
        window.landingPixelId = <?= json_encode($pixelId) ?>;
        window.landingTiktokPixelId = <?= json_encode($tiktokPixelId) ?>;
    </script>

    <!-- Analítica propia del embudo (ver public/js/landing-track.js).
         A diferencia de Clarity y el Pixel, esta SÍ corre en local: guarda
         la visita marcada como entorno "local" y el panel filtra producción
         por defecto, así se puede probar el tracking sin ensuciar los datos. -->
    <script src="<?= asset_url('public/js/landing-track.js') ?>" defer></script>

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

    <script src="<?= asset_url('public/js/pricing-summary.js') ?>" defer></script>
    <!-- El envio del pedido va aparte y no dentro de main.js: es lo unico que
         no puede fallar, y alli compartia archivo con carruseles y videos. -->
    <script src="<?= asset_url('public/js/order-submit.js') ?>" defer></script>
    <script src="<?= asset_url('public/js/funcionesLandin.js') ?>" defer></script>

    <!-- Botón WhatsApp flotante -->
    <?php if ($showWhatsappBtn): ?>
    <aside aria-label="Contacto directo">
    <a href="https://wa.me/<?= urlencode($waPhone) ?>?text=Hola%2C%20me%20interesa%20el%20producto%20y%20tengo%20una%20consulta."
       class="wa-float-btn" target="_blank" rel="noopener" aria-label="Consultar por WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.126.557 4.116 1.526 5.845L.057 23.998l6.304-1.658A11.954 11.954 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.793 9.793 0 01-4.997-1.367l-.356-.212-3.745.985.993-3.644-.232-.373A9.79 9.79 0 012.182 12C2.182 6.57 6.57 2.182 12 2.182S21.818 6.57 21.818 12 17.43 21.818 12 21.818z"/>
        </svg>
    </a>
    </aside>
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
            <div class="exit-popup__badge"><span class="exit-popup__badge-ico" aria-hidden="true"><?= $micoFlame ?></span> ¡Espera un momento!</div>
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
                <?= $micoCheck ?> Quiero aprovecharlo ahora
            </a>
            <button class="exit-popup__dismiss" id="exitPopupDismiss">
                No gracias, prefiero perder el descuento
            </button>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!es_entorno_local()): // en local no se cargan: ensuciaban Clarity y el Pixel con pruebas ?>
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
        fbq('init',<?= json_encode($pixelId) ?>);
        fbq('track','PageView');
        fbq('track','ViewContent',{
            content_name: <?= json_encode($producto['nombre'] ?? 'Producto') ?>,
            content_ids:  [<?= json_encode((string)($producto['id'] ?? '')) ?>],
            content_type: 'product',
            value:        <?= json_encode((float)($producto['precio_venta'] ?? 0)) ?>,
            currency:     'COP'
        });

        // Red de seguridad: si el pedido se guardó por el POST nativo (sin
        // fetch, sin JS en el momento del envío), order-submit.js nunca llegó
        // a disparar Lead/Purchase. Esta página sí carga con JS, así que los
        // dispara aquí, leyendo lo que el servidor dejó en la sesión.
        if (window.landingSuccess && window.landingSuccessPedido) {
            (function () {
                var sp = window.landingSuccessPedido;
                var cantidad = sp.cantidad_total || 1;
                var productId = <?= json_encode((string)($producto['id'] ?? '')) ?>;
                var nombreProd = <?= json_encode($producto['nombre'] ?? 'Producto') ?>;

                fbq('track', 'Lead', {
                    value: sp.precio_total || 0,
                    currency: 'COP',
                    content_name: nombreProd
                });
                fbq('track', 'Purchase', {
                    value: sp.precio_total || 0,
                    currency: 'COP',
                    content_name: nombreProd,
                    content_ids: [productId],
                    content_type: 'product',
                    num_items: cantidad,
                    contents: [{
                        id: productId,
                        quantity: cantidad,
                        item_price: cantidad ? (sp.precio_total / cantidad) : (sp.precio_total || 0)
                    }]
                }, { eventID: 'pedido_' + sp.pedido_id });
            })();
        }
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
             src="https://www.facebook.com/tr?id=<?= urlencode($pixelId) ?>&ev=PageView&noscript=1">
    </noscript>

    <!-- TikTok Pixel -->
    <?php if ($tiktokPixelId !== ''): ?>
    <script>
        !function (w, d, t) {
            w.TiktokAnalyticsObject = t; var ttq = w[t] = w[t] || []; ttq.methods = ["page", "track", "identify", "instances", "debug", "on", "off", "once", "ready", "alias", "group", "enableCookie", "disableCookie", "holdConsent", "revokeConsent", "grantConsent"], ttq.setAndDefer = function (t, e) { t[e] = function () { t.push([e].concat(Array.prototype.slice.call(arguments, 0))) } }; for (var i = 0; i < ttq.methods.length; i++) ttq.setAndDefer(ttq, ttq.methods[i]); ttq.instance = function (t) { for (var e = ttq._i[t] || [], n = 0; n < ttq.methods.length; n++) ttq.setAndDefer(e, ttq.methods[n]); return e }, ttq.load = function (e, n) { var r = "https://analytics.tiktok.com/i18n/pixel/events.js", o = n && n.partner; ttq._i = ttq._i || {}, ttq._i[e] = [], ttq._i[e]._u = r, ttq._t = ttq._t || {}, ttq._t[e] = +new Date, ttq._o = ttq._o || {}, ttq._o[e] = n || {}; n = document.createElement("script"); n.type = "text/javascript", n.async = !0, n.src = r + "?sdkid=" + e + "&lib=" + t; e = document.getElementsByTagName("script")[0]; e.parentNode.insertBefore(n, e) };

            ttq.load(<?= json_encode($tiktokPixelId) ?>);
            ttq.page();
        }(window, document, 'ttq');

        ttq.track('ViewContent', {
            contents: [{
                content_id:   <?= json_encode((string)($producto['id'] ?? '')) ?>,
                content_type: 'product',
                content_name: <?= json_encode($producto['nombre'] ?? 'Producto') ?>
            }],
            value:    <?= json_encode((float)($producto['precio_venta'] ?? 0)) ?>,
            currency: 'COP'
        });

        // Misma red de seguridad que Facebook Pixel arriba: pedido guardado
        // por el POST nativo (sin fetch), esta página sí carga con JS.
        if (window.landingSuccess && window.landingSuccessPedido) {
            (function () {
                var sp = window.landingSuccessPedido;
                var cantidad = sp.cantidad_total || 1;
                var productId = <?= json_encode((string)($producto['id'] ?? '')) ?>;
                var nombreProd = <?= json_encode($producto['nombre'] ?? 'Producto') ?>;
                var valorUnit = cantidad ? (sp.precio_total / cantidad) : (sp.precio_total || 0);

                ttq.track('SubmitForm', {
                    contents: [{ content_id: productId, content_type: 'product', content_name: nombreProd }],
                    value: sp.precio_total || 0,
                    currency: 'COP'
                });
                ttq.track('CompletePayment', {
                    contents: [{
                        content_id:   productId,
                        content_type: 'product',
                        content_name: nombreProd,
                        quantity:     cantidad,
                        price:        valorUnit
                    }],
                    value:    sp.precio_total || 0,
                    currency: 'COP'
                }, { event_id: 'pedido_' + sp.pedido_id });
            })();
        }
    </script>
    <?php endif; // fin bloque TikTok Pixel ?>

    <!-- Microsoft Clarity -->
    <?php $clarityId = $val('clarity_id', 'wm68pleap5'); ?>
    <?php if ($clarityId !== ''): ?>
    <script>
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window,document,"clarity","script","<?= htmlspecialchars($clarityId) ?>");
    </script>
    <?php endif; // fin bloque Clarity ?>
    <script src="<?= asset_url('public/js/clarity-tags.js') ?>"></script>
    <?php endif; // fin analytics solo-produccion ?>

</body>

</html>
