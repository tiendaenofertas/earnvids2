<?php
/**
 * CSS Frontend Dinámico: Grid Responsive + Estilos
 */

function mi_tema_custom_css_output() {

    // Helpers
    function get_safe_mod( $key, $default ) {
        $val = get_theme_mod( $key, $default );
        return ( empty( $val ) && $val !== '0' ) ? $default : $val;
    }

    // VARIABLES
    $brand       = get_safe_mod('color_brand', '#4f46e5');
    $text_main   = get_safe_mod('color_text', '#1f2937');
    $link        = get_safe_mod('color_link', '#10b981');
    $h_bg        = get_safe_mod('header_bg', '#ffffff');
    $h_text      = get_safe_mod('header_text', '#333333');
    $f_bg        = get_safe_mod('footer_bg', '#111827');
    $f_text      = get_safe_mod('footer_text', '#f9fafb');
    $btn_bg      = get_safe_mod('btn_bg', '#4f46e5');
    $btn_text    = get_safe_mod('btn_text', '#ffffff');

    // DISEÑO
    $container_w = get_safe_mod('container_width', 1200);
    $sidebar_pct = get_safe_mod('sidebar_width_pct', 30); // Porcentaje
    $font_size   = get_safe_mod('body_font_size', 16);
    $img_radius  = get_safe_mod('img_radius', 8);
    $btn_radius  = get_safe_mod('btn_radius', 4);
    $btn_scale   = get_safe_mod('btn_padding_factor', 1);

    // Padding Botones
    $btn_py = 10 * $btn_scale; 
    $btn_px = 20 * $btn_scale;


    // Recuperamos los colores oscuros
    $dm_bg      = get_theme_mod('dm_bg_body', '#111827');
    $dm_text    = get_theme_mod('dm_text_main', '#f3f4f6');
    $dm_header  = get_theme_mod('dm_bg_header', '#1f2937');
    $dm_card    = get_theme_mod('dm_bg_card', '#1f2937');
    // Reusamos el brand color o puedes crear uno específico para dark si quieres

    // Recuperar Fuentes
    $font_heading = get_theme_mod( 'typo_heading_font', 'Inter' );
    $font_body    = get_theme_mod( 'typo_body_font', 'Inter' );

    // Recuperar Tamaños
    $s_h1   = get_theme_mod( 'typo_size_h1', 38 );
    $s_h2   = get_theme_mod( 'typo_size_h2', 32 );
    $s_h3   = get_theme_mod( 'typo_size_h3', 28 );
    $s_h4   = get_theme_mod( 'typo_size_h4', 23 );
    $s_body = get_theme_mod( 'typo_size_body', 16 );
    $s_list = get_theme_mod( 'typo_size_listing', 18 );
    $s_feat = get_theme_mod( 'typo_size_listing_feat', 25 );

    // LOGICA FUENTES LOCALES VS GOOGLE
    // Si elige "System", usamos la pila nativa rápida
    $f_stack_heading = ($font_heading === 'system') ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif' : '"' . $font_heading . '", sans-serif';
    $f_stack_body    = ($font_body === 'system')    ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif' : '"' . $font_body . '", sans-serif';

    // Si elige "Local", asumimos que tú cargaste el @font-face manualmente con el nombre "Fuente Local"
    // (Opcional: puedes personalizar esto más adelante)

    ?>

   
    <style id="mi-tema-dynamic-css">
        :root {
           
            /* Colores */
            --theme-color-brand: <?php echo $brand; ?>;
            --theme-color-link:  <?php echo $link; ?>;
            --theme-text-main:   <?php echo $text_main; ?>;
            --header-bg:         <?php echo $h_bg; ?>;
            --header-text:       <?php echo $h_text; ?>;
            --footer-bg:         <?php echo $f_bg; ?>;
            --footer-text:       <?php echo $f_text; ?>;
            --btn-bg:            <?php echo $btn_bg; ?>;
            --btn-text:          <?php echo $btn_text; ?>;

            /* Diseño */
            --container-width:   <?php echo $container_w; ?>px;
            --sidebar-width:     <?php echo $sidebar_pct; ?>%; 
            --body-font-size:    <?php echo $font_size; ?>px;
            --img-radius:        <?php echo $img_radius; ?>px;
            --btn-radius:        <?php echo $btn_radius; ?>px;
            --btn-padding:       <?php echo $btn_py . 'px ' . $btn_px . 'px'; ?>;
            
            /* Espacio entre Main y Sidebar */
            --grid-gap:          2rem; 

            /* --- ✒️ TIPOGRAFÍA --- */
            --font-heading: <?php echo $f_stack_heading; ?>;
            --font-body:    <?php echo $f_stack_body; ?>;

            /* --- 📏 TAMAÑOS --- */
            --size-h1:      <?php echo $s_h1; ?>px;
            --size-h2:      <?php echo $s_h2; ?>px;
            --size-h3:      <?php echo $s_h3; ?>px;
            --size-h4:      <?php echo $s_h4; ?>px;
            --size-body:    <?php echo $s_body; ?>px;
            --size-list:    <?php echo $s_list; ?>px;
            --size-feat:    <?php echo $s_feat; ?>px;
        }

        /* APLICACIÓN GLOBAL */
        body { 
            font-family: var(--font-body);
            font-size: var(--size-body);
        }
        
        h1, h2, h3, h4, h5, h6, .site-title { 
            font-family: var(--font-heading);
            font-weight: 700; /* Peso bold por defecto para headers */
        }

        h1 { font-size: var(--size-h1); }
        h2 { font-size: var(--size-h2); }
        h3 { font-size: var(--size-h3); }
        h4 { font-size: var(--size-h4); }

        /* APLICACIÓN ESPECÍFICA (Listados) */
        .card-title { font-size: var(--size-list); }
        .style-overlay .card-title { font-size: var(--size-feat); }
   


    body.dark-mode {
        /* Sobrescribimos las variables globales */
        --theme-text-main: <?php echo $dm_text; ?>;
        
        /* Variables de Fondo que necesitamos crear en style.css si no existen */
        --body-bg:         <?php echo $dm_bg; ?>;
        
        --header-bg:       <?php echo $dm_header; ?>;
        --header-text:     <?php echo $dm_text; ?>;
        
        --footer-bg:       <?php echo $dm_header; ?>;
        --footer-text:     <?php echo $dm_text; ?>;

        /* Para las tarjetas */
        --card-bg:         <?php echo $dm_card; ?>;
        --card-border:     #374151; /* Borde más oscuro */
    }

        /* --- GLOBAL --- */
        body { 
            color: var(--theme-text-main); font-size: var(--body-font-size); line-height: 1.6; 
        }
        a, a:hover { color: var(--theme-color-link); text-decoration: none; }
        
        /* Contenedor */
        .container, .site-content-wrapper {
            max-width: var(--container-width);
            margin: 0 auto; padding-left: 1rem; padding-right: 1rem;
        }

        /* Imágenes y Botones */
        img, .wp-block-image img { border-radius: var(--img-radius); max-width: 100%; height: auto; }
        
        .button, input[type="submit"], .btn, .wp-block-button__link {
            background-color: var(--btn-bg) !important;
            color: var(--btn-text) !important;
            border-radius: var(--btn-radius) !important;
            padding: var(--btn-padding) !important;
            border: none; cursor: pointer; transition: opacity 0.3s;
        }
        button:hover { opacity: 0.9; }

        /* --- SISTEMA DE LAYOUT GRID --- */
        
        /* Contenedor del Grid (debes usar esta clase en tu HTML) */
        .mainter-grid-layout {
            display: grid;
            /* Móvil por defecto: 1 columna */
            grid-template-columns: 100%;
            gap: var(--grid-gap);
            margin:1rem 0;
        }

        /* Escritorio (Solo si la pantalla es grande) */
        @media (min-width: 992px) {
            .mainter-grid-layout {
                /* La Magia: 1fr (Main) y Porcentaje (Sidebar) */
                grid-template-columns: 1fr var(--sidebar-width);
            }

            /* Si se ocultó el sidebar por layout-logic.php, volvemos a 1 columna */
            body.no-sidebar .mainter-grid-layout,
            body.full-width-layout .mainter-grid-layout {
                grid-template-columns: 100% !important;
            }
        }

        /* --- Header & Footer --- */
        .mainter-header { background: var(--header-bg); color: var(--header-text);box-shadow: 0px 4px 8px rgba(0, 21, 64, .3); }
        .mainter-header a { color: var(--header-text); }
        .site-footer { background: var(--footer-bg); color: var(--footer-text); }

        /* Ocultar Título Header */
        <?php if ( ! display_header_text() ) : ?>
            .header-branding .site-title, .header-branding .site-description { position: absolute; clip: rect(1px, 1px, 1px, 1px); }
        <?php endif; ?>

        /* Ocultar Sidebar Visualmente si la lógica lo dice */
        body.no-sidebar #secondary { display: none !important; }

    </style>
    <?php
}
add_action( 'wp_head', 'mi_tema_custom_css_output', 100 );