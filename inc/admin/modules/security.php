<?php
/**
 * Módulo de Seguridad Avanzada (Mainter Theme)
 * Incluye limpieza de cabeceras y Security Headers HTTP.
 */

// 1. REGISTRO DE LA CONFIGURACIÓN
if ( ! function_exists( 'mi_tema_register_security_settings' ) ) {
    function mi_tema_register_security_settings() {
        register_setting( 'mainter_sec_group', 'mainter_security_options', 'mi_tema_security_sanitize' );
    }
    add_action( 'admin_init', 'mi_tema_register_security_settings' );
}

// 2. RENDERIZADO VISUAL (Interfaz idéntica a tu captura)
if ( ! function_exists( 'mi_tema_render_security_content' ) ) {
    function mi_tema_render_security_content() {
        $opts = get_option( 'mainter_security_options' );
        
        // Recuperar valores (0 por defecto)
        $hide_ver   = isset($opts['remove_wp_version']) ? $opts['remove_wp_version'] : 0;
        $hide_wlw   = isset($opts['remove_wlw']) ? $opts['remove_wlw'] : 0;
        $hide_rsd   = isset($opts['remove_rsd']) ? $opts['remove_rsd'] : 0;
        $hide_rest  = isset($opts['remove_rest_links']) ? $opts['remove_rest_links'] : 0;
        $disable_xml = isset($opts['disable_xmlrpc']) ? $opts['disable_xmlrpc'] : 0;

        // Cabeceras
        $h_cto      = isset($opts['header_cto']) ? $opts['header_cto'] : 0;
        $h_frame    = isset($opts['header_frame']) ? $opts['header_frame'] : 0;
        $h_xss      = isset($opts['header_xss']) ? $opts['header_xss'] : 0;
        $h_hsts     = isset($opts['header_hsts']) ? $opts['header_hsts'] : 0;
        $h_ref      = isset($opts['header_referrer']) ? $opts['header_referrer'] : 0;
        ?>

        <h3>🧹 Limpieza y Hardening</h3>
        <p class="description">Elimina metadatos innecesarios que exponen información de tu sitio.</p>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[remove_wp_version]" value="1" <?php checked( 1, $hide_ver ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Ocultar versión de WordPress</h4>
                <p>Evita que escaneen tu versión exacta buscando vulnerabilidades conocidas.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[remove_wlw]" value="1" <?php checked( 1, $hide_wlw ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Eliminar enlace WLW Manifest</h4>
                <p>Si no usas Windows Live Writer, este archivo es innecesario.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[remove_rsd]" value="1" <?php checked( 1, $hide_rsd ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Eliminar enlace RSD (EditURI)</h4>
                <p>Desactiva el descubrimiento para edición remota (XML-RPC).</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[remove_rest_links]" value="1" <?php checked( 1, $hide_rest ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Eliminar enlaces API REST</h4>
                <p>Oculta los enlaces JSON del head (no desactiva la API, solo la oculta).</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[disable_xmlrpc]" value="1" <?php checked( 1, $disable_xml ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Desactivar XML-RPC Completo</h4>
                <p>Cierra la puerta principal a ataques de fuerza bruta y DDoS.</p>
            </div>
        </div>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">

        <h3>🛡️ Cabeceras de Seguridad (HTTP Headers)</h3>
        <p class="description">Protección a nivel de navegador contra XSS, Clickjacking y más.</p>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[header_cto]" value="1" <?php checked( 1, $h_cto ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>X-Content-Type-Options (No-Sniff)</h4>
                <p>Evita que el navegador "adivine" tipos de archivos (protege contra subidas maliciosas).</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[header_frame]" value="1" <?php checked( 1, $h_frame ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>X-Frame-Options (Clickjacking)</h4>
                <p>Impide que otros sitios incrusten tu web en un iframe invisible para robar clics.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[header_xss]" value="1" <?php checked( 1, $h_xss ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>X-XSS-Protection</h4>
                <p>Filtro básico contra Cross-Site Scripting en navegadores antiguos.</p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[header_hsts]" value="1" <?php checked( 1, $h_hsts ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Strict-Transport-Security (HSTS)</h4>
                <p>Fuerza al navegador a usar siempre HTTPS. <strong>Activar solo si tienes SSL.</strong></p>
            </div>
        </div>

        <div class="option-row">
            <div class="switch">
                <label>
                    <input type="checkbox" name="mainter_security_options[header_referrer]" value="1" <?php checked( 1, $h_ref ); ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="option-desc">
                <h4>Referrer-Policy</h4>
                <p>Controla qué información envías al hacer clic en un enlace externo.</p>
            </div>
        </div>

        <?php
    }
}

// 3. LÓGICA: APLICAR LOS CAMBIOS
function mainter_apply_security_tweaks() {
    $opts = get_option('mainter_security_options');
    if ( ! $opts ) return;

    // --- A. LIMPIEZA ---
    
    // 1. Desactivar XML-RPC
    if ( ! empty( $opts['disable_xmlrpc'] ) ) {
        add_filter( 'xmlrpc_enabled', '__return_false' );
    }

    // 2. Ocultar Versión
    if ( ! empty( $opts['remove_wp_version'] ) ) {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
    }

    // 3. Eliminar WLW (Windows Live Writer)
    if ( ! empty( $opts['remove_wlw'] ) ) {
        remove_action('wp_head', 'wlwmanifest_link');
    }

    // 4. Eliminar RSD (EditURI)
    if ( ! empty( $opts['remove_rsd'] ) ) {
        remove_action('wp_head', 'rsd_link');
    }

    // 5. Eliminar Enlaces REST API del Head
    if ( ! empty( $opts['remove_rest_links'] ) ) {
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
    }

    // --- B. CABECERAS HTTP (Security Headers) ---
    // Usamos el hook 'send_headers' para inyectarlas antes de enviar el HTML
    
    add_action( 'send_headers', function() use ($opts) {
        
        // 1. No Sniff
        if ( ! empty( $opts['header_cto'] ) ) {
            header( 'X-Content-Type-Options: nosniff' );
        }

        // 2. Clickjacking Protection
        if ( ! empty( $opts['header_frame'] ) ) {
            header( 'X-Frame-Options: SAMEORIGIN' );
        }

        // 3. XSS Protection
        if ( ! empty( $opts['header_xss'] ) ) {
            header( 'X-XSS-Protection: 1; mode=block' );
        }

        // 4. HSTS (Solo si es HTTPS para no romper el sitio)
        if ( ! empty( $opts['header_hsts'] ) && is_ssl() ) {
            header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
        }

        // 5. Referrer Policy
        if ( ! empty( $opts['header_referrer'] ) ) {
            header( 'Referrer-Policy: no-referrer-when-downgrade' );
        }
    });
}
add_action( 'init', 'mainter_apply_security_tweaks' );


// 4. SANITIZACIÓN (Guardar datos limpios)
if ( ! function_exists( 'mi_tema_security_sanitize' ) ) {
    function mi_tema_security_sanitize( $input ) {
        $new = array();
        // Checkboxes: Si viene = 1, sino = 0
        $checks = array(
            'remove_wp_version', 'remove_wlw', 'remove_rsd', 'remove_rest_links', 'disable_xmlrpc',
            'header_cto', 'header_frame', 'header_xss', 'header_hsts', 'header_referrer'
        );

        foreach ( $checks as $key ) {
            $new[$key] = isset( $input[$key] ) ? 1 : 0;
        }

        return $new;
    }
}