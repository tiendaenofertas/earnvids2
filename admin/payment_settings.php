<?php
// admin/payment_settings.php - Configuración de Pasarela y Planes
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php'; // Aseguramos conexión DB
requireAdmin();

$message = '';
$messageType = '';

// --- PROCESAR FORMULARIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Actualizar Credenciales de NowPayments
    if (isset($_POST['action']) && $_POST['action'] === 'update_keys') {
        $apiKey = trim($_POST['api_key'] ?? '');
        $ipnSecret = trim($_POST['ipn_secret'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            // Usamos ON DUPLICATE KEY UPDATE para asegurar que siempre haya una fila ID 1
            $stmt = db()->prepare("
                INSERT INTO payment_settings (id, api_key, ipn_secret, is_active) 
                VALUES (1, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE api_key = ?, ipn_secret = ?, is_active = ?
            ");
            $stmt->execute([$apiKey, $ipnSecret, $isActive, $apiKey, $ipnSecret, $isActive]);
            
            $message = "Credenciales actualizadas correctamente.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error al guardar: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    // 2. Actualizar Un Plan Específico
    if (isset($_POST['action']) && $_POST['action'] === 'update_plan') {
        $planId = intval($_POST['plan_id']);
        $price = floatval($_POST['price']);
        $duration = intval($_POST['duration']);
        $planActive = isset($_POST['plan_active']) ? 1 : 0;
        
        try {
            $stmt = db()->prepare("UPDATE plans SET price = ?, duration_days = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$price, $duration, $planActive, $planId]);
            
            $message = "Plan actualizado correctamente.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error al actualizar plan: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// --- CARGAR DATOS ---
// 1. Configuración de Pagos
$stmt = db()->query("SELECT * FROM payment_settings WHERE id = 1");
$config = $stmt->fetch();
if (!$config) $config = ['api_key' => '', 'ipn_secret' => '', 'is_active' => 0];

// 2. Planes
$stmt = db()->query("SELECT * FROM plans ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Pagos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .config-grid { display: grid; grid-template-columns: 1fr; gap: 30px; }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; }
        .section-header { margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .section-title { font-size: 18px; font-weight: 600; color: var(--text-primary); margin: 0; }
        
        /* Estilos para Planes */
        .plans-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .plan-card { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; position: relative; transition: border-color 0.3s; }
        .plan-card:hover { border-color: var(--accent-green); }
        .plan-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .plan-name { font-weight: bold; font-size: 1.1em; color: var(--accent-green); }
        .plan-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
        .badge-active { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); }
        .badge-inactive { background: rgba(255, 59, 59, 0.1); color: var(--accent-red); }
        
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; color: var(--text-secondary); font-size: 0.9em; }
        .form-control { width: 100%; padding: 10px; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 6px; }
        .form-control:focus { outline: none; border-color: var(--accent-green); }
        
        .toggle-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .toggle-switch input { width: 18px; height: 18px; accent-color: var(--accent-green); }
        
        .btn-save { background: var(--accent-green); color: #000; font-weight: bold; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; transition: opacity 0.2s; width: 100%; }
        .btn-save:hover { opacity: 0.9; }
        
        .notification { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .notification.success { background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.2); }
        .notification.error { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración de Pago</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="notification <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="config-grid">
            
            <div class="card">
                <div class="section-header">
                    <h3 class="section-title">🔌 Credenciales NowPayments</h3>
                    <div style="font-size: 0.9em; color: var(--text-secondary);">Conexión con Pasarela</div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_keys">
                    
                    <div class="form-group">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" value="1" <?= $config['is_active'] ? 'checked' : '' ?>>
                            <span>Habilitar Pasarela de Pagos</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <input type="text" name="api_key" class="form-control" value="<?= htmlspecialchars($config['api_key']) ?>" placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">IPN Secret Key</label>
                        <input type="password" name="ipn_secret" class="form-control" value="<?= htmlspecialchars($config['ipn_secret']) ?>" placeholder="Clave secreta para validar pagos">
                    </div>
                    
                    <button type="submit" class="btn-save">Guardar Credenciales</button>
                </form>
            </div>
            
            <div class="card">
                <div class="section-header">
                    <h3 class="section-title">💎 Planes de Membresía</h3>
                    <div style="font-size: 0.9em; color: var(--text-secondary);">Edita precios y duración</div>
                </div>
                
                <div class="plans-container">
                    <?php foreach ($plans as $plan): ?>
                    <div class="plan-card">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_plan">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            
                            <div class="plan-header">
                                <span class="plan-name"><?= htmlspecialchars($plan['name']) ?></span>
                                <span class="plan-badge <?= $plan['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $plan['is_active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Precio (USD)</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $plan['price'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Duración (Días)</label>
                                <input type="number" name="duration" class="form-control" value="<?= $plan['duration_days'] ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="plan_active" value="1" <?= $plan['is_active'] ? 'checked' : '' ?>>
                                    <span style="font-size: 0.9em;">Plan Visible</span>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-secondary" style="width: 100%; padding: 8px;">Actualizar Plan</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>