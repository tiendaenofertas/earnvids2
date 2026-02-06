<?php
// s3-proxy.php - Proxy completo para servir videos desde Contabo S3
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Función para crear firma S3 v2
function signRequestV2($method, $bucket, $key, $expires, $accessKey, $secretKey) {
    $stringToSign = "{$method}\n\n\n{$expires}\n/{$bucket}/{$key}";
    return base64_encode(hash_hmac('sha1', $stringToSign, $secretKey, true));
}

// Función para servir video con autenticación S3
function serveVideoFromS3($videoPath, $accessKey, $secretKey, $bucket, $endpoint) {
    $expires = time() + 3600; // 1 hora
    $signature = signRequestV2('GET', $bucket, $videoPath, $expires, $accessKey, $secretKey);
    
    // Construir URL firmada
    $signedUrl = "{$endpoint}/{$bucket}/{$videoPath}?" . http_build_query([
        'AWSAccessKeyId' => $accessKey,
        'Expires' => $expires,
        'Signature' => $signature
    ]);
    
    // Obtener el contenido usando CURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $signedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // Manejar rangos para streaming
    if (isset($_SERVER['HTTP_RANGE'])) {
        curl_setopt($ch, CURLOPT_RANGE, str_replace('bytes=', '', $_SERVER['HTTP_RANGE']));
        header('HTTP/1.1 206 Partial Content');
    }
    
    // Headers para streaming
    header('Content-Type: video/mp4');
    header('Accept-Ranges: bytes');
    header('Cache-Control: public, max-age=3600');
    
    // Callback para escribir directamente al navegador
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
        echo $data;
        flush();
        return strlen($data);
    });
    
    // Callback para headers
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        
        if (count($header) < 2) return $len;
        
        $name = strtolower(trim($header[0]));
        $value = trim($header[1]);
        
        // Reenviar ciertos headers al cliente
        if (in_array($name, ['content-length', 'content-range', 'accept-ranges'])) {
            header("{$header[0]}: {$value}");
        }
        
        return $len;
    });
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 400) {
        header('HTTP/1.0 404 Not Found');
        exit('Video no disponible');
    }
}

// MAIN
$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('HTTP/1.0 404 Not Found');
    exit('Video no especificado');
}

// Obtener video de la BD
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE embed_code = ? AND status = 'active' AND storage_type = 'contabo'
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    header('HTTP/1.0 404 Not Found');
    exit('Video no encontrado');
}

// Obtener configuración de storage
$stmt = db()->query("SELECT * FROM storage_config WHERE storage_type = 'contabo' AND is_active = 1");
$storage = $stmt->fetch();

if (!$storage) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Error de configuración');
}

// Incrementar vistas
$viewKey = 'viewed_' . $video['id'];
if (!isset($_SESSION[$viewKey])) {
    incrementViews($video['id']);
    $_SESSION[$viewKey] = true;
}

// Servir video
serveVideoFromS3(
    $video['storage_path'],
    $storage['access_key'],
    $storage['secret_key'],
    $storage['bucket'],
    $storage['endpoint']
);
?>