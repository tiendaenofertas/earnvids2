<?php
/**
 * Módulo: SEO Avanzado (Schema JSON-LD + Recomendaciones)
 */

// 1. REGISTRO DE OPCIONES (Base de datos)
if ( ! function_exists( 'mi_tema_register_seo_settings' ) ) {
    function mi_tema_register_seo_settings() {
        // Guardamos las opciones de Schema
        register_setting( 'mainter_seo_group', 'mainter_seo_options', 'mi_tema_seo_sanitize' );
    }
    add_action( 'admin_init', 'mi_tema_register_seo_settings' );
}

// 2. RENDERIZADO VISUAL (Interfaz de Usuario)
if ( ! function_exists( 'mi_tema_render_seo_content' ) ) {
    function mi_tema_render_seo_content() {
        $opts = get_option( 'mainter_seo_options' );
        
        // Valores actuales
        $s_org    = isset($opts['schema_org']) ? $opts['schema_org'] : 0;
        $s_art    = isset($opts['schema_article']) ? $opts['schema_article'] : 0;
        $s_bread  = isset($opts['schema_breadcrumbs']) ? $opts['schema_breadcrumbs'] : 0;
        $s_search = isset($opts['schema_search']) ? $opts['schema_search'] : 0;
        $s_video  = isset($opts['schema_video']) ? $opts['schema_video'] : 0;
        ?>

        <style>
            /* Grid para los interruptores */
            .seo-settings-container { margin-bottom: 40px; }
            
            /* Grid compacta para plugins */
            .plugins-mini-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                gap: 15px; 
                margin-top: 15px; 
            }
            .plugin-mini-card {
                background: #f8f9fa; border: 1px solid #e2e4e7; border-radius: 6px;
                padding: 12px; display: flex; align-items: center; gap: 12px;
                text-decoration: none; transition: all 0.2s ease;
            }
            .plugin-mini-card:hover {
                background: #fff; border-color: #007cba; transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            }
            .p-icon { font-size: 24px; line-height: 1; }
            .p-info h5 { margin: 0; font-size: 14px; color: #1d2327; font-weight: 600; }
            .p-info span { font-size: 11px; color: #646970; text-transform: uppercase; letter-spacing: 0.5px; }
            
            .section-divider { 
                border-top: 1px dashed #c3c4c7; margin: 30px 0; position: relative; 
            }
            .section-divider span { 
                position: absolute; top: -12px; left: 0; background: #f0f0f1; 
                padding-right: 10px; font-size: 12px; color: #646970; font-weight: 600;
            }
        </style>

        <div class="seo-settings-container">
            <h3>🤖 Datos Estructurados (Schema.org)</h3>
            <p class="description">Genera código JSON-LD automático para mejorar tu visibilidad en Google.</p>

            <div class="option-row">
                <div class="switch">
                    <label><input type="checkbox" name="mainter_seo_options[schema_org]" value="1" <?php checked( 1, $s_org ); ?>><span class="slider"></span></label>
                </div>
                <div class="option-desc">
                    <h4>Organización</h4>
                    <p>Marca la Home como una entidad/marca oficial.</p>
                </div>
            </div>

            <div class="option-row">
                <div class="switch">
                    <label><input type="checkbox" name="mainter_seo_options[schema_article]" value="1" <?php checked( 1, $s_art ); ?>><span class="slider"></span></label>
                </div>
                <div class="option-desc">
                    <h4>Artículo de Blog</h4>
                    <p>Datos ricos para entradas (Autor, Fechas, Imagen).</p>
                </div>
            </div>

            <div class="option-row">
                <div class="switch">
                    <label><input type="checkbox" name="mainter_seo_options[schema_breadcrumbs]" value="1" <?php checked( 1, $s_bread ); ?>><span class="slider"></span></label>
                </div>
                <div class="option-desc">
                    <h4>Migas de Pan</h4>
                    <p>Muestra la ruta de navegación en los resultados.</p>
                </div>
            </div>

            <div class="option-row">
                <div class="switch">
                    <label><input type="checkbox" name="mainter_seo_options[schema_search]" value="1" <?php checked( 1, $s_search ); ?>><span class="slider"></span></label>
                </div>
                <div class="option-desc">
                    <h4>Caja de Búsqueda</h4>
                    <p>Habilita la búsqueda interna desde Google.</p>
                </div>
            </div>
        </div>

        <div class="section-divider"><span>¿NECESITAS MÁS POTENCIA?</span></div>

        <p class="description" style="margin-bottom: 5px;">Si necesitas análisis de palabras clave o sitemaps complejos, instala <strong>uno</strong> de estos:</p>
        
        <div class="plugins-mini-grid">
            <a href="<?php echo esc_url( admin_url('plugin-install.php?s=rank+math&tab=search&type=term') ); ?>" target="_blank" class="plugin-mini-card">
                <span class="p-icon">📈</span>
                <div class="p-info">
                    <h5>Rank Math</h5>
                    <span>Recomendado</span>
                </div>
            </a>

            <a href="<?php echo esc_url( admin_url('plugin-install.php?s=yoast+seo&tab=search&type=term') ); ?>" target="_blank" class="plugin-mini-card">
                <span class="p-icon">🚦</span>
                <div class="p-info">
                    <h5>Yoast SEO</h5>
                    <span>Clásico</span>
                </div>
            </a>

            <a href="<?php echo esc_url( admin_url('plugin-install.php?s=seopress&tab=search&type=term') ); ?>" target="_blank" class="plugin-mini-card">
                <span class="p-icon">🏢</span>
                <div class="p-info">
                    <h5>SEOPress</h5>
                    <span>Ligero</span>
                </div>
            </a>
        </div>

        <?php
    }
}

// 3. LÓGICA: GENERADOR DE JSON-LD (La maquinaria invisible)
function mainter_output_json_ld() {
    $opts = get_option('mainter_seo_options');
    if ( ! $opts ) return;

    $schemas = array();

    // A. ORGANIZATION
    if ( ! empty( $opts['schema_org'] ) && is_front_page() ) {
        $logo_url = get_theme_mod( 'custom_logo' ) ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '';
        $schemas[] = array(
            '@context' => 'https://schema.org', '@type' => 'Organization',
            'name' => get_bloginfo( 'name' ), 'url' => home_url(),
            'logo' => array( '@type' => 'ImageObject', 'url' => $logo_url )
        );
    }

    // B. SEARCH
    if ( ! empty( $opts['schema_search'] ) && is_front_page() ) {
        $schemas[] = array(
            '@context' => 'https://schema.org', '@type' => 'WebSite',
            'url' => home_url(),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url( '?s={search_term_string}' ),
                'query-input' => 'required name=search_term_string'
            )
        );
    }

    // C. BLOG POSTING
    if ( ! empty( $opts['schema_article'] ) && is_single() ) {
        global $post;
        $thumb_url = has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, 'full' ) : '';
        $schemas[] = array(
            '@context' => 'https://schema.org', '@type' => 'BlogPosting',
            'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => get_permalink() ),
            'headline' => get_the_title(), 'image' => array( $thumb_url ),
            'datePublished' => get_the_date( 'c' ), 'dateModified' => get_the_modified_date( 'c' ),
            'author' => array( '@type' => 'Person', 'name' => get_the_author() ),
            'publisher' => array(
                '@type' => 'Organization', 'name' => get_bloginfo( 'name' ),
                'logo' => array( '@type' => 'ImageObject', 'url' => get_theme_mod( 'custom_logo' ) ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '' )
            )
        );
    }

    // D. BREADCRUMBS
    if ( ! empty( $opts['schema_breadcrumbs'] ) && is_single() ) {
        global $post;
        $categories = get_the_category( $post->ID );
        $item_list = array();
        $item_list[] = array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Inicio', 'item' => home_url() );
        if ( ! empty( $categories ) ) {
            $item_list[] = array( '@type' => 'ListItem', 'position' => 2, 'name' => $categories[0]->name, 'item' => get_category_link( $categories[0]->term_id ) );
        }
        $item_list[] = array( '@type' => 'ListItem', 'position' => 3, 'name' => get_the_title(), 'item' => get_permalink() );

        $schemas[] = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $item_list );
    }

    // SALIDA
    if ( ! empty( $schemas ) ) {
        foreach ( $schemas as $schema ) {
            echo '<script type="application/ld+json">' . json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        }
    }
}
add_action( 'wp_head', 'mainter_output_json_ld', 5 );

// 4. SANITIZACIÓN
if ( ! function_exists( 'mi_tema_seo_sanitize' ) ) {
    function mi_tema_seo_sanitize( $input ) {
        $new = array();
        $checks = array( 'schema_org', 'schema_article', 'schema_breadcrumbs', 'schema_search' );
        foreach ( $checks as $key ) { $new[$key] = isset( $input[$key] ) ? 1 : 0; }
        return $new;
    }
}