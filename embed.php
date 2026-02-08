<?php
// embed.php - Reproductor optimizado, seguro y responsive
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// 1. Sanitización de Entrada
$embedCode = isset($_GET['v']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['v']) : '';

if (empty($embedCode)) {
    die("Error: Código de video no proporcionado.");
}

// 2. Obtener metadatos del video de forma segura
try {
    $stmt = db()->prepare("
        SELECT id, title, storage_type, storage_path, status, created_at, views 
        FROM videos 
        WHERE embed_code = ? LIMIT 1
    ");
    $stmt->execute([$embedCode]);
    $video = $stmt->fetch();

    if (!$video || $video['status'] !== 'active') {
        http_response_code(404);
        die("Este video no está disponible o ha sido eliminado.");
    }

    // 3. Determinar la URL del video (Source)
    // Usamos los proxies optimizados para garantizar velocidad y acceso a buckets privados
    $videoUrl = '';
    if ($video['storage_type'] === 'local') {
        $videoUrl = APP_URL . '/stream.php?v=' . $embedCode;
    } else {
        // Para Wasabi, Contabo, AWS usamos el proxy S3 V4
        $videoUrl = APP_URL . '/s3-proxy.php?v=' . $embedCode;
    }
    
    // Generar URL de miniatura (Poster) si existe, o una por defecto
    // Asumimos que si hay imagen, sigue el mismo patrón de nombre pero jpg
    $posterUrl = APP_URL . '/assets/img/video-placeholder.jpg'; // Imagen por defecto
    
    // Título seguro para HTML
    $safeTitle = htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8');
    $shareUrl = APP_URL . '/embed.php?v=' . $embedCode;

} catch (PDOException $e) {
    error_log("Embed DB Error: " . $e->getMessage());
    http_response_code(500);
    die("Error temporal del sistema.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $safeTitle; ?> - Reproductor</title>
    
    <meta property="og:title" content="<?php echo $safeTitle; ?>" />
    <meta property="og:type" content="video.other" />
    <meta property="og:url" content="<?php echo $shareUrl; ?>" />
    <meta property="og:image" content="<?php echo $posterUrl; ?>" />
    <meta property="og:description" content="Mira este video en nuestra plataforma." />
    <meta name="twitter:card" content="player" />
    <meta name="twitter:player" content="<?php echo $shareUrl; ?>" />
    <meta name="twitter:player:width" content="1280" />
    <meta name="twitter:player:height" content="720" />

    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background: #000; overflow: hidden; }
        .video-container {
            position: relative;
            width: 100%;
            height: 100vh; /* Ocupar toda la ventana */
            display: flex;
            justify-content: center;
            align-items: center;
        }
        video {
            max-width: 100%;
            max-height: 100%;
            width: 100%;
            height: 100%;
            object-fit: contain; /* Mantiene la proporción sin deformar */
        }
        /* Estilo personalizado simple para controles (opcional) */
        video::-webkit-media-controls-panel {
            background-image: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        }
    </style>
</head>
<body>

<div class="video-container">
    <video 
        id="mainPlayer" 
        controls 
        autoplay 
        preload="metadata" 
        poster="<?php echo $posterUrl; ?>"
        playsinline>
        <source src="<?php echo $videoUrl; ?>" type="video/mp4">
        Tu navegador no soporta la reproducción de video HTML5.
    </video>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const video = document.getElementById('mainPlayer');
        
        // Manejo de errores de carga
        video.addEventListener('error', function() {
            console.error('Error al cargar el video:', video.error);
            // Aquí podrías mostrar un div con mensaje de error amigable
        });

        // Prevenir menú contextual (clic derecho) sobre el video para "dificultar" descarga simple
        video.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        }, false);
    });
</script>

</body>
</html>
