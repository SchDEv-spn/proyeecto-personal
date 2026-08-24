<?php
/**
 * SEED — Landing de pruebas QA
 * =============================================================
 * Crea (o refresca) un producto demo con TODAS las secciones de la
 * landing encendidas y todos los campos de texto poblados con acentos,
 * eñes, signos de apertura y guiones largos.
 *
 * Para qué sirve: la landing sólo se puede auditar de verdad cuando se
 * ven las 14 secciones seguidas. Las landings reales tienen la mayoría
 * de secciones apagadas, así que no sirven de banco de pruebas.
 *
 * Es idempotente: se puede correr las veces que haga falta y siempre
 * deja el mismo estado. No toca ningún producto que no sea el suyo.
 *
 *   php seed_landing_qa.php
 *
 * Sólo desarrollo — no subir a producción.
 */

/* ── CERROJO ──────────────────────────────────────────────────
   Este archivo vive en la raíz del webroot, así que en un hosting
   compartido sería alcanzable como https://tudominio.com/seed_landing_qa.php
   — y al abrirlo crearía un producto y una landing en la base de
   producción. Dos cierres:

     1. Sólo por línea de comandos. Una petición web nunca lo ejecuta.
     2. Sólo en entorno local. Si APP_ENV es production, o el dominio no
        es local, se planta aunque se llame desde consola.

   Si algún día hace falta correrlo contra otro entorno, el camino es
   sacarlo del webroot, no quitar el cerrojo. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/app/helpers.php';
if (!es_entorno_local()) {
    fwrite(STDERR, "Este seeder es sólo para desarrollo. Abortado.\n");
    exit(1);
}

const SLUG = 'demo-qa-landing';
const UP   = '/tienda_mvc/public/uploads/landing/';

$cfg = require __DIR__ . '/app/config/config.php';
$pdo = new PDO(
    "mysql:host={$cfg['db_host']};dbname={$cfg['db_name']};charset=utf8mb4",
    $cfg['db_user'],
    $cfg['db_password'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]
);

/* ── 1. Producto ─────────────────────────────────────────────── */
$producto = [
    'nombre'                         => 'Bolso Orígami — Cuero Vegano (DEMO QA)',
    'slug'                           => SLUG,
    'precio_venta'                   => 89000,
    'precio_regular'                 => 139000,
    'precio_proveedor'               => 38000,
    'costo_envio'                    => 12000,
    'imagen_principal'               => UP . 'gallery_1_file_1779573244_9839.jpg',
    'activo'                         => 1,
    'descuento_multicantidad_activo' => 1,
    'descuento_2da'                  => 15,
    'descuento_3ra'                  => 20,
];

$id = $pdo->prepare('SELECT id FROM productos WHERE slug = ?');
$id->execute([SLUG]);
$productoId = (int)($id->fetchColumn() ?: 0);

if ($productoId) {
    $set = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($producto)));
    $st  = $pdo->prepare("UPDATE productos SET $set WHERE id = :id");
    $st->execute($producto + ['id' => $productoId]);
    echo "producto actualizado (id=$productoId)\n";
} else {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($producto)));
    $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($producto)));
    $pdo->prepare("INSERT INTO productos ($cols) VALUES ($vals)")->execute($producto);
    $productoId = (int)$pdo->lastInsertId();
    echo "producto creado (id=$productoId)\n";
}

/* ── 2. Colores (activan las pills del paso 2 del formulario) ─── */
$pdo->prepare('DELETE FROM producto_colores WHERE producto_id = ?')->execute([$productoId]);
$insColor = $pdo->prepare('INSERT INTO producto_colores (producto_id, color, activo) VALUES (?, ?, 1)');
foreach (['Negro', 'Camel', 'Vino tinto', 'Verde oliva'] as $color) {
    $insColor->execute([$productoId, $color]);
}
echo "4 colores cargados\n";

