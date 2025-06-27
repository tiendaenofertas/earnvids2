<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Si está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// Obtener videos públicos recientes
$stmt = db()->prepare("
    SELECT v.*, u.username 
    FROM videos v
    JOIN users u ON v.user_id = u.id
    WHERE v.status = 'active' AND v.access_type = 'public'
    ORDER BY v.created_at DESC
    LIMIT 12
");
$stmt->execute();
$videos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Plataforma de Videos</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .landing-page {
            min-height: 100vh;
        }
        .landing-header {
            background-color: var(--bg-secondary);
            padding: 20px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .landing-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .landing-nav {
            display: flex;
            gap: 15px;
        }
        .hero {
            padding: 100px 0;
            text-align: center;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 168, 255, 0.1) 100%);
        }
        .hero h2 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .hero p {
            font-size: 20px;
            color: var(--text-secondary);
            margin-bottom: 30px;
        }
        .btn-large {
            font-size: 18px;
            padding: 15px 40px;
        }
        .recent-videos {
            padding: 60px 0;
        }
        .recent-videos h3 {
            font-size: 32px;
            margin-bottom: 40px;
            text-align: center;
        }
        .video-thumbnail {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            background-color: var(--bg-secondary);
            overflow: hidden;
        }
        .video-thumbnail img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-duration {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }
        .video-info {
            padding: 15px;
        }
        .video-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .video-meta {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--text-secondary);
        }
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
        
        <?php if (!empty($videos)): ?>
        <section class="recent-videos">
            <div class="container">
                <h3>Videos Recientes</h3>
                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                    <div class="video-card">
                        <a href="/watch.php?v=<?= $video['embed_code'] ?>">
                            <div class="video-thumbnail">
                                <img src="<?= $video['thumbnail'] ?: '/assets/img/default-thumb.jpg' ?>" alt="<?= htmlspecialchars($video['title']) ?>">
                                <span class="video-duration"><?= formatDuration($video['duration']) ?></span>
                            </div>
                        </a>
                        <div class="video-info">
                            <h4 class="video-title"><?= htmlspecialchars($video['title']) ?></h4>
                            <div class="video-meta">
                                <span><?= $video['username'] ?></span>
                                <span><?= number_format($video['views']) ?> vistas</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>