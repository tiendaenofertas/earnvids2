<?php
// ========== account.php ==========
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

$message = '';
$error = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Verificar contraseña actual
        $stmt = db()->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Contraseña actual incorrecta';
        } elseif (strlen($new_password) < 6) {
            $error = 'La nueva contraseña debe tener al menos 6 caracteres';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden';
        } else {
            // Actualizar contraseña
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            
            logActivity('change_password');
            $message = 'Contraseña actualizada exitosamente';
        }
    }
    
    if ($_POST['action'] === 'regenerate_api_key') {
        $newApiKey = md5($_SESSION['username'] . time());
        $stmt = db()->prepare("UPDATE users SET api_key = ? WHERE id = ?");
        $stmt->execute([$newApiKey, $_SESSION['user_id']]);
        
        logActivity('regenerate_api_key');
        $message = 'API Key regenerada exitosamente';
    }
}

// Obtener información del usuario
$stmt = db()->prepare("
    SELECT u.*, 
           COUNT(DISTINCT v.id) as total_videos,
           COALESCE(SUM(v.file_size), 0) as total_storage,
           COALESCE(SUM(v.views), 0) as total_views,
           COALESCE(SUM(v.downloads), 0) as total_downloads
    FROM users u
    LEFT JOIN videos v ON u.id = v.user_id AND v.status = 'active'
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$_SESSION['user_id']]);
$userInfo = $stmt->fetch();

// Obtener información de almacenamiento por tipo
$storageInfo = getUserStorageInfo($_SESSION['user_id']);

// Obtener almacenamiento activo actual
$activeStorage = getActiveStorage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .account-container {
            max-width: 1000px;
        }
        .account-header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 168, 255, 0.1) 100%);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
        }
        .account-avatar {
            width: 100px;
            height: 100px;
            background-color: var(--accent-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            font-weight: 700;
            color: var(--bg-primary);
        }
        .account-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .account-section {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: var(--text-secondary);
        }
        .api-key-container {
            background-color: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            margin: 15px 0;
        }
        .storage-indicator {
            display: inline-block;
            padding: 4px 12px;
            background-color: rgba(0, 255, 136, 0.1);
            color: var(--accent-green);
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            margin-left: 10px;
        }
        .storage-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .storage-stat {
            background-color: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .storage-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .storage-stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 5px;
        }
        @media (max-width: 768px) {
            .account-grid {
                grid-template-columns: 1fr;
            }
            .storage-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mi Cuenta</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="notification success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="notification error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="account-container">
            <div class="account-header">
                <div class="account-avatar">
                    <?= strtoupper(substr($userInfo['username'], 0, 1)) ?>
                </div>
                <h2><?= htmlspecialchars($userInfo['username']) ?></h2>
                <p style="color: var(--text-secondary);"><?= htmlspecialchars($userInfo['email']) ?></p>
                <span class="user-role <?= $userInfo['role'] ?>" style="margin-top: 10px; display: inline-block;">
                    <?= ucfirst($userInfo['role']) ?>
                </span>
            </div>
            
            <div class="account-grid">
                <div class="account-section">
                    <h3 class="section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                        </svg>
                        Información de la Cuenta
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Usuario</span>
                        <span><?= htmlspecialchars($userInfo['username']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span><?= htmlspecialchars($userInfo['email']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Miembro desde</span>
                        <span><?= date('d/m/Y', strtotime($userInfo['created_at'])) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Último acceso</span>
                        <span><?= $userInfo['last_login'] ? date('d/m/Y H:i', strtotime($userInfo['last_login'])) : 'Nunca' ?></span>
                    </div>
                </div>
                
                <div class="account-section">
                    <h3 class="section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                        </svg>
                        Estadísticas
                    </h3>
                    
                    <div class="info-row">
                        <span class="info-label">Videos subidos</span>
                        <span><?= number_format($userInfo['total_videos']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Almacenamiento usado</span>
                        <span><?= formatFileSize($userInfo['total_storage']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Vistas totales</span>
                        <span><?= number_format($userInfo['total_views']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Descargas totales</span>
                        <span><?= number_format($userInfo['total_downloads']) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Nueva sección de información de almacenamiento -->
            <div class="account-section" style="margin-bottom: 30px;">
                <h3 class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/>
                    </svg>
                    Almacenamiento
                    <span class="storage-indicator"><?= ucfirst($activeStorage['storage_type']) ?> Activo</span>
                </h3>
                
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    Actualmente, todos los nuevos archivos se almacenan en <strong><?= ucfirst($activeStorage['storage_type']) ?></strong>.
                </p>
                
                <div class="storage-stats">
                    <?php if ($storageInfo['local_files'] > 0): ?>
                    <div class="storage-stat">
                        <div class="storage-stat-value"><?= $storageInfo['local_files'] ?></div>
                        <div class="storage-stat-label">Archivos Locales</div>
                        <div style="font-size: 12px; margin-top: 5px;"><?= formatFileSize($storageInfo['local_size']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($storageInfo['contabo_files'] > 0): ?>
                    <div class="storage-stat">
                        <div class="storage-stat-value"><?= $storageInfo['contabo_files'] ?></div>
                        <div class="storage-stat-label">Archivos en Contabo</div>
                        <div style="font-size: 12px; margin-top: 5px;"><?= formatFileSize($storageInfo['contabo_size']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($storageInfo['wasabi_files'] > 0): ?>
                    <div class="storage-stat">
                        <div class="storage-stat-value"><?= $storageInfo['wasabi_files'] ?></div>
                        <div class="storage-stat-label">Archivos en Wasabi</div>
                        <div style="font-size: 12px; margin-top: 5px;"><?= formatFileSize($storageInfo['wasabi_size']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($storageInfo['aws_files'] > 0): ?>
                    <div class="storage-stat">
                        <div class="storage-stat-value"><?= $storageInfo['aws_files'] ?></div>
                        <div class="storage-stat-label">Archivos en AWS</div>
                        <div style="font-size: 12px; margin-top: 5px;"><?= formatFileSize($storageInfo['aws_size']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="account-section">
                <h3 class="section-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    Seguridad
                </h3>
                
                <form method="POST" style="margin-bottom: 30px;">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn">Cambiar Contraseña</button>
                </form>
                
                <hr style="border-color: var(--border-color); margin: 30px 0;">
                
                <h4 style="margin-bottom: 15px;">API Key</h4>
                <p style="color: var(--text-secondary); margin-bottom: 15px;">
                    Usa esta clave para acceder a la API de EARNVIDS
                </p>
                
                <div class="api-key-container" onclick="copyToClipboard('<?= $userInfo['api_key'] ?>')">
                    <?= $userInfo['api_key'] ?>
                </div>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="regenerate_api_key">
                    <button type="submit" class="btn btn-secondary" 
                            onclick="return confirm('¿Estás seguro? La clave anterior dejará de funcionar.')">
                        Regenerar API Key
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
