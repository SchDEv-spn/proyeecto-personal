<?php

/**
 * LandingRecoTareas — backlog de recomendaciones de la IA.
 *
 * Cada análisis (LandingAnalisis) devuelve acciones sueltas atadas a esa fila
 * y a ese periodo. Este backlog las junta por producto y sobrevive al
 * re-análisis: una recomendación que reaparece no se duplica (dedup por
 * huella), y si ya la marcaste hecha/aplicada/descartada se queda así.
 *
 * Para los cambios aplicados guarda el valor anterior (Deshacer) y, pasada
 * una ventana, compara la conversión de antes y después.
 */
class LandingRecoTareas extends Model
{
    /** Ventana de medición a cada lado de la fecha en que se resolvió. */
    private const VENTANA_DIAS = 14;

    private const DDL = 'CREATE TABLE IF NOT EXISTS landing_reco_tareas (
        id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
        producto_id    INT          NOT NULL,
        huella         VARCHAR(64)  NOT NULL,
        accion         VARCHAR(400) NOT NULL,
        donde          VARCHAR(255) NULL,
        seccion_id     VARCHAR(40)  NULL,
        impacto        ENUM("alto","medio","bajo") NULL,
        esfuerzo       ENUM("alto","medio","bajo") NULL,
        cambio_campo   VARCHAR(40)  NULL,
        cambio_valor   VARCHAR(40)  NULL,
        estado         ENUM("pendiente","hecha","aplicada","descartada") NOT NULL DEFAULT "pendiente",
        valor_anterior VARCHAR(40)  NULL,
        veces_sugerida SMALLINT UNSIGNED NOT NULL DEFAULT 1,
        primera_vez    DATETIME     NOT NULL,
        ultima_vez     DATETIME     NOT NULL,
        resuelta_en    DATETIME     NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_tarea (producto_id, huella),
        KEY idx_prod_estado (producto_id, estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    /** Consulta tolerante: crea la tabla ante 42S02 y reintenta una vez. */
    private function consultar(string $sql, array $params, bool $reintento = false): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            if (!$reintento && (string)$e->getCode() === '42S02') {
                $this->db->exec(self::DDL);
                return $this->consultar($sql, $params, true);
            }
            error_log('LandingRecoTareas — ' . $e->getMessage());
            return [];
        }
    }

    private function ejecutar(string $sql, array $params, bool $reintento = false): bool
    {
        try {
            return $this->db->prepare($sql)->execute($params);
        } catch (\PDOException $e) {
            if (!$reintento && (string)$e->getCode() === '42S02') {
                $this->db->exec(self::DDL);
                return $this->ejecutar($sql, $params, true);
            }
            error_log('LandingRecoTareas — ' . $e->getMessage());
            return false;
        }
    }

    // ══════════════════════════════════════════════════════════
    //  ESCRITURA
    // ══════════════════════════════════════════════════════════

    /**
     * Huella para deduplicar. Si la acción trae un cambio de config concreto,
     * dedup por (sección + campo): "enciende la garantía" es la misma tarea
     * aunque la IA la redacte distinto la próxima vez. Si no, por el texto
     * normalizado.
     */
    private function huella(int $productoId, string $seccionId, string $accion, ?string $cambioCampo): string
    {
        $clave = $cambioCampo
            ? 'campo:' . $cambioCampo
            : 'txt:' . mb_substr(preg_replace('/\s+/u', ' ', mb_strtolower(trim($accion))), 0, 160);

        return md5($productoId . '|' . $seccionId . '|' . $clave);
    }

    /**
     * Vuelca las acciones de un análisis al backlog. Reaparición → sube
     * `veces_sugerida` y refresca los metadatos, pero NO toca el estado ni
     * nada que haya decidido el usuario.
     */
    public function upsertDesdeAnalisis(int $productoId, array $acciones): void
    {
        if ($productoId <= 0 || !$acciones) return;
        $this->db->exec(self::DDL);

        foreach ($acciones as $a) {
            $accion = trim((string)($a['accion'] ?? ''));
            if ($accion === '') continue;

            $seccionId = (string)($a['seccion_id'] ?? 'ninguna');
            $cambio    = is_array($a['cambio'] ?? null) ? $a['cambio'] : [];
            $campo     = ($cambio['campo'] ?? 'ninguno') !== 'ninguno' ? (string)$cambio['campo'] : null;
            $valor     = $campo !== null ? (string)(int)($cambio['valor'] ?? 0) : null;

            $huella = $this->huella($productoId, $seccionId, $accion, $campo);

            $this->ejecutar(
                'INSERT INTO landing_reco_tareas
                    (producto_id, huella, accion, donde, seccion_id, impacto, esfuerzo,
                     cambio_campo, cambio_valor, primera_vez, ultima_vez)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                     accion       = VALUES(accion),
                     donde        = VALUES(donde),
                     seccion_id   = VALUES(seccion_id),
                     impacto      = VALUES(impacto),
                     esfuerzo     = VALUES(esfuerzo),
                     cambio_campo = VALUES(cambio_campo),
                     cambio_valor = VALUES(cambio_valor),
                     ultima_vez     = NOW(),
                     veces_sugerida = veces_sugerida + 1',
                [
                    $productoId, $huella,
                    mb_substr($accion, 0, 400),
                    mb_substr((string)($a['donde'] ?? ''), 0, 255) ?: null,
                    mb_substr($seccionId, 0, 40),
                    in_array($a['impacto'] ?? '', ['alto', 'medio', 'bajo'], true) ? $a['impacto'] : null,
                    in_array($a['esfuerzo'] ?? '', ['alto', 'medio', 'bajo'], true) ? $a['esfuerzo'] : null,
                    $campo, $valor,
                ]
            );
        }
    }

    /** hecha | descartada | pendiente (reabre). */
    public function marcar(int $tareaId, int $productoId, string $estado): bool
    {
        if (!in_array($estado, ['pendiente', 'hecha', 'descartada'], true)) return false;

        return $this->ejecutar(
            'UPDATE landing_reco_tareas
                SET estado = ?, resuelta_en = ' . ($estado === 'pendiente' ? 'NULL' : 'NOW()') . '
              WHERE id = ? AND producto_id = ?',
            [$estado, $tareaId, $productoId]
        );
    }

    /**
     * Aplica el cambio de config de una tarea y la marca 'aplicada',
     * guardando el valor anterior para Deshacer.
     */
    public function aplicar(int $tareaId, int $productoId): array
    {
        $tarea = $this->consultar(
            'SELECT * FROM landing_reco_tareas WHERE id = ? AND producto_id = ? LIMIT 1',
            [$tareaId, $productoId]
        );
        if (!$tarea) return ['ok' => false, 'error' => 'Tarea no encontrada'];
        $tarea = $tarea[0];

        if (empty($tarea['cambio_campo'])) {
            return ['ok' => false, 'error' => 'Esta recomendación no se puede aplicar automáticamente'];
        }

        $res = (new LandingConfig())->aplicarCampo(
            $productoId, $tarea['cambio_campo'], (int)$tarea['cambio_valor']
        );
        if (!$res['ok']) return $res;

        $this->ejecutar(
            'UPDATE landing_reco_tareas
                SET estado = "aplicada", valor_anterior = ?, resuelta_en = NOW()
              WHERE id = ? AND producto_id = ?',
            [(string)$res['anterior'], $tareaId, $productoId]
        );

        return ['ok' => true, 'anterior' => $res['anterior'], 'aplicado' => $res['aplicado'], 'label' => $res['label']];
    }

    /** Revierte un cambio aplicado y devuelve la tarea a pendiente. */
    public function deshacer(int $tareaId, int $productoId): array
    {
        $tarea = $this->consultar(
            'SELECT * FROM landing_reco_tareas
              WHERE id = ? AND producto_id = ? AND estado = "aplicada" LIMIT 1',
            [$tareaId, $productoId]
        );
        if (!$tarea) return ['ok' => false, 'error' => 'No hay nada que deshacer'];
        $tarea = $tarea[0];

        if ($tarea['valor_anterior'] === null || $tarea['cambio_campo'] === null) {
            return ['ok' => false, 'error' => 'Sin valor anterior guardado'];
        }

        $res = (new LandingConfig())->aplicarCampo(
            $productoId, $tarea['cambio_campo'], (int)$tarea['valor_anterior']
        );
        if (!$res['ok']) return $res;

        $this->ejecutar(
            'UPDATE landing_reco_tareas
                SET estado = "pendiente", valor_anterior = NULL, resuelta_en = NULL
              WHERE id = ? AND producto_id = ?',
            [$tareaId, $productoId]
        );

        return ['ok' => true];
    }

    // ══════════════════════════════════════════════════════════
    //  LECTURA
    // ══════════════════════════════════════════════════════════

    public function pendientes(int $productoId): array
    {
        if ($productoId <= 0) return [];
        return $this->consultar(
            "SELECT * FROM landing_reco_tareas
              WHERE producto_id = ? AND estado = 'pendiente'
              ORDER BY FIELD(impacto,'alto','medio','bajo'), FIELD(esfuerzo,'bajo','medio','alto'), ultima_vez DESC",
            [$productoId]
        );
    }

    public function resueltas(int $productoId, int $limite = 20): array
    {
        if ($productoId <= 0) return [];
        return $this->consultar(
            "SELECT * FROM landing_reco_tareas
              WHERE producto_id = ? AND estado IN ('hecha','aplicada','descartada')
              ORDER BY resuelta_en DESC LIMIT " . max(1, min(50, $limite)),
            [$productoId]
        );
    }

    /**
     * Anota cada tarea resuelta con la conversión de antes y después del
     * cambio. Solo para las resueltas entre hace 7 y 60 días (antes es
     * pronto, después ya es historia vieja) y como mucho 10, para no
     * disparar 20 consultas al abrir el editor.
     */
    public function conImpacto(array $resueltas): array
    {
        $analytics = new LandingAnalytics();
        $medidas   = 0;
        $ahora     = new DateTime('now');

        foreach ($resueltas as &$t) {
            if ($t['estado'] === 'descartada' || empty($t['resuelta_en'])) continue;

            $resuelta = new DateTime($t['resuelta_en']);
            $diasDesde = (int)$ahora->diff($resuelta)->days;
            if ($diasDesde < 7 || $diasDesde > 60 || $medidas >= 10) continue;
            $medidas++;

            $productoId = (int)$t['producto_id'];
            $antesDesde   = (clone $resuelta)->modify('-' . self::VENTANA_DIAS . ' days');
            $despuesHasta = (clone $resuelta)->modify('+' . self::VENTANA_DIAS . ' days');
            if ($despuesHasta > $ahora) $despuesHasta = clone $ahora;

            $fmt = fn(DateTime $d) => $d->format('Y-m-d H:i:s');
            $antes = $analytics->resumen([
                'desde' => $fmt($antesDesde), 'hasta' => $fmt($resuelta),
                'producto_id' => $productoId, 'entorno' => 'produccion',
            ]);
            $despues = $analytics->resumen([
                'desde' => $fmt($resuelta), 'hasta' => $fmt($despuesHasta),
                'producto_id' => $productoId, 'entorno' => 'produccion',
            ]);

            if ((int)$antes['sesiones'] < 30 || (int)$despues['sesiones'] < 30) {
                $estado = $diasDesde < self::VENTANA_DIAS ? 'midiendo' : 'sin_datos';
                $nota   = $diasDesde < self::VENTANA_DIAS
                    ? 'faltan ' . (self::VENTANA_DIAS - $diasDesde) . ' días'
                    : 'pocas visitas para medir';
                $t['impacto_medido'] = ['estado' => $estado, 'nota' => $nota];
                continue;
            }

            $t['impacto_medido'] = [
                'estado'  => 'listo',
                'antes'   => (float)$antes['conversion'],
                'despues' => (float)$despues['conversion'],
                'delta'   => round((float)$despues['conversion'] - (float)$antes['conversion'], 2),
            ];
        }
        unset($t);

        return $resueltas;
    }
}
