<?php

class AdminLandingController extends Controller
{
    private function requireLogin()
    {
        if (empty($_SESSION['usuario_id'])) {
            header("Location: /tienda_mvc/Auth/login");
            exit;
        }
    }

    public function index()
    {
        $this->requireLogin();

        $productoId = (int)($_GET['producto_id'] ?? 1);
        if ($productoId <= 0) {
            $productoId = 1;
        }

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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /tienda_mvc/AdminLanding/index");
            exit;
        }

        $productoId = (int)($_POST['producto_id'] ?? 1);
        if ($productoId <= 0) {
            $productoId = 1;
        }

        // 1. Textos
        $data = [
            'hero_title'       => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle'    => trim($_POST['hero_subtitle'] ?? ''),
            'hero_note'        => trim($_POST['hero_note'] ?? ''),
            'hero_button_text' => trim($_POST['hero_button_text'] ?? ''),
            'hero_media_type'  => trim($_POST['hero_media_type'] ?? 'imagen'),

            'benefits_title' => trim($_POST['benefits_title'] ?? ''),
            'benefit_1'      => trim($_POST['benefit_1'] ?? ''),
            'benefit_2'      => trim($_POST['benefit_2'] ?? ''),
            'benefit_3'      => trim($_POST['benefit_3'] ?? ''),
            'benefit_4'      => trim($_POST['benefit_4'] ?? ''),

            'countdown_title' => trim($_POST['countdown_title'] ?? ''),
            'countdown_text'  => trim($_POST['countdown_text'] ?? ''),

            'porque_title'   => trim($_POST['porque_title'] ?? ''),
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

            'footer_text' => trim($_POST['footer_text'] ?? ''),

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

            // ===== WhatsApp Testimonios (editable) =====
            // Si lo pones como checkbox, cuando no viene -> 0. Si lo pones como select 0/1, también funciona.
            'wa_enabled'     => (isset($_POST['wa_enabled']) && (string)($_POST['wa_enabled']) !== '0') ? 1 : 0,
            'wa_title'       => trim($_POST['wa_title'] ?? ''),
            'wa_subtitle'    => trim($_POST['wa_subtitle'] ?? ''),
            'wa_footer_note' => trim($_POST['wa_footer_note'] ?? ''),
        ];

        // WhatsApp items (1..5)
        for ($i = 1; $i <= 5; $i++) {
            $data["wa{$i}_name"] = trim($_POST["wa{$i}_name"] ?? '');
            $data["wa{$i}_time"] = trim($_POST["wa{$i}_time"] ?? '');
            $data["wa{$i}_text"] = trim($_POST["wa{$i}_text"] ?? '');
        }

        // 2. Colores
        $data['primary_color']    = $_POST['primary_color']    ?: null;
        $data['secondary_color']  = $_POST['secondary_color']  ?: null;
        $data['accent_color']     = $_POST['accent_color']     ?: null;
        $data['background_color'] = $_POST['background_color'] ?: null;
        $data['text_color']       = $_POST['text_color']       ?: null;

        // 3. Paths actuales
        $data['hero_media_path']      = $_POST['hero_media_path_actual']      ?? null;
        $data['benefits_media_path']  = $_POST['benefits_media_path_actual']  ?? null;
        $data['gallery_1_path']       = $_POST['gallery_1_path_actual']       ?? null;
        $data['gallery_2_path']       = $_POST['gallery_2_path_actual']       ?? null;
        $data['gallery_3_path']       = $_POST['gallery_3_path_actual']       ?? null;
        $data['porque_media_path']    = $_POST['porque_media_path_actual']    ?? null;
        $data['test1_photo_path']     = $_POST['test1_photo_path_actual']     ?? null;
        $data['test2_photo_path']     = $_POST['test2_photo_path_actual']     ?? null;
        $data['test3_photo_path']     = $_POST['test3_photo_path_actual']     ?? null;

        // WhatsApp images actuales (1..5)
        for ($i = 1; $i <= 5; $i++) {
            $data["wa{$i}_image_path"] = $_POST["wa{$i}_image_path_actual"] ?? null;
        }

        // 4. Manejo de archivos
        $basePath  = dirname(__DIR__, 2);
        $uploadDir = $basePath . '/public/uploads/landing/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileMap = [
            'hero_media_file'      => 'hero_media_path',
            'benefits_media_file'  => 'benefits_media_path',
            'gallery_1_file'       => 'gallery_1_path',
            'gallery_2_file'       => 'gallery_2_path',
            'gallery_3_file'       => 'gallery_3_path',
            'porque_media_file'    => 'porque_media_path',
            'test1_photo_file'     => 'test1_photo_path',
            'test2_photo_file'     => 'test2_photo_path',
            'test3_photo_file'     => 'test3_photo_path',

            // ===== WhatsApp Testimonios (editable) =====
            'wa1_image_file'       => 'wa1_image_path',
            'wa2_image_file'       => 'wa2_image_path',
            'wa3_image_file'       => 'wa3_image_path',
            'wa4_image_file'       => 'wa4_image_path',
            'wa5_image_file'       => 'wa5_image_path',
        ];

        foreach ($fileMap as $inputName => $column) {
            if (
                isset($_FILES[$inputName]) &&
                $_FILES[$inputName]['error'] === UPLOAD_ERR_OK &&
                is_uploaded_file($_FILES[$inputName]['tmp_name'])
            ) {
                $tmpName  = $_FILES[$inputName]['tmp_name'];
                $origName = $_FILES[$inputName]['name'];

                $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $newName = $inputName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($tmpName, $destPath)) {
                    $webPath = '/tienda_mvc/public/uploads/landing/' . $newName;
                    $data[$column] = $webPath;
                }
            }
        }

        $configModel = new LandingConfig();
        $configModel->guardarPorProducto($productoId, $data);

        $_SESSION['admin_landing_success'] = "Cambios guardados correctamente.";

        header("Location: /tienda_mvc/AdminLanding/index?producto_id=" . $productoId);
        exit;
    }
}
