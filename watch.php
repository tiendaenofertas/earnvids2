<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

$embedCode = $_GET['v'] ?? '';
$debug = isset($_GET['debug']); // Agregar ?debug=1 a la URL para ver información

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

// Para debug: forzar URL correcta
if ($debug) {
    $storage = getActiveStorage();
    $debugInfo = [
        'video_id' => $video['id'],
        'storage_type' => $video['storage_type'],
        'storage_path' => $video['storage_path'],
        'bucket_config' => $storage['bucket'],
        'url_generada' => $videoUrl,
        'url_manual_earnvids' => "https://usc1.contabostorage.com/earnvids/{$video['storage_path']}",
        'url_manual_japojes' => "https://usc1.contabostorage.com/japojes/{$video['storage_path']}"
    ];
}
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
        .debug-info {
            background: #333;
            color: #0f0;
            padding: 20px;
            margin: 20px;
            font-family: monospace;
            font-size: 12px;
            border-radius: 8px;
            overflow-x: auto;
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
        
        <?php if ($debug && isset($debugInfo)): ?>
        <div class="debug-info">
            <h3>🔍 DEBUG INFO:</h3>
            <pre><?= json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
            <h4>Prueba directa de URLs:</h4>
            <p>URL earnvids: <a href="<?= $debugInfo['url_manual_earnvids'] ?>" target="_blank" style="color: #0f0;"><?= $debugInfo['url_manual_earnvids'] ?></a></p>
            <p>URL japojes: <a href="<?= $debugInfo['url_manual_japojes'] ?>" target="_blank" style="color: #0f0;"><?= $debugInfo['url_manual_japojes'] ?></a></p>
        </div>
        <?php endif; ?>
        
        <div class="player-container">
            <div class="video-player-wrapper">
                <video id="main-video" class="video-player" controls>
                    <source src="<?= $videoUrl ?>" type="video/mp4">
                    Tu navegador no soporta la reproducción de video.
                </video>
            </div>
            
            <?php if ($debug): ?>
            <div style="background: #444; color: white; padding: 10px; margin: 10px 0; border-radius: 5px;">
                <p><strong>URL del video:</strong> <?= $videoUrl ?></p>
                <button onclick="testVideoUrl()">Probar carga del video</button>
            </div>
            <?php endif; ?>
            
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
        
        <?php if ($debug): ?>
        function testVideoUrl() {
            const video = document.getElementById('main-video');
            const testUrls = [
                '<?= $videoUrl ?>',
                '<?= $debugInfo['url_manual_earnvids'] ?>',
                '<?= $debugInfo['url_manual_japojes'] ?>'
            ];
            
            console.log('Probando URLs:', testUrls);
            
            testUrls.forEach((url, index) => {
                fetch(url, { method: 'HEAD' })
                    .then(response => {
                        console.log(`URL ${index + 1}: ${response.status} - ${response.statusText}`);
                        if (response.ok && index === 1) {
                            // Si earnvids funciona, usarla
                            video.src = url;
                            console.log('Usando URL de earnvids');
                        }
                    })
                    .catch(error => console.error(`Error URL ${index + 1}:`, error));
            });
        }
        
        // Log de errores del video
        const video = document.getElementById('main-video');
        video.addEventListener('error', function(e) {
            console.error('Error de video:', e);
            console.error('Código de error:', video.error?.code);
            console.error('Mensaje:', video.error?.message);
            
            // Intentar con URL alternativa si falla
            if (video.src.includes('japojes')) {
                console.log('Intentando con earnvids...');
                video.src = '<?= $debugInfo['url_manual_earnvids'] ?>';
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
