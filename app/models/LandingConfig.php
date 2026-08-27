<?php

class LandingConfig extends Model
{
    /**
     * Temas válidos, desde la fuente única app/config/themes.php.
     * Se cachea en estático porque el guardado la consulta una vez por
     * fila y el archivo no cambia dentro de una petición.
     */
    public static function temasValidos(): array
    {
        static $temas = null;
        if ($temas === null) {
            $temas = require dirname(__DIR__) . '/config/themes.php';
        }
        return $temas;
    }

    /**
     * Slug de tema definitivo para un valor guardado.
     *
     * Al pasar de nueve temas a cinco quedaron landings apuntando a
     * slugs que ya no existen. Sin esto el validador no los reconoce y
     * cae al tema por defecto EN SILENCIO: una landing en 'obsidian'
     * amanece con los colores de otro tema y no hay nada en ningún log
     * que lo explique. Es el mismo fallo que ya costó caro cuando se
     * añadió midnight-amber y se olvidó una de las cinco whitelists.
     *
     * Cada tema declara en 'alias' los slugs retirados que hereda, así
     * que 'obsidian' resuelve a 'relojes' y no a un tema cualquiera.
     * Un slug desconocido de verdad sí cae al por defecto, que es lo
     * único que se puede hacer con él.
     */
    public static function resolverTema(?string $slug): string
    {
        $temas = self::temasValidos();

        if ($slug !== null && isset($temas[$slug])) {
            return $slug;
        }

        static $alias = null;
        if ($alias === null) {
            $alias = [];
            foreach ($temas as $destino => $t) {
                foreach ($t['alias'] ?? [] as $viejo) {
                    $alias[$viejo] = $destino;
                }
            }
        }

        return $alias[$slug] ?? self::temaPorDefecto();
    }

    /**
     * El primero de la lista. Antes estaba escrito a mano como
     * 'dark-luxury' en cuatro sitios, y ese slug ya no existe.
     */
    public static function temaPorDefecto(): string
    {
        return array_key_first(self::temasValidos());
    }

    /**
     * Secciones del editor de landing: id del bloque en el DOM (mismo que
     * usa el TOC en `#landingToc a[data-target]`), etiqueta legible, y la
     * columna booleana de `landing_config` que la enciende/apaga (o null si
     * es un grupo de config sin un único toggle, o siempre visible).
     *
     * Fuente única para: el enum `seccion_id` del análisis con IA
     * (`LandingAnalisis::esquema()`), el bloque de secciones del prompt
     * (`LandingAnalisis::sistema()`), y el salto "Ir a la sección" del panel
     * de recomendaciones. El orden es el de aparición en la landing.
     */
    public const SECCIONES_EDITOR = [
        'sec-secciones'            => ['label' => 'Secciones visibles y orden',      'show' => null],
        'sec-announcement'         => ['label' => 'Barra de anuncio',                'show' => 'show_announcement_bar'],
        'sec-hero'                 => ['label' => 'Hero',                            'show' => null],
        'sec-hero-trust'           => ['label' => 'Señales de confianza del hero',   'show' => 'show_trust_strip'],
        'sec-beneficios'           => ['label' => 'Beneficios',                      'show' => 'show_benefits'],
        'sec-galeria'              => ['label' => 'Galería',                         'show' => 'show_gallery'],
        'sec-caracteristicas'      => ['label' => 'Características del producto',     'show' => 'show_caracteristicas'],
        'sec-comofunciona-content' => ['label' => 'Cómo funciona',                   'show' => 'show_como_funciona'],
        'sec-contador'             => ['label' => 'Contador / Oferta',               'show' => 'show_countdown'],
        'sec-porque'               => ['label' => '¿Por qué te encantará?',          'show' => 'show_porque'],
        'sec-comparison'           => ['label' => 'Tabla comparativa (con / sin)',   'show' => 'show_comparison'],
        'sec-paraquien'            => ['label' => '¿Para quién es?',                 'show' => 'show_para_quien'],
        'sec-testimonios'          => ['label' => 'Testimonios',                     'show' => 'show_testimonios'],
        'sec-wa'                   => ['label' => 'Testimonios de WhatsApp',         'show' => 'show_wa_testimonios'],
        'sec-faq'                  => ['label' => 'Preguntas frecuentes',            'show' => 'show_faqs'],
        'sec-garantia'             => ['label' => 'Banner de garantía',              'show' => 'show_garantia'],
        'sec-autoridad'            => ['label' => 'Bloque de autoridad',             'show' => 'authority_enabled'],
        'sec-transportadoras'      => ['label' => 'Logos de transportadoras',        'show' => null],
        'sec-regalo'               => ['label' => 'Regalo incluido',                 'show' => 'show_regalo'],
        'sec-form-header'          => ['label' => 'Formulario de pedido',            'show' => null],
        'sec-footer'               => ['label' => 'Footer',                          'show' => 'show_footer'],
        'sec-ctas'                 => ['label' => 'Botones CTA de las secciones',    'show' => null],
        'sec-combo'                => ['label' => 'Combo / x2 unidades',             'show' => 'combo_enabled'],
        'sec-colores'              => ['label' => 'Colores del tema',               'show' => null],
    ];

