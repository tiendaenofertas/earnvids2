<?php
function mainter_customize_footer( $wp_customize ) {

    // Sección Footer
    $wp_customize->add_section( 'mainter_footer_section', array(
        'title'    => 'Footer',
        'priority' => 130,
        'panel'    => 'mainter_options_panel',
    ));
/*
    // --- A. FOOTER SUPERIOR (Widgets) ---
    $wp_customize->add_setting( 'enable_upper_footer', array(
        'default' => true, 'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));
    $wp_customize->add_control( 'enable_upper_footer', array(
        'label'    => 'Activar Footer Superior (Widgets)',
        'section'  => 'mainter_footer_section',
        'type'     => 'checkbox',
    ));

    */

    // --- B. FOOTER INFERIOR (Copyright) ---
    $wp_customize->add_setting( 'enable_lower_footer', array(
        'default' => true, 'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));
    $wp_customize->add_control( 'enable_lower_footer', array(
        'label'    => 'Activar Footer Inferior (Copyright)',
        'section'  => 'mainter_footer_section',
        'type'     => 'checkbox',
    ));

 

    
}
add_action( 'customize_register', 'mainter_customize_footer' );