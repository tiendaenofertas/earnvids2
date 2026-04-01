<?php
/**
 * Se ejecuta al DESINSTALAR el plugin desde el panel de WordPress.
 */

// Si la desinstalación no fue llamada por WordPress, abortamos por seguridad
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// 1. Borramos la opción principal de la base de datos (donde guardaremos todo)
delete_option( 'mainter_seo_settings' );

// (En el futuro, si creamos tablas personalizadas o metadatos, los borraremos aquí)