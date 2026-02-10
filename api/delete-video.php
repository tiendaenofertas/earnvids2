<?php
// api/delete-video.php - Endpoint para eliminar videos
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/upload_handler.php'; // Usamos el handler centralizado

header('Content-Type: application/json');

// 1. Verificar sesión
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$videoId = isset($input['video_id']) ? intval($input['video_id']) : 0;

if ($videoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de video inválido']);
    exit;
}

try {
    // 2. Usar el UploadHandler para borrar (maneja BD y Archivos físicos)
    $handler = new UploadHandler();
    
    // El método deleteVideo verifica internamente si eres el dueño o admin
    $result = $handler->deleteVideo($videoId, $_SESSION['user_id']);
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error deleting video: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
