<?php
/**
 * Motor de Anuncios: Simple y Directo
 */

// --- 1. FUNCIÓN MAESTRA DE ESTADO ---
function mainter_is_ads_on() {
    // Si devuelve false, 0 o null, es que está apagado.
    return (bool) get_theme_mod( 'enable_ads_system', true );
}

// --- 2. INYECCIÓN EN CONTENIDO (Solo Artículos y Páginas) ---
function mainter_inject_ads_in_content( $content ) {

    // A. CORTE GLOBAL: Si está apagado, devolvemos contenido limpio.
    if ( ! mainter_is_ads_on() ) {
        return $content;
    }

    // B. FILTRO DE LUGAR: Solo en Entradas (post) y Páginas (page) individuales.
    // Si es la Home, Feed, Admin o Categoría -> NO TOCAR.
    if ( ! is_singular() || is_admin() || is_feed() ) {
        return $content;
    }

    // C. OBTENER CÓDIGOS
    $ad_before = get_theme_mod( 'ad_before_post' );
    $ad_inside = get_theme_mod( 'ad_inside_post' );
    $ad_after  = get_theme_mod( 'ad_after_post' );

    // D. PREPARAR HTML (Si hay código, lo envolvemos)
    $html_before = !empty($ad_before) ? '<div class="mainter-ad ad-before">' . $ad_before . '</div>' : '';
    $html_after  = !empty($ad_after)  ? '<div class="mainter-ad ad-after">' . $ad_after . '</div>' : '';
    
    // E. INYECTAR EN MEDIO (Párrafo 3)
    if ( !empty($ad_inside) ) {
        $ad_inside_html = '<div class="mainter-ad ad-inside">' . $ad_inside . '</div>';
        $paragraphs = explode( '</p>', $content );
        
        if ( count( $paragraphs ) > 3 ) {
            array_splice( $paragraphs, 3, 0, $ad_inside_html );
            $content = implode( '</p>', $paragraphs );
        }
    }

    // F. UNIR TODO
    return $html_before . $content . $html_after;
}
add_filter( 'the_content', 'mainter_inject_ads_in_content', 20 );


// --- 3. ZONAS MANUALES (Header, Sidebar, Footer) ---
function mainter_render_ad( $location ) {

    // A. CORTE GLOBAL: Si está apagado, no imprimir NADA.
    if ( ! mainter_is_ads_on() ) {
        return;
    }

    // B. IMPRIMIR SI HAY CÓDIGO
    $ad_code = get_theme_mod( $location );
    
    if ( ! empty( $ad_code ) ) {
        echo '<div class="mainter-ad ad-' . esc_attr( str_replace('_', '-', $location) ) . '">';
        echo $ad_code; // Imprimir script crudo
        echo '</div>';
    }
}