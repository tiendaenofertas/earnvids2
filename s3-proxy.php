<?php
// s3-proxy.php - Proxy Inteligente Multiusuario con Soporte S3 V4
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Configuración para Streaming de alto rendimiento
@set_time_limit(0);
ini_set('memory_limit', '1024M'); // Aumentado para manejar buffers grandes si es necesario

/**
 * Clase Proxy S3 V4
 * Maneja la firma AWS Signature V4 y el streaming directo al cliente
 */
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
        $this->region = $region ?: 'us-east-1';
        $this->bucket = $bucket;
    }

    public function stream($objectPath) {
        // Limpieza del path para evitar dobles slashes
        $objectPath = ltrim($objectPath, '/');
        $uri = '/' . $this->bucket . '/' . $objectPath;
        $url = $this->endpoint . $uri;

        // Headers base
        $headers = [];
        
        // --- Forwarding de Headers Críticos para Streaming de Video ---
        
        // 1. Range (Permite adelantar/retroceder el video)
        if (isset($_SERVER['HTTP_RANGE'])) {
            $headers['Range'] = $_SERVER['HTTP_RANGE'];
        }

        // 2. Cache (Mejora rendimiento en cliente)
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
            CURLOPT_RETURNTRANSFER => false, // FALSE = Escribe directamente al output buffer (Streaming real)
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false, // Cambiar a true en producción con certificados válidos
            CURLOPT_HEADER => false, // No incluir headers en el body
            // Callback para procesar headers de respuesta de S3 y enviarlos al navegador
            CURLOPT_HEADERFUNCTION => function($ch, $header) {
                $len = strlen($header);
                $headerParts = explode(':', $header, 2);
                if (count($headerParts) < 2) return $len;

                $name = strtolower(trim($headerParts[0]));
                $value = trim($headerParts[1]);

                // Headers permitidos para pasar al cliente (Seguridad y Funcionalidad)
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

        // Ejecutar request
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Manejo de errores S3
        if (!$success || $httpCode >= 400) {
            if (!headers_sent()) {
                if ($httpCode === 404) {
                    header("HTTP/1.1 404 Not Found");
                    echo "El archivo no se encuentra en el bucket configurado.";
                } elseif ($httpCode === 403) {
                    header("HTTP/1.1 403 Forbidden");
                    echo "Acceso denegado. Verifique las credenciales y permisos del bucket.";
                } else {
                    header("HTTP/1.1 500 Internal Server Error");
                    echo "Error en el servidor de almacenamiento ($httpCode).";
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

        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $headers['Host'] = $host;
        $headers['x-amz-date'] = $amzDate;
        $headers['x-amz-content-sha256'] = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'; // Hash de payload vacío (GET)

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

// === Lógica Principal del Proxy ===

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('HTTP/1.1 400 Bad Request');
    exit('Falta el código de video');
}

// 1. Obtener información del video Y SU DUEÑO (user_id)
$stmt = db()->prepare("
    SELECT id, storage_type, storage_path, status, user_id 
    FROM videos 
    WHERE embed_code = ? LIMIT 1
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video || $video['status'] !== 'active') {
    header('HTTP/1.1 404 Not Found');
    exit('Video no disponible o eliminado');
}

// 2. Obtener la Configuración de Almacenamiento CORRECTA
// Lógica Jerárquica: Usuario > Global

$storageType = $video['storage_type'];
$userId = $video['user_id'];
$storage = null;

// Intento A: Buscar configuración privada del usuario dueño del video
$stmt = db()->prepare("
    SELECT * FROM storage_config 
    WHERE user_id = ? AND storage_type = ? AND is_active = 1 
    LIMIT 1
");
$stmt->execute([$userId, $storageType]);
$storage = $stmt->fetch();

// Intento B: Si no tiene config privada, buscar configuración global (Admin)
if (!$storage) {
    $stmt = db()->prepare("
        SELECT * FROM storage_config 
        WHERE user_id IS NULL AND storage_type = ? AND is_active = 1 
        LIMIT 1
    ");
    $stmt->execute([$storageType]);
    $storage = $stmt->fetch();
}

// Validación final de configuración
if (!$storage && in_array($storageType, ['contabo', 'wasabi', 'aws'])) {
    // Si es un tipo nube y no hay credenciales, error fatal
    error_log("Error Crítico: No se encontraron credenciales para Video ID {$video['id']} (User $userId, Type $storageType)");
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error de configuración de almacenamiento: Credenciales no encontradas.');
}

// 3. Registrar vista (Throttling básico)
$viewKey = 'viewed_' . $video['id'];
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION[$viewKey])) {
    if (function_exists('incrementViews')) {
        incrementViews($video['id']);
    }
    $_SESSION[$viewKey] = true;
}

// Headers Globales
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');

// 4. Ejecutar Streaming según tipo
if (in_array($storageType, ['contabo', 'wasabi', 'aws'])) {
    
    // Iniciar Proxy S3 con las credenciales obtenidas (Privadas o Globales)
    $proxy = new S3ProxyV4(
        $storage['access_key'],
        $storage['secret_key'],
        $storage['endpoint'],
        $storage['region'],
        $storage['bucket']
    );
    
    $proxy->stream($video['storage_path']);

} elseif ($storageType === 'local') {
    // Fallback a Streaming local por si acaso se llama este archivo para local
    $filePath = 'uploads/' . $video['storage_path'];
    
    if (!file_exists($filePath)) {
        header('HTTP/1.1 404 Not Found');
        exit('Archivo local no encontrado');
    }

    require_once 'stream.php'; // Reutilizar lógica si existe, o implementar básica aquí
    // Nota: Normalmente stream.php maneja esto mejor, pero implementamos un fallback rápido:
    header("Content-Type: video/mp4");
    header("Content-Length: " . filesize($filePath));
    readfile($filePath);
    
} else {
    header('HTTP/1.1 501 Not Implemented');
    exit('Tipo de almacenamiento no soportado');
}
?>
