<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Verificar API Key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$user = Auth::verifyApiKey($apiKey);

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'API Key inválida']);
    exit;
}

// GET - Listar videos
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Contar total
    $countStmt = db()->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ? AND status = 'active'");
    $countStmt->execute([$user['id']]);
    $totalVideos = $countStmt->fetchColumn();
    
    // Obtener videos
    $stmt = db()->prepare("
        SELECT id, title, embed_code, views, downloads, file_size, created_at
        FROM videos 
        WHERE user_id = ? AND status = 'active'
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], $limit, $offset]);
    $videos = $stmt->fetchAll();
    
    // Formatear respuesta
    $formattedVideos = array_map(function($video) {
        return [
            'id' => $video['id'],
            'title' => $video['title'],
            'embed_code' => $video['embed_code'],
            'views' => intval($video['views']),
            'downloads' => intval($video['downloads']),
            'size' => intval($video['file_size']),
            'created_at' => $video['created_at'],
            'url' => SITE_URL . '/watch/' . $video['embed_code'],
            'embed_url' => SITE_URL . '/embed/' . $video['embed_code']
        ];
    }, $videos);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedVideos,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalVideos / $limit),
            'total_videos' => intval($totalVideos)
        ]
    ]);
}

// DELETE - Eliminar video
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $embedCode = trim($pathInfo, '/');
    
    if (!$embedCode) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código de video requerido']);
        exit;
    }
    
    // Verificar que el video pertenece al usuario
    $stmt = db()->prepare("SELECT id FROM videos WHERE embed_code = ? AND user_id = ?");
    $stmt->execute([$embedCode, $user['id']]);
    $video = $stmt->fetch();
    
    if (!$video) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Video no encontrado']);
        exit;
    }
    
    require_once '../includes/upload_handler.php';
    $uploadHandler = new UploadHandler();
    $result = $uploadHandler->deleteVideo($video['id'], $user['id']);
    
    echo json_encode($result);
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}