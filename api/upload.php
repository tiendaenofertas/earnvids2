<?php
// api/upload.php - Final corregido
if (ob_get_level()) ob_end_clean();

// Configuración temporal para ver errores si ocurren
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message']]);
        exit;
    }
});

try {
    // 1. Cargar configuración usando ruta relativa segura
    $configPath = __DIR__ . '/../config/app.php';
    if (!file_exists($configPath)) throw new Exception("No se encuentra config/app.php");
    require_once $configPath;

    // 2. Usar la constante ROOT_PATH definida ahora correctamente en app.php
    require_once ROOT_PATH . '/includes/auth.php';
    require_once ROOT_PATH . '/includes/upload_handler.php';

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesión expirada. Inicia sesión nuevamente (Nueva sesión generada).']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    if (empty($_FILES)) {
        if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
            throw new Exception("El archivo supera el límite del servidor (" . ini_get('post_max_size') . ").");
        }
        throw new Exception('No se recibió archivo.');
    }

    $fileInputName = array_key_first($_FILES);
    $fileData = $_FILES[$fileInputName];
    $userId = $_SESSION['user_id'];
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;

    $handler = new UploadHandler();
    $result = $handler->processUpload($fileData, $userId, $title);
    
    if ($result['success']) {
        if (!isset($result['redirect_url'])) {
            $result['redirect_url'] = '/MisVideos';
        }
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
