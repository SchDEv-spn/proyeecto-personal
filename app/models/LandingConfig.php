<?php

class LandingConfig extends Model
{
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

    public function guardarPorProducto(int $productoId, array $data)
    {
        $this->asegurarFilaProducto($productoId);

        $sql = "UPDATE landing_config
            SET theme              = :theme,

                hero_title         = :hero_title,
                hero_subtitle      = :hero_subtitle,
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
                test1_photo_path  = :test1_photo_path,
                test1_banner_path = :test1_banner_path,
                test2_name        = :test2_name,
                test2_text        = :test2_text,
                test2_photo_path  = :test2_photo_path,
                test2_banner_path = :test2_banner_path,
                test3_name        = :test3_name,
                test3_text        = :test3_text,
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

                footer_text = :footer_text,

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

                form_title    = :form_title,
                form_subtitle = :form_subtitle

            WHERE producto_id = :producto_id";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':theme'           => in_array($data['theme'] ?? '', [
                'dark-luxury',
                'light-luxury',
                'bold-conversion',
                'minimal-clean',
                'femme-rose',
                'natural-sage',
            ], true) ? $data['theme'] : 'dark-luxury',

            ':hero_title'         => $data['hero_title']       ?? null,
            ':hero_subtitle'      => $data['hero_subtitle']    ?? null,
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
            ':test1_photo_path'  => $data['test1_photo_path']  ?? null,
            ':test1_banner_path' => $data['test1_banner_path'] ?? null,
            ':test2_name'        => $data['test2_name']        ?? null,
            ':test2_text'        => $data['test2_text']        ?? null,
            ':test2_photo_path'  => $data['test2_photo_path']  ?? null,
            ':test2_banner_path' => $data['test2_banner_path'] ?? null,
            ':test3_name'        => $data['test3_name']        ?? null,
            ':test3_text'        => $data['test3_text']        ?? null,
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

            ':footer_text' => $data['footer_text'] ?? null,

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

            ':form_title'    => $data['form_title']    ?? null,
            ':form_subtitle' => $data['form_subtitle'] ?? null,

            ':producto_id' => $productoId,
        ]);

        if (!$ok) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error al guardar landing_config: " . implode(' | ', $errorInfo));
        }

        return $ok;
    }
}
