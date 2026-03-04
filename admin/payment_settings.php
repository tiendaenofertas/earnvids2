<?php
// admin/payment_settings.php - Configuración de Pasarelas y Planes Múltiples
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php'; // Aseguramos conexión DB
requireAdmin();

$message = '';
$messageType = '';

// --- INICIALIZAR REGISTRO SI NO EXISTE ---
// Garantiza que siempre exista el ID 1 para evitar errores de actualización
db()->query("INSERT IGNORE INTO payment_settings (id) VALUES (1)");

// --- PROCESAR FORMULARIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Actualizar Credenciales de NowPayments
    if (isset($_POST['action']) && $_POST['action'] === 'update_np_keys') {
        $apiKey = trim($_POST['api_key'] ?? '');
        $ipnSecret = trim($_POST['ipn_secret'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = db()->prepare("UPDATE payment_settings SET api_key = ?, ipn_secret = ?, is_active = ? WHERE id = 1");
            $stmt->execute([$apiKey, $ipnSecret, $isActive]);
            
            $message = "Credenciales de Criptomonedas (NowPayments) actualizadas.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error al guardar: " . $e->getMessage();
            $messageType = "error";
        }
    }
    
    // 2. Actualizar Credenciales de PayPal
    if (isset($_POST['action']) && $_POST['action'] === 'update_paypal_keys') {
        $paypalClientId = trim($_POST['paypal_client_id'] ?? '');
        $paypalSecret = trim($_POST['paypal_secret'] ?? '');
        $paypalActive = isset($_POST['paypal_active']) ? 1 : 0;
        
        try {
            $stmt = db()->prepare("UPDATE payment_settings SET paypal_client_id = ?, paypal_secret = ?, paypal_active = ? WHERE id = 1");
            $stmt->execute([$paypalClientId, $paypalSecret, $paypalActive]);
            
            $message = "Credenciales de PayPal actualizadas correctamente.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error al guardar PayPal: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // 3. Actualizar Credenciales de Bold
    if (isset($_POST['action']) && $_POST['action'] === 'update_bold_keys') {
        $boldApiKey = trim($_POST['bold_api_key'] ?? '');
        $boldSecret = trim($_POST['bold_secret'] ?? '');
        $boldActive = isset($_POST['bold_active']) ? 1 : 0;
        
        try {
            $stmt = db()->prepare("UPDATE payment_settings SET bold_api_key = ?, bold_secret = ?, bold_active = ? WHERE id = 1");
            $stmt->execute([$boldApiKey, $boldSecret, $boldActive]);
            
            $message = "Credenciales de Bold actualizadas correctamente.";
            $messageType = "success";
        } catch (Exception $e) {
            $message = "Error al guardar Bold: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // 4. Actualizar Un Plan Específico
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
if (!$config) {
    // Valores por defecto seguros para evitar advertencias de variables indefinidas
    $config = [
        'api_key' => '', 'ipn_secret' => '', 'is_active' => 0,
        'paypal_client_id' => '', 'paypal_secret' => '', 'paypal_active' => 0,
        'bold_api_key' => '', 'bold_secret' => '', 'bold_active' => 0
    ];
}

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
        .config-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-bottom: 30px; }
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; display: flex; flex-direction: column; }
        .card.active-gateway { border-color: var(--accent-green); box-shadow: 0 0 10px rgba(0, 255, 136, 0.1); }
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
        
        .toggle-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; margin-bottom: 15px; }
        .toggle-switch input { width: 18px; height: 18px; accent-color: var(--accent-green); }
        
        .btn-save { background: var(--bg-hover); color: var(--text-primary); border: 1px solid var(--border-color); padding: 10px 20px; border-radius: 6px; cursor: pointer; transition: all 0.2s; width: 100%; margin-top: auto; }
        .btn-save:hover { background: var(--accent-green); color: #000; border-color: var(--accent-green); }
        
        .notification { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: 500; }
        .notification.success { background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.2); }
        .notification.error { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración de Pasarelas</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="notification <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="config-grid">
            
            <div class="card <?= $config['is_active'] ? 'active-gateway' : '' ?>">
                <div class="section-header">
                    <h3 class="section-title">🪙 NowPayments</h3>
                    <div style="font-size: 0.8em; color: var(--text-secondary);">Criptomonedas</div>
                </div>
                <form method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
                    <input type="hidden" name="action" value="update_np_keys">
                    
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_active" value="1" <?= $config['is_active'] ? 'checked' : '' ?>>
                        <span style="font-weight: bold; color: <?= $config['is_active'] ? 'var(--accent-green)' : 'inherit' ?>;">Habilitar Pasarela</span>
                    </label>

                    <div class="form-group">
                        <label class="form-label">API Key</label>
                        <input type="text" name="api_key" class="form-control" value="<?= htmlspecialchars($config['api_key']) ?>" placeholder="XXXXXXXX-XXXXXXXX-XXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">IPN Secret Key</label>
                        <input type="password" name="ipn_secret" class="form-control" value="<?= htmlspecialchars($config['ipn_secret']) ?>" placeholder="Clave para validar pagos">
                    </div>
                    <button type="submit" class="btn-save">Guardar NowPayments</button>
                </form>
            </div>

            <div class="card <?= isset($config['bold_active']) && $config['bold_active'] ? 'active-gateway' : '' ?>">
                <div class="section-header">
                    <h3 class="section-title">💳 Bold.co</h3>
                    <div style="font-size: 0.8em; color: var(--text-secondary);">Tarjetas / PSE</div>
                </div>
                <form method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
                    <input type="hidden" name="action" value="update_bold_keys">
                    
                    <label class="toggle-switch">
                        <input type="checkbox" name="bold_active" value="1" <?= isset($config['bold_active']) && $config['bold_active'] ? 'checked' : '' ?>>
                        <span style="font-weight: bold; color: <?= isset($config['bold_active']) && $config['bold_active'] ? 'var(--accent-green)' : 'inherit' ?>;">Habilitar Pasarela</span>
                    </label>

                    <div class="form-group">
                        <label class="form-label">Llave de Identidad (API Key)</label>
                        <input type="text" name="bold_api_key" class="form-control" value="<?= htmlspecialchars($config['bold_api_key'] ?? '') ?>" placeholder="Llave pública de Bold">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Llave Secreta</label>
                        <input type="password" name="bold_secret" class="form-control" value="<?= htmlspecialchars($config['bold_secret'] ?? '') ?>" placeholder="Llave secreta de Bold">
                    </div>
                    <button type="submit" class="btn-save">Guardar Bold</button>
                </form>
            </div>

            <div class="card <?= isset($config['paypal_active']) && $config['paypal_active'] ? 'active-gateway' : '' ?>">
                <div class="section-header">
                    <h3 class="section-title">🌐 PayPal</h3>
                    <div style="font-size: 0.8em; color: var(--text-secondary);">Pagos Internacionales</div>
                </div>
                <form method="POST" style="display: flex; flex-direction: column; flex-grow: 1;">
                    <input type="hidden" name="action" value="update_paypal_keys">
                    
                    <label class="toggle-switch">
                        <input type="checkbox" name="paypal_active" value="1" <?= isset($config['paypal_active']) && $config['paypal_active'] ? 'checked' : '' ?>>
                        <span style="font-weight: bold; color: <?= isset($config['paypal_active']) && $config['paypal_active'] ? 'var(--accent-green)' : 'inherit' ?>;">Habilitar Pasarela</span>
                    </label>

                    <div class="form-group">
                        <label class="form-label">Nombre de usuario API / Client ID</label>
                        <input type="text" name="paypal_client_id" class="form-control" value="<?= htmlspecialchars($config['paypal_client_id'] ?? '') ?>" placeholder="ID de Cliente / Usuario API">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contraseña API / Secret</label>
                        <input type="password" name="paypal_secret" class="form-control" value="<?= htmlspecialchars($config['paypal_secret'] ?? '') ?>" placeholder="Firma / Secreto API">
                    </div>
                    <button type="submit" class="btn-save">Guardar PayPal</button>
                </form>
            </div>

        </div> <div class="card" style="margin-top: 20px;">
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
                            <label class="toggle-switch" style="background: transparent; padding: 0; margin-bottom: 20px;">
                                <input type="checkbox" name="plan_active" value="1" <?= $plan['is_active'] ? 'checked' : '' ?>>
                                <span style="font-size: 0.9em;">Plan Visible al Público</span>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn-save" style="background: var(--bg-primary);">Actualizar Plan</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
            
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
