<?php
// stream.php - Reproductor Local Reparado
// 1. Cargar configuración usando ruta absoluta (__DIR__)
require_once __DIR__ . '/config/app.php'; 
require_once ROOT_PATH . '/includes/db_connect.php';

// 2. LIMPIEZA DE BUFFER (CRÍTICO): Borra cualquier texto/error previo para no corromper el video
while (ob_get_level()) ob_end_clean();

@set_time_limit(0);
ini_set('memory_limit', '512M');

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    http_response_code(400);
    die('Bad Request');
}

// 3. Buscar video en BD
$stmt = db()->prepare("SELECT storage_path, status FROM videos WHERE embed_code = ? LIMIT 1");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video || $video['status'] !== 'active') {
    http_response_code(404);
    die('Video not found');
}

// 4. Construir ruta física absoluta
$fileName = basename($video['storage_path']);
$filePath = VIDEO_PATH . $fileName;

if (!file_exists($filePath)) {
    // Intento de recuperación: buscar en la carpeta uploads directamente
    $filePath = UPLOAD_PATH . $fileName;
    if (!file_exists($filePath)) {
        http_response_code(404);
        die("Error: Archivo de video no encontrado en el servidor.");
    }
}

// 5. Streaming
$fileSize = filesize($filePath);
$fp = @fopen($filePath, 'rb');
$mime = 'video/mp4'; 

// Headers HTTP
header("Content-Type: $mime");
header("Accept-Ranges: bytes");
header("Content-Length: " . $fileSize);

// Manejo de rangos (Permite adelantar/retroceder)
if (isset($_SERVER['HTTP_RANGE'])) {
    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    $range = explode('-', $range);
    $start = intval($range[0]);
    $end = (isset($range[1]) && is_numeric($range[1])) ? intval($range[1]) : $fileSize - 1;
    
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Content-Length: " . ($end - $start + 1));
    fseek($fp, $start);
}

// Enviar archivo directo al navegador
fpassthru($fp);
fclose($fp);
exit;
?>
