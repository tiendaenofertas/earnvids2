<?php
// fix_admin_files.php - Arregla los archivos admin que están vacíos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>
    body { background: #0a0a0a; color: #fff; font-family: Arial; padding: 20px; }
    .success { color: #00ff88; }
    .error { color: #ff3b3b; }
    pre { background: #1a1a1a; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<h1>🔧 Reparador de Archivos Admin</h1>";

// ARCHIVO 1: admin/videos.php
$videosContent = '<?php
require_once \'../config/app.php\';
require_once \'../includes/functions.php\';
requireLogin();

$page = isset($_GET[\'page\']) ? max(1, intval($_GET[\'page\'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

if (isAdmin()) {
    $countStmt = db()->query("SELECT COUNT(*) FROM videos WHERE status = \'active\'");
    $totalVideos = $countStmt->fetchColumn();
    
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.status = \'active\'
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
} else {
    $countStmt = db()->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ? AND status = \'active\'");
    $countStmt->execute([$_SESSION[\'user_id\']]);
    $totalVideos = $countStmt->fetchColumn();
    
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.user_id = ? AND v.status = \'active\'
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION[\'user_id\'], $perPage, $offset]);
}

$videos = $stmt->fetchAll();
$totalPages = ceil($totalVideos / $perPage);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Videos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .videos-table { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; }
        .videos-table table { width: 100%; border-collapse: collapse; }
        .videos-table th { background-color: var(--bg-secondary); padding: 15px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: var(--text-secondary); }
        .videos-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
        .video-title-cell { display: flex; align-items: center; gap: 15px; }
        .video-thumb-small { width: 80px; height: 45px; background-color: var(--bg-secondary); border-radius: 6px; overflow: hidden; }
        .video-thumb-small img { width: 100%; height: 100%; object-fit: cover; }
        .action-buttons { display: flex; gap: 10px; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; opacity: 0.3; }
    </style>
</head>
<body>
    <?php include \'../includes/sidebar.php\'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mis Videos</h1>
            <button class="btn" onclick="location.href=\'/upload.php\'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
                </svg>
                Subir Video
            </button>
        </div>
        
        <?php if (empty($videos)): ?>
            <div class="videos-table">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                    </svg>
                    <h3>No tienes videos subidos aún</h3>
                    <p>Comienza subiendo tu primer video</p>
                    <a href="/upload.php" class="btn" style="margin-top: 20px;">Subir Video</a>
                </div>
            </div>
        <?php else: ?>
            <div class="videos-table">
                <table>
                    <thead>
                        <tr>
                            <th>Video</th>
                            <th>Vistas</th>
                            <th>Tamaño</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                        <tr>
                            <td>
                                <div class="video-title-cell">
                                    <div class="video-thumb-small">
                                        <div style="width: 100%; height: 100%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center;">
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="var(--text-secondary)">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($video[\'title\']) ?></strong>
                                        <?php if (isAdmin()): ?>
                                            <br><small style="color: var(--text-secondary);">por <?= htmlspecialchars($video[\'username\']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($video[\'views\']) ?></td>
                            <td><?= formatFileSize($video[\'file_size\']) ?></td>
                            <td><?= date(\'d/m/Y\', strtotime($video[\'created_at\'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/watch.php?v=<?= $video[\'embed_code\'] ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                        </svg>
                                    </a>
                                    <button onclick="copyToClipboard(\'<?= SITE_URL ?>/watch.php?v=<?= $video[\'embed_code\'] ?>\')" class="btn btn-secondary" style="padding: 6px 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                        </svg>
                                    </button>
                                    <button onclick="deleteVideo(<?= $video[\'id\'] ?>)" class="btn btn-danger" style="padding: 6px 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function deleteVideo(videoId) {
            if (confirm(\'¿Estás seguro de eliminar este video?\')) {
                fetch(\'/api/delete-video.php\', {
                    method: \'POST\',
                    headers: {\'Content-Type\': \'application/json\'},
                    body: JSON.stringify({ video_id: videoId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(\'Video eliminado exitosamente\');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || \'Error al eliminar\', \'error\');
                    }
                });
            }
        }
    </script>
</body>
</html>';

// ARCHIVO 2: admin/users.php
$usersContent = '<?php
require_once \'../config/app.php\';
require_once \'../includes/functions.php\';
requireAdmin();

$page = isset($_GET[\'page\']) ? max(1, intval($_GET[\'page\'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = db()->query("SELECT COUNT(*) FROM users");
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

$stmt = db()->prepare("
    SELECT u.*, 
           COUNT(DISTINCT v.id) as video_count,
           COALESCE(SUM(v.file_size), 0) as total_storage
    FROM users u
    LEFT JOIN videos v ON u.id = v.user_id AND v.status = \'active\'
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .users-table { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; }
        .users-table table { width: 100%; border-collapse: collapse; }
        .users-table th { background-color: var(--bg-secondary); padding: 15px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: var(--text-secondary); }
        .users-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
        .user-role { display: inline-block; padding: 4px 12px; background-color: rgba(0, 168, 255, 0.1); color: var(--accent-blue); font-size: 12px; font-weight: 600; border-radius: 20px; }
        .user-role.admin { background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); }
        .user-status { display: inline-block; padding: 4px 12px; background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); font-size: 12px; font-weight: 600; border-radius: 20px; }
        .user-status.suspended { background-color: rgba(255, 59, 59, 0.1); color: var(--accent-red); }
    </style>
</head>
<body>
    <?php include \'../includes/sidebar.php\'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gestión de Usuarios</h1>
            <div class="header-stats">Total: <?= $totalUsers ?> usuarios</div>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Videos</th>
                        <th>Almacenamiento</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($user[\'username\']) ?></strong></td>
                        <td><?= htmlspecialchars($user[\'email\']) ?></td>
                        <td><span class="user-role <?= $user[\'role\'] ?>"><?= ucfirst($user[\'role\']) ?></span></td>
                        <td><?= number_format($user[\'video_count\']) ?></td>
                        <td><?= formatFileSize($user[\'total_storage\']) ?></td>
                        <td><span class="user-status <?= $user[\'status\'] ?>"><?= ucfirst($user[\'status\']) ?></span></td>
                        <td><?= date(\'d/m/Y\', strtotime($user[\'created_at\'])) ?></td>
                        <td>
                            <?php if ($user[\'id\'] !== $_SESSION[\'user_id\']): ?>
                                <?php if ($user[\'status\'] === \'active\'): ?>
                                    <button onclick="suspendUser(<?= $user[\'id\'] ?>)" class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;">Suspender</button>
                                <?php else: ?>
                                    <button onclick="activateUser(<?= $user[\'id\'] ?>)" class="btn btn-secondary" style="padding: 6px 12px; font-size: 14px;">Activar</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 14px;">Tu cuenta</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function suspendUser(userId) {
            if (confirm(\'¿Estás seguro de suspender este usuario?\')) {
                updateUserStatus(userId, \'suspended\');
            }
        }
        
        function activateUser(userId) {
            updateUserStatus(userId, \'active\');
        }
        
        function updateUserStatus(userId, status) {
            fetch(\'/api/update-user.php\', {
                method: \'POST\',
                headers: {\'Content-Type\': \'application/json\'},
                body: JSON.stringify({ user_id: userId, status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(\'Usuario actualizado\');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || \'Error\', \'error\');
                }
            });
        }
    </script>
</body>
</html>';

// ARCHIVO 3: admin/storage.php
$storageContent = '<?php
require_once \'../config/app.php\';
require_once \'../includes/functions.php\';
requireAdmin();

$stmt = db()->query("SELECT * FROM storage_config ORDER BY storage_type");
$storageConfigs = $stmt->fetchAll();

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    $action = $_POST[\'action\'] ?? \'\';
    
    if ($action === \'toggle\') {
        $storageType = $_POST[\'storage_type\'] ?? \'\';
        $isActive = $_POST[\'is_active\'] ?? 0;
        
        db()->exec("UPDATE storage_config SET is_active = 0");
        
        if ($isActive) {
            $stmt = db()->prepare("UPDATE storage_config SET is_active = 1 WHERE storage_type = ?");
            $stmt->execute([$storageType]);
        }
        
        header(\'Location: /admin/storage.php\');
        exit;
    }
    
    if ($action === \'update\') {
        $storageType = $_POST[\'storage_type\'] ?? \'\';
        $accessKey = $_POST[\'access_key\'] ?? \'\';
        $secretKey = $_POST[\'secret_key\'] ?? \'\';
        $bucket = $_POST[\'bucket\'] ?? \'\';
        $region = $_POST[\'region\'] ?? \'\';
        $endpoint = $_POST[\'endpoint\'] ?? \'\';
        
        $stmt = db()->prepare("
            UPDATE storage_config 
            SET access_key = ?, secret_key = ?, bucket = ?, region = ?, endpoint = ?
            WHERE storage_type = ?
        ");
        $stmt->execute([$accessKey, $secretKey, $bucket, $region, $endpoint, $storageType]);
        
        header(\'Location: /admin/storage.php\');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Almacenamiento - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .storage-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .storage-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; position: relative; }
        .storage-card.active { border-color: var(--accent-green); box-shadow: 0 0 0 1px var(--accent-green); }
        .storage-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .storage-title { font-size: 20px; font-weight: 600; text-transform: capitalize; }
        .storage-status { display: inline-block; padding: 4px 12px; background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); font-size: 12px; font-weight: 600; border-radius: 20px; margin-bottom: 15px; }
        .storage-status.inactive { background-color: rgba(155, 155, 155, 0.1); color: var(--text-secondary); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include \'../includes/sidebar.php\'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración de Almacenamiento</h1>
        </div>
        
        <div class="storage-cards">
            <?php foreach ($storageConfigs as $config): ?>
            <div class="storage-card <?= $config[\'is_active\'] ? \'active\' : \'\' ?>">
                <div class="storage-header">
                    <h3 class="storage-title"><?= ucfirst($config[\'storage_type\']) ?></h3>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="storage_type" value="<?= $config[\'storage_type\'] ?>">
                        <label>
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= $config[\'is_active\'] ? \'checked\' : \'\' ?>
                                   onchange="this.form.submit()">
                            Activo
                        </label>
                    </form>
                </div>
                
                <span class="storage-status <?= $config[\'is_active\'] ? \'\' : \'inactive\' ?>">
                    <?= $config[\'is_active\'] ? \'Activo\' : \'Inactivo\' ?>
                </span>
                
                <?php if ($config[\'storage_type\'] === \'local\'): ?>
                    <p style="color: var(--text-secondary);">Almacenamiento en el servidor local. No requiere configuración adicional.</p>
                <?php else: ?>
                    <form method="POST" class="config-form">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="storage_type" value="<?= $config[\'storage_type\'] ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Access Key</label>
                                <input type="text" name="access_key" class="form-control" 
                                       value="<?= htmlspecialchars($config[\'access_key\'] ?? \'\') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Secret Key</label>
                                <input type="password" name="secret_key" class="form-control" 
                                       value="<?= htmlspecialchars($config[\'secret_key\'] ?? \'\') ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Bucket</label>
                                <input type="text" name="bucket" class="form-control" 
                                       value="<?= htmlspecialchars($config[\'bucket\'] ?? \'\') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Región</label>
                                <input type="text" name="region" class="form-control" 
                                       value="<?= htmlspecialchars($config[\'region\'] ?? \'\') ?>">
                            </div>
                        </div>
                        
                        <?php if ($config[\'storage_type\'] === \'contabo\' || $config[\'storage_type\'] === \'wasabi\'): ?>
                        <div class="form-group">
                            <label class="form-label">Endpoint</label>
                            <input type="text" name="endpoint" class="form-control" 
                                   value="<?= htmlspecialchars($config[\'endpoint\'] ?? \'\') ?>">
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-secondary">Guardar Configuración</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>';

// ARCHIVO 4: api/stats.php
$apiStatsContent = '<?php
require_once \'../config/app.php\';
require_once \'../includes/functions.php\';

header(\'Content-Type: application/json\');

if (!isLoggedIn()) {
    echo json_encode([\'success\' => false, \'message\' => \'No autorizado\']);
    exit;
}

$stats = isAdmin() ? getGlobalStats() : getUserStats($_SESSION[\'user_id\']);

// Obtener top videos
$userId = isAdmin() ? null : $_SESSION[\'user_id\'];
$topVideosQuery = "
    SELECT title, views, 
           ROUND((views / (SELECT SUM(views) FROM videos WHERE status = \'active\' " . 
           ($userId ? "AND user_id = ?" : "") . ")) * 100, 2) as percentage
    FROM videos 
    WHERE status = \'active\' " . ($userId ? "AND user_id = ?" : "") . "
    ORDER BY views DESC 
    LIMIT 5
";

$stmt = db()->prepare($topVideosQuery);
if ($userId) {
    $stmt->execute([$userId, $userId]);
} else {
    $stmt->execute();
}
$topVideos = $stmt->fetchAll();

// Datos del gráfico (últimos 7 días)
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date(\'Y-m-d\', strtotime("-$i days"));
    $stmt = db()->prepare("
        SELECT COALESCE(SUM(s.views), 0) as views
        FROM statistics s
        JOIN videos v ON s.video_id = v.id
        WHERE s.date = ? " . ($userId ? "AND v.user_id = ?" : "")
    );
    
    if ($userId) {
        $stmt->execute([$date, $userId]);
    } else {
        $stmt->execute([$date]);
    }
    
    $chartData[] = [
        \'date\' => $date,
        \'views\' => $stmt->fetchColumn()
    ];
}

echo json_encode([
    \'success\' => true,
    \'stats\' => $stats,
    \'topVideos\' => $topVideos,
    \'chartData\' => $chartData
]);';

// Guardar archivos
$files = [
    'admin/videos.php' => $videosContent,
    'admin/users.php' => $usersContent,
    'admin/storage.php' => $storageContent,
    'api/stats.php' => $apiStatsContent
];

$fixed = 0;
$errors = 0;

foreach ($files as $filepath => $content) {
    echo "<h3>Procesando: $filepath</h3>";
    
    // Crear directorio si no existe
    $dir = dirname($filepath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "<p class='success'>✓ Directorio creado: $dir</p>";
    }
    
    // Guardar archivo
    if (file_put_contents($filepath, $content)) {
        echo "<p class='success'>✓ Archivo guardado: $filepath (" . strlen($content) . " bytes)</p>";
        $fixed++;
    } else {
        echo "<p class='error'>✗ Error al guardar: $filepath</p>";
        $errors++;
    }
    
    // Verificar que se guardó correctamente
    if (file_exists($filepath)) {
        $size = filesize($filepath);
        echo "<p>Tamaño del archivo: $size bytes</p>";
        
        if ($size == 0) {
            echo "<p class='error'>⚠️ Advertencia: El archivo está vacío!</p>";
        }
    }
}

echo "<h2>Resumen:</h2>";
echo "<p>Archivos corregidos: <span class='success'>$fixed</span></p>";
echo "<p>Errores: <span class='error'>$errors</span></p>";

// Verificar permisos
echo "<h2>Verificación de permisos:</h2>";
$dirs = ['admin', 'api'];
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "<p class='success'>✓ $dir es escribible</p>";
    } else {
        echo "<p class='error'>✗ $dir NO es escribible - ejecuta: chmod 755 $dir</p>";
    }
}

echo "<h2>Enlaces para probar:</h2>";
echo "<ul>";
echo "<li><a href='/admin/videos.php' style='color: #00ff88;'>Videos</a></li>";
echo "<li><a href='/admin/users.php' style='color: #00ff88;'>Usuarios</a></li>";
echo "<li><a href='/admin/storage.php' style='color: #00ff88;'>Almacenamiento</a></li>";
echo "<li><a href='/api/stats.php' style='color: #00ff88;'>API Stats</a></li>";
echo "</ul>";
?>
