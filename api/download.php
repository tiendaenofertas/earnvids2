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

// Obtener almacenamiento activo
$stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1");
$storage = $stmt->fetch();

// Generar URL de descarga según el tipo de almacenamiento
switch ($storage['storage_type']) {
    case 'contabo':
        // Para Contabo S3, redirigir a la URL directa
        $downloadUrl = $storage['endpoint'] . '/' . $storage['bucket'] . '/' . $video['storage_path'];
        header('Location: ' . $downloadUrl);
        break;
        
    case 'local':
        // Para almacenamiento local, servir el archivo
        $filePath = '../uploads/' . $video['storage_path'];
        
        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $video['original_name'] . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'Archivo no encontrado en el servidor';
        }
        break;
        
    default:
        header('HTTP/1.0 404 Not Found');
        echo 'Tipo de almacenamiento no soportado';
}
exit;