<?php
/**
 * Panel Sección Hero (Portada) - Actualizado V2
 */

function mainter_customize_hero( $wp_customize ) {

    // Obtener Categorías
    $cats = get_categories( array( 'hide_empty' => false ) );
    $cats_choices = array( '0' => 'Todas las Categorías (Mix)' );
    foreach ( $cats as $c ) { $cats_choices[ $c->term_id ] = $c->name; }

    // Sección
    $wp_customize->add_section( 'mainter_hero_section', array(
        'title'    => __( 'Portada (Destacados)', 'mainter' ),
        'priority' => 20,
        'panel'    => 'mainter_options_panel',
    ));

    // A. Activar
    $wp_customize->add_setting( 'hero_enable', array( 'default' => false, 'sanitize_callback' => 'mainter_sanitize_checkbox' ));
    $wp_customize->add_control( 'hero_enable', array( 'label' => 'Activar en Portada', 'section' => 'mainter_hero_section', 'type' => 'checkbox' ));

    // B. Título
    $wp_customize->add_setting( 'hero_title', array( 'default' => 'Destacados', 'sanitize_callback' => 'sanitize_text_field' ));
    $wp_customize->add_control( 'hero_title', array( 'label' => 'Título Sección', 'section' => 'mainter_hero_section', 'type' => 'text' ));

    // C. Categoría
    $wp_customize->add_setting( 'hero_category', array( 'default' => '0', 'sanitize_callback' => 'absint' ));
    $wp_customize->add_control( 'hero_category', array( 'label' => 'Fuente de contenido', 'section' => 'mainter_hero_section', 'type' => 'select', 'choices' => $cats_choices ));

    // --- NUEVOS CONTROLES ---

    // D. Ocultar Badge "Destacado"
    $wp_customize->add_setting( 'hero_hide_badge', array( 'default' => false, 'sanitize_callback' => 'mainter_sanitize_checkbox' ));
    $wp_customize->add_control( 'hero_hide_badge', array(
        'label'       => 'Ocultar etiqueta "Destacado"',
        'description' => 'Quita el badge rojo del artículo principal.',
        'section'     => 'mainter_hero_section',
        'type'        => 'checkbox',
    ));

    // E. Color de Acento Hero
    $wp_customize->add_setting( 'hero_accent_color', array( 'default' => '#e25822', 'sanitize_callback' => 'sanitize_hex_color' ));
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'hero_accent_color', array(
        'label'       => 'Color de Acento Hero',
        'description' => 'Color para el icono de fuego y los badges.',
        'section'     => 'mainter_hero_section',
    )));
}
add_action( 'customize_register', 'mainter_customize_hero' );