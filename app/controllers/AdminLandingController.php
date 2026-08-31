<?php

class AdminLandingController extends Controller
{
    /** Modelo de Claude para toda la generación de copy de la landing. */
    private const COPY_MODEL = 'claude-sonnet-4-6';

    /** Voz de marca → instrucción de tono para el prompt. */
    private const VOCES = [
        'cercana' => 'Cercano y cálido, como una amiga que te recomienda algo que a ella le funcionó. Tuteo, nada de corporativo.',
        'experta' => 'Experta y segura sin ser fría: sabe del tema y lo demuestra con precisión, pero habla claro y directo.',
        'picara'  => 'Pícara y con humor colombiano, juega con el lenguaje y se permite una broma — sin payasear ni perder la venta.',
        'premium' => 'Sobria y aspiracional: menos signos de admiración, frases con ritmo; la calidad se siente en el tono, no se grita.',
    ];

    /** Escala de agresividad del copy (1 = informativo, 5 = confrontacional). */
    private const AGRESIVIDAD = [
        1 => 'Muy suave: informativo y amable, cero presión, sin urgencia agresiva.',
        2 => 'Suave: beneficio por delante, la urgencia se menciona una vez y sin dramatismo.',
        3 => 'Equilibrado: agita el dolor lo justo, urgencia real pero creíble.',
        4 => 'Directo (response marketing clásico): nombra el dolor sin anestesia, urgencia en varios puntos, preguntas desde el dolor.',
        5 => 'Muy agresivo: confronta, hace preguntas incómodas desde el dolor, escasez y pérdida en cada sección. Nunca insulta al lector.',
    ];

    /** Frases muertas de plantilla que la IA NO puede usar (ni variantes). */
    private const FRASES_PROHIBIDAS = [
        'descubre el secreto', 'no esperes más', 'lleva tu rutina al siguiente nivel',
        'eleva tu experiencia', 'la solución definitiva', 'dale un giro a tu vida',
        'revoluciona tu día', 'no te quedes sin el tuyo', 'calidad premium',
        'diseñado pensando en ti', 'lo que estabas buscando', 'hecho con amor',
        'transforma tu día a día', 'vive la diferencia', 'la mejor decisión que tomarás',
    ];

    private function briefKey(int $pid): string
    {
        return 'landing_brief_' . $pid;
    }

    /** Lee el brief estratégico guardado de un producto (siempre con todas las claves). */
    private function leerBrief(int $pid): array
    {
        $raw = $pid > 0 ? (new AppSettings())->get($this->briefKey($pid), '') : '';
        $b   = $raw ? json_decode($raw, true) : [];
        if (!is_array($b)) $b = [];

        return array_replace([
            'avatar' => '', 'escena' => '', 'objecion' => '', 'alternativa' => '',
            'voz' => 'cercana', 'agresividad' => 3,
        ], $b, ['angulo' => array_replace(
            ['dolor' => '', 'gran_idea' => '', 'headline' => '', 'a_quien' => ''],
            is_array($b['angulo'] ?? null) ? $b['angulo'] : []
        )]);
    }

    /** Normaliza el brief que llega por POST (campos sueltos + angulo_* opcionales). */
    private function briefDesdePost(): array
    {
        $voz = trim($_POST['brief_voz'] ?? 'cercana');
        if (!isset(self::VOCES[$voz])) $voz = 'cercana';

        $ag = (int)($_POST['brief_agresividad'] ?? 3);
        $ag = max(1, min(5, $ag));

        return [
            'avatar'      => trim($_POST['brief_avatar']      ?? ''),
            'escena'      => trim($_POST['brief_escena']      ?? ''),
            'objecion'    => trim($_POST['brief_objecion']    ?? ''),
            'alternativa' => trim($_POST['brief_alternativa'] ?? ''),
            'voz'         => $voz,
            'agresividad' => $ag,
            'angulo'      => [
                'dolor'     => trim($_POST['angulo_dolor']     ?? ''),
                'gran_idea' => trim($_POST['angulo_gran_idea'] ?? ''),
                'headline'  => trim($_POST['angulo_headline']  ?? ''),
                'a_quien'   => trim($_POST['angulo_a_quien']   ?? ''),
            ],
        ];
    }

    private function guardarBrief(int $pid, array $brief): void
    {
        if ($pid > 0) {
            (new AppSettings())->set($this->briefKey($pid), json_encode($brief, JSON_UNESCAPED_UNICODE));
        }
    }

    /** ¿El brief trae un ángulo elegido? */
    private function tieneAngulo(array $brief): bool
    {
        $a = $brief['angulo'] ?? [];
        return trim(($a['dolor'] ?? '') . ($a['gran_idea'] ?? '') . ($a['headline'] ?? '')) !== '';
    }

    /** Notas de marca / swipe file — global, no por producto. */
    private function notasMarca(): string
    {
        return (string)(new AppSettings())->get('landing_copy_brand', '');
    }

    private function guardarNotasMarcaDesdePost(): void
    {
        if (isset($_POST['notas_marca'])) {
            (new AppSettings())->set('landing_copy_brand', trim((string)$_POST['notas_marca']));
        }
    }

    /** Últimas correcciones a mano (IA escribió X → el dueño lo dejó Y), global. */
    private function edicionesMarca(): array
    {
        $raw = (new AppSettings())->get('landing_copy_ediciones', '');
        $ed  = $raw ? json_decode($raw, true) : [];
        return is_array($ed) ? $ed : [];
    }

    // ── Registra una corrección a mano de un copy generado por IA ────────────
    public function registrarEdicionIA()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        $ia     = trim((string)($_POST['ia']     ?? ''));
        $humano = trim((string)($_POST['humano'] ?? ''));
        $campo  = trim((string)($_POST['campo']  ?? ''));

        // Ignora ruido: sin cambio real, textos vacíos o ediciones triviales.
        if ($ia === '' || $humano === '' || $ia === $humano
            || mb_strlen($humano) < 3
            || similar_text($ia, $humano) / max(mb_strlen($ia), 1) > 0.97) {
            echo json_encode(['ok' => true, 'skip' => true]);
            return;
        }

        $s = new AppSettings();
        $ed = $this->edicionesMarca();
        // Una entrada por campo (la última gana); tope 15, se descarta lo viejo.
        $ed = array_values(array_filter($ed, fn($e) => ($e['campo'] ?? '') !== $campo));
        $ed[] = ['campo' => $campo, 'ia' => mb_substr($ia, 0, 300), 'humano' => mb_substr($humano, 0, 300)];
        if (count($ed) > 15) $ed = array_slice($ed, -15);

