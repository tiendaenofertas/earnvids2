<?php
/**
 * Panel de Diseño del Grid del Blog
 * Permite elegir entre estilos de tarjeta Basic y Overlay.
 */

function mainter_customize_blog_grid( $wp_customize ) {

    // 1. SECCIÓN "GRID DEL BLOG"
    $wp_customize->add_section( 'mainter_blog_grid_section', array(
        'title'    => __( 'Card', 'mainter' ),
        'priority' => 23, // Después de Diseño y antes de Layout
        'panel'    => 'mainter_options_panel',
    ));

    // --- A. ESTILO DE TARJETA (Radio Button) ---
    $wp_customize->add_setting( 'blog_card_style', array(
        'default'           => 'basic', // 'basic' o 'overlay'
        'sanitize_callback' => 'mainter_sanitize_card_style',
        // 'transport'      => 'postMessage', // (Opcional para futuro JS)
    ));

    $wp_customize->add_control( 'blog_card_style', array(
        'label'       => 'Estilo de Tarjeta',
        'description' => 'Elige cómo se ven las entradas en la portada y archivos.',
        'section'     => 'mainter_blog_grid_section',
        'type'        => 'radio',
        'choices'     => array(
            'basic'   => 'Básico (Imagen + Texto abajo)',
            'overlay' => 'Overlay (Texto sobre imagen)',
        ),
    ));

    // --- B. MOSTRAR FECHA ---
    $wp_customize->add_setting( 'blog_show_date', array(
        'default'           => true,
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'blog_show_date', array(
        'label'       => 'Mostrar Fecha',
        'section'     => 'mainter_blog_grid_section',
        'type'        => 'checkbox',
    ));

     // --- C. MOSTRAR EXTRACTO ---
     $wp_customize->add_setting( 'blog_show_excerpt', array(
        'default'           => true,
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'blog_show_excerpt', array(
        'label'       => 'Mostrar Descripción (Extracto)',
        'section'     => 'mainter_blog_grid_section',
        'type'        => 'checkbox',
    ));
}
add_action( 'customize_register', 'mainter_customize_blog_grid' );

// Sanitización para el selector de estilo
if ( ! function_exists( 'mainter_sanitize_card_style' ) ) {
    function mainter_sanitize_card_style( $input ) {
        $valid = array( 'basic', 'overlay' );
        return ( in_array( $input, $valid ) ? $input : 'basic' );
    }
}
// (Asumo que 'mainter_sanitize_checkbox' ya la tienes en layout.php)