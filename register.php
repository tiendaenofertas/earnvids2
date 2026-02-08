<?php
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // Verificar duplicados
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $error = 'El email o usuario ya está registrado';
        } else {
            // Crear usuario
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $apiKey = md5($email . time());
            
            try {
                $stmt = db()->prepare("INSERT INTO users (username, email, password, role, api_key) VALUES (?, ?, ?, 'user', ?)");
                $stmt->execute([$username, $email, $hashed, $apiKey]);
                $success = '¡Cuenta creada! <a href="/Login">Inicia sesión aquí</a>';
            } catch (PDOException $e) {
                $error = 'Error al registrar: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .auth-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .auth-box { background: var(--bg-card); padding: 40px; border-radius: 12px; width: 100%; max-width: 400px; border: 1px solid var(--border-color); }
        .auth-title { text-align: center; margin-bottom: 30px; color: var(--accent-green); font-size: 24px; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">Crear Cuenta</h1>
            
            <?php if ($error): ?>
                <div style="background: rgba(255,59,59,0.1); color: #ff3b3b; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: rgba(0,255,136,0.1); color: #00ff88; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center;">
                    <?= $success ?>
                </div>
            <?php else: ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($username ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Registrarse</button>
            </form>
            <?php endif; ?>
            
            <div style="margin-top: 20px; text-align: center; font-size: 0.9em; color: var(--text-secondary);">
                ¿Ya tienes cuenta? <a href="/Login" style="color: var(--accent-green);">Inicia sesión</a>
            </div>
        </div>
    </div>
</body>
</html>
