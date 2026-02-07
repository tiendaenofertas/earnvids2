<?php
// account.php - Versión blindada y autónoma
// 1. Activar reporte de errores temporalmente para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/app.php';
require_once 'includes/db_connect.php'; 
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Verificación de sesión segura
if (!function_exists('isLoggedIn') || !function_exists('requireLogin')) {
    // Fallback de emergencia si functions.php falla
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
} else {
    requireLogin();
}

$message = '';
$error = '';

// Procesar Acciones (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Cambiar Contraseña
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $stmt = db()->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = 'La contraseña actual es incorrecta.';
        } elseif (strlen($new_password) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Las nuevas contraseñas no coinciden.';
        } else {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $message = '¡Contraseña actualizada correctamente!';
            
            // Log de actividad seguro
            if (function_exists('logActivity')) {
                logActivity('change_password', ['user_id' => $_SESSION['user_id']]);
            }
        }
    }
    
    // 2. Regenerar API Key
    if ($_POST['action'] === 'regenerate_api_key') {
        $username = $_SESSION['username'] ?? 'user';
        $newApiKey = md5($username . time() . mt_rand());
        $stmt = db()->prepare("UPDATE users SET api_key = ? WHERE id = ?");
        $stmt->execute([$newApiKey, $_SESSION['user_id']]);
        $message = 'API Key regenerada. Actualiza tus aplicaciones.';
    }
}

