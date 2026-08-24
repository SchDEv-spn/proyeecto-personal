<?php
/**
 * AUDITORÍA DE CONTRASTE DE LOS TEMAS DE LA LANDING
 * =============================================================
 * Uso:  php contraste_temas.php
 *
 * Lee los bloques [data-theme="..."] de public/css/style.css y comprueba
 * los pares de color que de verdad se pintan juntos en la landing. Lee
 * el CSS, NO app/config/themes.php: si comprobara la copia de themes.php
 * diría que todo está bien mientras el CSS —que es lo que ve el
 * visitante— dice otra cosa. El desajuste entre esas dos listas ya costó
 * caro una vez.
 *
 * Qué comprueba y por qué ese par y no otro:
 *
 *   --gold-light sobre la tarjeta   Es COLOR DE TEXTO en treinta sitios
 *                                   de style.css, aunque el nombre no lo
 *                                   sugiera. Aquí es donde estaba el
 *                                   fallo gordo: natural-sage lo tenía en
 *                                   un verde pastel, 1,99:1.
 *   --cream-muted sobre la tarjeta  Texto de 11-12px (la hora de los
 *                                   testimonios, el aviso de "desliza").
 *   --cta-text sobre --cta Y        El botón es un degradado. El blanco
 *   --cta-text sobre --cta-soft     aguanta el extremo oscuro y se cae
 *                                   en el claro; comprobar sólo uno deja
 *                                   pasar la mitad de los fallos.
 *
 * Y la distancia entre --cta y el acento se mide por ΔE en Lab, no por
 * contraste: el par de midnight-amber (ámbar/naranja) da 1,57:1 y sin
 * embargo es inconfundible, porque lo que los separa es el tono y no la
 * luminosidad. Medirlo con contraste daría un fallo falso justo en el
 * tema que sirve de referencia.
 *
 * Salida: una línea por comprobación y un resumen. Devuelve código 1 si
 * algo falla, para poder encadenarlo.
 */

$CSS   = __DIR__ . '/public/css/style.css';
$MIN   = 4.5;   // WCAG AA, texto normal
$MIN_DE = 38.6; // ΔE del par ámbar/naranja de midnight-amber: el listón

/* ── color ────────────────────────────────────────────────── */

function hex2rgb(string $h): ?array {
    $h = ltrim(trim($h), '#');
    if (strlen($h) === 3) $h = $h[0].$h[0].$h[1].$h[1].$h[2].$h[2];
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $h)) return null;
    return [hexdec(substr($h,0,2)), hexdec(substr($h,2,2)), hexdec(substr($h,4,2))];
}

function lin(float $v): float {
    $v /= 255;
    return $v <= 0.03928 ? $v/12.92 : pow(($v+0.055)/1.055, 2.4);
}

function luminancia(array $rgb): float {
    return 0.2126*lin($rgb[0]) + 0.7152*lin($rgb[1]) + 0.0722*lin($rgb[2]);
}

function contraste(array $a, array $b): float {
    $l1 = luminancia($a); $l2 = luminancia($b);
    if ($l1 < $l2) [$l1, $l2] = [$l2, $l1];
    return ($l1 + 0.05) / ($l2 + 0.05);
}

function lab(array $rgb): array {
    $r = lin($rgb[0]); $g = lin($rgb[1]); $b = lin($rgb[2]);
    $X = ($r*0.4124564 + $g*0.3575761 + $b*0.1804375) / 0.95047;
    $Y = ($r*0.2126729 + $g*0.7151522 + $b*0.0721750);
    $Z = ($r*0.0193339 + $g*0.1191920 + $b*0.9503041) / 1.08883;
    $f = fn($t) => $t > 0.008856 ? pow($t, 1/3) : (7.787*$t + 16/116);
    return [116*$f($Y)-16, 500*($f($X)-$f($Y)), 200*($f($Y)-$f($Z))];
}

