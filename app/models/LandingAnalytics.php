<?php

/**
 * LandingAnalytics — analítica propia de la landing.
 *
 * Por qué existe teniendo Clarity: Clarity da grabaciones y mapas de calor,
 * pero su Data Export API solo devuelve los últimos 1-3 días agregados, con
 * 10 llamadas diarias, y no expone etiquetas personalizadas. Con eso no se
 * puede responder la pregunta del negocio: cuánta gente entró y en qué punto
 * exacto se cayó la intención de compra. Eso necesita histórico propio.
 *
 * Dos tablas:
 *   landing_sesiones — una fila por visita, con el estado agregado ya
 *                      calculado (paso máximo, scroll, sección más lejana).
 *                      El panel consulta casi todo aquí: una sola tabla,
 *                      sin recorrer eventos, que es lo que mantiene barato
 *                      el dashboard en hosting compartido.
 *   landing_eventos  — el detalle, para desglosar secciones y campos.
 */
class LandingAnalytics extends Model
{
    /** Pasos del embudo. El número se guarda en landing_sesiones.paso_max. */
    public const PASOS = [
        1 => 'Entró a la landing',
        2 => 'Se quedó leyendo',
        3 => 'Tocó un botón de compra',
        4 => 'Llegó al formulario',
        5 => 'Empezó a llenarlo',
        6 => 'Envió el formulario',
        7 => 'Pedido confirmado',
    ];

    /** Evento del navegador → paso del embudo que acredita. */
    private const PASO_POR_EVENTO = [
        'vista'      => 1,
        'seccion'    => 1,
        'interes'    => 2,
        'cta'        => 3,
        'formulario' => 4,
        'campo'      => 5,
        'envio'      => 6,
    ];

    /** Tipos aceptados desde el navegador. Todo lo demás se descarta. */
    private const TIPOS = [
        'vista', 'interes', 'seccion', 'cta', 'formulario',
        'campo', 'envio', 'salida', 'error',
    ];

    /** Tope de eventos por sesión: un bucle en el cliente no puede llenar la tabla. */
    private const MAX_EVENTOS_SESION = 80;

    // ══════════════════════════════════════════════════════════
    //  ESCRITURA (endpoint público de tracking)
    // ══════════════════════════════════════════════════════════

    /**
     * Guarda un lote de eventos de una sesión.
     *
     * Devuelve false si el lote se descartó (bot, payload inválido). No lanza:
     * la analítica nunca debe romper la landing ni devolver un error visible.
     */
    public function registrar(array $payload, string $userAgent): bool
    {
        $sid = (string)($payload['sid'] ?? '');
        if (!preg_match('/^[a-f0-9]{32}$/', $sid)) return false;
        if ($this->esBot($userAgent)) return false;

        $eventos = $this->normalizarEventos($payload['ev'] ?? []);
        if (!$eventos) return false;

        try {
            $sesion = $this->buscarSesion($sid);
            if ($sesion === null) {
                $sesion = $this->crearSesion($sid, $payload, $userAgent);
                if ($sesion === null) return false;
            }

            if ((int)$sesion['eventos'] >= self::MAX_EVENTOS_SESION) return false;

            $this->insertarEventos((int)$sesion['id'], $eventos);
            $this->actualizarAgregados((int)$sesion['id'], $payload, $eventos);
            return true;
        } catch (\Throwable $e) {
            error_log('LandingAnalytics::registrar — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enlaza la sesión con el pedido creado. Se llama desde el servidor al
     * guardar el pedido, no desde el navegador: el paso 7 es el único dato
     * del embudo que no depende de que el JS del cliente sobreviva.
     */
    public function marcarPedido(string $sid, int $pedidoId): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $sid) || $pedidoId <= 0) return;

        try {
            $stmt = $this->db->prepare(
                'UPDATE landing_sesiones
                    SET pedido_id = ?, paso_max = 7, visto_en = NOW()
                  WHERE sid = ? LIMIT 1'
            );
            $stmt->execute([$pedidoId, $sid]);
        } catch (\Throwable $e) {
            error_log('LandingAnalytics::marcarPedido — ' . $e->getMessage());
        }
    }

