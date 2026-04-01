<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Registrar el Metabox
function mainter_seo_add_metabox() {
    $screens = [ 'post', 'page' ]; // Se mostrará en entradas y páginas
    foreach ( $screens as $screen ) {
        add_meta_box(
            'mainter_seo_meta_box',           // ID
            '🚀 Mainter SEO - Ajustes del Artículo', // Título
            'mainter_seo_render_metabox',     // Función que lo pinta
            $screen,                          // Pantalla
            'normal',                         // Contexto (debajo del contenido)
            'high'                            // Prioridad
        );
    }
}
add_action( 'add_meta_boxes', 'mainter_seo_add_metabox' );

// 2. Renderizar (Pintar) el Metabox
function mainter_seo_render_metabox( $post ) {
    // Seguridad (Nonce)
    wp_nonce_field( 'mainter_seo_save_meta', 'mainter_seo_meta_nonce' );

    // Recuperar datos previamente guardados
    $title  = get_post_meta( $post->ID, '_mainter_seo_title', true );
    $desc   = get_post_meta( $post->ID, '_mainter_seo_desc', true );
    $robots = get_post_meta( $post->ID, '_mainter_seo_robots', true );
    ?>
    
    <!-- Estilos específicos para el metabox -->
    <style>
        /* Contenedor principal */
        .m-seo-box {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        /* Vista previa */
        .m-seo-preview {
            background: #f9f9f9;
            border-left: 4px solid #007cba;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .m-seo-preview-label {
            margin: 0 0 10px 0;
            font-size: 11px;
            font-weight: 700;
            color: #70757a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .m-seo-preview-url {
            font-size: 12px;
            color: #006621;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .m-seo-preview-icon {
            background: #f1f3f4;
            padding: 2px 6px;
            border-radius: 20px;
            font-size: 12px;
        }
        .m-seo-preview-title {
            font-size: 16px;
            font-weight: 400;
            color: #1a0dab;
            margin-bottom: 3px;
            line-height: 1.3;
            cursor: pointer;
        }
        .m-seo-preview-title:hover {
            text-decoration: underline;
        }
        .m-seo-preview-desc {
            font-size: 13px;
            color: #4d5156;
            line-height: 1.4;
            word-break: break-word;
        }
        /* Campos del formulario */
        .m-seo-field {
            margin-bottom: 18px;
        }
        .m-seo-field label {
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
            color: #23282d;
        }
        .m-seo-field input[type="text"],
        .m-seo-field textarea,
        .m-seo-field select {
            width: 100%;
            border-radius: 4px;
            border: 1px solid #8c8f94;
            padding: 8px 10px;
            font-size: 13px;
        }
        .m-seo-field input[type="text"]:focus,
        .m-seo-field textarea:focus,
        .m-seo-field select:focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
            outline: none;
        }
        .m-seo-field .description {
            color: #646970;
            font-size: 12px;
            margin-top: 4px;
            font-style: italic;
        }
    </style>

  

    <div class="m-seo-box">
        
        <div class="m-seo-preview">
            <p style="margin: 0 0 10px 0; font-size: 11px; font-weight: 700; color: #70757a; text-transform: uppercase;">Vista previa en Google</p>
            <div class="m-seo-preview-url">
                <span style="background: #f1f3f4; padding: 2px 6px; border-radius: 20px; font-size: 12px;">🌐</span> 
                <?php echo esc_url( home_url() ); ?> › ...
            </div>
            <div class="m-seo-preview-title" id="m-seo-preview-title">
                <?php echo $title ? esc_html( $title ) : esc_html( get_the_title( $post->ID ) . ' - ' . get_bloginfo('name') ); ?>
            </div>
            <div class="m-seo-preview-desc" id="m-seo-preview-desc">
                <?php echo $desc ? esc_html( $desc ) : 'Aquí aparecerá la descripción automática extraída del contenido de tu artículo si no escribes una personalizada...'; ?>
            </div>
        </div>

        <div class="m-seo-field">
            <label for="mainter_seo_title">Meta Título Personalizado:</label>
            <input type="text" id="mainter_seo_title" name="mainter_seo_title" value="<?php echo esc_attr( $title ); ?>" placeholder="Ej: Las 10 Mejores Técnicas de SEO en 2024">
            <p class="description">Déjalo en blanco para usar el título por defecto del artículo. Recomendado: 50-60 caracteres.</p>
        </div>

        <div class="m-seo-field">
            <label for="mainter_seo_desc">Meta Descripción Personalizada:</label>
            <textarea id="mainter_seo_desc" name="mainter_seo_desc" rows="3" placeholder="Escribe un resumen persuasivo para atraer clics..."><?php echo esc_textarea( $desc ); ?></textarea>
            <p class="description">Déjalo en blanco para que se genere automáticamente del contenido. Recomendado: 150-160 caracteres.</p>
        </div>

        <div class="m-seo-field">
            <label for="mainter_seo_robots">Visibilidad Avanzada (Robots):</label>
            <select id="mainter_seo_robots" name="mainter_seo_robots" style="max-width: 400px;">
                <option value="default" <?php selected( $robots, 'default' ); ?>>Usar configuración global del panel</option>
                <option value="index, follow" <?php selected( $robots, 'index, follow' ); ?>>✅ Forzar: Indexar y Seguir</option>
                <option value="noindex, follow" <?php selected( $robots, 'noindex, follow' ); ?>>⚠️ Forzar: NO Indexar, pero Seguir</option>
                <option value="noindex, nofollow" <?php selected( $robots, 'noindex, nofollow' ); ?>>❌ Forzar: Ocultar Totalmente</option>
            </select>
            <p class="description">Usa esta opción solo si quieres contradecir las reglas generales que configuraste en tu panel de Mainter SEO.</p>
        </div>
    </div>

    <script>
        // JS Ligero para actualizar la vista previa en tiempo real
        document.getElementById('mainter_seo_title').addEventListener('input', function() {
            let val = this.value.trim();
            document.getElementById('m-seo-preview-title').textContent = val ? val : '<?php echo esc_js( get_the_title( $post->ID ) . ' - ' . get_bloginfo('name') ); ?>';
        });
        document.getElementById('mainter_seo_desc').addEventListener('input', function() {
            let val = this.value.trim();
            document.getElementById('m-seo-preview-desc').textContent = val ? val : 'Aquí aparecerá la descripción automática extraída del contenido...';
        });
    </script>
    <?php
}

// 3. Guardar los datos al darle a "Actualizar" o "Publicar"
function mainter_seo_save_meta( $post_id ) {
    if ( ! isset( $_POST['mainter_seo_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mainter_seo_meta_nonce'], 'mainter_seo_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['mainter_seo_title'] ) ) update_post_meta( $post_id, '_mainter_seo_title', sanitize_text_field( $_POST['mainter_seo_title'] ) );
    if ( isset( $_POST['mainter_seo_desc'] ) ) update_post_meta( $post_id, '_mainter_seo_desc', sanitize_textarea_field( $_POST['mainter_seo_desc'] ) );
    if ( isset( $_POST['mainter_seo_robots'] ) ) update_post_meta( $post_id, '_mainter_seo_robots', sanitize_text_field( $_POST['mainter_seo_robots'] ) );
}
add_action( 'save_post', 'mainter_seo_save_meta' );