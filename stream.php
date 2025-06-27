<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Obtener video
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE embed_code = ? AND status = 'active' AND storage_type = 'local'
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

$filePath = 'uploads/' . $video['storage_path'];

if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Incrementar vistas (solo una vez por sesión)
$viewKey = 'viewed_' . $video['id'];
if (!isset($_SESSION[$viewKey])) {
    incrementViews($video['id']);
    $_SESSION[$viewKey] = true;
}

// Obtener información del archivo
$fileSize = filesize($filePath);
$fileInfo = pathinfo($filePath);
$extension = strtolower($fileInfo['extension']);

// Determinar MIME type
$mimeTypes = [
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mkv' => 'video/x-matroska',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'flv' => 'video/x-flv',
    'wmv' => 'video/x-ms-wmv'
];

$mimeType = $mimeTypes[$extension] ?? 'video/mp4';

// Manejar solicitudes de rango
$start = 0;
$end = $fileSize - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    $range = $_SERVER['HTTP_RANGE'];
    $range = str_replace('bytes=', '', $range);
    $range = explode('-', $range);
    
    $start = intval($range[0]);
    if (isset($range[1]) && !empty($range[1])) {
        $end = intval($range[1]);
    }
    
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
    header('Content-Length: ' . ($end - $start + 1));
} else {
    header('Content-Length: ' . $fileSize);
}

// Headers para streaming
header('Content-Type: ' . $mimeType);
header('Accept-Ranges: bytes');
header('Cache-Control: no-cache');

// Abrir archivo y enviar contenido
$fp = fopen($filePath, 'rb');
fseek($fp, $start);

$buffer = 1024 * 8; // 8KB buffer
while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
    if ($pos + $buffer > $end) {
        $buffer = $end - $pos + 1;
    }
    
    echo fread($fp, $buffer);
    flush();
}

fclose($fp);
exit;