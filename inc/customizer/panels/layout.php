<?php
/**
 * Panel de Disposición (Layout) y Sidebar
 * Permite ocultar el sidebar en secciones específicas.
 */

function mainter_customize_layout( $wp_customize ) {

    // 1. Crear Sección "Diseño y Sidebar"
    $wp_customize->add_section( 'mainter_layout_section', array(
        'title'    => __( 'Sidebar', 'mainter' ),
        'priority' => 25, // Justo después de los colores
        'panel'    => 'mainter_options_panel', // Usamos tu panel existente
    ));

    // --- OPCIONES DE VISIBILIDAD ---
    
    // Array de configuraciones para hacerlo más limpio
    $sidebar_options = array(
        'layout_hide_home'   => 'Ocultar en Portada (Home)',
        'layout_hide_single' => 'Ocultar en Entradas (Single)',
        'layout_hide_page'   => 'Ocultar en Páginas',
        'layout_hide_archive'=> 'Ocultar en Archivos/Categorías',
        'layout_hide_search' => 'Ocultar en Búsqueda',
        'layout_hide_404'    => 'Ocultar en Error 404',
    );

    foreach ( $sidebar_options as $id => $label ) {
        $wp_customize->add_setting( $id, array(
            'default'           => false,
            'sanitize_callback' => 'mainter_sanitize_checkbox',
        ));

        $wp_customize->add_control( $id, array(
            'label'       => $label,
            'section'     => 'mainter_layout_section',
            'type'        => 'checkbox',
        ));
    }
}
add_action( 'customize_register', 'mainter_customize_layout' );

// Sanitización para checkbox (si no la tenías definida antes)
if ( ! function_exists( 'mainter_sanitize_checkbox' ) ) {
    function mainter_sanitize_checkbox( $checked ) {
        return ( ( isset( $checked ) && true == $checked ) ? true : false );
    }
}