<?php
// embed.php - Reproductor Híbrido: Protección de Dominio + Verificación de Membresía del DUEÑO
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php'; 

// Inicializar variables
$canWatch = false;
$video = null;
$lockTitle = "";
$lockMsg = "";
$showLock = false;

// 1. Obtener Video
$embedCode = isset($_GET['v']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['v']) : '';

if (!empty($embedCode)) {
    try {
        $stmt = db()->prepare("
            SELECT id, title, storage_type, storage_path, status, views, user_id 
            FROM videos 
            WHERE embed_code = ? LIMIT 1
        ");
        $stmt->execute([$embedCode]);
        $video = $stmt->fetch();
    } catch (PDOException $e) {
        // Error silencioso
    }
}

// 2. LÓGICA DE VALIDACIÓN
if ($video && $video['status'] === 'active') {

    // --- PASO A: Verificar Dominio (Hotlink) ---
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $refererHost = '';
    if ($referer) {
        $parsedUrl = parse_url($referer);
        $refererHost = isset($parsedUrl['host']) ? strtolower($parsedUrl['host']) : '';
    }
    $selfHost = parse_url(SITE_URL, PHP_URL_HOST);
    $selfHost = strtolower($selfHost ?? '');

    $isDomainAllowed = false;

    // A1. Permitir siempre solicitudes desde el propio dominio (Dashboard, Admin)
    if ($refererHost === $selfHost || empty($refererHost)) {
        $isDomainAllowed = true;
    } else {
        // A2. Verificar lista blanca del dueño
        $stmt = db()->prepare("SELECT domain FROM user_domains WHERE user_id = ?");
        $stmt->execute([$video['user_id']]);
        $userDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $domainMatch = false;
        if (!empty($userDomains)) {
            foreach ($userDomains as $d) {
                if (strpos($refererHost, strtolower(trim($d))) !== false) {
                    $domainMatch = true;
                    break;
                }
            }
        }

        // A3. Verificar estado de la Membresía del DUEÑO DEL VIDEO (Nueva Lógica)
        // Si el dominio coincide, verificamos si el dueño pagó.
        if ($domainMatch) {
            $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
            $stmt->execute([$video['user_id']]);
            $owner = $stmt->fetch();

            $isOwnerActive = ($owner && !empty($owner['membership_expiry']) && strtotime($owner['membership_expiry']) > time());

            if ($isOwnerActive) {
                $isDomainAllowed = true;
            } else {
                // Si la membresía venció, bloqueamos aunque el dominio esté en la lista
                $isDomainAllowed = false;
                $lockTitle = "Servicio Suspendido";
                $lockMsg = "El propietario del video no tiene una membresía activa para permitir la reproducción en este sitio.";
            }
        } else {
            // Si no coincide con la lista del usuario, revisar configuración global
            if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
                 foreach (ALLOWED_DOMAINS as $globalDomain) {
                    if (strpos($refererHost, strtolower(trim($globalDomain))) !== false) {
                        $isDomainAllowed = true;
                        break;
                    }
                }
            } else {
                $isDomainAllowed = true; // Público por defecto si no hay reglas globales
            }
        }
    }

    if (!$isDomainAllowed) {
        // BLOQUEO POR DOMINIO
        $canWatch = false;
        $showLock = true;
        if (empty($lockTitle)) {
            $lockTitle = "Dominio No Autorizado";
            $lockMsg = "El propietario no permite la reproducción en este sitio web ($refererHost).";
        }
    } else {
        // --- PASO B: Verificar Estado del ESPECTADOR (Membresía para ver contenido Premium) ---
        // Esto verifica si el que VE el video tiene permiso (si el sistema es de suscripción para ver)
        
        if (isLoggedIn()) {
            if (isAdmin() || $_SESSION['user_id'] == $video['user_id']) {
                $canWatch = true; // Admin y Dueño siempre ven
            } else {
                $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $viewerStatus = $stmt->fetch();

                if ($viewerStatus && !empty($viewerStatus['membership_expiry']) && strtotime($viewerStatus['membership_expiry']) > time()) {
                    $canWatch = true; 
                } else {
                    $canWatch = false;
                    $showLock = true;
                    $lockTitle = "Membresía Expirada";
                    $lockMsg = "Tu membresía ha vencido. Renueva tu plan para continuar viendo contenido.";
                }
            }
        } else {
            // Invitados pueden ver si pasó la validación de dominio
            $canWatch = true; 
        }
    }

} else {
    $showLock = true;
    $lockTitle = "No Disponible";
    $lockMsg = "El video no existe o ha sido eliminado.";
}

// 3. Preparar URLs
$videoUrl = '';
$posterUrl = SITE_URL . '/assets/img/video-placeholder.jpg';
$safeTitle = $video ? htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8') : 'Error';

if ($canWatch && $video) {
    if ($video['storage_type'] === 'local') {
        $videoUrl = SITE_URL . '/stream.php?v=' . $embedCode;
    } else {
        $videoUrl = SITE_URL . '/s3-proxy.php?v=' . $embedCode;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $safeTitle ?></title>
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
        .lock-icon { font-size: 50px; margin-bottom: 20px; animation: float 3s infinite ease-in-out; }
        .lock-title { font-size: 22px; font-weight: bold; margin-bottom: 10px; color: #ff3b3b; }
        .lock-msg { font-size: 16px; color: #aaa; margin-bottom: 25px; max-width: 400px; line-height: 1.5; }
        .btn-action {
            background: #00ff88; color: #000;
            text-decoration: none; padding: 10px 25px;
            border-radius: 30px; font-weight: bold; font-size: 14px;
            transition: transform 0.2s; display: inline-block;
            border: none; cursor: pointer;
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
            poster="<?= $posterUrl ?>"
            playsinline>
            <source src="<?= $videoUrl ?>" type="video/mp4">
            Tu navegador no soporta video HTML5.
        </video>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const video = document.getElementById('mainPlayer');
                if(video) {
                    video.addEventListener('contextmenu', e => e.preventDefault());
                    video.play().catch(e => { console.log('Autoplay requiere interacción'); });
                }
            });
        </script>
        
    <?php else: ?>
        <div class="locked-overlay">
            <div class="lock-icon">🔒</div>
            <div class="lock-title"><?= htmlspecialchars($lockTitle) ?></div>
            <div class="lock-msg"><?= $lockMsg ?></div>
            
            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/account.php" target="_blank" class="btn-action">
                    Renovar Membresía
                </a>
            <?php else: ?>
                 <a href="<?= SITE_URL ?>" target="_blank" class="btn-action">
                    Más Información
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
