<?php
// api/upload.php - Endpoint API limpio y seguro para subida de videos
require_once '../config/app.php';
require_once '../includes/auth.php'; // Asegura acceso a funciones de sesión como isLoggedIn()
require_once '../includes/upload_handler.php';

// Configuración de headers para respuesta JSON limpia
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Ajustar según necesidades de CORS
header('Access-Control-Allow-Methods: POST');

// 1. Capa de Seguridad: Autenticación
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Sesión no válida o expirada. Por favor, inicia sesión nuevamente.'
    ]);
    exit;
}

// 2. Validación de Método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método HTTP no permitido']);
    exit;
}

// 3. Procesamiento de la Subida
try {
    // Detección inteligente del archivo:
    // Toma el primer archivo enviado en $_FILES, sin importar si el input se llama 'video', 'file', etc.
    if (empty($_FILES)) {
        throw new Exception('No se recibió ningún archivo de video.');
    }
    
    $fileInputName = array_key_first($_FILES);
    $fileData = $_FILES[$fileInputName];
    
    // Obtener metadatos opcionales
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $userId = $_SESSION['user_id'];

    // Instanciar el manejador blindado
    $handler = new UploadHandler();
    
    // Ejecutar proceso
    $result = $handler->processUpload($fileData, $userId, $title);
    
    if ($result['success']) {
        // Respuesta 200 OK
        echo json_encode($result);
    } else {
        // Error de validación (400 Bad Request)
        http_response_code(400);
        echo json_encode($result);
    }

} catch (Exception $e) {
    // Error inesperado del servidor (500)
    error_log("[API Upload Critical Error] " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor al procesar la solicitud.'
    ]);
}
?>
