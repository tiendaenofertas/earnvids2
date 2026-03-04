<?php
// api/upload.php - Subida con validación de almacenamiento activo
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/upload_handler.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401); echo json_encode(['success'=>false, 'message'=>'Login requerido']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false, 'message'=>'Método no permitido']); exit;
}

try {
    $userId = $_SESSION['user_id'];
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    // --- VALIDACIÓN DE ALMACENAMIENTO ---
    if (!$isAdmin) {
        // Verificar que tenga AL MENOS UNO activo
        $stmt = db()->prepare("SELECT id FROM storage_users WHERE user_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'No tienes un almacenamiento activo. Ve a Cuenta y activa uno (Wasabi/Contabo).',
                'error_code' => 'NO_ACTIVE_STORAGE'
            ]);
            exit;
        }
    }

    if (empty($_FILES)) throw new Exception('No se recibió archivo');
    
    $fileInput = array_key_first($_FILES);
    $file = $_FILES[$fileInput];
    $title = $_POST['title'] ?? null;

    $handler = new UploadHandler();
    // El handler usará automáticamente el almacenamiento marcado como ACTIVO
    $result = $handler->processUpload($file, $userId, $title);
    
    if ($result['success']) {
        $result['redirect_url'] = '/watch.php?v=' . $result['embed_code'];
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
}
?>
