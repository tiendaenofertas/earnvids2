<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. REESCRITURA DE LA ETIQUETA <TITLE>
// ==========================================
function mainter_seo_process_tags( $text ) {
    if ( empty( $text ) ) return '';
    $tags = [
        '%%sitename%%' => get_bloginfo( 'name' ),
        '%%sitedesc%%' => get_bloginfo( 'description' ),
        '%%sep%%'      => '-', 
    ];
    return str_replace( array_keys( $tags ), array_values( $tags ), $text );
}

function mainter_seo_custom_title( $title ) {
    $opts = get_option( 'mainter_seo_settings', [] );
    
    // 1. Título Home
    if ( ( is_front_page() || is_home() ) && ! empty( $opts['home_module_active'] ) ) {
        if ( ! empty( $opts['home_title'] ) ) {
            return mainter_seo_process_tags( $opts['home_title'] );
        }
    } 
    // 2. Título Personalizado desde Metabox (Entradas/Páginas)
    elseif ( is_singular() ) {
        $custom_title = get_post_meta( get_the_ID(), '_mainter_seo_title', true );
        if ( ! empty( $custom_title ) ) {
            return mainter_seo_process_tags( $custom_title );
        }
    }
    
    return $title;
}
add_filter( 'pre_get_document_title', 'mainter_seo_custom_title', 999 );

// ==========================================
// 2. INYECCIÓN DE METADATOS, REDES Y ROBOTS
// ==========================================

// Eliminar etiqueta nativa para evitar duplicados
remove_action( 'wp_head', 'wp_robots', 1 );