// Obtener datos del usuario de forma segura
try {
    // Consulta principal de usuario y totales
    $stmt = db()->prepare("
        SELECT u.*, 
            (SELECT COUNT(*) FROM videos WHERE user_id = u.id AND status = 'active') as total_videos,
            (SELECT COALESCE(SUM(file_size), 0) FROM videos WHERE user_id = u.id AND status = 'active') as total_storage,
            (SELECT COALESCE(SUM(views), 0) FROM videos WHERE user_id = u.id AND status = 'active') as total_views,
            (SELECT COALESCE(SUM(downloads), 0) FROM videos WHERE user_id = u.id AND status = 'active') as total_downloads
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userInfo = $stmt->fetch();

    if (!$userInfo) {
        // Si el usuario no existe en BD, forzar logout
        if (class_exists('Auth')) { Auth::logout(); } else { session_destroy(); header('Location: /login.php'); }
        exit;
    }

    // LÓGICA DE ALMACENAMIENTO INCRUSTADA (Para evitar Error 500)
    // Calculamos aquí mismo los datos en lugar de depender de functions.php
    $stmt = db()->prepare("
        SELECT 
            COUNT(CASE WHEN storage_type = 'local' THEN 1 END) as local_files,
            COUNT(CASE WHEN storage_type IN ('contabo', 'wasabi', 'aws') THEN 1 END) as cloud_files,
            COALESCE(SUM(CASE WHEN storage_type = 'local' THEN file_size ELSE 0 END), 0) as local_size,
            COALESCE(SUM(CASE WHEN storage_type IN ('contabo', 'wasabi', 'aws') THEN file_size ELSE 0 END), 0) as cloud_size
        FROM videos 
        WHERE user_id = ? AND status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $storageInfo = $stmt->fetch();

    // Obtener config activa
    $activeStorageName = 'Desconocido';
    $stmt = db()->query("SELECT storage_type FROM storage_config WHERE is_active = 1 LIMIT 1");
    $activeConfig = $stmt->fetch();
    if ($activeConfig) {
        $activeStorageName = ucfirst($activeConfig['storage_type']);
    }

} catch (Exception $e) {
    die("Error crítico en account.php: " . $e->getMessage());
}

// Helpers seguros para formateo
function safeFormatSize($bytes) {
    if (function_exists('formatFileSize')) return formatFileSize($bytes);
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - <?= defined('SITE_NAME') ? SITE_NAME : 'EarnVids' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .account-container { max-width: 1000px; margin: 0 auto; }
        .account-header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 168, 255, 0.1) 100%);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        .account-avatar {
            width: 80px; height: 80px;
            background: var(--accent-green);
            color: #000;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; font-weight: bold;
            margin: 0 auto 15px;
        }
        .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .account-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
        }
        .section-title {
            font-size: 18px; font-weight: 600; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            color: var(--accent-green);
        }
        .info-row {
            display: flex; justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-secondary); }
        .api-key-box {
            background: #000;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            word-break: break-all;
            margin: 15px 0;
            border: 1px solid var(--border-color);
            cursor: pointer;
        }
        .api-key-box:hover { border-color: var(--accent-green); }
        .storage-pill {
            background: rgba(0, 168, 255, 0.1);
            color: var(--accent-blue);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        @media (max-width: 768px) { .account-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mi Cuenta</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="notification success" style="background: rgba(0,255,136,0.2); color: #00ff88; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="notification error" style="background: rgba(255,59,59,0.2); color: #ff3b3b; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <div class="account-container">
            <div class="account-header">
                <div class="account-avatar">
                    <?= strtoupper(substr($userInfo['username'], 0, 1)) ?>
                </div>
                <h2><?= htmlspecialchars($userInfo['username']) ?></h2>
                <p style="color: var(--text-secondary);"><?= htmlspecialchars($userInfo['email']) ?></p>
                <div style="margin-top: 10px;">
                    <span class="storage-pill" style="background: rgba(255,255,255,0.1); color: #fff;">
                        <?= ucfirst($userInfo['role']) ?>
                    </span>
                </div>
            </div>
            
            <div class="account-grid">
                <div class="account-section">
                    <h3 class="section-title">📊 Estadísticas</h3>
                    <div class="info-row">
                        <span class="info-label">Videos Subidos</span>
                        <span><?= number_format($userInfo['total_videos']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Espacio Total</span>
                        <span><?= safeFormatSize($userInfo['total_storage']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Reproducciones</span>
                        <span><?= number_format($userInfo['total_views']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Descargas</span>
                        <span><?= number_format($userInfo['total_downloads']) ?></span>
                    </div>
                </div>

                <div class="account-section">
                    <h3 class="section-title">
                        💾 Almacenamiento
                        <span class="storage-pill"><?= $activeStorageName ?> Activo</span>
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Archivos Locales</span>
                        <span>
                            <?= $storageInfo['local_files'] ?> 
                            <small class="text-muted">(<?= safeFormatSize($storageInfo['local_size']) ?>)</small>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Archivos Nube</span>
                        <span>
                            <?= $storageInfo['cloud_files'] ?> 
                            <small class="text-muted">(<?= safeFormatSize($storageInfo['cloud_size']) ?>)</small>
                        </span>
                    </div>
                    <p style="margin-top: 15px; font-size: 13px; color: var(--text-secondary);">
                        Tus nuevos videos se guardarán en <strong><?= $activeStorageName ?></strong>.
                    </p>
                </div>
            </div>
            
            <div class="account-section">
                <h3 class="section-title">🔒 Seguridad & API</h3>
                
                <form method="POST" style="margin-bottom: 30px; border-bottom: 1px solid var(--border-color); padding-bottom: 30px;">
                    <input type="hidden" name="action" value="change_password">
                    <h4 style="margin-bottom: 15px; color: var(--text-primary);">Cambiar Contraseña</h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label class="form-label">Actual</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nueva</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Nueva</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn">Actualizar</button>
                </form>
                
                <h4 style="margin-bottom: 10px; color: var(--text-primary);">Tu API Key</h4>
                <div class="api-key-box" onclick="copyToClipboard('<?= $userInfo['api_key'] ?>')">
                    <?= $userInfo['api_key'] ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="regenerate_api_key">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('¿Seguro?')">
                        Regenerar Key
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('API Key copiada');
            });
        }
    </script>
</body>
</html>
