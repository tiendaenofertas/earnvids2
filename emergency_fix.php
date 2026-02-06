<?php
// emergency_fix.php - Reparación de emergencia
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Emergency Fix - EARNVIDS</title>
    <style>
        body { background: #0a0a0a; color: #fff; font-family: Arial; padding: 20px; }
        .box { background: #1a1a1a; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: #00ff88; }
        .error { color: #ff3b3b; }
        .warning { color: #ffa500; }
        pre { background: #2a2a2a; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🚨 Reparación de Emergencia EARNVIDS</h1>";

// 1. Verificar archivos críticos
echo "<div class='box'>";
echo "<h2>1. Verificando archivos críticos...</h2>";

$criticalFiles = [
    'config/app.php',
    'config/database.php', 
    'includes/functions.php',
    'includes/db_connect.php',
    'index.php'
];

$errors = [];
foreach ($criticalFiles as $file) {
    if (!file_exists($file)) {
        echo "<p class='error'>✗ FALTA: $file</p>";
        $errors[] = $file;
    } else {
        $size = filesize($file);
        if ($size == 0) {
            echo "<p class='error'>✗ VACÍO: $file</p>";
            $errors[] = $file;
        } else {
            echo "<p class='success'>✓ OK: $file ($size bytes)</p>";
        }
    }
}
echo "</div>";

// 2. Restaurar functions.php
if (in_array('includes/functions.php', $errors) || filesize('includes/functions.php') == 0) {
    echo "<div class='box'>";
    echo "<h2>2. Restaurando includes/functions.php...</h2>";
    
    $functionsContent = '<?php
require_once __DIR__ . \'/db_connect.php\';

function generateEmbedCode($length = 10) {
    $characters = \'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ\';
    $code = \'\';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

function formatFileSize($bytes) {
    $units = [\'B\', \'KB\', \'MB\', \'GB\', \'TB\'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . \' \' . $units[$i];
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, \'UTF-8\');
}

function isAdmin() {
    return isset($_SESSION[\'role\']) && $_SESSION[\'role\'] === \'admin\';
}

function isLoggedIn() {
    return isset($_SESSION[\'user_id\']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header(\'Location: /login.php\');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header(\'Location: /\');
        exit;
    }
}

function getUserStats($userId) {
    $stmt = db()->prepare("
        SELECT 
            COUNT(*) as total_videos,
            COALESCE(SUM(file_size), 0) as total_storage,
            COALESCE(SUM(views), 0) as total_views,
            COALESCE(SUM(downloads), 0) as total_downloads
        FROM videos 
        WHERE user_id = ? AND status = \'active\'
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getGlobalStats() {
    $stats = [];
    
    $stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE status = \'active\'");
    $stats[\'total_users\'] = $stmt->fetch()[\'total\'];
    
    $stmt = db()->query("SELECT COUNT(*) as total FROM videos WHERE status = \'active\'");
    $stats[\'total_videos\'] = $stmt->fetch()[\'total\'];
    
    $stmt = db()->query("SELECT COALESCE(SUM(file_size), 0) as total FROM videos WHERE status = \'active\'");
    $stats[\'total_storage\'] = $stmt->fetch()[\'total\'];
    
    $stmt = db()->query("SELECT COALESCE(SUM(views), 0) as total FROM statistics WHERE date = CURDATE()");
    $stats[\'views_today\'] = $stmt->fetch()[\'total\'];
    
    return $stats;
}

function logActivity($action, $details = []) {
    try {
        $stmt = db()->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $userId = $_SESSION[\'user_id\'] ?? null;
        $ip = $_SERVER[\'REMOTE_ADDR\'] ?? null;
        $userAgent = $_SERVER[\'HTTP_USER_AGENT\'] ?? null;
        
        $stmt->execute([
            $userId,
            $action,
            json_encode($details),
            $ip,
            $userAgent
        ]);
    } catch (Exception $e) {
        // Silently fail
    }
}

function incrementViews($videoId) {
    $stmt = db()->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$videoId]);
    
    $stmt = db()->prepare("
        INSERT INTO statistics (video_id, date, views)
        VALUES (?, CURDATE(), 1)
        ON DUPLICATE KEY UPDATE views = views + 1
    ");
    $stmt->execute([$videoId]);
}

function getActiveStorage() {
    $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
    return $stmt->fetch();
}

function isValidVideoExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

function getVideoUrl($video) {
    $storage = getActiveStorage();
    
    if (!$storage) {
        return \'#\';
    }
    
    switch ($storage[\'storage_type\']) {
        case \'contabo\':
            return $storage[\'endpoint\'] . \'/\' . $storage[\'bucket\'] . \'/\' . $video[\'storage_path\'];
        case \'local\':
            return SITE_URL . \'/stream.php?v=\' . $video[\'embed_code\'];
        default:
            return \'#\';
    }
}

function formatDuration($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = floor($seconds % 60);
    
    if ($h > 0) {
        return sprintf(\'%d:%02d:%02d\', $h, $m, $s);
    }
    return sprintf(\'%d:%02d\', $m, $s);
}';

    if (file_put_contents('includes/functions.php', $functionsContent)) {
        echo "<p class='success'>✓ functions.php restaurado</p>";
    } else {
        echo "<p class='error'>✗ Error al restaurar functions.php</p>";
    }
    echo "</div>";
}

// 3. Verificar index.php
echo "<div class='box'>";
echo "<h2>3. Verificando index.php...</h2>";

if (!file_exists('index.php') || filesize('index.php') == 0) {
    $indexContent = '<?php
require_once \'config/app.php\';
require_once \'includes/functions.php\';
require_once \'includes/db_connect.php\';

// Si está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header(\'Location: /admin/\');
    exit;
}

// Intentar obtener videos públicos
try {
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.status = \'active\' AND v.access_type = \'public\'
        ORDER BY v.created_at DESC
        LIMIT 12
    ");
    $stmt->execute();
    $videos = $stmt->fetchAll();
} catch (Exception $e) {
    $videos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Plataforma de Videos</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .landing-page { min-height: 100vh; }
        .landing-header { background-color: var(--bg-secondary); padding: 20px 0; border-bottom: 1px solid var(--border-color); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .landing-header .container { display: flex; justify-content: space-between; align-items: center; }
        .landing-nav { display: flex; gap: 15px; }
        .hero { padding: 100px 0; text-align: center; background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 168, 255, 0.1) 100%); }
        .hero h2 { font-size: 48px; margin-bottom: 20px; }
        .hero p { font-size: 20px; color: var(--text-secondary); margin-bottom: 30px; }
        .btn-large { font-size: 18px; padding: 15px 40px; }
    </style>
</head>
<body>
    <div class="landing-page">
        <header class="landing-header">
            <div class="container">
                <div class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    <h1>EARN<span>VIDS</span></h1>
                </div>
                <nav class="landing-nav">
                    <a href="/login.php" class="btn btn-secondary">Iniciar Sesión</a>
                    <a href="/register.php" class="btn">Registrarse</a>
                </nav>
            </div>
        </header>
        
        <section class="hero">
            <div class="container">
                <h2>Tu plataforma de videos profesional</h2>
                <p>Sube, comparte y gestiona tus videos con almacenamiento ilimitado</p>
                <a href="/register.php" class="btn btn-large">Comenzar Gratis</a>
            </div>
        </section>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>';

    if (file_put_contents('index.php', $indexContent)) {
        echo "<p class='success'>✓ index.php restaurado</p>";
    } else {
        echo "<p class='error'>✗ Error al restaurar index.php</p>";
    }
} else {
    echo "<p class='success'>✓ index.php existe</p>";
}
echo "</div>";

// 4. Verificar sintaxis PHP
echo "<div class='box'>";
echo "<h2>4. Verificando errores de sintaxis...</h2>";

$filesToCheck = ['index.php', 'includes/functions.php', 'config/app.php'];
foreach ($filesToCheck as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "<p class='success'>✓ $file - Sin errores de sintaxis</p>";
        } else {
            echo "<p class='error'>✗ $file - Error de sintaxis:</p>";
            echo "<pre>$output</pre>";
        }
    }
}
echo "</div>";

// 5. Crear archivo de prueba
echo "<div class='box'>";
echo "<h2>5. Creando página de prueba...</h2>";

$testContent = '<?php
// test.php - Página de prueba
phpinfo();
?>';

if (file_put_contents('test.php', $testContent)) {
    echo "<p class='success'>✓ test.php creado</p>";
    echo "<p>Prueba aquí: <a href='/test.php' target='_blank' style='color: #00ff88;'>test.php</a></p>";
}
echo "</div>";

// 6. Limpiar caché de opcache si está activo
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<div class='box'><p class='success'>✓ Caché de PHP limpiado</p></div>";
}

echo "<div class='box' style='background: #0a3d0a; border-color: #00ff88;'>";
echo "<h2>✅ Reparación Completada</h2>";
echo "<p>Prueba estos enlaces:</p>";
echo "<ul>";
echo "<li><a href='/' style='color: #00ff88;'>Página principal</a></li>";
echo "<li><a href='/test.php' style='color: #00ff88;'>Test PHP</a></li>";
echo "<li><a href='/login.php' style='color: #00ff88;'>Login</a></li>";
echo "</ul>";
echo "<p>Si aún hay errores, revisa el log de errores del servidor.</p>";
echo "</div>";

echo "</body></html>";
?>