    /** Ids válidos para el enum del análisis: las secciones + "ninguna". */
    public static function seccionIdsValidos(): array
    {
        return array_merge(array_keys(self::SECCIONES_EDITOR), ['ninguna']);
    }

    public function obtenerPorProducto(int $productoId)
    {
        $sql = "SELECT * FROM landing_config WHERE producto_id = :producto_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':producto_id' => $productoId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function crearPorProducto(int $productoId)
    {
        $sql = "INSERT INTO landing_config (producto_id) VALUES (:producto_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':producto_id' => $productoId]);
    }

    public function asegurarFilaProducto(int $productoId)
    {
        $actual = $this->obtenerPorProducto($productoId);
        if (!$actual) {
            $this->crearPorProducto($productoId);
        }
    }

    // Columnas que definen la ESTRUCTURA de la landing: el orden de las
    // secciones ordenables y si cada una está visible u oculta.
    // No incluye textos, imágenes, colores ni elementos fijos/CTA.
    private const COLUMNAS_ESTRUCTURA = [
        'section_order',
        'show_benefits',
        'show_gallery',
        'show_caracteristicas',
        'show_como_funciona',
        'show_countdown',
        'show_porque',
        'show_comparison',
        'show_para_quien',
        'show_testimonios',
        'show_faqs',
        'show_wa_testimonios',
        'show_garantia',
        'show_regalo',
        'show_price_box',

        // Elementos fijos (sin orden propio, pero también son estructura)
        'show_sticky_bar',
        'show_announcement_bar',
        'show_resumen_oferta',
        'show_cta_sticky',
        'show_whatsapp_btn',
        'show_fomo',
        'show_exit_popup',
    ];

    // Copia solo el orden estructural y la visibilidad de las secciones
    // de un producto a otro — no toca textos, imágenes ni colores.
    public function copiarEstructura(int $productoIdOrigen, int $productoIdDestino): bool
    {
        $origen = $this->obtenerPorProducto($productoIdOrigen);
        if (!$origen) {
            return false;
        }

        $this->asegurarFilaProducto($productoIdDestino);

        $sets   = implode(', ', array_map(fn($c) => "$c = :$c", self::COLUMNAS_ESTRUCTURA));
        $params = [':producto_id' => $productoIdDestino];
        foreach (self::COLUMNAS_ESTRUCTURA as $col) {
            $params[":$col"] = $origen[$col] ?? null;
        }

        $sql  = "UPDATE landing_config SET $sets WHERE producto_id = :producto_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function guardarPorProducto(int $productoId, array $data)
    {
        $this->asegurarFilaProducto($productoId);

        $sql = "UPDATE landing_config
            SET theme              = :theme,

                hero_title         = :hero_title,
                hero_subtitle      = :hero_subtitle,
                hero_subtitle_2    = :hero_subtitle_2,
                hero_subtitle_3    = :hero_subtitle_3,
                hero_note          = :hero_note,
                hero_button_text   = :hero_button_text,
                hero_media_type    = :hero_media_type,
                hero_media_path    = :hero_media_path,
                hero_poster_path   = :hero_poster_path,

                benefits_title      = :benefits_title,
                benefit_1           = :benefit_1,
                benefit_2           = :benefit_2,
                benefit_3           = :benefit_3,
                benefit_4           = :benefit_4,
                benefit_1_img       = :benefit_1_img,
                benefit_2_img       = :benefit_2_img,
                benefit_3_img       = :benefit_3_img,
                benefit_4_img       = :benefit_4_img,
                benefits_media_path = :benefits_media_path,
                benefits_media_type = :benefits_media_type,

                gallery_1_path = :gallery_1_path,
                gallery_2_path = :gallery_2_path,
                gallery_3_path = :gallery_3_path,
                gallery_4_path = :gallery_4_path,

                countdown_title = :countdown_title,
                countdown_text  = :countdown_text,

                porque_title      = :porque_title,
                porque_text       = :porque_text,
                porque_bullet1    = :porque_bullet1,
                porque_bullet2    = :porque_bullet2,
                porque_bullet3    = :porque_bullet3,
                porque_media_path = :porque_media_path,
                porque_media_type = :porque_media_type,

                test1_name        = :test1_name,
                test1_text        = :test1_text,
                test1_city        = :test1_city,
                test1_photo_path  = :test1_photo_path,
                test1_banner_path = :test1_banner_path,
                test2_name        = :test2_name,
                test2_text        = :test2_text,
                test2_city        = :test2_city,
                test2_photo_path  = :test2_photo_path,
                test2_banner_path = :test2_banner_path,
                test3_name        = :test3_name,
                test3_text        = :test3_text,
                test3_city        = :test3_city,
                test3_photo_path  = :test3_photo_path,
                test3_banner_path = :test3_banner_path,

                faq1_q = :faq1_q,
                faq1_a = :faq1_a,
                faq2_q = :faq2_q,
                faq2_a = :faq2_a,
                faq3_q = :faq3_q,
                faq3_a = :faq3_a,
                faq4_q = :faq4_q,
                faq4_a = :faq4_a,
                faq5_q = :faq5_q,
                faq5_a = :faq5_a,
                faq6_q = :faq6_q,
                faq6_a = :faq6_a,

                footer_text  = :footer_text,
                show_footer  = :show_footer,

                cta_benefits_text        = :cta_benefits_text,
                cta_benefits_button      = :cta_benefits_button,
                cta_gallery_text         = :cta_gallery_text,
                cta_gallery_button       = :cta_gallery_button,
                cta_porque_text          = :cta_porque_text,
                cta_porque_button        = :cta_porque_button,
                cta_testimonials_text    = :cta_testimonials_text,
                cta_testimonials_button  = :cta_testimonials_button,
                cta_faq_text             = :cta_faq_text,
                cta_faq_button           = :cta_faq_button,
                cta_sticky_mobile_text   = :cta_sticky_mobile_text,

                wa_enabled     = :wa_enabled,
                wa_title       = :wa_title,
                wa_subtitle    = :wa_subtitle,
                wa_footer_note = :wa_footer_note,

                wa1_name       = :wa1_name,
                wa1_time       = :wa1_time,
                wa1_text       = :wa1_text,
                wa1_image_path = :wa1_image_path,

                wa2_name       = :wa2_name,
                wa2_time       = :wa2_time,
                wa2_text       = :wa2_text,
                wa2_image_path = :wa2_image_path,

                wa3_name       = :wa3_name,
                wa3_time       = :wa3_time,
                wa3_text       = :wa3_text,
                wa3_image_path = :wa3_image_path,

                wa4_name       = :wa4_name,
                wa4_time       = :wa4_time,
                wa4_text       = :wa4_text,
                wa4_image_path = :wa4_image_path,

                wa5_name       = :wa5_name,
                wa5_time       = :wa5_time,
                wa5_text       = :wa5_text,
                wa5_image_path = :wa5_image_path,

                primary_color    = :primary_color,
                secondary_color  = :secondary_color,
                accent_color     = :accent_color,
                background_color = :background_color,
                text_color       = :text_color,

                color_gold       = :color_gold,
                color_gold_light = :color_gold_light,
                color_success    = :color_success,
                color_countdown  = :color_countdown,
                color_bg_card    = :color_bg_card,
                color_border     = :color_border,

                combo_enabled = :combo_enabled,
                combo_price_2 = :combo_price_2,

                antes_path          = :antes_path,
                despues_path        = :despues_path,
                antes_label         = :antes_label,
                despues_label       = :despues_label,
                antes_despues_title = :antes_despues_title,

                para_quien_si_1 = :para_quien_si_1,
                para_quien_si_2 = :para_quien_si_2,
                para_quien_si_3 = :para_quien_si_3,
                para_quien_si_4 = :para_quien_si_4,
                para_quien_no_1 = :para_quien_no_1,
                para_quien_no_2 = :para_quien_no_2,
                para_quien_no_3 = :para_quien_no_3,

                wa_phone           = :wa_phone,
                hero_badge_stars   = :hero_badge_stars,
                hero_badge_customers = :hero_badge_customers,
                urgency_stock      = :urgency_stock,
                countdown_minutes  = :countdown_minutes,

                comparison_img_without = :comparison_img_without,
                comparison_img_with    = :comparison_img_with,

                comparison_title          = :comparison_title,
                comparison_label_without  = :comparison_label_without,
                comparison_label_with     = :comparison_label_with,
                comparison_1_without   = :comparison_1_without,
                comparison_1_with      = :comparison_1_with,
                comparison_2_without   = :comparison_2_without,
                comparison_2_with      = :comparison_2_with,
                comparison_3_without   = :comparison_3_without,
                comparison_3_with      = :comparison_3_with,
                comparison_4_without   = :comparison_4_without,
                comparison_4_with      = :comparison_4_with,
                comparison_5_without   = :comparison_5_without,
                comparison_5_with      = :comparison_5_with,

                authority_enabled    = :authority_enabled,
                authority_title      = :authority_title,
                authority_years      = :authority_years,
                authority_deliveries = :authority_deliveries,
                authority_rating     = :authority_rating,
                authority_guarantee  = :authority_guarantee,

                show_wa_testimonios = :show_wa_testimonios,

                show_benefits      = :show_benefits,
                show_gallery       = :show_gallery,
                show_antes_despues = :show_antes_despues,
                show_como_funciona = :show_como_funciona,
                show_countdown     = :show_countdown,
                show_porque        = :show_porque,
                show_para_quien    = :show_para_quien,
                show_testimonios   = :show_testimonios,
                show_faqs          = :show_faqs,

                section_order      = :section_order,

                gallery_title     = :gallery_title,
                testimonios_title = :testimonios_title,
                para_quien_title  = :para_quien_title,
                faq_title         = :faq_title,

                announcement_item_1 = :announcement_item_1,
                announcement_item_2 = :announcement_item_2,
                announcement_item_3 = :announcement_item_3,
                announcement_item_4 = :announcement_item_4,
                announcement_item_5 = :announcement_item_5,
                announcement_item_6 = :announcement_item_6,

                hero_trust_1 = :hero_trust_1,
                hero_trust_2 = :hero_trust_2,
                hero_trust_3 = :hero_trust_3,

                cf_title       = :cf_title,
                cf_step1_icon  = :cf_step1_icon,
                cf_step1_title = :cf_step1_title,
                cf_step1_desc  = :cf_step1_desc,
                cf_step2_icon  = :cf_step2_icon,
                cf_step2_title = :cf_step2_title,
                cf_step2_desc  = :cf_step2_desc,
                cf_step3_icon  = :cf_step3_icon,
                cf_step3_title = :cf_step3_title,
                cf_step3_desc  = :cf_step3_desc,

                show_garantia  = :show_garantia,
                garantia_title = :garantia_title,
                garantia_desc  = :garantia_desc,
                garantia_item1 = :garantia_item1,
                garantia_item2 = :garantia_item2,
                garantia_item3 = :garantia_item3,
                garantia_item4 = :garantia_item4,

                show_trust_strip = :show_trust_strip,

                show_announcement_bar = :show_announcement_bar,
                show_sticky_bar       = :show_sticky_bar,
                show_comparison       = :show_comparison,
                show_resumen_oferta   = :show_resumen_oferta,
                show_cta_sticky       = :show_cta_sticky,
                show_whatsapp_btn     = :show_whatsapp_btn,
                show_fomo             = :show_fomo,
                show_exit_popup       = :show_exit_popup,

                show_cta_benefits        = :show_cta_benefits,
                show_cta_gallery         = :show_cta_gallery,
                show_cta_porque          = :show_cta_porque,
                show_cta_testimonials    = :show_cta_testimonials,
                show_cta_faq             = :show_cta_faq,
                show_cta_como_funciona   = :show_cta_como_funciona,
                cta_como_funciona_text   = :cta_como_funciona_text,
                cta_como_funciona_button = :cta_como_funciona_button,
                show_cta_comparison      = :show_cta_comparison,
                cta_comparison_button    = :cta_comparison_button,
                show_cta_para_quien      = :show_cta_para_quien,
                cta_para_quien_button    = :cta_para_quien_button,
                show_cta_wa_testimonios  = :show_cta_wa_testimonios,
                cta_wa_testimonios_button = :cta_wa_testimonios_button,

                show_caracteristicas  = :show_caracteristicas,
                caract_section_title  = :caract_section_title,
                caract1_active        = :caract1_active,
                caract1_media_path    = :caract1_media_path,
                caract1_media_type    = :caract1_media_type,
                caract1_title         = :caract1_title,
                caract1_text          = :caract1_text,
                caract2_active        = :caract2_active,
                caract2_media_path    = :caract2_media_path,
                caract2_media_type    = :caract2_media_type,
                caract2_title         = :caract2_title,
                caract2_text          = :caract2_text,
                caract3_active        = :caract3_active,
                caract3_media_path    = :caract3_media_path,
                caract3_media_type    = :caract3_media_type,
                caract3_title         = :caract3_title,
                caract3_text          = :caract3_text,
                caract4_active        = :caract4_active,
                caract4_media_path    = :caract4_media_path,
                caract4_media_type    = :caract4_media_type,
                caract4_title         = :caract4_title,
                caract4_text          = :caract4_text,

                form_title    = :form_title,
                form_subtitle = :form_subtitle,

                regalo_image_path = :regalo_image_path,
                regalo_label      = :regalo_label,
                show_regalo       = :show_regalo,
                show_price_box    = :show_price_box,

                color_variants    = :color_variants,

                pixel_id   = :pixel_id,
                clarity_id = :clarity_id

            WHERE producto_id = :producto_id";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            /* Quinta y última copia de la lista de temas: ésta era la que
               seguía rechazando midnight-amber después de arreglar las
               otras cuatro. Ahora sale de app/config/themes.php como
               todas, y pasa por resolverTema() para que un slug retirado
               herede su sucesor en vez de caer al por defecto sin avisar. */
            ':theme'           => self::resolverTema($data['theme'] ?? null),

            ':hero_title'         => $data['hero_title']       ?? null,
            ':hero_subtitle'      => $data['hero_subtitle']    ?? null,
            ':hero_subtitle_2'    => $data['hero_subtitle_2']  ?? null,
            ':hero_subtitle_3'    => $data['hero_subtitle_3']  ?? null,
            ':hero_note'          => $data['hero_note']        ?? null,
            ':hero_button_text'   => $data['hero_button_text'] ?? null,
            ':hero_media_type'    => $data['hero_media_type']  ?? null,
            ':hero_media_path'    => $data['hero_media_path']  ?? null,
            ':hero_poster_path'   => $data['hero_poster_path'] ?? null,

            ':benefits_title'      => $data['benefits_title']      ?? null,
            ':benefit_1'           => $data['benefit_1']           ?? null,
            ':benefit_2'           => $data['benefit_2']           ?? null,
            ':benefit_3'           => $data['benefit_3']           ?? null,
            ':benefit_4'           => $data['benefit_4']           ?? null,
            ':benefit_1_img'       => $data['benefit_1_img']       ?? null,
            ':benefit_2_img'       => $data['benefit_2_img']       ?? null,
            ':benefit_3_img'       => $data['benefit_3_img']       ?? null,
            ':benefit_4_img'       => $data['benefit_4_img']       ?? null,
            ':benefits_media_path' => $data['benefits_media_path'] ?? null,
            ':benefits_media_type' => $data['benefits_media_type'] ?? 'imagen',

            ':gallery_1_path' => $data['gallery_1_path'] ?? null,
            ':gallery_2_path' => $data['gallery_2_path'] ?? null,
            ':gallery_3_path' => $data['gallery_3_path'] ?? null,
            ':gallery_4_path' => $data['gallery_4_path'] ?? null,

            ':countdown_title' => $data['countdown_title'] ?? null,
            ':countdown_text'  => $data['countdown_text']  ?? null,

            ':porque_title'      => $data['porque_title']      ?? null,
            ':porque_text'       => $data['porque_text']       ?? null,
            ':porque_bullet1'    => $data['porque_bullet1']    ?? null,
            ':porque_bullet2'    => $data['porque_bullet2']    ?? null,
            ':porque_bullet3'    => $data['porque_bullet3']    ?? null,
            ':porque_media_path' => $data['porque_media_path'] ?? null,
            ':porque_media_type' => $data['porque_media_type'] ?? 'imagen',

            ':test1_name'        => $data['test1_name']        ?? null,
            ':test1_text'        => $data['test1_text']        ?? null,
            ':test1_city'        => $data['test1_city']        ?? null,
            ':test1_photo_path'  => $data['test1_photo_path']  ?? null,
            ':test1_banner_path' => $data['test1_banner_path'] ?? null,
            ':test2_name'        => $data['test2_name']        ?? null,
            ':test2_text'        => $data['test2_text']        ?? null,
            ':test2_city'        => $data['test2_city']        ?? null,
            ':test2_photo_path'  => $data['test2_photo_path']  ?? null,
            ':test2_banner_path' => $data['test2_banner_path'] ?? null,
            ':test3_name'        => $data['test3_name']        ?? null,
            ':test3_text'        => $data['test3_text']        ?? null,
            ':test3_city'        => $data['test3_city']        ?? null,
            ':test3_photo_path'  => $data['test3_photo_path']  ?? null,
            ':test3_banner_path' => $data['test3_banner_path'] ?? null,

            ':faq1_q' => $data['faq1_q'] ?? null,
            ':faq1_a' => $data['faq1_a'] ?? null,
            ':faq2_q' => $data['faq2_q'] ?? null,
            ':faq2_a' => $data['faq2_a'] ?? null,
            ':faq3_q' => $data['faq3_q'] ?? null,
            ':faq3_a' => $data['faq3_a'] ?? null,
            ':faq4_q' => $data['faq4_q'] ?? null,
            ':faq4_a' => $data['faq4_a'] ?? null,
            ':faq5_q' => $data['faq5_q'] ?? null,
            ':faq5_a' => $data['faq5_a'] ?? null,
            ':faq6_q' => $data['faq6_q'] ?? null,
            ':faq6_a' => $data['faq6_a'] ?? null,

            ':footer_text'  => $data['footer_text'] ?? null,
            ':show_footer'  => $data['show_footer']  ?? 1,

            ':cta_benefits_text'       => $data['cta_benefits_text']       ?? null,
            ':cta_benefits_button'     => $data['cta_benefits_button']     ?? null,
            ':cta_gallery_text'        => $data['cta_gallery_text']        ?? null,
            ':cta_gallery_button'      => $data['cta_gallery_button']      ?? null,
            ':cta_porque_text'         => $data['cta_porque_text']         ?? null,
            ':cta_porque_button'       => $data['cta_porque_button']       ?? null,
            ':cta_testimonials_text'   => $data['cta_testimonials_text']   ?? null,
            ':cta_testimonials_button' => $data['cta_testimonials_button'] ?? null,
            ':cta_faq_text'            => $data['cta_faq_text']            ?? null,
            ':cta_faq_button'          => $data['cta_faq_button']          ?? null,
            ':cta_sticky_mobile_text'  => $data['cta_sticky_mobile_text']  ?? null,

            ':wa_enabled'     => isset($data['wa_enabled']) ? (int)$data['wa_enabled'] : 1,
            ':wa_title'       => $data['wa_title']       ?? null,
            ':wa_subtitle'    => $data['wa_subtitle']    ?? null,
            ':wa_footer_note' => $data['wa_footer_note'] ?? null,

            ':wa1_name'       => $data['wa1_name']       ?? null,
            ':wa1_time'       => $data['wa1_time']       ?? null,
            ':wa1_text'       => $data['wa1_text']       ?? null,
            ':wa1_image_path' => $data['wa1_image_path'] ?? null,
            ':wa2_name'       => $data['wa2_name']       ?? null,
            ':wa2_time'       => $data['wa2_time']       ?? null,
            ':wa2_text'       => $data['wa2_text']       ?? null,
            ':wa2_image_path' => $data['wa2_image_path'] ?? null,
            ':wa3_name'       => $data['wa3_name']       ?? null,
            ':wa3_time'       => $data['wa3_time']       ?? null,
            ':wa3_text'       => $data['wa3_text']       ?? null,
            ':wa3_image_path' => $data['wa3_image_path'] ?? null,
            ':wa4_name'       => $data['wa4_name']       ?? null,
            ':wa4_time'       => $data['wa4_time']       ?? null,
            ':wa4_text'       => $data['wa4_text']       ?? null,
            ':wa4_image_path' => $data['wa4_image_path'] ?? null,
            ':wa5_name'       => $data['wa5_name']       ?? null,
            ':wa5_time'       => $data['wa5_time']       ?? null,
            ':wa5_text'       => $data['wa5_text']       ?? null,
            ':wa5_image_path' => $data['wa5_image_path'] ?? null,

            ':primary_color'    => $data['primary_color']    ?? null,
            ':secondary_color'  => $data['secondary_color']  ?? null,
            ':accent_color'     => $data['accent_color']     ?? null,
            ':background_color' => $data['background_color'] ?? null,
            ':text_color'       => $data['text_color']       ?? null,

            ':color_gold'       => $data['color_gold']       ?? null,
            ':color_gold_light' => $data['color_gold_light'] ?? null,
            ':color_success'    => $data['color_success']    ?? null,
            ':color_countdown'  => $data['color_countdown']  ?? null,
            ':color_bg_card'    => $data['color_bg_card']    ?? null,
            ':color_border'     => $data['color_border']     ?? null,

            ':combo_enabled' => isset($data['combo_enabled']) ? (int)$data['combo_enabled'] : 0,
            ':combo_price_2' => isset($data['combo_price_2']) ? (int)$data['combo_price_2'] : 0,

            ':antes_path'          => $data['antes_path']          ?? null,
            ':despues_path'        => $data['despues_path']        ?? null,
            ':antes_label'         => $data['antes_label']         ?? null,
            ':despues_label'       => $data['despues_label']       ?? null,
            ':antes_despues_title' => $data['antes_despues_title'] ?? null,

            ':para_quien_si_1' => $data['para_quien_si_1'] ?? null,
            ':para_quien_si_2' => $data['para_quien_si_2'] ?? null,
            ':para_quien_si_3' => $data['para_quien_si_3'] ?? null,
            ':para_quien_si_4' => $data['para_quien_si_4'] ?? null,
            ':para_quien_no_1' => $data['para_quien_no_1'] ?? null,
            ':para_quien_no_2' => $data['para_quien_no_2'] ?? null,
            ':para_quien_no_3' => $data['para_quien_no_3'] ?? null,

            ':wa_phone'            => $data['wa_phone']            ?? null,
            ':hero_badge_stars'    => $data['hero_badge_stars']    ?? null,
            ':hero_badge_customers'=> $data['hero_badge_customers']?? null,
            ':urgency_stock'       => isset($data['urgency_stock'])      ? (int)$data['urgency_stock']      : 12,
            ':countdown_minutes'   => isset($data['countdown_minutes'])  ? (int)$data['countdown_minutes']  : 25,

            ':comparison_img_without' => $data['comparison_img_without'] ?? null,
            ':comparison_img_with'    => $data['comparison_img_with']    ?? null,

            ':comparison_title'          => $data['comparison_title']          ?? null,
            ':comparison_label_without'  => $data['comparison_label_without']  ?? null,
            ':comparison_label_with'     => $data['comparison_label_with']     ?? null,
            ':comparison_1_without' => $data['comparison_1_without'] ?? null,
            ':comparison_1_with'    => $data['comparison_1_with']    ?? null,
            ':comparison_2_without' => $data['comparison_2_without'] ?? null,
            ':comparison_2_with'    => $data['comparison_2_with']    ?? null,
            ':comparison_3_without' => $data['comparison_3_without'] ?? null,
            ':comparison_3_with'    => $data['comparison_3_with']    ?? null,
            ':comparison_4_without' => $data['comparison_4_without'] ?? null,
            ':comparison_4_with'    => $data['comparison_4_with']    ?? null,
            ':comparison_5_without' => $data['comparison_5_without'] ?? null,
            ':comparison_5_with'    => $data['comparison_5_with']    ?? null,

            ':authority_enabled'    => isset($data['authority_enabled']) ? (int)$data['authority_enabled'] : 0,
            ':authority_title'      => $data['authority_title']      ?? null,
            ':authority_years'      => $data['authority_years']      ?? null,
            ':authority_deliveries' => $data['authority_deliveries'] ?? null,
            ':authority_rating'     => $data['authority_rating']     ?? null,
            ':authority_guarantee'  => $data['authority_guarantee']  ?? null,

            ':show_wa_testimonios' => isset($data['show_wa_testimonios']) ? (int)$data['show_wa_testimonios'] : 1,

            ':show_benefits'      => isset($data['show_benefits'])      ? (int)$data['show_benefits']      : 1,
            ':show_gallery'       => isset($data['show_gallery'])       ? (int)$data['show_gallery']       : 1,
            ':show_antes_despues' => isset($data['show_antes_despues']) ? (int)$data['show_antes_despues'] : 1,
            ':show_como_funciona' => isset($data['show_como_funciona']) ? (int)$data['show_como_funciona'] : 1,
            ':show_countdown'     => isset($data['show_countdown'])     ? (int)$data['show_countdown']     : 1,
            ':show_porque'        => isset($data['show_porque'])        ? (int)$data['show_porque']        : 1,
            ':show_para_quien'    => isset($data['show_para_quien'])    ? (int)$data['show_para_quien']    : 1,
            ':show_testimonios'   => isset($data['show_testimonios'])   ? (int)$data['show_testimonios']   : 1,
            ':show_faqs'          => isset($data['show_faqs'])          ? (int)$data['show_faqs']          : 1,

            ':section_order' => $data['section_order'] ?? null,

            ':gallery_title'     => $data['gallery_title']     ?? null,
            ':testimonios_title' => $data['testimonios_title'] ?? null,
            ':para_quien_title'  => $data['para_quien_title']  ?? null,
            ':faq_title'         => $data['faq_title']         ?? null,

            ':announcement_item_1' => $data['announcement_item_1'] ?? null,
            ':announcement_item_2' => $data['announcement_item_2'] ?? null,
            ':announcement_item_3' => $data['announcement_item_3'] ?? null,
            ':announcement_item_4' => $data['announcement_item_4'] ?? null,
            ':announcement_item_5' => $data['announcement_item_5'] ?? null,
            ':announcement_item_6' => $data['announcement_item_6'] ?? null,

            ':hero_trust_1' => $data['hero_trust_1'] ?? null,
            ':hero_trust_2' => $data['hero_trust_2'] ?? null,
            ':hero_trust_3' => $data['hero_trust_3'] ?? null,

            ':cf_title'       => $data['cf_title']       ?? null,
            ':cf_step1_icon'  => $data['cf_step1_icon']  ?? null,
            ':cf_step1_title' => $data['cf_step1_title'] ?? null,
            ':cf_step1_desc'  => $data['cf_step1_desc']  ?? null,
            ':cf_step2_icon'  => $data['cf_step2_icon']  ?? null,
            ':cf_step2_title' => $data['cf_step2_title'] ?? null,
            ':cf_step2_desc'  => $data['cf_step2_desc']  ?? null,
            ':cf_step3_icon'  => $data['cf_step3_icon']  ?? null,
            ':cf_step3_title' => $data['cf_step3_title'] ?? null,
            ':cf_step3_desc'  => $data['cf_step3_desc']  ?? null,

            ':show_garantia'  => isset($data['show_garantia'])  ? (int)$data['show_garantia']  : 1,
            ':garantia_title' => $data['garantia_title'] ?? null,
            ':garantia_desc'  => $data['garantia_desc']  ?? null,
            ':garantia_item1' => $data['garantia_item1'] ?? null,
            ':garantia_item2' => $data['garantia_item2'] ?? null,
            ':garantia_item3' => $data['garantia_item3'] ?? null,
            ':garantia_item4' => $data['garantia_item4'] ?? null,

            ':show_trust_strip' => isset($data['show_trust_strip']) ? (int)$data['show_trust_strip'] : 1,

            ':show_announcement_bar' => isset($data['show_announcement_bar']) ? (int)$data['show_announcement_bar'] : 1,
            ':show_sticky_bar'       => isset($data['show_sticky_bar'])       ? (int)$data['show_sticky_bar']       : 1,
            ':show_comparison'       => isset($data['show_comparison'])       ? (int)$data['show_comparison']       : 1,
            ':show_resumen_oferta'   => isset($data['show_resumen_oferta'])   ? (int)$data['show_resumen_oferta']   : 1,
            ':show_cta_sticky'       => isset($data['show_cta_sticky'])       ? (int)$data['show_cta_sticky']       : 1,
            ':show_whatsapp_btn'     => isset($data['show_whatsapp_btn'])     ? (int)$data['show_whatsapp_btn']     : 1,
            ':show_fomo'             => isset($data['show_fomo'])             ? (int)$data['show_fomo']             : 1,
            ':show_exit_popup'       => isset($data['show_exit_popup'])       ? (int)$data['show_exit_popup']       : 1,

            ':show_cta_benefits'          => isset($data['show_cta_benefits'])          ? (int)$data['show_cta_benefits']          : 1,
            ':show_cta_gallery'           => isset($data['show_cta_gallery'])           ? (int)$data['show_cta_gallery']           : 1,
            ':show_cta_porque'            => isset($data['show_cta_porque'])            ? (int)$data['show_cta_porque']            : 1,
            ':show_cta_testimonials'      => isset($data['show_cta_testimonials'])      ? (int)$data['show_cta_testimonials']      : 1,
            ':show_cta_faq'               => isset($data['show_cta_faq'])               ? (int)$data['show_cta_faq']               : 1,
            ':show_cta_como_funciona'     => isset($data['show_cta_como_funciona'])     ? (int)$data['show_cta_como_funciona']     : 1,
            ':cta_como_funciona_text'     => $data['cta_como_funciona_text']     ?? null,
            ':cta_como_funciona_button'   => $data['cta_como_funciona_button']   ?? null,
            ':show_cta_comparison'        => isset($data['show_cta_comparison'])        ? (int)$data['show_cta_comparison']        : 1,
            ':cta_comparison_button'      => $data['cta_comparison_button']      ?? null,
            ':show_cta_para_quien'        => isset($data['show_cta_para_quien'])        ? (int)$data['show_cta_para_quien']        : 1,
            ':cta_para_quien_button'      => $data['cta_para_quien_button']      ?? null,
            ':show_cta_wa_testimonios'    => isset($data['show_cta_wa_testimonios'])    ? (int)$data['show_cta_wa_testimonios']    : 1,
            ':cta_wa_testimonios_button'  => $data['cta_wa_testimonios_button']  ?? null,

            ':show_caracteristicas'  => isset($data['show_caracteristicas'])  ? (int)$data['show_caracteristicas']  : 1,
            ':caract_section_title'  => $data['caract_section_title']  ?? null,
            ':caract1_active'        => isset($data['caract1_active'])  ? (int)$data['caract1_active']  : 1,
            ':caract1_media_path'    => $data['caract1_media_path']    ?? null,
            ':caract1_media_type'    => $data['caract1_media_type']    ?? 'image',
            ':caract1_title'         => $data['caract1_title']         ?? null,
            ':caract1_text'          => $data['caract1_text']          ?? null,
            ':caract2_active'        => isset($data['caract2_active'])  ? (int)$data['caract2_active']  : 1,
            ':caract2_media_path'    => $data['caract2_media_path']    ?? null,
            ':caract2_media_type'    => $data['caract2_media_type']    ?? 'image',
            ':caract2_title'         => $data['caract2_title']         ?? null,
            ':caract2_text'          => $data['caract2_text']          ?? null,
            ':caract3_active'        => isset($data['caract3_active'])  ? (int)$data['caract3_active']  : 1,
            ':caract3_media_path'    => $data['caract3_media_path']    ?? null,
            ':caract3_media_type'    => $data['caract3_media_type']    ?? 'image',
            ':caract3_title'         => $data['caract3_title']         ?? null,
            ':caract3_text'          => $data['caract3_text']          ?? null,
            ':caract4_active'        => isset($data['caract4_active'])  ? (int)$data['caract4_active']  : 1,
            ':caract4_media_path'    => $data['caract4_media_path']    ?? null,
            ':caract4_media_type'    => $data['caract4_media_type']    ?? 'image',
            ':caract4_title'         => $data['caract4_title']         ?? null,
            ':caract4_text'          => $data['caract4_text']          ?? null,

            ':form_title'    => $data['form_title']    ?? null,
            ':form_subtitle' => $data['form_subtitle'] ?? null,

            ':regalo_image_path' => $data['regalo_image_path'] ?? null,
            ':regalo_label'      => $data['regalo_label']      ?? null,
            ':show_regalo'       => isset($data['show_regalo'])     ? (int)$data['show_regalo']     : 1,
            ':show_price_box'    => isset($data['show_price_box'])  ? (int)$data['show_price_box']  : 1,
            ':color_variants'    => $data['color_variants'] ?? null,

            ':pixel_id'   => $data['pixel_id']   ?? null,
            ':clarity_id' => $data['clarity_id'] ?? null,

            ':producto_id' => $productoId,
        ]);

        if (!$ok) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error al guardar landing_config: " . implode(' | ', $errorInfo));
        }

        return $ok;
    }
}
