<?php
// api/update-user.php - Endpoint para gestionar usuarios (Admin)
// Actualizado para soportar límites de dominios
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// 1. Seguridad: Solo administradores pueden tocar esto
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}

// 2. Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// 3. Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$action = isset($input['action']) ? $input['action'] : 'update_status'; // Default para retrocompatibilidad

// 4. Validación básica de Usuario
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

try {
    // --- CASO 1: ACTUALIZAR LÍMITE DE DOMINIOS (NUEVO) ---
    if ($action === 'update_limit') {
        $limit = isset($input['domain_limit']) ? intval($input['domain_limit']) : 3;
        
        if ($limit < 0) {
            echo json_encode(['success' => false, 'message' => 'El límite no puede ser negativo']);
            exit;
        }

        $stmt = db()->prepare("UPDATE users SET domain_limit = ? WHERE id = ?");
        $stmt->execute([$limit, $userId]);

        logActivity('update_domain_limit', [
            'target_user_id' => $userId,
            'new_limit' => $limit
        ]);

        echo json_encode(['success' => true, 'message' => 'Límite de dominios actualizado a ' . $limit]);
        exit;
    }

    // --- CASO 2: ACTUALIZAR ESTADO (Lógica Original) ---
    // Si no es update_limit, asumimos que es cambio de estado (active/suspended)
    $status = isset($input['status']) ? trim($input['status']) : '';

    if (!in_array($status, ['active', 'suspended'])) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido. Use "active" o "suspended"']);
        exit;
    }

    // Evitar auto-suspensión (¡Importante!)
    if ($userId === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes suspender tu propia cuenta de administrador.']);
        exit;
    }

    $stmt = db()->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $userId]);
    
    logActivity('update_user_status', [
        'target_user_id' => $userId,
        'new_status' => $status
    ]);
    
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
