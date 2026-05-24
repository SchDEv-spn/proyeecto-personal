<?php

class AdminLandingController extends Controller
{
    private function requireLogin()
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: " . BASE_URL . "/Auth/login");
            exit;
        }
    }

    public function index()
    {
        $this->requireLogin();

        $productoId = (int)($_GET['producto_id'] ?? 1);
        if ($productoId <= 0) $productoId = 1;

        $configModel = new LandingConfig();
        $config = $configModel->obtenerPorProducto($productoId);

        if (!$config) {
            $configModel->crearPorProducto($productoId);
            $config = $configModel->obtenerPorProducto($productoId);
        }

        $productoModel  = new Producto();
        $productos      = $productoModel->obtenerTodos();
        $productoActual = $productoModel->obtenerPorId($productoId);

        $success = '';
        if (!empty($_SESSION['admin_landing_success'])) {
            $success = $_SESSION['admin_landing_success'];
            unset($_SESSION['admin_landing_success']);
        }

        $this->view('admin/landing/index', [
            'config'      => $config,
            'success'     => $success,
            'producto_id' => $productoId,
            'productos'   => $productos,
            'producto'    => $productoActual,
        ]);
    }

    public function guardar()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminLanding/index");
            exit;
        }

        $productoId = (int)($_POST['producto_id'] ?? 1);
        if ($productoId <= 0) $productoId = 1;

        // ===== COMBOS (landing_config) =====
        $comboEnabled = (isset($_POST['combo_enabled']) && (string)$_POST['combo_enabled'] === '1') ? 1 : 0;
        $comboPrice2  = (int)($_POST['combo_price_2'] ?? 0);
        if ($comboPrice2 < 0) $comboPrice2 = 0;

        // 1. Textos
        $data = [
            'hero_title'       => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle'    => trim($_POST['hero_subtitle']   ?? ''),
            'hero_subtitle_2'  => trim($_POST['hero_subtitle_2'] ?? ''),
            'hero_subtitle_3'  => trim($_POST['hero_subtitle_3'] ?? ''),
            'hero_note'        => trim($_POST['hero_note'] ?? ''),
            'hero_button_text' => trim($_POST['hero_button_text'] ?? ''),
            'hero_media_type'  => trim($_POST['hero_media_type'] ?? 'imagen'),

            'benefits_title' => trim($_POST['benefits_title'] ?? ''),
            'benefits_media_type' => in_array(
                trim($_POST['benefits_media_type'] ?? 'imagen'),
                ['imagen', 'video', 'gif'],
                true
            ) ? trim($_POST['benefits_media_type']) : 'imagen',
            'benefit_1'      => trim($_POST['benefit_1'] ?? ''),
            'benefit_2'      => trim($_POST['benefit_2'] ?? ''),
            'benefit_3'      => trim($_POST['benefit_3'] ?? ''),
            'benefit_4'      => trim($_POST['benefit_4'] ?? ''),

            'countdown_title' => trim($_POST['countdown_title'] ?? ''),
            'countdown_text'  => trim($_POST['countdown_text'] ?? ''),

            'porque_title'   => trim($_POST['porque_title'] ?? ''),
            'porque_media_type' => in_array(
                trim($_POST['porque_media_type'] ?? 'imagen'),
                ['imagen', 'video', 'gif'],
                true
            ) ? trim($_POST['porque_media_type']) : 'imagen',
            'porque_text'    => trim($_POST['porque_text'] ?? ''),
            'porque_bullet1' => trim($_POST['porque_bullet1'] ?? ''),
            'porque_bullet2' => trim($_POST['porque_bullet2'] ?? ''),
            'porque_bullet3' => trim($_POST['porque_bullet3'] ?? ''),

            'test1_name' => trim($_POST['test1_name'] ?? ''),
            'test1_text' => trim($_POST['test1_text'] ?? ''),
            'test2_name' => trim($_POST['test2_name'] ?? ''),
            'test2_text' => trim($_POST['test2_text'] ?? ''),
            'test3_name' => trim($_POST['test3_name'] ?? ''),
            'test3_text' => trim($_POST['test3_text'] ?? ''),

            'faq1_q' => trim($_POST['faq1_q'] ?? ''),
            'faq1_a' => trim($_POST['faq1_a'] ?? ''),
            'faq2_q' => trim($_POST['faq2_q'] ?? ''),
            'faq2_a' => trim($_POST['faq2_a'] ?? ''),
            'faq3_q' => trim($_POST['faq3_q'] ?? ''),
            'faq3_a' => trim($_POST['faq3_a'] ?? ''),
            'faq4_q' => trim($_POST['faq4_q'] ?? ''),
            'faq4_a' => trim($_POST['faq4_a'] ?? ''),
            'faq5_q' => trim($_POST['faq5_q'] ?? ''),
            'faq5_a' => trim($_POST['faq5_a'] ?? ''),
            'faq6_q' => trim($_POST['faq6_q'] ?? ''),
            'faq6_a' => trim($_POST['faq6_a'] ?? ''),

            'footer_text'  => trim($_POST['footer_text'] ?? ''),
            'show_footer'  => (int)($_POST['show_footer'] ?? 1),

            'cta_benefits_text'       => trim($_POST['cta_benefits_text'] ?? ''),
            'cta_benefits_button'     => trim($_POST['cta_benefits_button'] ?? ''),
            'cta_gallery_text'        => trim($_POST['cta_gallery_text'] ?? ''),
            'cta_gallery_button'      => trim($_POST['cta_gallery_button'] ?? ''),
            'cta_porque_text'         => trim($_POST['cta_porque_text'] ?? ''),
            'cta_porque_button'       => trim($_POST['cta_porque_button'] ?? ''),
            'cta_testimonials_text'   => trim($_POST['cta_testimonials_text'] ?? ''),
            'cta_testimonials_button' => trim($_POST['cta_testimonials_button'] ?? ''),
            'cta_faq_text'            => trim($_POST['cta_faq_text'] ?? ''),
            'cta_faq_button'          => trim($_POST['cta_faq_button'] ?? ''),
            'cta_sticky_mobile_text'  => trim($_POST['cta_sticky_mobile_text'] ?? ''),

            // ===== WhatsApp Testimonios =====
            'wa_enabled'     => (isset($_POST['wa_enabled']) && (string)($_POST['wa_enabled']) !== '0') ? 1 : 0,
            'wa_title'       => trim($_POST['wa_title'] ?? ''),
            'wa_subtitle'    => trim($_POST['wa_subtitle'] ?? ''),
            'wa_footer_note' => trim($_POST['wa_footer_note'] ?? ''),

            // ===== Antes y Después (legacy, kept for compat) =====
            'antes_label'         => trim($_POST['antes_label']         ?? 'Antes'),
            'despues_label'       => trim($_POST['despues_label']       ?? 'Después'),
            'antes_despues_title' => trim($_POST['antes_despues_title'] ?? 'Mira la diferencia'),

            // ===== Características =====
            'show_caracteristicas' => (int)($_POST['show_caracteristicas'] ?? 1),
            'caract_section_title' => trim($_POST['caract_section_title'] ?? ''),

            // ===== Para quién es =====
            'para_quien_si_1' => trim($_POST['para_quien_si_1'] ?? ''),
            'para_quien_si_2' => trim($_POST['para_quien_si_2'] ?? ''),
            'para_quien_si_3' => trim($_POST['para_quien_si_3'] ?? ''),
            'para_quien_si_4' => trim($_POST['para_quien_si_4'] ?? ''),
            'para_quien_no_1' => trim($_POST['para_quien_no_1'] ?? ''),
            'para_quien_no_2' => trim($_POST['para_quien_no_2'] ?? ''),
            'para_quien_no_3' => trim($_POST['para_quien_no_3'] ?? ''),

            // ===== WhatsApp flotante =====
            'wa_phone' => preg_replace('/\D/', '', trim($_POST['wa_phone'] ?? '573023959721')),

            // ===== Hero badge =====
            'hero_badge_stars'    => trim($_POST['hero_badge_stars']    ?? '4.9'),
            'hero_badge_customers'=> trim($_POST['hero_badge_customers']?? '+3.200 clientes felices'),

            // ===== Urgencia =====
            'urgency_stock'     => max(1, (int)($_POST['urgency_stock']     ?? 12)),
            'countdown_minutes' => max(1, (int)($_POST['countdown_minutes'] ?? 25)),

            // ===== Tabla comparativa =====
            'comparison_title'          => trim($_POST['comparison_title']          ?? ''),
            'comparison_label_without'  => trim($_POST['comparison_label_without']  ?? ''),
            'comparison_label_with'     => trim($_POST['comparison_label_with']     ?? ''),
            'comparison_1_without' => trim($_POST['comparison_1_without'] ?? ''),
            'comparison_1_with'    => trim($_POST['comparison_1_with']    ?? ''),
            'comparison_2_without' => trim($_POST['comparison_2_without'] ?? ''),
            'comparison_2_with'    => trim($_POST['comparison_2_with']    ?? ''),
            'comparison_3_without' => trim($_POST['comparison_3_without'] ?? ''),
            'comparison_3_with'    => trim($_POST['comparison_3_with']    ?? ''),
            'comparison_4_without' => trim($_POST['comparison_4_without'] ?? ''),
            'comparison_4_with'    => trim($_POST['comparison_4_with']    ?? ''),
            'comparison_5_without' => trim($_POST['comparison_5_without'] ?? ''),
            'comparison_5_with'    => trim($_POST['comparison_5_with']    ?? ''),

            // ===== Autoridad =====
            'authority_enabled'    => isset($_POST['authority_enabled']) ? 1 : 0,
            'authority_title'      => trim($_POST['authority_title']      ?? ''),
            'authority_years'      => trim($_POST['authority_years']      ?? ''),
            'authority_deliveries' => trim($_POST['authority_deliveries'] ?? ''),
            'authority_rating'     => trim($_POST['authority_rating']     ?? ''),
            'authority_guarantee'  => trim($_POST['authority_guarantee']  ?? ''),

            // ✅ COMBOS
            'combo_enabled' => $comboEnabled,
            'combo_price_2' => $comboPrice2,

            // ===== Secciones visibles + orden =====
            'section_order'        => trim($_POST['section_order'] ?? ''),
            'show_benefits'        => (int)($_POST['show_benefits']        ?? 1),
            'show_gallery'         => (int)($_POST['show_gallery']         ?? 1),
            'show_antes_despues'   => (int)($_POST['show_antes_despues']   ?? 1),
            'show_como_funciona'   => (int)($_POST['show_como_funciona']   ?? 1),
            'show_countdown'     => (int)($_POST['show_countdown']     ?? 1),
            'show_porque'        => (int)($_POST['show_porque']        ?? 1),
            'show_para_quien'    => (int)($_POST['show_para_quien']    ?? 1),
            'show_testimonios'   => (int)($_POST['show_testimonios']   ?? 1),
            'show_faqs'          => (int)($_POST['show_faqs']          ?? 1),

            // ===== Section titles =====
            'gallery_title'     => trim($_POST['gallery_title']     ?? ''),
            'testimonios_title' => trim($_POST['testimonios_title'] ?? ''),
            'para_quien_title'  => trim($_POST['para_quien_title']  ?? ''),
            'faq_title'         => trim($_POST['faq_title']         ?? ''),

            // ===== Hero trust row =====
            'hero_trust_1' => trim($_POST['hero_trust_1'] ?? ''),
            'hero_trust_2' => trim($_POST['hero_trust_2'] ?? ''),
            'hero_trust_3' => trim($_POST['hero_trust_3'] ?? ''),

            // ===== Cómo funciona steps =====
            'cf_title'       => trim($_POST['cf_title']       ?? ''),
            'cf_step1_icon'  => trim($_POST['cf_step1_icon']  ?? ''),
            'cf_step1_title' => trim($_POST['cf_step1_title'] ?? ''),
            'cf_step1_desc'  => trim($_POST['cf_step1_desc']  ?? ''),
            'cf_step2_icon'  => trim($_POST['cf_step2_icon']  ?? ''),
            'cf_step2_title' => trim($_POST['cf_step2_title'] ?? ''),
            'cf_step2_desc'  => trim($_POST['cf_step2_desc']  ?? ''),
            'cf_step3_icon'  => trim($_POST['cf_step3_icon']  ?? ''),
            'cf_step3_title' => trim($_POST['cf_step3_title'] ?? ''),
            'cf_step3_desc'  => trim($_POST['cf_step3_desc']  ?? ''),

            // ===== Garantía =====
            'show_garantia'  => (int)($_POST['show_garantia']  ?? 1),
            'garantia_title' => trim($_POST['garantia_title']  ?? ''),
            'garantia_desc'  => trim($_POST['garantia_desc']   ?? ''),
            'garantia_item1' => trim($_POST['garantia_item1']  ?? ''),
            'garantia_item2' => trim($_POST['garantia_item2']  ?? ''),
            'garantia_item3' => trim($_POST['garantia_item3']  ?? ''),
            'garantia_item4' => trim($_POST['garantia_item4']  ?? ''),

            // ===== Transportadoras =====
            'show_trust_strip'     => (int)($_POST['show_trust_strip']     ?? 1),
            'show_wa_testimonios'  => (int)($_POST['show_wa_testimonios']  ?? 1),

            // ===== Elementos fijos =====
            'show_sticky_bar'       => (int)($_POST['show_sticky_bar']       ?? 1),
            'show_announcement_bar' => (int)($_POST['show_announcement_bar'] ?? 1),
            'show_comparison'       => (int)($_POST['show_comparison']       ?? 1),
            'show_resumen_oferta'   => (int)($_POST['show_resumen_oferta']   ?? 1),
            'show_cta_sticky'       => (int)($_POST['show_cta_sticky']       ?? 1),
            'show_whatsapp_btn'     => (int)($_POST['show_whatsapp_btn']     ?? 1),
            'show_fomo'             => (int)($_POST['show_fomo']             ?? 1),
            'show_exit_popup'       => (int)($_POST['show_exit_popup']       ?? 1),

            // ===== Form header =====
            'form_title'    => trim($_POST['form_title']    ?? ''),
            'form_subtitle' => trim($_POST['form_subtitle'] ?? ''),
        ];

        // WhatsApp items (1..5)
        for ($i = 1; $i <= 5; $i++) {
            $data["wa{$i}_name"] = trim($_POST["wa{$i}_name"] ?? '');
            $data["wa{$i}_time"] = trim($_POST["wa{$i}_time"] ?? '');
            $data["wa{$i}_text"] = trim($_POST["wa{$i}_text"] ?? '');
        }

        // Características items (1..4)
        for ($i = 1; $i <= 4; $i++) {
            $data["caract{$i}_media_type"] = in_array(
                trim($_POST["caract{$i}_media_type"] ?? 'image'), ['image', 'video', 'gif'], true
            ) ? trim($_POST["caract{$i}_media_type"]) : 'image';
            $data["caract{$i}_title"] = trim($_POST["caract{$i}_title"] ?? '');
            $data["caract{$i}_text"]  = trim($_POST["caract{$i}_text"]  ?? '');
        }

        // CTAs de sección show/hide
        $data['show_cta_benefits']          = (int)($_POST['show_cta_benefits']          ?? 1);
        $data['show_cta_gallery']           = (int)($_POST['show_cta_gallery']           ?? 1);
        $data['show_cta_porque']            = (int)($_POST['show_cta_porque']            ?? 1);
        $data['show_cta_testimonials']      = (int)($_POST['show_cta_testimonials']      ?? 1);
        $data['show_cta_faq']               = (int)($_POST['show_cta_faq']               ?? 1);
        $data['show_cta_como_funciona']     = (int)($_POST['show_cta_como_funciona']     ?? 1);
        $data['cta_como_funciona_text']     = trim($_POST['cta_como_funciona_text']     ?? '');
        $data['cta_como_funciona_button']   = trim($_POST['cta_como_funciona_button']   ?? '');
        $data['show_cta_comparison']        = (int)($_POST['show_cta_comparison']        ?? 1);
        $data['cta_comparison_button']      = trim($_POST['cta_comparison_button']      ?? '');
        $data['show_cta_para_quien']        = (int)($_POST['show_cta_para_quien']        ?? 1);
        $data['cta_para_quien_button']      = trim($_POST['cta_para_quien_button']      ?? '');
        $data['show_cta_wa_testimonios']    = (int)($_POST['show_cta_wa_testimonios']    ?? 1);
        $data['cta_wa_testimonios_button']  = trim($_POST['cta_wa_testimonios_button']  ?? '');

        // Announcement bar items (1..6)
        for ($i = 1; $i <= 6; $i++) {
            $data["announcement_item_{$i}"] = trim($_POST["announcement_item_{$i}"] ?? '');
        }

        // 2. Colores
        $data['primary_color']    = $_POST['primary_color']    ?: null;
        $data['secondary_color']  = $_POST['secondary_color']  ?: null;
        $data['accent_color']     = $_POST['accent_color']     ?: null;
        $data['background_color'] = $_POST['background_color'] ?: null;
        $data['text_color']       = $_POST['text_color']       ?: null;

        // Tema
        $data['theme'] = in_array(
            trim($_POST['theme'] ?? ''),
            ['dark-luxury', 'light-luxury', 'bold-conversion', 'minimal-clean', 'femme-rose', 'natural-sage', 'obsidian', 'blanc-luxe'],
            true
        ) ? trim($_POST['theme']) : 'dark-luxury';

        // Colores extendidos — solo guardar si son hex válidos
        $extendedColors = [
            'color_gold',
            'color_gold_light',
            'color_success',
            'color_countdown',
            'color_bg_card',
            'color_border',
        ];

        foreach ($extendedColors as $key) {
            $val = trim($_POST[$key] ?? '');
            $data[$key] = preg_match('/^#[0-9A-Fa-f]{6}$/', $val) ? $val : null;
        }

        // 3. Paths actuales
        $data['hero_media_path']     = $_POST['hero_media_path_actual']     ?? null;
        $data['hero_poster_path']    = $_POST['hero_poster_path_actual']    ?? null;
        $data['benefits_media_path'] = $_POST['benefits_media_path_actual'] ?? null;
        $data['benefit_1_img'] = $_POST['benefit_1_img_actual'] ?? null;
        $data['benefit_2_img'] = $_POST['benefit_2_img_actual'] ?? null;
        $data['benefit_3_img'] = $_POST['benefit_3_img_actual'] ?? null;
        $data['benefit_4_img'] = $_POST['benefit_4_img_actual'] ?? null;

        $data['gallery_1_path'] = $_POST['gallery_1_path_actual'] ?? null;
        $data['gallery_2_path'] = $_POST['gallery_2_path_actual'] ?? null;
        $data['gallery_3_path'] = $_POST['gallery_3_path_actual'] ?? null;
        $data['gallery_4_path'] = $_POST['gallery_4_path_actual'] ?? null;

        $data['porque_media_path'] = $_POST['porque_media_path_actual'] ?? null;

        $data['test1_photo_path']  = $_POST['test1_photo_path_actual']  ?? null;
        $data['test2_photo_path']  = $_POST['test2_photo_path_actual']  ?? null;
        $data['test3_photo_path']  = $_POST['test3_photo_path_actual']  ?? null;
        $data['test1_banner_path'] = $_POST['test1_banner_path_actual'] ?? null;
        $data['test2_banner_path'] = $_POST['test2_banner_path_actual'] ?? null;
        $data['test3_banner_path'] = $_POST['test3_banner_path_actual'] ?? null;

        // WhatsApp images actuales (1..5)
        for ($i = 1; $i <= 5; $i++) {
            $data["wa{$i}_image_path"] = $_POST["wa{$i}_image_path_actual"] ?? null;
        }

        // Antes/Después paths actuales (legacy)
        $data['antes_path']   = $_POST['antes_path_actual']   ?? null;
        $data['despues_path'] = $_POST['despues_path_actual'] ?? null;

        // Características media paths actuales
        for ($i = 1; $i <= 4; $i++) {
            $data["caract{$i}_media_path"] = $_POST["caract{$i}_media_path_actual"] ?? null;
        }

        // Comparativa imágenes actuales
        $data['comparison_img_without'] = $_POST['comparison_img_without_path_actual'] ?? null;
        $data['comparison_img_with']    = $_POST['comparison_img_with_path_actual']    ?? null;

        // 4. Manejo de archivos
        $persistentBase = dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))) . '/uploads';
        $uploadDir = is_dir($persistentBase)
            ? $persistentBase . '/landing/'
            : dirname(__DIR__, 2) . '/public/uploads/landing/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileMap = [
            'hero_media_file'      => 'hero_media_path',
            'hero_poster_file'     => 'hero_poster_path',
            'benefits_media_file'  => 'benefits_media_path',
            'gallery_1_file'       => 'gallery_1_path',
            'gallery_2_file'       => 'gallery_2_path',
            'gallery_3_file'       => 'gallery_3_path',
            'gallery_4_file'       => 'gallery_4_path',
            'porque_media_file'    => 'porque_media_path',
            'test1_photo_file'     => 'test1_photo_path',
            'test2_photo_file'     => 'test2_photo_path',
            'test3_photo_file'     => 'test3_photo_path',
            'test1_banner_file'    => 'test1_banner_path',
            'test2_banner_file'    => 'test2_banner_path',
            'test3_banner_file'    => 'test3_banner_path',

            'wa1_image_file'       => 'wa1_image_path',
            'wa2_image_file'       => 'wa2_image_path',
            'wa3_image_file'       => 'wa3_image_path',
            'wa4_image_file'       => 'wa4_image_path',
            'wa5_image_file'       => 'wa5_image_path',

            'benefit_1_img_file' => 'benefit_1_img',
            'benefit_2_img_file' => 'benefit_2_img',
            'benefit_3_img_file' => 'benefit_3_img',
            'benefit_4_img_file' => 'benefit_4_img',

            'antes_file'   => 'antes_path',
            'despues_file' => 'despues_path',

            'caract1_media_file' => 'caract1_media_path',
            'caract2_media_file' => 'caract2_media_path',
            'caract3_media_file' => 'caract3_media_path',
            'caract4_media_file' => 'caract4_media_path',

            'comparison_img_without_file' => 'comparison_img_without',
            'comparison_img_with_file'    => 'comparison_img_with',
        ];

        $allowedExts  = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'webm'];
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/quicktime', 'video/webm',
        ];

        foreach ($fileMap as $inputName => $column) {
            if (
                isset($_FILES[$inputName]) &&
                $_FILES[$inputName]['error'] === UPLOAD_ERR_OK &&
                is_uploaded_file($_FILES[$inputName]['tmp_name'])
            ) {
                $tmpName  = $_FILES[$inputName]['tmp_name'];
                $origName = $_FILES[$inputName]['name'];
                $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowedExts, true)) {
                    continue;
                }

                // Validar MIME real del contenido del archivo
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeReal = $finfo->file($tmpName);
                if (!in_array($mimeReal, $allowedMimes, true)) {
                    continue;
                }

                $newName  = $inputName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($tmpName, $destPath)) {
                    $data[$column] = BASE_URL . '/public/uploads/landing/' . $newName;
                }
            }
        }

        $configModel = new LandingConfig();
        $configModel->guardarPorProducto($productoId, $data);

        $_SESSION['admin_landing_success'] = "Cambios guardados correctamente.";
        header("Location: " . BASE_URL . "/AdminLanding/index?producto_id=" . $productoId);
        exit;
    }
}
