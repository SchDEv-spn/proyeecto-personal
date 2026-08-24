<?php
/**
 * TEMAS DE LA LANDING — fuente única
 * =============================================================
 * Antes esta lista vivía copiada en cuatro sitios: la whitelist de la
 * vista pública, las tarjetas del editor, el mapa de paletas del JS del
 * admin y la whitelist de guardado del controlador.
 *
 * Mantener cuatro copias a mano falla sola, y falló: al añadir
 * midnight-amber se actualizaron tres y se olvidó la del controlador.
 * Como esa es la que valida al guardar, el servidor rechazaba el tema
 * nuevo y escribía 'dark-luxury' en su lugar — sin error, sin aviso. El
 * usuario elegía el tema y la landing salía con los colores del
 * anterior, que es un fallo carísimo de diagnosticar desde fuera.
 *
 * A partir de aquí el tema se declara UNA vez. Para añadir uno nuevo:
 *   1. Su entrada aquí.
 *   2. Su bloque [data-theme="slug"] en public/css/style.css.
 * Nada más: el editor, el validador y la vista pública lo recogen solos.
 *
 * -------------------------------------------------------------
 * DE NUEVE TEMAS A CINCO
 * -------------------------------------------------------------
 * Había nueve y cuatro eran variaciones del mismo sitio: light-luxury,
 * femme-rose y blanc-luxe eran tres rosas distintos, y obsidian era
 * dark-luxury sin el dorado. Elegir entre nueve cuando cuatro se parecen
 * no es tener más opciones, es tener que comparar.
 *
 * Peor: los temas claros no estaban diseñados, estaban parcheados. Cada
 * uno arrastraba unos cincuenta selectores propios repintando componente
 * a componente, y con ellos la legibilidad se caía. Medido con WCAG, 13
 * de 54 comprobaciones fallaban. El caso claro es --accent-color: es el
 * valor que alimenta --gold-light, y --gold-light se usa como COLOR DE
 * TEXTO en treinta sitios; natural-sage lo tenía en #52b788, un verde
 * pastel que sobre su propia tarjeta da 1,99:1. Ilegible, y no por poco.
 *
 * Ahora son cinco, uno por nicho, y todos siguen la receta de
 * midnight-amber, que era el único que estaba bien:
 *
 *   - Capas de fondo en hex SÓLIDO (--bg-base .. --bg-layer3). Nada de
 *     rgba sobre un fondo que no controlas: una tarjeta a
 *     rgba(45,106,79,0.05) sobre #f4f7f4 no es "sutil", es invisible.
 *   - Borde teñido con el color del propio tema, nunca blanco ni negro
 *     con alfa. Un blanco al 8% sobre azul se ve gris frío y recorta la
 *     caja en vez de disimularla.
 *   - --cream-dim y --cream-muted en hex MEDIDO, no en color-mix con
 *     transparente: el color-mix depende del fondo y deja de cumplir en
 *     cuanto la tarjeta cambia de tono.
 *   - --cta propio, distinto del acento decorativo. Ver el bloque
 *     "EL ACENTO DE ACCIÓN" en style.css.
 *
 * Con eso los cinco temas necesitan CERO overrides por componente: el
 * sistema de tarjetas de style.css hace el resto.
 *
 * Todos los pares de color pasan 4,5:1 (WCAG AA para texto normal), y la
 * distancia perceptual entre --cta y el acento supera en los cinco el
 * ΔE = 38,6 del par de midnight-amber, que es la referencia dada por
 * buena. Ver contraste_temas.php.
 *
 * 'paleta' son los valores que el editor escribe en los campos de color
 * al elegir el tema, y deben coincidir con el bloque CSS correspondiente.
 *
 * 'cta' es el color del botón de compra. Va fuera de 'paleta' porque
 * 'paleta' son los campos que el editor guarda en la BD y el CTA lo fija
 * el tema en CSS; aquí está sólo para que la miniatura del editor pinte
 * el botón de verdad. Antes esas miniaturas llevaban los nueve colores
 * escritos a mano en admin-unified.css — una sexta copia de la paleta,
 * que es exactamente la clase de duplicado que ya falló una vez.
 *
 * 'alias' son los slugs retirados que resuelven a este tema. Existen
 * porque en producción hay landings guardadas con los nombres viejos y
 * el validador, al no reconocerlos, caía a 'dark-luxury' EN SILENCIO: la
 * landing cambiaba de colores sola y sin dejar rastro. Con el alias, una
 * landing en 'obsidian' pasa a 'relojes', que es su sucesor real.
 * Ver LandingConfig::resolverTema().
 *
 * OJO: las columnas color_* de landing_config son varchar(7). Nada de
 * hex con alfa (#rrggbbaa): se truncan al guardar y dejan el color
 * opaco. Así fue como un borde pensado como blanco al 8% acabó siendo
 * blanco puro en toda la landing.
 */

