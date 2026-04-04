<?php
/**
 * Panel de Colores - Versión Completa y Estable
 */

// 1. Sanitizador Seguro (Evita errores de validación)
function mi_tema_sanitize_hex( $color ) {
    if ( empty( $color ) ) return '';
    return sanitize_hex_color( $color );
}

function mi_tema_customize_colors( $wp_customize ) {

    // --- LIMPIEZA: Eliminamos la sección "Colors" por defecto de WordPress ---
    // Esto quita la opción duplicada del menú principal.
    $wp_customize->remove_section( 'colors' );
    $wp_customize->remove_section( 'header_image' ); // Opcional: si tampoco quieres esta suelta


    // --- NUESTRA SECCIÓN PERSONALIZADA ---
    $wp_customize->add_section( 'mi_tema_colors_section', array(
        'title'    => __( 'Apariencia', 'mi_tema' ),
        'priority' => 10,
        'panel'    => 'mainter_options_panel', // Todo dentro de tu panel
    ));

    // Definimos todas las opciones que tenías antes
    $opciones = array(
        // ID Setting        // Default      // Label
        'color_brand'     => ['#4f46e5', 'Color Principal (Marca)'],
        'color_text'      => ['#1f2937', 'Color de Texto General'],
        'color_link'      => ['#10b981', 'Color de Enlaces'],
        
        'header_bg'       => ['#ffffff', 'Fondo del Header'],
        'header_text'     => ['#333333', 'Texto del Header'],
        
        'footer_bg'       => ['#111827', 'Fondo del Footer'],
        'footer_text'     => ['#f9fafb', 'Texto del Footer'],
        
        'btn_bg'          => ['#4f46e5', 'Fondo Botones'],
        'btn_text'        => ['#ffffff', 'Texto Botones'],
    );

    foreach ( $opciones as $id => $data ) {
        // Registrar Setting
        $wp_customize->add_setting( $id, array(
            'default'           => $data[0],
            'sanitize_callback' => 'mi_tema_sanitize_hex',
            'transport'         => 'refresh',
        ));

        // Registrar Control
        $wp_customize->add_control( new WP_Customize_Color_Control(
            $wp_customize,
            $id,
            array(
                'label'    => $data[1],
                'section'  => 'mi_tema_colors_section',
                'settings' => $id,
            )
        ));
    }
}
add_action( 'customize_register', 'mi_tema_customize_colors' );