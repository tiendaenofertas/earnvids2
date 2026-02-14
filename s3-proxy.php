<?php
// s3-proxy.php - Proxy Inteligente con Protección de Dominios (MODO ESTRICTO)
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// --- MEJORAS DE RENDIMIENTO ---
@set_time_limit(0);
ini_set('memory_limit', '1024M'); 

// Clase Proxy S3 V4 (Optimizada)
class S3ProxyV4 {
    private $accessKey, $secretKey, $endpoint, $region, $bucket;
    
    public function __construct($ak, $sk, $ep, $rg, $bk) {
        $this->accessKey = $ak; 
        $this->secretKey = $sk; 
        $this->endpoint = rtrim($ep, '/'); 
        $this->region = $rg ?: 'us-east-1'; 
        $this->bucket = $bk;
    }
    
    public function stream($path) {
        $path = ltrim($path, '/');
        $uri = '/' . $this->bucket . '/' . $path;
        $url = $this->endpoint . $uri;
        
        $headers = []; 
        if (isset($_SERVER['HTTP_RANGE'])) $headers['Range'] = $_SERVER['HTTP_RANGE'];
        
        $signedHeaders = $this->sign('GET', $uri, $headers);
        $ch = curl_init();
        
        $curlHeaders = [];
        foreach ($signedHeaders as $k => $v) $curlHeaders[] = "$k: $v";
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url, 
            CURLOPT_HTTPHEADER => $curlHeaders, 
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true, 
            CURLOPT_SSL_VERIFYPEER => false, 
            CURLOPT_HEADER => false,
            CURLOPT_HEADERFUNCTION => function($ch, $h) {
                $p = explode(':', $h, 2);
                if (count($p) < 2) return strlen($h);
                $n = strtolower(trim($p[0])); 
                $v = trim($p[1]);
                if (in_array($n, ['content-type','content-length','content-range','accept-ranges','etag','last-modified'])) {
                    header("$n: $v");
                }
                return strlen($h);
            }
        ]);
        
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if (!$res || $code >= 400) {
            if(!headers_sent()) { 
                http_response_code($code == 404 ? 404 : 500); 
                echo "Error streaming ($code)"; 
            }
            exit;
        }
    }
    
    private function sign($method, $uri, $headers) {
        $algo = 'AWS4-HMAC-SHA256'; $now = time();
        $date = gmdate('Ymd\THis\Z', $now); $day = gmdate('Ymd', $now);
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        
        $headers['Host'] = $host;
        $headers['x-amz-date'] = $date;
        $headers['x-amz-content-sha256'] = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
        ksort($headers);
        
        $canH = ''; $signH = [];
        foreach($headers as $k=>$v) { $kl=strtolower($k); $canH.="$kl:".trim($v)."\n"; $signH[]=$kl; }
        $signHs = implode(';', $signH);
        
        $req = "$method\n$uri\n\n$canH\n$signHs\n".$headers['x-amz-content-sha256'];
        $scope = "$day/{$this->region}/s3/aws4_request";
        $str = "$algo\n$date\n$scope\n".hash('sha256', $req);
        
        $k = hash_hmac('sha256', 'aws4_request', hash_hmac('sha256', 's3', hash_hmac('sha256', $this->region, hash_hmac('sha256', $day, 'AWS4'.$this->secretKey, true), true), true), true);
        $sig = hash_hmac('sha256', $str, $k);
        
        $headers['Authorization'] = "$algo Credential={$this->accessKey}/$scope, SignedHeaders=$signHs, Signature=$sig";
        return $headers;
    }
}

// === MAIN ===

$v = $_GET['v'] ?? '';
if (!$v) { 
    header('HTTP/1.1 400 Bad Request'); 
    exit('Bad Request'); 
}

// 1. OBTENER VIDEO
$stmt = db()->prepare("SELECT id, storage_type, storage_path, status, user_id FROM videos WHERE embed_code = ? LIMIT 1");
$stmt->execute([$v]);
$video = $stmt->fetch();

if (!$video || $video['status'] !== 'active') { 
    header('HTTP/1.1 404 Not Found'); 
    exit('Video not found'); 
}

// --- 2. SEGURIDAD: CONTROL DE DOMINIOS (MODO ESTRICTO) ---
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$refererHost = '';

if ($referer) {
    $parsedUrl = parse_url($referer);
    $refererHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
}

$selfHost = parse_url(SITE_URL, PHP_URL_HOST);
$selfHost = strtolower($selfHost ?? '');

$isAllowed = false;

// [CAMBIO IMPORTANTE]: Si no hay referer (acceso directo en navegador), BLOQUEAR.
if (empty($refererHost)) {
    $isAllowed = false; // Bloquea si copian y pegan el link en el navegador
} 
// REGLA A: Permitir si viene de nuestro propio sitio
elseif ($refererHost === $selfHost) {
    $isAllowed = true;
} 
else {
    // REGLA B: Verificar lista blanca del USUARIO (Dueño del video)
    $stmt = db()->prepare("SELECT domain FROM user_domains WHERE user_id = ?");
    $stmt->execute([$video['user_id']]);
    $userDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userDomains)) {
        // El usuario tiene dominios configurados, SOLO permitimos esos
        foreach ($userDomains as $d) {
            if (strtolower(trim($d)) === $refererHost) {
                $isAllowed = true;
                break;
            }
        }
    } else {
        // REGLA C: El usuario NO tiene dominios configurados. Usamos configuración GLOBAL.
        if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
             foreach (ALLOWED_DOMAINS as $globalDomain) {
                if (strtolower(trim($globalDomain)) === $refererHost) {
                    $isAllowed = true;
                    break;
                }
            }
        } else {
            // REGLA D: Nadie restringió nada. Es público.
            $isAllowed = true;
        }
    }
}

// BLOQUEO FINAL
if (!$isAllowed) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access Denied');
}
// -----------------------------------------------------

// 3. OBTENER CREDENCIALES
$storage = null;
$stmt = db()->prepare("SELECT * FROM storage_users WHERE user_id = ? AND storage_type = ? LIMIT 1");
$stmt->execute([$video['user_id'], $video['storage_type']]);
$storage = $stmt->fetch();

// Fallback Global
if (!$storage) {
    $stmt = db()->prepare("SELECT * FROM storage_config WHERE storage_type = ? LIMIT 1");
    $stmt->execute([$video['storage_type']]);
    $storage = $stmt->fetch();
}

if (!$storage && in_array($video['storage_type'], ['wasabi','contabo','aws'])) {
    header('HTTP/1.1 500 Error'); 
    exit('Credenciales no encontradas'); 
}

// 4. REGISTRAR VISTA
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["view_$v"])) {
    if (function_exists('incrementViews')) {
        incrementViews($video['id']);
    }
    $_SESSION["view_$v"] = true;
}

// 5. STREAM
if ($video['storage_type'] === 'local') {
    $file = 'uploads/' . $video['storage_path'];
    if (file_exists($file)) {
        header("Content-Type: video/mp4"); 
        header("Content-Length: ".filesize($file)); 
        readfile($file);
    } else { 
        header('HTTP/1.1 404 Not Found'); 
    }
} else {
    $proxy = new S3ProxyV4(
        $storage['access_key'], 
        $storage['secret_key'], 
        $storage['endpoint'], 
        $storage['region'], 
        $storage['bucket']
    );
    $proxy->stream($video['storage_path']);
}
?>