function mainter_seo_essential_meta_tags() {
    $opts = get_option( 'mainter_seo_settings', [] );

    $is_home     = is_front_page() || is_home();
    $is_singular = is_singular();

    $title = wp_get_document_title();
    $desc  = '';
    $url   = wp_get_canonical_url() ?: home_url( $_SERVER['REQUEST_URI'] );
    $image = '';
    
    // VARIABLES DE AUTOR
    $author_name = '';
    $author_url  = '';
    
    // REGLAS DE ROBOTS BASE
    $robots = 'index, follow'; 
    
    // Lógica Automática y Prioridades
    if ( $is_home ) {
        $robots = $opts['robots_home'] ?? 'index, follow';
        $desc = ( ! empty( $opts['home_module_active'] ) && ! empty( $opts['home_desc'] ) ) 
            ? mainter_seo_process_tags( $opts['home_desc'] ) 
            : get_bloginfo( 'description' );
        $image = $opts['home_og_image'] ?? '';
        
        $author_name = get_bloginfo( 'name' ); // En la Home, el autor es la Marca
        
    } elseif ( $is_singular ) {
        $post_id = get_the_ID();
        
        // 1. Robots
        $post_robot = get_post_meta( $post_id, '_mainter_seo_robots', true );
        if ( ! empty( $post_robot ) && $post_robot !== 'default' ) {
            $robots = $post_robot; 
        } else {
            $post_type = get_post_type();
            $robots = ($post_type === 'page') ? ($opts['robots_pages'] ?? 'index, follow') : ($opts['robots_posts'] ?? 'index, follow');
        }
        
        // 2. Descripción
        $custom_desc = get_post_meta( $post_id, '_mainter_seo_desc', true );
        if ( ! empty( $custom_desc ) ) {
            $desc = $custom_desc;
        } else {
            $desc = has_excerpt( $post_id ) 
                ? get_the_excerpt() 
                : wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $post_id ) ) ), 30, '...' );
        }

        // 3. Imagen
        $image = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'full' ) : ($opts['home_og_image'] ?? '');
        
        // 4. Extracción de datos del Autor
        $author_id   = get_post_field( 'post_author', $post_id );
        $author_name = get_the_author_meta( 'display_name', $author_id );
        
        // Buscamos la URL web del autor en su perfil; si no existe, sacamos el link a todos sus artículos
        $user_url = get_the_author_meta( 'user_url', $author_id );
        $author_url  = ! empty( $user_url ) ? $user_url : get_author_posts_url( $author_id );
        
    } elseif ( is_category() ) {
        $robots = $opts['robots_cats'] ?? 'index, follow';
        $desc = wp_strip_all_tags( term_description() );
    } elseif ( is_tag() ) {
        $robots = $opts['robots_tags'] ?? 'noindex, follow';
        $desc = wp_strip_all_tags( term_description() );
    }

    // Fusión de etiqueta Max Image Preview
    if ( ! isset($opts['robots_max_image']) || ! empty( $opts['robots_max_image'] ) ) {
        $robots .= ', max-image-preview:large';
    }

    // --- IMPRESIÓN EN EL HTML ---
     echo '<!--  Mainter SEO Plugin v1.0 -->';
    echo "\n";

    // 1. Etiqueta Robots
    echo '<meta name="robots" content="' . esc_attr( $robots ) . '">' . "\n";

    // 2. Meta Descripción
    if ( ! empty( $desc ) ) {
        echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
    }

    // 3. Enlace Canónico
    if ( ! empty( $opts['enable_canonical'] ) && $url ) {
        echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
    }
    
    // 4. ETIQUETA AUTHOR GLOBAL (Estándar W3C)
    if ( ! empty( $author_name ) ) {
        echo '<meta name="author" content="' . esc_attr( $author_name ) . '">' . "\n";
    }

    // 5. Open Graph (Facebook, WhatsApp)
    if ( ! empty( $opts['enable_og'] ) ) {
        echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        if ( ! empty( $desc ) ) echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
        echo '<meta property="og:type" content="' . ( $is_singular ? 'article' : 'website' ) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
        if ( ! empty( $image ) ) echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
        
        // ETIQUETAS DE AUTOR Y PUBLISHER PARA ARTÍCULOS
        if ( $is_singular && get_post_type() === 'post' ) {
            // Perfil del Autor
            if ( ! empty( $author_url ) ) {
                echo '<meta property="article:author" content="' . esc_url( $author_url ) . '">' . "\n";
            }
            // Perfil del Publisher (Página Oficial del Sitio configurada en el panel)
            if ( ! empty( $opts['social_facebook'] ) ) {
                echo '<meta property="article:publisher" content="' . esc_url( $opts['social_facebook'] ) . '">' . "\n";
            }
        }
    }

    // 6. Twitter Cards
    if ( ! empty( $opts['enable_tw'] ) ) {
        $tw_image = ( $is_home && ! empty( $opts['home_tw_image'] ) ) ? $opts['home_tw_image'] : $image;
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
        if ( ! empty( $desc ) ) echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
        if ( ! empty( $tw_image ) ) echo '<meta name="twitter:image" content="' . esc_url( $tw_image ) . '">' . "\n";
    }
	
	echo '<meta content="1200" property="og:image:width"/>' . "\n";
	echo '<meta content="630" property="og:image:height"/>' . "\n";
	


    echo "\n";
     echo '<!-- / Mainter SEO Plugin v1.0 -->';
}
add_action( 'wp_head', 'mainter_seo_essential_meta_tags', 1 );