function deltaE(array $a, array $b): float {
    [$l1,$a1,$b1] = lab($a); [$l2,$a2,$b2] = lab($b);
    return sqrt(($l1-$l2)**2 + ($a1-$a2)**2 + ($b1-$b2)**2);
}

/* rgba(r,g,b,alfa) compuesto sobre un fondo opaco */
function componer(array $frente, float $alfa, array $fondo): array {
    return [
        (int) round($frente[0]*$alfa + $fondo[0]*(1-$alfa)),
        (int) round($frente[1]*$alfa + $fondo[1]*(1-$alfa)),
        (int) round($frente[2]*$alfa + $fondo[2]*(1-$alfa)),
    ];
}

/**
 * Resuelve el valor de un token dentro de un tema hasta llegar a un
 * color usable. Sigue cadenas de var(), compone rgba() sobre el fondo
 * dado y entiende color-mix(in srgb, C p%, transparent).
 */
function resolver(string $token, array $tema, ?array $fondo = null, int $prof = 0): ?array {
    if ($prof > 8 || !isset($tema[$token])) return null;
    $v = trim($tema[$token]);

    if ($v !== '' && $v[0] === '#') return hex2rgb($v);

    if (preg_match('/^var\(\s*--([a-z0-9-]+)/i', $v, $m)) {
        return resolver($m[1], $tema, $fondo, $prof+1);
    }

    if (preg_match('/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)/', $v, $m)) {
        $c = [(int)$m[1], (int)$m[2], (int)$m[3]];
        $a = isset($m[4]) ? (float)$m[4] : 1.0;
        if ($a >= 1.0) return $c;
        return $fondo ? componer($c, $a, $fondo) : $c;
    }

    if (preg_match('/^color-mix\(\s*in\s+srgb\s*,\s*(.+?)\s+([\d.]+)%\s*,\s*transparent\s*\)/i', $v, $m)) {
        $base = trim($m[1]);
        $c = $base[0] === '#'
            ? hex2rgb($base)
            : (preg_match('/^var\(\s*--([a-z0-9-]+)/i', $base, $mm) ? resolver($mm[1], $tema, $fondo, $prof+1) : null);
        if (!$c) return null;
        return $fondo ? componer($c, ((float)$m[2])/100, $fondo) : $c;
    }

    return null;
}

/* ── leer los temas del CSS ───────────────────────────────── */

if (!is_file($CSS)) { fwrite(STDERR, "No encuentro $CSS\n"); exit(2); }
$css = file_get_contents($CSS);

$temas = [];
if (preg_match_all('/\[data-theme="([a-z0-9-]+)"\]\s*\{([^}]*)\}/i', $css, $M, PREG_SET_ORDER)) {
    foreach ($M as $m) {
        $vars = [];
        if (preg_match_all('/--([a-z0-9-]+)\s*:\s*([^;]+);/i', $m[2], $V, PREG_SET_ORDER)) {
            foreach ($V as $v) $vars[strtolower($v[1])] = trim($v[2]);
        }
        if ($vars) $temas[$m[1]] = $vars;
    }
}

if (!$temas) { fwrite(STDERR, "No he encontrado ningun bloque [data-theme] en el CSS\n"); exit(2); }

/* ── comprobar ────────────────────────────────────────────── */

$fallos = 0; $total = 0;

