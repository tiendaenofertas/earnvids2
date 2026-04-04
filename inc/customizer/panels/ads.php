<?php
/**
 * Panel: Publicidad (Versión Limpia y Estable)
 */
function mainter_customize_ads( $wp_customize ) {

    // 1. Sección
    $wp_customize->add_section( 'mainter_ads_section', array(
        'title'    => 'Publicidad',
        'priority' => 20,
        'panel'    => 'mainter_options_panel',
    ));

    // 2. INTERRUPTOR MAESTRO (ON/OFF TOTAL)
    $wp_customize->add_setting( 'enable_ads_system', array(
        'default'           => true,
        'sanitize_callback' => 'mainter_sanitize_checkbox', // Simple true/false
    ));

    $wp_customize->add_control( 'enable_ads_system', array(
        'label'       => '🔴 ESTADO GLOBAL DE PUBLICIDAD',
        'description' => 'Activa o desactiva todos los anuncios. ⚠️ NOTA IMPORTANTE: Si usas plugins de caché (LiteSpeed, WP Rocket) y los cambios no se ven al instante, purga la caché manualmente..',
        'section'     => 'mainter_ads_section',
        'type'        => 'checkbox',
    ));

    // 3. CAMPOS DE CÓDIGO
    $ad_spots = array(
        'ad_header'        => 'Header (Cabecera Global)',
        'ad_sidebar'       => 'Barra Lateral (Sidebar)',
        'ad_footer'        => 'Antes del Footer (Global)',
        'ad_before_post'   => 'Dentro del Artículo: Arriba (Antes del texto)',
        'ad_inside_post'   => 'Dentro del Artículo: En Medio (Párrafo 3)',
        'ad_after_post'    => 'Dentro del Artículo: Abajo (Final)',
    );

    foreach ( $ad_spots as $id => $label ) {
        // Setting (Permite Scripts)
        $wp_customize->add_setting( $id, array(
            'default'           => '',
            'sanitize_callback' => 'mainter_sanitize_raw_ads', // Tu función de seguridad
        ));

        // Control
        $wp_customize->add_control( $id, array(
            'label'       => $label,
            'section'     => 'mainter_ads_section',
            'settings'    => $id,
            'type'        => 'textarea',
            // Ocultamos visualmente si el global está apagado para limpiar la vista
            'active_callback' => 'mainter_ads_are_enabled', 
        ));
    }
}
add_action( 'customize_register', 'mainter_customize_ads' );

// --- FUNCIONES DE AYUDA ---

// 1. Verifica si está activo para mostrar/ocultar campos
function mainter_ads_are_enabled() {
    return (bool) get_theme_mod( 'enable_ads_system', true );
}

// 2. Sanitizado simple para checkbox
function mainter_sanitize_checkbox( $checked ) {
    return ( ( isset( $checked ) && true == $checked ) ? true : false );
}

// 3. Sanitizado RAW para scripts (CRUCIAL PARA QUE NO SE BORREN)
function mainter_sanitize_raw_ads( $input ) {
    if ( current_user_can( 'edit_theme_options' ) ) {
        return $input;
    }
    return wp_kses_post( $input );
}