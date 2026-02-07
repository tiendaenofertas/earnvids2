<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../config/storage.php';

class StorageManager {
    private $activeStorage;
    private $s3Client = null;
    
    public function __construct() {
        $this->activeStorage = $this->getActiveStorage();
        
        if ($this->activeStorage && in_array($this->activeStorage['storage_type'], ['contabo', 'wasabi', 'aws'])) {
            $this->initS3Client();
        }
    }
    
    private function getActiveStorage() {
        // Optimización: Usamos prepare para evitar inyecciones teóricas aunque no haya inputs aquí
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    private function initS3Client() {
        // Corrección: Aseguramos que la región tenga un valor por defecto si está vacía
        $region = !empty($this->activeStorage['region']) ? $this->activeStorage['region'] : 'us-east-1';
        
        $this->s3Client = new SimpleS3ClientV4(
            $this->activeStorage['access_key'],
            $this->activeStorage['secret_key'],
            $this->activeStorage['endpoint'],
            $region
        );
    }
    
    public function getActiveStorageType() {
        return $this->activeStorage ? $this->activeStorage['storage_type'] : null;
    }
    
    public function uploadFile($file, $targetName = null) {
        if (!$this->activeStorage) {
            return ['success' => false, 'error' => 'No hay almacenamiento activo configurado'];
        }
        
        // Saneamiento básico del nombre
        $fileName = $targetName ?: uniqid() . '_' . basename($file['name']);
        $filePath = $file['tmp_name'];
        
        // Validación de seguridad: Verificar que el archivo temporal realmente fue subido
        if (!is_uploaded_file($filePath)) {
             return ['success' => false, 'error' => 'Intento de subida de archivo inválido'];
        }

        switch ($this->activeStorage['storage_type']) {
            case 'contabo':
            case 'wasabi':
            case 'aws':
                return $this->uploadToS3($filePath, $fileName, $file['type']);
                
            case 'local':
                return $this->uploadToLocal($filePath, $fileName);
                
            default:
                return ['success' => false, 'error' => 'Tipo de almacenamiento no soportado'];
        }
    }
    
    private function uploadToS3($filePath, $fileName, $mimeType) {
        try {
            // Normalizar el bucket y path
            $bucket = $this->activeStorage['bucket'];
            $key = 'videos/' . $fileName;
            
            $result = $this->s3Client->putObject(
                $bucket,
                $key,
                $filePath,
                $mimeType
            );
            
            if ($result) {
                return [
                    'success' => true,
                    'path' => $key,
                    'url' => $this->activeStorage['endpoint'] . '/' . $bucket . '/' . $key
                ];
            }
            
            return ['success' => false, 'error' => 'Fallo la subida a S3 (Sin respuesta positiva)'];
            
        } catch (Exception $e) {
            error_log("S3 Upload Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function uploadToLocal($filePath, $fileName) {
        // Seguridad: basename previene Directory Traversal
        $safeFileName = basename($fileName);
        $targetPath = VIDEO_PATH . $safeFileName;
        
        // Asegurar que el directorio existe
        if (!is_dir(VIDEO_PATH)) {
            mkdir(VIDEO_PATH, 0755, true);
        }
        
        if (move_uploaded_file($filePath, $targetPath)) {
            return [
                'success' => true,
                'path' => 'videos/' . $safeFileName,
                'url' => '/uploads/videos/' . $safeFileName
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Error al mover archivo localmente'
        ];
    }
    
    public function deleteFile($path) {
        if (!$this->activeStorage) {
            return false;
        }
        
        try {
            switch ($this->activeStorage['storage_type']) {
                case 'contabo':
                case 'wasabi':
                case 'aws':
                    return $this->s3Client->deleteObject(
                        $this->activeStorage['bucket'],
                        $path
                    );
                    
                case 'local':
                    // Seguridad: Prevenir borrado arbitrario fuera del directorio uploads
                    $safePath = basename($path);
                    // Asumimos que $path viene como "videos/archivo.mp4", extraemos solo el archivo si es local
                    if (strpos($path, '/') !== false) {
                        $safePath = basename($path);
                    }
                    
                    $fullPath = VIDEO_PATH . $safePath;
                    
                    if (file_exists($fullPath)) {
                        return unlink($fullPath);
                    }
                    return true; // Si no existe, consideramos que ya está borrado
                    
                default:
                    return false;
            }
        } catch (Exception $e) {
            error_log("Delete Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getFileUrl($path) {
        if (!$this->activeStorage) {
            return null;
        }
        
        switch ($this->activeStorage['storage_type']) {
            case 'contabo':
            case 'wasabi':
            case 'aws':
                return $this->activeStorage['endpoint'] . '/' . 
                       $this->activeStorage['bucket'] . '/' . $path;
                
            case 'local':
                return '/uploads/' . $path;
                
            default:
                return null;
        }
    }
}

/**
 * Cliente S3 Minimalista con soporte para Signature V4.
 * Necesario para Wasabi y regiones nuevas de AWS.
 */
class SimpleS3ClientV4 {
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $region;
    
    public function __construct($accessKey, $secretKey, $endpoint, $region) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        // Eliminar trailing slashes del endpoint
        $this->endpoint = rtrim($endpoint, '/');
        $this->region = $region;
    }
    
    public function putObject($bucket, $key, $filePath, $contentType = 'application/octet-stream') {
        $uri = '/' . $bucket . '/' . $key;
        $url = $this->endpoint . $uri;
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("No se pudo leer el archivo local: $filePath");
        }
        
        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => strlen($content)
        ];
        
        $signedHeaders = $this->getSignatureV4('PUT', $uri, [], $headers, $content);
        
        return $this->executeRequest('PUT', $url, $signedHeaders, $content);
    }
    
    public function deleteObject($bucket, $key) {
        $uri = '/' . $bucket . '/' . $key;
        $url = $this->endpoint . $uri;
        
        $signedHeaders = $this->getSignatureV4('DELETE', $uri, [], [], '');
        
        return $this->executeRequest('DELETE', $url, $signedHeaders);
    }
    
    private function executeRequest($method, $url, $headers, $content = '') {
        $ch = curl_init();
        
        // Convertir headers array asoc a formato curl
        $curlHeaders = [];
        foreach ($headers as $k => $v) {
            $curlHeaders[] = "$k: $v";
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // En producción idealmente true con certificados correctos
        curl_setopt($ch, CURLOPT_HEADER, true); // Necesitamos ver headers de respuesta para debug
        
        if ($content && $method === 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        
        throw new Exception("S3 Error [$httpCode]: $error");
    }
    
    /**
     * Genera AWS Signature Version 4
     */
    private function getSignatureV4($method, $uri, $queryParams, $headers, $payload) {
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        
        // 1. Canonical Request
        $canonicalUri = $uri;
        $canonicalQueryString = ''; // Asumimos query params vacíos para upload/delete simple
        
        // Asegurar host header
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        $headers['Host'] = $host;
        $headers['x-amz-date'] = $amzDate;
        $headers['x-amz-content-sha256'] = hash('sha256', $payload);
        
        // Ordenar headers
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
        
        $canonicalRequest = "$method\n$canonicalUri\n$canonicalQueryString\n$canonicalHeaders\n$signedHeadersString\n$payloadHash";
        
        // 2. String to Sign
        $credentialScope = "$dateStamp/{$this->region}/$service/aws4_request";
        $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);
        
        // 3. Calculate Signature
        $kSecret = 'AWS4' . $this->secretKey;
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        // 4. Authorization Header
        $authorization = "$algorithm Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeadersString, Signature=$signature";
        
        $headers['Authorization'] = $authorization;
        
        return $headers;
    }
}
?>
