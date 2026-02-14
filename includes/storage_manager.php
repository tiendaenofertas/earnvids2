<?php
// includes/storage_manager.php - Gestor Multi-Storage Blindado
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../config/storage.php';

/**
 * Cliente S3 Ligero V4 compatible con Wasabi, Contabo y AWS.
 */
if (!class_exists('SimpleS3ClientV4')) {
    class SimpleS3ClientV4 {
        private $accessKey;
        private $secretKey;
        private $endpoint;
        private $region;

        public function __construct($ak, $sk, $ep, $reg) {
            $this->accessKey = $ak;
            $this->secretKey = $sk;
            
            // --- CORRECCIÓN CRÍTICA DE PROTOCOLO ---
            // Si el endpoint viene sin https://, parse_url falla y rompe la firma.
            // Lo corregimos aquí mismo para prevenir errores 400.
            $ep = trim($ep);
            if (!preg_match("~^https?://~i", $ep)) {
                $ep = "https://" . $ep;
            }
            
            $this->endpoint = rtrim($ep, '/');
            $this->region = $reg ?: 'us-east-1';
        }

        public function put($bucket, $key, $filePath, $mimeType) {
            return $this->putObject($bucket, $key, $filePath, $mimeType);
        }

        public function putObject($bucket, $key, $filePath, $mimeType = 'application/octet-stream') {
            $url = $this->endpoint . '/' . $bucket . '/' . $key;
            
            if (!file_exists($filePath)) return false;

            $size = filesize($filePath);
            $host = parse_url($this->endpoint, PHP_URL_HOST);
            
            // Protección extra: Si parse_url falla, usamos el endpoint tal cual (sin protocolo)
            if (!$host) $host = str_replace(['https://', 'http://'], '', $this->endpoint);

            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $size,
                'Host' => $host,
                'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
                'x-amz-date' => gmdate('Ymd\THis\Z')
            ];

            $auth = $this->getAuth('PUT', '/' . $bucket . '/' . $key, $headers);
            $headers['Authorization'] = $auth;

            $curlHeaders = [];
            foreach ($headers as $k => $v) $curlHeaders[] = "$k: $v";

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_PUT => true,
                CURLOPT_INFILE => fopen($filePath, 'r'),
                CURLOPT_INFILESIZE => $size,
                CURLOPT_HTTPHEADER => $curlHeaders,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return true;
            } else {
                // Esto enviará el error real al log de PHP
                error_log("S3 Upload Failed: Code $httpCode. Response: $response. Curl Error: $err");
                return false;
            }
        }

        public function delete($bucket, $key) {
            return $this->deleteObject($bucket, $key);
        }

        public function deleteObject($bucket, $key) {
            $url = $this->endpoint . '/' . $bucket . '/' . $key;
            $host = parse_url($this->endpoint, PHP_URL_HOST);
            if (!$host) $host = str_replace(['https://', 'http://'], '', $this->endpoint);

            $headers = [
                'Host' => $host,
                'x-amz-date' => gmdate('Ymd\THis\Z'),
                'x-amz-content-sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'
            ];

            $auth = $this->getAuth('DELETE', '/' . $bucket . '/' . $key, $headers);
            $headers['Authorization'] = $auth;

            $curlHeaders = [];
            foreach ($headers as $k => $v) $curlHeaders[] = "$k: $v";

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => $curlHeaders,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_exec($ch);
            curl_close($ch);

            return ($httpCode >= 200 && $httpCode < 300);
        }

        private function getAuth($method, $uri, $headers) {
            ksort($headers);
            $canonicalHeaders = '';
            $signedHeaders = [];
            foreach ($headers as $key => $value) {
                $lowerKey = strtolower($key);
                $canonicalHeaders .= "$lowerKey:" . trim($value) . "\n";
                $signedHeaders[] = $lowerKey;
            }
            $signedHeadersString = implode(';', $signedHeaders);
            
            $payloadHash = $headers['x-amz-content-sha256'];
            $canonicalRequest = "$method\n$uri\n\n$canonicalHeaders\n$signedHeadersString\n$payloadHash";
            
            $date = $headers['x-amz-date'];
            $shortDate = substr($date, 0, 8);
            $scope = "$shortDate/{$this->region}/s3/aws4_request";
            $algorithm = 'AWS4-HMAC-SHA256';
            $stringToSign = "$algorithm\n$date\n$scope\n" . hash('sha256', $canonicalRequest);
            
            $kSecret = 'AWS4' . $this->secretKey;
            $kDate = hash_hmac('sha256', $shortDate, $kSecret, true);
            $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
            $kService = hash_hmac('sha256', 's3', $kRegion, true);
            $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
            $signature = hash_hmac('sha256', $stringToSign, $kSigning);
            
            return "$algorithm Credential={$this->accessKey}/$scope, SignedHeaders=$signedHeadersString, Signature=$signature";
        }
    }
}

