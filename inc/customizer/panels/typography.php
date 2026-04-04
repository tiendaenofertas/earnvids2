<?php
/**
 * Panel de Tipografía Completo
 */

function mainter_customize_typography( $wp_customize ) {

    // SECCIÓN
    $wp_customize->add_section( 'mainter_typography_section', array(
        'title'    => __( 'Tipografía', 'mainter' ),
        'priority' => 21, // Al principio
        'panel'    => 'mainter_options_panel',
    ));

    // === A. FAMILIAS DE FUENTE ===
    
    // 1. Fuente Encabezados
    $wp_customize->add_setting( 'typo_heading_font', array( 
        'default' => 'Inter', 
        'sanitize_callback' => 'sanitize_key' // Guarda el valor simple
    ));
    
    $wp_customize->add_control( 'typo_heading_font', array(
        'label'   => 'Fuente Encabezados (H1-H6)',
        'section' => 'mainter_typography_section',
        'type'    => 'select',
        // ESTO ES LO IMPORTANTE:
        'choices' => mainter_get_font_choices(), 
    ));

    // 2. Fuente Texto Base
    $wp_customize->add_setting( 'typo_body_font', array( 'default' => 'Inter', 'sanitize_callback' => 'sanitize_key' ));
    $wp_customize->add_control( 'typo_body_font', array(
        'label'   => 'Fuente Texto (Body)',
        'section' => 'mainter_typography_section',
        'type'    => 'select',
        'choices' => mainter_get_font_choices(),
    ));

    // === B. TAMAÑOS (H1 - H4) ===
    
    // Helper para crear controles rápido
    $sizes = array(
        'h1' => array( 'label' => 'Tamaño Encabezado H1', 'default' => 38 ),
        'h2' => array( 'label' => 'Tamaño Encabezado H2', 'default' => 32 ),
        'h3' => array( 'label' => 'Tamaño Encabezado H3', 'default' => 28 ),
        'h4' => array( 'label' => 'Tamaño Encabezado H4', 'default' => 23 ),
        'body' => array( 'label' => 'Tamaño Texto Base', 'default' => 16 ),
        // Los extras que pediste en la imagen
        'listing' => array( 'label' => 'Tamaño Listado (Entradas)', 'default' => 18 ),
        'listing_feat' => array( 'label' => 'Tamaño Destacado', 'default' => 25 ),
    );

    foreach ( $sizes as $id => $args ) {
        $setting_id = 'typo_size_' . $id;
        
        $wp_customize->add_setting( $setting_id, array(
            'default'           => $args['default'],
            'sanitize_callback' => 'absint',
        ));

        $wp_customize->add_control( $setting_id, array(
            'label'       => $args['label'],
            'section'     => 'mainter_typography_section',
            'type'        => 'number',
            'input_attrs' => array( 'min' => 10, 'max' => 100, 'step' => 1 ),
        ));
    }
}
add_action( 'customize_register', 'mainter_customize_typography' );