        $s->set('landing_copy_ediciones', json_encode($ed, JSON_UNESCAPED_UNICODE));
        echo json_encode(['ok' => true]);
    }

    /**
     * Bloque de contexto estratégico que se antepone a TODOS los prompts de
     * copy (landing completa y por sección). Traduce el brief del vendedor,
     * las notas de marca y la lista de frases prohibidas a instrucciones.
     */
    private function bloqueBrief(array $brief): string
    {
        $L = [];

        $avatar = trim($brief['avatar']      ?? '');
        $escena = trim($brief['escena']      ?? '');
        $obj    = trim($brief['objecion']    ?? '');
        $alt    = trim($brief['alternativa'] ?? '');
        $voz    = $brief['voz'] ?? 'cercana';
        $ag     = (int)($brief['agresividad'] ?? 3);
        $ag     = max(1, min(5, $ag));
        $ang    = $brief['angulo'] ?? [];

        if ($avatar !== '' || $escena !== '' || $obj !== '' || $alt !== '') {
            $L[] = 'CLIENTE REAL (usa esto, no un promedio):';
            if ($avatar !== '') $L[] = "- Quién es: {$avatar}";
            if ($escena !== '') $L[] = "- El momento exacto en que siente el dolor: {$escena}";
            if ($obj    !== '') $L[] = "- Lo que lo frena para comprar: {$obj} — bájale ese miedo en la FAQ y en el copy, no lo esquives.";
            if ($alt    !== '') $L[] = "- Qué hace hoy en vez de comprar esto: {$alt} — el copy debe dejar claro por qué eso no le alcanza.";
        }

        $L[] = "\nVOZ DE MARCA: " . (self::VOCES[$voz] ?? self::VOCES['cercana']);
        $L[] = "NIVEL DE AGRESIVIDAD DEL COPY ({$ag}/5): " . (self::AGRESIVIDAD[$ag] ?? self::AGRESIVIDAD[3]);

        if ($this->tieneAngulo($brief)) {
            $L[] = "\nÁNGULO DE VENTA YA DECIDIDO — NO LO CAMBIES NI LO GENERALICES. Todo el copy es este mismo ángulo contado desde cada sección:";
            if (!empty($ang['dolor']))     $L[] = "- Dolor central: {$ang['dolor']}";
            if (!empty($ang['gran_idea'])) $L[] = "- Gran idea / promesa: {$ang['gran_idea']}";
            if (!empty($ang['headline']))  $L[] = "- Dirección del titular: {$ang['headline']}";
            if (!empty($ang['a_quien']))   $L[] = "- Le habla a: {$ang['a_quien']}";
        }

        $notas = trim($this->notasMarca());
        if ($notas !== '') {
            $L[] = "\nNOTAS DE LA MARCA + EJEMPLOS DE COPY QUE SÍ FUNCIONA (imita el tono y el nivel de concreción, NO copies literal):\n{$notas}";
        }

        $ediciones = $this->edicionesMarca();
        if ($ediciones) {
            $L[] = "\nCÓMO CORRIGE EL DUEÑO SUS COPYS (aprende el CRITERIO — qué hace más corto, más directo, menos cursi, más colombiano — no copies el contenido):";
            foreach (array_slice($ediciones, -8) as $e) {
                $L[] = "- En vez de «{$e['ia']}» dejó «{$e['humano']}»";
            }
        }

        $L[] = "\nNUNCA escribas frases de plantilla como estas (ni sus variantes):";
        $L[] = '- ' . implode("\n- ", self::FRASES_PROHIBIDAS);
        $L[] = 'Si una frase podría estar en la landing de cualquier otro producto, bórrala y escribe una que solo sirva para ESTE.';

        return implode("\n", $L) . "\n";
    }

    /**
     * Devuelve $valor si está en $permitidos; si no, el respaldo.
     *
     * Sustituye a un patrón que estaba copiado tres veces y que fallaba en
     * el caso que más importa — que el campo no venga en el POST:
     *
     *   in_array(trim($_POST[x] ?? 'imagen'), [...]) ? trim($_POST[x]) : 'imagen'
     *
     * La condición aplicaba el respaldo, pero la rama verdadera volvía a
     * leer $_POST[x] sin él. Sin la clave, la condición daba true con
     * 'imagen' y el resultado era trim(null) = ''. En una columna ENUM
     * NOT NULL con MySQL en modo estricto, eso tira el guardado entero.
     *
     * Aquí el valor se decide UNA vez, así que la rama verdadera no puede
     * discrepar de la condición.
     */
    private function enumValido(?string $valor, array $permitidos, string $respaldo): string
    {
        $v = trim((string)$valor);
        return in_array($v, $permitidos, true) ? $v : $respaldo;
    }

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

        $error = '';
        if (!empty($_SESSION['admin_landing_error'])) {
            $error = $_SESSION['admin_landing_error'];
            unset($_SESSION['admin_landing_error']);
        }

        $settings = new AppSettings();
        $this->view('admin/landing/index', [
            'config'             => $config,
            'success'            => $success,
            'error'              => $error,
            'producto_id'        => $productoId,
            'productos'          => $productos,
            'producto'           => $productoActual,
            'tiene_api_key'      => $settings->hasKey('claude_api_key'),
            'tiene_replicate_key'=> $settings->hasKey('replicate_api_key'),
            'brief'              => $this->leerBrief($productoId),
            'notas_marca'        => $this->notasMarca(),
            // Backlog de recomendaciones de IA para el panel del editor:
            // pendientes + resueltas (con conversión antes/después) + el
            // último análisis para la fecha del pie.
            'recomendaciones'    => (function () use ($productoId) {
                $tareas = new LandingRecoTareas();
                return [
                    'pendientes' => $tareas->pendientes($productoId),
                    'resueltas'  => $tareas->conImpacto($tareas->resueltas($productoId)),
                    'ultimo'     => (new LandingAnalisis())->ultimoDeProducto($productoId),
                ];
            })(),
        ]);
    }

    /** Producto del editor actual, para acotar los endpoints del backlog. */
    private function productoIdActual(): int
    {
        $id = (int)($_POST['producto_id'] ?? 0);
        return $id > 0 ? $id : 0;
    }

    /**
     * AJAX: marca una tarea del backlog como hecha / descartada / pendiente.
     */
    public function marcarRecomendacion(): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        $tareaId    = (int)($_POST['tarea_id'] ?? 0);
        $productoId = $this->productoIdActual();
        $estado     = (string)($_POST['estado'] ?? '');

        if ($tareaId <= 0 || $productoId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            return;
        }

        $ok = (new LandingRecoTareas())->marcar($tareaId, $productoId, $estado);
        echo json_encode(['ok' => $ok]);
    }

    /** AJAX: aplica el cambio de config de una recomendación (con Deshacer). */
    public function aplicarRecomendacion(): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        $tareaId    = (int)($_POST['tarea_id'] ?? 0);
        $productoId = $this->productoIdActual();

        if ($tareaId <= 0 || $productoId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            return;
        }

        echo json_encode((new LandingRecoTareas())->aplicar($tareaId, $productoId));
    }

    /** AJAX: revierte un cambio aplicado. */
    public function deshacerRecomendacion(): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        header('Content-Type: application/json; charset=utf-8');

        $tareaId    = (int)($_POST['tarea_id'] ?? 0);
        $productoId = $this->productoIdActual();

        if ($tareaId <= 0 || $productoId <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            return;
        }

        echo json_encode((new LandingRecoTareas())->deshacer($tareaId, $productoId));
    }

    // Copia solo el orden de secciones de otro producto hacia el actual
    public function copiarOrden()
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/AdminLanding/index");
            exit;
        }

        $productoId        = (int)($_POST['producto_id'] ?? 0);
        $productoIdOrigen  = (int)($_POST['producto_id_origen'] ?? 0);

        if ($productoId > 0 && $productoIdOrigen > 0 && $productoIdOrigen !== $productoId) {
            $productoModel = new Producto();
            if ($productoModel->obtenerPorId($productoIdOrigen)) {
                $configModel = new LandingConfig();
                if ($configModel->copiarEstructura($productoIdOrigen, $productoId)) {
                    $_SESSION['admin_landing_success'] = "Orden y secciones visibles copiados correctamente.";
                } else {
                    $_SESSION['admin_landing_error'] = "No se pudo copiar la estructura de secciones.";
                }
            } else {
                $_SESSION['admin_landing_error'] = "El producto de origen no existe.";
            }
        } else {
            $_SESSION['admin_landing_error'] = "Selecciona un producto de origen válido.";
        }

        header("Location: " . BASE_URL . "/AdminLanding/index?producto_id=" . $productoId);
        exit;
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
            /* El ?? tiene que estar en las DOS ramas, no sólo en la condición.
               Estaba así: in_array(trim($_POST[x] ?? 'imagen'), ...) ? trim($_POST[x]) : 'imagen'
               Cuando el campo no viene en el POST, la condición evalúa
               'imagen' y da true — pero la rama verdadera vuelve a leer
               $_POST[x], que no existe, y trim(null) devuelve ''. La columna
               es ENUM NOT NULL, así que con MySQL en modo estricto el
               guardado entero revienta con 1265 Data truncated.
               Y este campo concreto ya NO está en el formulario del editor,
               así que le pasaba en cada guardado: el editor no podía
               guardar nada. */
            'benefits_media_type' => $this->enumValido($_POST['benefits_media_type'] ?? null, ['imagen', 'video', 'gif'], 'imagen'),
            'benefit_1'      => trim($_POST['benefit_1'] ?? ''),
            'benefit_2'      => trim($_POST['benefit_2'] ?? ''),
            'benefit_3'      => trim($_POST['benefit_3'] ?? ''),
            'benefit_4'      => trim($_POST['benefit_4'] ?? ''),

            'countdown_title' => trim($_POST['countdown_title'] ?? ''),
            'countdown_text'  => trim($_POST['countdown_text'] ?? ''),

            'porque_title'   => trim($_POST['porque_title'] ?? ''),
            // Mismo patrón roto que benefits_media_type. Aquí no llegaba a
            // fallar porque el campo sí está en el formulario, pero era una
            // bomba de relojería: el día que se quite del editor, revienta.
            'porque_media_type' => $this->enumValido($_POST['porque_media_type'] ?? null, ['imagen', 'video', 'gif'], 'imagen'),
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

            // ===== Analítica — vacío usa el valor por defecto del código =====
            'pixel_id'   => mb_substr(trim($_POST['pixel_id']   ?? ''), 0, 50),
            'clarity_id' => mb_substr(trim($_POST['clarity_id'] ?? ''), 0, 50),

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
            'authority_enabled'    => (int)($_POST['authority_enabled'] ?? 0),
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
            'form_kicker'   => trim($_POST['form_kicker']   ?? ''),
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
            // Tercer sitio con el mismo patrón. Esta columna es text y no
            // enum, así que un '' no reventaba — pero dejaba el tipo de
            // medio en blanco y la característica se pintaba como imagen
            // aunque fuera un vídeo.
            $data["caract{$i}_media_type"] = $this->enumValido($_POST["caract{$i}_media_type"] ?? null, ['image', 'video', 'gif'], 'image');
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

        /* Tema — la lista sale de app/config/themes.php, no se escribe aquí.
           Cuando esta whitelist se mantenía a mano se quedó sin
           midnight-amber, así que el servidor lo rechazaba y guardaba
           'dark-luxury' sin decir nada: el admin elegía un tema y la
           landing salía con los colores del anterior.
           resolverTema() además traduce los slugs retirados en la poda de
           nueve temas a cinco, para que no caigan al por defecto. */
        $data['theme'] = LandingConfig::resolverTema(trim($_POST['theme'] ?? ''));

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

        // Variantes de color — se construye un JSON desde los campos del form
        // Los paths actuales vienen de hidden inputs; las subidas nuevas se procesan abajo
        // y reemplazan los paths en el array antes de codificar a JSON
        $colorVariantsRaw = [];
        for ($ci = 1; $ci <= 4; $ci++) {
            $cName = trim($_POST["cv{$ci}_name"] ?? '');
            $cHex  = trim($_POST["cv{$ci}_hex"]  ?? '');
            if ($cName === '' && $cHex === '') continue;
            $colorVariantsRaw[$ci] = [
                'name'   => $cName,
                'hex'    => preg_match('/^#[0-9A-Fa-f]{6}$/', $cHex) ? $cHex : '#000000',
                'images' => [
                    $_POST["cv{$ci}_g1_actual"] ?? '',
                    $_POST["cv{$ci}_g2_actual"] ?? '',
                    $_POST["cv{$ci}_g3_actual"] ?? '',
                    $_POST["cv{$ci}_g4_actual"] ?? '',
                ],
            ];
        }

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

        // Archivos de variantes de color (cv1_g1_file … cv4_g4_file) — guardados en _tmp_cv_*
        // para después inyectarlos en el JSON
        for ($ci = 1; $ci <= 4; $ci++) {
            for ($gi = 1; $gi <= 4; $gi++) {
                $fileMap["cv{$ci}_g{$gi}_file"] = "_tmp_cv{$ci}_g{$gi}";
            }
        }

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

        // Inyectar paths de imágenes subidas en las variantes de color y guardar JSON
        for ($ci = 1; $ci <= 4; $ci++) {
            for ($gi = 1; $gi <= 4; $gi++) {
                $tmpKey = "_tmp_cv{$ci}_g{$gi}";
                if (!empty($data[$tmpKey]) && isset($colorVariantsRaw[$ci])) {
                    $colorVariantsRaw[$ci]['images'][$gi - 1] = $data[$tmpKey];
                }
                unset($data[$tmpKey]);
            }
        }
        $finalVariants = array_values(array_filter($colorVariantsRaw, fn($v) => trim($v['name']) !== ''));
        $data['color_variants'] = empty($finalVariants) ? null : json_encode($finalVariants, JSON_UNESCAPED_UNICODE);

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

        $producto       = trim($_POST['producto']        ?? '');
        $descripcion    = trim($_POST['descripcion']     ?? '');
        $seccion        = trim($_POST['seccion']          ?? 'hero');
        $promptActual   = trim($_POST['prompt_actual']    ?? '');
        $seccionTitulo  = trim($_POST['seccion_titulo']   ?? '');
        $seccionTexto   = trim($_POST['seccion_texto']    ?? '');

        // Contexto fallback por sección (se usa solo si no hay título/texto reales)
        $ctxMap = [
            'hero'               => 'hero image of the product, professional studio photography, clean elegant background',
            'benefits'           => 'lifestyle photo showing the product benefit in use, warm natural light',
            'benefit_1'          => 'image illustrating this product benefit, clean lifestyle photo',
            'benefit_2'          => 'image illustrating this product benefit, clean lifestyle photo',
            'benefit_3'          => 'image illustrating this product benefit, clean lifestyle photo',
            'benefit_4'          => 'image illustrating this product benefit, clean lifestyle photo',
            'gallery_1'          => 'detailed product shot showing quality and finish, studio lighting',
            'gallery_2'          => 'product from a different angle, showing unique design details',
            'gallery_3'          => 'product in real-life use context, lifestyle photography',
            'gallery_4'          => 'product with packaging and accessories, flat lay composition',
            'porque'             => 'emotional image showing positive transformation the product brings',
            'comparison_without' => 'situation WITHOUT the product, subtle frustration or inconvenience',
            'comparison_with'    => 'ideal situation WITH the product, happy satisfied person, warm light',
            'test1_banner'       => 'satisfied Colombian customer holding or using the product, genuine smile',
            'test2_banner'       => 'happy Colombian customer with the product, different setting',
            'test3_banner'       => 'customer showing the received product, unboxing or in-use moment',
        ];

        // Si tenemos el contenido real de la sección, úsalo como contexto principal
        if ($seccionTitulo || $seccionTexto) {
            $ctx = "section titled \"{$seccionTitulo}\": {$seccionTexto}";
        } else {
            $ctx = $ctxMap[$seccion] ?? 'professional product image for e-commerce landing page';
        }

        if ($promptActual) {
            $msg = "Improve this Flux AI image prompt: \"{$promptActual}\"\n\nProduct: {$producto}. {$descripcion}\nSection: {$ctx}\n\nReturn ONLY the improved prompt in English, detailed, professional. No explanations, no quotes. Max 150 words.";
        } else {
            $msg = "Write an English image prompt for Flux AI for this landing page section:\n\nProduct: {$producto}\nProduct description: {$descripcion}\nSection: {$ctx}\n\nThe prompt must describe: photorealistic commercial photography, lighting, composition, mood. If a reference photo of the product will be used, write the prompt as an EDIT INSTRUCTION (e.g. 'Place this product on...', 'Show this product...').\n\nReturn ONLY the prompt in English. No explanations, no quotes. Max 120 words.";
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
            'benefit_1'          => '1:1',  'benefit_2' => '1:1',
            'benefit_3'          => '1:1',  'benefit_4' => '1:1',
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
            'benefit_1'          => [600,  600],  'benefit_2' => [600, 600],
            'benefit_3'          => [600,  600],  'benefit_4' => [600, 600],
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

        $productoId  = (int)($_POST['producto_id'] ?? 0);
        $seccion     = trim($_POST['seccion']     ?? '');
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $publico     = trim($_POST['publico']      ?? 'adultos colombianos');
        $precio      = trim($_POST['precio']      ?? '');
        $extra       = trim($_POST['extra']        ?? '');
        $n           = max(1, min(3, (int)($_POST['n'] ?? 1)));

        if (!$seccion || !$nombre) {
            echo json_encode(['ok' => false, 'error' => 'Faltan datos del producto']);
            return;
        }

        // El brief guardado del producto mantiene coherente el ángulo entre
        // la landing completa y cada regeneración de sección.
        $bloque = $productoId > 0 ? $this->bloqueBrief($this->leerBrief($productoId)) : '';

        $prompt = $this->buildSeccionPrompt($seccion, $nombre, $descripcion, $publico, $precio, $extra, $bloque, $n);
        if (!$prompt) {
            echo json_encode(['ok' => false, 'error' => 'Sección no reconocida']);
            return;
        }

        $res = $this->callClaudeApi($apiKey, $prompt, $n > 1 ? 6000 : 4096);

        if ($n > 1) {
            if (empty($res['ok'])) { echo json_encode($res); return; }
            $vs = $res['fields']['variantes'] ?? [];
            $vs = is_array($vs) ? array_values(array_filter($vs, 'is_array')) : [];
            if (!$vs) {
                echo json_encode(['ok' => false, 'error' => 'La IA no devolvió versiones. Intenta de nuevo.']);
                return;
            }
            echo json_encode(['ok' => true, 'variantes' => $vs]);
            return;
        }

        echo json_encode($res);
    }

    // ── Prompt focalizado por sección ─────────────────────────────────────────
    private function buildSeccionPrompt(string $sec, string $nombre, string $desc, string $publico, string $precio, string $extra, string $briefBlock = '', int $n = 1): ?string
    {
        $anguloYaFijo = str_contains($briefBlock, 'ÁNGULO DE VENTA YA DECIDIDO');
        $paso0 = $anguloYaFijo
            ? "El dolor y el ángulo YA están decididos arriba. No elijas otro ni lo generalices: escribe esta sección desde ese ángulo.\n\n"
            : "\nANTES DE ESCRIBIR (no lo muestres en la respuesta): identifica UN solo dolor o frustración concreta que este producto resuelve para este público. Todo el copy de esta sección debe ser ese mismo dolor contado desde el ángulo de esta sección — no lo cambies ni lo generalices.\n\n";

        $base = "Eres experto en copywriting de alta conversión para e-commerce colombiano (dropshipping). No vendes el producto: vendes el alivio de un dolor concreto.\n"
              . "Producto: {$nombre}" . ($desc ? " — {$desc}" : '') . "\n"
              . "Público: {$publico}" . ($precio ? " · Precio: {$precio} COP" : '') . "\n"
              . ($extra ? "Instrucciones adicionales (tienen prioridad): {$extra}\n" : '')
              . ($briefBlock !== '' ? "\n{$briefBlock}\n" : '')
              . $paso0
              . "REGLAS: español colombiano informal, emocional, orientado al beneficio (nunca a características técnicas sueltas), "
              . "pago contraentrega, urgencia real, nombres/ciudades colombianas. Frases cortas, directo al punto, sin párrafos largos ni relleno. "
              . "Emojis solo cuando sumen (✅🔥📦⏰😍🚚), máximo 1-2 por texto, nunca en nombres/ciudades ni en preguntas de FAQ.\n\n"
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

            'autoridad' => '{"authority_title":"","authority_years":"2","authority_deliveries":"800+","authority_rating":"4.9","authority_guarantee":"Satisfacción garantizada"}',

            'ctas' => '{"cta_benefits_text":"","cta_benefits_button":"","cta_gallery_text":"","cta_gallery_button":"","cta_porque_text":"","cta_porque_button":"","cta_testimonials_text":"","cta_testimonials_button":"","cta_faq_text":"","cta_faq_button":"","cta_como_funciona_text":"","cta_como_funciona_button":"","cta_comparison_button":"","cta_para_quien_button":"","cta_wa_testimonios_button":"","cta_sticky_mobile_text":""}',

            'form' => '{"form_kicker":"","form_title":"","form_subtitle":""}',

            'announcement' => '{"announcement_item_1":"","announcement_item_2":"","announcement_item_3":"","announcement_item_4":"","announcement_item_5":"","announcement_item_6":""}',
        ];

        if (!isset($schemas[$sec])) return null;

        $hints = [
            'hero'            => 'Hero: título ≤8 palabras que nombra el dolor o promete su alivio (no describe el producto). Subtítulo agita ese dolor. hero_note menciona pago contraentrega.',
            'beneficios'      => 'Beneficios: cada uno es una consecuencia concreta de seguir sin el producto, resuelta — nunca una característica técnica.',
            'caracteristicas' => 'Características: cada texto conecta la característica física con el alivio emocional que produce (característica → por qué le importa a alguien con ese dolor).',
            'countdown'       => 'Countdown: escasez + pérdida inminente (loss aversion) — el cliente pierde la chance de resolver su dolor, no solo "una oferta".',
            'porque'          => 'Por qué: estructura Problema → Agitación → Solución. porque_text nombra el dolor, muestra el costo de ignorarlo, y lo resuelve. Es el párrafo más persuasivo de la landing.',
            'comparativa'     => 'Comparativa: SIN el producto = el dolor en una escena de vida real; CON el producto = esa escena resuelta. Nunca specs.',
            'testimonios'     => 'Testimonios: prueba social — cada uno es alguien que vivía ese mismo dolor y lo resolvió. Nombres y ciudades colombianas 100% reales. Textos ≤100 chars, muy naturales.',
            'paraquien'       => 'Para quién: los "Sí" describen a quien tiene el dolor (identificación); los "No" describen a quien no lo tiene (califica y genera FOMO inverso).',
            'wa'              => 'WhatsApp: prueba social informal del mismo dolor resuelto. Mensajes ultra-informales, emojis naturales, como copiados del celular de un cliente feliz.',
            'faq'             => 'FAQ: cada pregunta es una objeción real que frena la compra (miedo a perder la plata); la respuesta baja ese riesgo. faq1 SIEMPRE sobre pago (contraentrega), faq2 sobre tiempo de envío (3-7 días hábiles Colombia).',
            'autoridad'       => 'Autoridad: 4 fichas de estadística (cifra grande + etiqueta fija). Los valores van CORTOS, jamás una frase — una oración dentro de la cifra rompe la tarjeta. authority_years: SOLO el número de años, sin palabras (ej "2"; la plantilla ya escribe "años en el mercado" y le añade un "+"). authority_deliveries: SOLO una cifra de pedidos en formato colombiano (ej "800+", "5.000+"), sin texto. authority_rating: la nota sobre 5 (ej "4.9"). authority_guarantee: la ÚNICA de texto y aun así máximo 3 palabras (ej "Satisfacción garantizada", "Devolución sin líos"). authority_title sí puede ser una pregunta o frase corta ("¿Por qué confiar en nosotros?"). Números creíbles para una marca joven; nunca inventes miles si el negocio es nuevo.',
            'ctas'            => 'CTAs: directo al grano, cero rodeos. Botón ≤5 palabras, verbo de acción + urgencia (emoji opcional si suma, ej 🔥⏰). cta_*_text: una sola frase corta que empuje al clic, no una explicación.',
            'form'            => 'Cabecera del formulario de pedido — es lo último que lee antes de dejar sus datos. form_kicker: micro-aviso de impulso ≤7 palabras que baja la fricción ("Último paso · te toma menos de 1 minuto"), sin emoji. form_title: acción + tranquilidad de pagar al recibir, ≤7 palabras. form_subtitle: una frase que quita el miedo (sin adelantos, el mensajero llega a la puerta). Nada de urgencia agresiva aquí: ya decidió, solo hay que quitarle el último freno.',
            'announcement'    => 'Barra de anuncios: el ticker que corre arriba de todo. Cada ítem es una frase de 2-5 palabras que abre con UN emoji relevante + espacio (🔥 escasez, 🚚 envío, 💳 pago contraentrega, ⭐ o 😍 prueba social, 📦 empaque, ⏰ urgencia) — es la única zona de la landing donde el emoji suma. Un emoji por ítem, al inicio, nunca en medio de la frase. Mezcla urgencia real, envío/pago y prueba social. Llena los 6; deben leerse bien girando en bucle.',
        ];

        if ($n > 1) {
            return $base . ($hints[$sec] ?? '')
                . "\n\nDame {$n} versiones DISTINTAS de esta sección: cada una entra por un ángulo emocional diferente (no la misma frase reordenada), todas listas para publicar y coherentes con el ángulo, la voz y el nivel de agresividad de arriba.\n"
                . "Devuelve SOLO este JSON válido (sin markdown): {\"variantes\": [OBJ, OBJ, OBJ]} — donde cada OBJ tiene EXACTAMENTE esta forma y todos sus campos llenos:\n"
                . $schemas[$sec];
        }

        return $base . ($hints[$sec] ?? '') . "\n\nJSON a completar:\n" . $schemas[$sec];
    }

    // ── Propone el brief (público + dolor real) desde la descripción ─────────
    public function sugerirBriefIA()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $apiKey = (new AppSettings())->get('claude_api_key');
        if (!$apiKey) { echo json_encode(['ok' => false, 'error' => 'no_key']); return; }

        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio      = trim($_POST['precio']      ?? '');

        if (!$nombre && !$descripcion) {
            echo json_encode(['ok' => false, 'error' => 'Describe el producto primero (aunque sea en una frase).']);
            return;
        }

        $precioLine = $precio ? "- Precio: {$precio} COP\n" : '';
        $vocesLista = implode(', ', array_keys(self::VOCES));

        $prompt = <<<PROMPT
