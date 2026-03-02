<?php
// api/download.php - Descargas seguras y optimizadas
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';
require_once '../includes/storage_manager.php'; // Usamos el gestor centralizado

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    http_response_code(404);
    die('Video no encontrado');
}

// 1. Obtener video
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE embed_code = ? AND status = 'active'
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    die('Video no disponible');
}

// 2. Registrar la descarga
$stmt = db()->prepare("UPDATE videos SET downloads = downloads + 1 WHERE id = ?");
$stmt->execute([$video['id']]);

// 3. Gestionar la descarga según el almacenamiento
try {
    if ($video['storage_type'] === 'local') {
        // --- DESCARGA LOCAL ---
        $filePath = '../uploads/' . $video['storage_path'];
        
        if (file_exists($filePath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($video['original_name']) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            http_response_code(404);
            die('Archivo físico no encontrado en el servidor.');
        }

    } else {
        // --- DESCARGA NUBE (Wasabi/Contabo/AWS) ---
        // Usamos StorageManager para obtener credenciales frescas
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE storage_type = ? LIMIT 1");
        $stmt->execute([$video['storage_type']]);
        $storage = $stmt->fetch();
        
        if (!$storage) {
            // Fallback: intentar con la activa si no coincide
            $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
            $storage = $stmt->fetch();
        }

        if ($storage) {
            // Generamos una URL prefirmada (Presigned URL)
            // Esto permite que el usuario descargue DIRECTO de Wasabi (ahorrando tu ancho de banda)
            // pero de forma segura (el enlace caduca en 1 hora).
            
            $s3 = new SimpleS3ClientV4(
                $storage['access_key'],
                $storage['secret_key'],
                $storage['endpoint'],
                $storage['region'] ?: 'us-east-1'
            );
            
            // Lógica manual de presigned URL para V4 (integrada aquí para no complicar classes)
            $expires = 3600; // 1 hora
            $bucket = $storage['bucket'];
            $key = $video['storage_path'];
            $host = parse_url($storage['endpoint'], PHP_URL_HOST);
            
            $algorithm = 'AWS4-HMAC-SHA256';
            $now = time();
            $amzDate = gmdate('Ymd\THis\Z', $now);
            $dateStamp = gmdate('Ymd', $now);
            $region = $storage['region'] ?: 'us-east-1';
            
            // Canonical Request
            $canonicalUri = '/' . $bucket . '/' . $key;
            $credentialScope = "$dateStamp/$region/s3/aws4_request";
            
            $queryParams = [
                'X-Amz-Algorithm' => $algorithm,
                'X-Amz-Credential' => $storage['access_key'] . '/' . $credentialScope,
                'X-Amz-Date' => $amzDate,
                'X-Amz-Expires' => $expires,
                'X-Amz-SignedHeaders' => 'host',
                // Forzar descarga con nombre correcto
                'response-content-disposition' => 'attachment; filename="' . $video['original_name'] . '"'
            ];
            
            ksort($queryParams);
            $queryString = http_build_query($queryParams);
            
            $canonicalHeaders = "host:$host\n";
            $signedHeaders = "host";
            $payloadHash = "UNSIGNED-PAYLOAD";
            
            $canonicalRequest = "GET\n$canonicalUri\n$queryString\n$canonicalHeaders\n$signedHeaders\n$payloadHash";
            
            // String to Sign
            $stringToSign = "$algorithm\n$amzDate\n$credentialScope\n" . hash('sha256', $canonicalRequest);
            
            // Signature Calculation
            $kSecret = 'AWS4' . $storage['secret_key'];
            $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
            $kRegion = hash_hmac('sha256', $region, $kDate, true);
            $kService = hash_hmac('sha256', 's3', $kRegion, true);
            $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
            $signature = hash_hmac('sha256', $stringToSign, $kSigning);
            
            // Final URL
            $url = $storage['endpoint'] . $canonicalUri . '?' . $queryString . '&X-Amz-Signature=' . $signature;
            
            // Redireccionar al usuario a la descarga directa
            header("Location: $url");
            exit;
        } else {
            die("Error de configuración de almacenamiento.");
        }
    }

} catch (Exception $e) {
    logActivity('download_error', ['error' => $e->getMessage(), 'video_id' => $video['id']]);
    die("Error al procesar la descarga.");
}
?>
