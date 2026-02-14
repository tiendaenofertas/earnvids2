<?php
// embed.php - Reproductor Híbrido: Protección de Dominio + Verificación de Membresía Vencida
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

    // Permitir propio sitio o acceso directo (según configuración previa)
    if ($refererHost === $selfHost || empty($refererHost)) {
        $isDomainAllowed = true;
    } else {
        // Verificar lista blanca del dueño
        $stmt = db()->prepare("SELECT domain FROM user_domains WHERE user_id = ?");
        $stmt->execute([$video['user_id']]);
        $userDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($userDomains)) {
            foreach ($userDomains as $d) {
                if (strpos($refererHost, strtolower(trim($d))) !== false) {
                    $isDomainAllowed = true;
                    break;
                }
            }
        } else {
            // Verificar lista global
            if (defined('ALLOWED_DOMAINS') && is_array(ALLOWED_DOMAINS) && !empty(ALLOWED_DOMAINS)) {
                 foreach (ALLOWED_DOMAINS as $globalDomain) {
                    if (strpos($refererHost, strtolower(trim($globalDomain))) !== false) {
                        $isDomainAllowed = true;
                        break;
                    }
                }
            } else {
                $isDomainAllowed = true; // Público por defecto si no hay reglas
            }
        }
    }

    if (!$isDomainAllowed) {
        // BLOQUEO POR DOMINIO
        $canWatch = false;
        $showLock = true;
        $lockTitle = "Dominio No Autorizado";
        $lockMsg = "El propietario no permite la reproducción en este sitio web ($refererHost).";
    } else {
        // --- PASO B: Verificar Estado del Usuario (Solo si está logueado) ---
        // Si el usuario inicia sesión, aplicamos las reglas de su cuenta.
        // Si es invitado, pasa libre (ya validó dominio).
        
        if (isLoggedIn()) {
            // Usuario Logueado: Verificar Membresía
            if (isAdmin() || $_SESSION['user_id'] == $video['user_id']) {
                $canWatch = true; // Admin y Dueño siempre ven
            } else {
                // Consultar expiración
                $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $userStatus = $stmt->fetch();

                if ($userStatus && !empty($userStatus['membership_expiry']) && strtotime($userStatus['membership_expiry']) > time()) {
                    $canWatch = true; // Membresía Activa
                } else {
                    // BLOQUEO POR MEMBRESÍA VENCIDA
                    // Aquí es donde mostramos el mensaje que pediste
                    $canWatch = false;
                    $showLock = true;
                    $lockTitle = "Membresía Expirada";
                    $lockMsg = "Tu membresía ha vencido o no está activa. <br>Por favor renueva tu plan para continuar.";
                }
            }
        } else {
            // Usuario Invitado (No logueado):
            // Como pediste que los videos se reproduzcan "independientemente de la sesión",
            // al invitado le permitimos ver si pasó la prueba de dominio.
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
        
        /* PANTALLA DE BLOQUEO (Diseño Original Restaurado) */
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
                video.addEventListener('contextmenu', e => e.preventDefault());
                video.play().catch(e => { console.log('Autoplay requiere interacción'); });
            });
        </script>
        
    <?php else: ?>
        <div class="locked-overlay">
            <div class="lock-icon">🔒</div>
            <div class="lock-title"><?= htmlspecialchars($lockTitle) ?></div>
            <div class="lock-msg"><?= $lockMsg // Permitir HTML para <br> ?></div>
            
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
