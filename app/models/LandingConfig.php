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
        // Nos aseguramos de que exista la fila
        $this->asegurarFilaProducto($productoId);

        $sql = "UPDATE landing_config
                SET hero_title         = :hero_title,
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

                    gallery_1_path = :gallery_1_path,
                    gallery_2_path = :gallery_2_path,
                    gallery_3_path = :gallery_3_path,

                    countdown_title = :countdown_title,
                    countdown_text  = :countdown_text,

                    porque_title      = :porque_title,
                    porque_text       = :porque_text,
                    porque_bullet1    = :porque_bullet1,
                    porque_bullet2    = :porque_bullet2,
                    porque_bullet3    = :porque_bullet3,
                    porque_media_path = :porque_media_path,

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

                    primary_color           = :primary_color,
                    secondary_color         = :secondary_color,
                    accent_color            = :accent_color,
                    background_color        = :background_color,
                    text_color              = :text_color

                WHERE producto_id = :producto_id";

        $stmt = $this->db->prepare($sql);

        $ok = $stmt->execute([
            ':hero_title'         => $data['hero_title'] ?? null,
            ':hero_subtitle'      => $data['hero_subtitle'] ?? null,
            ':hero_note'          => $data['hero_note'] ?? null,
            ':hero_button_text'   => $data['hero_button_text'] ?? null,
            ':hero_media_type'    => $data['hero_media_type'] ?? null,
            ':hero_media_path'    => $data['hero_media_path'] ?? null,

            ':benefits_title'      => $data['benefits_title'] ?? null,
            ':benefit_1'           => $data['benefit_1'] ?? null,
            ':benefit_2'           => $data['benefit_2'] ?? null,
            ':benefit_3'           => $data['benefit_3'] ?? null,
            ':benefit_4'           => $data['benefit_4'] ?? null,
            ':benefits_media_path' => $data['benefits_media_path'] ?? null,

            ':gallery_1_path' => $data['gallery_1_path'] ?? null,
            ':gallery_2_path' => $data['gallery_2_path'] ?? null,
            ':gallery_3_path' => $data['gallery_3_path'] ?? null,

            ':countdown_title' => $data['countdown_title'] ?? null,
            ':countdown_text'  => $data['countdown_text'] ?? null,

            ':porque_title'      => $data['porque_title'] ?? null,
            ':porque_text'       => $data['porque_text'] ?? null,
            ':porque_bullet1'    => $data['porque_bullet1'] ?? null,
            ':porque_bullet2'    => $data['porque_bullet2'] ?? null,
            ':porque_bullet3'    => $data['porque_bullet3'] ?? null,
            ':porque_media_path' => $data['porque_media_path'] ?? null,

            ':test1_name'       => $data['test1_name'] ?? null,
            ':test1_text'       => $data['test1_text'] ?? null,
            ':test1_photo_path' => $data['test1_photo_path'] ?? null,
            ':test2_name'       => $data['test2_name'] ?? null,
            ':test2_text'       => $data['test2_text'] ?? null,
            ':test2_photo_path' => $data['test2_photo_path'] ?? null,
            ':test3_name'       => $data['test3_name'] ?? null,
            ':test3_text'       => $data['test3_text'] ?? null,
            ':test3_photo_path' => $data['test3_photo_path'] ?? null,

            ':faq1_q' => $data['faq1_q'] ?? null,
            ':faq1_a' => $data['faq1_a'] ?? null,
            ':faq2_q' => $data['faq2_q'] ?? null,
            ':faq2_a' => $data['faq2_a'] ?? null,
            ':faq3_q' => $data['faq3_q'] ?? null,
            ':faq3_a' => $data['faq3_a'] ?? null,

            ':footer_text' => $data['footer_text'] ?? null,

            ':cta_benefits_text'       => $data['cta_benefits_text'] ?? null,
            ':cta_benefits_button'     => $data['cta_benefits_button'] ?? null,
            ':cta_gallery_text'        => $data['cta_gallery_text'] ?? null,
            ':cta_gallery_button'      => $data['cta_gallery_button'] ?? null,
            ':cta_porque_text'         => $data['cta_porque_text'] ?? null,
            ':cta_porque_button'       => $data['cta_porque_button'] ?? null,
            ':cta_testimonials_text'   => $data['cta_testimonials_text'] ?? null,
            ':cta_testimonials_button' => $data['cta_testimonials_button'] ?? null,
            ':cta_faq_text'            => $data['cta_faq_text'] ?? null,
            ':cta_faq_button'          => $data['cta_faq_button'] ?? null,
            ':cta_sticky_mobile_text'  => $data['cta_sticky_mobile_text'] ?? null,

            ':primary_color'           => $data['primary_color'] ?? null,
            ':secondary_color'         => $data['secondary_color'] ?? null,
            ':accent_color'            => $data['accent_color'] ?? null,
            ':background_color'        => $data['background_color'] ?? null,
            ':text_color'              => $data['text_color'] ?? null,

            ':producto_id' => $productoId,
        ]);

        // Si algo falla, que reviente con mensaje claro
        if (!$ok) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error al guardar landing_config: " . implode(' | ', $errorInfo));
        }

        return $ok;
    }
}