class StorageManager {
    private $activeConfig; 
    private $s3Client = null;
    private $userId = null;
    
    public function __construct($userId = null) {
        $this->userId = $userId;
        $this->activeConfig = $this->loadActiveConfig();
        
        if ($this->activeConfig && in_array($this->activeConfig['storage_type'], ['contabo', 'wasabi', 'aws'])) {
            $this->initS3Client($this->activeConfig);
        }
    }
    
    private function loadActiveConfig() {
        if ($this->userId) {
            $stmt = db()->prepare("SELECT * FROM storage_users WHERE user_id = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$this->userId]);
            $conf = $stmt->fetch();
            if ($conf) return $conf;
        }
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    private function getConfigByType($type) {
        if ($this->userId) {
            $stmt = db()->prepare("SELECT * FROM storage_users WHERE user_id = ? AND storage_type = ? LIMIT 1");
            $stmt->execute([$this->userId, $type]);
            $conf = $stmt->fetch();
            if ($conf) return $conf;
        }
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE storage_type = ? LIMIT 1");
        $stmt->execute([$type]);
        return $stmt->fetch();
    }

    private function initS3Client($config) {
        // La validación de protocolo se hace ahora dentro de la clase S3,
        // pero pasamos los parámetros limpios por seguridad.
        $this->s3Client = new SimpleS3ClientV4(
            $config['access_key'], 
            $config['secret_key'], 
            $config['endpoint'], 
            $config['region']
        );
    }
    
    public function getActiveStorageType() {
        return $this->activeConfig ? $this->activeConfig['storage_type'] : 'local';
    }
    
    public function uploadFile($file, $targetName) {
        if (!$this->activeConfig) {
             // Fallback local si no hay config
             if (defined('VIDEO_PATH')) {
                 $path = VIDEO_PATH;
                 if (!is_dir($path)) @mkdir($path, 0755, true);
                 if(move_uploaded_file($file['tmp_name'], $path . $targetName)) {
                     return ['success'=>true, 'path'=>$targetName, 'url'=>''];
                 }
             }
             return ['success'=>false, 'error'=>'No hay almacenamiento activo.'];
        }
        
        $tmp = $file['tmp_name'];
        if (in_array($this->activeConfig['storage_type'], ['contabo','wasabi','aws'])) {
            $key = ($this->userId ? "videos/{$this->userId}/" : "videos/") . $targetName;
            
            if ($this->s3Client && $this->s3Client->put($this->activeConfig['bucket'], $key, $tmp, $file['type'])) {
                return ['success'=>true, 'path'=>$key, 'url'=>'']; 
            }
            return ['success'=>false, 'error'=>'Fallo al conectar con S3 (Revise credenciales/endpoint)'];
        } else {
            $path = defined('VIDEO_PATH') ? VIDEO_PATH : '../uploads/videos/';
            if (!is_dir($path)) @mkdir($path, 0755, true);
            if(move_uploaded_file($tmp, $path . $targetName)) return ['success'=>true, 'path'=>$targetName];
            return ['success'=>false, 'error'=>'Fallo subida local'];
        }
    }
    
    public function deleteFile($path, $type = null) {
        $config = $type ? $this->getConfigByType($type) : $this->activeConfig;
        
        // Intento de borrado local si no hay config de nube
        if (!$config || $type == 'local') {
             $local = (defined('VIDEO_PATH') ? VIDEO_PATH : '../uploads/videos/') . basename($path);
             if(file_exists($local)) unlink($local);
             return true;
        }
        
        if (in_array($config['storage_type'], ['contabo','wasabi','aws'])) {
            $client = new SimpleS3ClientV4($config['access_key'], $config['secret_key'], $config['endpoint'], $config['region']);
            return $client->delete($config['bucket'], $path);
        }
        return false;
    }
}
?>
