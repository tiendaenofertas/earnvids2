<?php
// watch.php - Página de visualización corregida para usar Proxy S3
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// 1. Obtener y sanitizar el código del video
$embedCode = isset($_GET['v']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['v']) : '';

if (empty($embedCode)) {
    header("Location: " . SITE_URL);
    exit;
}

// 2. Buscar el video en la base de datos
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
    die("El video no existe o ha sido eliminado.");
}

// 3. Incrementar Vistas (con protección de sesión simple)
$viewKey = 'viewed_' . $video['id'];
if (!isset($_SESSION[$viewKey])) {
    incrementViews($video['id']);
    $_SESSION[$viewKey] = true;
    // Actualizamos el contador visual localmente para mostrarlo ya incrementado
    $video['views']++;
}

// 4. Generar la URL correcta usando los Proxies (¡Esto soluciona la pantalla negra!)
$videoUrl = '';
if ($video['storage_type'] === 'local') {
    // Si es local, usamos stream.php
    $videoUrl = '/stream.php?v=' . $video['embed_code'];
} else {
    // Si es Contabo, Wasabi o AWS, OBLIGAMOS a usar el proxy para manejar la autenticación
    $videoUrl = '/s3-proxy.php?v=' . $video['embed_code'];
}

// URL para compartir y Poster
$shareUrl = SITE_URL . '/watch.php?v=' . $video['embed_code'];
$embedUrl = SITE_URL . '/embed.php?v=' . $video['embed_code'];
// Imagen por defecto si no hay miniatura generada
$posterUrl = '/assets/img/video-placeholder.jpg'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .watch-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .video-wrapper {
            position: relative;
            width: 100%;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-bottom: 20px;
        }
        /* Aspect Ratio 16:9 responsive */
        .video-wrapper video {
            width: 100%;
            height: auto;
            max-height: 80vh;
            display: block;
        }
        .video-info {
            background: var(--bg-card);
            padding: 25px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .video-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        .video-meta {
            color: var(--text-secondary);
            font-size: 14px;
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        .actions-bar {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        .embed-box {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        .embed-code {
            width: 100%;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="watch-container">
            <div class="video-wrapper">
                <video 
                    id="videoPlayer" 
                    controls 
                    autoplay 
                    preload="metadata"
                    poster="<?= $posterUrl ?>">
                    <source src="<?= $videoUrl ?>" type="video/mp4">
                    Tu navegador no soporta la reproducción de video HTML5.
                </video>
            </div>
            
            <div class="video-info">
                <h1 class="video-title"><?= htmlspecialchars($video['title']) ?></h1>
                
                <div class="video-meta">
                    <span>👁️ <?= number_format($video['views']) ?> vistas</span>
                    <span>•</span>
                    <span>📅 <?= date('d/m/Y', strtotime($video['created_at'])) ?></span>
                    <span>•</span>
                    <span>👤 Subido por <?= htmlspecialchars($video['username']) ?></span>
                </div>
                
                <div class="actions-bar">
                    <a href="/api/download.php?v=<?= $video['embed_code'] ?>" class="btn btn-secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
                            <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                        </svg>
                        Descargar
                    </a>
                    
                    <button class="btn btn-secondary" onclick="toggleEmbed()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
                            <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
                        </svg>
                        Insertar (Embed)
                    </button>
                    
                    <button class="btn btn-secondary" onclick="copyLink()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
                            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                        </svg>
                        Copiar Enlace
                    </button>
                </div>
                
                <div id="embedBox" class="embed-box">
                    <p style="margin-bottom: 10px; color: var(--text-secondary);">Copia este código para poner el video en tu sitio web:</p>
                    <input type="text" class="embed-code" readonly 
                           value='<iframe src="<?= $embedUrl ?>" width="640" height="360" frameborder="0" allowfullscreen></iframe>' 
                           onclick="this.select()">
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function toggleEmbed() {
            const box = document.getElementById('embedBox');
            box.style.display = (box.style.display === 'block') ? 'none' : 'block';
        }
        
        function copyLink() {
            const link = "<?= $shareUrl ?>";
            navigator.clipboard.writeText(link).then(() => {
                showNotification('¡Enlace copiado!');
            });
        }
        
        // Auto-reproducir con manejo de promesas (evita errores en consola)
        document.addEventListener('DOMContentLoaded', () => {
            const video = document.getElementById('videoPlayer');
            const playPromise = video.play();
            
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    // El navegador bloqueó el autoplay (común si no está silenciado)
                    console.log('Autoplay bloqueado por el navegador. El usuario debe iniciar la reproducción.');
                });
            }
        });
    </script>
</body>
</html>
