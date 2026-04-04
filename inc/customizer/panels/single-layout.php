<?php
/**
 * Panel: Diseño de Entradas (Single)
 */
function mainter_customize_single_layout( $wp_customize ) {

    // Crear Sección
    $wp_customize->add_section( 'mainter_single_layout_section', array(
        'title'    => __( 'Entradas', 'mainter' ),
        'priority' => 25,
        'panel'    => 'mainter_options_panel',
    ));



    // Crear Checkbox "Activar Hero"
    $wp_customize->add_setting( 'single_hero_enable', array(
        'default'           => false,
        'sanitize_callback' => 'mainter_sanitize_checkbox', // Asegúrate de tener esta función en tu theme
    ));

    $wp_customize->add_control( 'single_hero_enable', array(
        'label'       => 'Activar Hero Header (Imagen Grande)',
        'description' => 'Muestra el título y la imagen destacada en formato gigante al inicio.',
        'section'     => 'mainter_single_layout_section',
        'type'        => 'checkbox',
    ));

    // 1. Activar Botones
    $wp_customize->add_setting( 'enable_share_buttons', array(
        'default'           => true, // Activo por defecto
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'enable_share_buttons', array(
        'label'       => 'Mostrar Botones de Compartir',
        'description' => 'Añade botones para compartir (Facebook, X, WhatsApp) al final del post.',
        'section'     => 'mainter_single_layout_section', // El mismo panel de antes
        'type'        => 'checkbox',
    ));

    // 2. Texto del encabezado de compartir
    $wp_customize->add_setting( 'share_buttons_title', array(
        'default'           => 'Comparte este artículo:',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control( 'share_buttons_title', array(
        'label'       => 'Título de la sección compartir',
        'section'     => 'mainter_single_layout_section',
        'type'        => 'text',
    ));


    // 1. Activar Relacionados
$wp_customize->add_setting( 'enable_related_posts', array(
    'default'           => true,
    'sanitize_callback' => 'mainter_sanitize_checkbox',
));

$wp_customize->add_control( 'enable_related_posts', array(
    'label'       => 'Mostrar Entradas Relacionadas',
    'section'     => 'mainter_single_layout_section',
    'type'        => 'checkbox',
));

// 2. Título
$wp_customize->add_setting( 'related_posts_title', array(
    'default'           => 'Te podría interesar',
    'sanitize_callback' => 'sanitize_text_field',
));

$wp_customize->add_control( 'related_posts_title', array(
    'label'       => 'Título Sección Relacionados',
    'section'     => 'mainter_single_layout_section',
    'type'        => 'text',
));


/* --- 👤 CAJA DE AUTOR --- */
    
    // 1. Activar Caja
    $wp_customize->add_setting( 'enable_author_box', array(
        'default'           => true,
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'enable_author_box', array(
        'label'       => 'Mostrar Caja de Autor',
        'description' => 'Muestra la biografía y redes al final del post.',
        'section'     => 'mainter_single_layout_section',
        'type'        => 'checkbox',
    ));

    // 2. Título de la caja
    $wp_customize->add_setting( 'author_box_title', array(
        'default'           => 'Sobre el autor',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control( 'author_box_title', array(
        'label'       => 'Título Caja de Autor',
        'section'     => 'mainter_single_layout_section',
        'type'        => 'text',
    ));

    /* --- 🍞 MIGAS DE PAN (BREADCRUMBS) --- */
    
    $wp_customize->add_setting( 'enable_single_breadcrumbs', array(
        'default'           => true,
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'enable_single_breadcrumbs', array(
        'label'       => 'Mostrar Migas de Pan',
        'description' => 'Muestra la ruta de navegación antes del título (Inicio > Categoría > Título).',
        'section'     => 'mainter_single_layout_section',
        'type'        => 'checkbox',
    ));

    // Opción: Texto de Inicio (Agrega esto debajo)
    $wp_customize->add_setting( 'breadcrumbs_home_text', array(
        'default'           => 'Inicio',
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control( 'breadcrumbs_home_text', array(
        'label'       => 'Texto del enlace Home',
        'section'     => 'mainter_single_layout_section', // Usamos tu misma sección
        'type'        => 'text',
    ));

     /* --- 📌 SIDEBAR STICKY --- */
    
    $wp_customize->add_setting( 'enable_sticky_sidebar', array(
        'default'           => false,
        'sanitize_callback' => 'mainter_sanitize_checkbox',
    ));

    $wp_customize->add_control( 'enable_sticky_sidebar', array(
        'label'       => 'Fijar Sidebar (Sticky)',
        'description' => 'Hace que la barra lateral se quede fija al hacer scroll.',
        'section'     => 'mainter_single_layout_section',
        'type'        => 'checkbox',
    ));


    // Separador visual (Opcional, pero ayuda a organizar)
    $wp_customize->add_setting( 'toc_separator', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    $wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'toc_separator', array(
        'section'  => 'mainter_single_layout_section',
        'type'     => 'hidden',
        'label'    => '──────────────', 
        'description' => '<strong>TABLA DE CONTENIDOS</strong>',
    ) ) );

    // A. Activar / Desactivar
    $wp_customize->add_setting( 'enable_toc', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control( 'enable_toc', array(
        'label'    => 'Activar Tabla de Contenidos',
        'description' => 'Se inserta automáticamente después del primer párrafo.',
        'section'  => 'mainter_single_layout_section', // <--- AQUÍ ESTABA EL ERROR
        'type'     => 'checkbox',
    ));

    // B. Título de la Tabla
    $wp_customize->add_setting( 'toc_title', array(
        'default'           => 'Índice de contenidos',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control( 'toc_title', array(
        'label'    => 'Título de la caja',
        'section'  => 'mainter_single_layout_section', // <--- CORREGIDO
        'type'     => 'text',
    ));
    
    // C. Abrir/Cerrar por defecto
    $wp_customize->add_setting( 'toc_open_default', array(
        'default'           => true,
        'sanitize_callback' => 'absint',
    ));
    $wp_customize->add_control( 'toc_open_default', array(
        'label'    => 'Desplegar por defecto',
        'section'  => 'mainter_single_layout_section', // <--- CORREGIDO
        'type'     => 'checkbox',
    ));

}


add_action( 'customize_register', 'mainter_customize_single_layout' );