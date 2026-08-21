<?php

/**
 * Panel de estadísticas de la landing.
 *
 * Lee lo que guarda LandingAnalytics y lo presenta como embudo: cuánta gente
 * entró, hasta dónde llegó y en qué paso se cayó la intención de compra.
 * El botón "Analizar" manda esos mismos agregados a Claude para que diga qué
 * hacer con ellos (ver LandingAnalisis).
 */
class AdminEstadisticasController extends Controller
{
    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/Auth/login');
            exit;
        }
    }

    /** Días permitidos en el selector de periodo. */
    private function dias(): int
    {
        $dias = (int)($_GET['dias'] ?? 7);
        return in_array($dias, [1, 7, 15, 30, 90], true) ? $dias : 7;
    }

    private function entorno(): string
    {
        return ($_GET['entorno'] ?? 'produccion') === 'local' ? 'local' : 'produccion';
    }

    /**
     * El rango arranca a las 00:00 del primer día y termina mañana a las
     * 00:00: si terminara "ahora", el día en curso saldría recortado y la
     * gráfica mostraría una caída al final que no es real.
     *
     * @param int $desplazamiento 0 = periodo actual, 1 = el inmediatamente anterior
     */
    private function filtro(int $dias, int $productoId, string $entorno, int $desplazamiento = 0): array
    {
        $hasta = (new DateTime('tomorrow'))->modify('-' . ($dias * $desplazamiento) . ' days');
        $desde = (clone $hasta)->modify('-' . $dias . ' days');

        return [
            'desde'       => $desde->format('Y-m-d H:i:s'),
            'hasta'       => $hasta->format('Y-m-d H:i:s'),
            'producto_id' => $productoId,
            'entorno'     => $entorno,
        ];
    }

    public function index(): void
    {
        $this->requireLogin();

        $analytics = new LandingAnalytics();

        // Sin tablas todavía (nadie ha visitado la landing desde el deploy):
        // la vista lo explica en vez de mostrar un panel lleno de ceros.
        if (!$analytics->instalado()) {
            $this->view('admin/estadisticas/index', [
                'instalado' => false,
                'productos' => $this->productos(),
            ]);
            return;
        }

        $dias       = $this->dias();
        $entorno    = $this->entorno();
        $productoId = (int)($_GET['producto'] ?? 0);

        $filtro  = $this->filtro($dias, $productoId, $entorno);
        $previo  = $this->filtro($dias, $productoId, $entorno, 1);

        $resumen  = $analytics->resumen($filtro);
        $anterior = $analytics->resumen($previo);

        $settings  = new AppSettings();
        $analisis  = new LandingAnalisis();

        $this->view('admin/estadisticas/index', [
            'instalado'    => true,
            'dias'         => $dias,
            'producto_id'  => $productoId,
            'entorno'      => $entorno,
            'productos'    => $this->productos(),
            'resumen'      => $resumen,
            'tendencias'   => $this->tendencias($resumen, $anterior),
            'embudo'       => $analytics->embudo($filtro),
            'secciones'    => $analytics->secciones($filtro),
            'campos'       => $analytics->camposFormulario($filtro),
            'dispositivos' => $analytics->porDimension($filtro, 'dispositivo'),
            'navegadores'  => $analytics->porDimension($filtro, 'navegador'),
            'fuentes'      => $analytics->porDimension($filtro, 'fuente'),
            'serie'        => $analytics->serieDiaria($filtro),
            'errores'      => $analytics->errores($filtro),
            'sesiones'     => $analytics->ultimasSesiones($filtro, 40),
            'tiene_claude_key' => $settings->hasKey('claude_api_key'),
            'analisis'     => $analisis->ultimo($productoId, $dias, $entorno),
            'min_sesiones' => LandingAnalisis::MIN_SESIONES,
            'modelos'      => LandingAnalisis::MODELOS,
            'modelo_elegido' => LandingAnalisis::modeloValido(
                $settings->get('claude_modelo_analisis', LandingAnalisis::MODELO_POR_DEFECTO)
            ),
        ]);
    }

    /**
     * Variación de cada KPI respecto al periodo anterior de la misma
     * duración. Sin esto no se sabe si un 2% de conversión es una mejora o
     * un desplome, ni el panel ni el análisis de Claude.
     */
    private function tendencias(array $actual, array $anterior): array
    {
        $comparar = function (float $hoy, float $antes, bool $masEsMejor = true): array {
            if ($antes <= 0) {
                return ['dir' => 'flat', 'label' => 'sin comparación', 'pct' => null];
            }

            $variacion = (($hoy - $antes) / $antes) * 100;
            if (abs($variacion) < 1) {
                return ['dir' => 'flat', 'label' => 'igual', 'pct' => 0.0];
            }

            $subio = $variacion > 0;
            return [
                // 'up' pinta en verde: lo que importa no es si el número sube,
                // sino si el cambio es bueno.
                'dir'   => ($subio === $masEsMejor) ? 'up' : 'down',
                'label' => ($subio ? '+' : '') . number_format($variacion, 0, ',', '.') . '%',
                'pct'   => round($variacion, 1),
            ];
        };

        return [
            'sesiones'     => $comparar((float)$actual['sesiones'],     (float)$anterior['sesiones']),
            'pedidos'      => $comparar((float)$actual['pedidos'],      (float)$anterior['pedidos']),
            'conversion'   => $comparar((float)$actual['conversion'],   (float)$anterior['conversion']),
            'intencion'    => $comparar((float)$actual['con_intencion'],(float)$anterior['con_intencion']),
            'dur_media'    => $comparar((float)$actual['dur_media'],    (float)$anterior['dur_media']),
            'scroll_medio' => $comparar((float)$actual['scroll_medio'], (float)$anterior['scroll_medio']),
            'anterior'     => $anterior,
        ];
    }

    // ══════════════════════════════════════════════════════════
    //  ANÁLISIS CON CLAUDE
    // ══════════════════════════════════════════════════════════

    /**
     * AJAX: manda el periodo a Claude y devuelve el análisis.
     *
     * Solo se dispara al pulsar el botón, nunca al cargar la página: la
     * llamada cuesta dinero y tarda, y el panel debe seguir abriéndose al
     * instante.
     */
    public function analizar(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $settings = new AppSettings();
            $apiKey   = $settings->get('claude_api_key');

            if (!$apiKey) {
                echo json_encode(['ok' => false, 'error' => 'Falta la API key de Claude. Se configura en Marketing IA.']);
                return;
            }

            $dias       = (int)($_POST['dias'] ?? 7);
            if (!in_array($dias, [1, 7, 15, 30, 90], true)) $dias = 7;
            $productoId = (int)($_POST['producto'] ?? 0);
            $entorno    = ($_POST['entorno'] ?? 'produccion') === 'local' ? 'local' : 'produccion';

            // El modelo lo elige el administrador en el panel. Se valida contra
            // la lista blanca del modelo (llega por POST y va a la API) y se
            // recuerda para la próxima vez.
            $modelo = LandingAnalisis::modeloValido($_POST['modelo'] ?? null);
            $settings->set('claude_modelo_analisis', $modelo);

            $contexto = $this->contextoParaAnalisis($dias, $productoId, $entorno);
            $analisis = new LandingAnalisis();
            $salida   = $analisis->analizar($apiKey, $contexto, $productoId, $dias, $entorno, $modelo);

            if (!$salida['ok']) {
                echo json_encode($salida);
                return;
            }

            echo json_encode([
                'ok'        => true,
                'resultado' => $salida['resultado'],
                'creado_en' => $salida['creado_en'],
                'modelo'    => $salida['modelo'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('AdminEstadisticas::analizar — ' . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Error interno al generar el análisis.']);
        }
    }

    /**
     * Todo lo que Claude necesita para no dar consejos genéricos: las
     * métricas del periodo, cómo iban en el anterior, y la configuración
     * real de la landing y del producto.
     */
    private function contextoParaAnalisis(int $dias, int $productoId, string $entorno): array
    {
        $analytics = new LandingAnalytics();
        $filtro    = $this->filtro($dias, $productoId, $entorno);
        $previo    = $this->filtro($dias, $productoId, $entorno, 1);

        $resumen  = $analytics->resumen($filtro);
        $anterior = $analytics->resumen($previo);

        return [
            'periodo' => [
                'dias'  => $dias,
                'desde' => $filtro['desde'],
                'hasta' => $filtro['hasta'],
            ],
            'producto' => $this->contextoProducto($productoId),
            'landing'  => $this->contextoLanding($productoId),
            'metricas' => [
                'resumen'            => $resumen,
                'periodo_anterior'   => $anterior,
                'variacion'          => $this->tendencias($resumen, $anterior),
                'embudo'             => $analytics->embudo($filtro),
                'hasta_que_seccion'  => $analytics->secciones($filtro),
                'ultimo_campo_form'  => $analytics->camposFormulario($filtro),
                'por_dispositivo'    => $analytics->porDimension($filtro, 'dispositivo'),
                'por_navegador'      => $analytics->porDimension($filtro, 'navegador'),
                'por_fuente'         => $analytics->porDimension($filtro, 'fuente'),
                'por_dia'            => $analytics->serieDiaria($filtro),
                'errores_js'         => $analytics->errores($filtro),
            ],
        ];
    }

    private function contextoProducto(int $productoId): array
    {
        try {
            $modelo   = new Producto();
            $producto = $productoId > 0
                ? $modelo->obtenerPorId($productoId)
                : $modelo->obtenerPrimeroActivo();

            if (!$producto) return ['nota' => 'Sin producto asociado (vista de todos los productos).'];

            return [
                'nombre'                => $producto['nombre'] ?? '',
                'precio_venta'          => (float)($producto['precio_venta'] ?? 0),
                'precio_regular'        => (float)($producto['precio_regular'] ?? 0),
                'costo_envio'           => (float)($producto['costo_envio'] ?? 0),
                'margen_por_unidad'     => (float)($producto['precio_venta'] ?? 0) - (float)($producto['precio_proveedor'] ?? 0),
                'descuento_2da_unidad'  => (int)($producto['descuento_2da'] ?? 0),
                'descuento_3ra_unidad'  => (int)($producto['descuento_3ra'] ?? 0),
                'descuentos_activos'    => (int)($producto['descuento_multicantidad_activo'] ?? 0) === 1,
                'moneda'                => 'COP',
            ];
        } catch (\Throwable $e) {
            return ['nota' => 'No se pudo leer el producto.'];
        }
    }

    /**
     * Qué hay encendido en esa landing. Es lo que convierte "mejora tu
     * embudo" en "la sección de garantía está apagada y la gente se cae
     * justo antes del formulario".
     */
    private function contextoLanding(int $productoId): array
    {
        try {
            if ($productoId <= 0) {
                $p = (new Producto())->obtenerPrimeroActivo();
                $productoId = (int)($p['id'] ?? 0);
            }
            if ($productoId <= 0) return [];

            $cfg = (new LandingConfig())->obtenerPorProducto($productoId) ?? [];
            if (!$cfg) return [];

            // Las secciones son ~40 columnas show_*: se resumen en dos listas
            // en vez de volcar la fila entera, que son 200 campos de texto.
            $encendidas = [];
            $apagadas   = [];
            foreach ($cfg as $clave => $valor) {
                if (strpos($clave, 'show_') !== 0) continue;
                $nombre = substr($clave, 5);
                ((int)$valor === 1) ? $encendidas[] = $nombre : $apagadas[] = $nombre;
            }

            return [
                'secciones_encendidas' => $encendidas,
                'secciones_apagadas'   => $apagadas,
                'countdown_minutos'    => (int)($cfg['countdown_minutes'] ?? 0),
                'combo_x2_activo'      => (int)($cfg['combo_enabled'] ?? 0) === 1,
                'combo_precio_2'       => (int)($cfg['combo_price_2'] ?? 0),
                'stock_declarado'      => $cfg['urgency_stock'] ?? null,
                'titulo_hero'          => mb_substr((string)($cfg['hero_title'] ?? ''), 0, 160),
                'titulo_formulario'    => mb_substr((string)($cfg['form_title'] ?? ''), 0, 160),
                'orden_secciones'      => $cfg['section_order'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════
    //  MANTENIMIENTO Y EXPORTACIÓN
    // ══════════════════════════════════════════════════════════

    /** Borra las visitas de prueba (entorno local). Nunca el tráfico real. */
    public function limpiarPruebas(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        $borradas = (new LandingAnalytics())->purgarPruebas();

        $_SESSION['success'] = $borradas > 0
            ? "Se borraron {$borradas} visitas de prueba."
            : 'No había visitas de prueba que borrar.';

        header('Location: ' . BASE_URL . '/AdminEstadisticas/index?' . http_build_query([
            'dias'     => $this->dias(),
            'producto' => (int)($_POST['producto'] ?? 0),
            'entorno'  => $this->entorno(),
        ]));
        exit;
    }

    /** CSV de las sesiones del periodo, para mirarlas en una hoja de cálculo. */
    public function exportarCsv(): void
    {
        $this->requireLogin();

        $dias       = $this->dias();
        $entorno    = $this->entorno();
        $productoId = (int)($_GET['producto'] ?? 0);
        $filtro     = $this->filtro($dias, $productoId, $entorno);

        $sesiones = (new LandingAnalytics())->sesionesParaExport($filtro);

        while (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="visitas-landing-' . date('Y-m-d') . '.csv"');

        $salida = fopen('php://output', 'w');
        // BOM: sin él Excel en Windows abre los acentos rotos.
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, [
            'Fecha', 'Landing', 'Llegó hasta', 'Paso', 'Sección', 'Campo',
            'Scroll %', 'Segundos', 'Dispositivo', 'Navegador', 'Fuente', 'Campaña', 'Pedido',
        ], ';');

        $limpiar = fn($v) => $v === null || $v === ''
            ? ''
            : str_replace(['-', '_'], ' ', preg_replace('/^\d+-/', '', (string)$v));

        foreach ($sesiones as $s) {
            $paso = (int)$s['paso_max'];
            fputcsv($salida, [
                $s['creado_en'],
                $s['slug'],
                LandingAnalytics::PASOS[$paso] ?? '',
                $paso,
                $limpiar($s['seccion_max']),
                $limpiar($s['campo_max']),
                (int)$s['scroll_max'],
                (int)$s['duracion_seg'],
                $s['dispositivo'],
                $s['navegador'],
                $s['fuente'],
                $s['campana'] ?? '',
                $s['pedido_id'] ? '#' . (int)$s['pedido_id'] : '',
            ], ';');
        }

        fclose($salida);
        exit;
    }

    private function productos(): array
    {
        try {
            $modelo = new Producto();
            if (method_exists($modelo, 'obtenerTodos')) return $modelo->obtenerTodos();
        } catch (\Throwable $e) {
            error_log('AdminEstadisticas::productos — ' . $e->getMessage());
        }
        return [];
    }
}
