<?php
/**
 * Inicializador de Widgets
 * Carga y registra todos los widgets personalizados del tema.
 */

// 1. Incluir los archivos de las clases
require_once get_template_directory() . '/inc/widgets/widget/popular-posts.php';
// 1. Incluir los archivos de las clases
require_once get_template_directory() . '/inc/widgets/widget/footer-links.php';
require_once get_template_directory() . '/inc/widgets/widget/copyright.php';

// 2. Registrar los widgets en WordPress
function mainter_register_custom_widgets() {
    
    register_widget( 'Mainter_Popular_Posts_Widget' );
    

}
add_action( 'widgets_init', 'mainter_register_custom_widgets' );


function mainter_register_bottom_widgets() {
    
    // 1. Zona Izquierda (Para Copyright)
    register_sidebar( array(
        'name'          => 'Footer Inferior (Izquierda)',
        'id'            => 'footer-bottom-left',
        'description'   => 'Arrastra aquí el widget de Copyright.',
        'before_widget' => '<div id="%1$s" class="footer-copyright-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="screen-reader-text">',
        'after_title'   => '</h4>',
    ) );

    // 2. Zona Derecha (Para Enlaces/Iconos) - (Ya la tenías, asegúrate de mantenerla)
    register_sidebar( array(
        'name'          => 'Footer Inferior (Derecha)',
        'id'            => 'footer-bottom-right',
        'description'   => 'Arrastra aquí el widget de Enlaces/Iconos.',
        'before_widget' => '<div id="%1$s" class="footer-inline-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="screen-reader-text">',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'mainter_register_bottom_widgets' );