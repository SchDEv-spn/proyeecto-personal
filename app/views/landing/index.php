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

// ===== FOOTER =====
$footerText = $cfg['footer_text'] ?? ('© ' . date('Y') . ' Tu Marca. Todos los derechos reservados.');

// ===== CTAs dinámicas =====
$ctaBenefitsText       = $cfg['cta_benefits_text']
    ?? 'Si estos beneficios encajan contigo, haz tu pedido ahora y asegura el precio de hoy.';
$ctaBenefitsButton     = $cfg['cta_benefits_button'] ?? 'Quiero aprovechar la oferta';

$ctaGalleryText        = $cfg['cta_gallery_text']
    ?? 'Todo lo que ves en las fotos es exactamente lo que recibirás en casa.';
$ctaGalleryButton      = $cfg['cta_gallery_button'] ?? 'Lo quiero igual que en las fotos';

$ctaPorqueText         = $cfg['cta_porque_text']
    ?? 'Si quieres sentir estos mismos resultados, haz tu pedido en menos de 1 minuto.';
$ctaPorqueButton       = $cfg['cta_porque_button'] ?? 'Quiero sentir ese cambio';

$ctaTestimonialsText   = $cfg['cta_testimonials_text']
    ?? 'Cada día más personas reciben su pedido y quedan igual de felices que ellos.';
$ctaTestimonialsButton = $cfg['cta_testimonials_button'] ?? 'Quiero ser el próximo en recibirlo';

$ctaFaqText            = $cfg['cta_faq_text']
    ?? 'Si ya resolviste tus dudas, el siguiente paso es hacer tu pedido. Es rápido y seguro.';
$ctaFaqButton          = $cfg['cta_faq_button'] ?? 'Sí, quiero pedirlo ahora';

$ctaStickyMobileText   = $cfg['cta_sticky_mobile_text'] ?? '🔥 Aprovechar oferta hoy';

// Colores con fallback
$primaryColor    = $config['primary_color']    ?? '#3c7a4a';
$secondaryColor  = $config['secondary_color']  ?? '#007bff';
$accentColor     = $config['accent_color']     ?? '#730dad';
$backgroundColor = $config['background_color'] ?? '#f5f5f5';
$textColor       = $config['text_color']       ?? '#222222';
?>
<!DOCTYPE html>
<html lang="es">

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($heroTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS base de la landing -->
    <link rel="stylesheet" href="/tienda_mvc/public/css/style.css">

    <!-- Variables de color específicas de ESTA landing -->
    <style>
        :root {
            --primary-color: <?= htmlspecialchars($primaryColor) ?>;
            --secondary-color: <?= htmlspecialchars($secondaryColor) ?>;
            --accent-color: <?= htmlspecialchars($accentColor) ?>;
            --background-color: <?= htmlspecialchars($backgroundColor) ?>;
            --text-color: <?= htmlspecialchars($textColor) ?>;
        }
    </style>

    <script src="/tienda_mvc/public/js/main.js" defer></script>

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
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '1248724310406936');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id=1248724310406936&ev=PageView&noscript=1" />
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

</head>


