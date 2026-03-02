<?php
// cron/cleanup.php - Limpieza automática optimizada y compatible con Nube
// Ejecutar diariamente via CRON: 0 3 * * * php /path/to/cron/cleanup.php

// Aseguramos que detecte correctamente la raíz del proyecto
define('ROOT_PATH', dirname(__DIR__));

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/includes/db_connect.php';
// Importante: Incluimos el gestor de almacenamiento para borrar en la nube
require_once ROOT_PATH . '/includes/storage_manager.php';

// Aumentar tiempo de ejecución para tareas pesadas
set_time_limit(600); 

echo "[" . date('Y-m-d H:i:s') . "] Iniciando limpieza...\n";

// 1. Instanciar el Gestor de Almacenamiento
$storageManager = new StorageManager();

// 2. Buscar videos marcados como 'deleted' hace más de 30 días
// (Opcional: Ajustar el intervalo según tus políticas)
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE status = 'deleted' 
    AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    LIMIT 500
"); // Limitamos a 500 por ejecución para no saturar memoria
$stmt->execute();
$deletedVideos = $stmt->fetchAll();

$count = 0;
foreach ($deletedVideos as $video) {
    try {
        echo "Procesando video ID {$video['id']} ({$video['storage_type']})... ";
        
        // CORRECCIÓN CRÍTICA:
        // Usamos deleteFile() del manager, que sabe cómo borrar en Wasabi/Contabo/Local
        // pasando la ruta de almacenamiento correcta.
        $storageManager->deleteFile($video['storage_path']);
        
        // Eliminar definitivamente de la Base de Datos
        $delStmt = db()->prepare("DELETE FROM videos WHERE id = ?");
        $delStmt->execute([$video['id']]);
        
        // Limpiar estadísticas huérfanas
        db()->prepare("DELETE FROM statistics WHERE video_id = ?")->execute([$video['id']]);
        
        echo "OK\n";
        $count++;
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

echo "Videos eliminados y limpiados: $count\n";

// 3. Limpiar estadísticas antiguas (más de 90 días para mantener la BD ligera)
$stmt = db()->exec("
    DELETE FROM statistics 
    WHERE date < DATE_SUB(NOW(), INTERVAL 90 DAY)
");
echo "Estadísticas antiguas depuradas.\n";

// 4. Limpiar logs de actividad antiguos (más de 60 días)
$stmt = db()->exec("
    DELETE FROM activity_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)
");
echo "Logs de actividad depurados.\n";

// 5. Optimizar tablas para recuperar espacio físico en disco del servidor DB
$tables = ['videos', 'statistics', 'activity_logs', 'users'];
foreach ($tables as $table) {
    try {
        db()->exec("OPTIMIZE TABLE $table");
    } catch (Exception $e) {
        // Ignorar errores de optimización si la tabla no soporta (ej: InnoDB a veces)
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Limpieza completada exitosamente.\n";
?>