/* ── 3. Variantes de color de la galería ─────────────────────── */
$colorVariants = json_encode([
    [
        'name'   => 'Negro',
        'hex'    => '#1c1c1c',
        'images' => [
            UP . 'gallery_1_file_1779573244_9839.jpg',
            UP . 'gallery_2_file_1779573244_8715.jpg',
            UP . 'gallery_3_file_1779573244_9910.jpg',
            UP . 'gallery_4_file_1779573244_6031.jpg',
        ],
    ],
    [
        'name'   => 'Camel',
        'hex'    => '#b07d42',
        'images' => [
            UP . 'cv2_g1_file_1781671182_3891.jpeg',
            UP . 'cv2_g2_file_1781671182_2176.jpeg',
            UP . 'cv2_g3_file_1781671888_1030.jpeg',
            UP . 'cv2_g4_file_1781671888_8811.jpeg',
        ],
    ],
    [
        'name'   => 'Vino tinto',
        'hex'    => '#6e1c2f',
        'images' => [UP . 'cv3_g1_file_1786735098_7168.jpg'],
    ],
    [
        'name'   => 'Verde oliva',
        'hex'    => '#4a5231',
        'images' => [UP . 'cv4_g1_file_1786735098_2278.jpg'],
    ],
], JSON_UNESCAPED_UNICODE);

