<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    header('Location: /');
    exit;
}

// Obtener video
$stmt = db()->prepare("
    SELECT v.*, u.username 
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.embed_code = ? AND v.status = 'active'
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    header('Location: /');
    exit;
}

// Incrementar vistas
incrementViews($video['id']);

// Obtener URL del video
$videoUrl = getVideoUrl($video);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .watch-page {
            background-color: var(--bg-primary);
            min-height: 100vh;
        }
        .watch-header {
            background-color: var(--bg-secondary);
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .player-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .video-player-wrapper {
            background-color: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .video-player {
            width: 100%;
            height: auto;
            max-height: 80vh;
        }
        .video-details {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
        }
        .video-details h1 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        .video-meta {
            display: flex;
            gap: 15px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        .video-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .embed-section {
            background-color: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .embed-code {
            background-color: var(--bg-primary);
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            word-break: break-all;
            margin-top: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="watch-page">
        <header class="watch-header">
            <div class="container">
                <a href="/" class="logo">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    <h1>EARN<span>VIDS</span></h1>
                </a>
            </div>
        </header>
        
        <div class="player-container">
            <div class="video-player-wrapper">
                <video id="main-video" class="video-player" controls>
                    <source src="<?= $videoUrl ?>" type="video/mp4">
                    Tu navegador no soporta la reproducción de video.
                </video>
            </div>
            
            <div class="video-details">
                <h1><?= htmlspecialchars($video['title']) ?></h1>
                <div class="video-meta">
                    <span><?= number_format($video['views']) ?> vistas</span>
                    <span>•</span>
                    <span>Subido por <?= htmlspecialchars($video['username']) ?></span>
                    <span>•</span>
                    <span><?= date('d/m/Y', strtotime($video['created_at'])) ?></span>
                </div>
                
                <div class="video-actions">
                    <button class="btn btn-secondary" onclick="downloadVideo()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                        </svg>
                        Descargar
                    </button>
                    <button class="btn btn-secondary" onclick="toggleEmbed()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
                        </svg>
                        Embed
                    </button>
                </div>
                
                <div id="embed-section" class="embed-section" style="display: none;">
                    <h3>Código de inserción</h3>
                    <div class="embed-code" onclick="copyToClipboard(this.textContent)">
&lt;iframe src="<?= SITE_URL ?>/embed.php?v=<?= $video['embed_code'] ?>" width="640" height="360" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;
                    </div>
                    <small style="color: var(--text-secondary);">Haz clic para copiar</small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function downloadVideo() {
            window.location.href = '/api/download.php?v=<?= $video['embed_code'] ?>';
        }
        
        function toggleEmbed() {
            const embedSection = document.getElementById('embed-section');
            embedSection.style.display = embedSection.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>