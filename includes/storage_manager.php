<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../config/storage.php';

class StorageManager {
    private $activeStorage;
    private $s3Client = null;
    private $userId = null;
    
    public function __construct($userId = null) {
        $this->userId = $userId;
        $this->activeStorage = $this->getActiveStorage();
        
        // Inicializamos el cliente S3 para la config activa por defecto
        if ($this->activeStorage && in_array($this->activeStorage['storage_type'], ['contabo', 'wasabi', 'aws'])) {
            $this->initS3Client();
        }
    }
    
    private function getActiveStorage() {
        if ($this->userId) {
            $stmt = db()->prepare("SELECT * FROM storage_config WHERE user_id = ? AND is_active = 1 LIMIT 1");
            try {
                $stmt->execute([$this->userId]);
                $config = $stmt->fetch();
                if ($config) return $config;
            } catch (Exception $e) {}
        }
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE (user_id IS NULL OR user_id = 0) AND is_active = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Nueva función para obtener config específica para borrar archivos viejos
    private function getConfigForType($type) {
        if ($this->userId) {
            $stmt = db()->prepare("SELECT * FROM storage_config WHERE user_id = ? AND storage_type = ? LIMIT 1");
            $stmt->execute([$this->userId, $type]);
            $config = $stmt->fetch();
            if ($config) return $config;
        }
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE (user_id IS NULL OR user_id = 0) AND storage_type = ? LIMIT 1");
        $stmt->execute([$type]);
        return $stmt->fetch();
    }

    private function initS3Client() {
        $region = !empty($this->activeStorage['region']) ? $this->activeStorage['region'] : 'us-east-1';
        $this->s3Client = new SimpleS3ClientV4(
            $this->activeStorage['access_key'],
            $this->activeStorage['secret_key'],
            $this->activeStorage['endpoint'],
            $region
        );
    }
    
    public function getActiveStorageType() {
        return $this->activeStorage ? $this->activeStorage['storage_type'] : 'local';
    }
    
    public function uploadFile($file, $targetName = null) {
        if (!$this->activeStorage) $this->activeStorage = ['storage_type' => 'local'];
        
        $fileName = $targetName ?: uniqid() . '_' . basename($file['name']);
        $filePath = $file['tmp_name'];
        
        if (!is_uploaded_file($filePath)) return ['success' => false, 'error' => 'Archivo inválido'];

        switch ($this->activeStorage['storage_type']) {
            case 'contabo':
            case 'wasabi':
            case 'aws':
                return $this->uploadToS3($filePath, $fileName, $file['type']);
            case 'local':
            default:
                return $this->uploadToLocal($filePath, $fileName);
        }
    }
    
    private function uploadToS3($filePath, $fileName, $mimeType) {
        try {
            $bucket = $this->activeStorage['bucket'];
            $prefix = ($this->userId) ? "videos/{$this->userId}/" : "videos/";
            $key = $prefix . $fileName;
            
            $result = $this->s3Client->putObject($bucket, $key, $filePath, $mimeType);
            
            if ($result) {
                return [
                    'success' => true,
                    'path' => $key,
                    'url' => rtrim($this->activeStorage['endpoint'], '/') . '/' . $bucket . '/' . $key
                ];
            }
            return ['success' => false, 'error' => 'Fallo subida a nube'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function uploadToLocal($filePath, $fileName) {
        $safeFileName = basename($fileName);
        $targetDir = defined('VIDEO_PATH') ? VIDEO_PATH : dirname(__DIR__) . '/../uploads/videos/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        
        $targetPath = $targetDir . $safeFileName;
        if (move_uploaded_file($filePath, $targetPath)) {
            return ['success' => true, 'path' => $safeFileName, 'url' => '/uploads/videos/' . $safeFileName];
        }
        return ['success' => false, 'error' => 'Error de permisos locales'];
    }
    
    // CORRECCIÓN PRINCIPAL: Aceptar $specificStorageType
    public function deleteFile($path, $specificStorageType = null) {
        // Determinamos qué configuración usar
        $config = $this->activeStorage;
        if ($specificStorageType) {
            $config = $this->getConfigForType($specificStorageType);
        }
        
        // Si no hay config (ej: es local por defecto), forzamos local
        if (!$config) $config = ['storage_type' => 'local'];
        
        try {
            if (in_array($config['storage_type'], ['contabo', 'wasabi', 'aws'])) {
                // Creamos un cliente temporal con las credenciales CORRECTAS para este archivo
                $client = new SimpleS3ClientV4(
                    $config['access_key'],
                    $config['secret_key'],
                    $config['endpoint'],
                    $config['region'] ?? 'us-east-1'
                );
                return $client->deleteObject($config['bucket'], $path);
                
            } else {
                // Borrado Local
                $targetDir = defined('VIDEO_PATH') ? VIDEO_PATH : dirname(__DIR__) . '/../uploads/videos/';
                // Si el path viene como "videos/archivo.mp4" (S3 style) y es local, extraemos solo el nombre
                $safePath = basename($path);
                $fullPath = $targetDir . $safePath;
                
                if (file_exists($fullPath)) return unlink($fullPath);
                return true;
            }
        } catch (Exception $e) {
            error_log("Delete Error: " . $e->getMessage());
            return false;
        }
    }

    public function getFileUrl($path) { return null; }
}

class SimpleS3ClientV4 {
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $region;
    
    public function __construct($accessKey, $secretKey, $endpoint, $region) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->endpoint = rtrim($endpoint, '/');
        $this->region = $region;
    }
    
    public function putObject($bucket, $key, $filePath, $contentType = 'application/octet-stream') {
        $url = $this->endpoint . '/' . $bucket . '/' . $key;
        $fileSize = filesize($filePath);
        $fp = fopen($filePath, 'r');
        
        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => $fileSize,
            'Host' => parse_url($this->endpoint, PHP_URL_HOST),
            'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD'
        ];
        
        $signedHeaders = $this->getSignatureV4('PUT', '/' . $bucket . '/' . $key, [], $headers);
        $curlHeaders = [];
        foreach ($signedHeaders as $k => $v) $curlHeaders[] = "$k: $v";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileSize);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        
        return ($code >= 200 && $code < 300);
    }
    
    public function deleteObject($bucket, $key) {
        $url = $this->endpoint . '/' . $bucket . '/' . $key;
        $headers = ['Host' => parse_url($this->endpoint, PHP_URL_HOST), 'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'];
        $signedHeaders = $this->getSignatureV4('DELETE', '/' . $bucket . '/' . $key, [], $headers);
        $curlHeaders = [];
        foreach ($signedHeaders as $k => $v) $curlHeaders[] = "$k: $v";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
        return true;
    }
    
    private function getSignatureV4($method, $uri, $queryParams, $headers) {
        $service = 's3';
        $algorithm = 'AWS4-HMAC-SHA256';
        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        $headers['x-amz-date'] = $amzDate;
        
        ksort($headers, SORT_STRING | SORT_FLAG_CASE);
        $canonicalHeaders = ''; $signedHeaders = [];
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
        $headers['Authorization'] = "$algorithm Credential={$this->accessKey}/$credentialScope, SignedHeaders=$signedHeadersString, Signature=$signature";
        return $headers;
    }
}
?>
