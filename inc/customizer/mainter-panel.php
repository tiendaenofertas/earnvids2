<?php
// inc/customizer/mainter-panel.php

function mi_tema_register_main_panel( $wp_customize ) {
    
    // Registramos el PANEL PRINCIPAL
    $wp_customize->add_panel( 'mainter_options_panel', array(
        'title'       => __( 'Mainter Opciones', 'mi_tema' ),
        'description' => __( 'Panel de control central para la personalización del tema.', 'mi_tema' ),
        'priority'    => 10, // Aparecerá arriba del todo
    ));
}
add_action( 'customize_register', 'mi_tema_register_main_panel' );

// Mover "Identidad del Sitio" (Logo y Título) dentro de nuestro Panel
function mi_tema_move_identity( $wp_customize ) {
    $wp_customize->get_section( 'title_tagline' )->panel = 'mainter_options_panel';
    $wp_customize->get_section( 'title_tagline' )->priority = 5; // Ponerlo primero
}
add_action( 'customize_register', 'mi_tema_move_identity' );