<?php
/**
 * MIGRACIÓN: nueve temas -> cinco
 * =============================================================
 * Uso:   php migrar_temas_5.php          (informe, no toca nada)
 *        php migrar_temas_5.php --aplicar
 *
 * Cambiar la columna `theme` NO basta, y esta es la parte que no se ve
 * leyendo el código:
 *
 * El editor, al elegir un tema, copia su paleta en las columnas color_*
 * de landing_config, y la vista pública las inyecta en un <style> que
 * gana sobre el tema. Es decir: cada landing lleva CONGELADA la paleta
 * que estaba vigente el día que se guardó. Si sólo se migra `theme`, la
 * landing sigue pintándose con los colores viejos por encima del tema
 * nuevo — y en el caso de natural-sage eso significa seguir con el
 * accent #52b788, el verde pastel de 1,99:1 que motivó todo esto.
 *
 * Así que aquí se hacen dos cosas:
 *
 *   1. theme: slug viejo -> su sucesor, según el mapa 'alias' de
 *      app/config/themes.php.
 *   2. Los colores congelados: si coinciden EXACTAMENTE con la paleta
 *      del tema viejo, es una copia que nadie tocó y se sustituye por la
 *      del tema nuevo. Si difieren en algo, el dueño los personalizó a
 *      mano: NO se tocan y se avisa, porque ahí sobrescribir sería
 *      borrarle trabajo.
 *
 * Las paletas viejas van escritas abajo a propósito, en vez de leerse de
 * git: este archivo tiene que seguir funcionando cuando el histórico ya
 * no esté a mano, y es de un solo uso.
 */

require __DIR__ . '/app/core/Database.php';

$aplicar = in_array('--aplicar', $argv, true);
$nuevos  = require __DIR__ . '/app/config/themes.php';

/* Paleta de cada tema retirado, tal y como la escribía el editor. */
$viejas = [
  'dark-luxury' => ['background_color'=>'#080808','text_color'=>'#f5ede0','primary_color'=>'#d4a853','accent_color'=>'#f0c472','secondary_color'=>'#6b4c1e','color_gold'=>'#d4a853','color_gold_light'=>'#f0c472','color_success'=>'#4caf7d','color_countdown'=>'#f0c472','color_bg_card'=>'#1c1814','color_border'=>'#d4a853'],
  'light-luxury' => ['background_color'=>'#fdf8f5','text_color'=>'#1a1014','primary_color'=>'#8b2252','accent_color'=>'#b5436e','secondary_color'=>'#4a1228','color_gold'=>'#8b2252','color_gold_light'=>'#b5436e','color_success'=>'#2e7d32','color_countdown'=>'#8b2252','color_bg_card'=>'#f7f0ec','color_border'=>'#8b2252'],
  'bold-conversion' => ['background_color'=>'#ffffff','text_color'=>'#1a1410','primary_color'=>'#e76f51','accent_color'=>'#f4a261','secondary_color'=>'#264653','color_gold'=>'#e76f51','color_gold_light'=>'#f4a261','color_success'=>'#2d6a4f','color_countdown'=>'#e76f51','color_bg_card'=>'#fdf8f5','color_border'=>'#e76f51'],
  'minimal-clean' => ['background_color'=>'#f8fafc','text_color'=>'#0f1d30','primary_color'=>'#1a2e4a','accent_color'=>'#2563eb','secondary_color'=>'#0f1d30','color_gold'=>'#1a2e4a','color_gold_light'=>'#2563eb','color_success'=>'#1b5e20','color_countdown'=>'#2563eb','color_bg_card'=>'#f0f4f8','color_border'=>'#1a2e4a'],
  'femme-rose' => ['background_color'=>'#fff5f7','text_color'=>'#2d1420','primary_color'=>'#c94a6b','accent_color'=>'#e87d9a','secondary_color'=>'#7a1f3d','color_gold'=>'#c94a6b','color_gold_light'=>'#e87d9a','color_success'=>'#2e7d32','color_countdown'=>'#c94a6b','color_bg_card'=>'#ffedf1','color_border'=>'#c94a6b'],
  'natural-sage' => ['background_color'=>'#f4f7f4','text_color'=>'#1a2e22','primary_color'=>'#2d6a4f','accent_color'=>'#52b788','secondary_color'=>'#1b4332','color_gold'=>'#2d6a4f','color_gold_light'=>'#52b788','color_success'=>'#1b4332','color_countdown'=>'#2d6a4f','color_bg_card'=>'#eaf2ec','color_border'=>'#2d6a4f'],
  'obsidian' => ['background_color'=>'#050505','text_color'=>'#f0f0f0','primary_color'=>'#b0b0b0','accent_color'=>'#e0e0e0','secondary_color'=>'#606060','color_gold'=>'#b0b0b0','color_gold_light'=>'#e0e0e0','color_success'=>'#6acd8e','color_countdown'=>'#e0e0e0','color_bg_card'=>'#161616','color_border'=>'#b0b0b0'],
  'blanc-luxe' => ['background_color'=>'#fff8f6','text_color'=>'#4a2535','primary_color'=>'#c4687a','accent_color'=>'#e8a4b8','secondary_color'=>'#a04060','color_gold'=>'#c4687a','color_gold_light'=>'#e8a4b8','color_success'=>'#3a7c5c','color_countdown'=>'#c4687a','color_bg_card'=>'#fdf0ee','color_border'=>'#c4687a'],
  'midnight-amber' => ['background_color'=>'#0f1729','text_color'=>'#e8eefc','primary_color'=>'#f0a83c','accent_color'=>'#ffc46b','secondary_color'=>'#23304d','color_gold'=>'#f0a83c','color_gold_light'=>'#ffc46b','color_success'=>'#3ecf8e','color_countdown'=>'#ffc46b','color_bg_card'=>'#1a2338','color_border'=>'#2b3550'],
];

