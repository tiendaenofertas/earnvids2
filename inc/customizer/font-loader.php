<?php
/**
 * Cargador de Fuentes (Bunny Fonts API + Local)
 * CORREGIDO: Claves en minúscula para persistencia en Customizer.
 */

// 1. LISTA DE FUENTES
// La clave (izquierda) debe ser el ID técnico (minúsculas/guiones).
// El valor (derecha) es lo que lee el humano.
function mainter_get_font_choices() {
    return array(
        'system'            => 'System Sans (Nativa)',
        'local'             => 'Fuente Local (style.css)',
        
        // Fuentes de Bunny (Claves corregidas)
        'roboto'            => 'Roboto',
        'open-sans'         => 'Open Sans',
        'montserrat'        => 'Montserrat',
        'poppins'           => 'Poppins',
        'inter'             => 'Inter',
        'lato'              => 'Lato',
        'oswald'            => 'Oswald',
        'merriweather'      => 'Merriweather',
        'playfair-display'  => 'Playfair Display',
        'libre-franklin'    => 'Libre Franklin',
        'nunito'            => 'Nunito',
        'raleway'           => 'Raleway',
        'rubik'             => 'Rubik',
        'work-sans'         => 'Work Sans',
        'quicksand'         => 'Quicksand',
        'fira-sans'         => 'Fira Sans',
        'pt-sans'           => 'PT Sans',
        'lora'              => 'Lora'
    );
}

// 2. FUNCIÓN DE CARGA (Bunny Fonts)
function mainter_enqueue_bunny_fonts() {
    
    // Obtenemos las opciones (que ahora vendrán como 'open-sans', 'roboto', etc.)
    $heading_font = get_theme_mod( 'typo_heading_font', 'inter' );
    $body_font    = get_theme_mod( 'typo_body_font', 'inter' );

    $fonts_raw = array();

    // Filtramos 'system' y 'local'
    if ( $heading_font && $heading_font !== 'system' && $heading_font !== 'local' ) {
        $fonts_raw[] = $heading_font;
    }
    if ( $body_font && $body_font !== 'system' && $body_font !== 'local' && $body_font !== $heading_font ) {
        $fonts_raw[] = $body_font;
    }

    if ( empty( $fonts_raw ) ) return;

    $font_args = array();

    foreach ( $fonts_raw as $font_key ) {
        // La clave ya viene limpia (ej: 'open-sans'), pero por seguridad:
        // 1. Limpiamos cualquier residuo de números viejos
        $clean_key = preg_replace( '/[0-9]+/', '', $font_key ); 
        $clean_key = trim( $clean_key );
        $clean_key = strtolower( $clean_key ); // Aseguramos minúsculas
        
        // 2. Pedimos los pesos a Bunny
        $font_args[] = "{$clean_key}:300,500,700";
    }

    $query_string = implode( '|', $font_args );
    $request_url = "https://fonts.bunny.net/css?family={$query_string}&display=swap";

    wp_enqueue_style( 'mainter-bunny-fonts', $request_url, array(), null );
    
    add_action('wp_head', function() {
        echo '<link rel="preconnect" href="https://fonts.bunny.net">';
    }, 5);
}
add_action( 'wp_enqueue_scripts', 'mainter_enqueue_bunny_fonts', 20 );