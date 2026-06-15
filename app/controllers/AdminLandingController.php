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

    // Ruta física del directorio de uploads (igual que serve-upload.php y guardar())
    private function uploadDir(): string
    {
        $persistent = rtrim(dirname(dirname(dirname($_SERVER['DOCUMENT_ROOT']))), '/') . '/uploads/landing/';
        $local      = dirname(__DIR__, 2) . '/public/uploads/landing/';
        $dir        = is_dir(dirname($persistent)) ? $persistent : $local;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
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

        $settings = new AppSettings();
        $this->view('admin/landing/index', [
            'config'             => $config,
            'success'            => $success,
            'producto_id'        => $productoId,
            'productos'          => $productos,
            'producto'           => $productoActual,
            'tiene_api_key'      => $settings->hasKey('claude_api_key'),
            'tiene_replicate_key'=> $settings->hasKey('replicate_api_key'),
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
            'test1_city' => trim($_POST['test1_city'] ?? ''),
            'test2_name' => trim($_POST['test2_name'] ?? ''),
            'test2_text' => trim($_POST['test2_text'] ?? ''),
            'test2_city' => trim($_POST['test2_city'] ?? ''),
            'test3_name' => trim($_POST['test3_name'] ?? ''),
            'test3_text' => trim($_POST['test3_text'] ?? ''),
            'test3_city' => trim($_POST['test3_city'] ?? ''),

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

            // ===== Regalo =====
            'regalo_label'  => trim($_POST['regalo_label'] ?? ''),
            'show_regalo'   => (int)($_POST['show_regalo']    ?? 1),
            'show_price_box'=> (int)($_POST['show_price_box'] ?? 1),
        ];

        // WhatsApp items (1..5)
        for ($i = 1; $i <= 5; $i++) {
            $data["wa{$i}_name"] = trim($_POST["wa{$i}_name"] ?? '');
            $data["wa{$i}_time"] = trim($_POST["wa{$i}_time"] ?? '');
            $data["wa{$i}_text"] = trim($_POST["wa{$i}_text"] ?? '');
        }

        // Características items (1..4)
        for ($i = 1; $i <= 4; $i++) {
            $data["caract{$i}_active"]     = isset($_POST["caract{$i}_active"]) ? 1 : 0;
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

        // Regalo imagen actual
        $data['regalo_image_path'] = $_POST['regalo_image_path'] ?? null;

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

            'regalo_image_file' => 'regalo_image_path',
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

    // ── Guarda API keys (Claude o Replicate) en app_settings ─────────────────
    public function guardarApiKey()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $tipo = trim($_POST['tipo'] ?? 'claude');
        $key  = trim($_POST['api_key'] ?? '');

        if ($tipo === 'replicate') {
            if (!$key || !str_starts_with($key, 'r8_')) {
                echo json_encode(['ok' => false, 'error' => 'La API key de Replicate debe empezar con r8_']);
                return;
            }
            (new AppSettings())->set('replicate_api_key', $key);
        } else {
            if (!$key || !str_starts_with($key, 'sk-ant-')) {
                echo json_encode(['ok' => false, 'error' => 'La API key de Claude debe empezar con sk-ant-']);
                return;
            }
            (new AppSettings())->set('claude_api_key', $key);
        }

        echo json_encode(['ok' => true]);
    }

    // ── Sugiere un prompt de imagen usando Claude ─────────────────────────────
    public function sugerirPrompt()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $apiKey = (new AppSettings())->get('claude_api_key');
        if (!$apiKey) { echo json_encode(['ok' => false, 'error' => 'no_claude_key']); return; }

        $producto     = trim($_POST['producto']      ?? '');
        $descripcion  = trim($_POST['descripcion']   ?? '');
        $seccion      = trim($_POST['seccion']        ?? 'hero');
        $promptActual = trim($_POST['prompt_actual']  ?? '');

        $ctxMap = [
            'hero'                => 'hero image of the product for a landing page, professional product photography, perfect studio lighting, clean elegant background',
            'benefits'            => 'lifestyle photo showing the product benefit in use, happy person using the product, warm natural light',
            'gallery_1'           => 'detailed product shot showing quality and finish, studio lighting, clean white or gradient background',
            'gallery_2'           => 'product from a different angle, showing unique design details, professional photography',
            'gallery_3'           => 'product in real-life use context, lifestyle photography',
            'gallery_4'           => 'product with packaging and accessories, flat lay or arranged composition',
            'caract1'             => 'image illustrating the first key feature of the product, close-up detail',
            'caract2'             => 'image illustrating the second feature, showing functionality',
            'caract3'             => 'image illustrating the third feature, elegant product shot',
            'caract4'             => 'image illustrating the fourth feature, lifestyle or detail photo',
            'porque'              => 'emotional image showing positive transformation the product brings, satisfied smiling person',
            'comparison_without'  => 'image showing the uncomfortable or problematic situation WITHOUT the product, subtle expression of frustration or inconvenience',
            'comparison_with'     => 'image showing the ideal situation WITH the product, happy satisfied person, warm light',
            'test1_banner'        => 'photo of a satisfied Colombian customer holding or using the product, genuine smile, natural setting',
            'test2_banner'        => 'another happy Colombian customer with the product, different setting',
            'test3_banner'        => 'third customer showing the received product, unboxing or in-use moment',
        ];

        $ctx = $ctxMap[$seccion] ?? 'professional product image for e-commerce landing page';

        if ($promptActual) {
            $msg = "Improve and expand this Flux AI image prompt: \"{$promptActual}\"\n\nProduct: {$producto}. {$descripcion}\nSection context: {$ctx}\n\nReturn ONLY the improved prompt in English, detailed, professional, optimized for Flux 1.1 Pro. No explanations, no quotes, just the prompt. Max 150 words.";
        } else {
            $msg = "Write an English prompt for Flux 1.1 Pro AI image generator for this landing page section:\n\nProduct: {$producto}\nDescription: {$descripcion}\nImage context: {$ctx}\n\nThe prompt must be: photorealistic, professional commercial photography style, high quality e-commerce. Describe lighting, composition, photographic style, mood.\n\nReturn ONLY the prompt in English. No explanations, no quotes, just the prompt. Max 120 words.";
        }

        $result = $this->callClaudeText($apiKey, $msg);
        echo json_encode($result);
    }

    // ── Genera imagen con Replicate Flux 1.1 Pro + optimiza a WebP ───────────
    public function generarImagenIA()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $replicateKey = (new AppSettings())->get('replicate_api_key');
        if (!$replicateKey) { echo json_encode(['ok' => false, 'error' => 'no_replicate_key']); return; }

        $prompt         = trim($_POST['prompt']          ?? '');
        $seccion        = trim($_POST['seccion']         ?? 'hero');
        $referenciaUrl  = trim($_POST['referencia_url']  ?? '');
        $promptStrength = (float)($_POST['prompt_strength'] ?? 0.80);
        $promptStrength = max(0.5, min(1.0, $promptStrength));

        if (!$prompt) { echo json_encode(['ok' => false, 'error' => 'El prompt es requerido']); return; }

        // Convertir referencia local a base64 para que Replicate pueda accederla
        if ($referenciaUrl && str_starts_with($referenciaUrl, BASE_URL)) {
            $filename  = basename(parse_url($referenciaUrl, PHP_URL_PATH));
            $localFile = $this->uploadDir() . $filename;
            if ($filename && file_exists($localFile)) {
                $finfo         = new finfo(FILEINFO_MIME_TYPE);
                $mime          = $finfo->file($localFile);
                $referenciaUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($localFile));
            } else {
                $referenciaUrl = '';
            }
        }

        $aspectos = [
            'hero'               => '2:3',
            'benefits'           => '3:2',
            'gallery_1'          => '1:1',  'gallery_2' => '1:1',
            'gallery_3'          => '1:1',  'gallery_4' => '1:1',
            'caract1'            => '1:1',  'caract2'   => '1:1',
            'caract3'            => '1:1',  'caract4'   => '1:1',
            'porque'             => '3:2',
            'comparison_without' => '2:3',  'comparison_with' => '2:3',
            'test1_banner'       => '16:9', 'test2_banner'    => '16:9',
            'test3_banner'       => '16:9',
        ];
        $maxDims = [
            'hero'               => [800, 1200],
            'benefits'           => [900,  600],
            'gallery_1'          => [800,  800],  'gallery_2' => [800, 800],
            'gallery_3'          => [800,  800],  'gallery_4' => [800, 800],
            'caract1'            => [600,  600],  'caract2'   => [600, 600],
            'caract3'            => [600,  600],  'caract4'   => [600, 600],
            'porque'             => [900,  600],
            'comparison_without' => [500,  700],  'comparison_with' => [500, 700],
            'test1_banner'       => [800,  400],  'test2_banner'    => [800, 400],
            'test3_banner'       => [800,  400],
        ];

        $aspectRatio = $aspectos[$seccion]  ?? '1:1';
        $dims        = $maxDims[$seccion]   ?? [800, 800];

        $imageUrl = $this->callReplicateFlux($replicateKey, $prompt, $aspectRatio, $referenciaUrl ?: null, $promptStrength);
        if (is_array($imageUrl)) { echo json_encode(['ok' => false, 'error' => $imageUrl['error']]); return; }

        $publicUrl = $this->downloadAndOptimizeImage($imageUrl, $seccion, $dims);
        if (!$publicUrl) { echo json_encode(['ok' => false, 'error' => 'Imagen generada pero no se pudo descargar del CDN de Replicate. Revisa que el servidor tenga acceso a Internet y soporte GD.']); return; }

        echo json_encode(['ok' => true, 'url' => $publicUrl]);
    }

    // ── Sube foto de referencia del producto al servidor ─────────────────────
    public function subirReferencia()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $file = $_FILES['referencia'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['ok' => false, 'error' => 'No se recibió ningún archivo']);
            return;
        }

        $allowedMime = ['image/jpeg','image/png','image/webp','image/gif'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMime, true)) {
            echo json_encode(['ok' => false, 'error' => 'Solo se permiten imágenes JPG, PNG, WEBP o GIF']);
            return;
        }

        $uploadDir = $this->uploadDir();

        $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$mime];
        $filename = 'ref_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['ok' => false, 'error' => 'Error al guardar el archivo']);
            return;
        }

        echo json_encode(['ok' => true, 'url' => BASE_URL . '/public/uploads/landing/' . $filename]);
    }

    // ── Genera el texto de UNA sección específica con Claude ─────────────────
    public function generarSeccionIA()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $apiKey = (new AppSettings())->get('claude_api_key');
        if (!$apiKey) { echo json_encode(['ok' => false, 'error' => 'no_key']); return; }

        $seccion     = trim($_POST['seccion']     ?? '');
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $publico     = trim($_POST['publico']      ?? 'adultos colombianos');
        $precio      = trim($_POST['precio']      ?? '');
        $extra       = trim($_POST['extra']        ?? '');

        if (!$seccion || !$nombre) {
            echo json_encode(['ok' => false, 'error' => 'Faltan datos del producto']);
            return;
        }

        $prompt = $this->buildSeccionPrompt($seccion, $nombre, $descripcion, $publico, $precio, $extra);
        if (!$prompt) {
            echo json_encode(['ok' => false, 'error' => 'Sección no reconocida']);
            return;
        }

        echo json_encode($this->callClaudeApi($apiKey, $prompt));
    }

    // ── Prompt focalizado por sección ─────────────────────────────────────────
    private function buildSeccionPrompt(string $sec, string $nombre, string $desc, string $publico, string $precio, string $extra): ?string
    {
        $base = "Eres experto en copywriting de alta conversión para e-commerce colombiano (dropshipping).\n"
              . "Producto: {$nombre}" . ($desc ? " — {$desc}" : '') . "\n"
              . "Público: {$publico}" . ($precio ? " · Precio: {$precio} COP" : '') . "\n"
              . ($extra ? "Instrucciones adicionales: {$extra}\n" : '')
              . "REGLAS: español colombiano informal, emocional, orientado al beneficio, "
              . "pago contraentrega, urgencia real, nombres/ciudades colombianas.\n\n"
              . "Devuelve SOLO JSON válido (sin markdown). Rellena cada campo con copy real, no con descripciones.\n\n";

        $schemas = [
            'hero' => '{"hero_title":"","hero_subtitle":"","hero_button_text":"","hero_note":"Ej: Pago al recibir • Envío gratis","hero_badge_customers":""}',

            'beneficios' => '{"benefits_title":"","benefit_1":"","benefit_2":"","benefit_3":"","benefit_4":""}',

            'caracteristicas' => '{"caract_section_title":"","caract1_title":"","caract1_text":"","caract2_title":"","caract2_text":"","caract3_title":"","caract3_text":"","caract4_title":"","caract4_text":""}',

            'countdown' => '{"countdown_title":"","countdown_text":""}',

            'porque' => '{"porque_title":"","porque_text":"","porque_bullet1":"","porque_bullet2":"","porque_bullet3":""}',

            'comparativa' => '{"comparison_title":"","comparison_label_without":"Sin el producto","comparison_label_with":"Con el producto","comparison_1_without":"","comparison_1_with":"","comparison_2_without":"","comparison_2_with":"","comparison_3_without":"","comparison_3_with":"","comparison_4_without":"","comparison_4_with":"","comparison_5_without":"","comparison_5_with":""}',

            'testimonios' => '{"test1_name":"","test1_city":"","test1_text":"","test2_name":"","test2_city":"","test2_text":"","test3_name":"","test3_city":"","test3_text":""}',

            'paraquien' => '{"para_quien_si_1":"","para_quien_si_2":"","para_quien_si_3":"","para_quien_si_4":"","para_quien_no_1":"","para_quien_no_2":"","para_quien_no_3":""}',

            'wa' => '{"wa_title":"","wa_subtitle":"","wa_footer_note":"","wa1_name":"","wa1_time":"","wa1_text":"","wa2_name":"","wa2_time":"","wa2_text":"","wa3_name":"","wa3_time":"","wa3_text":"","wa4_name":"","wa4_time":"","wa4_text":"","wa5_name":"","wa5_time":"","wa5_text":""}',

            'faq' => '{"faq1_q":"","faq1_a":"","faq2_q":"","faq2_a":"","faq3_q":"","faq3_a":"","faq4_q":"","faq4_a":"","faq5_q":"","faq5_a":"","faq6_q":"","faq6_a":""}',

            'autoridad' => '{"authority_title":"","authority_years":"","authority_deliveries":"","authority_rating":"4.9","authority_guarantee":""}',

            'ctas' => '{"cta_benefits_text":"","cta_benefits_button":"","cta_gallery_text":"","cta_gallery_button":"","cta_porque_text":"","cta_porque_button":"","cta_testimonials_text":"","cta_testimonials_button":"","cta_faq_text":"","cta_faq_button":"","cta_como_funciona_text":"","cta_como_funciona_button":"","cta_comparison_button":"","cta_para_quien_button":"","cta_wa_testimonios_button":"","cta_sticky_mobile_text":""}',
        ];

        if (!isset($schemas[$sec])) return null;

        $hints = [
            'hero'            => 'Hero: título ≤8 palabras, promesa poderosa. Subtítulo habla al dolor. hero_note menciona pago contraentrega.',
            'beneficios'      => 'Beneficios: orientados a resultados concretos, no a características técnicas.',
            'caracteristicas' => 'Características: cada texto 2-3 oraciones que explican el beneficio de esa característica.',
            'countdown'       => 'Countdown: urgencia real. El texto debe crear miedo a perder la oferta.',
            'porque'          => 'Por qué: emocional. porque_text es el párrafo más persuasivo de la landing.',
            'comparativa'     => 'Comparativa: TRANSFORMACIÓN emocional. Sin/Con no son specs, son situaciones de vida.',
            'testimonios'     => 'Testimonios: nombres y ciudades colombianas 100% reales. Textos ≤100 chars, muy naturales.',
            'paraquien'       => 'Para quién: los "Sí" generan identificación, los "No" califican y generan FOMO inverso.',
            'wa'              => 'WhatsApp: mensajes ultra-informales, emojis naturales, como copiados del celular de un cliente feliz.',
            'faq'             => 'FAQ: faq1 SIEMPRE sobre pago (contraentrega), faq2 sobre tiempo de envío (3-7 días hábiles Colombia).',
            'autoridad'       => 'Autoridad: números creíbles. authority_years puede ser pequeño si la marca es nueva.',
            'ctas'            => 'CTAs: máx 6 palabras por botón. Verbo acción + urgencia. Textos 1 oración persuasiva.',
        ];

        return $base . ($hints[$sec] ?? '') . "\n\nJSON a completar:\n" . $schemas[$sec];
    }

    // ── Genera el contenido de la landing con Claude ──────────────────────────
    public function generarConIA()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $settings = new AppSettings();
        $apiKey   = $settings->get('claude_api_key');

        if (!$apiKey) {
            echo json_encode(['ok' => false, 'error' => 'no_key']);
            return;
        }

        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $publico     = trim($_POST['publico']     ?? 'adultos colombianos');
        $precio      = trim($_POST['precio']      ?? '');

        if (!$nombre || !$descripcion) {
            echo json_encode(['ok' => false, 'error' => 'El nombre y la descripción son requeridos']);
            return;
        }

        $prompt = $this->buildColombianPrompt($nombre, $descripcion, $publico, $precio);
        $result = $this->callClaudeApi($apiKey, $prompt);

        echo json_encode($result);
    }

    // ── Prompt optimizado para conversión colombiana ──────────────────────────
    private function buildColombianPrompt(string $nombre, string $descripcion, string $publico, string $precio): string
    {
        $precioLine = $precio ? "- Precio: $precio COP" : '';

        return <<<PROMPT
Eres el mejor copywriter de e-commerce colombiano. Tu especialidad es escribir textos que VENDEN para dropshipping en Colombia. Tu copy convierte porque habla exactamente como el colombiano real: cálido, directo, con urgencia genuina.

PRODUCTO A TRABAJAR:
- Nombre: {$nombre}
- Descripción: {$descripcion}
- Público objetivo: {$publico}
{$precioLine}

REGLAS OBLIGATORIAS DE ESTILO (romperlas es inaceptable):
1. Español colombiano 100% natural. Tuteo informal. NADA de "usted" en CTAs o textos de urgencia.
2. Cada texto habla al DOLOR o DESEO del cliente, nunca a características técnicas.
3. PAGO CONTRAENTREGA es el argumento de confianza #1. Mencionarlo en hero_note, FAQ y testimonios.
4. Urgencia real: "quedan pocas unidades", "solo por hoy", "la oferta termina pronto".
5. Testimonios con nombres colombianos auténticos y ciudades colombianas reales (Bogotá, Medellín, Cali, Barranquilla, Bucaramanga, Pereira, Manizales, Santa Marta, Ibagué, Cúcuta, Cartagena).
6. Mensajes de WhatsApp ultra-naturales: como si fueran copiados del celular de un cliente feliz (emojis reales, ortografía casi perfecta pero informal).
7. FAQ siempre incluye: pago contraentrega, tiempo de envío (3-7 días hábiles), garantía, devoluciones.
8. Hero title: máximo 8 palabras. Promesa de transformación o resultado.
9. Comparativa: TRANSFORMACIÓN EMOCIONAL antes/después (no listas de specs).
10. CTAs: cortos, con verbo de acción + urgencia. Ej: "¡Lo quiero ahora!" / "Pedir el mío →" / "Aprovechar oferta".

Devuelve ÚNICAMENTE el siguiente JSON válido. Sin markdown, sin bloques de código, sin texto antes o después. Solo el JSON:

{
  "hero_title": "",
  "hero_subtitle": "",
  "hero_button_text": "",
  "hero_note": "",
  "hero_badge_customers": "",
  "benefits_title": "",
  "benefit_1": "",
  "benefit_2": "",
  "benefit_3": "",
  "benefit_4": "",
  "caract_section_title": "",
  "caract1_title": "",
  "caract1_text": "",
  "caract2_title": "",
  "caract2_text": "",
  "caract3_title": "",
  "caract3_text": "",
  "caract4_title": "",
  "caract4_text": "",
  "countdown_title": "",
  "countdown_text": "",
  "porque_title": "",
  "porque_text": "",
  "porque_bullet1": "",
  "porque_bullet2": "",
  "porque_bullet3": "",
  "comparison_title": "",
  "comparison_label_without": "Sin el producto",
  "comparison_label_with": "Con el producto",
  "comparison_1_without": "",
  "comparison_1_with": "",
  "comparison_2_without": "",
  "comparison_2_with": "",
  "comparison_3_without": "",
  "comparison_3_with": "",
  "comparison_4_without": "",
  "comparison_4_with": "",
  "comparison_5_without": "",
  "comparison_5_with": "",
  "test1_name": "",
  "test1_city": "",
  "test1_text": "",
  "test2_name": "",
  "test2_city": "",
  "test2_text": "",
  "test3_name": "",
  "test3_city": "",
  "test3_text": "",
  "para_quien_si_1": "",
  "para_quien_si_2": "",
  "para_quien_si_3": "",
  "para_quien_si_4": "",
  "para_quien_no_1": "",
  "para_quien_no_2": "",
  "para_quien_no_3": "",
  "wa_title": "",
  "wa_subtitle": "",
  "wa_footer_note": "",
  "wa1_name": "",
  "wa1_time": "",
  "wa1_text": "",
  "wa2_name": "",
  "wa2_time": "",
  "wa2_text": "",
  "wa3_name": "",
  "wa3_time": "",
  "wa3_text": "",
  "wa4_name": "",
  "wa4_time": "",
  "wa4_text": "",
  "wa5_name": "",
  "wa5_time": "",
  "wa5_text": "",
  "faq1_q": "",
  "faq1_a": "",
  "faq2_q": "",
  "faq2_a": "",
  "faq3_q": "",
  "faq3_a": "",
  "faq4_q": "",
  "faq4_a": "",
  "faq5_q": "",
  "faq5_a": "",
  "faq6_q": "",
  "faq6_a": "",
  "authority_title": "",
  "authority_years": "",
  "authority_deliveries": "",
  "authority_rating": "4.9",
  "authority_guarantee": "",
  "cta_benefits_text": "",
  "cta_benefits_button": "",
  "cta_gallery_text": "",
  "cta_gallery_button": "",
  "cta_porque_text": "",
  "cta_porque_button": "",
  "cta_testimonials_text": "",
  "cta_testimonials_button": "",
  "cta_faq_text": "",
  "cta_faq_button": "",
  "cta_como_funciona_text": "",
  "cta_como_funciona_button": "",
  "cta_comparison_button": "",
  "cta_para_quien_button": "",
  "cta_wa_testimonios_button": "",
  "cta_sticky_mobile_text": ""
}
PROMPT;
    }

    // ── Llama a la API de Claude y devuelve array resultado ───────────────────
    private function callClaudeApi(string $apiKey, string $prompt): array
    {
        $payload = json_encode([
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 4096,
            'messages'   => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 90,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        unset($ch);

        if (!$response) {
            return ['ok' => false, 'error' => 'Error de conexión: ' . $curlErr];
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $msg = $data['error']['message'] ?? 'Error desconocido de la API';
            return ['ok' => false, 'error' => $msg];
        }

        $text = $data['content'][0]['text'] ?? '';

        // Extraer JSON de la respuesta (por si Claude añade texto extra)
        $parsed = json_decode($text, true);
        if (!$parsed) {
            if (preg_match('/\{[\s\S]*\}/u', $text, $m)) {
                $parsed = json_decode($m[0], true);
            }
        }

        if (!$parsed) {
            return ['ok' => false, 'error' => 'No se pudo procesar la respuesta de la IA. Intenta de nuevo.'];
        }

        return ['ok' => true, 'fields' => $parsed];
    }

    // ── Claude: respuesta de texto plano (para prompts) ───────────────────────
    private function callClaudeText(string $apiKey, string $prompt): array
    {
        $payload = json_encode([
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 300,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if (!$response) return ['ok' => false, 'error' => 'Error de conexión con Claude'];
        $data = json_decode($response, true);
        if ($httpCode !== 200) return ['ok' => false, 'error' => $data['error']['message'] ?? 'Error API'];

        return ['ok' => true, 'text' => trim($data['content'][0]['text'] ?? '')];
    }

    // ── Replicate: llama a Flux 1.1 Pro (texto o img2img) ────────────────────
    private function callReplicateFlux(string $apiKey, string $prompt, string $aspectRatio, ?string $imageUrl = null, float $promptStrength = 0.80): string|array
    {
        $input = [
            'prompt'           => $prompt,
            'output_format'    => 'webp',
            'output_quality'   => 85,
            'safety_tolerance' => 2,
        ];

        if ($imageUrl) {
            // img2img: referencia del producto real
            $input['image']           = $imageUrl;
            $input['prompt_strength'] = $promptStrength;
        } else {
            // text2img: sin referencia, usar aspect_ratio
            $input['aspect_ratio'] = $aspectRatio;
        }

        $payload = json_encode(['input' => $input]);

        $ch = curl_init('https://api.replicate.com/v1/models/black-forest-labs/flux-1.1-pro/predictions');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
                'Prefer: wait',
            ],
            CURLOPT_TIMEOUT => 120,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        unset($ch);

        if (!$response) return ['error' => 'Error de conexión con Replicate: ' . $curlErr];

        $data = json_decode($response, true);
        if ($httpCode !== 200 && $httpCode !== 201) {
            return ['error' => 'Error Replicate: ' . ($data['detail'] ?? $data['error'] ?? 'Error desconocido')];
        }

        $output = $data['output'] ?? null;
        if (is_array($output)) $output = $output[0] ?? null;

        if (!$output) {
            $id = $data['id'] ?? null;
            return $id ? $this->pollReplicatePrediction($apiKey, $id) : ['error' => 'Sin URL de imagen'];
        }

        return $output;
    }

    // ── Replicate: polling si Prefer:wait no resolvió ─────────────────────────
    private function pollReplicatePrediction(string $apiKey, string $id): string|array
    {
        for ($i = 0; $i < 20; $i++) {
            sleep(3);
            $ch = curl_init("https://api.replicate.com/v1/predictions/{$id}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $data   = json_decode(curl_exec($ch), true);
            unset($ch);
            $status = $data['status'] ?? '';
            if ($status === 'succeeded') {
                $out = $data['output'] ?? null;
                return is_array($out) ? ($out[0] ?? ['error' => 'Sin URL']) : ($out ?: ['error' => 'Sin URL']);
            }
            if ($status === 'failed') return ['error' => $data['error'] ?? 'Generación fallida'];
        }
        return ['error' => 'Timeout: la imagen tardó demasiado'];
    }

    // ── Descarga imagen, la redimensiona y guarda (WebP si está disponible, sino JPEG) ──
    private function downloadAndOptimizeImage(string $url, string $seccion, array $maxDims): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; TiendaIA/1.0)',
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if (!$raw || $curlErr || $httpCode !== 200) return null;

        $src = @imagecreatefromstring($raw);
        if (!$src) return null;

        [$maxW, $maxH] = $maxDims;
        $srcW  = imagesx($src);
        $srcH  = imagesy($src);
        $ratio = min($maxW / $srcW, $maxH / $srcH, 1.0);
        $newW  = (int)round($srcW * $ratio);
        $newH  = (int)round($srcH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
        unset($src);

        $uploadDir = $this->uploadDir();

        $useWebp  = function_exists('imagewebp');
        $ext      = $useWebp ? 'webp' : 'jpg';
        $filename = 'ia_' . $seccion . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $path     = $uploadDir . $filename;

        $saved = $useWebp ? imagewebp($dst, $path, 82) : imagejpeg($dst, $path, 85);

        // imagewebp puede existir pero fallar si GD no tiene soporte WebP compilado
        if (!$saved && $useWebp) {
            $ext      = 'jpg';
            $filename = 'ia_' . $seccion . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
            $path     = $uploadDir . $filename;
            $saved    = imagejpeg($dst, $path, 85);
        }
        unset($dst);

        return ($saved && file_exists($path)) ? BASE_URL . '/public/uploads/landing/' . $filename : null;
    }
}
