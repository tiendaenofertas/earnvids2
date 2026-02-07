<?php
// s3-proxy.php - Proxy optimizado con soporte S3 V4 y Caching agresivo
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Aumentar límites para streaming de archivos grandes
@set_time_limit(0);
ini_set('memory_limit', '512M');

// Clase dedicada para manejar el proxy S3 con Signature V4
class S3ProxyV4 {
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $region;
    private $bucket;

    public function __construct($accessKey, $secretKey, $endpoint, $region, $bucket) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->endpoint = rtrim($endpoint, '/');
        // Fallback a us-east-1 si no se define región (necesario para algunos compatibles con S3)
        $this->region = $region ?: 'us-east-1';
        $this->bucket = $bucket;
    }

    public function stream($objectPath) {
        // Limpiar path
        $objectPath = ltrim($objectPath, '/');
        $uri = '/' . $this->bucket . '/' . $objectPath;
        $url = $this->endpoint . $uri;

        // Headers base
        $headers = [];
        
        // Manejo de Range (Seeking)
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers['Range'] = $_SERVER['HTTP_RANGE'];
        }

        // Manejo de Cache condicional (ETag / If-None-Match)
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $headers['If-None-Match'] = $_SERVER['HTTP_IF_NONE_MATCH'];
        }
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $headers['If-Modified-Since'] = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
        }

        // Generar firma V4
        $signedHeaders = $this->getSignatureV4('GET', $uri, [], $headers);
        
        // Preparar cURL
        $ch = curl_init();
        $curlHeaders = [];
        foreach ($signedHeaders as $k => $v) {
            $curlHeaders[] = "$k: $v";
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_RETURNTRANSFER => false, // Importante: false para streaming directo
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // Ajustar a true en producción si hay certs
            CURLOPT_HEADER => false, // No enviar headers en el body
            // Callback para headers de respuesta
            CURLOPT_HEADERFUNCTION => function($ch, $header) {
                $len = strlen($header);
                $headerParts = explode(':', $header, 2);
                if (count($headerParts) < 2) return $len;

                $name = strtolower(trim($headerParts[0]));
                $value = trim($headerParts[1]);

                // Headers permitidos para pasar al cliente
                $allowedHeaders = [
                    'content-type',
                    'content-length',
                    'content-range',
                    'accept-ranges',
                    'etag',
                    'last-modified',
                    'cache-control',
                    'expires'
                ];

                if (in_array($name, $allowedHeaders)) {
                    header("$name: $value");
                }
                
                return $len;
            }
        ]);

        // Ejecutar request - cURL escribirá directamente al output buffer
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Manejo básico de errores si S3 falla
        if (!$success || $httpCode >= 400) {
            // Solo si no se han enviado headers aún
            if (!headers_sent()) {
                if ($httpCode === 404) {
                    header("HTTP/1.1 404 Not Found");
                    echo "Video no encontrado en el servidor de almacenamiento.";
                } elseif ($httpCode === 403) {
                    header("HTTP/1.1 403 Forbidden");
                    echo "Acceso denegado al archivo.";
                } else {
                    header("HTTP/1.1 500 Internal Server Error");
                    echo "Error en el servidor de streaming ($httpCode).";
                }
            }
            exit;
        }
    }

    private function getSignatureV4($method, $uri, $queryParams, $headers) {
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);

        // Host header es obligatorio para V4
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $headers['Host'] = $host;
        $headers['x-amz-date'] = $amzDate;
        $headers['x-amz-content-sha256'] = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'; // Hash de payload vacío

        ksort($headers, SORT_STRING | SORT_FLAG_CASE);

        $canonicalHeaders = '';
        $signedHeaders = [];
        foreach ($headers as $k => $v) {
            $k_lower = strtolower($k);
            $canonicalHeaders .= $k_lower . ':' . trim($v) . "\n";
            $signedHeaders[] = $k_lower;
        }
        $signedHeadersString = implode(';', $signedHeaders);
        $payloadHash = $headers['x-amz-content-sha256'];

        $canonicalRequest = "$method\n$uri\n\n$canonicalHeaders\n$signedHeadersString\n$payloadHash";

        $credentialScope = "$dateStamp/{$this->region}/$service/aws4_request";
        $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);

        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        $authorization = "$algorithm Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeadersString, Signature=$signature";

        $headers['Authorization'] = $authorization;
        return $headers;
    }
}