/* slug viejo -> slug nuevo, desde el mapa de alias */
$sucesor = [];
foreach ($nuevos as $slug => $t) {
    $sucesor[$slug] = $slug;
    foreach ($t['alias'] ?? [] as $a) $sucesor[$a] = $slug;
}

$COLS = array_keys($viejas['dark-luxury']);

$db = (new Database())->conn;
$filas = $db->query("SELECT producto_id, theme, " . implode(', ', $COLS) . " FROM landing_config")->fetchAll(PDO::FETCH_ASSOC);

$norm = fn($v) => strtolower(trim((string)$v));

$plan = [];
foreach ($filas as $f) {
    $temaGuardado = $f['theme'] ?? '';
    $destino = $sucesor[$temaGuardado] ?? array_key_first($nuevos);

    /* ¿Los colores congelados son una copia intacta de ALGUNA paleta
       vieja? Se busca entre todas, no sólo la del tema guardado: una
       landing puede haber cambiado de tema sin volver a guardar colores. */
    $coincideCon = null;
    foreach ($viejas as $slugViejo => $pal) {
        $igual = true;
        foreach ($COLS as $c) {
            if ($norm($f[$c]) !== $norm($pal[$c])) { $igual = false; break; }
        }
        if ($igual) { $coincideCon = $slugViejo; break; }
    }

    $todoVacio = true;
    foreach ($COLS as $c) { if ($norm($f[$c]) !== '') { $todoVacio = false; break; } }

    if ($todoVacio) {
        $accion = 'solo-tema';   // sin colores propios: el tema manda ya
    } elseif ($coincideCon !== null) {
        $accion = 'repintar';    // copia intacta de una paleta vieja
    } else {
        $accion = 'respetar';    // personalizado a mano
    }

    $plan[] = ['id'=>$f['producto_id'], 'de'=>$temaGuardado, 'a'=>$destino,
               'accion'=>$accion, 'copiaDe'=>$coincideCon];
}

printf("%-6s %-16s %-11s %-10s %s\n", 'prod', 'tema guardado', '-> nuevo', 'colores', 'detalle');
echo str_repeat('-', 78) . "\n";
$n = ['solo-tema'=>0,'repintar'=>0,'respetar'=>0];
foreach ($plan as $p) {
    $n[$p['accion']]++;
    $det = $p['accion'] === 'repintar'  ? 'copia intacta de ' . $p['copiaDe']
         : ($p['accion'] === 'respetar' ? 'PERSONALIZADO — no se toca' : 'sin colores propios');
    printf("%-6s %-16s %-11s %-10s %s\n", $p['id'], $p['de'], $p['a'], $p['accion'], $det);
}
echo str_repeat('-', 78) . "\n";
printf("repintar: %d   respetar: %d   solo-tema: %d\n", $n['repintar'], $n['respetar'], $n['solo-tema']);

if (!$aplicar) {
    echo "\nInforme solamente. Para escribir: php migrar_temas_5.php --aplicar\n";
    exit(0);
}

$db->beginTransaction();
try {
    $setCols = implode(', ', array_map(fn($c) => "$c = :$c", $COLS));
    $stmtFull = $db->prepare("UPDATE landing_config SET theme = :theme, $setCols WHERE producto_id = :pid");
    $stmtTema = $db->prepare("UPDATE landing_config SET theme = :theme WHERE producto_id = :pid");

    foreach ($plan as $p) {
        if ($p['accion'] === 'repintar') {
            $pal = $nuevos[$p['a']]['paleta'];
            $args = [':theme' => $p['a'], ':pid' => $p['id']];
            foreach ($COLS as $c) $args[":$c"] = $pal[$c];
            $stmtFull->execute($args);
        } else {
            $stmtTema->execute([':theme' => $p['a'], ':pid' => $p['id']]);
        }
    }
    $db->commit();
    echo "\nAplicado.\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Fallo, nada escrito: " . $e->getMessage() . "\n");
    exit(1);
}
