<?php
// api/update-user.php - Gestión de usuarios para Administradores
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// 1. Seguridad: Solo administradores pueden entrar aquí
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Permisos de administrador requeridos.']);
    exit;
}

// 2. Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// 3. Obtener datos (JSON)
$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$status = isset($input['status']) ? trim($input['status']) : '';

// 4. Validaciones
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

if (!in_array($status, ['active', 'suspended'])) {
    echo json_encode(['success' => false, 'message' => 'Estado inválido. Use "active" o "suspended"']);
    exit;
}

// Protección: No permitir que el admin se suspenda a sí mismo
if ($userId === $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'No puedes suspender tu propia cuenta.']);
    exit;
}

try {
    // 5. Actualizar estado
    $stmt = db()->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $userId]);
    
    // Loguear acción
    if (function_exists('logActivity')) {
        logActivity('update_user_status', [
            'target_user_id' => $userId,
            'new_status' => $status
        ]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Usuario actualizado correctamente a: ' . ucfirst($status)
    ]);

} catch (Exception $e) {
    error_log("Error updating user: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}
?>
