<?php
/**
 * Widget: Mainter Copyright Pro (Con Variables Dinámicas)
 */
class Mainter_Copyright_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'mainter_copyright', 
            '⚡ Mainter: Copyright Inteligente', 
            array( 'description' => 'Muestra copyright con variables automáticas (Año, Fecha, Dominio, Privacidad).' )
        );
    }

    public function widget( $args, $instance ) {
        // Texto por defecto si está vacío
        $text = ! empty( $instance['text'] ) ? $instance['text'] : '© {year} {site_name}. Todos los derechos reservados.';
        
        // --- 1. DICCIONARIO DE VARIABLES ---
        // Aquí definimos qué hace cada "etiqueta mágica"
        
        // A. Básicos
        $year       = date( 'Y' );
        $site_name  = get_bloginfo( 'name' );
        $site_desc  = get_bloginfo( 'description' );
        
        // B. Fecha Completa (Traducida al idioma de tu WP)
        $full_date  = date_i18n( get_option( 'date_format' ) ); // Ej: 14 de enero de 2026
        
        // C. Dominio (Limpio, sin https)
        $domain     = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $site_name;

        // D. Autor (Nombre del Administrador principal - ID 1)
        // Esto es útil si quieres que salga tu nombre personal automáticamente
        $admin_user = get_user_by( 'ID', 1 );
        $author     = $admin_user ? $admin_user->display_name : 'Admin';

        // E. Enlace de Privacidad (Automático)
        $privacy_url = get_privacy_policy_url();
        $privacy_link = $privacy_url ? '<a href="' . esc_url( $privacy_url ) . '">Política de Privacidad</a>' : '';

        // --- 2. REEMPLAZO AUTOMÁTICO ---
        $replacements = array(
            '{year}'        => $year,
            '{site_name}'   => $site_name,
            '{site_desc}'   => $site_desc,
            '{date}'        => $full_date,
            '{domain}'      => $domain,
            '{author}'      => $author,      // Tu nombre (Admin)
            '{privacy}'     => $privacy_link // Enlace HTML ya hecho
        );

        // Hacemos el cambio mágico
        $output = strtr( $text, $replacements );

        echo $args['before_widget'];
        echo '<div class="copyright-content">' . wp_kses_post( $output ) . '</div>';
        echo $args['after_widget'];
    }

    // --- FORMULARIO EN EL ADMIN ---
    public function form( $instance ) {
        $text = ! empty( $instance['text'] ) ? $instance['text'] : '© {year} {site_name}. Hecho con ❤️ por {author}.';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'text' ); ?>">Texto del Copyright:</label>
            <textarea class="widefat" rows="5" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
        </p>
        
        <div style="background: #f0f0f1; padding: 10px; border-radius: 4px; font-size: 11px; color: #666; border: 1px solid #ddd;">
            <strong>Variables Disponibles:</strong>
            <ul style="margin: 5px 0 0 15px; list-style: square;">
                <li><code>{year}</code> = Año actual (2026)</li>
                <li><code>{date}</code> = Fecha completa (14 Enero...)</li>
                <li><code>{site_name}</code> = Nombre de la web</li>
                <li><code>{site_desc}</code> = Descripción corta</li>
                <li><code>{domain}</code> = Dominio (ej: misitio.com)</li>
                <li><code>{author}</code> = Nombre del Admin</li>
                <li><code>{privacy}</code> = Enlace a Política de Privacidad</li>
            </ul>
        </div>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        // Permitimos HTML para poder poner enlaces o negritas
        $instance['text'] = wp_kses_post( $new_instance['text'] );
        return $instance;
    }
}

function register_mainter_copyright_widget() {
    register_widget( 'Mainter_Copyright_Widget' );
}
add_action( 'widgets_init', 'register_mainter_copyright_widget' );