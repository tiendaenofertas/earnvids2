<?php
/**
 * Plugin Name: XVIDSPRO Secure Player
 * Plugin URI: https://xvidspro.com
 * Description: Reproductor HTML5 Ultra Rápido con encriptación, protección Anti-Robo nativa, Escudo Anti-Descargas, Monetización y Estadísticas sincronizadas.
 * Version: 2.3.0
 * Author: XVIDSPRO
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Definir rutas globales para usarlas fácilmente en las clases
define('XVIDSPRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('XVIDSPRO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Cargar el sistema modular
require_once XVIDSPRO_PLUGIN_DIR . 'includes/class-xvidspro-admin.php';
require_once XVIDSPRO_PLUGIN_DIR . 'includes/class-xvidspro-public.php';

class XvidsPro_Plugin {
    
    public function __construct() {
        // 1. Cargar panel solo si es administrador
        if (is_admin()) {
            new XvidsPro_Admin();
        }
        
        // 2. Cargar funciones públicas y reproductores
        new XvidsPro_Public();
        
        // 3. Registrar reglas de URL
        add_action('init', [$this, 'add_rewrite_endpoints']);
    }

    public function add_rewrite_endpoints() {
        add_rewrite_tag('%xvid_embed%', '([^&]+)');
        add_rewrite_tag('%xvid_stream%', '([^&]+)');
        add_rewrite_tag('%xvid_download%', '([^&]+)'); 
        add_rewrite_tag('%xvid_force_dl%', '([^&]+)'); 
    }
}

// Iniciar el plugin
new XvidsPro_Plugin();

// Ganchos de Activación
register_activation_hook(__FILE__, function() {
    $plugin = new XvidsPro_Plugin();
    $plugin->add_rewrite_endpoints();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
?>
