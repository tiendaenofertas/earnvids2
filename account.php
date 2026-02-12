<?php
// account.php - Cuenta Usuario con Multi-Almacenamiento y Planes
require_once 'config/app.php';
require_once 'includes/db_connect.php'; 
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // --- 1. ACTIVAR UNA CONFIGURACIÓN (Switch Active) ---
    if ($_POST['action'] === 'set_active') {
        $configId = intval($_POST['config_id']);
        try {
            db()->beginTransaction();
            // Desactivar todas las de este usuario
            $stmt = db()->prepare("UPDATE storage_users SET is_active = 0 WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Activar la elegida
            $stmt = db()->prepare("UPDATE storage_users SET is_active = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$configId, $_SESSION['user_id']]);
            db()->commit();
            $message = "Almacenamiento activo actualizado."; $messageType = "success";
        } catch (Exception $e) {
            db()->rollBack(); $message = "Error: " . $e->getMessage(); $messageType = "error";
        }
    }

    // --- 2. AGREGAR / EDITAR CONFIGURACIÓN ---
    if ($_POST['action'] === 'save_storage') {
        $type = $_POST['storage_type'];
        $bucket = trim($_POST['bucket']);
        $endpoint = trim($_POST['endpoint']);
        $ak = trim($_POST['access_key']);
        $sk = trim($_POST['secret_key']);
        $region = trim($_POST['region']);

        if(empty($bucket) || empty($endpoint) || empty($ak)) {
            $message = "Faltan datos obligatorios."; $messageType = "error";
        } else {
            // Verificar si ya existe este tipo para el usuario
            $stmt = db()->prepare("SELECT id, secret_key FROM storage_users WHERE user_id = ? AND storage_type = ?");
            $stmt->execute([$_SESSION['user_id'], $type]);
            $exist = $stmt->fetch();

            if (empty($sk) && $exist) $sk = $exist['secret_key'];

            if ($exist) {
                $sql = "UPDATE storage_users SET access_key=?, secret_key=?, bucket=?, region=?, endpoint=? WHERE id=?";
                db()->prepare($sql)->execute([$ak, $sk, $bucket, $region, $endpoint, $exist['id']]);
                $message = "Configuración actualizada.";
            } else {
                // Si es la primera, la activamos por defecto
                $count = db()->query("SELECT count(*) FROM storage_users WHERE user_id=".$_SESSION['user_id'])->fetchColumn();
                $active = ($count == 0) ? 1 : 0;
                $sql = "INSERT INTO storage_users (user_id, storage_type, access_key, secret_key, bucket, region, endpoint, is_active) VALUES (?,?,?,?,?,?,?,?)";
                db()->prepare($sql)->execute([$_SESSION['user_id'], $type, $ak, $sk, $bucket, $region, $endpoint, $active]);
                $message = "Nuevo almacenamiento agregado.";
            }
            $messageType = "success";
        }
    }
    
    // --- 3. ELIMINAR CONFIGURACIÓN ---
    if ($_POST['action'] === 'delete_storage') {
        $id = intval($_POST['config_id']);
        db()->prepare("DELETE FROM storage_users WHERE id=? AND user_id=?")->execute([$id, $_SESSION['user_id']]);
        $message = "Configuración eliminada."; $messageType = "success";
    }
    
    // --- 4. COMPRAR PLAN ---
    if ($_POST['action'] === 'buy_plan') {
        // Redirección simulada a pasarela
        $planId = $_POST['plan_id'];
        $message="Redirigiendo a pasarela de pago para Plan ID: $planId..."; $messageType="success";
    }

    // --- 5. CAMBIAR PASSWORD ---
    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password']; $new = $_POST['new_password']; $confirm = $_POST['confirm_password'];
        $u = db()->prepare("SELECT password FROM users WHERE id=?"); $u->execute([$_SESSION['user_id']]); $u=$u->fetch();
        if(!password_verify($current, $u['password'])) { $message="Contraseña actual incorrecta"; $messageType="error"; }
        elseif($new!==$confirm) { $message="No coinciden"; $messageType="error"; }
        else { 
            db()->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);
            $message="Contraseña actualizada"; $messageType="success";
        }
    }

    // --- 6. REGENERAR API KEY ---
    if ($_POST['action'] === 'regenerate_api_key') {
        $newKey = md5($_SESSION['username'].time().rand());
        db()->prepare("UPDATE users SET api_key=? WHERE id=?")->execute([$newKey, $_SESSION['user_id']]);
        $message="API Key regenerada"; $messageType="success";
    }
}

