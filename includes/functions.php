<?php
// includes/functions.php - Funciones auxiliares optimizadas y limpias
require_once __DIR__ . '/db_connect.php';

/**
 * Genera un código aleatorio para los videos (Embed Code)
 */
function generateEmbedCode($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Formatea bytes a tamaño legible (MB, GB)
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Formatea segundos a duración (HH:MM:SS)
 */
function formatDuration($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = floor($seconds % 60);
    
    if ($h > 0) {
        return sprintf('%d:%02d:%02d', $h, $m, $s);
    }
    return sprintf('%d:%02d', $m, $s);
}

/**
 * Sanitiza inputs básicos
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// --- Helpers de Autenticación ---

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

// --- Helpers de Estadísticas ---

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

/**
 * Registra actividad del usuario en la BD
 */
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
        // Silencio en caso de error de log para no romper flujo principal
    }
}

/**
 * Incrementa vistas de forma segura
 */
function incrementViews($videoId) {
    // 1. Incrementar contador total
    $stmt = db()->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$videoId]);
    
    // 2. Registrar estadística diaria
    $stmt = db()->prepare("
        INSERT INTO statistics (video_id, date, views)
        VALUES (?, CURDATE(), 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ");
    $stmt->execute([$videoId]);
}

/**
 * Obtiene la URL correcta de reproducción.
 * IMPORTANTE: Ahora fuerza el uso de proxies para seguridad.
 */
function getVideoUrl($video) {
    // Si es local
    if ($video['storage_type'] === 'local') {
        return SITE_URL . '/stream.php?v=' . $video['embed_code'];
    }
    
    // Si es nube (Wasabi, Contabo, AWS), usar siempre el proxy
    // Esto oculta el bucket y usa las credenciales del servidor
    return SITE_URL . '/s3-proxy.php?v=' . $video['embed_code'];
}

/**
 * Obtiene información detallada del uso de almacenamiento por usuario
 */
function getUserStorageInfo($userId) {
    $stmt = db()->prepare("
        SELECT 
            COUNT(CASE WHEN storage_type = 'local' THEN 1 END) as local_files,
            COUNT(CASE WHEN storage_type IN ('contabo', 'wasabi', 'aws') THEN 1 END) as cloud_files,
            SUM(CASE WHEN storage_type = 'local' THEN file_size ELSE 0 END) as local_size,
            SUM(CASE WHEN storage_type IN ('contabo', 'wasabi', 'aws') THEN file_size ELSE 0 END) as cloud_size
        FROM videos 
        WHERE user_id = ? AND status = 'active'
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
?>
