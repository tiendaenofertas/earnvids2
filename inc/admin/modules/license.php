<?php
/**
 * Módulo: Gestión de Licencia (Seguro y Oculto)
 */

// 1. REGISTRO Y SANITIZACIÓN INTELIGENTE
if ( ! function_exists( 'mi_tema_register_license_settings' ) ) {
    function mi_tema_register_license_settings() {
        // Usamos una función de sanitización personalizada para manejar el borrado
        register_setting( 'mainter_license_group', 'mainter_license_key', 'mi_tema_sanitize_license' );
    }
    add_action( 'admin_init', 'mi_tema_register_license_settings' );
}

// 2. RENDERIZADO VISUAL (Interfaz Cambiante)
if ( ! function_exists( 'mi_tema_render_license_content' ) ) {
    function mi_tema_render_license_content() {
        $license_key = get_option( 'mainter_license_key' );
        $is_active   = mainter_is_license_active();
        ?>

        <h3>🔑 Estado de la Licencia</h3>

        <?php if ( $is_active ) : ?>
            
            <div style="background: #ecfdf5; border: 1px solid #6ee7b7; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="color: #065f46; margin: 0 0 10px 0;">✅ Licencia Activada Correctamente</h4>
                <p style="margin: 0; color: #047857;">Gracias por apoyar el desarrollo. Todas las funciones Pro están desbloqueadas.</p>
            </div>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Clave de Licencia</th>
                    <td>
                        <code style="font-size: 1.2em; background: #f3f4f6; padding: 5px 10px; border-radius: 4px;">
                            MAINTER-PRO-********************
                        </code>
                        <p class="description">La clave está oculta por seguridad.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Acciones</th>
                    <td>
                        <label style="color: #dc2626; font-weight: 600;">
                            <input type="checkbox" name="mainter_deactivate_license" value="1"> 
                            Desvincular licencia de este sitio
                        </label>
                        <p class="description">Marca esta casilla y guarda cambios para eliminar la licencia y volver a la versión gratuita.</p>
                    </td>
                </tr>
            </table>

            <input type="hidden" name="mainter_license_key" value="<?php echo esc_attr( $license_key ); ?>">

        <?php else : ?>

            <div style="background: #fff; border: 1px solid #e5e7eb; padding: 30px; border-radius: 8px; text-align: center; max-width: 500px;">
                <span style="font-size: 40px; display: block; margin-bottom: 10px;">🔒</span>
                <h2 style="margin-top: 0;">Versión Gratuita</h2>
                <p>Introduce tu clave para desbloquear las actualizaciones, el módulo SEO y el soporte Premium.</p>
                
                <div style="margin: 20px 0; text-align: left;">
                    <label style="font-weight: 600; display: block; margin-bottom: 5px;">Código de Licencia:</label>
                    <input type="text" name="mainter_license_key" value="" class="regular-text" placeholder="Pega tu código aquí..." style="width: 100%; padding: 10px;">
                </div>
                
                <p class="description" style="margin-bottom: 0;">
                    ¿No tienes una? <a href="https://discord.gg/tuserver" target="_blank">Consíguela en Discord</a>.
                </p>
            </div>

        <?php endif; ?>

        <?php
    }
}

// 3. LÓGICA DE VALIDACIÓN (Tu clave maestra)
function mainter_is_license_active() {
    $key = get_option( 'mainter_license_key' );
    // CLAVE MAESTRA
    return ( $key === 'MAINTER-LIFETIME-2026' );
}

// 4. SANITIZACIÓN ESPECIAL (Maneja el borrado)
function mi_tema_sanitize_license( $input_value ) {
    // Si el usuario marcó "Desvincular", borramos la clave
    if ( isset( $_POST['mainter_deactivate_license'] ) ) {
        return ''; 
    }
    return sanitize_text_field( $input_value );
}

// 5. AVISO EN ADMIN (Solo si no está activa)
function mainter_admin_notice_license() {
    if ( ! mainter_is_license_active() ) {
        $screen = get_current_screen();
        if ( strpos( $screen->base, 'mainter_options' ) !== false ) return;
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>🔒 Mainter Theme:</strong> Funciones Premium bloqueadas. <a href="<?php echo admin_url('admin.php?page=mainter_options&tab=license'); ?>">Activar Licencia</a></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'mainter_admin_notice_license' );