// === Lógica Principal ===

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('HTTP/1.1 400 Bad Request');
    exit('Falta el código de video');
}

// 1. Obtener información del video
// Optimizacion: Solo seleccionamos campos necesarios
$stmt = db()->prepare("
    SELECT id, storage_type, storage_path, status 
    FROM videos 
    WHERE embed_code = ? LIMIT 1
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video || $video['status'] !== 'active') {
    header('HTTP/1.1 404 Not Found');
    exit('Video no disponible o eliminado');
}

// 2. Obtener configuración de almacenamiento
// Priorizamos la configuración del tipo que usa el video, si no, la activa
$storageType = $video['storage_type'];
$stmt = db()->prepare("SELECT * FROM storage_config WHERE storage_type = ? LIMIT 1");
$stmt->execute([$storageType]);
$storage = $stmt->fetch();

if (!$storage) {
    // Fallback: intentar con la activa por defecto si coincide el tipo
    $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
    $activeStorage = $stmt->fetch();
    if ($activeStorage && $activeStorage['storage_type'] === $storageType) {
        $storage = $activeStorage;
    } else {
        error_log("Error: Configuración de almacenamiento no encontrada para tipo {$storageType}");
        header('HTTP/1.1 500 Internal Server Error');
        exit('Error de configuración de almacenamiento');
    }
}

// 3. Registrar vista (con throttling simple por sesión para no saturar DB)
$viewKey = 'viewed_' . $video['id'];
if (!isset($_SESSION[$viewKey])) {
    // Usamos una función ligera si es posible, o la estándar
    if (function_exists('incrementViews')) {
        incrementViews($video['id']);
    }
    $_SESSION[$viewKey] = true;
}

// 4. Iniciar Streaming según tipo
// Headers globales para optimización
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *'); // Permitir embed en otros sitios si se desea

if (in_array($storageType, ['contabo', 'wasabi', 'aws'])) {
    $proxy = new S3ProxyV4(
        $storage['access_key'],
        $storage['secret_key'],
        $storage['endpoint'],
        $storage['region'],
        $storage['bucket']
    );
    $proxy->stream($video['storage_path']);

} elseif ($storageType === 'local') {
    // Streaming local optimizado
    $filePath = 'uploads/' . $video['storage_path']; // Ajusta path si es necesario según tu config
    
    if (!file_exists($filePath)) {
        header('HTTP/1.1 404 Not Found');
        exit('Archivo local no encontrado');
    }

    $fileSize = filesize($filePath);
    $start = 0;
    $end = $fileSize - 1;

    // Caché headers para local
    $etag = md5($filePath . $fileSize . filemtime($filePath));
    header("ETag: \"$etag\"");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", filemtime($filePath)) . " GMT");
    header("Cache-Control: public, max-age=86400"); // 1 día de caché

    // Check If-None-Match
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }

    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        $range = str_replace('bytes=', '', $range);
        $rangeParts = explode('-', $range);
        $start = intval($rangeParts[0]);
        if (isset($rangeParts[1]) && is_numeric($rangeParts[1])) {
            $end = intval($rangeParts[1]);
        }
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$fileSize");
    } else {
        header('HTTP/1.1 200 OK');
    }

    $length = $end - $start + 1;
    header("Content-Length: $length");
    header("Content-Type: video/mp4"); // Idealmente detectar mime type real
    header("Accept-Ranges: bytes");

    $fp = fopen($filePath, 'rb');
    fseek($fp, $start);
    
    // Buffer optimizado (8KB a 16KB suele ser bueno para PHP)
    $bufferSize = 16384; 
    $bytesSent = 0;
    
    while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
        if ($bytesSent >= $length) break;
        
        $readSize = min($bufferSize, $length - $bytesSent);
        echo fread($fp, $readSize);
        flush(); // Forzar envío al cliente
        $bytesSent += $readSize;
    }
    fclose($fp);
} else {
    header('HTTP/1.1 501 Not Implemented');
    exit('Tipo de almacenamiento no soportado por el proxy');
}
?>