Eres estratega de marketing de respuesta directa para e-commerce colombiano (dropshipping, pago contraentrega, tráfico frío de Facebook/TikTok). Te dan un producto y tu trabajo es armar el BRIEF: a quién le duele algo que este producto resuelve, y cuál es ese dolor — pero un dolor que de verdad mueve la intención de compra, no una generalidad.

PRODUCTO:
- Nombre: {$nombre}
- Descripción: {$descripcion}
{$precioLine}
QUÉ HACE A UN DOLOR "QUE VENDE" (aplícalo):
- Es una escena concreta y repetida de la vida real, no un concepto ("se me revienta la bolsa del mercado en plena calle", no "quiero practicidad").
- Tiene consecuencia: vergüenza frente a otros, plata perdida, tiempo perdido, incomodidad física, un plan arruinado.
- La persona ya intentó resolverlo y lo que usa hoy no le alcanza.
- Es urgente o recurrente: le pasa seguido y le molesta cada vez.
Evita dolores genéricos tipo "quiere verse bien", "busca calidad", "quiere ahorrar".

Elige el público donde ese dolor sea más agudo y más frecuente (no "todos"). Sé específico: momento de vida, ocupación, contexto colombiano.

Devuelve SOLO este JSON válido, sin markdown:
{
  "publico": "público objetivo concreto en una frase (quién, edad aproximada, situación de vida)",
  "avatar": "retrato en 1-2 frases: cómo es su día, qué le importa, cómo habla",
  "escena": "la escena exacta y cotidiana en la que siente el dolor",
  "dolor_principal": "el dolor que más aumenta la intención de compra, en una frase y en la voz del cliente",
  "objecion": "la objeción #1 que lo frena para comprar por internet a una marca nueva",
  "alternativa": "qué usa o hace hoy en vez de comprar este producto, y por qué no le alcanza",
  "voz": "una de: {$vocesLista}",
  "agresividad": 3
}
PROMPT;

        $res = $this->callClaudeApi($apiKey, $prompt, 1500);
        if (empty($res['ok'])) { echo json_encode($res); return; }

        $b = $res['fields'] ?? [];
        if (!is_array($b) || empty($b['publico'])) {
            echo json_encode(['ok' => false, 'error' => 'La IA no devolvió un brief válido. Intenta de nuevo.']);
            return;
        }

        $voz = trim((string)($b['voz'] ?? 'cercana'));
        if (!isset(self::VOCES[$voz])) $voz = 'cercana';
        $ag = (int)($b['agresividad'] ?? 3);
        $ag = max(1, min(5, $ag));

        echo json_encode(['ok' => true, 'brief' => [
            'publico'         => trim((string)($b['publico'] ?? '')),
            'avatar'          => trim((string)($b['avatar'] ?? '')),
            'escena'          => trim((string)($b['escena'] ?? '')),
            'dolor_principal' => trim((string)($b['dolor_principal'] ?? '')),
            'objecion'        => trim((string)($b['objecion'] ?? '')),
            'alternativa'     => trim((string)($b['alternativa'] ?? '')),
            'voz'             => $voz,
            'agresividad'     => $ag,
        ]]);
    }

    // ── Propone 3 ángulos de venta ANTES de escribir la landing ──────────────
    public function generarAngulosIA()
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            return;
        }

        $apiKey = (new AppSettings())->get('claude_api_key');
        if (!$apiKey) { echo json_encode(['ok' => false, 'error' => 'no_key']); return; }

        $productoId  = (int)($_POST['producto_id'] ?? 0);
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $publico     = trim($_POST['publico']     ?? 'adultos colombianos');
        $precio      = trim($_POST['precio']      ?? '');

        if (!$nombre || !$descripcion) {
            echo json_encode(['ok' => false, 'error' => 'El nombre y la descripción son requeridos']);
            return;
        }

        // Guardamos el brief (sin ángulo aún) y las notas de marca ya mismo.
        $brief = $this->briefDesdePost();
        $brief['angulo'] = ['dolor' => '', 'gran_idea' => '', 'headline' => '', 'a_quien' => ''];
        $this->guardarBrief($productoId, $brief);
        $this->guardarNotasMarcaDesdePost();

        $precioLine = $precio ? "- Precio: {$precio} COP" : '';
        $bloque     = $this->bloqueBrief($brief);

        $prompt = <<<PROMPT
