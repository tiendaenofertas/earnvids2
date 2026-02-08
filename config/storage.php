<?php
// config/storage.php - Configuración de rutas y límites
// Definimos rutas ABSOLUTAS para evitar errores de "carpeta no encontrada"

// Raíz del proyecto (asumiendo que este archivo está en /config)
define('ROOT_PATH', dirname(__DIR__) . '/');

// Ruta física donde se guardan los videos (Local)
define('VIDEO_PATH', ROOT_PATH . 'uploads/videos/');

// URL pública para acceder a los videos (Local)
define('VIDEO_URL', '/uploads/videos/');

// Extensiones permitidas
define('ALLOWED_EXTENSIONS', ['mp4', 'webm', 'mkv', 'avi', 'mov', 'flv', 'wmv']);

// Tamaño máximo (ej: 5GB)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024 * 1024);

// Asegurar que la carpeta exista
if (!is_dir(VIDEO_PATH)) {
    @mkdir(VIDEO_PATH, 0755, true);
}
?>
