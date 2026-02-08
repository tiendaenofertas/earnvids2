<?php
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Actualizar último login
            db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            header('Location: /Dashboard'); // Redirección limpia
            exit;
        } else {
            $error = 'Credenciales inválidas';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .auth-box {
            background: var(--bg-card);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-color);
        }
        .auth-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--accent-green);
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">EarnVids</h1>
            
            <?php if ($error): ?>
                <div style="background: rgba(255,59,59,0.1); color: #ff3b3b; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Ingresar</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center; font-size: 0.9em; color: var(--text-secondary);">
                ¿No tienes cuenta? <a href="/Registro" style="color: var(--accent-green);">Regístrate aquí</a>
            </div>
        </div>
    </div>
</body>
</html>
