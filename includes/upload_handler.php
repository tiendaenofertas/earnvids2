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
        // 1. Validaciones Básicas de PHP
        if (!isset($file['error']) || is_array($file['error'])) {
             return ['success' => false, 'message' => 'Parámetros de archivo inválidos'];
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'message' => 'No se envió ningún archivo'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'message' => 'El archivo excede el límite de tamaño del servidor'];
            default:
                return ['success' => false, 'message' => 'Error desconocido en la subida'];
        }
        
        // 2. Validación de Tamaño (Configuración de App)
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'El archivo excede el tamaño máximo permitido'];
        }
        
        // 3. Validación de Extensión
        if (!isValidVideoExtension($file['name'])) {
            return ['success' => false, 'message' => 'Formato de archivo no permitido (Extensión bloqueada)'];
        }
        
        // 4. Seguridad: Validación de Tipo MIME Real (Magic Bytes)
        // Esto evita que alguien suba un .php renombrado a .mp4
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        
        $allowedMimes = [
            'video/mp4', 'video/webm', 'video/x-matroska', 
            'video/avi', 'video/quicktime', 'video/x-flv', 
            'video/x-ms-wmv', 'application/octet-stream' // A veces MKV se detecta así
        ];
        
        if (!in_array($realMime, $allowedMimes)) {
            // Loguear intento sospechoso
            error_log("Seguridad: Intento de subida de archivo falso. Ext: " . $file['name'] . " MIME Real: " . $realMime);
            return ['success' => false, 'message' => 'El archivo no es un video válido'];
        }
        
        // 5. Preparar nombres y rutas
        $embedCode = generateEmbedCode();
        // Usamos pathinfo para asegurar una extensión limpia
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = $embedCode . '_' . time() . '.' . $ext;
        
        // Saneamiento del título (XSS Prevention)
        $cleanTitle = $title ? sanitize($title) : pathinfo($file['name'], PATHINFO_FILENAME);
        
        // 6. Subir usando el StorageManager corregido (Soporte V4/Wasabi)
        $uploadResult = $this->storageManager->uploadFile($file, $fileName);
        
        if (!$uploadResult['success']) {
            return ['success' => false, 'message' => 'Error al subir archivo al almacenamiento: ' . ($uploadResult['error'] ?? 'Desconocido')];
        }
        
        // 7. Guardar en Base de Datos
        try {
            $storageType = $this->storageManager->getActiveStorageType() ?? 'local';
            
            $stmt = db()->prepare("
                INSERT INTO videos (user_id, title, filename, original_name, file_size, 
                                  storage_type, storage_path, embed_code, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            
            $stmt->execute([
                $userId,
                $cleanTitle,
                $fileName,
                $file['name'],
                $file['size'],
                $storageType,
                $uploadResult['path'], // Ruta relativa devuelta por el manager
                $embedCode
            ]);
            
            $videoId = db()->lastInsertId();
            
            // Actualizar estadísticas de usuario
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
                'size' => $file['size'],
                'storage' => $storageType
            ]);
            
            return [
                'success' => true,
                'video_id' => $videoId,
                'embed_code' => $embedCode,
                'url' => $uploadResult['url'],
                'message' => 'Subida completada exitosamente'
            ];
            
        } catch (PDOException $e) {
            // Rollback: Si falla la BD, borramos el archivo subido para no dejar basura
            $this->storageManager->deleteFile($uploadResult['path']);
            error_log("DB Error Upload: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar referencia en base de datos'];
        }
    }
    
    public function deleteVideo($videoId, $userId) {
        // Verificar propiedad y permisos
        $stmt = db()->prepare("SELECT * FROM videos WHERE id = ? AND status != 'deleted'");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();
        
        if (!$video) {
            return ['success' => false, 'message' => 'Video no encontrado'];
        }
        
        // Verificar si es dueño o admin
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($video['user_id'] != $userId && !$isAdmin) {
             return ['success' => false, 'message' => 'No tienes permiso para eliminar este video'];
        }
        
        // 1. Eliminar archivo físico (S3 o Local)
        // Importante: No detenemos el proceso si falla el borrado físico (a veces ya no existe), 
        // pero lo logueamos. Priorizamos limpiar la BD.
        $deletedFisicamente = $this->storageManager->deleteFile($video['storage_path']);
        if (!$deletedFisicamente) {
            error_log("Warning: No se pudo borrar archivo físico: " . $video['storage_path']);
        }
        
        // 2. Marcar como eliminado en BD (Soft Delete) o Borrar (Hard Delete)
        // Usaremos Hard Delete para mantener limpia la BD según tu solicitud de optimización,
        // o Soft Delete si prefieres historial. Aquí uso DELETE real para limpiar.
        $stmt = db()->prepare("DELETE FROM videos WHERE id = ?");
        $stmt->execute([$videoId]);
        
        // Limpiar estadísticas asociadas
        db()->prepare("DELETE FROM statistics WHERE video_id = ?")->execute([$videoId]);
        
        // 3. Actualizar cuota de usuario
        $stmt = db()->prepare("
            UPDATE users 
            SET total_files = GREATEST(0, total_files - 1),
                total_storage = GREATEST(0, total_storage - ?)
            WHERE id = ?
        ");
        $stmt->execute([$video['file_size'], $video['user_id']]);
        
        logActivity('delete_video', ['video_id' => $videoId, 'title' => $video['title']]);
        
        return ['success' => true];
    }
}
?>
