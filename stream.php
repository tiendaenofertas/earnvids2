<?php
// stream.php - Streaming seguro de archivos locales
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

set_time_limit(0);
if (ini_get('zlib.output_compression')) { ini_set('zlib.output_compression', 'Off'); }

$embedCode = isset($_GET['v']) ? $_GET['v'] : '';

if (empty($embedCode)) { http_response_code(400); die('Solicitud inválida.'); }

try {
    $stmt = db()->prepare("SELECT * FROM videos WHERE embed_code = ? AND storage_type = 'local' LIMIT 1");
    $stmt->execute([$embedCode]);
    $video = $stmt->fetch();

    if (!$video || $video['status'] !== 'active') { http_response_code(404); die('Video no encontrado.'); }

    // --- PROTECCIÓN DE DOMINIOS + MEMBRESÍA ---
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererHost = '';
    if ($referer) {
        $parsedUrl = parse_url($referer);
        $refererHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
    }

    $selfHost = parse_url(SITE_URL, PHP_URL_HOST);
    $selfHost = strtolower($selfHost ?? '');
    $isAllowed = false;

    // Permitir propio dominio o acceso directo (navegador)
    if ($refererHost === $selfHost || empty($refererHost)) {
        $isAllowed = true;
    } else {
        // Verificar lista del usuario
        $stmt = db()->prepare("SELECT domain FROM user_domains WHERE user_id = ?");
        $stmt->execute([$video['user_id']]);
        $userDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $domainMatch = false;
        if (!empty($userDomains)) {
            foreach ($userDomains as $d) {
                if (strtolower(trim($d)) === $refererHost) {
                    $domainMatch = true; break;
                }
            }
        }

        if ($domainMatch) {
            // Verificar Membresía del Dueño
            $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
            $stmt->execute([$video['user_id']]);
            $owner = $stmt->fetch();
            // Solo permitir si tiene membresía activa
            if ($owner && !empty($owner['membership_expiry']) && strtotime($owner['membership_expiry']) > time()) {
                $isAllowed = true;
            }
        } else {
            // Fallback global
            if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
                 foreach (ALLOWED_DOMAINS as $globalDomain) {
                    if (strtolower(trim($globalDomain)) === $refererHost) { $isAllowed = true; break; }
                }
            } else { $isAllowed = true; }
        }
    }

    if (!$isAllowed) { http_response_code(403); die('Access Denied: Hotlink Protection'); }

    // --- STREAMING DEL ARCHIVO ---
    $filePath = $video['storage_path'];
    if (!file_exists($filePath)) {
        $realPath = __DIR__ . '/' . ltrim($filePath, '/');
        if (file_exists($realPath)) $filePath = $realPath;
    }

    if (!file_exists($filePath)) { http_response_code(404); die('Archivo no encontrado.'); }

    $fileSize = filesize($filePath);
    $fp = @fopen($filePath, 'rb');
    
    if (!$fp) { http_response_code(500); die('Error al abrir el archivo.'); }

    $start = 0; $end = $fileSize - 1; $length = $fileSize;
    $mime = mime_content_type($filePath);

    // Soporte para Range (Seek)
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_start = $start; $c_end = $end;
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            header("Content-Range: bytes $start-$end/$fileSize"); http_response_code(416); exit;
        }
        if ($range == '-') { $c_start = $fileSize - substr($range, 1); }
        else {
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
        }
        $c_end = ($c_end > $end) ? $end : $c_end;
        if ($c_start > $c_end || $c_start > $fileSize - 1 || $c_end >= $fileSize) {
            header("Content-Range: bytes $start-$end/$fileSize"); http_response_code(416); exit;
        }
        $start = $c_start; $end = $c_end; $length = $end - $start + 1;
        fseek($fp, $start);
        http_response_code(206);
        header("Content-Range: bytes $start-$end/$fileSize");
    } else {
        header("Content-Length: " . $length);
    }

    header("Content-Type: " . $mime);
    header("Accept-Ranges: bytes");
    while (ob_get_level()) ob_end_clean();

    $bufferSize = 1024 * 64;
    $bytesSent = 0;
    while (!feof($fp) && ($bytesSent < $length) && !connection_aborted()) {
        $readSize = min($bufferSize, $length - $bytesSent);
        $buffer = fread($fp, $readSize);
        echo $buffer; flush(); $bytesSent += strlen($buffer);
    }
    fclose($fp);

} catch (Exception $e) { http_response_code(500); die('Internal Error'); }
?>
