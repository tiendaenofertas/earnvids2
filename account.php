<?php
// account.php - Reparado con rutas absolutas
// Carga segura de configuración
require_once __DIR__ . '/config/app.php'; 
require_once ROOT_PATH . '/includes/db_connect.php'; 
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $new_pass = $_POST['new_password'] ?? '';
    if (strlen($new_pass) >= 6) {
        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($new_pass, PASSWORD_BCRYPT), $userId]);
        $message = "Contraseña actualizada.";
    } else {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    }
}

// Obtener datos
$stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch();

// Si no hay datos, cerrar sesión para evitar bucles
if (!$userInfo) { session_destroy(); header('Location: /login.php'); exit; }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <?php include ROOT_PATH . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mi Cuenta</h1>
        </div>
        
        <?php if ($message) echo "<div class='notification success' style='background:#0f03;padding:10px;border-radius:5px;margin-bottom:10px;'>$message</div>"; ?>
        <?php if ($error) echo "<div class='notification error' style='background:#f003;padding:10px;border-radius:5px;margin-bottom:10px;'>$error</div>"; ?>
        
        <div class="account-container">
            <div class="account-section" style="background:#1a1a1a; padding:20px; border-radius:10px;">
                <h3><?= htmlspecialchars($userInfo['username']) ?></h3>
                <p><?= htmlspecialchars($userInfo['email']) ?></p>
                <br>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <label>Nueva Contraseña:</label><br>
                    <input type="password" name="new_password" class="form-control" style="width:100%;padding:10px;margin:5px 0;background:#333;border:1px solid #444;color:#fff;" required>
                    <button type="submit" class="btn" style="background:#00ff88;color:#000;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin-top:10px;">Actualizar</button>
                </form>
            </div>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
