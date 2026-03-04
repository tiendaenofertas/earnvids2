<?php
// includes/upload_handler.php - Motor de subida con seguridad extrema (MIME & Magic Bytes)
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/storage_manager.php';

class UploadHandler {
    
    public function __construct() {
        // Constructor vacío
    }
    
    public function processUpload($file, $userId, $title = null) {
        $storageManager = new StorageManager($userId);

        // 1. Validaciones básicas de integridad de PHP
        if (!isset($file['error']) || is_array($file['error'])) return ['success' => false, 'message' => 'Error parámetros de subida.'];
        if ($file['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'message' => 'Error subida PHP: ' . $file['error']];

        // 2. SEGURIDAD EXTREMA: Verificar doble extensión (Ej: shell.php.mp4)
        $originalName = strtolower($file['name']);
        if (preg_match('/\.(php|phtml|php3|php4|php5|phps|exe|sh|cgi|pl|jsp|asp|aspx|py|js)$/i', $originalName) || 
            (substr_count($originalName, '.') > 1 && strpos($originalName, '.php') !== false)) {
            error_log("⚠️ INTENTO DE HACKEO DETECTADO: Usuario ID $userId intentó subir archivo sospechoso: " . $file['name']);
            return ['success' => false, 'message' => 'Seguridad: Archivo bloqueado por sospecha de inyección.'];
        }

        // 3. Validación de Extensión Permitida
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $allowed_exts = ['mp4','webm','mkv','avi','mov','flv','wmv'];
        if (!in_array($ext, $allowed_exts)) return ['success' => false, 'message' => 'Formato no permitido. Solo videos.'];

        // 4. SEGURIDAD EXTREMA: Leer el ADN del archivo (Magic Bytes)
        // Esto evita que un hacker cambie un archivo .exe a .mp4 y lo suba
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);
        
        $allowed_mimes = [
            'video/mp4', 'video/webm', 'video/x-matroska', 'video/avi', 
            'video/msvideo', 'video/x-msvideo', 'video/quicktime', 
            'video/x-flv', 'video/x-ms-wmv', 'application/octet-stream' // octet-stream permitido para mkv a veces
        ];
        
        if (!in_array($realMime, $allowed_mimes) || (strpos($realMime, 'video/') !== 0 && $realMime !== 'application/octet-stream')) {
            error_log("⚠️ INTENTO DE HACKEO: Firma MIME falsa ($realMime) detectada por el usuario ID $userId");
            return ['success' => false, 'message' => 'Seguridad: El archivo está corrupto o tiene una firma falsa.'];
        }

        // 5. Renombrado Criptográfico (Imposible de adivinar o inyectar)
        $embedCode = generateEmbedCode();
        $secureSalt = bin2hex(random_bytes(4)); // Entropía extra
        $fileName = $embedCode . '_' . time() . '_' . $secureSalt . '.' . $ext;
        
        $cleanTitle = $title ? sanitize($title) : pathinfo($file['name'], PATHINFO_FILENAME);
        
        // Iniciar subida al Almacenamiento (Local, Wasabi, Contabo, etc)
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
    
    public function deleteVideo($videoId, $userId) {
        $stmt = db()->prepare("SELECT * FROM videos WHERE id = ? AND status != 'deleted'");
        $stmt->execute([$videoId]);
        $video = $stmt->fetch();
        
        if (!$video) return ['success' => false, 'message' => 'Video no encontrado'];
        
        $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
        if ($video['user_id'] != $userId && !$isAdmin) return ['success' => false, 'message' => 'Sin permisos'];
        
        $storageManager = new StorageManager($video['user_id']);
        $storageManager->deleteFile($video['storage_path'], $video['storage_type']);
        
        db()->prepare("DELETE FROM videos WHERE id = ?")->execute([$videoId]);
        db()->prepare("DELETE FROM statistics WHERE video_id = ?")->execute([$videoId]);
        db()->prepare("UPDATE users SET total_files = GREATEST(0, total_files - 1), total_storage = GREATEST(0, total_storage - ?) WHERE id = ?")->execute([$video['file_size'], $video['user_id']]);
        
        return ['success' => true];
    }
}
?>