/* ── 4. Landing: todo encendido, todo escrito ────────────────── */
$landing = [
    'producto_id' => $productoId,
    'theme'       => 'generico',

    /* Sin colores propios a propósito. Las columnas color_* se inyectan
       en un <style> que gana sobre el tema, así que una landing con la
       paleta clavada se pinta igual elijas el tema que elijas. Esta
       existe justamente para mirar los temas encima de las 14 secciones:
       si se le clava una paleta, deja de servir para eso.
       Van explícitas a null y no omitidas, porque el seed reescribe una
       fila que ya existe y omitirlas dejaría la paleta anterior. */
    'background_color' => null,
    'text_color'       => null,
    'primary_color'    => null,
    'secondary_color'  => null,
    'accent_color'     => null,
    'color_gold'       => null,
    'color_gold_light' => null,
    'color_success'    => null,
    'color_countdown'  => null,
    'color_bg_card'    => null,
    'color_border'     => null,

    // — Hero —
    'hero_title'       => '¡Ordena tu día con estilo! Bolso Orígami',
    'hero_subtitle'    => 'Todo lo que necesitas, siempre a la mano — sin revolver nada.',
    'hero_subtitle_2'  => 'Diseño español, cuero vegano y compartimentos que sí sirven.',
    'hero_subtitle_3'  => 'Envío gratis a toda Colombia y pagas cuando lo tengas en la mano.',
    'hero_note'        => 'Promoción válida sólo por hoy en Colombia.',
    'hero_button_text' => '¡Necesito el mío!',
    'hero_media_type'  => 'video',
    'hero_media_path'  => UP . 'hero_media_file_1787283473_8992.mp4',
    'hero_badge_stars'     => '4.9',
    'hero_badge_customers' => '+3.200 clientas felices',
    'hero_trust_1' => 'Pago al recibir',
    'hero_trust_2' => 'Envío gratis',
    'hero_trust_3' => 'Cambios sin problema',

    // — Barra de anuncios —
    'announcement_item_1' => '🔥 Quedan pocas unidades',
    'announcement_item_2' => '🚚 Envío gratis a todo el país',
    'announcement_item_3' => '💳 Pago contraentrega',
    'announcement_item_4' => '⭐ +3.200 clientas felices',
    'announcement_item_5' => '📦 Empaque discreto y seguro',
    'announcement_item_6' => '🎁 Cartera a juego de regalo',

    // — Beneficios —
    'benefits_title' => 'Diseñado para tu ritmo de vida',
    'benefit_1'      => 'Cuero vegano premium: resistente y fácil de limpiar',
    'benefit_2'      => 'Cierres reforzados que no se atascan ni se rompen',
    'benefit_3'      => 'Tamaño ideal para celular, agenda, llaves y más',
    'benefit_4'      => 'Organización total con cinco compartimentos independientes',
    'benefit_1_img'  => UP . 'benefit_1_img_file_1779575418_1789.jpeg',
    'benefit_2_img'  => UP . 'benefit_2_img_file_1779575418_2026.jpeg',
    'benefit_3_img'  => UP . 'benefit_3_img_file_1779575418_2897.jpeg',
    'benefit_4_img'  => UP . 'benefit_4_img_file_1781503466_4617.png',
    'benefits_media_type' => 'imagen',
    'benefits_media_path' => UP . 'benefits_media_file_1781502847_7537.png',

    // — Galería —
    'gallery_title'  => 'Galería',
    'gallery_1_path' => UP . 'gallery_1_file_1779573244_9839.jpg',
    'gallery_2_path' => UP . 'gallery_2_file_1779573244_8715.jpg',
    'gallery_3_path' => UP . 'gallery_3_file_1779573244_9910.jpg',
    'gallery_4_path' => UP . 'gallery_4_file_1779573244_6031.jpg',
    'color_variants' => $colorVariants,

    // — Características (mezcla foto + video a propósito) —
    'caract_section_title' => 'Características del producto',
    'caract1_media_path' => UP . 'caract1_media_file_1779714277_5971.jpg',
    'caract1_media_type' => 'image',
    'caract1_title'      => 'Cuero vegano de alta densidad',
    'caract1_text'       => 'Acabado mate que no se descascara ni pierde el color con el uso diario.',
    'caract2_media_path' => UP . 'caract2_media_file_1779714277_3815.mp4',
    'caract2_media_type' => 'video',
    'caract2_title'      => 'Cinco compartimentos reales',
    'caract2_text'       => 'Cada cosa en su sitio: celular, billetera, llaves, gafas y agenda.',
    'caract3_media_path' => UP . 'caract3_media_file_1779714277_8616.mp4',
    'caract3_media_type' => 'video',
    'caract3_title'      => 'Correa ajustable y desmontable',
    'caract3_text'       => 'Se lleva cruzado, al hombro o en la mano según la ocasión.',
    'caract4_media_path' => UP . 'caract4_media_file_1779597499_6534.mp4',
    'caract4_media_type' => 'video',
    'caract4_title'      => 'Costuras dobles reforzadas',
    'caract4_text'       => 'Aguantan el peso del día a día sin ceder ni deformarse.',

    // — Cómo funciona —
    'cf_title'      => 'Así de simple es recibirlo en casa',
    'cf_step1_icon' => '📋',
    'cf_step1_title'=> 'Haz tu pedido',
    'cf_step1_desc' => 'Llena el formulario en menos de dos minutos. Sin registro ni tarjeta.',
    'cf_step2_icon' => '📦',
    'cf_step2_title'=> 'Empacamos y enviamos',
    'cf_step2_desc' => 'Al día siguiente hábil despachamos tu pedido hacia tu puerta.',
    'cf_step3_icon' => '🏠',
    'cf_step3_title'=> 'Lo recibes y pagas',
    'cf_step3_desc' => 'Revisas el producto y pagas al mensajero. Sin adelantos de ningún tipo.',

    // — Countdown —
    'countdown_title'   => 'La promoción termina en:',
    'countdown_text'    => 'Cuando el contador llegue a cero el precio vuelve a $139.000. ¡Asegura el tuyo ahora!',
    'countdown_minutes' => 25,
    'urgency_stock'     => 8,

    // — Por qué te encantará —
    'porque_title'      => '¿Cansada de no encontrar nada en tu bolso?',
    'porque_text'       => 'Sabemos lo frustrante que es buscar las llaves o el celular en un bolso desordenado. Por eso este diseño tiene compartimentos independientes que mantienen todo en su sitio: es la mezcla exacta entre accesorio de moda y herramienta de organización diaria.',
    'porque_bullet1'    => 'Encuentras lo que buscas en segundos, no en minutos',
    'porque_bullet2'    => 'Más fácil y rápido que cualquier organizador suelto',
    'porque_bullet3'    => 'Colores clásicos (Negro y Camel) que combinan con todo',
    'porque_media_type' => 'video',
    'porque_media_path' => UP . 'porque_media_file_1787283132_4178.mp4',

    // — Comparación —
    'comparison_title'         => 'La diferencia que hace este producto',
    'comparison_label_without' => 'Sin el bolso Orígami',
    'comparison_label_with'    => 'Con el bolso Orígami',
    'comparison_img_without'   => UP . 'comparison_img_without_file_1779576019_5109.jpeg',
    'comparison_img_with'      => UP . 'comparison_img_with_file_1779576019_3117.jpeg',
    'comparison_1_without' => 'Revuelves todo para encontrar las llaves',
    'comparison_1_with'    => 'Cada cosa tiene su compartimento fijo',
    'comparison_2_without' => 'Las costuras ceden a los pocos meses',
    'comparison_2_with'    => 'Costuras dobles que aguantan años',
    'comparison_3_without' => 'El cuero sintético se descascara',
    'comparison_3_with'    => 'Acabado mate que conserva su color',
    'comparison_4_without' => 'Una sola forma de llevarlo',
    'comparison_4_with'    => 'Cruzado, al hombro o en la mano',
    'comparison_5_without' => 'Pagas antes y cruzas los dedos',
    'comparison_5_with'    => 'Pagas cuando lo tienes en la mano',

    // — Para quién es —
    'para_quien_title'  => '¿Este producto es para ti?',
    'para_quien_si_1'   => 'Cargas mil cosas y vives buscándolas',
    'para_quien_si_2'   => 'Ya probaste bolsos baratos y se te dañaron',
    'para_quien_si_3'   => 'Quieres algo que llegue a tu puerta con envío gratis',
    'para_quien_si_4'   => 'Valoras un diseño sobrio que combine con todo',
    'para_quien_no_1'   => 'Buscas un bolso de fiesta muy pequeño',
    'para_quien_no_2'   => 'No estás dispuesta a recibir al mensajero',
    'para_quien_no_3'   => 'Buscas únicamente el precio más bajo del mercado',

    // — Testimonios —
    'testimonios_title' => 'Lo que cuentan nuestras clientas',
    'test1_name'  => 'María Fernanda G.',
    'test1_city'  => 'Bogotá',
    'test1_text'  => 'Me encantó. Por fin encuentro las llaves rápido. La calidad se siente muy buena para el precio.',
    'test1_photo_path' => UP . 'test1_photo_file_1766710859_2381.png',
    'test2_name'  => 'Catalina R.',
    'test2_city'  => 'Medellín',
    'test2_text'  => 'Pedí el color camel y es precioso. El envío fue súper rápido y pagué en la puerta de mi casa.',
    'test2_photo_path' => UP . 'test2_photo_file_1766070021_9286.jpeg',
    'test3_name'  => 'Marta Lucía G.',
    'test3_city'  => 'Cali',
    'test3_text'  => 'Lo recomiendo totalmente. Me dieron confianza con el pago contraentrega y cumplió 10/10.',
    'test3_photo_path' => UP . 'test3_photo_file_1766070021_7165.jpeg',

    // — Testimonios WhatsApp —
    'wa_enabled'     => 1,
    'wa_title'       => '📱 Testimonios Reales de WhatsApp',
    'wa_subtitle'    => 'Capturas reales de conversaciones con nuestras clientas',
    'wa_footer_note' => '💡 Desliza para ver más • Capturas 100% reales de WhatsApp',
    'wa1_name' => 'María González',   'wa1_time' => '• Hace 24 horas', 'wa1_text' => '¡Llegó antes de lo esperado! La calidad superó mis expectativas.',      'wa1_image_path' => UP . 'wa1_image_file_1766714812_5507.jpeg',
    'wa2_name' => 'Carolina Rodríguez','wa2_time' => '• Hace 3 días',   'wa2_text' => 'Ya se lo recomendé a tres amigas. La atención fue excelente.',           'wa2_image_path' => UP . 'wa2_image_file_1766714812_1529.png',
    'wa3_name' => 'Ana Martínez',      'wa3_time' => '• Hace 1 semana', 'wa3_text' => 'Segunda compra y vuelvo a quedar encantada. Mi tienda de confianza.',    'wa3_image_path' => UP . 'wa3_image_file_1766714812_6684.jpeg',
    'wa4_name' => 'Paola López',       'wa4_time' => '• Hace 2 días',   'wa4_text' => 'Envío en 24 horas. ¡Increíble! Justo lo que necesitaba con urgencia.',   'wa4_image_path' => UP . 'wa4_image_file_1766714812_2309.png',
    'wa5_name' => 'Laura Sánchez',     'wa5_time' => '• Hace 4 días',   'wa5_text' => 'Lo subí a mis historias y todas preguntan dónde lo compré.',            'wa5_image_path' => UP . 'wa5_image_file_1766714812_6633.png',

    // — FAQs (las seis) —
    'faq_title' => 'Preguntas frecuentes',
    'faq1_q' => '¿Tengo que pagar ahora mismo?',
    'faq1_a' => 'No. Para tu total seguridad pagas únicamente cuando recibas el producto en la puerta de tu casa o lugar de trabajo.',
    'faq2_q' => '¿Cuánto tarda en llegar mi pedido?',
    'faq2_a' => 'El tiempo promedio de entrega en Colombia es de 2 a 5 días hábiles, según tu ubicación.',
    'faq3_q' => '¿De qué material es el bolso?',
    'faq3_a' => 'Está fabricado en cuero vegano de alta resistencia (PU premium), diseñado para durar y mantener su color.',
    'faq4_q' => '¿Qué pasa si no me gusta cuando lo vea?',
    'faq4_a' => 'Si al recibirlo no es lo que esperabas, no lo recibes y no pagas nada. Así de simple.',
    'faq5_q' => '¿Hacen envíos a pueblos pequeños?',
    'faq5_a' => 'Sí. Enviamos con Interrapidísimo, Envía y Coordinadora a todo el país, incluidas zonas rurales.',
    'faq6_q' => '¿Puedo pedir más de un color?',
    'faq6_a' => 'Claro. En el formulario puedes agregar todos los colores que quieras y el descuento por cantidad se aplica solo.',

    // — Garantía —
    'garantia_title' => 'Tu compra está 100% protegida',
    'garantia_desc'  => 'Si el producto llega dañado, diferente a lo descrito o simplemente no te convence, te lo solucionamos. Sin burocracia y sin excusas.',
    'garantia_item1' => '💳 Pagas sólo cuando recibes el producto en tus manos',
    'garantia_item2' => '🚚 Envío gratis incluido a cualquier ciudad',
    'garantia_item3' => '🔄 Si llega dañado o incorrecto, lo reponemos',
    'garantia_item4' => '💬 Asesora en WhatsApp disponible para ti',

    // — Autoridad —
    'authority_enabled'    => 1,
    'authority_title'      => '¿Por qué confiar en nosotros?',
    'authority_years'      => '3',
    'authority_deliveries' => '5.000+',
    'authority_rating'     => '4.9',
    'authority_guarantee'  => 'Garantía de satisfacción',

    // — Regalo —
    'regalo_image_path' => UP . 'benefit_2_img_file_1779575418_2026.jpeg',
    'regalo_label'      => 'Cartera a juego incluida de regalo',

    // — Antes y después —
    'antes_path'          => UP . 'antes_file_1779573244_2962.jpg',
    'despues_path'        => UP . 'despues_file_1779573244_1171.jpg',
    'antes_label'         => 'Antes',
    'despues_label'       => 'Después',
    'antes_despues_title' => 'Mira la diferencia',

    // — CTAs de sección —
    'cta_benefits_text'        => '¡No más desorden en tus salidas! Ordena el tuyo con descuento hoy.',
    'cta_benefits_button'      => 'Quiero aprovechar la oferta',
    'cta_gallery_text'         => 'Lo que ves es lo que llega. Sin sorpresas, sin excusas.',
    'cta_gallery_button'       => 'Lo quiero igual que en las fotos',
    'cta_porque_text'          => 'Únete a las miles de mujeres que ya simplificaron su día a día.',
    'cta_porque_button'        => 'Quiero sentir ese cambio',
    'cta_testimonials_text'    => '¿Lista para estrenar? Aprovecha el envío gratis hoy mismo.',
    'cta_testimonials_button'  => 'Quiero ser la próxima en recibirlo',
    'cta_faq_text'             => '¿Tienes dudas? Recuerda que el envío es seguro y pagas al recibir.',
    'cta_faq_button'           => 'Sí, quiero pedirlo ahora',
    'cta_como_funciona_text'   => 'Así de simple. ¿Lista para empezar?',
    'cta_como_funciona_button' => 'Hacer mi pedido ahora →',
    'cta_comparison_button'    => 'Quiero experimentar la diferencia →',
    'cta_para_quien_button'    => 'Sí, es para mí →',
    'cta_wa_testimonios_button'=> 'Yo también lo quiero →',
    'cta_sticky_mobile_text'   => '🔥 Aprovechar oferta hoy',

    // — Formulario y pie —
    'form_title'    => 'Haz tu pedido — Pago al recibir',
    'form_subtitle' => 'Sin adelantos · El mensajero llega a tu puerta',
    'footer_text'   => '© ' . date('Y') . ' Bolsos Orígami. Todos los derechos reservados.',
    'wa_phone'      => '573023959721',

    // — Combo —
    'combo_enabled' => 1,
    'combo_price_2' => 155000,

    // — Orden de secciones (el completo) —
    'section_order' => 'price_box,benefits,gallery,caracteristicas,como_funciona,countdown,porque,comparison,para_quien,testimonios,wa_testimonios,faqs,garantia,regalo',
];

