<?php
// api/upload.php - Endpoint con excepción para Administradores
require_once '../config/app.php';
require_once '../includes/auth.php';
require_once '../includes/upload_handler.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $userId = $_SESSION['user_id'];
    // Detectar si es administrador
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    // --- VALIDACIÓN: Verificar Credenciales (SOLO SI NO ES ADMIN) ---
    if (!$isAdmin) {
        $stmt = db()->prepare("
            SELECT id 
            FROM storage_config 
            WHERE user_id = ? 
            AND is_active = 1 
            AND access_key != '' 
            AND secret_key != ''
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $hasStorage = $stmt->fetch();

        if (!$hasStorage) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Configure su almacenamiento. Es necesario configurar sus Access Keys en la sección de Cuenta antes de subir videos.',
                'error_code' => 'NO_STORAGE_CONFIG'
            ]);
            exit;
        }
    }
    // ------------------------------------------------------------------

    if (empty($_FILES)) {
        throw new Exception('No se recibió el archivo');
    }
    
    $fileInputName = array_key_first($_FILES);
    $fileData = $_FILES[$fileInputName];
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;

    $handler = new UploadHandler();
    $result = $handler->processUpload($fileData, $userId, $title);
    
    if ($result['success']) {
        $result['redirect_url'] = '/watch.php?v=' . $result['embed_code'];
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>
