<?php
// config/app.php - Configuración Maestra con Rutas Absolutas
// Definimos la raíz del proyecto de forma infalible subiendo un nivel desde /config
define('ROOT_PATH', realpath(__DIR__ . '/..'));

define('SITE_NAME', 'EARNVIDS');
define('SITE_URL', 'https://xv.xzorra.net'); 
define('SITE_VERSION', '1.6.0');
define('DEBUG_MODE', true);

// Configuración de Sesión (Evita bloqueos y conflictos)
if (session_status() === PHP_SESSION_NONE) {
    // Nombre único para evitar conflictos con cookies antiguas
    session_name('EARNVIDS_SESSION_V2');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 86400); // 24 horas
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

// Rutas Físicas Absolutas para Almacenamiento
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('VIDEO_PATH', UPLOAD_PATH . 'videos/');
define('THUMBNAIL_PATH', UPLOAD_PATH . 'thumbnails/');

// Límites
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024 * 1024); // 5GB
define('ALLOWED_EXTENSIONS', ['mp4', 'webm', 'mkv', 'avi', 'mov', 'flv', 'wmv']);
?>