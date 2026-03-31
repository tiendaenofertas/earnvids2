<?php
declare(strict_types=1);

// Configuración de errores y sesión
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    if (PHP_VERSION_ID >= 70300) {
        ini_set('session.cookie_samesite', 'Strict');
    }
}

// ==========================================
// CONFIGURACIÓN DE PERMANENCIA
// ==========================================
define('MP4_VERSION', '2.2-PERMANENT');
define('MP4_TOKEN_LIFETIME', 315360000); 
define('MP4_MAX_REQUESTS_PER_IP', 200); 

// Claves de encriptación 
define('MP4_ENCRYPTION_KEY', 'mp4_secure_key_2025_' . hash('sha256', 'xzorra_protection'));
define('MP4_ENCRYPTION_IV', substr(hash('sha256', 'xzorra_iv_2025'), 0, 16));
define('MP4_HMAC_KEY', 'hmac_xzorra_2025_' . hash('sha256', 'protection_key'));

// Dominios permitidos (Locales)
define('MP4_ALLOWED_DOMAINS', [
    'xcuca.net', 'www.xcuca.net',
    'earnvids.xzorra.net',
    'xpro.xcuca.net', 'xpro.xcuca.com',
    'xcuca.com', 'www.xcuca.com',
    'localhost', '127.0.0.1'
]);

// ==========================================
// 🛡️ SISTEMA DE LICENCIA XVIDSPRO
// ==========================================
define('MAIN_SERVER_API', 'https://xvidspro.com/api/verify_license.php');
define('LICENSE_FILE', __DIR__ . '/license.key');

if (!function_exists('checkLicenseStatus')) {
    function checkLicenseStatus(string $licenseKey): array {
        $domain = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $ch = curl_init(MAIN_SERVER_API);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['license_key' => $licenseKey, 'domain' => $domain]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if (!$response) return ['status' => 'error', 'message' => 'Error de conexión con el servidor principal XVIDSPRO.'];
        $data = json_decode($response, true);
        return $data ?: ['status' => 'error', 'message' => 'Respuesta inválida del servidor central.'];
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        $length = strlen($needle);
        if ($length === 0) return true;
        return substr($haystack, -$length) === $needle;
    }
}

function setSecurityHeaders(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    $csp = "default-src 'self' 'unsafe-inline' 'unsafe-eval' * data: blob:;";
    header("Content-Security-Policy: $csp");
}

function validateReferer(): bool { return true; }

if (!function_exists('encodeSecure')) {
    function encodeSecure(string $data): string|false {
        if (empty($data)) return false;
        try {
            $payload = json_encode([
                'data' => $data,
                'timestamp' => time(),
                'nonce' => bin2hex(random_bytes(8)),
                'ip' => getClientIP()
            ], JSON_THROW_ON_ERROR);
            
            $hmac = hash_hmac('sha256', $payload, MP4_HMAC_KEY);
            $combined = $payload . '|' . $hmac;
            
            $encryption = openssl_encrypt($combined, 'AES-256-CBC', MP4_ENCRYPTION_KEY, OPENSSL_RAW_DATA, MP4_ENCRYPTION_IV);
            return $encryption ? base64_encode($encryption) : false;
        } catch (Exception $e) { return false; }
    }
}

if (!function_exists('decodeSecure')) {
    function decodeSecure(string $encodedData, int $maxAge = MP4_TOKEN_LIFETIME): array|false {
        if (empty($encodedData)) return false;
        try {
            $encodedData = str_replace([" ", "-", "_"], "+", $encodedData);
            $padding = strlen($encodedData) % 4;
            if ($padding) $encodedData .= str_repeat('=', 4 - $padding);
            
            $decoded = base64_decode($encodedData, true);
            if ($decoded === false) return false;
            
            $decryption = openssl_decrypt($decoded, 'AES-256-CBC', MP4_ENCRYPTION_KEY, OPENSSL_RAW_DATA, MP4_ENCRYPTION_IV);
            if ($decryption === false) return false;
            
            $parts = explode('|', $decryption);
            if (count($parts) !== 2) return false;
            
            list($payload, $receivedHmac) = $parts;
            if (!hash_equals(hash_hmac('sha256', $payload, MP4_HMAC_KEY), $receivedHmac)) return false;
            
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            return $data;
        } catch (Exception $e) { return false; }
    }
}

if (!function_exists('encode')) { function encode($d) { return encodeSecure($d); } }
if (!function_exists('decode')) { function decode($d) { $r = decodeSecure($d); return $r ? $r['data'] : false; } }
if (!function_exists('checkRateLimit')) { function checkRateLimit() { return true; } }

if (!function_exists('getClientIP')) {
    function getClientIP(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input): string {
        if (is_array($input)) return '';
        return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}

// RESTAURADAS: Funciones vitales omitidas
if (!function_exists('validateUrl')) {
    function validateUrl($url): bool {
        if (empty($url)) return false;
        return filter_var(trim((string)$url), FILTER_VALIDATE_URL) !== false;
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

setSecurityHeaders();
?>
