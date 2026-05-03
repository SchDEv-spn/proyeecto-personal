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

                test1_name       = :test1_name,
                test1_text       = :test1_text,
                test1_photo_path = :test1_photo_path,
                test2_name       = :test2_name,
                test2_text       = :test2_text,
                test2_photo_path = :test2_photo_path,
                test3_name       = :test3_name,
                test3_text       = :test3_text,
                test3_photo_path = :test3_photo_path,

                faq1_q = :faq1_q,
                faq1_a = :faq1_a,
                faq2_q = :faq2_q,
                faq2_a = :faq2_a,
                faq3_q = :faq3_q,
                faq3_a = :faq3_a,

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
                combo_price_2 = :combo_price_2

            WHERE producto_id = :producto_id";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':theme'           => in_array($data['theme'] ?? '', [
                'dark-luxury',
                'light-luxury',
                'bold-conversion',
                'minimal-clean'
            ], true) ? $data['theme'] : 'dark-luxury',

            ':hero_title'         => $data['hero_title']       ?? null,
            ':hero_subtitle'      => $data['hero_subtitle']    ?? null,
            ':hero_note'          => $data['hero_note']        ?? null,
            ':hero_button_text'   => $data['hero_button_text'] ?? null,
            ':hero_media_type'    => $data['hero_media_type']  ?? null,
            ':hero_media_path'    => $data['hero_media_path']  ?? null,

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

            ':test1_name'       => $data['test1_name']       ?? null,
            ':test1_text'       => $data['test1_text']       ?? null,
            ':test1_photo_path' => $data['test1_photo_path'] ?? null,
            ':test2_name'       => $data['test2_name']       ?? null,
            ':test2_text'       => $data['test2_text']       ?? null,
            ':test2_photo_path' => $data['test2_photo_path'] ?? null,
            ':test3_name'       => $data['test3_name']       ?? null,
            ':test3_text'       => $data['test3_text']       ?? null,
            ':test3_photo_path' => $data['test3_photo_path'] ?? null,

            ':faq1_q' => $data['faq1_q'] ?? null,
            ':faq1_a' => $data['faq1_a'] ?? null,
            ':faq2_q' => $data['faq2_q'] ?? null,
            ':faq2_a' => $data['faq2_a'] ?? null,
            ':faq3_q' => $data['faq3_q'] ?? null,
            ':faq3_a' => $data['faq3_a'] ?? null,

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

            ':producto_id' => $productoId,
        ]);

        if (!$ok) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error al guardar landing_config: " . implode(' | ', $errorInfo));
        }

        return $ok;
    }
}
