<?php
/**
 * Lógica para los "Lápices de Edición" (Selective Refresh)
 */

function mainter_customize_partials( $wp_customize ) {

    // 1. LÁPIZ PARA EL LOGO / TÍTULO
    $wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
    $wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

    if ( isset( $wp_customize->selective_refresh ) ) {
        
        // Partial del Logo/Título
        $wp_customize->selective_refresh->add_partial( 'blogname', array(
            'selector'        => '.site-branding',
            'render_callback' => 'mainter_customize_partial_blogname',
        ) );

        // Partial del Menú Principal
        // Esto pone el lápiz sobre el menú
        $wp_customize->selective_refresh->add_partial( 'primary_menu_partial', array(
            'selector'        => '#site-navigation', // El ID de tu <nav>
            'settings'        => array( 'nav_menu_locations[menu-1]' ),
        ) );
    }
}
add_action( 'customize_register', 'mainter_customize_partials' );

// Callback para renderizar el título si no hay logo
function mainter_customize_partial_blogname() {
    bloginfo( 'name' );
}