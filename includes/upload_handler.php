<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/storage_manager.php';

class UploadHandler {
    private $storageManager;
    
    public function __construct() {
        $this->storageManager = new StorageManager();
    }
    
    public function processUpload($file, $userId, $title = null) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error en la subida del archivo'];
        }
        
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'El archivo excede el tamaño máximo permitido'];
        }
        
        if (!isValidVideoExtension($file['name'])) {
            return ['success' => false, 'message' => 'Formato de archivo no permitido'];
        }
        
        $embedCode = generateEmbedCode();
        $fileName = $embedCode . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $videoTitle = $title ?: pathinfo($file['name'], PATHINFO_FILENAME);
        
        $uploadResult = $this->storageManager->uploadFile($file, $fileName);
        
        if (!$uploadResult['success']) {
            return ['success' => false, 'message' => 'Error al subir archivo: ' . $uploadResult['error']];
        }
        
        try {
            $stmt = db()->prepare("
                INSERT INTO videos (user_id, title, filename, original_name, file_size, 
                                  storage_type, storage_path, embed_code, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            $storage = $this->storageManager->getActiveStorage();
            $stmt->execute([
                $userId,
                $videoTitle,
                $fileName,
                $file['name'],
                $file['size'],
                $storage['storage_type'],
                $uploadResult['path'],
                $embedCode
            ]);
            
            $videoId = db()->lastInsertId();
            
            $stmt = db()->prepare("
                UPDATE users 
                SET total_files = total_files + 1,
                    total_storage = total_storage + ?
                WHERE id = ?
            ");
            $stmt->execute([$file['size'], $userId]);
            
            logActivity('upload_video', [
                'video_id' => $videoId,
                'filename' => $fileName,
                'size' => $file['size']
            ]);
            
            return [
                'success' => true,
                'video_id' => $videoId,
                'embed_code' => $embedCode,
                'url' => $uploadResult['url']
            ];
            
        } catch (PDOException $e) {
            $this->storageManager->deleteFile($uploadResult['path']);
            return ['success' => false, 'message' => 'Error al guardar en base de datos'];
        }
    }
    
    public function deleteVideo($videoId, $userId) {
        $stmt = db()->prepare("SELECT * FROM videos WHERE id = ? AND (user_id = ? OR ? = 'admin')");
        $stmt->execute([$videoId, $userId, $_SESSION['role'] ?? '']);
        $video = $stmt->fetch();
        
        if (!$video) {
            return ['success' => false, 'message' => 'Video no encontrado o sin permisos'];
        }
        
        $this->storageManager->deleteFile($video['storage_path']);
        
        $stmt = db()->prepare("UPDATE videos SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$videoId]);
        
        $stmt = db()->prepare("
            UPDATE users 
            SET total_files = total_files - 1,
                total_storage = total_storage - ?
            WHERE id = ?
        ");
        $stmt->execute([$video['file_size'], $video['user_id']]);
        
        logActivity('delete_video', ['video_id' => $videoId]);
        
        return ['success' => true];
    }
}