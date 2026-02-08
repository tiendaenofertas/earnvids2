<?php
// admin/index.php - Corregido
// Truco para encontrar app.php subiendo niveles hasta encontrarlo si ROOT_PATH no está definido
$configFile = __DIR__ . '/../config/app.php';
if (file_exists($configFile)) {
    require_once $configFile;
} else {
    // Fallback por si la estructura cambia
    require_once $_SERVER['DOCUMENT_ROOT'] . '/config/app.php';
}

require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

requireAdmin(); // Función de seguridad admin

$stats = getGlobalStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <?php include ROOT_PATH . '/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Panel de Administración</h1>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= number_format($stats['total_users'] ?? 0) ?></h3>
                <p>Usuarios</p>
            </div>
            <div class="stat-card">
                <h3><?= number_format($stats['total_videos'] ?? 0) ?></h3>
                <p>Videos</p>
            </div>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
