<?php
/**
 * Plugin Name: Mainter SEO
 * Plugin URI:        https://xvidspro.com/
 * Description: Mainter es un plugin SEO ultra ligero todo en uno.
 * Version: 1.0.0
 * Author: Framber Silva
 * Author URI:        https://xvidspro.com
 */
// Seguridad: Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definir constantes del plugin para rutas fáciles
define( 'MAINTER_SEO_VERSION', '1.0.0' );
define( 'MAINTER_SEO_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAINTER_SEO_URL', plugin_dir_url( __FILE__ ) );

// Cargar los módulos según dónde estemos (Admin o Frontend)
if ( is_admin() ) {
    require_once MAINTER_SEO_DIR . 'admin/admin-core.php';
    require_once MAINTER_SEO_DIR . 'admin/metaboxes.php';
} else {
    require_once MAINTER_SEO_DIR . 'includes/frontend.php';
}
