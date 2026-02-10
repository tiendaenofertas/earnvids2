<?php
// account.php - Gestión de cuenta y Almacenamiento Privado
require_once 'config/app.php';
require_once 'includes/db_connect.php'; 
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$message = '';
$messageType = ''; // success, error

// --- PROCESAR FORMULARIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Configuración de Almacenamiento Privado
    if ($_POST['action'] === 'update_storage') {
        $storageType = $_POST['storage_type'] ?? '';
        $accessKey = trim($_POST['access_key'] ?? '');
        $secretKey = trim($_POST['secret_key'] ?? '');
        $bucket = trim($_POST['bucket'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $endpoint = trim($_POST['endpoint'] ?? '');
        $usePrivate = isset($_POST['use_private_storage']) ? 1 : 0;

        if ($usePrivate && (empty($storageType) || empty($bucket) || empty($endpoint))) {
             $message = "Error: Si activas el almacenamiento privado, debes llenar los campos obligatorios.";
             $messageType = "error";
        } else {
            try {
                // Verificar si ya existe configuración para este usuario
                $stmt = db()->prepare("SELECT id, secret_key FROM storage_config WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $existingConfig = $stmt->fetch();

                // Manejo inteligente de la Secret Key: Si está vacía en el post, mantenemos la anterior
                if (empty($secretKey) && $existingConfig) {
                    $secretKey = $existingConfig['secret_key'];
                }

                if ($existingConfig) {
                    // UPDATE
                    $sql = "UPDATE storage_config SET 
                            storage_type = ?, access_key = ?, secret_key = ?, 
                            bucket = ?, region = ?, endpoint = ?, is_active = ? 
                            WHERE user_id = ?";
                    $stmt = db()->prepare($sql);
                    $stmt->execute([$storageType, $accessKey, $secretKey, $bucket, $region, $endpoint, $usePrivate, $_SESSION['user_id']]);
                } else {
                    // INSERT
                    if (!empty($accessKey) && !empty($secretKey)) {
                        $sql = "INSERT INTO storage_config 
                                (user_id, storage_type, access_key, secret_key, bucket, region, endpoint, is_active) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = db()->prepare($sql);
                        $stmt->execute([$_SESSION['user_id'], $storageType, $accessKey, $secretKey, $bucket, $region, $endpoint, $usePrivate]);
                    }
                }
                $message = "Configuración de almacenamiento actualizada correctamente.";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error al guardar configuración: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
    
    // 2. Cambiar Contraseña
    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $stmt = db()->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($current, $user['password'])) {
            $message = 'La contraseña actual es incorrecta.';
            $messageType = "error";
        } elseif (strlen($new) < 6) {
            $message = 'La nueva contraseña debe tener al menos 6 caracteres.';
            $messageType = "error";
        } elseif ($new !== $confirm) {
            $message = 'Las nuevas contraseñas no coinciden.';
            $messageType = "error";
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            db()->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $_SESSION['user_id']]);
            $message = '¡Contraseña actualizada correctamente!';
            $messageType = "success";
        }
    }
    
    // 3. Regenerar API Key
    if ($_POST['action'] === 'regenerate_api_key') {
        $newApiKey = md5($_SESSION['username'] . time() . mt_rand());
        db()->prepare("UPDATE users SET api_key = ? WHERE id = ?")->execute([$newApiKey, $_SESSION['user_id']]);
        $message = 'API Key regenerada.';
        $messageType = "success";
    }
}

// --- CARGAR DATOS ---
// 1. Info Usuario
$stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userInfo = $stmt->fetch();

// 2. Stats Rápidas
$userStats = getUserStats($_SESSION['user_id']);

// 3. Configuración de Almacenamiento Privado
$stmt = db()->prepare("SELECT * FROM storage_config WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$myStorage = $stmt->fetch();

// Valores por defecto para el formulario
$stType = $myStorage['storage_type'] ?? 'wasabi';
$stEndpoint = $myStorage['endpoint'] ?? '';
$stBucket = $myStorage['bucket'] ?? '';
$stRegion = $myStorage['region'] ?? '';
$stAccess = $myStorage['access_key'] ?? '';
$stIsActive = $myStorage['is_active'] ?? 0;
// No pasamos secret key por seguridad visual
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
        .account-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .account-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; height: 100%; }
        .section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--accent-green); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; color: var(--text-secondary); font-size: 0.9em; }
        .form-control { width: 100%; padding: 10px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 6px; }
        .form-control:focus { outline: none; border-color: var(--accent-green); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .api-key-box { background: #000; padding: 15px; border-radius: 6px; font-family: monospace; word-break: break-all; margin: 10px 0; border: 1px solid var(--border-color); cursor: pointer; transition: all 0.2s; }
        .api-key-box:hover { border-color: var(--accent-green); }
        
        .notification { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .notification.success { background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.2); }
        .notification.error { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); }
        
        .toggle-switch { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; cursor: pointer; }
        .toggle-switch input { width: 18px; height: 18px; accent-color: var(--accent-green); }
        
        @media (max-width: 768px) { .account-grid, .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración de Cuenta</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="notification <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="account-container">
            <div class="account-grid">
                <div class="account-section">
                    <h3 class="section-title">👤 Perfil y Estadísticas</h3>
                    <div style="margin-bottom: 20px;">
                        <p><strong>Usuario:</strong> <?= htmlspecialchars($userInfo['username']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($userInfo['email']) ?></p>
                        <p><strong>Rol:</strong> <span style="text-transform: capitalize; color: var(--accent-green);"><?= $userInfo['role'] ?></span></p>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: var(--bg-secondary); padding: 15px; border-radius: 8px;">
                        <div>
                            <small style="color: var(--text-secondary);">Videos</small>
                            <div style="font-size: 1.2em; font-weight: bold;"><?= number_format($userStats['total_videos']) ?></div>
                        </div>
                        <div>
                            <small style="color: var(--text-secondary);">Almacenamiento</small>
                            <div style="font-size: 1.2em; font-weight: bold;"><?= formatFileSize($userStats['total_storage']) ?></div>
                        </div>
                    </div>

                    <div style="margin-top: 25px;">
                        <h4 style="font-size: 14px; margin-bottom: 10px; color: var(--text-secondary);">API KEY (Click para copiar)</h4>
                        <div class="api-key-box" onclick="copyToClipboard('<?= $userInfo['api_key'] ?>')">
                            <?= $userInfo['api_key'] ?>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="regenerate_api_key">
                            <button type="submit" class="btn btn-secondary" style="width: 100%;" onclick="return confirm('¿Regenerar API Key? Tendrás que actualizar tus apps.')">Regenerar Key</button>
                        </form>
                    </div>
                </div>

                <div class="account-section">
                    <h3 class="section-title">🔒 Seguridad</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirmar Nueva</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        <button type="submit" class="btn" style="width: 100%;">Actualizar Contraseña</button>
                    </form>
                </div>
            </div>

            <div class="account-section">
                <h3 class="section-title">☁️ Configuración de Almacenamiento Privado</h3>
                <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 0.9em;">
                    Conecta tu propio almacenamiento S3 compatible (Wasabi, Contabo, AWS). 
                    Tus videos se subirán directamente a tu bucket y tú controlas los archivos.
                </p>

                <form method="POST">
                    <input type="hidden" name="action" value="update_storage">
                    
                    <label class="toggle-switch">
                        <input type="checkbox" name="use_private_storage" value="1" <?= $stIsActive ? 'checked' : '' ?> onchange="toggleStorageForm(this.checked)">
                        <span style="font-weight: bold; color: var(--text-primary);">Usar mi propio almacenamiento</span>
                    </label>

                    <div id="storage-form" style="display: <?= $stIsActive ? 'block' : 'none' ?>; border-top: 1px solid var(--border-color); padding-top: 20px; margin-top: 10px;">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Proveedor / Tipo</label>
                                <select name="storage_type" class="form-control">
                                    <option value="wasabi" <?= $stType == 'wasabi' ? 'selected' : '' ?>>Wasabi S3</option>
                                    <option value="contabo" <?= $stType == 'contabo' ? 'selected' : '' ?>>Contabo S3</option>
                                    <option value="aws" <?= $stType == 'aws' ? 'selected' : '' ?>>Amazon AWS S3</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Región</label>
                                <input type="text" name="region" class="form-control" value="<?= htmlspecialchars($stRegion) ?>" placeholder="Ej: us-east-1">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Endpoint URL (Con https://)</label>
                            <input type="text" name="endpoint" class="form-control" value="<?= htmlspecialchars($stEndpoint) ?>" placeholder="Ej: https://s3.us-east-1.wasabisys.com">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Bucket Name</label>
                                <input type="text" name="bucket" class="form-control" value="<?= htmlspecialchars($stBucket) ?>" placeholder="nombre-de-tu-bucket">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Access Key</label>
                                <input type="text" name="access_key" class="form-control" value="<?= htmlspecialchars($stAccess) ?>" placeholder="AKIA...">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Secret Key</label>
                            <input type="password" name="secret_key" class="form-control" placeholder="<?= !empty($myStorage['secret_key']) ? '(Oculto por seguridad - Dejar vacío para mantener actual)' : 'Ingresa tu Secret Key' ?>">
                        </div>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="btn">Guardar Configuración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('¡Copiado al portapapeles!');
            });
        }
        
        function toggleStorageForm(isChecked) {
            const form = document.getElementById('storage-form');
            form.style.display = isChecked ? 'block' : 'none';
        }
    </script>
</body>
</html>