// Todos los interruptores en 1: el objetivo del banco de pruebas es ver
// las 14 secciones seguidas, que es donde se notan los desajustes.
foreach ([
    'show_benefits', 'show_gallery', 'show_antes_despues', 'show_como_funciona',
    'show_countdown', 'show_porque', 'show_para_quien', 'show_testimonios',
    'show_faqs', 'show_garantia', 'show_trust_strip', 'show_announcement_bar',
    'show_sticky_bar', 'show_comparison', 'show_resumen_oferta', 'show_regalo',
    'show_price_box', 'show_cta_sticky', 'show_whatsapp_btn', 'show_fomo',
    'show_exit_popup', 'show_wa_testimonios', 'show_footer', 'show_caracteristicas',
    'show_cta_benefits', 'show_cta_gallery', 'show_cta_porque', 'show_cta_testimonials',
    'show_cta_faq', 'show_cta_como_funciona', 'show_cta_comparison',
    'show_cta_para_quien', 'show_cta_wa_testimonios',
    'caract1_active', 'caract2_active', 'caract3_active', 'caract4_active',
] as $flag) {
    $landing[$flag] = 1;
}

/* ── 5. Guardar ──────────────────────────────────────────────── */
$existe = $pdo->prepare('SELECT id FROM landing_config WHERE producto_id = ?');
$existe->execute([$productoId]);

