<?php
// Script para limpiar archivos temporales y optimizar BD
// Ejecutar diariamente via CRON: 0 3 * * * php /path/to/cron/cleanup.php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/db_connect.php';

// Limpiar videos marcados como eliminados hace más de 30 días
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE status = 'deleted' 
    AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->execute();
$deletedVideos = $stmt->fetchAll();

foreach ($deletedVideos as $video) {
    // Eliminar archivo físico si existe
    if ($video['storage_type'] === 'local') {
        $filePath = VIDEO_PATH . $video['filename'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Eliminar registro de BD
    $stmt = db()->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$video['id']]);
}

// Limpiar estadísticas antiguas (más de 90 días)
$stmt = db()->exec("
    DELETE FROM statistics 
    WHERE date < DATE_SUB(NOW(), INTERVAL 90 DAY)
");

// Limpiar logs antiguos (más de 30 días)
$stmt = db()->exec("
    DELETE FROM activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
");

// Optimizar tablas
$tables = ['videos', 'statistics', 'activity_logs'];
foreach ($tables as $table) {
    db()->exec("OPTIMIZE TABLE $table");
}

echo date('Y-m-d H:i:s') . " - Limpieza completada\n";