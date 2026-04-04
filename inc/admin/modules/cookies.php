<?php
/**
 * Módulo de Aviso de Cookies
 * Incluye: Panel de opciones y Salida Frontend (HTML/JS/CSS)
 */

// 1. REGISTRO DE LA OPCIÓN (Backend)
if ( ! function_exists( 'mi_tema_register_cookie_settings' ) ) {
    function mi_tema_register_cookie_settings() {
        // Usamos un grupo ÚNICO para asegurar que guarde sin conflictos
        register_setting( 'mainter_cookie_group', 'mainter_cookie_options', 'mi_tema_cookie_sanitize' );
    }
    add_action( 'admin_init', 'mi_tema_register_cookie_settings' );
}

// 2. RENDERIZADO DEL PANEL (Lo que ves en el admin)
if ( ! function_exists( 'mi_tema_render_cookie_content' ) ) {
    function mi_tema_render_cookie_content() {
        $opts = get_option( 'mainter_cookie_options' );
        
        // Valores por defecto
        $enable  = isset($opts['enable_cookies']) ? $opts['enable_cookies'] : 0;
        $msg     = isset($opts['cookie_message']) ? $opts['cookie_message'] : 'Utilizamos cookies para mejorar tu experiencia. Al continuar navegando, aceptas nuestra política de privacidad.';
        $btn_txt = isset($opts['cookie_btn_text']) ? $opts['cookie_btn_text'] : 'Aceptar';
        ?>

        <h3>🍪 Aviso de Consentimiento</h3>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_cookie_options[enable_cookies]" value="1" <?php checked( 1, $enable ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Activar Aviso de Cookies</h4>
                <p>Muestra un banner flotante en la parte inferior hasta que el usuario acepte.</p>
            </div>
        </div>

        <hr>

        <div class="option-row">
            <div class="option-desc">
                <h4>Mensaje del Aviso</h4>
                <p>Puedes usar HTML simple (ej: enlaces &lt;a&gt; a tu política).</p>
            </div>
            <div style="flex-grow: 1; margin-left: 20px;">
                <textarea name="mainter_cookie_options[cookie_message]" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"><?php echo esc_textarea( $msg ); ?></textarea>
            </div>
        </div>

        <div class="option-row">
            <div class="option-desc">
                <h4>Texto del Botón</h4>
            </div>
            <div style="flex-grow: 1; margin-left: 20px;">
                <input type="text" name="mainter_cookie_options[cookie_btn_text]" value="<?php echo esc_attr( $btn_txt ); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
        </div>
        <?php
    }
}

// 3. SANITIZACIÓN
if ( ! function_exists( 'mi_tema_cookie_sanitize' ) ) {
    function mi_tema_cookie_sanitize( $input ) {
        $new = array();
        $new['enable_cookies']  = isset( $input['enable_cookies'] ) ? 1 : 0;
        // Permitimos HTML seguro en el mensaje (para enlaces)
        $new['cookie_message']  = wp_kses_post( $input['cookie_message'] ); 
        $new['cookie_btn_text'] = sanitize_text_field( $input['cookie_btn_text'] );
        return $new;
    }
}

// 4. SALIDA FRONTEND (El Banner Real)
function mi_tema_cookie_banner_output() {
    $opts = get_option( 'mainter_cookie_options' );

    // Si está desactivado, no imprimimos nada
    if ( empty( $opts['enable_cookies'] ) ) {
        return;
    }

    $msg = !empty($opts['cookie_message']) ? $opts['cookie_message'] : 'Usamos cookies.';
    $btn = !empty($opts['cookie_btn_text']) ? $opts['cookie_btn_text'] : 'Aceptar';
    ?>
    
    <div id="mainter-cookie-notice" class="mainter-cookie-wrap">
        <div class="cookie-content">
            <span class="cookie-text"><?php echo do_shortcode($msg); ?></span>
            <button id="mainter-accept-cookies" class="cookie-btn"><?php echo esc_html($btn); ?></button>
        </div>
    </div>

    <style>
        .mainter-cookie-wrap {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #fff; color: #333;
            padding: 15px 25px; border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 99999; display: none; /* Oculto por defecto hasta que JS verifique */
            border-left: 5px solid var(--theme-color-brand, #4f46e5);
            max-width: 1200px; margin: 0 auto;
        }
        .cookie-content { display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .cookie-text { font-size: 0.9rem; line-height: 1.4; }
        .cookie-btn {
            background: var(--theme-color-brand, #4f46e5); color: #fff;
            border: none; padding: 8px 20px; border-radius: 4px;
            cursor: pointer; font-weight: 600; white-space: nowrap;
            transition: opacity 0.3s;
        }
        .cookie-btn:hover { opacity: 0.9; }
        
        @media (max-width: 600px) {
            .mainter-cookie-wrap { bottom: 0; left: 0; right: 0; border-radius: 0; }
            .cookie-content { flex-direction: column; text-align: center; }
        }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const cookieBox = document.getElementById('mainter-cookie-notice');
        const acceptBtn = document.getElementById('mainter-accept-cookies');

        // 1. Verificar si ya aceptó antes (localStorage)
        if ( ! localStorage.getItem('mainter_cookie_consent') ) {
            cookieBox.style.display = 'block'; // Mostrar si no hay consentimiento
        }

        // 2. Al hacer clic en aceptar
        acceptBtn.addEventListener('click', function() {
            localStorage.setItem('mainter_cookie_consent', 'true');
            cookieBox.style.opacity = '0';
            setTimeout(() => { cookieBox.style.display = 'none'; }, 300);
        });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'mi_tema_cookie_banner_output' );