// Datos
$userInfo = db()->prepare("SELECT * FROM users WHERE id = ?"); $userInfo->execute([$_SESSION['user_id']]); $userInfo=$userInfo->fetch();
$userStats = getUserStats($_SESSION['user_id']);
// Obtener TODAS las configs del usuario
$myStorages = db()->prepare("SELECT * FROM storage_users WHERE user_id = ? ORDER BY is_active DESC"); 
$myStorages->execute([$_SESSION['user_id']]); 
$myStorages = $myStorages->fetchAll();
$plans = db()->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
$membershipStatus = ($userInfo['membership_expiry'] && strtotime($userInfo['membership_expiry']) > time()) ? 'active' : 'expired';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Cuenta - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .account-container { max-width: 1100px; margin: 0 auto; }
        .account-section { background: var(--bg-card); padding: 25px; border-radius: 12px; margin-bottom: 20px; border: 1px solid var(--border-color); }
        .storage-item { display: flex; justify-content: space-between; align-items: center; background: var(--bg-secondary); padding: 15px; margin-bottom: 10px; border-radius: 8px; border: 1px solid var(--border-color); }
        .storage-item.active { border-color: var(--accent-green); box-shadow: 0 0 5px rgba(0,255,136,0.2); }
        .badge-active { background: var(--accent-green); color: #000; font-size: 0.7em; padding: 2px 6px; border-radius: 4px; font-weight: bold; margin-left: 10px; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 10px; background: var(--bg-primary); border: 1px solid var(--border-color); color: #fff; border-radius: 6px; }
        .btn-sm { padding: 5px 10px; font-size: 0.85em; cursor: pointer; }
        
        /* Planes Premium */
        .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 20px; }
        .price-card { background: linear-gradient(145deg, #141414, #0f0f0f); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 30px; text-align: center; position: relative; }
        .price-card.featured { border: 2px solid var(--accent-green); transform: scale(1.05); z-index: 2; }
        .popular-badge { position: absolute; top: 0; left: 50%; transform: translateX(-50%); background: var(--accent-green); color: #000; padding: 5px 15px; font-weight: bold; font-size: 0.8em; border-radius: 0 0 10px 10px; }
        .plan-price { font-size: 2.5em; font-weight: bold; margin: 15px 0; color: #fff; }
        .btn-plan { width: 100%; padding: 12px; background: transparent; border: 1px solid var(--text-secondary); color: #fff; border-radius: 8px; cursor: pointer; margin-top: 15px; }
        .btn-plan:hover { background: var(--accent-green); color: #000; border-color: var(--accent-green); }
        .featured .btn-plan { background: var(--accent-green); color: #000; border: none; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header"><h1 class="page-title">Mi Cuenta</h1></div>
        <?php if($message): ?><div class="notification <?= $messageType ?>"><?= $message ?></div><?php endif; ?>
        
        <div class="account-container">
            <div class="account-section" style="border-left: 5px solid <?= $membershipStatus=='active' ? '#00ff88' : '#ff3b3b' ?>;">
                <h3 style="color: <?= $membershipStatus=='active' ? '#00ff88' : '#ff3b3b' ?>">
                    <?= $membershipStatus=='active' ? 'Membresía Activa' : 'Membresía Inactiva / Expirada' ?>
                </h3>
                <p style="color:#aaa;">
                    <?= $membershipStatus=='active' ? 'Tu acceso vence el: <strong>'.date('d/m/Y', strtotime($userInfo['membership_expiry'])).'</strong>' : 'Suscríbete para acceder al contenido.' ?>
                </p>
            </div>

            <div class="account-section">
                <h3>Planes Disponibles</h3>
                <div class="pricing-grid">
                    <?php foreach($plans as $plan): $isPop = ($plan['price'] >= 15 && $plan['price'] <= 25); ?>
                    <div class="price-card <?= $isPop ? 'featured' : '' ?>">
                        <?php if($isPop): ?><div class="popular-badge">RECOMENDADO</div><?php endif; ?>
                        <h3><?= $plan['name'] ?></h3>
                        <div class="plan-price">$<?= number_format($plan['price'],0) ?></div>
                        <p style="color:#888;"><?= $plan['duration_days'] ?> días de acceso</p>
                        <form method="POST">
                            <input type="hidden" name="action" value="buy_plan">
                            <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                            <button class="btn-plan">ELEGIR PLAN</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="account-section">
                <h3 style="margin-bottom: 20px; color: var(--accent-green);">☁️ Mis Conexiones de Almacenamiento</h3>
                
                <?php if($myStorages): ?>
                    <?php foreach($myStorages as $st): ?>
                    <div class="storage-item <?= $st['is_active']?'active':'' ?>">
                        <div>
                            <strong><?= ucfirst($st['storage_type']) ?></strong>
                            <?php if($st['is_active']): ?><span class="badge-active">ACTIVO (Subidas)</span><?php endif; ?>
                            <div style="font-size:0.9em; color:#999;"><?= $st['bucket'] ?> - <?= $st['region'] ?></div>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <?php if(!$st['is_active']): ?>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="set_active">
                                    <input type="hidden" name="config_id" value="<?= $st['id'] ?>">
                                    <button class="btn btn-sm" style="background:var(--accent-green); color:#000; border:none;">Activar</button>
                                </form>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('¿Borrar?');">
                                    <input type="hidden" name="action" value="delete_storage">
                                    <input type="hidden" name="config_id" value="<?= $st['id'] ?>">
                                    <button class="btn btn-sm" style="background:#ff3b3b; color:#fff; border:none;">Borrar</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm" disabled style="opacity:0.5;">Activo</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#777; text-align:center;">No tienes almacenamientos configurados.</p>
                <?php endif; ?>

                <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h4>Agregar / Editar Conexión</h4>
                    <form method="POST">
                        <input type="hidden" name="action" value="save_storage">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div>
                                <label>Proveedor</label>
                                <select name="storage_type" class="form-control">
                                    <option value="wasabi">Wasabi S3</option>
                                    <option value="contabo">Contabo S3</option>
                                    <option value="aws">Amazon AWS</option>
                                </select>
                            </div>
                            <div><label>Región</label><input type="text" name="region" class="form-control" placeholder="Ej: us-east-1" required></div>
                        </div>
                        <label>Endpoint</label><input type="text" name="endpoint" class="form-control" placeholder="https://..." required>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div><label>Bucket</label><input type="text" name="bucket" class="form-control" required></div>
                            <div><label>Access Key</label><input type="text" name="access_key" class="form-control" required></div>
                        </div>
                        <label>Secret Key</label><input type="password" name="secret_key" class="form-control">
                        <div style="text-align:right;"><button class="btn">Guardar Conexión</button></div>
                    </form>
                </div>
            </div>
            
            <div class="account-section">
                <h3>Perfil</h3>
                <p>Usuario: <?= htmlspecialchars($userInfo['username']) ?></p>
                <div style="background:#000; padding:10px; margin-top:10px; font-family:monospace;"><?= $userInfo['api_key'] ?></div>
                <form method="POST" style="margin-top:10px;"><input type="hidden" name="action" value="regenerate_api_key"><button class="btn btn-sm">Regenerar API Key</button></form>
            </div>
            
            <div class="account-section">
                <h3>Seguridad</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <input type="password" name="current_password" class="form-control" placeholder="Contraseña Actual" required>
                    <input type="password" name="new_password" class="form-control" placeholder="Nueva Contraseña" required>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirmar Nueva" required>
                    <button class="btn">Cambiar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
