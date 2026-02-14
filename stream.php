<?php
// stream.php - Streaming seguro de archivos locales con protección Anti-Hotlink
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Desactivar límites de tiempo para streaming de archivos grandes
set_time_limit(0);
// Desactivar compresión zlib si está activa para evitar problemas con el video
if (ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

$embedCode = isset($_GET['v']) ? $_GET['v'] : '';

if (empty($embedCode)) {
    http_response_code(400);
    die('Solicitud inválida.');
}

try {
    // 1. Obtener datos del video
    $stmt = db()->prepare("SELECT * FROM videos WHERE embed_code = ? AND storage_type = 'local' LIMIT 1");
    $stmt->execute([$embedCode]);
    $video = $stmt->fetch();

    if (!$video || $video['status'] !== 'active') {
        http_response_code(404);
        die('Video no encontrado o no disponible.');
    }

    // --- 2. PROTECCIÓN DE DOMINIOS (HOTLINK PROTECTION) ---
    // Esta lógica impide que otros sitios incrusten directamente este archivo stream.php
    
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererHost = '';

    if ($referer) {
        $parsedUrl = parse_url($referer);
        $refererHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
    }

    $selfHost = parse_url(SITE_URL, PHP_URL_HOST);
    $selfHost = strtolower($selfHost ?? '');

    $isAllowed = false;

    // A. Permitir siempre solicitudes desde el propio dominio
    // (Importante: Algunos navegadores o antivirus pueden no enviar referer, 
    // por compatibilidad solemos permitir si referer está vacío, aunque reduce seguridad estricta)
    if ($refererHost === $selfHost || empty($refererHost)) {
        $isAllowed = true;
    } else {
        // B. Verificar lista blanca del USUARIO (Dueño del video)
        $stmt = db()->prepare("SELECT domain FROM user_domains WHERE user_id = ?");
        $stmt->execute([$video['user_id']]);
        $userDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($userDomains)) {
            // El usuario configuró dominios, verificamos contra ellos
            foreach ($userDomains as $d) {
                if (strtolower(trim($d)) === $refererHost) {
                    $isAllowed = true;
                    break;
                }
            }
        } else {
            // C. Si el usuario no configuró, verificar configuración GLOBAL
            if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
                 foreach (ALLOWED_DOMAINS as $globalDomain) {
                    if (strtolower(trim($globalDomain)) === $refererHost) {
                        $isAllowed = true;
                        break;
                    }
                }
            } else {
                // D. Si nadie restringió nada, es público
                $isAllowed = true;
            }
        }
    }

    if (!$isAllowed) {
        http_response_code(403);
        die('Access Denied: Hotlink Protection');
    }
    // -----------------------------------------------------

    // 3. Ubicar el archivo físico
    $filePath = $video['storage_path'];
    
    // Ajuste por si la ruta en BD es relativa o absoluta
    if (!file_exists($filePath)) {
        // Intentar buscar relativo a la raíz del proyecto
        $realPath = __DIR__ . '/' . ltrim($filePath, '/');
        if (file_exists($realPath)) {
            $filePath = $realPath;
        }
    }

    if (!file_exists($filePath)) {
        error_log("Stream Error: Archivo no encontrado en $filePath");
        http_response_code(404);
        die('Archivo de video no encontrado en el servidor.');
    }

    // 4. Iniciar Streaming con soporte para Rango (Seek)
    $fileSize = filesize($filePath);
    $fp = @fopen($filePath, 'rb');
    
    if (!$fp) {
        http_response_code(500);
        die('Error al abrir el archivo de video.');
    }

    $start = 0;
    $end = $fileSize - 1;

    // Manejo de cabecera HTTP_RANGE (Para adelantar/retroceder video)
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_start = $start;
        $c_end = $end;

        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            http_response_code(416); // Range Not Satisfiable
            header("Content-Range: bytes $start-$end/$fileSize");
            exit;
        }

        if ($range == '-') {
            $c_start = $fileSize - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];
            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
        }

        $c_end = ($c_end > $end) ? $end : $c_end;
        
        if ($c_start > $c_end || $c_start > $fileSize - 1 || $c_end >= $fileSize) {
            http_response_code(416);
            header("Content-Range: bytes $start-$end/$fileSize");
            exit;
        }
        
        $start = $c_start;
        $end = $c_end;
        $length = $end - $start + 1;
        
        fseek($fp, $start);
        http_response_code(206); // Partial Content
        header("Content-Range: bytes $start-$end/$fileSize");
    } else {
        $length = $fileSize;
        header("Content-Length: " . $length);
    }

    // Cabeceras de tipo de contenido
    $mime = mime_content_type($filePath);
    header("Content-Type: " . $mime);
    header("Content-Length: " . $length);
    header("Accept-Ranges: bytes");
    
    // Limpiar buffers previos para evitar corrupción
    while (ob_get_level()) ob_end_clean();

    // Enviar archivo en trozos (chunks) para no saturar memoria RAM
    $bufferSize = 1024 * 64; // 64KB chunks
    $bytesSent = 0;

    while (!feof($fp) && ($bytesSent < $length) && !connection_aborted()) {
        $readSize = min($bufferSize, $length - $bytesSent);
        $buffer = fread($fp, $readSize);
        echo $buffer;
        flush();
        $bytesSent += strlen($buffer);
    }

    fclose($fp);

    // Actualizar contador de vistas (Opcional: solo si se vio desde el principio o simple check)
    // Nota: Para optimizar, el contador suele actualizarse vía AJAX en embed.php, 
    // pero si deseas hacerlo aquí, descomenta:
    /*
    $updateStmt = db()->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $updateStmt->execute([$video['id']]);
    */

} catch (Exception $e) {
    error_log("Error en stream.php: " . $e->getMessage());
    http_response_code(500);
    die('Error interno del servidor.');
}
?>