    // ── Piezas internas de escritura ──────────────────────────

    private function buscarSesion(string $sid): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, eventos FROM landing_sesiones WHERE sid = ? LIMIT 1'
        );
        try {
            $stmt->execute([$sid]);
        } catch (\PDOException $e) {
            // Primera visita tras el deploy: las tablas aún no existen.
            if (!$this->asegurarTablas($e)) throw $e;
            return null;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    private function crearSesion(string $sid, array $payload, string $userAgent): ?array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO landing_sesiones
                (sid, producto_id, slug, entorno, dispositivo, navegador, fuente,
                 campana, creado_en, visto_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $productoId = (int)($payload['prod'] ?? 0);

        try {
            $stmt->execute([
                $sid,
                $productoId > 0 ? $productoId : null,
                $this->recortar((string)($payload['slug'] ?? ''), 120),
                (($payload['env'] ?? '') === 'local') ? 'local' : 'produccion',
                $this->dispositivo($userAgent),
                $this->recortar((string)($payload['nav'] ?? 'navegador'), 30),
                $this->recortar((string)($payload['src'] ?? 'directo'), 60),
                $this->recortar((string)($payload['camp'] ?? ''), 120) ?: null,
            ]);
        } catch (\PDOException $e) {
            // Dos pestañas del mismo visitante pueden insertar a la vez:
            // el UNIQUE(sid) resuelve el empate, el perdedor relee la fila.
            if ((string)$e->getCode() === '23000') return $this->buscarSesion($sid);
            throw $e;
        }

        return ['id' => (int)$this->db->lastInsertId(), 'eventos' => 0];
    }

    private function insertarEventos(int $sesionId, array $eventos): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO landing_eventos (sesion_id, tipo, valor, creado_en)
             VALUES (?, ?, ?, NOW())'
        );
        foreach ($eventos as $ev) {
            $stmt->execute([$sesionId, $ev['tipo'], $ev['valor']]);
        }
    }

    /**
     * Sube los máximos de la sesión. Todo va con GREATEST porque los lotes
     * pueden llegar desordenados (sendBeacon no garantiza el orden) y un
     * lote tardío no puede hacer retroceder el embudo.
     *
     * seccion_max y campo_max se comparan como texto y por eso el cliente los
     * manda con el índice delante ("07-testimonios"): así el orden alfabético
     * coincide con el orden de la página.
     */
    private function actualizarAgregados(int $sesionId, array $payload, array $eventos): void
    {
        $paso    = 1;
        $seccion = null;
        $campo   = null;

        foreach ($eventos as $ev) {
            $paso = max($paso, self::PASO_POR_EVENTO[$ev['tipo']] ?? 0);
            if ($ev['tipo'] === 'seccion' && $ev['valor'] !== null) {
                $seccion = $seccion === null ? $ev['valor'] : max($seccion, $ev['valor']);
            }
            if ($ev['tipo'] === 'campo' && $ev['valor'] !== null) {
                $campo = $campo === null ? $ev['valor'] : max($campo, $ev['valor']);
            }
        }

        $scroll   = max(0, min(100, (int)($payload['sc'] ?? 0)));
        $duracion = max(0, min(65535, (int)($payload['dur'] ?? 0)));

        $stmt = $this->db->prepare(
            'UPDATE landing_sesiones SET
                paso_max     = GREATEST(paso_max, ?),
                scroll_max   = GREATEST(scroll_max, ?),
                duracion_seg = GREATEST(duracion_seg, ?),
                seccion_max  = GREATEST(COALESCE(seccion_max, ?), ?),
                campo_max    = GREATEST(COALESCE(campo_max, ?), ?),
                eventos      = eventos + ?,
                visto_en     = NOW()
              WHERE id = ? LIMIT 1'
        );
        $stmt->execute([
            $paso, $scroll, $duracion,
            $seccion, $seccion,
            $campo, $campo,
            count($eventos),
            $sesionId,
        ]);
    }

    /**
     * Filtra y recorta lo que llega del navegador. El formato de entrada es
     * compacto ([tipo, valor]) porque el lote viaja por sendBeacon desde
     * móviles con conexión lenta.
     */
    private function normalizarEventos($crudos): array
    {
        if (!is_array($crudos)) return [];

        $out = [];
        foreach (array_slice($crudos, 0, 30) as $ev) {
            if (!is_array($ev) || !isset($ev[0])) continue;
            $tipo = (string)$ev[0];
            if (!in_array($tipo, self::TIPOS, true)) continue;

            $valor = isset($ev[1]) && $ev[1] !== '' ? $this->recortar((string)$ev[1], 64) : null;
            $out[] = ['tipo' => $tipo, 'valor' => $valor];
        }
        return $out;
    }

    private function recortar(string $v, int $max): string
    {
        return mb_substr(trim(strip_tags($v)), 0, $max);
    }

    /**
     * Los rastreadores y los previsualizadores de enlaces (Facebook abre la
     * landing cada vez que alguien comparte el link) inflarían el número de
     * visitas y hundirían la tasa de conversión sin que nada haya cambiado.
     */
    private function esBot(string $ua): bool
    {
        if ($ua === '') return true;
        return (bool)preg_match(
            '/bot|crawl|spider|slurp|headless|preview|facebookexternalhit|lighthouse|pingdom|monitor/i',
            $ua
        );
    }

    private function dispositivo(string $ua): string
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) return 'tablet';
        if (preg_match('/Mobile|Android|iPhone|iPod|Opera Mini|IEMobile/i', $ua)) return 'movil';
        return 'escritorio';
    }

    /**
     * Definición de las tablas. Vive aquí y no en un .sql suelto porque
     * .gitignore excluye *.sql: un archivo de migración nunca llegaría a
     * Hostinger y la analítica quedaría muda en producción.
     */
    private const DDL = [
        // Una fila por visita. El estado agregado (paso máximo, scroll,
        // sección más lejana) se guarda ya calculado para que el panel no
        // tenga que recorrer los eventos en cada consulta.
        'CREATE TABLE IF NOT EXISTS landing_sesiones (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sid           CHAR(32)        NOT NULL,
            producto_id   INT             NULL,
            slug          VARCHAR(120)    NOT NULL DEFAULT "",
            entorno       ENUM("produccion","local") NOT NULL DEFAULT "produccion",
            dispositivo   ENUM("movil","tablet","escritorio") NOT NULL DEFAULT "movil",
            navegador     VARCHAR(30)     NOT NULL DEFAULT "navegador",
            fuente        VARCHAR(60)     NOT NULL DEFAULT "directo",
            campana       VARCHAR(120)    NULL,
            paso_max      TINYINT UNSIGNED NOT NULL DEFAULT 1,
            seccion_max   VARCHAR(48)     NULL,
            campo_max     VARCHAR(32)     NULL,
            scroll_max    TINYINT UNSIGNED NOT NULL DEFAULT 0,
            duracion_seg  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            eventos       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            pedido_id     INT             NULL,
            creado_en     DATETIME        NOT NULL,
            visto_en      DATETIME        NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_landing_sesiones_sid (sid),
            KEY idx_landing_sesiones_creado (creado_en),
            KEY idx_landing_sesiones_prod (producto_id, creado_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',

        // El detalle del recorrido: secciones vistas, campos llenados, errores.
        'CREATE TABLE IF NOT EXISTS landing_eventos (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sesion_id  BIGINT UNSIGNED NOT NULL,
            tipo       VARCHAR(24)     NOT NULL,
            valor      VARCHAR(64)     NULL,
            creado_en  DATETIME        NOT NULL,
            PRIMARY KEY (id),
            KEY idx_landing_eventos_sesion (sesion_id),
            KEY idx_landing_eventos_tipo (tipo, creado_en)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];

    /**
     * Crea las tablas al vuelo la primera vez. En Hostinger el deploy es un
     * push a main: no hay paso donde correr migraciones, y sin esto la
     * analítica quedaría muda hasta que alguien entre a phpMyAdmin.
     * Solo actúa ante el error "tabla no existe" (SQLSTATE 42S02).
     */
    private function asegurarTablas(\PDOException $e): bool
    {
        if ((string)$e->getCode() !== '42S02') return false;

        foreach (self::DDL as $sentencia) {
            $this->db->exec($sentencia);
        }
        return true;
    }

    // ══════════════════════════════════════════════════════════
    //  LECTURA (panel de estadísticas)
    // ══════════════════════════════════════════════════════════

    /**
     * Filtro común de todas las consultas del panel.
     *
     * @return array{0:string,1:array} WHERE ya armado y sus parámetros
     */
    private function filtro(array $f): array
    {
        $where  = ['s.creado_en >= ?', 's.creado_en < ?'];
        $params = [$f['desde'], $f['hasta']];

        // Por defecto solo tráfico real: las pruebas en XAMPP local sesgan
        // todos los promedios, que es justo el problema que ya se corrigió
        // en Clarity envolviendo el snippet en es_entorno_local().
        $where[]  = 's.entorno = ?';
        $params[] = ($f['entorno'] ?? 'produccion') === 'local' ? 'local' : 'produccion';

        if (!empty($f['producto_id'])) {
            $where[]  = 's.producto_id = ?';
            $params[] = (int)$f['producto_id'];
        }

        return [implode(' AND ', $where), $params];
    }

    /** ¿Ya hay tablas de analítica? El panel lo usa para explicar el vacío. */
    public function instalado(): bool
    {
        try {
            $this->db->query('SELECT 1 FROM landing_sesiones LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Totales de cabecera: visitas, pedidos, conversión, tiempo y scroll. */
    public function resumen(array $f): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT
                    COUNT(*)                                   AS sesiones,
                    SUM(s.pedido_id IS NOT NULL)               AS pedidos,
                    SUM(s.paso_max >= 3)                       AS con_intencion,
                    SUM(s.paso_max >= 5)                       AS empezaron_form,
                    AVG(s.duracion_seg)                        AS dur_media,
                    AVG(s.scroll_max)                          AS scroll_medio
                FROM landing_sesiones s
                WHERE {$where}";

        $row = $this->consulta($sql, $params)[0] ?? [];

        $sesiones = (int)($row['sesiones'] ?? 0);
        $pedidos  = (int)($row['pedidos'] ?? 0);

        return [
            'sesiones'       => $sesiones,
            'pedidos'        => $pedidos,
            'con_intencion'  => (int)($row['con_intencion'] ?? 0),
            'empezaron_form' => (int)($row['empezaron_form'] ?? 0),
            'conversion'     => $sesiones ? round($pedidos * 100 / $sesiones, 2) : 0.0,
            'dur_media'      => (int)round((float)($row['dur_media'] ?? 0)),
            'scroll_medio'   => (int)round((float)($row['scroll_medio'] ?? 0)),
        ];
    }

    /**
     * El embudo: cuántas sesiones alcanzaron cada paso, con el porcentaje de
     * caída respecto al paso anterior — el número que responde "dónde se
     * pierde la intención".
     */
    public function embudo(array $f): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT s.paso_max AS paso, COUNT(*) AS n
                FROM landing_sesiones s
                WHERE {$where}
                GROUP BY s.paso_max";

        $porPaso = [];
        foreach ($this->consulta($sql, $params) as $row) {
            $porPaso[(int)$row['paso']] = (int)$row['n'];
        }

        // paso_max es el punto donde se quedó: alcanzar el paso N significa
        // sumar todos los que llegaron a N o más lejos.
        $out    = [];
        $previo = null;
        $total  = array_sum($porPaso);

        foreach (self::PASOS as $n => $nombre) {
            $alcanzaron = 0;
            foreach ($porPaso as $paso => $cant) {
                if ($paso >= $n) $alcanzaron += $cant;
            }

            $out[] = [
                'paso'        => $n,
                'nombre'      => $nombre,
                'sesiones'    => $alcanzaron,
                'pct_total'   => $total ? round($alcanzaron * 100 / $total, 1) : 0.0,
                'abandonaron' => $previo === null ? 0 : max(0, $previo - $alcanzaron),
                'pct_caida'   => ($previo === null || $previo === 0)
                    ? 0.0
                    : round(($previo - $alcanzaron) * 100 / $previo, 1),
            ];
            $previo = $alcanzaron;
        }

        return $out;
    }

    /**
     * Hasta qué sección de la página llegó cada visitante. Es el mapa de
     * scroll en tabla: la sección donde se acumulan los abandonos es la que
     * está fallando.
     */
    public function secciones(array $f): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT s.seccion_max AS seccion, COUNT(*) AS n
                FROM landing_sesiones s
                WHERE {$where} AND s.seccion_max IS NOT NULL
                GROUP BY s.seccion_max
                ORDER BY s.seccion_max ASC";

        return $this->conPorcentaje($this->consulta($sql, $params), 'seccion');
    }

    /**
     * Último campo del formulario que llegó a llenarse. Aquí se ve si la
     * gente se cae en el teléfono, en la dirección o al elegir color.
     */
    public function camposFormulario(array $f): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT s.campo_max AS campo, COUNT(*) AS n,
                       SUM(s.pedido_id IS NOT NULL) AS pedidos
                FROM landing_sesiones s
                WHERE {$where} AND s.campo_max IS NOT NULL
                GROUP BY s.campo_max
                ORDER BY s.campo_max ASC";

        return $this->conPorcentaje($this->consulta($sql, $params), 'campo');
    }

    /** Desglose por dispositivo, navegador o fuente de tráfico. */
    public function porDimension(array $f, string $columna): array
    {
        $permitidas = ['dispositivo', 'navegador', 'fuente', 'campana'];
        if (!in_array($columna, $permitidas, true)) return [];

        [$where, $params] = $this->filtro($f);

        $sql = "SELECT s.{$columna} AS etiqueta, COUNT(*) AS n,
                       SUM(s.pedido_id IS NOT NULL) AS pedidos
                FROM landing_sesiones s
                WHERE {$where} AND s.{$columna} IS NOT NULL AND s.{$columna} <> ''
                GROUP BY s.{$columna}
                ORDER BY n DESC
                LIMIT 12";

        $filas = $this->consulta($sql, $params);
        foreach ($filas as &$fila) {
            $fila['n']          = (int)$fila['n'];
            $fila['pedidos']    = (int)$fila['pedidos'];
            $fila['conversion'] = $fila['n'] ? round($fila['pedidos'] * 100 / $fila['n'], 1) : 0.0;
        }
        return $filas;
    }

    /** Serie diaria de visitas y pedidos, para la gráfica de evolución. */
    public function serieDiaria(array $f): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT DATE(s.creado_en) AS dia,
                       COUNT(*) AS sesiones,
                       SUM(s.pedido_id IS NOT NULL) AS pedidos
                FROM landing_sesiones s
                WHERE {$where}
                GROUP BY DATE(s.creado_en)
                ORDER BY dia ASC";

        $porDia = [];
        foreach ($this->consulta($sql, $params) as $row) {
            $porDia[$row['dia']] = [
                'sesiones' => (int)$row['sesiones'],
                'pedidos'  => (int)$row['pedidos'],
            ];
        }

        // Rellenar los días sin visitas: un hueco en la gráfica se lee como
        // "no hay dato", y aquí un cero es información.
        $out    = [];
        $cursor = new DateTime(substr($f['desde'], 0, 10));
        $fin    = new DateTime(substr($f['hasta'], 0, 10));

        while ($cursor < $fin) {
            $dia   = $cursor->format('Y-m-d');
            $out[] = [
                'dia'      => $dia,
                'sesiones' => $porDia[$dia]['sesiones'] ?? 0,
                'pedidos'  => $porDia[$dia]['pedidos']  ?? 0,
            ];
            $cursor->modify('+1 day');
        }

        return $out;
    }

    /** Errores de JavaScript capturados en la landing, agrupados. */
    public function errores(array $f): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT e.valor AS mensaje, COUNT(DISTINCT e.sesion_id) AS n
                FROM landing_eventos e
                JOIN landing_sesiones s ON s.id = e.sesion_id
                WHERE {$where} AND e.tipo = 'error' AND e.valor IS NOT NULL
                GROUP BY e.valor
                ORDER BY n DESC
                LIMIT 10";

        $filas = $this->consulta($sql, $params);
        foreach ($filas as &$fila) $fila['n'] = (int)$fila['n'];
        return $filas;
    }

    /** Últimas visitas, para inspeccionar recorridos sueltos. */
    public function ultimasSesiones(array $f, int $limite = 40): array
    {
        [$where, $params] = $this->filtro($f);

        $sql = "SELECT s.sid, s.creado_en, s.duracion_seg, s.scroll_max, s.paso_max,
                       s.seccion_max, s.campo_max, s.dispositivo, s.navegador,
                       s.fuente, s.pedido_id
                FROM landing_sesiones s
                WHERE {$where}
                ORDER BY s.creado_en DESC
                LIMIT " . max(1, min(200, $limite));

        return $this->consulta($sql, $params);
    }

    /**
     * Borra el detalle de eventos con más de 90 días. Las sesiones (que es
     * de donde sale todo el panel) se quedan: pesan poco y son el histórico.
     * Sin esto landing_eventos crece sin techo en un hosting compartido.
     */
    public function purgarEventosViejos(): void
    {
        try {
            $this->db->exec(
                'DELETE FROM landing_eventos
                  WHERE creado_en < DATE_SUB(NOW(), INTERVAL 90 DAY)
                  LIMIT 5000'
            );
        } catch (\Throwable $e) {
            // Nada que hacer: la limpieza se reintenta en la próxima visita.
        }
    }

    /**
     * Borra las visitas marcadas como local (las pruebas desde XAMPP).
     * Antes solo se podían quitar entrando a phpMyAdmin. Nunca toca el
     * tráfico real: el WHERE por entorno es la única salvaguarda que hay,
     * así que no se parametriza ni se expone a la petición.
     */
    public function purgarPruebas(): int
    {
        try {
            $this->db->exec(
                'DELETE e FROM landing_eventos e
                   JOIN landing_sesiones s ON s.id = e.sesion_id
                  WHERE s.entorno = "local"'
            );
            return (int)$this->db->exec('DELETE FROM landing_sesiones WHERE entorno = "local"');
        } catch (\Throwable $e) {
            error_log('LandingAnalytics::purgarPruebas — ' . $e->getMessage());
            return 0;
        }
    }

    /** Todas las sesiones del periodo, para el CSV. */
    public function sesionesParaExport(array $f, int $limite = 5000): array
    {
        [$where, $params] = $this->filtro($f);

        return $this->consulta(
            "SELECT s.creado_en, s.slug, s.paso_max, s.seccion_max, s.campo_max,
                    s.scroll_max, s.duracion_seg, s.dispositivo, s.navegador,
                    s.fuente, s.campana, s.pedido_id
               FROM landing_sesiones s
              WHERE {$where}
              ORDER BY s.creado_en DESC
              LIMIT " . max(1, min(20000, $limite)),
            $params
        );
    }

    // ── Utilidades de lectura ─────────────────────────────────

    private function consulta(string $sql, array $params): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('LandingAnalytics — consulta fallida: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Añade el porcentaje sobre el total y limpia el prefijo numérico que el
     * cliente usa para ordenar ("07-testimonios" → "testimonios").
     */
    private function conPorcentaje(array $filas, string $clave): array
    {
        $total = 0;
        foreach ($filas as $fila) $total += (int)$fila['n'];

        foreach ($filas as &$fila) {
            $fila['n']         = (int)$fila['n'];
            $fila['pct']       = $total ? round($fila['n'] * 100 / $total, 1) : 0.0;
            $fila['etiqueta']  = preg_replace('/^\d+-/', '', (string)$fila[$clave]);
            $fila['etiqueta']  = str_replace(['-', '_'], ' ', $fila['etiqueta']);
        }

        return $filas;
    }
}
