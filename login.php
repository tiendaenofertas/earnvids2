<?php
// login.php - Login blindado
require_once 'config/app.php';
require_once 'includes/auth.php';

// Asegurar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// Generar Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verificar Token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Sesión expirada o inválida. Recarga la página.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        try {
            if (Auth::login($username, $password)) {
                // Regenerar sesión tras login exitoso (Evita Session Fixation)
                session_regenerate_id(true);
                header('Location: /admin/');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error del sistema.';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= defined('SITE_NAME') ? SITE_NAME : 'EarnVids' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-box { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 40px; width: 100%; max-width: 400px; }
        .login-logo { text-align: center; margin-bottom: 30px; }
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
                    <h1 style="font-size: 24px; margin-top: 10px;">XVIDS<span>PRO</span></h1>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="form-group">
                    <label for="username" class="form-label">Usuario o Email</label>
                    <input type="text" id="username" name="username" class="form-control" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-full">Iniciar Sesión</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--text-secondary);">
                ¿No tienes cuenta? <a href="/register.php" style="color: var(--accent-green);">Regístrate</a>
            </p>
        </div>
    </div>
</body>
</html>
