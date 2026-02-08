<?php
// watch.php - Corregido para URLs Amigables y Rutas Absolutas
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// 1. Obtener ID del video (soporta ?v=CODIGO y URL limpia)
$embedCode = '';
if (isset($_GET['v'])) {
    $embedCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['v']);
} else {
    // Intentar extraer de la URL si no viene por GET (para compatibilidad extra)
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('/\/watch\/([a-zA-Z0-9_-]+)/', $path, $matches)) {
        $embedCode = $matches[1];
    }
}

if (empty($embedCode)) {
    header("Location: /"); // Redirigir al home si no hay código
    exit;
}

// 2. Buscar video
$stmt = db()->prepare("
    SELECT v.*, u.username 
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.embed_code = ? AND v.status = 'active'
    LIMIT 1
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    die("<h1>Video no encontrado</h1><p>El video que buscas no existe o fue eliminado.</p><a href='/'>Volver al inicio</a>");
}

// 3. Incrementar vistas
$viewKey = 'viewed_' . $video['id'];
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION[$viewKey])) {
    incrementViews($video['id']);
    $_SESSION[$viewKey] = true;
    $video['views']++;
}

// 4. Determinar URL del video (IMPORTANTE: Rutas absolutas con / al inicio)
$videoUrl = '';
if ($video['storage_type'] === 'local') {
    $videoUrl = '/stream.php?v=' . $video['embed_code'];
} else {
    $videoUrl = '/s3-proxy.php?v=' . $video['embed_code'];
}

$shareUrl = SITE_URL . '/watch/' . $video['embed_code'];
$posterUrl = '/assets/img/video-placeholder.jpg'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
    <style>
        .watch-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .video-player-box { 
            position: relative; 
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            height: 0; 
            background: #000; 
            border-radius: 12px; 
            overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .video-player-box video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .video-header { margin-top: 20px; }
        .video-title { font-size: 24px; color: #fff; margin-bottom: 10px; }
        .video-meta { color: #888; font-size: 14px; display: flex; gap: 15px; }
        .actions { margin-top: 20px; display: flex; gap: 10px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="watch-container">
            <div class="video-player-box">
                <video 
                    id="mainPlayer"
                    controls 
                    autoplay 
                    playsinline
                    poster="<?= $posterUrl ?>">
                    <source src="<?= $videoUrl ?>" type="video/mp4">
                    Tu navegador no soporta HTML5 video.
                </video>
            </div>
            
            <div class="video-header">
                <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
                <div class="video-meta">
                    <span>👁️ <?= number_format($video['views']) ?> vistas</span>
                    <span>📅 <?= date('d/m/Y', strtotime($video['created_at'])) ?></span>
                    <span>👤 <?= htmlspecialchars($video['username']) ?></span>
                </div>
                
                <div class="actions">
                    <a href="/api/download.php?v=<?= $video['embed_code'] ?>" class="btn btn-secondary">
                        ⬇ Descargar
                    </a>
                    <button class="btn btn-secondary" onclick="navigator.clipboard.writeText('<?= $shareUrl ?>'); alert('¡Link copiado!');">
                        🔗 Copiar Link
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
