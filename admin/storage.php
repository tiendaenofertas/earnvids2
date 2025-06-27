<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
requireAdmin();

$stmt = db()->query("SELECT * FROM storage_config ORDER BY storage_type");
$storageConfigs = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle') {
        $storageType = $_POST['storage_type'] ?? '';
        $isActive = $_POST['is_active'] ?? 0;
        
        db()->exec("UPDATE storage_config SET is_active = 0");
        
        if ($isActive) {
            $stmt = db()->prepare("UPDATE storage_config SET is_active = 1 WHERE storage_type = ?");
            $stmt->execute([$storageType]);
        }
        
        header('Location: /admin/storage.php');
        exit;
    }
    
    if ($action === 'update') {
        $storageType = $_POST['storage_type'] ?? '';
        $accessKey = $_POST['access_key'] ?? '';
        $secretKey = $_POST['secret_key'] ?? '';
        $bucket = $_POST['bucket'] ?? '';
        $region = $_POST['region'] ?? '';
        $endpoint = $_POST['endpoint'] ?? '';
        
        $stmt = db()->prepare("
            UPDATE storage_config 
            SET access_key = ?, secret_key = ?, bucket = ?, region = ?, endpoint = ?
            WHERE storage_type = ?
        ");
        $stmt->execute([$accessKey, $secretKey, $bucket, $region, $endpoint, $storageType]);
        
        header('Location: /admin/storage.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Almacenamiento - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .storage-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .storage-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; position: relative; }
        .storage-card.active { border-color: var(--accent-green); box-shadow: 0 0 0 1px var(--accent-green); }
        .storage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .storage-title { font-size: 20px; font-weight: 600; text-transform: capitalize; }
        .storage-status { display: inline-block; padding: 4px 12px; background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); font-size: 12px; font-weight: 600; border-radius: 20px; margin-bottom: 15px; }
        .storage-status.inactive { background-color: rgba(155, 155, 155, 0.1); color: var(--text-secondary); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración de Almacenamiento</h1>
        </div>
        
        <div class="storage-cards">
            <?php foreach ($storageConfigs as $config): ?>
            <div class="storage-card <?= $config['is_active'] ? 'active' : '' ?>">
                <div class="storage-header">
                    <h3 class="storage-title"><?= ucfirst($config['storage_type']) ?></h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="storage_type" value="<?= $config['storage_type'] ?>">
                        <label>
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= $config['is_active'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            Activo
                        </label>
                    </form>
                </div>
                
                <span class="storage-status <?= $config['is_active'] ? '' : 'inactive' ?>">
                    <?= $config['is_active'] ? 'Activo' : 'Inactivo' ?>
                </span>
                
                <?php if ($config['storage_type'] === 'local'): ?>
                    <p style="color: var(--text-secondary);">Almacenamiento en el servidor local. No requiere configuración adicional.</p>
                <?php else: ?>
                    <form method="POST" class="config-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="storage_type" value="<?= $config['storage_type'] ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Access Key</label>
                                <input type="text" name="access_key" class="form-control" 
                                       value="<?= htmlspecialchars($config['access_key'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Secret Key</label>
                                <input type="password" name="secret_key" class="form-control" 
                                       value="<?= htmlspecialchars($config['secret_key'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Bucket</label>
                                <input type="text" name="bucket" class="form-control" 
                                       value="<?= htmlspecialchars($config['bucket'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Región</label>
                                <input type="text" name="region" class="form-control" 
                                       value="<?= htmlspecialchars($config['region'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <?php if ($config['storage_type'] === 'contabo' || $config['storage_type'] === 'wasabi'): ?>
                        <div class="form-group">
                            <label class="form-label">Endpoint</label>
                            <input type="text" name="endpoint" class="form-control" 
                                   value="<?= htmlspecialchars($config['endpoint'] ?? '') ?>">
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-secondary">Guardar Configuración</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>