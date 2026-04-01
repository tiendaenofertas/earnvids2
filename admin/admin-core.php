<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Crear el menú en el panel de WordPress
function mainter_seo_add_admin_menu() {
	// 1. Pega aquí el código de tu SVG (Asegúrate de usar comillas simples ' ' por fuera)
    // Consejo: Es recomendable que tu SVG tenga un viewBox cuadrado (ej: 0 0 24 24)
    $svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#95989b" d="m9.727 8.304l2.338 2.338a1.94 1.94 0 0 0 2.735 0l6.586-6.596a.25.25 0 0 1 .34 0l1.057 1.057c.089.09.212.14.34.136q.091.021.183 0a.485.485 0 0 0 .301-.475V.884a.485.485 0 0 0-.485-.485h-3.88a.485.485 0 0 0-.485.3a.47.47 0 0 0 .107.524l1.145 1.106a.233.233 0 0 1 0 .34l-2.91 2.91C14.487-.614 6.152-1.657 2.096 3.7A8.73 8.73 0 0 0 .327 9.187q.003.486.058.97a.97.97 0 0 0 .97.853h.116a.97.97 0 0 0 .854-1.076a7 7 0 0 1 0-.796C2.498 3.87 8.31.763 12.786 3.546a6.85 6.85 0 0 1 2.838 3.526L13.607 9.12a.26.26 0 0 1-.35 0L10.92 6.762a1.94 1.94 0 0 0-2.735 0L.619 14.318a.97.97 0 1 0 1.377 1.367l.563-.562a9.1 9.1 0 0 0 6.567 2.91a8.73 8.73 0 0 0 5.334-1.804a.25.25 0 0 1 .32 0l1.358 1.358a.24.24 0 0 1 .059.252a1.45 1.45 0 0 0 .34 1.513l3.763 3.773c1.004 1.105 2.828.708 3.283-.715a1.94 1.94 0 0 0-.548-2.03l-3.754-3.792a1.48 1.48 0 0 0-1.028-.427q-.25 0-.485.087a.24.24 0 0 1-.252-.058l-1.358-1.358a.243.243 0 0 1 0-.32a8.9 8.9 0 0 0 1.358-2.59a.97.97 0 1 0-1.843-.601a6.86 6.86 0 0 1-6.518 4.723a7.1 7.1 0 0 1-5.18-2.298l5.432-5.442a.243.243 0 0 1 .32 0"/></svg>';

    // 2. Lo convertimos al formato que entiende WordPress
    $icon_base64 = 'data:image/svg+xml;base64,' . base64_encode( $svg_icon );
    add_menu_page(
        'Mainter SEO',              // Título de la página
        'Mainter SEO',              // Título del menú
        'manage_options',           // Capacidad requerida
        'mainter-seo',              // Slug del menú
        'mainter_seo_render_dashboard', // Función que pinta la vista
        $icon_base64,      // Icono (puedes cambiarlo luego)
        60                          // Posición
    );
}
add_action( 'admin_menu', 'mainter_seo_add_admin_menu' );



// 2. Registrar las opciones en la base de datos
function mainter_seo_register_settings() {
    register_setting( 'mainter_seo_group', 'mainter_seo_settings', 'mainter_seo_sanitize_settings' );
}
add_action( 'admin_init', 'mainter_seo_register_settings' );

// 3. Limpiar los datos antes de guardarlos (Seguridad)
function mainter_seo_sanitize_settings( $input ) {
    $clean = [];
    
    // Módulo 1: Inicio
    $clean['home_module_active'] = isset( $input['home_module_active'] ) ? 1 : 0;
    $clean['home_title']         = sanitize_text_field( $input['home_title'] ?? '' );
    $clean['home_desc']          = sanitize_textarea_field( $input['home_desc'] ?? '' );
    
    // Módulo 2: Meta Tags y Social
    $clean['enable_canonical']   = isset( $input['enable_canonical'] ) ? 1 : 0;
    $clean['enable_og']          = isset( $input['enable_og'] ) ? 1 : 0;
    $clean['enable_tw']          = isset( $input['enable_tw'] ) ? 1 : 0;
    $clean['home_og_image']      = sanitize_url( $input['home_og_image'] ?? '' );
    $clean['home_tw_image']      = sanitize_url( $input['home_tw_image'] ?? '' );
    $clean['social_facebook']    = sanitize_url( $input['social_facebook'] ?? '' ); // <-- NUEVO: Facebook Publisher

    // Módulo 3: Robots e Indexación
    $clean['robots_home']        = sanitize_text_field( $input['robots_home'] ?? 'index, follow' );
    $clean['robots_posts']       = sanitize_text_field( $input['robots_posts'] ?? 'index, follow' );
    $clean['robots_pages']       = sanitize_text_field( $input['robots_pages'] ?? 'index, follow' );
    $clean['robots_cats']        = sanitize_text_field( $input['robots_cats'] ?? 'index, follow' );
    $clean['robots_tags']        = sanitize_text_field( $input['robots_tags'] ?? 'noindex, follow' );
    $clean['external_blank']     = isset( $input['external_blank'] ) ? 1 : 0;
    $clean['external_nofollow']  = isset( $input['external_nofollow'] ) ? 1 : 0;

    // --> ¡NUEVO! Interruptor para Max Image Preview
    $clean['robots_max_image']   = isset( $input['robots_max_image'] ) ? 1 : 0;

    // Módulo 4: Datos Estructurados (Schema JSON-LD) - ¡NUEVO!
    $clean['schema_org']         = isset( $input['schema_org'] ) ? 1 : 0;
    $clean['schema_search']      = isset( $input['schema_search'] ) ? 1 : 0;
    $clean['schema_article']     = isset( $input['schema_article'] ) ? 1 : 0;
    $clean['schema_breadcrumbs'] = isset( $input['schema_breadcrumbs'] ) ? 1 : 0;

	// Módulo 5: Sitemap XML (AVANZADO)
    $clean['enable_sitemap']     = isset( $input['enable_sitemap'] ) ? 1 : 0;
    $clean['sitemap_posts']      = isset( $input['sitemap_posts'] ) ? 1 : 0;
    $clean['sitemap_pages']      = isset( $input['sitemap_pages'] ) ? 1 : 0;
    $clean['sitemap_cats']       = isset( $input['sitemap_cats'] ) ? 1 : 0;
    $clean['sitemap_tags']       = isset( $input['sitemap_tags'] ) ? 1 : 0;

    return $clean;
}

// 4. Llamar a la Vista (UI)
function mainter_seo_render_dashboard() {
    // Verificamos permisos
    if ( ! current_user_can( 'manage_options' ) ) return;
    
    // Cargamos el diseño
    require_once MAINTER_SEO_DIR . 'admin/views/dashboard.php';
}