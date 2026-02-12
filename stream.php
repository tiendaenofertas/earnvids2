<?php
// stream.php - Streaming local optimizado con soporte de rangos, caché y Protección de Dominios
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// --- SEGURIDAD: CONTROL DE DOMINIOS (Hotlink Protection) ---
// Validamos el referer ANTES de iniciar cualquier proceso de streaming
if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererHost = '';
    
    if ($referer) {
        $parsedUrl = parse_url($referer);
        $refererHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
    }

    // Obtener host propio para siempre auto-permitirnos
    $selfHost = parse_url(SITE_URL, PHP_URL_HOST);
    $selfHost = strtolower($selfHost ?? '');

    $isAllowed = false;

    // 1. Permitir siempre a nuestro propio sitio
    if ($refererHost === $selfHost) {
        $isAllowed = true;
    }
    
    // 2. Si no es propio, buscar en la lista blanca
    if (!$isAllowed) {
        foreach (ALLOWED_DOMAINS as $allowedDomain) {
            // Limpieza extra por seguridad
            if (strtolower(trim($allowedDomain)) === $refererHost) {
                $isAllowed = true;
                break;
            }
        }
    }

    // 3. Si no pasó ninguna validación, bloquear
    if (!$isAllowed) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access Denied: Domain not authorized.');
    }
}
// -----------------------------------------------------------

// Optimización: Desactivar límites de tiempo para archivos grandes y aumentar memoria
@set_time_limit(0);
ini_set('memory_limit', '512M');

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('HTTP/1.1 400 Bad Request');
    exit('Falta el código de video');
}

// 1. Obtener información del video
// Optimizamos la query para traer solo lo necesario
$stmt = db()->prepare("
    SELECT id, storage_path, status, title 
    FROM videos 
    WHERE embed_code = ? AND storage_type = 'local' 
    LIMIT 1
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video || $video['status'] !== 'active') {
    header('HTTP/1.1 404 Not Found');
    exit('Video no encontrado o eliminado');
}

// 2. Validar archivo físico de forma segura
// Seguridad: basename previene ataques de Directory Traversal
$safePath = basename($video['storage_path']); 

// Intentar ruta definida en constantes
$filePath = VIDEO_PATH . $safePath;

if (!file_exists($filePath)) {
    // Fallback: intentar ruta relativa manual si la constante falla o es estructura antigua
    $filePath = __DIR__ . '/uploads/videos/' . $safePath;
    
    if (!file_exists($filePath)) {
        // Último intento: usar el path tal cual viene de BD (con cuidado)
        $cleanDbPath = str_replace(['../', '..\\'], '', $video['storage_path']);
        $filePath = __DIR__ . '/uploads/' . $cleanDbPath;
        
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            exit('Archivo de video no encontrado en el servidor');
        }
    }
}

// 3. Registrar vista (Con control de sesión para no inflar contador)
$viewKey = 'viewed_' . $video['id'];
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION[$viewKey])) {
    if (function_exists('incrementViews')) {
        incrementViews($video['id']);
    }
    $_SESSION[$viewKey] = true;
}

// 4. Preparar Headers de Streaming
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath) ?: 'video/mp4';
$lastModified = filemtime($filePath);
// ETag único basado en ruta, tamaño y fecha
$etag = md5($filePath . $fileSize . $lastModified);

// Headers de Caché y Tipo
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
header("ETag: \"$etag\"");
header("Cache-Control: public, max-age=86400"); // 1 día de caché
header('Accept-Ranges: bytes');
header("Content-Type: $mimeType");
header('X-Content-Type-Options: nosniff');

// Manejo de caché del navegador (304 Not Modified)
// Si el navegador ya tiene el archivo, no enviamos nada
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
    header("HTTP/1.1 304 Not Modified");
    exit;
}

// 5. Manejo de Rangos (Lógica crítica para adelantar/retroceder)
$start = 0;
$end = $fileSize - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    $c_start = $start;
    $c_end = $end;
    
    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$fileSize");
        exit;
    }
    
    if ($range == '-') {
        $c_start = $fileSize - substr($range, 1);
    } else {
        $range = explode('-', $range);
        $c_start = $range[0];
        $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
    }
    
    $c_end = ($c_end > $end) ? $end : $c_end;
    
    if ($c_start > $c_end || $c_start > $fileSize - 1 || $c_end >= $fileSize) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes $start-$end/$fileSize");
        exit;
    }
    
    $start = $c_start;
    $end = $c_end;
    $length = $end - $start + 1;
    
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
    header("Content-Length: $length");
} else {
    header("Content-Length: $fileSize");
}

// 6. Enviar contenido optimizado
$fp = fopen($filePath, 'rb');
fseek($fp, $start);

// Buffer de 16KB para balancear memoria y velocidad
$bufferSize = 1024 * 16; 
$bytesSent = 0;
$totalToSend = $end - $start + 1;

while (!feof($fp) && ($pos = ftell($fp)) <= $end && connection_status() == 0) {
    if ($bytesSent >= $totalToSend) break;
    
    $readSize = min($bufferSize, $totalToSend - $bytesSent);
    echo fread($fp, $readSize);
    flush(); // Forzar envío al cliente
    $bytesSent += $readSize;
}

fclose($fp);
exit;
?>
