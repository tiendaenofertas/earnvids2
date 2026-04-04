<?php
/**
 * SOLUCIÓN ESPECÍFICA PARA EL COLOR DEL HEADER
 * Este archivo aísla la lógica del color del header para evitar conflictos.
 */

// 1. Registrar la opción (Si ya la tienes en otro lado, esto asegura que exista)
function mainter_header_specific_option( $wp_customize ) {
    
    // Panel existente o Sección nueva si prefieres aislarlo visualmente
    $wp_customize->add_section( 'mainter_header_color_fix', array(
        'title'    => '🎨 Color del Header (Fix)',
        'priority' => 1, // Lo ponemos de primero para que lo veas rápido
        'panel'    => 'mainter_options_panel',
    ));

    // Opción de Color de Texto
    $wp_customize->add_setting( 'header_text_fix', array(
        'default'           => '#333333',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh', // Forzamos recarga para asegurar el cambio
    ));

    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'header_text_fix', array(
        'label'       => 'Color de Texto y Menú',
        'description' => 'Este control tiene prioridad absoluta sobre cualquier otro estilo.',
        'section'     => 'mainter_header_color_fix',
    )));
}
add_action( 'customize_register', 'mainter_header_specific_option' );

// 2. Función para obtener el color (Helper)
function mainter_get_header_color() {
    $color = get_theme_mod( 'header_text_fix', '#333333' );
    return $color;
}