<?php
// stream-contabo.php - Proxy para servir videos desde Contabo S3 con autenticación
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
    WHERE embed_code = ? AND status = 'active' AND storage_type = 'contabo'
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Obtener configuración de Contabo
$stmt = db()->query("SELECT * FROM storage_config WHERE storage_type = 'contabo' AND is_active = 1");
$storage = $stmt->fetch();

if (!$storage) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Storage configuration not found');
}

// Incrementar vistas
$viewKey = 'viewed_' . $video['id'];
if (!isset($_SESSION[$viewKey])) {
    incrementViews($video['id']);
    $_SESSION[$viewKey] = true;
}

// Construir URL de Contabo
$endpoint = rtrim($storage['endpoint'], '/');
$bucket = trim($storage['bucket'], '/');
$path = ltrim($video['storage_path'], '/');
$url = $endpoint . '/' . $bucket . '/' . $path;

// Crear contexto con autenticación si es necesario
$opts = [
    "http" => [
        "method" => "GET",
        "header" => [
            "User-Agent: EARNVIDS/1.0"
        ]
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
];

$context = stream_context_create($opts);

// Obtener headers del archivo remoto
$headers = @get_headers($url, 1, $context);

if (!$headers || strpos($headers[0], '200') === false) {
    // Si falla, intentar con S3 API
    require_once 'includes/storage_manager.php';
    
    try {
        // Generar URL firmada (presigned URL)
        $expires = time() + 3600; // 1 hora
        $stringToSign = "GET\n\n\n{$expires}\n/{$bucket}/{$path}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $storage['secret_key'], true));
        
        $url = $endpoint . '/' . $bucket . '/' . $path . 
               '?AWSAccessKeyId=' . $storage['access_key'] . 
               '&Expires=' . $expires . 
               '&Signature=' . urlencode($signature);
        
        $headers = @get_headers($url, 1, $context);
        
        if (!$headers || strpos($headers[0], '200') === false) {
            header('HTTP/1.0 404 Not Found');
            exit('Cannot access video file');
        }
    } catch (Exception $e) {
        header('HTTP/1.0 500 Internal Server Error');
        exit('Error accessing video');
    }
}

// Obtener tamaño del archivo
$fileSize = isset($headers['Content-Length']) ? intval($headers['Content-Length']) : 0;

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
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=3600');

// Si es una solicitud de rango, usar CURL
if ($start > 0 || $end < $fileSize - 1) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RANGE, $start . '-' . $end);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        echo $data;
        flush();
        return strlen($data);
    });
    curl_exec($ch);
    curl_close($ch);
} else {
    // Para solicitudes completas, usar readfile
    readfile($url, false, $context);
}

exit;
