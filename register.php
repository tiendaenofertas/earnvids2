<?php
// register.php - Registro blindado con CSRF y Sanitización
require_once 'config/app.php';
require_once 'includes/auth.php'; // Auth debe manejar session_start() internamente
require_once 'includes/functions.php';

// Asegurar que la sesión esté activa para el token CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// Generar Token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificar Token CSRF (Seguridad Crítica)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Error de seguridad: Token inválido. Recarga la página e intenta de nuevo.';
    } else {
        // 2. Sanitizar Entradas
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // 3. Validaciones
        if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'El usuario debe tener al menos 3 caracteres y solo letras/números.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email inválido.';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            // Intentar registro
            try {
                $result = Auth::register($username, $email, $password);
                if ($result['success']) {
                    $success = '¡Cuenta creada con éxito! Ahora puedes iniciar sesión.';
                    // Limpiar post para evitar reenvíos
                    $_POST = [];
                } else {
                    $error = $result['message'];
                }
            } catch (Exception $e) {
                $error = 'Ocurrió un error inesperado. Intenta más tarde.';
                error_log("Register Error: " . $e->getMessage());
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
    <title>Registrarse - <?= defined('SITE_NAME') ? SITE_NAME : 'EarnVids' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-box { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; }
        .login-logo { text-align: center; margin-bottom: 30px; }
        .success-message { background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center; border: 1px solid rgba(0, 255, 136, 0.2); }
        .error-message { background-color: rgba(255, 59, 59, 0.1); color: #ff3b3b; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center; border: 1px solid rgba(255, 59, 59, 0.2); }
        .btn-full { width: 100%; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <div class="logo">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="currentColor" style="color:var(--accent-green)">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    <h1 style="font-size: 24px; margin-top: 10px;">EARN<span>VIDS</span></h1>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn btn-full">Crear Cuenta</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
                ¿Ya tienes cuenta? <a href="/login.php" style="color: var(--accent-green);">Iniciar Sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
