<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('HTTP/1.0 404 Not Found');
    exit('Video no encontrado');
}

// Obtener video
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE embed_code = ? AND status = 'active'
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    header('HTTP/1.0 404 Not Found');
    exit('Video no disponible');
}

// Incrementar contador de descargas
$stmt = db()->prepare("UPDATE videos SET downloads = downloads + 1 WHERE id = ?");
$stmt->execute([$video['id']]);

// Obtener configuración de almacenamiento del video
$storageType = $video['storage_type'];
$stmt = db()->prepare("SELECT * FROM storage_config WHERE storage_type = ?");
$stmt->execute([$storageType]);
$storage = $stmt->fetch();

if (!$storage) {
    // Si no se encuentra la configuración específica, usar la activa
    $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1");
    $storage = $stmt->fetch();
}

// Generar URL de descarga según el tipo de almacenamiento
switch ($storage['storage_type']) {
    case 'contabo':
        // Para Contabo S3, generar URL firmada
        $downloadUrl = $storage['endpoint'] . '/' . $storage['bucket'] . '/' . $video['storage_path'];
        
        // Añadir headers para forzar descarga
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $video['original_name'] . '"');
        header('Location: ' . $downloadUrl);
        break;
        
    case 'wasabi':
        // Para Wasabi S3, generar URL firmada
        $downloadUrl = $storage['endpoint'] . '/' . $storage['bucket'] . '/' . $video['storage_path'];
        
        // Añadir headers para forzar descarga
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $video['original_name'] . '"');
        header('Location: ' . $downloadUrl);
        break;
        
    case 'aws':
        // Para AWS S3, generar URL firmada
        $downloadUrl = 'https://' . $storage['bucket'] . '.s3.' . $storage['region'] . '.amazonaws.com/' . $video['storage_path'];
        
        // Añadir headers para forzar descarga
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $video['original_name'] . '"');
        header('Location: ' . $downloadUrl);
        break;
        
    case 'local':
        // Para almacenamiento local, servir el archivo
        $filePath = '../uploads/' . $video['storage_path'];
        
        if (file_exists($filePath)) {
            // Obtener el tamaño del archivo
            $fileSize = filesize($filePath);
            
            // Configurar headers para descarga
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $video['original_name'] . '"');
            header('Content-Length: ' . $fileSize);
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Expires: 0');
            
            // Limpiar cualquier salida previa
            ob_clean();
            flush();
            
            // Enviar el archivo
            readfile($filePath);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'Archivo no encontrado en el servidor';
        }
        break;
        
    default:
        header('HTTP/1.0 404 Not Found');
        echo 'Tipo de almacenamiento no soportado: ' . $storage['storage_type'];
}

// Log de actividad
logActivity('download_video', [
    'video_id' => $video['id'],
    'embed_code' => $video['embed_code'],
    'storage_type' => $storage['storage_type']
]);

exit;
