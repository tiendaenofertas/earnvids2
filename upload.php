<?php
// upload.php - Frontend de subida con validación visual en storage_users
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';
requireLogin();

// Lógica de Permisos de Almacenamiento (CORREGIDA)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userHasStorage = 'true'; 

if (!$isAdmin) {
    // Verificar en storage_users (NUEVA TABLA)
    $stmt = db()->prepare("
        SELECT id 
        FROM storage_users 
        WHERE user_id = ? 
        AND is_active = 1 
        AND access_key != '' 
        AND secret_key != ''
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userHasStorage = $stmt->fetch() ? 'true' : 'false';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Video - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .upload-icon { width: 80px; height: 80px; margin: 0 auto 20px; fill: var(--text-secondary); }
        #upload-progress { margin-top: 30px; }
        .progress-bar { width: 100%; height: 30px; background-color: var(--bg-hover); border-radius: 15px; overflow: hidden; margin-top: 10px; }
        .progress-fill { height: 100%; background-color: var(--accent-green); width: 0%; transition: width 0.3s ease; }
        .upload-area { cursor: pointer; transition: all 0.3s ease; border: 2px dashed var(--border-color); padding: 50px; text-align: center; border-radius: 10px; }
        .upload-area:hover, .upload-area.dragging { border-color: var(--accent-green); background: rgba(0,255,136,0.05); }
        .upload-area.locked { cursor: not-allowed; opacity: 0.7; border-color: var(--accent-red); }
        .upload-area.locked:hover { background: rgba(255, 59, 59, 0.05); }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Subir Video</h1>
        </div>
        
        <div class="upload-container">
            <div class="upload-area <?= $userHasStorage === 'false' ? 'locked' : '' ?>" 
                 id="upload-area" 
                 data-has-storage="<?= $userHasStorage ?>">
                
                <svg class="upload-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
                </svg>
                <h3>Haz clic o arrastra tu video aquí</h3>
                <p style="color: var(--text-secondary); margin-top: 10px;">MP4, WebM, MKV, AVI, MOV</p>
                <?php if($userHasStorage === 'false'): ?>
                    <p style="color: var(--accent-red); margin-top: 15px; font-weight: bold;">⚠️ Configuración de almacenamiento requerida</p>
                <?php endif; ?>
            </div>
            
            <input type="file" id="file-input" accept="video/*" style="display: none;">
            
            <div id="upload-progress" style="display: none;">
                <h4>Subiendo video...</h4>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-bar"></div>
                </div>
                <p id="progress-text" style="text-align: center; margin-top: 10px;">0%</p>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/upload.js"></script>
</body>
</html>
