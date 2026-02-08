<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
// Incluimos el gestor para poder probar la conexión usando la clase S3 actualizada
require_once '../includes/storage_manager.php'; 

requireAdmin();

$message = '';
$messageType = '';

// Procesar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- Acción: Activar/Desactivar ---
    if ($action === 'toggle') {
        $storageType = $_POST['storage_type'] ?? '';
        $isActive = $_POST['is_active'] ?? 0;
        
        try {
            db()->beginTransaction();
            // Primero desactivamos todos
            db()->exec("UPDATE storage_config SET is_active = 0");
            
            // Si se solicitó activar, activamos el seleccionado
            if ($isActive) {
                $stmt = db()->prepare("UPDATE storage_config SET is_active = 1 WHERE storage_type = ?");
                $stmt->execute([$storageType]);
            }
            db()->commit();
            $message = "Almacenamiento activo actualizado correctamente.";
            $messageType = "success";
        } catch (Exception $e) {
            db()->rollBack();
            $message = "Error al cambiar estado: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    // --- Acción: Guardar Configuración ---
    if ($action === 'update') {
        // Usamos trim() para eliminar espacios en blanco accidentales (Causa #1 de error en Wasabi)
        $storageType = trim($_POST['storage_type'] ?? '');
        $accessKey = trim($_POST['access_key'] ?? '');
        $secretKey = trim($_POST['secret_key'] ?? '');
        $bucket = trim($_POST['bucket'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $endpoint = trim($_POST['endpoint'] ?? '');
        
        // Validación básica
        if (empty($bucket) || empty($accessKey) || empty($secretKey)) {
            $message = "Error: Access Key, Secret Key y Bucket son obligatorios.";
            $messageType = "error";
        } else {
            $stmt = db()->prepare("
                UPDATE storage_config 
                SET access_key = ?, secret_key = ?, bucket = ?, region = ?, endpoint = ?
                WHERE storage_type = ?
            ");
            
            if ($stmt->execute([$accessKey, $secretKey, $bucket, $region, $endpoint, $storageType])) {
                $message = "Configuración de " . ucfirst($storageType) . " guardada correctamente.";
                $messageType = "success";
            } else {
                $message = "Error al guardar en base de datos.";
                $messageType = "error";
            }
        }
    }

    // --- Acción: Probar Conexión (Nueva Funcionalidad) ---
    if ($action === 'test_connection') {
        $storageType = trim($_POST['storage_type'] ?? '');
        $accessKey = trim($_POST['access_key'] ?? '');
        $secretKey = trim($_POST['secret_key'] ?? '');
        $bucket = trim($_POST['bucket'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $endpoint = trim($_POST['endpoint'] ?? '');

        if ($storageType !== 'local') {
            try {
                // Instanciamos el cliente S3 V4 directamente con los datos del formulario
                // Esto valida las credenciales SIN tener que guardarlas primero
                $s3 = new SimpleS3ClientV4($accessKey, $secretKey, $endpoint, $region);
                
                // Intentar subir un archivo pequeño de prueba
                $testFileName = 'connection_test_' . time() . '.txt';
                $tempFile = tempnam(sys_get_temp_dir(), 'test');
                file_put_contents($tempFile, 'Test de conexión exitoso.');
                
                // Prueba 1: Subir
                $s3->putObject($bucket, $testFileName, $tempFile, 'text/plain');
                
                // Prueba 2: Borrar (para limpiar)
                $s3->deleteObject($bucket, $testFileName);
                
                unlink($tempFile);
                
                $message = "✅ ¡Conexión Exitosa! Las credenciales de Wasabi/S3 funcionan.";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "❌ Error de Conexión: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Obtener configuraciones frescas
$stmt = db()->query("SELECT * FROM storage_config ORDER BY storage_type");
$storageConfigs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Almacenamiento - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .storage-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .storage-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; position: relative; }
        .storage-card.active { border-color: var(--accent-green); box-shadow: 0 0 0 1px var(--accent-green); }
        .storage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .storage-title { font-size: 20px; font-weight: 600; text-transform: capitalize; color: var(--text-primary); }
        
        .storage-status { display: inline-block; padding: 4px 12px; background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); font-size: 12px; font-weight: 600; border-radius: 20px; margin-bottom: 15px; }
        .storage-status.inactive { background-color: rgba(155, 155, 155, 0.1); color: var(--text-secondary); }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; color: var(--text-secondary); font-size: 0.9em; }
        .form-control { width: 100%; padding: 10px; background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 6px; }
        .form-control:focus { outline: none; border-color: var(--accent-green); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 136, 0.2); }
        .alert-error { background: rgba(255, 87, 87, 0.1); color: #ff5757; border: 1px solid rgba(255, 87, 87, 0.2); }
        
        .btn-group { display: flex; gap: 10px; margin-top: 15px; }
        .btn-test { background: transparent; border: 1px solid var(--text-secondary); color: var(--text-secondary); }
        .btn-test:hover { border-color: var(--text-primary); color: var(--text-primary); }
        
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración de Almacenamiento</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="storage-cards">
            <?php foreach ($storageConfigs as $config): ?>
            <div class="storage-card <?= $config['is_active'] ? 'active' : '' ?>">
                <div class="storage-header">
                    <h3 class="storage-title">
                        <?php 
                            if($config['storage_type'] == 'wasabi') echo 'Wasabi S3';
                            elseif($config['storage_type'] == 'contabo') echo 'Contabo S3';
                            elseif($config['storage_type'] == 'aws') echo 'Amazon S3';
                            else echo 'Local Storage';
                        ?>
                    </h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="storage_type" value="<?= $config['storage_type'] ?>">
                        <label style="cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= $config['is_active'] ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span style="margin-left: 5px;">Activo</span>
                        </label>
                    </form>
                </div>
                
                <span class="storage-status <?= $config['is_active'] ? '' : 'inactive' ?>">
                    <?= $config['is_active'] ? 'En Uso' : 'Inactivo' ?>
                </span>
                
                <?php if ($config['storage_type'] === 'local'): ?>
                    <p style="color: var(--text-secondary); line-height: 1.5;">
                        El almacenamiento local guarda los videos directamente en el disco duro de tu servidor (VPS/Dedicado).
                        <br><br>
                        <strong>Ruta:</strong> <code>/uploads/videos/</code>
                    </p>
                <?php else: ?>
                    <form method="POST" class="config-form">
                        <input type="hidden" name="storage_type" value="<?= $config['storage_type'] ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Access Key</label>
                                <input type="text" name="access_key" class="form-control" 
                                       value="<?= htmlspecialchars($config['access_key'] ?? '') ?>" placeholder="Ej: AKIA..." required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Secret Key</label>
                                <input type="password" name="secret_key" class="form-control" 
                                       value="<?= htmlspecialchars($config['secret_key'] ?? '') ?>" placeholder="Ej: wJalr..." required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Bucket Name</label>
                                <input type="text" name="bucket" class="form-control" 
                                       value="<?= htmlspecialchars($config['bucket'] ?? '') ?>" placeholder="nombre-bucket" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Región</label>
                                <input type="text" name="region" class="form-control" 
                                       value="<?= htmlspecialchars($config['region'] ?? '') ?>" placeholder="Ej: us-east-1" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Endpoint URL</label>
                            <input type="text" name="endpoint" class="form-control" 
                                   value="<?= htmlspecialchars($config['endpoint'] ?? '') ?>" 
                                   placeholder="Ej: https://s3.us-east-1.wasabisys.com">
                            <small style="color: var(--text-secondary); font-size: 0.8em;">
                                Importante: Incluye <code>https://</code>. Para Wasabi/Contabo es obligatorio el endpoint completo de la región.
                            </small>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" name="action" value="update" class="btn btn-secondary">
                                Guardar Cambios
                            </button>
                            <button type="submit" name="action" value="test_connection" class="btn btn-test">
                                🔌 Probar Conexión
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
