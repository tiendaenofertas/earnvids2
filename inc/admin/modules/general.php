<?php
/**
 * Módulo: Ajustes Generales
 * Utilidades globales (404, Editores Clásicos, etc.)
 */

// 1. REGISTRO DE OPCIONES
if ( ! function_exists( 'mi_tema_register_general_settings' ) ) {
    function mi_tema_register_general_settings() {
        // Grupo: mainter_general_group
        // Opción en BD: mainter_general_options
        register_setting( 'mainter_general_group', 'mainter_general_options', 'mi_tema_general_sanitize' );
    }
    add_action( 'admin_init', 'mi_tema_register_general_settings' );
}

// 2. RENDERIZADO VISUAL (El contenido de la pestaña)
if ( ! function_exists( 'mi_tema_render_general_content' ) ) {
    function mi_tema_render_general_content() {
        $opts = get_option( 'mainter_general_options' );
        
        // Obtener valores (0 por defecto)
        $r_404   = isset($opts['redirect_404']) ? $opts['redirect_404'] : 0;
        $c_edit  = isset($opts['classic_editor']) ? $opts['classic_editor'] : 0;
        $c_wid   = isset($opts['classic_widgets']) ? $opts['classic_widgets'] : 0;
        ?>

        <h3>🛠️ Comportamiento del Sitio</h3>
        <p class="description">Ajustes útiles para mejorar la experiencia de usuario y administración.</p>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_general_options[redirect_404]" value="1" <?php checked( 1, $r_404 ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Redirigir errores 404 al Home</h4>
                <p>Evita que los usuarios vean una página de error y los envía a la portada.</p>
            </div>
        </div>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">

        <h3>📝 Experiencia de Edición (Legacy)</h3>
        <p class="description">Herramientas clásicas para quienes prefieren la interfaz antigua.</p>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_general_options[classic_editor]" value="1" <?php checked( 1, $c_edit ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Activar Editor Clásico (Entradas)</h4>
                <p>Desactiva Gutenberg y restaura el editor de texto tradicional.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_general_options[classic_widgets]" value="1" <?php checked( 1, $c_wid ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Activar Widgets Clásicos</h4>
                <p>Restaura la pantalla de gestión de widgets antigua (sin bloques).</p>
            </div>
        </div>

        <?php
    }
}

// 3. LÓGICA: APLICAR LOS CAMBIOS
function mainter_apply_general_tweaks() {
    $opts = get_option('mainter_general_options');
    if ( ! $opts ) return;

    // --- A. EDITORES CLÁSICOS ---
    
    // 1. Editor Clásico en Posts
    if ( ! empty( $opts['classic_editor'] ) ) {
        add_filter( 'use_block_editor_for_post', '__return_false' );
    }

    // 2. Editor Clásico en Widgets
    if ( ! empty( $opts['classic_widgets'] ) ) {
        // Desactiva el editor de bloques en widgets
        add_filter( 'use_widgets_block_editor', '__return_false' );
        remove_theme_support( 'widgets-block-editor' );
    }
}
add_action( 'init', 'mainter_apply_general_tweaks' );


// --- B. LÓGICA REDIRECCIÓN 404 (Hook separado) ---
function mainter_handle_404_redirect_logic() {
    $opts = get_option('mainter_general_options');
    
    // Si la opción está activa Y es una página 404
    if ( ! empty( $opts['redirect_404'] ) && is_404() ) {
        wp_redirect( home_url() );
        exit();
    }
}
add_action( 'template_redirect', 'mainter_handle_404_redirect_logic' );


// 4. SANITIZACIÓN
if ( ! function_exists( 'mi_tema_general_sanitize' ) ) {
    function mi_tema_general_sanitize( $input ) {
        $new = array();
        $checks = array( 'redirect_404', 'classic_editor', 'classic_widgets' );

        foreach ( $checks as $key ) {
            $new[$key] = isset( $input[$key] ) ? 1 : 0;
        }
        return $new;
    }
}