<?php
// watch.php - Reproductor con Bloqueo de Membresía (Content Locking)
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // Necesario para verificar sesión y rol

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

// --- LÓGICA DE MEMBRESÍA (EL PORTERO) ---
$canWatch = false;
$lockTitle = "Contenido Protegido";
$lockMessage = "Necesitas una membresía activa para ver este video.";
$lockButton = "Suscribirse Ahora";
$lockLink = "/index.php#plans"; // O /account.php si ya está logueado

if (isLoggedIn()) {
    // 1. Si es admin, pase VIP
    if (isAdmin()) {
        $canWatch = true;
    } else {
        // 2. Verificar expiración del usuario actual
        $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userStatus = $stmt->fetch();

        if ($userStatus && $userStatus['membership_expiry'] && strtotime($userStatus['membership_expiry']) > time()) {
            $canWatch = true;
        } else {
            $lockTitle = "Membresía Expirada";
            $lockMessage = "🔒 Tu membresía ha expirado. Renueva tu plan para continuar viendo este contenido.";
            $lockButton = "Renovar Membresía";
            $lockLink = "/account.php#plans-section";
        }
    }
} else {
    $lockTitle = "Identifícate";
    $lockMessage = "🔒 Este contenido es exclusivo para miembros.";
    $lockButton = "Iniciar Sesión / Registrarse";
    $lockLink = "/login.php";
}
// ----------------------------------------

// 3. Preparar Video SOLO si tiene permiso
$videoUrl = '';
if ($canWatch) {
    // Incrementar Vistas solo si se va a ver
    $viewKey = 'viewed_' . $video['id'];
    if (!isset($_SESSION[$viewKey])) {
        incrementViews($video['id']);
        $_SESSION[$viewKey] = true;
        $video['views']++;
    }

    // Generar URL segura
    if ($video['storage_type'] === 'local') {
        $videoUrl = '/stream.php?v=' . $video['embed_code'];
    } else {
        $videoUrl = '/s3-proxy.php?v=' . $video['embed_code'];
    }
}

// URL para compartir y Poster
$shareUrl = SITE_URL . '/watch.php?v=' . $video['embed_code'];
$embedUrl = SITE_URL . '/embed.php?v=' . $video['embed_code'];
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
            /* Aspect Ratio Hack para evitar saltos */
            padding-top: 56.25%; /* 16:9 */
        }
        
        /* Reproductor y Pantalla de Bloqueo ocupan el mismo espacio absoluto */
        .video-wrapper video, .locked-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .video-wrapper video {
            object-fit: contain;
        }

        /* Estilos de la Pantalla de Bloqueo */
        .locked-screen {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle, #1a1a1a 0%, #000000 100%);
            color: #fff;
            text-align: center;
            z-index: 10;
        }
        .lock-icon {
            font-size: 60px;
            margin-bottom: 20px;
            color: var(--accent-red, #ff3b3b);
            animation: pulse 2s infinite;
        }
        .lock-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .lock-msg {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 16px;
            max-width: 80%;
        }
        .btn-unlock {
            background: var(--accent-green);
            color: #000;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: bold;
            text-decoration: none;
            transition: transform 0.2s;
        }
        .btn-unlock:hover { transform: scale(1.05); }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
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
                <?php if ($canWatch): ?>
                    <video 
                        id="videoPlayer" 
                        controls 
                        autoplay 
                        preload="metadata"
                        poster="<?= $posterUrl ?>">
                        <source src="<?= $videoUrl ?>" type="video/mp4">
                        Tu navegador no soporta la reproducción de video HTML5.
                    </video>
                <?php else: ?>
                    <div class="locked-screen">
                        <div class="lock-icon">🔒</div>
                        <div class="lock-title"><?= htmlspecialchars($lockTitle) ?></div>
                        <div class="lock-msg"><?= htmlspecialchars($lockMessage) ?></div>
                        <a href="<?= $lockLink ?>" class="btn-unlock"><?= $lockButton ?></a>
                    </div>
                <?php endif; ?>
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
                    <?php if ($canWatch): ?>
                        <a href="/api/download.php?v=<?= $video['embed_code'] ?>" class="btn btn-secondary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                            </svg>
                            Descargar
                        </a>
                    <?php else: ?>
                         <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right:8px;">
                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                            </svg>
                            Descargar (Bloqueado)
                        </button>
                    <?php endif; ?>
                    
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
        
        <?php if ($canWatch): ?>
        // Auto-reproducir solo si tiene permiso
        document.addEventListener('DOMContentLoaded', () => {
            const video = document.getElementById('videoPlayer');
            if(video) {
                const playPromise = video.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        console.log('Autoplay bloqueado por el navegador.');
                    });
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
