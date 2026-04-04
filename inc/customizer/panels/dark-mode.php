<?php
/**
 * Panel de Modo Oscuro
 * Gestión de colores para el tema dark y visibilidad del botón.
 */

function mainter_customize_dark_mode( $wp_customize ) {

    $wp_customize->add_section( 'mainter_dark_mode_section', array(
        'title'    => __( 'Modo Oscuro', 'mainter' ),
        'priority' => 24, // Cerca de Colores
        'panel'    => 'mainter_options_panel',
    ));

    // 1. ACTIVAR BOTÓN
    $wp_customize->add_setting( 'enable_dark_mode_btn', array(
        'default'           => true,
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'enable_dark_mode_btn', array(
        'label'       => 'Mostrar Botón de Cambio (Sol/Luna)',
        'section'     => 'mainter_dark_mode_section',
        'type'        => 'checkbox',
    ));

    // 2. COLORES MODO OSCURO
    // Fondo Body
    $wp_customize->add_setting( 'dm_bg_body', array( 'default' => '#111827', 'sanitize_callback' => 'sanitize_hex_color' ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dm_bg_body', array(
        'label' => 'Fondo Principal (Oscuro)', 'section' => 'mainter_dark_mode_section',
    )));

    // Texto Body
    $wp_customize->add_setting( 'dm_text_main', array( 'default' => '#f3f4f6', 'sanitize_callback' => 'sanitize_hex_color' ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dm_text_main', array(
        'label' => 'Texto Principal (Oscuro)', 'section' => 'mainter_dark_mode_section',
    )));

    // Fondo Header/Footer
    $wp_customize->add_setting( 'dm_bg_header', array( 'default' => '#1f2937', 'sanitize_callback' => 'sanitize_hex_color' ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dm_bg_header', array(
        'label' => 'Fondo Header/Footer (Oscuro)', 'section' => 'mainter_dark_mode_section',
    )));

    // Color de Tarjetas
    $wp_customize->add_setting( 'dm_bg_card', array( 'default' => '#1f2937', 'sanitize_callback' => 'sanitize_hex_color' ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dm_bg_card', array(
        'label' => 'Fondo de Tarjetas (Oscuro)', 'section' => 'mainter_dark_mode_section',
    )));
}
add_action( 'customize_register', 'mainter_customize_dark_mode' );