foreach ($temas as $slug => $t) {
    printf("\n=== %s ===\n", $slug);

    $tarjeta = resolver('bg-card', $t) ?? resolver('bg-layer2', $t);
    $base    = resolver('bg-base', $t);
    if (!$tarjeta || !$base) { echo "  (sin --bg-card / --bg-base legibles, salto)\n"; continue; }

    $pares = [
        ['texto sobre la tarjeta',            'cream',           $tarjeta],
        ['texto sobre el fondo',              'cream',           $base],
        ['--cream-dim sobre la tarjeta',      'cream-dim',       $tarjeta],
        ['--cream-muted sobre la tarjeta',    'cream-muted',     $tarjeta],
        ['--gold-light (TEXTO, 30 usos)',     'gold-light',      $tarjeta],
        ['--gold sobre la tarjeta',           'gold',            $tarjeta],
        ['--success sobre la tarjeta',        'success',         $tarjeta],
    ];

    foreach ($pares as [$etiqueta, $token, $fondo]) {
        $c = resolver($token, $t, $fondo);
        if (!$c) { printf("  %-4s %-34s (no resuelve)\n", '??', $etiqueta); continue; }
        $r = contraste($c, $fondo);
        $ok = $r >= $MIN; $total++; if (!$ok) $fallos++;
        printf("  %-4s %-34s %5.2f:1\n", $ok ? 'OK' : 'BAJO', $etiqueta, $r);
    }

    /* Botón principal: texto sobre el acento */
    $gold = resolver('gold', $t); $btn = resolver('btn-text', $t);
    if ($gold && $btn) {
        $r = contraste($btn, $gold); $ok = $r >= $MIN; $total++; if (!$ok) $fallos++;
        printf("  %-4s %-34s %5.2f:1\n", $ok ? 'OK' : 'BAJO', 'texto del boton sobre --gold', $r);
    }

    /* Botón de compra: LOS DOS extremos del degradado */
    $cta  = resolver('cta', $t);
    $soft = resolver('cta-soft', $t);
    $txt  = resolver('cta-text', $t);
    if ($cta && $txt) {
        foreach ([['--cta-text sobre --cta', $cta], ['--cta-text sobre --cta-soft', $soft]] as [$et, $bg]) {
            if (!$bg) continue;
            $r = contraste($txt, $bg); $ok = $r >= $MIN; $total++; if (!$ok) $fallos++;
            printf("  %-4s %-34s %5.2f:1\n", $ok ? 'OK' : 'BAJO', $et, $r);
        }
    }

    /* Separación CTA / acento: por tono, no por luminosidad */
    if ($cta && $gold) {
        $d = deltaE($cta, $gold); $ok = $d >= $MIN_DE; $total++; if (!$ok) $fallos++;
        printf("  %-4s %-34s dE %5.1f  (min %.1f)\n", $ok ? 'OK' : 'BAJO', 'distancia CTA vs acento', $d, $MIN_DE);
    }

    /* La píldora sobre foto tiene su propia superficie */
    $pbg = resolver('pill-photo-bg', $t);
    $ptx = resolver('pill-photo-text', $t, $pbg);
    if ($pbg && $ptx) {
        $r = contraste($ptx, $pbg); $ok = $r >= $MIN; $total++; if (!$ok) $fallos++;
        printf("  %-4s %-34s %5.2f:1\n", $ok ? 'OK' : 'BAJO', 'pildora sobre foto', $r);
    }
}

/* ── el botón de cada tema contra el de los demás ──────────────
   Todo lo de arriba compara colores DENTRO de un tema, y por eso dejó
   pasar un choque que sólo apareció al abrir dos capturas seguidas: el
   violeta que llevaba belleza y el índigo de tecnología eran a ojo el
   mismo botón. Cada tema aprobaba su examen por separado y aun así dos
   de cinco se confundían entre sí. */
echo "\n=== los botones, unos contra otros ===\n";
$ctas = [];
foreach ($temas as $slug => $t) {
    $c = resolver('cta', $t);
    if ($c) $ctas[$slug] = $c;
}
$slugs = array_keys($ctas);
for ($i = 0; $i < count($slugs); $i++) {
    for ($j = $i + 1; $j < count($slugs); $j++) {
        $d = deltaE($ctas[$slugs[$i]], $ctas[$slugs[$j]]);
        $ok = $d >= $MIN_DE; $total++; if (!$ok) $fallos++;
        printf("  %-4s %-16s vs %-16s dE %5.1f\n", $ok ? 'OK' : 'BAJO', $slugs[$i], $slugs[$j], $d);
    }
}

printf("\n---------------------------------------------\n");
printf("Temas: %d   Comprobaciones: %d   Fallos: %d\n", count($temas), $total, $fallos);
exit($fallos > 0 ? 1 : 0);
