<?php
// includes/storage_manager.php - Gestor Multi-Storage
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../config/storage.php';

class StorageManager {
    private $activeConfig; // Configuración ACTIVA para subir
    private $s3Client = null;
    private $userId = null;
    
    public function __construct($userId = null) {
        $this->userId = $userId;
        // Cargar configuración ACTIVA al iniciar (para uploads)
        $this->activeConfig = $this->loadActiveConfig();
        
        if ($this->activeConfig && in_array($this->activeConfig['storage_type'], ['contabo', 'wasabi', 'aws'])) {
            $this->initS3Client($this->activeConfig);
        }
    }
    
    private function loadActiveConfig() {
        if ($this->userId) {
            // Usuario: Buscar en storage_users el que tenga is_active=1
            $stmt = db()->prepare("SELECT * FROM storage_users WHERE user_id = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$this->userId]);
            $conf = $stmt->fetch();
            if ($conf) return $conf;
        }
        // Admin/Global fallback
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // Para borrados: obtener config específica aunque no esté activa
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
        // Clase simple inline para evitar dependencias externas en este archivo
        if(!class_exists('SimpleS3Uploader')) {
            class SimpleS3Uploader {
                private $ak, $sk, $ep, $reg;
                public function __construct($ak, $sk, $ep, $reg) { $this->ak=$ak; $this->sk=$sk; $this->ep=rtrim($ep,'/'); $this->reg=$reg; }
                
                public function put($bucket, $key, $file, $mime) {
                    $url = $this->ep . '/' . $bucket . '/' . $key;
                    $size = filesize($file);
                    $headers = [
                        'Content-Type' => $mime,
                        'Content-Length' => $size,
                        'Host' => parse_url($this->ep, PHP_URL_HOST),
                        'x-amz-content-sha256' => 'UNSIGNED-PAYLOAD',
                        'x-amz-date' => gmdate('Ymd\THis\Z')
                    ];
                    // Firma simplificada V4 (para brevedad, asumiendo funciona igual que en s3-proxy)
                    $auth = $this->getAuth('PUT', '/' . $bucket . '/' . $key, $headers);
                    $headers['Authorization'] = $auth;
                    
                    $ch = curl_init($url);
                    $h = []; foreach($headers as $k=>$v) $h[]="$k: $v";
                    curl_setopt_array($ch, [
                        CURLOPT_PUT=>true, CURLOPT_INFILE=>fopen($file,'r'), CURLOPT_INFILESIZE=>$size,
                        CURLOPT_HTTPHEADER=>$h, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false
                    ]);
                    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
                    return ($code >= 200 && $code < 300);
                }
                
                public function delete($bucket, $key) {
                    $url = $this->ep . '/' . $bucket . '/' . $key;
                    $headers = ['Host'=>parse_url($this->ep, PHP_URL_HOST), 'x-amz-date'=>gmdate('Ymd\THis\Z'), 'x-amz-content-sha256'=>'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'];
                    $headers['Authorization'] = $this->getAuth('DELETE', '/' . $bucket . '/' . $key, $headers);
                    
                    $ch = curl_init($url);
                    $h=[]; foreach($headers as $k=>$v) $h[]="$k: $v";
                    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST=>'DELETE', CURLOPT_HTTPHEADER=>$h, CURLOPT_RETURNTRANSFER=>true, CURLOPT_SSL_VERIFYPEER=>false]);
                    curl_exec($ch); curl_close($ch);
                    return true;
                }

                private function getAuth($m, $u, $h) {
                    ksort($h); $canH=''; $signH=[]; foreach($h as $k=>$v) { $lk=strtolower($k); $canH.="$lk:".trim($v)."\n"; $signH[]=$lk; }
                    $sH = implode(';', $signH);
                    $p = $h['x-amz-content-sha256'];
                    $req = "$m\n$u\n\n$canH\n$sH\n$p";
                    $ymd = gmdate('Ymd');
                    $scope = "$ymd/{$this->reg}/s3/aws4_request";
                    $str = "AWS4-HMAC-SHA256\n".$h['x-amz-date']."\n$scope\n".hash('sha256', $req);
                    $k = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 's3', hash_hmac('sha256', $this->reg, hash_hmac('sha256', $ymd, 'AWS4'.$this->sk, true), true), true), true);
                    return "AWS4-HMAC-SHA256 Credential={$this->ak}/$scope, SignedHeaders=$sH, Signature=".hash_hmac('sha256', $str, $k);
                }
            }
        }
        $this->s3Client = new SimpleS3Uploader($config['access_key'], $config['secret_key'], $config['endpoint'], $config['region']);
    }
    
    public function getActiveStorageType() {
        return $this->activeConfig ? $this->activeConfig['storage_type'] : 'local';
    }
    
    public function uploadFile($file, $targetName) {
        if (!$this->activeConfig) return ['success'=>false, 'error'=>'No hay almacenamiento activo'];
        
        $tmp = $file['tmp_name'];
        if (in_array($this->activeConfig['storage_type'], ['contabo','wasabi','aws'])) {
            $key = ($this->userId ? "videos/{$this->userId}/" : "videos/") . $targetName;
            if ($this->s3Client->put($this->activeConfig['bucket'], $key, $tmp, $file['type'])) {
                return ['success'=>true, 'path'=>$key, 'url'=>'']; // URL se genera dinámicamente
            }
            return ['success'=>false, 'error'=>'Fallo subida S3'];
        } else {
            // Local
            $path = defined('VIDEO_PATH') ? VIDEO_PATH : '../uploads/videos/';
            if(move_uploaded_file($tmp, $path . $targetName)) return ['success'=>true, 'path'=>$targetName];
            return ['success'=>false, 'error'=>'Fallo subida local'];
        }
    }
    
    public function deleteFile($path, $type = null) {
        $config = $type ? $this->getConfigByType($type) : $this->activeConfig;
        if (!$config) return false; // No hay credenciales para borrar
        
        if (in_array($config['storage_type'], ['contabo','wasabi','aws'])) {
            $client = new SimpleS3Uploader($config['access_key'], $config['secret_key'], $config['endpoint'], $config['region']);
            return $client->delete($config['bucket'], $path);
        } else {
            $local = (defined('VIDEO_PATH') ? VIDEO_PATH : '../uploads/videos/') . basename($path);
            if(file_exists($local)) unlink($local);
            return true;
        }
    }
}
?>