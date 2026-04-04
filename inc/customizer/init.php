<?php
// inc/customizer/init.php

// 1. Cargar el Panel Principal (El contenedor)
require get_template_directory() . '/inc/customizer/mainter-panel.php';

// 2. Cargar las Secciones (Hijas del panel)
require get_template_directory() . '/inc/customizer/panels/colors.php';
require get_template_directory() . '/inc/customizer/panels/ads.php';
require get_template_directory() . '/inc/customizer/panels/layout.php';
require get_template_directory() . '/inc/customizer/panels/design.php';
require get_template_directory() . '/inc/customizer/panels/blog-grid.php';
require get_template_directory() . '/inc/customizer/panels/dark-mode.php';
require get_template_directory() . '/inc/customizer/panels/typography.php';
require get_template_directory() . '/inc/customizer/panels/hero.php';
require get_template_directory() . '/inc/customizer/panels/single-layout.php';
require get_template_directory() . '/inc/customizer/panels/search.php';
require get_template_directory() . '/inc/customizer/panels/footer.php';


// 3. Cargar salida CSS
require get_template_directory() . '/inc/customizer/frontend-css.php';

// Configuración extra si la necesitas
function mi_tema_customizer_clean( $wp_customize ) {
    // Opcional: Esto mueve la opción de "Identidad del sitio" (Logo) dentro de tu panel si quisieras
    // $wp_customize->get_section('title_tagline')->panel = 'mainter_options_panel';
}
add_action( 'customize_register', 'mi_tema_customizer_clean' );