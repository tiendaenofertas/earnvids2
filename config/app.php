<?php
define('SITE_NAME', 'EARNVIDS');
define('SITE_URL', 'https://xv.xzorra.net');
define('SITE_VERSION', '1.0.0');
define('DEBUG_MODE', false);

// Verificar si la sesión ya está iniciada
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', ['mp4', 'webm', 'mkv', 'avi', 'mov', 'flv', 'wmv']);
define('THUMBNAIL_WIDTH', 320);
define('THUMBNAIL_HEIGHT', 180);