return [

    /* ── GENÉRICO · Midnight Amber ────────────────────────────
       Intacto: es la referencia de la que salen los otros cuatro.
       Único cambio, --cream-muted de #6e7f9e a #8494b3, porque el
       anterior daba 3,87:1 sobre la tarjeta y se usa en la hora de los
       testimonios de WhatsApp y en el aviso de "desliza" — texto
       pequeño, justo donde menos perdona. */
    'generico' => [
        'cta'    => '#f4621e',
        'nombre' => 'Midnight Amber',
        'nicho'  => 'Genérico',
        'desc'   => 'Azul noche · Ámbar · Todo terreno',
        'alias'  => ['midnight-amber', 'bold-conversion'],
        'paleta' => [
            'background_color'     => '#0f1729',
            'text_color'           => '#e8eefc',
            'primary_color'        => '#f0a83c',
            'accent_color'         => '#ffc46b',
            'secondary_color'      => '#23304d',
            'color_gold'           => '#f0a83c',
            'color_gold_light'     => '#ffc46b',
            'color_success'        => '#3ecf8e',
            'color_countdown'      => '#ffc46b',
            'color_bg_card'        => '#1a2338',
            'color_border'         => '#2b3550',
        ],
    ],

    /* ── RELOJES · Noir Or ────────────────────────────────────
       Negro carbón, no negro puro, por la misma razón que midnight-amber
       tampoco lo es: sobre #000 una tarjeta apenas más clara se lee como
       una mancha gris y desaparece la sensación de capas.
       El oro es el de un reloj, no el de un marco: menos amarillo y algo
       apagado, para que aguante bloques grandes en mayúscula.
       El botón va en rojo vino — el oro decora toda la página, así que la
       acción necesita un color que no salga en ningún otro sitio. */
    'relojes' => [
        'cta'    => '#a51e2d',
        'nombre' => 'Noir Or',
        'nicho'  => 'Relojes',
        'desc'   => 'Negro carbón · Oro · Premium',
        'alias'  => ['dark-luxury', 'obsidian'],
        'paleta' => [
            'background_color'     => '#0d0d0f',
            'text_color'           => '#f2efe9',
            'primary_color'        => '#d9b061',
            'accent_color'         => '#f0cd8e',
            'secondary_color'      => '#3a3226',
            'color_gold'           => '#d9b061',
            'color_gold_light'     => '#f0cd8e',
            'color_success'        => '#46c98a',
            'color_countdown'      => '#f0cd8e',
            'color_bg_card'        => '#1a1a1d',
            'color_border'         => '#2e2e34',
        ],
    ],

    /* ── TECNOLOGÍA · Steel Cyan ──────────────────────────────
       Acero azul-verdoso y cian. El azul marino de minimal-clean se
       descartó porque era el mismo azul que ya lleva el genérico, y dos
       temas que se parecen no son dos temas.
       El botón va en índigo: el cian decora, y sobre fondo oscuro el
       índigo está lo bastante lejos para que no se confundan. */
    'tecnologia' => [
        'cta'    => '#4f39d9',
        'nombre' => 'Steel Cyan',
        'nicho'  => 'Tecnología',
        'desc'   => 'Acero · Cian · Producto técnico',
        'alias'  => ['minimal-clean'],
        'paleta' => [
            'background_color'     => '#0d1418',
            'text_color'           => '#e8f2f7',
            'primary_color'        => '#38bdf8',
            'accent_color'         => '#7dd3fc',
            'secondary_color'      => '#1e3a4d',
            'color_gold'           => '#38bdf8',
            'color_gold_light'     => '#7dd3fc',
            'color_success'        => '#34d399',
            'color_countdown'      => '#7dd3fc',
            'color_bg_card'        => '#17222a',
            'color_border'         => '#24333d',
        ],
    ],

    /* ── SALUD · Clinic Sage ──────────────────────────────────
       Tema CLARO. La tarjeta es blanco puro y el fondo un blanco con un
       punto de verde: la profundidad la lleva la sombra, no un borde —la
       misma regla que en los oscuros.
       OJO con el par de acentos: en un tema claro el que contrasta es el
       OSCURO. Por eso aquí --accent-color (que alimenta --gold-light, y
       --gold-light es texto en treinta sitios) es el verde PROFUNDO
       #094a39, no un verde claro. Invertir esto es exactamente lo que
       rompía natural-sage.
       El botón va en teja: el verde decora y además significa "correcto"
       en los ✓, así que no puede ser también el color de la acción. */
    'salud' => [
        'cta'    => '#0b5a80',
        'nombre' => 'Clinic Sage',
        'nicho'  => 'Salud',
        'desc'   => 'Blanco · Verde clínico · Confianza',
        'alias'  => ['natural-sage'],
        'paleta' => [
            'background_color'     => '#f5f8f6',
            'text_color'           => '#16241d',
            'primary_color'        => '#0d6b51',
            'accent_color'         => '#094a39',
            'secondary_color'      => '#094a39',
            'color_gold'           => '#0d6b51',
            'color_gold_light'     => '#094a39',
            'color_success'        => '#15803d',
            'color_countdown'      => '#0d6b51',
            'color_bg_card'        => '#ffffff',
            'color_border'         => '#d8e4dd',
        ],
    ],

    /* ── BELLEZA · Rose Nude ──────────────────────────────────
       Tema CLARO, misma receta que salud. Sustituye a los tres rosas que
       había (light-luxury, femme-rose, blanc-luxe), que se diferenciaban
       en poco más que el tono del fondo.
       Igual que en salud, el acento "light" es el rosa PROFUNDO: sobre
       blanco, un rosa pastel como el #e8a4b8 de blanc-luxe daba 1,81:1.
       El botón va en violeta — rosa y violeta es un par corriente en
       cosmética y la distancia perceptual sobra (ΔE 82). */
    'belleza' => [
        'cta'    => '#8f5709',
        'nombre' => 'Rose Nude',
        'nicho'  => 'Belleza',
        'desc'   => 'Nude · Rosa profundo · Cosmética',
        'alias'  => ['femme-rose', 'light-luxury', 'blanc-luxe'],
        'paleta' => [
            'background_color'     => '#fdf7f6',
            'text_color'           => '#2a161d',
            'primary_color'        => '#a8305a',
            'accent_color'         => '#7d1f42',
            'secondary_color'      => '#7d1f42',
            'color_gold'           => '#a8305a',
            'color_gold_light'     => '#7d1f42',
            'color_success'        => '#15803d',
            'color_countdown'      => '#a8305a',
            'color_bg_card'        => '#ffffff',
            'color_border'         => '#ecd9dc',
        ],
    ],
];