// ==========================================
// 3. ENLACES EXTERNOS (TARGET BLANK / NOFOLLOW)
// ==========================================
function mainter_seo_external_links_script() {
    $opts = get_option( 'mainter_seo_settings', [] );
    
    $blank = ! empty( $opts['external_blank'] );
    $nofollow = ! empty( $opts['external_nofollow'] );

    if ( ! $blank && ! $nofollow ) return;
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var links = document.querySelectorAll("a[href^='http']");
        var host = window.location.hostname;
        var addBlank = <?php echo $blank ? 'true' : 'false'; ?>;
        var addNofollow = <?php echo $nofollow ? 'true' : 'false'; ?>;

        links.forEach(function(link) {
            if (link.hostname !== host) {
                if (addBlank) link.setAttribute("target", "_blank");
                
                var relAttr = addBlank ? "noopener noreferrer" : ""; 
                if (addNofollow) relAttr += relAttr ? " nofollow" : "nofollow";
                
                if (relAttr) link.setAttribute("rel", relAttr);
            }
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'mainter_seo_external_links_script', 99 );


// ==========================================
// 4. DATOS ESTRUCTURADOS (SCHEMA JSON-LD)
// ==========================================
function mainter_seo_output_json_ld() {
    $opts = get_option( 'mainter_seo_settings', [] );
    
    $is_home = is_front_page() || is_home();
    $is_single = is_single();
    
    // Solo generar si algún módulo schema está activo
    if ( empty($opts['schema_org']) && empty($opts['schema_search']) && empty($opts['schema_article']) && empty($opts['schema_breadcrumbs']) ) return;

    $logo_id = get_theme_mod( 'custom_logo' );
    $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
    $site_name = wp_strip_all_tags( get_bloginfo( 'name' ) );
    
    // Mejor Práctica: Agrupar todo en un "@graph"
    $graph = [];

    // A. ORGANIZATION
    if ( $is_home && ! empty( $opts['schema_org'] ) ) {
        $org = [
            "@type" => "Organization",
            "@id" => esc_url( home_url( '/#organization' ) ),
            "name" => $site_name,
            "url" => esc_url( home_url() ),
        ];
        if ( $logo_url ) {
            $org["logo"] = [ "@type" => "ImageObject", "url" => esc_url( $logo_url ) ];
            $org["image"] = [ "@id" => esc_url( home_url( '/#logo' ) ) ];
        }
        $graph[] = $org;
    }

    // B. SITELINKS SEARCHBOX
    if ( $is_home && ! empty( $opts['schema_search'] ) ) {
        $graph[] = [
            "@type" => "WebSite",
            "@id" => esc_url( home_url( '/#website' ) ),
            "url" => esc_url( home_url() ),
            "name" => $site_name,
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => esc_url( home_url( '/' ) ) . "?s={search_term_string}",
                "query-input" => "required name=search_term_string",
            ],
        ];
    }

    // C. BLOG POSTING (Artículo)
    if ( $is_single && get_post_type() === 'post' && ! empty( $opts['schema_article'] ) ) {
        global $post;
        $thumb_url = has_post_thumbnail( $post->ID ) ? get_the_post_thumbnail_url( $post->ID, 'full' ) : '';

        $article = [
            "@type" => "Article",
            "@id" => esc_url( get_permalink() ) . '#article',
            "isPartOf" => [ "@id" => esc_url( get_permalink() ) ],
            "headline" => wp_strip_all_tags( get_the_title() ),
            "datePublished" => get_the_date( 'c' ),
            "dateModified" => get_the_modified_date( 'c' ),
            "author" => [ "@type" => "Person", "name" => wp_strip_all_tags( get_the_author() ) ],
            "publisher" => [ "@id" => esc_url( home_url( '/#organization' ) ) ],
            "inLanguage" => get_bloginfo( 'language' )
        ];
        if ( $thumb_url ) $article["image"] = [ "@type" => "ImageObject", "url" => esc_url( $thumb_url ) ];
        
        $graph[] = $article;
    }

    // D. BREADCRUMBS (Migas de Pan)
    if ( $is_single && ! empty( $opts['schema_breadcrumbs'] ) ) {
        global $post;
        $categories = get_the_category( $post->ID );
        $item_list = [];

        $item_list[] = [ "@type" => "ListItem", "position" => 1, "name" => "Inicio", "item" => esc_url( home_url() ) ];

        if ( ! empty( $categories ) ) {
            $cat = $categories[0];
            $item_list[] = [ "@type" => "ListItem", "position" => 2, "name" => wp_strip_all_tags( $cat->name ), "item" => esc_url( get_category_link( $cat->term_id ) ) ];
        }

        $item_list[] = [ "@type" => "ListItem", "position" => ! empty( $categories ) ? 3 : 2, "name" => wp_strip_all_tags( get_the_title() ), "item" => esc_url( get_permalink() ) ];

        $graph[] = [ 
            "@type" => "BreadcrumbList", 
            "@id" => esc_url( get_permalink() ) . '#breadcrumb',
            "itemListElement" => $item_list 
        ];
    }

    // --- IMPRESIÓN DEL JSON UNIFICADO ---
    if ( ! empty( $graph ) ) {
        $final_schema = [
            "@context" => "https://schema.org",
            "@graph" => $graph
        ];
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $final_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
        echo "</script>\n";
    }
}
add_action( 'wp_head', 'mainter_seo_output_json_ld', 5 );


// ==========================================
// 5. GENERADOR DE SITEMAP XML
// ==========================================
function mainter_seo_generate_sitemap() {
    $opts = get_option( 'mainter_seo_settings', [] );
    if ( empty( $opts['enable_sitemap'] ) ) return;

    $raw_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $parsed_uri = wp_parse_url( $raw_uri, PHP_URL_PATH );
    $uri = is_string($parsed_uri) ? untrailingslashit( $parsed_uri ) : '';

    if ( $uri === '/sitemap.xml' ) {
        wp_redirect( home_url( '/sitemap_index.xml' ), 301 );
        exit;
    }

    if ( $uri === '/mainter-sitemap.xsl' ) {
        status_header( 200 ); // Forzamos al servidor a que devuelva un estado OK
        header( 'Content-Type: text/xsl; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>
        <xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
            <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
            <xsl:template match="/">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <title>Mapa del sitio XML</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; color: #333; }
                        .hero { background-color: #3b82f6; color: #fff; padding: 40px 20px; }
                        .hero-content { max-width: 1000px; margin: 0 auto; }
                        .hero h1 { margin: 0 0 10px 0; font-size: 32px; font-weight: 600; }
                        .wrap { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
                        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                        th { background: #3b82f6; color: #fff; text-align: left; padding: 15px; font-size: 14px; }
                        td { padding: 12px 15px; border-bottom: 1px solid #f0f0f1; font-size: 13px; }
                        tr:nth-child(even) td { background: #f8f9fa; }
                        a { color: #3b82f6; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="hero"><div class="hero-content"><h1>Mapa del sitio XML</h1><p>Generado por Mainter SEO.</p></div></div>
                    <div class="wrap">
                        <xsl:choose>
                            <xsl:when test="sitemap:sitemapindex">
                                <table>
                                    <tr><th>Mapa del sitio</th><th>Última modificación</th></tr>
                                    <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                                        <tr><td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td><td><xsl:value-of select="substring(sitemap:lastmod, 0, 17)"/></td></tr>
                                    </xsl:for-each>
                                </table>
                            </xsl:when>
                            <xsl:otherwise>
                                <table>
                                    <tr><th>URL</th><th>Última modificación</th></tr>
                                    <xsl:for-each select="sitemap:urlset/sitemap:url">
                                        <tr><td><a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a></td><td><xsl:value-of select="substring(sitemap:lastmod, 0, 17)"/></td></tr>
                                    </xsl:for-each>
                                </table>
                            </xsl:otherwise>
                        </xsl:choose>
                    </div>
                </body>
                </html>
            </xsl:template>
        </xsl:stylesheet>';
        exit;
    }

    if ( $uri === '/sitemap_index.xml' ) {
        status_header( 200 ); // Forzamos al servidor a que devuelva un estado OK
        header( 'Content-Type: text/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/mainter-sitemap.xsl' ) ) . '"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $date = date('c');
        $sitemaps = [];

        if ( !empty($opts['sitemap_posts']) || !isset($opts['sitemap_posts']) ) $sitemaps[] = 'post-sitemap.xml';
        if ( !empty($opts['sitemap_pages']) || !isset($opts['sitemap_pages']) ) $sitemaps[] = 'page-sitemap.xml';
        if ( !empty($opts['sitemap_cats']) || !isset($opts['sitemap_cats']) ) $sitemaps[] = 'category-sitemap.xml';
        if ( !empty($opts['sitemap_tags']) ) $sitemaps[] = 'tag-sitemap.xml';

        foreach ( $sitemaps as $sitemap ) {
            echo "  <sitemap>\n    <loc>" . esc_url( home_url( '/' . $sitemap ) ) . "</loc>\n    <lastmod>{$date}</lastmod>\n  </sitemap>\n";
        }
        echo '</sitemapindex>';
        exit;
    }

    $is_post_sitemap = ( $uri === '/post-sitemap.xml' && !empty($opts['sitemap_posts']) );
    $is_page_sitemap = ( $uri === '/page-sitemap.xml' && !empty($opts['sitemap_pages']) );

    if ( $is_post_sitemap || $is_page_sitemap ) {
        status_header( 200 ); // Forzamos al servidor a que devuelva un estado OK
        header( 'Content-Type: text/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/mainter-sitemap.xsl' ) ) . '"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $post_type = $is_post_sitemap ? 'post' : 'page';

        if ( $is_page_sitemap && strpos( ($opts['robots_home'] ?? 'index'), 'noindex' ) === false ) {
            echo "  <url><loc>" . esc_url( home_url( '/' ) ) . "</loc><lastmod>" . date('c') . "</lastmod></url>\n";
        }

        $query = new WP_Query([
            'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => 500, 
            'fields' => 'ids', 'orderby' => 'modified', 'order' => 'DESC'
        ]);
        
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post_id ) {
                $global_rule = ($post_type === 'page') ? ($opts['robots_pages'] ?? 'index') : ($opts['robots_posts'] ?? 'index');
                $individual_rule = get_post_meta( $post_id, '_mainter_seo_robots', true );
                $final_rule = (!empty($individual_rule) && $individual_rule !== 'default') ? $individual_rule : $global_rule;
                
                if ( strpos( $final_rule, 'noindex' ) === false ) {
                    echo "  <url>\n    <loc>" . esc_url( get_permalink( $post_id ) ) . "</loc>\n    <lastmod>" . get_the_modified_time( 'c', $post_id ) . "</lastmod>\n  </url>\n";
                }
            }
        }
        echo '</urlset>';
        exit;
    }

    $is_cat_sitemap = ( $uri === '/category-sitemap.xml' && !empty($opts['sitemap_cats']) );
    $is_tag_sitemap = ( $uri === '/tag-sitemap.xml' && !empty($opts['sitemap_tags']) );

    if ( $is_cat_sitemap || $is_tag_sitemap ) {
        status_header( 200 ); // Forzamos al servidor a que devuelva un estado OK
        header( 'Content-Type: text/xml; charset=utf-8' );
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( home_url( '/mainter-sitemap.xsl' ) ) . '"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $taxonomy = $is_cat_sitemap ? 'category' : 'post_tag';
        $global_rule = $is_cat_sitemap ? ($opts['robots_cats'] ?? 'index') : ($opts['robots_tags'] ?? 'noindex');

        if ( strpos( $global_rule, 'noindex' ) === false ) {
            $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => true ] );
            if ( ! is_wp_error( $terms ) ) {
                foreach ( $terms as $term ) {
                    echo "  <url>\n    <loc>" . esc_url( get_term_link( $term ) ) . "</loc>\n  </url>\n";
                }
            }
        }
        echo '</urlset>';
        exit;
    }
}
// ¡Cambiamos el Hook a 'init' para que capture la URL antes de que WordPress declare un error 404!
add_action( 'init', 'mainter_seo_generate_sitemap', 0 );