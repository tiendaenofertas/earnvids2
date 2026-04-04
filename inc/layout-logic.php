<?php
/**
 * Lógica de Layout y Sidebar
 * Define cuándo mostrar el sidebar y ajusta las clases del body.
 */

/**
 * 1. FUNCIÓN MAESTRA: ¿Debemos mostrar el sidebar?
 * Retorna TRUE si se debe mostrar, FALSE si se debe ocultar.
 */
function mainter_has_sidebar() {
    // Por defecto, SI mostramos sidebar
    $show = true;

    // 1. Home / Portada
    if ( is_front_page() || is_home() ) {
        if ( get_theme_mod( 'layout_hide_home' ) ) $show = false;
    }
    // 2. Entradas Individuales
    elseif ( is_single() ) {
        if ( get_theme_mod( 'layout_hide_single' ) ) $show = false;
    }
    // 3. Páginas
    elseif ( is_page() ) {
        if ( get_theme_mod( 'layout_hide_page' ) ) $show = false;
    }
    // 4. Archivos (Categorías, Tags, Autor)
    elseif ( is_archive() ) {
        if ( get_theme_mod( 'layout_hide_archive' ) ) $show = false;
    }
    // 5. Búsqueda
    elseif ( is_search() ) {
        if ( get_theme_mod( 'layout_hide_search' ) ) $show = false;
    }
    // 6. Error 404
    elseif ( is_404() ) {
        if ( get_theme_mod( 'layout_hide_404' ) ) $show = false;
    }

    return $show;
}

/**
 * 2. FILTRO DE CLASES DEL BODY
 * Agrega la clase 'no-sidebar' si la función de arriba dice false.
 * Esto te permite expandir el contenido con CSS.
 */
function mainter_body_classes_layout( $classes ) {
    if ( ! mainter_has_sidebar() ) {
        $classes[] = 'no-sidebar';       // Clase estándar
        $classes[] = 'full-width-layout'; // Clase explícita para tu CSS
    }
    return $classes;
}
add_filter( 'body_class', 'mainter_body_classes_layout' );