Eres estratega de dirección creativa para e-commerce colombiano (dropshipping, pago contraentrega). Tu trabajo AHORA no es escribir la landing, sino proponer 3 ÁNGULOS DE VENTA distintos entre los que el dueño va a elegir uno.

PRODUCTO:
- Nombre: {$nombre}
- Descripción: {$descripcion}
- Público: {$publico}
{$precioLine}

{$bloque}

Un ángulo = un dolor concreto + una gran idea que lo resuelve + a quién le pega más fuerte. Los 3 ángulos deben atacar dolores DISTINTOS (no el mismo con otras palabras) y ser tan específicos que el cliente piense "esto es exactamente lo que me pasa". Nada de generalidades tipo "quieres verte bien".

Para cada ángulo:
- "dolor": la frustración concreta que vive hoy, en una frase y en la voz del cliente.
- "gran_idea": el giro que vuelve a este producto LA solución a ese dolor (no una lista de features).
- "headline": un titular de ejemplo (≤10 palabras) que abre con ese dolor o su alivio.
- "a_quien": en una línea, a qué tipo de persona le pega más fuerte este ángulo.
- "por_que": una frase de por qué podría convertir en tráfico frío de Facebook/TikTok.

Devuelve SOLO este JSON válido, sin markdown ni texto alrededor:
{"angulos":[{"dolor":"","gran_idea":"","headline":"","a_quien":"","por_que":""},{"dolor":"","gran_idea":"","headline":"","a_quien":"","por_que":""},{"dolor":"","gran_idea":"","headline":"","a_quien":"","por_que":""}]}
PROMPT;

        $res = $this->callClaudeApi($apiKey, $prompt, 2000);
        if (empty($res['ok'])) { echo json_encode($res); return; }

        $angulos = $res['fields']['angulos'] ?? null;
        if (!is_array($angulos) || !$angulos) {
            echo json_encode(['ok' => false, 'error' => 'La IA no devolvió ángulos válidos. Intenta de nuevo.']);
            return;
        }

        echo json_encode(['ok' => true, 'angulos' => array_values($angulos)]);
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

        $productoId  = (int)($_POST['producto_id'] ?? 0);
        $nombre      = trim($_POST['nombre']      ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $publico     = trim($_POST['publico']     ?? 'adultos colombianos');
        $precio      = trim($_POST['precio']      ?? '');

        if (!$nombre || !$descripcion) {
            echo json_encode(['ok' => false, 'error' => 'El nombre y la descripción son requeridos']);
            return;
        }

        $brief = $this->briefDesdePost();
        $this->guardarBrief($productoId, $brief);
        $this->guardarNotasMarcaDesdePost();

        $bloque     = $this->bloqueBrief($brief);
        $anguloFijo = $this->tieneAngulo($brief);

        // El front pide un lote por vez ('gancho' | 'prueba' | 'cierre') para
        // que cada llamada sea corta y con más filo. Sin lote → los 3 seguidos.
        $loteReq = trim($_POST['lote'] ?? '');
        $lotes   = in_array($loteReq, self::LOTES_COPY, true) ? [$loteReq] : self::LOTES_COPY;
        if (count($lotes) > 1) @set_time_limit(220);

        $fields = [];
        foreach ($lotes as $l) {
            $prompt = $this->buildColombianPrompt($nombre, $descripcion, $publico, $precio, $bloque, $anguloFijo, $l);
            $r = $this->callClaudeApi($apiKey, $prompt, 4000);
            if (empty($r['ok'])) {
                echo json_encode($r + ['lote' => $l, 'fields' => $fields]);
                return;
            }
            foreach (($r['fields'] ?? []) as $k => $v) {
                if ($v !== '' && $v !== null) $fields[$k] = $v;
            }
        }

        echo json_encode(['ok' => true, 'fields' => $fields, 'lote' => $loteReq]);
    }

    /** Lotes en que se parte la generación de la landing completa. */
    private const LOTES_COPY = ['gancho', 'prueba', 'cierre'];

    /**
     * Campos de cada lote + la guía de "cómo se usa el dolor" para esos campos.
     * Cada lote reescribe _dolor/_angulo para mantener el ancla estratégica.
     */
    private function loteCopy(string $lote): array
    {
        $grupos = [
            'gancho' => [
                'guia' => "- hero_title / hero_subtitle: nombra el dolor o la promesa de alivio inmediato — no describas el producto.\n"
                    . "- benefit_1 a benefit_4: cada uno es una CONSECUENCIA concreta de seguir sin el producto, resuelta — no una característica técnica.\n"
                    . "- caract1 a caract4: conecta cada característica física con el alivio emocional que produce.\n"
                    . "- countdown: escasez + pérdida inminente (el cliente pierde la oportunidad de resolver su dolor, no solo 'una oferta').\n"
                    . "- porque_text: estructura Problema → Agitación → Solución. Nombra el dolor, muestra el costo de ignorarlo, y resuelve. Es el párrafo más persuasivo de la landing.",
                'keys' => [
                    'hero_title', 'hero_subtitle', 'hero_button_text', 'hero_note', 'hero_badge_customers',
                    'benefits_title', 'benefit_1', 'benefit_2', 'benefit_3', 'benefit_4',
                    'caract_section_title',
                    'caract1_title', 'caract1_text', 'caract2_title', 'caract2_text',
                    'caract3_title', 'caract3_text', 'caract4_title', 'caract4_text',
                    'countdown_title', 'countdown_text',
                    'porque_title', 'porque_text', 'porque_bullet1', 'porque_bullet2', 'porque_bullet3',
                ],
            ],
            'prueba' => [
                'guia' => "- comparison_*: SIN el producto = el dolor en una escena de vida real; CON el producto = esa misma escena resuelta. Nunca specs.\n"
                    . "- test1-3: prueba social — cada testimonio es alguien que vivía ESE dolor y lo resolvió, en su propia voz. Nombres y ciudades colombianas reales.\n"
                    . "- para_quien_si_*: describen a quien tiene el dolor (identificación); para_quien_no_*: a quien no lo tiene (califica y genera FOMO inverso).\n"
                    . "- wa1-5: prueba social informal, mismo dolor resuelto, tono 100% casero, como copiado del celular.",
                'keys' => [
                    'comparison_title', 'comparison_label_without', 'comparison_label_with',
                    'comparison_1_without', 'comparison_1_with', 'comparison_2_without', 'comparison_2_with',
                    'comparison_3_without', 'comparison_3_with', 'comparison_4_without', 'comparison_4_with',
                    'comparison_5_without', 'comparison_5_with',
                    'test1_name', 'test1_city', 'test1_text', 'test2_name', 'test2_city', 'test2_text',
                    'test3_name', 'test3_city', 'test3_text',
                    'para_quien_si_1', 'para_quien_si_2', 'para_quien_si_3', 'para_quien_si_4',
                    'para_quien_no_1', 'para_quien_no_2', 'para_quien_no_3',
                    'wa_title', 'wa_subtitle', 'wa_footer_note',
                    'wa1_name', 'wa1_time', 'wa1_text', 'wa2_name', 'wa2_time', 'wa2_text',
                    'wa3_name', 'wa3_time', 'wa3_text', 'wa4_name', 'wa4_time', 'wa4_text',
                    'wa5_name', 'wa5_time', 'wa5_text',
                ],
            ],
            'cierre' => [
                'guia' => "- faq1-6: cada pregunta es una objeción real que le impide comprar (duda = miedo a perder la plata); la respuesta baja ese riesgo. faq1 SIEMPRE sobre pago contraentrega, faq2 sobre tiempo de envío (3-7 días hábiles).\n"
                    . "- authority_*: reduce el riesgo percibido de confiarle ese dolor a una marca nueva. Números creíbles.\n"
                    . "- cta_*: urgencia para actuar YA y dejar de vivir con el dolor. Botón ≤5 palabras, verbo + urgencia.",
                'keys' => [
                    'faq1_q', 'faq1_a', 'faq2_q', 'faq2_a', 'faq3_q', 'faq3_a',
                    'faq4_q', 'faq4_a', 'faq5_q', 'faq5_a', 'faq6_q', 'faq6_a',
                    'authority_title', 'authority_years', 'authority_deliveries', 'authority_rating', 'authority_guarantee',
                    'cta_benefits_text', 'cta_benefits_button', 'cta_gallery_text', 'cta_gallery_button',
                    'cta_porque_text', 'cta_porque_button', 'cta_testimonials_text', 'cta_testimonials_button',
                    'cta_faq_text', 'cta_faq_button', 'cta_como_funciona_text', 'cta_como_funciona_button',
                    'cta_comparison_button', 'cta_para_quien_button', 'cta_wa_testimonios_button', 'cta_sticky_mobile_text',
                ],
            ],
        ];

        if (!isset($grupos[$lote])) {
            // Landing completa: todos los campos, toda la guía.
            $keys = [];
            $guia = [];
            foreach ($grupos as $g) {
                $keys = array_merge($keys, $g['keys']);
                $guia[] = $g['guia'];
            }
            $guia = implode("\n", $guia);
        } else {
            $keys = $grupos[$lote]['keys'];
            $guia = $grupos[$lote]['guia'];
        }

        $obj = ['_dolor' => '', '_angulo' => ''];
        foreach ($keys as $k) $obj[$k] = '';
        if (isset($obj['comparison_label_without'])) $obj['comparison_label_without'] = 'Sin el producto';
        if (isset($obj['comparison_label_with']))    $obj['comparison_label_with']    = 'Con el producto';
        if (isset($obj['authority_rating']))         $obj['authority_rating']         = '4.9';

        return [
            'guia' => $guia,
            'json' => json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    // ── Prompt optimizado para conversión colombiana ──────────────────────────
    private function buildColombianPrompt(
        string $nombre, string $descripcion, string $publico, string $precio,
        string $briefBlock = '', bool $anguloFijo = false, string $lote = ''
    ): string {
        $precioLine = $precio ? "- Precio: $precio COP" : '';

        $briefSection = $briefBlock !== '' ? "\n{$briefBlock}\n" : '';

        $partes     = $this->loteCopy($lote);
        $guiaLote   = $partes['guia'];
        $jsonSchema = $partes['json'];

        $enfoque = in_array($lote, self::LOTES_COPY, true)
            ? "Ahora escribes SOLO el lote \"{$lote}\" de la landing (los campos del JSON de abajo). El resto de la landing se escribe aparte, así que concéntrate en estos campos y dales el máximo filo.\n\n"
            : '';

        $paso0 = $anguloFijo
            ? "PASO 0 — El ángulo y el dolor central YA están decididos (arriba). No elijas otro, no lo generalices, no lo cambies por sección. Salta directo a escribir cada campo desde ese ángulo."
            : "PASO 0 — ANTES DE ESCRIBIR (no lo muestres en la respuesta):\nIdentifica UN solo dolor o frustración central que este producto resuelve para este público — algo concreto que la persona vive hoy sin el producto (una molestia física, una vergüenza social, una pérdida de tiempo o dinero, un miedo). Todo el copy de las ~60 variables debe ser ese mismo dolor contado desde ángulos distintos: nunca inventes un dolor nuevo por sección.";

        return <<<PROMPT
Eres el mejor copywriter de e-commerce colombiano. Tu especialidad es escribir textos que VENDEN para dropshipping en Colombia. No vendes un producto: vendes el alivio de un dolor específico. Tu copy convierte porque habla exactamente como el colombiano real: cálido, directo, con urgencia genuina.

PRODUCTO A TRABAJAR:
- Nombre: {$nombre}
- Descripción: {$descripcion}
- Público objetivo: {$publico}
{$precioLine}
{$briefSection}
{$paso0}

{$enfoque}CÓMO SE USA EL DOLOR EN ESTOS CAMPOS (psicología de venta aplicada):
{$guiaLote}

REGLAS OBLIGATORIAS DE ESTILO (romperlas es inaceptable):
1. Español colombiano 100% natural. Tuteo informal. NADA de "usted" en CTAs o textos de urgencia.
2. Cada texto conecta con el MISMO dolor identificado en el Paso 0 — nunca hables de características técnicas sueltas.
3. PAGO CONTRAENTREGA es el argumento de confianza #1. Mencionarlo en hero_note, FAQ y testimonios.
4. Urgencia real: "quedan pocas unidades", "solo por hoy", "la oferta termina pronto".
5. Testimonios con nombres colombianos auténticos y ciudades colombianas reales (Bogotá, Medellín, Cali, Barranquilla, Bucaramanga, Pereira, Manizales, Santa Marta, Ibagué, Cúcuta, Cartagena).
6. Mensajes de WhatsApp ultra-naturales: como si fueran copiados del celular de un cliente feliz (emojis reales, ortografía casi perfecta pero informal).
7. FAQ siempre incluye: pago contraentrega, tiempo de envío (3-7 días hábiles), garantía, devoluciones.
8. Hero title: máximo 8 palabras. Promesa de transformación o resultado, no descripción del producto.
9. Comparativa: TRANSFORMACIÓN EMOCIONAL antes/después (no listas de specs).
10. CTAs (cta_*_button y cta_*_text): directo al grano, cero rodeos, cero explicación. Botón ≤5 palabras con verbo de acción + urgencia. Ej: "¡Lo quiero ahora! 🔥" / "Pedir el mío →" / "Aprovechar oferta ⏰".
11. BREVEDAD en todos los campos: frases cortas, sin relleno ni párrafos largos. Si se dice en menos palabras, así se dice.
12. Emojis solo cuando sumen al mensaje (✅🔥📦⏰😍🚚), sin saturar — 1 o 2 por texto como máximo. Nunca en nombres/ciudades de testimonios ni en preguntas de FAQ.

Devuelve ÚNICAMENTE el siguiente JSON válido. Sin markdown, sin bloques de código, sin texto antes o después. Solo el JSON.
Los dos primeros campos ("_dolor" y "_angulo") son notas para el dueño (no se publican): en una frase cada uno, di qué dolor estás atacando y cuál es la gran idea con la que lo resuelves. El resto del copy debe ser coherente con esas dos frases.

{$jsonSchema}
PROMPT;
    }

    // ── Llama a la API de Claude y devuelve array resultado ───────────────────
    private function callClaudeApi(string $apiKey, string $prompt, int $maxTokens = 4096): array
    {
        $payload = json_encode([
            'model'      => self::COPY_MODEL,
            'max_tokens' => $maxTokens,
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
            CURLOPT_TIMEOUT        => 120,
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

    // ── Replicate: Kontext Pro (con referencia) o Flux 1.1 Pro (sin referencia) ─
    private function callReplicateFlux(string $apiKey, string $prompt, string $aspectRatio, ?string $imageUrl = null, float $promptStrength = 0.80): string|array
    {
        if ($imageUrl) {
            // Flux Kontext Pro: edita preservando la identidad del producto
            $endpoint = 'https://api.replicate.com/v1/models/black-forest-labs/flux-kontext-pro/predictions';
            $input = [
                'prompt'           => $prompt,
                'input_image'      => $imageUrl,
                'output_format'    => 'jpg',
                'safety_tolerance' => 2,
                'aspect_ratio'     => $aspectRatio,
            ];
        } else {
            // Flux 1.1 Pro: text-to-image puro sin referencia
            $endpoint = 'https://api.replicate.com/v1/models/black-forest-labs/flux-1.1-pro/predictions';
            $input = [
                'prompt'           => $prompt,
                'aspect_ratio'     => $aspectRatio,
                'output_format'    => 'webp',
                'output_quality'   => 85,
                'safety_tolerance' => 2,
            ];
        }

        $payload = json_encode(['input' => $input]);

        $ch = curl_init($endpoint);
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
