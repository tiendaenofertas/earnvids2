<?php
require_once __DIR__ . '/db_connect.php';

function generateEmbedCode($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /');
        exit;
    }
}

function getUserStats($userId) {
    $stmt = db()->prepare("
        SELECT 
            COUNT(*) as total_videos,
            COALESCE(SUM(file_size), 0) as total_storage,
            COALESCE(SUM(views), 0) as total_views,
            COALESCE(SUM(downloads), 0) as total_downloads
        FROM videos 
        WHERE user_id = ? AND status = 'active'
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getGlobalStats() {
    $stats = [];
    
    $stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    $stmt = db()->query("SELECT COUNT(*) as total FROM videos WHERE status = 'active'");
    $stats['total_videos'] = $stmt->fetch()['total'];
    
    $stmt = db()->query("SELECT COALESCE(SUM(file_size), 0) as total FROM videos WHERE status = 'active'");
    $stats['total_storage'] = $stmt->fetch()['total'];
    
    $stmt = db()->query("SELECT COALESCE(SUM(views), 0) as total FROM statistics WHERE date = CURDATE()");
    $stats['views_today'] = $stmt->fetch()['total'];
    
    return $stats;
}

function logActivity($action, $details = []) {
    try {
        $stmt = db()->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->execute([
            $userId,
            $action,
            json_encode($details),
            $ip,
            $userAgent
        ]);
    } catch (Exception $e) {
        // Silently fail
    }
}

function incrementViews($videoId) {
    $stmt = db()->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$videoId]);
    
    $stmt = db()->prepare("
        INSERT INTO statistics (video_id, date, views)
        VALUES (?, CURDATE(), 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ");
    $stmt->execute([$videoId]);
}

function getActiveStorage() {
    $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
    return $stmt->fetch();
}

function isValidVideoExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

function getVideoUrl($video) {
    // Primero intentamos obtener la configuración de almacenamiento del video específico
    $storageType = $video['storage_type'] ?? null;
    
    // Si no tiene storage_type, obtenemos el almacenamiento activo actual
    if (!$storageType) {
        $storage = getActiveStorage();
        $storageType = $storage['storage_type'] ?? 'local';
    } else {
        // Si el video tiene storage_type, obtenemos la configuración de ese tipo
        $stmt = db()->prepare("SELECT * FROM storage_config WHERE storage_type = ?");
        $stmt->execute([$storageType]);
        $storage = $stmt->fetch();
    }
    
    if (!$storage) {
        return '#';
    }
    
    switch ($storageType) {
        case 'contabo':
            // TEMPORAL: Usar proxy mientras se configuran permisos públicos
            // Esto evitará el error 401 Unauthorized
            return SITE_URL . '/s3-proxy.php?v=' . $video['embed_code'];
            
            /* Código original - descomentar cuando los permisos estén configurados
            $endpoint = rtrim($storage['endpoint'], '/');
            $bucket = trim($storage['bucket'], '/');
            $path = ltrim($video['storage_path'], '/');
            
            $url = $endpoint . '/' . $bucket . '/' . $path;
            return $url;
            */
            
        case 'wasabi':
            // Para Wasabi S3
            $url = $storage['endpoint'] . '/' . $storage['bucket'] . '/' . $video['storage_path'];
            // Asegurar que tenga protocolo
            if (strpos($url, 'http') !== 0) {
                $url = 'https://' . $url;
            }
            return $url;
            
        case 'aws':
            // Para AWS S3
            $url = 'https://' . $storage['bucket'] . '.s3.' . $storage['region'] . '.amazonaws.com/' . $video['storage_path'];
            return $url;
            
        case 'local':
            return SITE_URL . '/stream.php?v=' . $video['embed_code'];
            
        default:
            return '#';
    }
}

function formatDuration($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = floor($seconds % 60);
    
    if ($h > 0) {
        return sprintf('%d:%02d:%02d', $h, $m, $s);
    }
    return sprintf('%d:%02d', $m, $s);
}

// NUEVAS FUNCIONES AGREGADAS:

// Función auxiliar para verificar el estado del almacenamiento
function getStorageStatus() {
    $storages = [];
    $stmt = db()->query("SELECT * FROM storage_config ORDER BY is_active DESC, storage_type ASC");
    
    while ($row = $stmt->fetch()) {
        $storages[] = [
            'type' => $row['storage_type'],
            'active' => $row['is_active'] == 1,
            'configured' => !empty($row['access_key']) || $row['storage_type'] == 'local'
        ];
    }
    
    return $storages;
}

// Función para obtener información de almacenamiento del usuario
function getUserStorageInfo($userId) {
    $stmt = db()->prepare("
        SELECT 
            COUNT(CASE WHEN storage_type = 'local' THEN 1 END) as local_files,
            COUNT(CASE WHEN storage_type = 'contabo' THEN 1 END) as contabo_files,
            COUNT(CASE WHEN storage_type = 'wasabi' THEN 1 END) as wasabi_files,
            COUNT(CASE WHEN storage_type = 'aws' THEN 1 END) as aws_files,
            SUM(CASE WHEN storage_type = 'local' THEN file_size ELSE 0 END) as local_size,
            SUM(CASE WHEN storage_type = 'contabo' THEN file_size ELSE 0 END) as contabo_size,
            SUM(CASE WHEN storage_type = 'wasabi' THEN file_size ELSE 0 END) as wasabi_size,
            SUM(CASE WHEN storage_type = 'aws' THEN file_size ELSE 0 END) as aws_size
        FROM videos 
        WHERE user_id = ? AND status = 'active'
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
