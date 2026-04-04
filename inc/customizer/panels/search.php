<?php
/**
 * Panel: Buscador (Search)
 */
function mainter_customize_search( $wp_customize ) {

    // 1. Crear Sección Exclusiva "Buscador"
    $wp_customize->add_section( 'mainter_search_section', array(
        'title'    => 'Buscador',
        'priority' => 40,
        'panel'    => 'mainter_options_panel', // O 'mainter_header_section' si prefieres agruparlo
    ));

    // 2. Opción: Activar/Desactivar
    $wp_customize->add_setting( 'enable_header_search', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
    ));

    $wp_customize->add_control( 'enable_header_search', array(
        'label'       => 'Activar Icono de Búsqueda',
        'description' => 'Muestra la lupa en el menú y activa la funcionalidad de búsqueda.',
        'section'     => 'mainter_search_section',
        'type'        => 'checkbox',
    ));
}
add_action( 'customize_register', 'mainter_customize_search' );