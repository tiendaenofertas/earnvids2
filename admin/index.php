<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
requireLogin();

$stats = isAdmin() ? getGlobalStats() : getUserStats($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content" id="dashboard-page">
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <button class="btn" onclick="location.href='/upload.php'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
                </svg>
                Subir Video
            </button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-label">Usuarios activos</p>
                        <p class="stat-value"><?= $stats['total_users'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-label">Videos totales</p>
                        <p class="stat-value"><?= $stats['total_videos'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-label">Almacenamiento usado</p>
                        <p class="stat-value"><?= formatFileSize($stats['total_storage'] ?? 0) ?></p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="stat-label">Vistas hoy</p>
                        <p class="stat-value"><?= $stats['views_today'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>