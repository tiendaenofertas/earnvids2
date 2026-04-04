<?php
/**
 * Módulo de Rendimiento Avanzado
 * Limpieza de Core, jQuery, Speculation Rules y CSS Bloques.
 */

// 1. REGISTRO (Usando el GRUPO MAESTRO 'mainter_pro_group')
if ( ! function_exists( 'mi_tema_register_perf_settings' ) ) {
    function mi_tema_register_perf_settings() {
       register_setting( 'mainter_perf_group', 'mainter_perf_options', 'mi_tema_perf_sanitize' );
    }
    add_action( 'admin_init', 'mi_tema_register_perf_settings' );
}

// 2. LÓGICA DE LIMPIEZA (Frontend)
function mainter_apply_performance_tweaks() {
    $opts = get_option('mainter_perf_options');

    if ( is_admin() ) return; // No tocar el admin

    // A. DESACTIVAR EMOJIS
    if ( ! empty( $opts['disable_emojis'] ) ) {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
    }

    // B. LIMPIAR CSS NATIVO (Gutenberg y Global Styles)
    if ( ! empty( $opts['remove_core_css'] ) ) {
        add_action( 'wp_enqueue_scripts', function() {
            // Estilos CSS archivos
            wp_dequeue_style( 'wp-block-library' );
            wp_dequeue_style( 'wp-block-library-theme' );
            wp_dequeue_style( 'wc-blocks-style' ); // Woo
            wp_dequeue_style( 'classic-theme-styles' );
            
            // Estilos Inline (Global Styles) - Lo que mostraste en la captura
            wp_dequeue_style( 'global-styles' ); 
        }, 100 );

        // Quitar inyecciones en el head y footer
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
        remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
    }

    // C. ELIMINAR JQUERY (¡Cuidado! Solo activar si no usas plugins dependientes)
    if ( ! empty( $opts['disable_jquery'] ) ) {
        add_action( 'wp_enqueue_scripts', function() {
            wp_deregister_script( 'jquery' );
            wp_deregister_script( 'jquery-core' );
            wp_deregister_script( 'jquery-migrate' );
        }, 100 );
    }

    // D. ELIMINAR SPECULATION RULES (Prefetch API)
    // Ese script <script type="speculationrules"> que mostraste
    if ( ! empty( $opts['disable_speculation'] ) ) {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_speculation_rules' );
    }
}
add_action( 'init', 'mainter_apply_performance_tweaks' );


// 3. RENDERIZADO VISUAL
if ( ! function_exists( 'mi_tema_render_performance_content' ) ) {
    function mi_tema_render_performance_content() {
        $opts = get_option( 'mainter_perf_options' );
        
        $emojis      = isset($opts['disable_emojis']) ? $opts['disable_emojis'] : 0;
        $core_css    = isset($opts['remove_core_css']) ? $opts['remove_core_css'] : 0;
        $jquery      = isset($opts['disable_jquery']) ? $opts['disable_jquery'] : 0;
        $speculation = isset($opts['disable_speculation']) ? $opts['disable_speculation'] : 0;
        ?>
        <h3>⚡ Limpieza Profunda del Core</h3>
        <p>Activa estas opciones con cuidado. Si algo se rompe, desactívalas.</p>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_perf_options[remove_core_css]" value="1" <?php checked( 1, $core_css ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Limpiar CSS de Bloques (Gutenberg)</h4>
                <p>Elimina <code>global-styles</code>, <code>wp-block-library</code> y SVG filters. Elimina ese código CSS gigante del head.</p>
            </div>
        </div>

        <div class="option-row" style="background: #fff5f5; border-color: #feb2b2;">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_perf_options[disable_jquery]" value="1" <?php checked( 1, $jquery ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4 style="color: #c53030;">Eliminar jQuery</h4>
                <p>Deja de cargar jQuery y jQuery Migrate. Ahorra mucho peso, pero puede romper plugins antiguos.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_perf_options[disable_speculation]" value="1" <?php checked( 1, $speculation ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Desactivar Speculation Rules</h4>
                <p>Elimina el script <code>speculationrules</code> que hace pre-carga de páginas al pasar el mouse.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_perf_options[disable_emojis]" value="1" <?php checked( 1, $emojis ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Desactivar Emojis WP</h4>
                <p>Evita la carga del script de detección de emojis.</p>
            </div>
        </div>
        <?php
    }
}

// 4. SANITIZACIÓN
if ( ! function_exists( 'mi_tema_perf_sanitize' ) ) {
    function mi_tema_perf_sanitize( $input ) {
        $new = array();
        $new['disable_emojis']      = isset( $input['disable_emojis'] ) ? 1 : 0;
        $new['remove_core_css']     = isset( $input['remove_core_css'] ) ? 1 : 0;
        $new['disable_jquery']      = isset( $input['disable_jquery'] ) ? 1 : 0;
        $new['disable_speculation'] = isset( $input['disable_speculation'] ) ? 1 : 0;
        return $new;
    }
}