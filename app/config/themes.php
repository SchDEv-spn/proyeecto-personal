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
 * 'paleta' son los valores que el editor escribe en los campos de color
 * al elegir el tema, y deben coincidir con el bloque CSS correspondiente.
 *
 * OJO: las columnas color_* de landing_config son varchar(7). Nada de
 * hex con alfa (#rrggbbaa): se truncan al guardar y dejan el color
 * opaco. Así fue como un borde pensado como blanco al 8% acabó siendo
 * blanco puro en toda la landing.
 */

return [
    'dark-luxury' => [
        'nombre' => 'Dark Luxury',
        'desc'   => 'Negro · Dorado cálido · Premium',
        'paleta' => [
            'background_color'     => '#080808',
            'text_color'           => '#f5ede0',
            'primary_color'        => '#d4a853',
            'accent_color'         => '#f0c472',
            'secondary_color'      => '#6b4c1e',
            'color_gold'           => '#d4a853',
            'color_gold_light'     => '#f0c472',
            'color_success'        => '#4caf7d',
            'color_countdown'      => '#f0c472',
            'color_bg_card'        => '#1c1814',
            'color_border'         => '#d4a853',
        ],
    ],

    'light-luxury' => [
        'nombre' => 'Light Luxury',
        'desc'   => 'Crema · Borgoña · Femenino',
        'paleta' => [
            'background_color'     => '#fdf8f5',
            'text_color'           => '#1a1014',
            'primary_color'        => '#8b2252',
            'accent_color'         => '#b5436e',
            'secondary_color'      => '#4a1228',
            'color_gold'           => '#8b2252',
            'color_gold_light'     => '#b5436e',
            'color_success'        => '#2e7d32',
            'color_countdown'      => '#8b2252',
            'color_bg_card'        => '#f7f0ec',
            'color_border'         => '#8b2252',
        ],
    ],

    'bold-conversion' => [
        'nombre' => 'Bold Conversion',
        'desc'   => 'Blanco · Naranja · Energético',
        'paleta' => [
            'background_color'     => '#ffffff',
            'text_color'           => '#1a1410',
            'primary_color'        => '#e76f51',
            'accent_color'         => '#f4a261',
            'secondary_color'      => '#264653',
            'color_gold'           => '#e76f51',
            'color_gold_light'     => '#f4a261',
            'color_success'        => '#2d6a4f',
            'color_countdown'      => '#e76f51',
            'color_bg_card'        => '#fdf8f5',
            'color_border'         => '#e76f51',
        ],
    ],

    'minimal-clean' => [
        'nombre' => 'Minimal Clean',
        'desc'   => 'Azul marino · Confianza · Tech',
        'paleta' => [
            'background_color'     => '#f8fafc',
            'text_color'           => '#0f1d30',
            'primary_color'        => '#1a2e4a',
            'accent_color'         => '#2563eb',
            'secondary_color'      => '#0f1d30',
            'color_gold'           => '#1a2e4a',
            'color_gold_light'     => '#2563eb',
            'color_success'        => '#1b5e20',
            'color_countdown'      => '#2563eb',
            'color_bg_card'        => '#f0f4f8',
            'color_border'         => '#1a2e4a',
        ],
    ],

    'femme-rose' => [
        'nombre' => 'Femme Rose',
        'desc'   => 'Rosa · Fucsia · Belleza',
        'paleta' => [
            'background_color'     => '#fff5f7',
            'text_color'           => '#2d1420',
            'primary_color'        => '#c94a6b',
            'accent_color'         => '#e87d9a',
            'secondary_color'      => '#7a1f3d',
            'color_gold'           => '#c94a6b',
            'color_gold_light'     => '#e87d9a',
            'color_success'        => '#2e7d32',
            'color_countdown'      => '#c94a6b',
            'color_bg_card'        => '#ffedf1',
            'color_border'         => '#c94a6b',
        ],
    ],

    'natural-sage' => [
        'nombre' => 'Natural Sage',
        'desc'   => 'Verde · Orgánico · Salud',
        'paleta' => [
            'background_color'     => '#f4f7f4',
            'text_color'           => '#1a2e22',
            'primary_color'        => '#2d6a4f',
            'accent_color'         => '#52b788',
            'secondary_color'      => '#1b4332',
            'color_gold'           => '#2d6a4f',
            'color_gold_light'     => '#52b788',
            'color_success'        => '#1b4332',
            'color_countdown'      => '#2d6a4f',
            'color_bg_card'        => '#eaf2ec',
            'color_border'         => '#2d6a4f',
        ],
    ],

    'obsidian' => [
        'nombre' => 'Obsidian',
        'desc'   => 'Negro · Plata · Ultra premium',
        'paleta' => [
            'background_color'     => '#050505',
            'text_color'           => '#f0f0f0',
            'primary_color'        => '#b0b0b0',
            'accent_color'         => '#e0e0e0',
            'secondary_color'      => '#606060',
            'color_gold'           => '#b0b0b0',
            'color_gold_light'     => '#e0e0e0',
            'color_success'        => '#6acd8e',
            'color_countdown'      => '#e0e0e0',
            'color_bg_card'        => '#161616',
            'color_border'         => '#b0b0b0',
        ],
    ],

    'blanc-luxe' => [
        'nombre' => 'Blanc Luxe',
        'desc'   => 'Blanco · Oro · Alta elegancia',
        'paleta' => [
            'background_color'     => '#fff8f6',
            'text_color'           => '#4a2535',
            'primary_color'        => '#c4687a',
            'accent_color'         => '#e8a4b8',
            'secondary_color'      => '#a04060',
            'color_gold'           => '#c4687a',
            'color_gold_light'     => '#e8a4b8',
            'color_success'        => '#3a7c5c',
            'color_countdown'      => '#c4687a',
            'color_bg_card'        => '#fdf0ee',
            'color_border'         => '#c4687a',
        ],
    ],

    'midnight-amber' => [
        'nombre' => 'Midnight Amber',
        'desc'   => 'Azul noche · Ámbar · Tarjetas',
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
];
