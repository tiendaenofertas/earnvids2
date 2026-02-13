<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/storage_manager.php';

class UploadHandler {
    
    public function __construct() {
        // Constructor vacío
    }
    
    public function processUpload($file, $userId, $title = null) {
        $storageManager = new StorageManager($userId);

        if (!isset($file['error']) || is_array($file['error'])) return ['success' => false, 'message' => 'Error parámetros'];
        if ($file['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'message' => 'Error subida PHP: ' . $file['error']];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['mp4','webm','mkv','avi','mov','flv','wmv'])) return ['success' => false, 'message' => 'Formato no permitido'];

        $embedCode = generateEmbedCode();
        $fileName = $embedCode . '_' . time() . '.' . $ext;
        $cleanTitle = $title ? sanitize($title) : pathinfo($file['name'], PATHINFO_FILENAME);
        
        $uploadResult = $storageManager->uploadFile($file, $fileName);
        
        if (!$uploadResult['success']) return ['success' => false, 'message' => 'Error storage: ' . ($uploadResult['error'] ?? 'N/A')];
        
        try {
            $stmt = db()->prepare("INSERT INTO videos (user_id, title, filename, original_name, file_size, storage_type, storage_path, embed_code, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            $storageType = $storageManager->getActiveStorageType();
            $stmt->execute([$userId, $cleanTitle, $fileName, $file['name'], $file['size'], $storageType, $uploadResult['path'], $embedCode]);
            
            $videoId = db()->lastInsertId();
            db()->prepare("UPDATE users SET total_files = total_files + 1, total_storage = total_storage + ? WHERE id = ?")->execute([$file['size'], $userId]);
            
            return ['success' => true, 'video_id' => $videoId, 'embed_code' => $embedCode, 'url' => $uploadResult['url'], 'redirect_url' => '/watch.php?v=' . $embedCode];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error DB: ' . $e->getMessage()];
        }
    }
    
    // --- FUNCIÓN DE BORRADO CORREGIDA ---
    public function deleteVideo($videoId, $userId) {
        $stmt = db()->prepare("SELECT * FROM videos WHERE id = ? AND status != 'deleted'");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();
        
        if (!$video) return ['success' => false, 'message' => 'Video no encontrado'];
        
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($video['user_id'] != $userId && !$isAdmin) return ['success' => false, 'message' => 'Sin permisos'];
        
        // CORRECCIÓN: Instanciamos StorageManager con el ID del dueño del video
        $storageManager = new StorageManager($video['user_id']);
        
        // Y le pasamos explícitamente el TIPO de almacenamiento para que cargue la config correcta
        // (ej: si el video es de Wasabi pero el usuario ahora usa Local, esto forzará borrar en Wasabi)
        $storageManager->deleteFile($video['storage_path'], $video['storage_type']);
        
        // Borrar de BD
        db()->prepare("DELETE FROM videos WHERE id = ?")->execute([$videoId]);
        db()->prepare("DELETE FROM statistics WHERE video_id = ?")->execute([$videoId]);
        db()->prepare("UPDATE users SET total_files = GREATEST(0, total_files - 1), total_storage = GREATEST(0, total_storage - ?) WHERE id = ?")->execute([$video['file_size'], $video['user_id']]);
        
        return ['success' => true];
    }
}
?>
