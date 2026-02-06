<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../config/storage.php';

class StorageManager {
    private $activeStorage;
    private $s3Client = null;
    
    public function __construct() {
        $this->activeStorage = $this->getActiveStorage();
        
        if ($this->activeStorage && $this->activeStorage['storage_type'] === 'contabo') {
            $this->initS3Client();
        }
    }
    
    private function getActiveStorage() {
        $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
        return $stmt->fetch();
    }
    
    private function initS3Client() {
        $this->s3Client = new SimpleS3Client(
            $this->activeStorage['access_key'],
            $this->activeStorage['secret_key'],
            $this->activeStorage['endpoint'],
            $this->activeStorage['region']
        );
    }
    
    public function uploadFile($file, $targetName = null) {
        if (!$this->activeStorage) {
            throw new Exception('No hay almacenamiento activo configurado');
        }
        
        $fileName = $targetName ?: uniqid() . '_' . $file['name'];
        $filePath = $file['tmp_name'];
        
        switch ($this->activeStorage['storage_type']) {
            case 'contabo':
                return $this->uploadToContabo($filePath, $fileName, $file['type']);
                
            case 'local':
                return $this->uploadToLocal($filePath, $fileName);
                
            default:
                throw new Exception('Tipo de almacenamiento no soportado');
        }
    }
    
    private function uploadToContabo($filePath, $fileName, $mimeType) {
        try {
            $result = $this->s3Client->putObject(
                $this->activeStorage['bucket'],
                'videos/' . $fileName,
                $filePath,
                $mimeType
            );
            
            return [
                'success' => true,
                'path' => 'videos/' . $fileName,
                'url' => $this->activeStorage['endpoint'] . '/' . $this->activeStorage['bucket'] . '/videos/' . $fileName
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function uploadToLocal($filePath, $fileName) {
        $targetPath = VIDEO_PATH . $fileName;
        
        if (move_uploaded_file($filePath, $targetPath)) {
            return [
                'success' => true,
                'path' => 'videos/' . $fileName,
                'url' => '/uploads/videos/' . $fileName
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Error al mover archivo'
        ];
    }
    
    public function deleteFile($path) {
        if (!$this->activeStorage) {
            return false;
        }
        
        switch ($this->activeStorage['storage_type']) {
            case 'contabo':
                return $this->s3Client->deleteObject(
                    $this->activeStorage['bucket'],
                    $path
                );
                
            case 'local':
                $fullPath = UPLOAD_PATH . $path;
                return file_exists($fullPath) && unlink($fullPath);
                
            default:
                return false;
        }
    }
    
    public function getFileUrl($path) {
        if (!$this->activeStorage) {
            return null;
        }
        
        switch ($this->activeStorage['storage_type']) {
            case 'contabo':
                return $this->activeStorage['endpoint'] . '/' . 
                       $this->activeStorage['bucket'] . '/' . $path;
                
            case 'local':
                return '/uploads/' . $path;
                
            default:
                return null;
        }
    }
}

class SimpleS3Client {
    private $accessKey;
    private $secretKey;
    private $endpoint;
    private $region;
    
    public function __construct($accessKey, $secretKey, $endpoint, $region) {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->endpoint = $endpoint;
        $this->region = $region;
    }
    
    public function putObject($bucket, $key, $filePath, $contentType = 'application/octet-stream') {
        $url = $this->endpoint . '/' . $bucket . '/' . $key;
        $date = gmdate('D, d M Y H:i:s T');
        $fileContent = file_get_contents($filePath);
        $contentMd5 = base64_encode(md5($fileContent, true));
        
        $stringToSign = "PUT\n{$contentMd5}\n{$contentType}\n{$date}\n/{$bucket}/{$key}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));
        
        $headers = [
            'Host: ' . parse_url($this->endpoint, PHP_URL_HOST),
            'Date: ' . $date,
            'Content-Type: ' . $contentType,
            'Content-MD5: ' . $contentMd5,
            'Authorization: AWS ' . $this->accessKey . ':' . $signature,
            'Content-Length: ' . strlen($fileContent)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        
        throw new Exception('Error uploading to S3: HTTP ' . $httpCode);
    }
    
    public function deleteObject($bucket, $key) {
        $url = $this->endpoint . '/' . $bucket . '/' . $key;
        $date = gmdate('D, d M Y H:i:s T');
        
        $stringToSign = "DELETE\n\n\n{$date}\n/{$bucket}/{$key}";
        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));
        
        $headers = [
            'Host: ' . parse_url($this->endpoint, PHP_URL_HOST),
            'Date: ' . $date,
            'Authorization: AWS ' . $this->accessKey . ':' . $signature
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
}