<body>

    <!-- HERO -->
    <header class="container hero">
        <div class="hero-text">
            <h1><?= htmlspecialchars($heroTitle) ?></h1>

            <p class="hero-subtitle">
                <?= htmlspecialchars($heroSubtitle) ?>
            </p>

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

            </div>

            <a href="#form-pedido" class="btn-primary">
                <?= htmlspecialchars($heroButtonText) ?>
            </a>
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
        </div>
    </header>

    <main>

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
                    <img src="<?= htmlspecialchars($benefitsMediaPath) ?>" alt="Uso del producto">
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

        <!-- GALERÍA (principal + miniaturas) -->
        <section class="container">
            <h2 class="section-title">MIRA LOS COLORES</h2>

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
        <!-- CONTADOR PROMOCIÓN -->
        <section class="container">
            <div class="countdown">
                <h2><?= htmlspecialchars($countdownTitle) ?></h2>
                <span id="countdown-timer">59:59</span>
                <p><?= htmlspecialchars($countdownText) ?></p>
            </div>
        </section>

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
                    <img src="<?= htmlspecialchars($porqueMediaPath) ?>" alt="Cliente feliz">
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

        <!-- TESTIMONIOS -->
        <section class="container">
            <h2 class="section-title">Lo que cuentan nuestros clientes</h2>
            <div class="testimonials">
                <article class="testimonial">
                    <div class="testimonial-photo">
                        <img src="<?= htmlspecialchars($test1Photo) ?>" alt="Cliente 1">
                    </div>
                    <div class="testimonial-content">
                        <h3><?= htmlspecialchars($test1Name) ?></h3>
                        <p>"<?= htmlspecialchars($test1Text) ?>"</p>
                    </div>
                </article>

                <article class="testimonial">
                    <div class="testimonial-photo">
                        <img src="<?= htmlspecialchars($test2Photo) ?>" alt="Cliente 2">
                    </div>
                    <div class="testimonial-content">
                        <h3><?= htmlspecialchars($test2Name) ?></h3>
                        <p>"<?= htmlspecialchars($test2Text) ?>"</p>
                    </div>
                </article>

                <article class="testimonial">
                    <div class="testimonial-photo">
                        <img src="<?= htmlspecialchars($test3Photo) ?>" alt="Cliente 3">
                    </div>
                    <div class="testimonial-content">
                        <h3><?= htmlspecialchars($test3Name) ?></h3>
                        <p>"<?= htmlspecialchars($test3Text) ?>"</p>
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
                            <button class="slider-btn prev-btn" aria-label="Anterior">
                                <svg viewBox="0 0 24 24">
                                    <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
                                </svg>
                            </button>
                            <button class="slider-btn next-btn" aria-label="Siguiente">
                                <svg viewBox="0 0 24 24">
                                    <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
                                </svg>
                            </button>

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

                                    // Clones para infinite loop: último al inicio, primero al final
                                    $first = $items[1];
                                    $last  = $items[5];

                                    // Helpers para imprimir un slide
                                    $renderSlide = function ($idx, $data, $suffix = '') {
                                        $dataIndex = $suffix ? ($idx . '-' . $suffix) : (string)($idx - 1); // 0..4 en slides reales
                                        $name  = $data['name'] ?? '';
                                        $time  = $data['time'] ?? '';
                                        $text  = $data['text'] ?? '';
                                        $image = $data['image'] ?? '';

                                        // Si no hay imagen configurada, dejamos vacío para que tú la subas.
                                        // (Si quieres, puedes poner un placeholder aquí.)
                                    ?>
                                        <div class="testimonial-slide" data-index="<?= htmlspecialchars($dataIndex) ?>">
                                            <div class="whatsapp-card">
                                                <div class="badge-verified">✅ Compra Verificada</div>
                                                <div class="img-wrapper">
                                                    <?php if (!empty($image)): ?>
                                                        <img src="<?= htmlspecialchars($image) ?>" alt="Testimonio WhatsApp <?= htmlspecialchars($name) ?>" class="whatsapp-screenshot">
                                                    <?php else: ?>
                                                        <img src="/tienda_mvc/public/img/testimonios/1.jpeg" alt="Testimonio WhatsApp" class="whatsapp-screenshot">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-content">
                                                    <strong><?= htmlspecialchars($name) ?></strong>
                                                    <span><?= htmlspecialchars($time) ?></span>
                                                    <p>"<?= htmlspecialchars($text) ?>"</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                    };

                                    // Clone del último (index 4-clone)
                                    $renderSlide(5, $last, 'clone');

                                    // Slides reales (index 0..4)
                                    for ($i = 1; $i <= 5; $i++) {
                                        $renderSlide($i, $items[$i]);
                                    }

                                    // Clone del primero (index 0-clone)
                                    $renderSlide(1, $first, 'clone');
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

        <!-- PREGUNTAS FRECUENTES -->
        <section class="container">
            <h2 class="section-title">Preguntas frecuentes</h2>
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
            </div>

            <!-- CTA de sección -->
            <div class="section-cta">
                <p><?= htmlspecialchars($ctaFaqText) ?></p>
                <a href="#form-pedido" class="btn-primary btn-cta-section">
                    <?= htmlspecialchars($ctaFaqButton) ?>
                </a>
            </div>
        </section>

        <!-- FORMULARIO: REALIZA TU PEDIDO -->
        <section class="container" id="form-pedido">
            <h2 class="section-title">Realiza tu pedido ahora y paga al recibir</h2>

            <?php if (!empty($success)): ?>
                <div class="success">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="form-box">
                <form action="/tienda_mvc/Landing/enviarPedido" method="POST">
                    <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['id'] ?? 1) ?>">

                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($old['nombre'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="apellidos">Apellidos *</label>
                        <input type="text" id="apellidos" name="apellidos" required value="<?= htmlspecialchars($old['apellidos'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Número de WhatsApp *</label>
                        <input type="text" id="telefono" name="telefono" required value="<?= htmlspecialchars($old['telefono'] ?? '') ?>">
                    </div>

                    <?php
                    // Colores disponibles
                    $colores = $colores ?? [];

                    // Precios base
                    $precioVenta     = (float)($producto['precio_venta'] ?? 72000);
                    $precioProveedor = (float)($producto['precio_proveedor'] ?? 0);

                    // Regular
                    $precioRegular = (float)($producto['precio_regular'] ?? $precioVenta);
                    if ($precioRegular <= 0 || $precioRegular < $precioVenta) $precioRegular = $precioVenta;

                    // Descuentos % (para modo individual)
                    $d2  = (int)($producto['descuento_2da'] ?? 15);
                    $d3  = (int)($producto['descuento_3ra'] ?? 20);
                    $act = (int)($producto['descuento_multicantidad_activo'] ?? 1);

                    // Combo
                    $precioCombo2 = (int)($comboPrice2 ?? 115000);
                    if ($precioCombo2 <= 0) $precioCombo2 = 115000;

                    // JSON colores para JS combo
                    $colorsJson = json_encode(array_values($colores), JSON_UNESCAPED_UNICODE);
                    ?>

                    <?php if (!empty($colores)): ?>

                        <?php if (!empty($comboEnabled) && (int)$comboEnabled === 1): ?>
                            <!-- ✅ MODO COMBO -->
                            <div class="form-group">
                                <label for="pricingModeSelect"><strong>Elige tu oferta</strong></label>
                                <select id="pricingModeSelect" class="color-select">
                                    <option value="combo" selected>🔥 Combo x2 — $<?= number_format($precioCombo2, 0, ',', '.') ?> (Recomendado)</option>
                                    <option value="individual">1 unidad — $<?= number_format($precioVenta, 0, ',', '.') ?></option>
                                </select>

                                <input type="hidden" name="pricing_mode" id="pricingMode" value="combo">
                            </div>

                            <!-- COMBOS -->
                            <div id="comboWrap" class="form-group">
                                <label><strong>Selecciona los colores de tu combo</strong></label>
                                <div id="combosContainer" class="colors-qty-wrap"></div>

                                <button type="button" id="addComboBtn" class="btn-ghost add-color-btn">
                                    + Agregar otro combo
                                </button>

                                <small class="help total-units-note">
                                    Total unidades: <strong id="totalUnits">2</strong> (cada combo = 2 unidades)
                                </small>
                            </div>

                            <!-- INDIVIDUAL -->
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
                                            <option value="<?= $i ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <small class="help">Aquí sí aplican descuentos por multicantidad si están activos.</small>
                            </div>

                            <!-- Aquí generamos color_item[] y qty_item[] antes del submit -->
                            <div id="generatedItems"></div>

                        <?php else: ?>
                            <!-- ✅ MODO NORMAL -->
                            <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">

                            <?php
                            $oldColorItems = $old['color_item'] ?? [];
                            $oldQtyItems   = $old['qty_item'] ?? [];
                            if (!is_array($oldColorItems)) $oldColorItems = [];
                            if (!is_array($oldQtyItems))   $oldQtyItems = [];
                            $rowsCount  = max(1, count($oldColorItems), count($oldQtyItems));
                            ?>

                            <div class="form-group">
                                <label>Colores y cantidades *</label>

                                <div id="colorsQtyWrap" class="colors-qty-wrap">
                                    <?php for ($r = 0; $r < $rowsCount; $r++): ?>
                                        <?php
                                        $selColor = $oldColorItems[$r] ?? '';
                                        $selQty   = (int)($oldQtyItems[$r] ?? 1);
                                        if ($selQty < 1) $selQty = 1;
                                        if ($selQty > 5) $selQty = 5;
                                        ?>
                                        <div class="color-qty-row">
                                            <select name="color_item[]" required class="color-select">
                                                <option value="">Selecciona un color</option>
                                                <?php foreach ($colores as $c): ?>
                                                    <option value="<?= htmlspecialchars($c) ?>" <?= ($selColor === $c) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($c) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <select name="qty_item[]" required class="qty-select">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <option value="<?= $i ?>" <?= ($selQty === $i) ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>

                                            <button type="button" class="remove-color-qty" aria-label="Quitar">
                                                <span class="remove-icon">×</span>
                                                <span class="remove-text">Borrar</span>
                                            </button>
                                        </div>
                                    <?php endfor; ?>
                                </div>

                                <button type="button" id="addColorQtyBtn" class="btn-ghost add-color-btn">
                                    + Agregar otro color
                                </button>

                                <small class="help total-units-note">
                                    Total unidades: <strong id="totalUnits">1</strong>
                                </small>
                            </div>

                        <?php endif; ?>

                    <?php else: ?>
                        <input type="hidden" name="pricing_mode" id="pricingMode" value="individual">
                    <?php endif; ?>

                    <!-- ✅ ESTE HIDDEN VA UNA SOLA VEZ (para combo y normal) -->
                    <input type="hidden" id="cantidad_total" name="cantidad_total"
                        value="<?= htmlspecialchars((string)($old['cantidad_total'] ?? 1)) ?>">


                    <!-- (Aquí sigue tu resto de campos: departamento, municipio, entrega, dirección, resumen, submit...) -->


                    <div class="form-group">
                        <label for="departamento">Departamento *</label>
                        <select id="departamento"
                            name="departamento"
                            required
                            data-old="<?= htmlspecialchars($old['departamento'] ?? '') ?>">
                            <option value="">Selecciona un departamento</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="municipio">Municipio *</label>
                        <select id="municipio"
                            name="municipio"
                            required
                            data-old="<?= htmlspecialchars($old['municipio'] ?? '') ?>">
                            <option value="">Selecciona primero un departamento</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <span>¿Cómo quieres recibir tu pedido? *</span>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="tipo_entrega" value="domicilio"
                                    <?= (isset($old['tipo_entrega']) && $old['tipo_entrega'] === 'domicilio') ? 'checked' : '' ?>>
                                Envío a domicilio
                            </label>
                            <label>
                                <input type="radio" name="tipo_entrega" value="oficina"
                                    <?= (isset($old['tipo_entrega']) && $old['tipo_entrega'] === 'oficina') ? 'checked' : '' ?>>
                                Recoger en oficina de Interrapidísimo
                            </label>
                        </div>
                    </div>

                    <div class="form-group" id="grupo-direccion" style="display:none;">
                        <label for="direccion">Dirección completa *</label>
                        <input type="text" id="direccion" name="direccion"
                            value="<?= htmlspecialchars($old['direccion'] ?? '') ?>">
                    </div>

                    <?php
                    $precioUnit = (float)($producto['precio_venta'] ?? 0);
                    ?>
                    <?php
                    $precioVenta      = (float)($producto['precio_venta'] ?? 0);
                    $precioProveedor  = (float)($producto['precio_proveedor'] ?? 0);
                    ?>
                    <?php
                    $precioVenta   = (float)($producto['precio_venta'] ?? 0);
                    $precioProveedor = (float)($producto['precio_proveedor'] ?? 0);

                    $precioRegular = (float)($producto['precio_regular'] ?? $precioVenta);
                    if ($precioRegular <= 0 || $precioRegular < $precioVenta) {
                        $precioRegular = $precioVenta;
                    }

                    // Descuentos del producto (defaults)
                    $d2  = (int)($producto['descuento_2da'] ?? 15);
                    $d3  = (int)($producto['descuento_3ra'] ?? 20);
                    $act = (int)($producto['descuento_multicantidad_activo'] ?? 1);
                    ?>

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
                                <span>
                                    Subtotal (<strong id="summaryQty">1</strong> <span id="summaryQtyWord">unidad</span>)
                                </span>
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

                        <p class="order-summary__question">¿Estás 100% seguro de tu compra?</p>

                        <label class="order-summary__confirm">
                            <input type="checkbox" id="confirmPurchase" name="confirm_purchase" value="1" required
                                <?= !empty($old['confirm_purchase']) ? 'checked' : '' ?>>
                            Sí, quiero el producto y pagaré al recibirlo.
                        </label>

                        <small class="order-summary__note">
                            Al confirmar, aceptas que un asesor te contacte para validar tu pedido.
                        </small>
                    </div>




                    <button type="submit" class="btn-primary btn-full">Confirmar mi pedido</button>
                    <p class="form-note">
                        Uno de nuestros asesores te contactará por WhatsApp para confirmar los datos.
                    </p>
                </form>
            </div>
        </section>

    </main>



    <div class="footer-text">
        <?= htmlspecialchars($footerText) ?>
    </div>



    <!-- CTA sticky para móviles -->
    <a href="#form-pedido" class="cta-sticky-mobile">
        <?= htmlspecialchars($ctaStickyMobileText) ?>
    </a>

    <?php
    $success        = $success ?? '';
    $precioProducto = (float)($producto['precio_venta'] ?? 0);
    $nombreProducto = $producto['nombre'] ?? 'Producto';
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var successMsg = <?= json_encode($success, JSON_UNESCAPED_UNICODE) ?>;

            if (successMsg) {
                alert(successMsg);

                var formSection = document.getElementById('form-pedido');
                if (formSection) {
                    formSection.scrollIntoView({
                        behavior: 'smooth'
                    });
                }

                // Evento de conversión al crear pedido
                if (typeof fbq === 'function') {
                    fbq('track', 'Lead', {
                        content_name: <?= json_encode($nombreProducto) ?>,
                        value: <?= json_encode($precioProducto) ?>,
                        currency: 'COP'
                    });

                    // Si prefieres trabajar con Purchase (conversión de compra directa), cambia:
                    // fbq('track', 'Purchase', { ... });
                }
            }
        });
    </script>

    <?php
    $colorsJson = json_encode(array_values($colores ?? []), JSON_UNESCAPED_UNICODE);
    ?>
    <div id="landingConfig"
        data-combo-enabled="<?= (int)($cfg['combo_enabled'] ?? 0) ?>"
        data-combo-price2="<?= (int)($cfg['combo_price_2'] ?? 0) ?>"
        data-colors='<?= htmlspecialchars($colorsJson, ENT_QUOTES, "UTF-8") ?>'>
    </div>

    <script src="/tienda_mvc/public/js/pricing-summary.js" defer></script>
    <script src="/tienda_mvc/public/js/pricing-combo.js" defer></script>
    <script src="/tienda_mvc/public/js/funcionesLandin.js" defer></script>

    <?php
    // Asegura que $colores sea array simple
    $colores = $colores ?? [];
    $colorsJson = json_encode(array_values($colores), JSON_UNESCAPED_UNICODE);

    // Ojo: comboEnabled / comboPrice2 deben venir de $cfg / $config (como ya lo tienes arriba)
    $comboEnabled = (int)($cfg['combo_enabled'] ?? 0);
    $comboPrice2  = (int)($cfg['combo_price_2'] ?? 0);
    if ($comboPrice2 <= 0) $comboPrice2 = 115000;
    ?>
    <div id="landingConfig"
        data-combo-enabled="<?= $comboEnabled ?>"
        data-combo-price2="<?= $comboPrice2 ?>"
        data-colors='<?= htmlspecialchars($colorsJson, ENT_QUOTES, "UTF-8") ?>'>
    </div>


</body>

</html>