if ($existe->fetchColumn()) {
    $set = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($landing)));
    $pdo->prepare("UPDATE landing_config SET $set WHERE producto_id = :producto_id")
        ->execute($landing);
    echo "landing_config actualizada\n";
} else {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($landing)));
    $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($landing)));
    $pdo->prepare("INSERT INTO landing_config ($cols) VALUES ($vals)")->execute($landing);
    echo "landing_config creada\n";
}

/* ── 6. Verificación del viaje de ida y vuelta de los acentos ── */
// Un "?" pegado a una letra es el rastro que deja una conversión con
// pérdida (latin1 → utf8mb4). Un "?" suelto o al final de una frase es
// puntuación legítima y no debe dar falsa alarma.
$dañado = fn(string $t): bool => (bool)preg_match('/\?[\p{L}]|[\p{L}]\?[\p{Ll}]/u', $t);

$check = $pdo->prepare('SELECT * FROM landing_config WHERE producto_id = ?');
$check->execute([$productoId]);
$row = $check->fetch();

$rotos = 0;
$conAcento = 0;
foreach ($row as $valor) {
    if (!is_string($valor) || $valor === '') continue;
    if (preg_match('/[áéíóúñüÁÉÍÓÚÑÜ¿¡—·]/u', $valor)) $conAcento++;
    if ($dañado($valor)) $rotos++;
}

echo "\n--- verificación UTF-8 ---\n";
printf("  campos con acentos intactos: %d\n", $conAcento);
printf("  campos con pérdida:          %d %s\n", $rotos, $rotos ? '← revisar' : '');
printf("  hero_title: %s\n", $row['hero_title']);
printf("  bytes:      %s\n", bin2hex(substr($row['hero_title'], 0, 8)));

echo "\nlisto → http://localhost/tienda_mvc/producto/" . SLUG . "\n";
