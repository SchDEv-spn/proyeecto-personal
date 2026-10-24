<?php

/**
 * Panel de vendedoras: crear vendedoras, agregarles pedidos pegando los teléfonos de su cuaderno, ver qué
 * hay por pagar y liquidar lo entregado. También muestra y cambia el enlace privado de la consulta pública
 * (ConsultaController). Todo exige sesión; lo que cambia datos exige POST y token CSRF.
 */
class AdminVendedorasController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $vend = new Vendedora();
        $consulta = new ConsultaPedidos();

        $this->view('admin/vendedoras/index', [
            'vendedoras'   => $vend->todas(),
            'resumen'      => $vend->resumen(),
            'enlace'       => $this->enlaceConsulta($consulta->clave()),
            'consultasHoy' => $consulta->consultasHoy(),
            'ultimas'      => $consulta->ultimas(12),
            'ok'           => $this->flash('vend_ok'),
            'errores'      => array_filter([$this->flash('vend_error')]),
        ]);
    }

    /** Una vendedora: lo que tiene por pagar, en camino, sin pago y pagado, más agregar pedidos. */
    public function ver($id = 0)
    {
        $this->requireLogin();
        $this->mostrarVendedora((int)$id);
    }

    public function guardar()
    {
        $this->requireLogin();
        $this->soloPost();
        $this->requireCsrf();

        $id = (int)($_POST['id'] ?? 0);
        try {
            $nuevoId = (new Vendedora())->guardar(
                $id > 0 ? $id : null,
                (string)($_POST['nombre'] ?? ''),
                (int)preg_replace('/\D+/', '', (string)($_POST['valor_pedido'] ?? '0')),
                $id > 0 ? !empty($_POST['activa']) : true
            );
            $_SESSION['vend_ok'] = $id > 0 ? 'Cambios guardados.' : 'Vendedora creada. Ahora agrégale los pedidos de su cuaderno.';
            if ($id > 0) $this->terminar($id);
            else $this->ir('/AdminVendedoras/ver/' . $nuevoId);
        } catch (InvalidArgumentException $e) {
            $_SESSION['vend_error'] = $e->getMessage();
            if ($id > 0) $this->terminar($id);
            else $this->ir('/AdminVendedoras/index');
        }
    }

    /** Busca en Dropi los teléfonos pegados y muestra qué pedido tiene cada uno, sin guardar nada todavía. */
    public function revisar()
    {
        $this->requireLogin();
        $this->soloPost();
        $this->requireCsrf();

        $this->mostrarVendedora((int)($_POST['vendedora_id'] ?? 0), (string)($_POST['telefonos'] ?? ''), $this->esAjax());
    }

    public function asignar()
    {
        $this->requireLogin();
        $this->soloPost();
        $this->requireCsrf();

        $id = (int)($_POST['vendedora_id'] ?? 0);
        $n = (new Vendedora())->asignar($id, (array)($_POST['ordenes'] ?? []));
        if ($n > 0) $_SESSION['vend_ok'] = $n === 1 ? 'Agregué 1 pedido.' : "Agregué {$n} pedidos.";
        else $_SESSION['vend_error'] = 'No agregué ningún pedido: marca al menos uno que se pueda agregar.';
        $this->terminar($id);
    }

    public function quitar()
    {
        $this->requireLogin();
        $this->soloPost();
        $this->requireCsrf();

        $id = (int)($_POST['vendedora_id'] ?? 0);
        $n = (new Vendedora())->quitar($id, (array)($_POST['ordenes'] ?? []));
        if ($n > 0) $_SESSION['vend_ok'] = $n === 1 ? 'Quité 1 pedido.' : "Quité {$n} pedidos.";
        else $_SESSION['vend_error'] = 'No quité ningún pedido: marca los que quieras quitar (los ya pagados no se pueden quitar).';
        $this->terminar($id);
    }

    /** Paga lo entregado y todavía no pagado de lo que se marcó. */
    public function liquidar()
    {
        $this->requireLogin();
        $this->soloPost();
        $this->requireCsrf();

        $id = (int)($_POST['vendedora_id'] ?? 0);
        try {
            $r = (new Vendedora())->liquidar($id, (array)($_POST['ordenes'] ?? []), (string)($_POST['nota'] ?? ''));
            if ($r === null) $_SESSION['vend_error'] = 'No había nada para liquidar: marca pedidos entregados que todavía no se hayan pagado.';
            else $_SESSION['vend_ok'] = 'Liquidación hecha: ' . $r['ordenes'] . ($r['ordenes'] === 1 ? ' pedido' : ' pedidos') . ' por $' . number_format($r['total'], 0, ',', '.') . '.';
        } catch (InvalidArgumentException $e) {
            $_SESSION['vend_error'] = $e->getMessage();
        }
        $this->terminar($id);
    }

    /** Cambia la clave del enlace de consulta: el enlace que tenían guardado deja de servir. */
    public function cambiarEnlace()
    {
        $this->requireLogin();
        $this->soloPost();
        $this->requireCsrf();

        $clave = (new ConsultaPedidos())->rotarClave();
        $_SESSION['vend_ok'] = 'Listo: ese enlace dejó de funcionar. Mándales el nuevo.';
        if ($this->esAjax()) {
            $this->json(['ok' => true, 'mensaje' => $this->flash('vend_ok'), 'enlace' => $this->enlaceConsulta($clave)]);
        }
        $this->ir('/AdminVendedoras/index');
    }

    /* ─────────────────────────────  Internos  ───────────────────────────── */

    /** Después de una acción: si se hizo sin recargar, el contenido nuevo en JSON; si no, de vuelta a la página. */
    private function terminar(int $id): void
    {
        if ($this->esAjax()) $this->mostrarVendedora($id, null, true);
        $this->ir('/AdminVendedoras/ver/' . $id);
    }

    private function esAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    private function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function subtitulo(array $v): string
    {
        return (float)$v['valor_pedido'] > 0
            ? '$' . number_format((float)$v['valor_pedido'], 0, ',', '.') . ' por unidad entregada · 2 unidades se pagan doble, 3 triple…'
            : 'Falta el valor por unidad';
    }

    private function mostrarVendedora(int $id, ?string $textoTelefonos = null, bool $ajax = false): void
    {
        $vend = new Vendedora();
        $vendedora = $vend->porId($id);
        if (!$vendedora) {
            if ($ajax) $this->json(['ok' => false, 'error' => 'Esa vendedora no existe.'], 404);
            $_SESSION['vend_error'] = 'Esa vendedora no existe.';
            $this->ir('/AdminVendedoras/index');
        }

        $revision = null;
        $invalidos = [];
        $repetidos = 0;
        if ($textoTelefonos !== null) {
            $p = Vendedora::parsearTelefonos($textoTelefonos);
            $invalidos = $p['invalidos'];
            $repetidos = $p['repetidos'];
            $revision = $vend->revisar($id, $p['validos']);
            if (count($p['validos']) > Vendedora::MAX_TELEFONOS) {
                $invalidos[] = 'Revisé los primeros ' . Vendedora::MAX_TELEFONOS . ' teléfonos; pega el resto en otra tanda.';
            }
        }

        $datos = [
            'vendedora' => $vendedora,
            'pedidos'   => $vend->pedidosDe($id),
            'historial' => $vend->historial($id),
            'texto'     => (string)$textoTelefonos,
            'revision'  => $revision,
            'invalidos' => $invalidos,
            'repetidos' => $repetidos,
            'ok'        => $this->flash('vend_ok'),
            'errores'   => array_filter([$this->flash('vend_error')]),
            'titulo'    => $vendedora['nombre'],
            'subtitulo' => $this->subtitulo($vendedora),
        ];

        if ($ajax) {
            // Solo el contenido, para cambiarlo en su sitio; el aviso (éxito o error) va aparte.
            $error = (string)(reset($datos['errores']) ?: '');
            extract($datos);
            ob_start();
            require __DIR__ . '/../views/admin/vendedoras/_ver_contenido.php';
            $this->json(['ok' => $error === '', 'mensaje' => $datos['ok'], 'error' => $error, 'html' => ob_get_clean(),
                         'titulo' => $datos['titulo'], 'subtitulo' => $datos['subtitulo']]);
        }

        $this->view('admin/vendedoras/ver', $datos);
    }

    /** Dirección completa del enlace de consulta, para copiarla y mandarla. */
    private function enlaceConsulta(string $clave): string
    {
        $esquema = es_entorno_local() ? 'http' : 'https';
        $host = preg_replace('/[^A-Za-z0-9\.\-:]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return $esquema . '://' . $host . BASE_URL . '/Consulta/ver/' . $clave;
    }

    private function flash(string $clave): string
    {
        $v = (string)($_SESSION[$clave] ?? '');
        unset($_SESSION[$clave]);
        return $v;
    }

    private function ir(string $ruta): void
    {
        header('Location: ' . BASE_URL . $ruta);
        exit;
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Método no permitido');
        }
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/Auth/login');
            exit;
        }
    }
}
