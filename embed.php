<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

$embedCode = $_GET['v'] ?? '';

if (!$embedCode) {
    exit('Video no encontrado');
}

// Obtener video
$stmt = db()->prepare("
    SELECT * FROM videos 
    WHERE embed_code = ? AND status = 'active' AND access_type IN ('public', 'unlisted')
");
$stmt->execute([$embedCode]);
$video = $stmt->fetch();

if (!$video) {
    exit('Video no disponible');
}

// Incrementar vistas
incrementViews($video['id']);

$videoUrl = getVideoUrl($video);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; overflow: hidden; }
        video { width: 100%; height: 100vh; object-fit: contain; }
    </style>
</head>
<body>
    <video controls autoplay>
        <source src="<?= $videoUrl ?>" type="video/mp4">
    </video>
</body>
</html>