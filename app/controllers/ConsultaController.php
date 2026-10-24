<?php

/**
 * Consulta pública de pedidos para las vendedoras (sin iniciar sesión). Ver ConsultaPedidos: la seguridad es
 * el enlace privado largo, buscar solo por teléfono completo, datos mínimos y límite por IP.
 *
 * La ÚNICA ruta útil es /Consulta/ver/<clave>. Cualquier otra, o una clave equivocada, responde 404.
 */
class ConsultaController extends Controller
{
    public function index()
    {
        $this->notFound('No encontramos la página que buscas.');
    }

    public function ver($clave = '')
    {
        $consulta = new ConsultaPedidos();
        if (!$consulta->claveValida((string)$clave)) {
            $this->notFound('No encontramos la página que buscas.');
        }

        // Una página con datos de clientes no se guarda en cachés ni en buscadores, ni pasa su dirección a otros sitios.
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: no-referrer');

        $datos = ['clave' => (string)$clave, 'telefono' => '', 'ordenes' => null, 'aviso' => '', 'datosAl' => $consulta->datosAl()];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $texto = (string)($_POST['telefono'] ?? '');
            $datos['telefono'] = mb_substr($texto, 0, 30);
            $tel = ConsultaPedidos::normalizarTelefono($texto);
            $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

            if ($tel === null) {
                $datos['aviso'] = 'Escribe el número completo del cliente, con sus 10 dígitos (por ejemplo 3001234567).';
            } elseif (!$consulta->permitido($ip)) {
                http_response_code(429);
                $datos['aviso'] = 'Hiciste muchas consultas seguidas. Espera unos minutos y vuelve a intentar.';
            } else {
                $ordenes = $consulta->buscar($tel);
                $consulta->registrar($ip, $tel, count($ordenes));
                $datos['telefono'] = $tel;
                $agente = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
                $datos['ordenes'] = array_map(static function (array $o) use ($tel, $agente): array {
                    $mensaje = ConsultaPedidos::mensaje($o);
                    return [
                        'producto'  => trim((string)$o['producto']),
                        'cantidad'  => (int)$o['n_lineas'] > 0 ? (int)$o['cantidad'] : null,
                        'fecha'     => $o['fecha_pedido'],
                        'estado'    => ConsultaPedidos::estadoLegible($o),
                        'estado_clase' => (string)$o['estado'],
                        'guia'      => (string)$o['guia'],
                        'transportadora' => (DropiOrden::transportadoraInfo((string)$o['transportadora'])['nombre'] ?? ''),
                        'municipio' => (string)$o['municipio'],
                        'mensaje'   => $mensaje,
                        'whatsapp'  => ConsultaPedidos::enlaceWhatsApp($tel, $mensaje, $agente),
                    ];
                }, $ordenes);
            }
        }

        // Si se consultó sin recargar (fetch), solo se devuelve el resultado; si no, la página completa.
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            header('Content-Type: application/json; charset=utf-8');
            $aviso = $datos['aviso'];
            $ordenes = $datos['ordenes'];
            ob_start();
            require __DIR__ . '/../views/consulta/_resultados.php';
            echo json_encode(['html' => ob_get_clean()], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->view('consulta/ver', $datos);
    }
}
