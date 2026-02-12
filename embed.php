<?php
// embed.php - Reproductor Seguro: Dominios Permitidos + Membresía Requerida
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; // Necesario para detectar usuario logueado

// --- CAPA 1: PROTECCIÓN DE DOMINIOS (Hotlink Protection) ---
if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererHost = '';
    
    if ($referer) {
        $parsedUrl = parse_url($referer);
        $refererHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
    }

    $selfHost = parse_url(SITE_URL, PHP_URL_HOST);
    $selfHost = strtolower($selfHost ?? '');

    $isDomainAllowed = false;

    // Permitir siempre a nuestro propio sitio
    if ($refererHost === $selfHost || empty($refererHost)) {
        // Nota: Permitimos referer vacío para pruebas directas, 
        // pero en producción estricta podrías bloquearlo si quisieras.
        $isDomainAllowed = true;
    }
    
    // Buscar en lista blanca
    if (!$isDomainAllowed) {
        foreach (ALLOWED_DOMAINS as $allowedDomain) {
            if (strtolower(trim($allowedDomain)) === $refererHost) {
                $isDomainAllowed = true;
                break;
            }
        }
    }

    if (!$isDomainAllowed) {
        http_response_code(403);
        die('
            <div style="display:flex;justify-content:center;align-items:center;height:100vh;background:#000;color:#fff;font-family:sans-serif;text-align:center;padding:20px;">
                <div>
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="#ff3b3b" style="margin-bottom:20px;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <h3>Dominio no autorizado</h3>
                    <p style="color:#999;font-size:14px;">El propietario ha restringido dónde se puede ver este video.</p>
                </div>
            </div>
        ');
    }
}
// -----------------------------------------------------

// 1. Sanitización de Entrada
$embedCode = isset($_GET['v']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['v']) : '';

if (empty($embedCode)) {
    die("Error: Video no especificado.");
}

// 2. Obtener metadatos del video
try {
    $stmt = db()->prepare("
        SELECT id, title, storage_type, storage_path, status, views 
        FROM videos 
        WHERE embed_code = ? LIMIT 1
    ");
    $stmt->execute([$embedCode]);
    $video = $stmt->fetch();

    if (!$video || $video['status'] !== 'active') {
        http_response_code(404);
        die("Video no disponible.");
    }

    // --- CAPA 2: VERIFICACIÓN DE MEMBRESÍA (Content Locking) ---
    $canWatch = false;
    $lockTitle = "Contenido Premium";
    $lockMsg = "Inicia sesión con una membresía activa para ver.";
    
    // Verificar sesión y plan
    if (isLoggedIn()) {
        if (isAdmin()) {
            $canWatch = true; // Admin siempre ve todo
        } else {
            // Verificar expiración
            $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $userStatus = $stmt->fetch();

            if ($userStatus && $userStatus['membership_expiry'] && strtotime($userStatus['membership_expiry']) > time()) {
                $canWatch = true;
            } else {
                $lockTitle = "Membresía Expirada";
                $lockMsg = "Tu suscripción ha caducado. Renuévala para continuar.";
            }
        }
    } else {
        $lockTitle = "Solo Miembros";
        $lockMsg = "Este video es exclusivo para suscriptores.";
    }
    // ---------------------------------------------------------

    // 3. Preparar URL del video (Solo si pasó los filtros)
    $videoUrl = '';
    if ($canWatch) {
        if ($video['storage_type'] === 'local') {
            $videoUrl = APP_URL . '/stream.php?v=' . $embedCode;
        } else {
            $videoUrl = APP_URL . '/s3-proxy.php?v=' . $embedCode;
        }
    }
    
    $posterUrl = APP_URL . '/assets/img/video-placeholder.jpg';
    $safeTitle = htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8');

} catch (PDOException $e) {
    http_response_code(500);
    die("Error del sistema.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $safeTitle; ?></title>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background: #000; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .video-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* PANTALLA DE BLOQUEO (LOCKED SCREEN) */
        .locked-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at center, #1a1a1a 0%, #000 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            text-align: center;
            padding: 20px;
            z-index: 20;
        }
        .lock-icon { font-size: 40px; margin-bottom: 15px; animation: float 3s infinite ease-in-out; }
        .lock-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #00ff88; }
        .lock-msg { font-size: 14px; color: #aaa; margin-bottom: 20px; max-width: 300px; line-height: 1.4; }
        .btn-action {
            background: #00ff88; color: #000;
            text-decoration: none; padding: 10px 20px;
            border-radius: 30px; font-weight: bold; font-size: 14px;
            transition: transform 0.2s;
            display: inline-block;
        }
        .btn-action:hover { transform: scale(1.05); background: #00cc6a; }
        
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    </style>
</head>
<body>

<div class="video-container">
    <?php if ($canWatch): ?>
        <video 
            id="mainPlayer" 
            controls 
            autoplay 
            preload="metadata" 
            poster="<?php echo $posterUrl; ?>"
            playsinline>
            <source src="<?php echo $videoUrl; ?>" type="video/mp4">
            Tu navegador no soporta video HTML5.
        </video>
    <?php else: ?>
        <div class="locked-overlay">
            <div class="lock-icon">🔒</div>
            <div class="lock-title"><?php echo $lockTitle; ?></div>
            <div class="lock-msg"><?php echo $lockMsg; ?></div>
            
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/login.php" target="_top" class="btn-action">Iniciar Sesión</a>
            <?php else: ?>
                <a href="<?php echo SITE_URL; ?>/account.php" target="_top" class="btn-action">Ver Planes</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    <?php if ($canWatch): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('mainPlayer');
        // Prevenir click derecho
        video.addEventListener('contextmenu', e => e.preventDefault());
        
        // Intentar autoplay
        video.play().catch(e => { console.log('Autoplay requiere interacción'); });
    });
    <?php endif; ?>
</script>

</body>
</html>
