<?php
// admin/storage.php - Gestión Global de Almacenamiento (Admin) - Corregido
require_once '../config/app.php';
require_once '../includes/functions.php';
// Incluimos el gestor para tener acceso a la clase SimpleS3ClientV4 real
require_once '../includes/storage_manager.php'; 

requireAdmin();

$message = '';
$messageType = '';

// Procesar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // --- Acción: Activar/Desactivar Globalmente ---
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
        $storageType = trim($_POST['storage_type'] ?? '');
        $accessKey = trim($_POST['access_key'] ?? '');
        $secretKey = trim($_POST['secret_key'] ?? '');
        $bucket = trim($_POST['bucket'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $endpoint = trim($_POST['endpoint'] ?? '');
        
        if (empty($bucket) && $storageType !== 'local') {
            $message = "Error: El nombre del Bucket es obligatorio.";
            $messageType = "error";
        } else {
            // Verificar si existe registro, si no, crear
            $check = db()->prepare("SELECT id FROM storage_config WHERE storage_type = ?");
            $check->execute([$storageType]);
            
            if ($check->fetch()) {
                $stmt = db()->prepare("UPDATE storage_config SET access_key=?, secret_key=?, bucket=?, region=?, endpoint=? WHERE storage_type=?");
                $res = $stmt->execute([$accessKey, $secretKey, $bucket, $region, $endpoint, $storageType]);
            } else {
                $stmt = db()->prepare("INSERT INTO storage_config (storage_type, access_key, secret_key, bucket, region, endpoint, is_active) VALUES (?, ?, ?, ?, ?, ?, 0)");
                $res = $stmt->execute([$storageType, $accessKey, $secretKey, $bucket, $region, $endpoint]);
            }
            
            if ($res) {
                $message = "Configuración de " . ucfirst($storageType) . " guardada.";
                $messageType = "success";
            } else {
                $message = "Error al guardar en base de datos.";
                $messageType = "error";
            }
        }
    }

    // --- Acción: Probar Conexión (REAL) ---
    if ($action === 'test_connection') {
        $storageType = trim($_POST['storage_type'] ?? '');
        $accessKey = trim($_POST['access_key'] ?? '');
        $secretKey = trim($_POST['secret_key'] ?? '');
        $bucket = trim($_POST['bucket'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $endpoint = trim($_POST['endpoint'] ?? '');

        if ($storageType !== 'local') {
            try {
                if(empty($accessKey) || empty($secretKey) || empty($endpoint) || empty($bucket)) {
                     throw new Exception("Faltan datos para realizar la prueba.");
                }

                // Usamos la clase real definida en storage_manager.php
                if (!class_exists('SimpleS3ClientV4')) {
                    throw new Exception("La clase del cliente S3 no está disponible.");
                }

                $s3 = new SimpleS3ClientV4($accessKey, $secretKey, $endpoint, $region);

                // Crear un archivo temporal real para probar la subida
                $testContent = "Prueba de conexión exitosa - " . date('Y-m-d H:i:s');
                $testFileName = 'connection_test_' . time() . '.txt';
                $tempFilePath = sys_get_temp_dir() . '/' . $testFileName;
                
                if (file_put_contents($tempFilePath, $testContent) === false) {
                    throw new Exception("No se pudo crear el archivo temporal local para la prueba.");
                }

                // 1. Intentar Subir (PUT)
                if ($s3->put($bucket, $testFileName, $tempFilePath, 'text/plain')) {
                    
                    // 2. Intentar Borrar (DELETE) para no dejar basura
                    $deleteOk = $s3->delete($bucket, $testFileName);
                    
                    if ($deleteOk) {
                        $message = "✅ Conexión Exitosa: Se pudo subir y eliminar archivos en el Bucket correctamente.";
                        $messageType = "success";
                    } else {
                        $message = "⚠️ Conexión Parcial: Se pudo subir el archivo, pero falló al eliminarlo. Verifique permisos de borrado.";
                        $messageType = "warning"; // Usamos clase error visualmente si warning no existe en CSS, o modificamos abajo
                    }
                    
                } else {
                    throw new Exception("Falló la subida del archivo. Verifique credenciales y permisos de escritura.");
                }

                // Limpieza local
                @unlink($tempFilePath);

            } catch (Exception $e) {
                $message = "❌ Error de Conexión: " . $e->getMessage();
                $messageType = "error";
                // Limpieza local en caso de error
                if (isset($tempFilePath) && file_exists($tempFilePath)) @unlink($tempFilePath);
            }
        } else {
             $message = "El almacenamiento local no requiere prueba de conexión API.";
             $messageType = "success";
        }
    }
}

// Obtener configuraciones
$stmt = db()->query("SELECT * FROM storage_config ORDER BY storage_type");
$storageConfigs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Almacenamiento - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .storage-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .storage-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; }
        .storage-card.active { border-color: var(--accent-green); box-shadow: 0 0 0 1px var(--accent-green); }
        .storage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 10px; background: var(--bg-primary); border: 1px solid var(--border-color); color: #fff; border-radius: 6px; }
        .btn-test { background: transparent; border: 1px solid var(--text-secondary); color: var(--text-secondary); cursor: pointer; padding: 8px 15px; border-radius: 6px; }
        .btn-test:hover { border-color: #fff; color: #fff; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header"><h1 class="page-title">Configuración de Almacenamiento (Admin)</h1></div>

        <?php if ($message): ?>
            <div class="notification <?= ($messageType === 'success' || $messageType === 'warning') ? 'success' : 'error' ?>" 
                 style="margin-bottom: 20px; padding: 15px; 
                 background: <?= $messageType === 'success' ? 'rgba(0,255,136,0.1)' : ($messageType === 'warning' ? 'rgba(255, 193, 7, 0.1)' : 'rgba(255,59,59,0.1)') ?>; 
                 color: <?= $messageType === 'success' ? '#00ff88' : ($messageType === 'warning' ? '#ffc107' : '#ff3b3b') ?>; 
                 border-radius: 8px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="storage-cards">
            <?php foreach ($storageConfigs as $config): ?>
            <div class="storage-card <?= $config['is_active'] ? 'active' : '' ?>">
                <div class="storage-header">
                    <h3 style="text-transform: capitalize;"><?= $config['storage_type'] ?> S3</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="storage_type" value="<?= $config['storage_type'] ?>">
                        <label style="cursor: pointer; display: flex; align-items: center; gap: 5px;">
                            <input type="checkbox" name="is_active" value="1" <?= $config['is_active'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span>Activo</span>
                        </label>
                    </form>
                </div>
                
                <?php if ($config['storage_type'] !== 'local'): ?>
                    <form method="POST">
                        <input type="hidden" name="storage_type" value="<?= $config['storage_type'] ?>">
                        <input type="text" name="endpoint" class="form-control" value="<?= htmlspecialchars($config['endpoint'] ?? '') ?>" placeholder="Endpoint URL (https://...)" required>
                        <input type="text" name="region" class="form-control" value="<?= htmlspecialchars($config['region'] ?? '') ?>" placeholder="Región (ej: us-east-1)" required>
                        <input type="text" name="bucket" class="form-control" value="<?= htmlspecialchars($config['bucket'] ?? '') ?>" placeholder="Bucket Name" required>
                        <input type="text" name="access_key" class="form-control" value="<?= htmlspecialchars($config['access_key'] ?? '') ?>" placeholder="Access Key" required>
                        <input type="password" name="secret_key" class="form-control" value="<?= htmlspecialchars($config['secret_key'] ?? '') ?>" placeholder="Secret Key" required>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" name="action" value="update" class="btn">Guardar</button>
                            <button type="submit" name="action" value="test_connection" class="btn-test">Probar Conexión</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p style="color: #999;">Almacenamiento local en disco servidor.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
