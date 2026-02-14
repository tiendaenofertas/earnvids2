<?php
// api/membership_action.php - Gestión de Membresías (Admin)
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php'; // Incluye db_connect

header('Content-Type: application/json');

// 1. Seguridad: Solo administradores
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// 2. Obtener datos
$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? intval($input['user_id']) : 0;
$action = isset($input['action']) ? $input['action'] : ''; // 'set', 'add', 'remove'
$days = isset($input['days']) ? intval($input['days']) : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario inválido']);
    exit;
}

try {
    if ($action === 'remove') {
        // DESACTIVAR MEMBRESÍA
        $stmt = db()->prepare("UPDATE users SET membership_expiry = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        $message = "Membresía desactivada.";
        
    } elseif ($action === 'set' && $days > 0) {
        // ACTIVAR / ESTABLECER DÍAS (Desde HOY)
        $newDate = date('Y-m-d H:i:s', strtotime("+$days days"));
        $stmt = db()->prepare("UPDATE users SET membership_expiry = ? WHERE id = ?");
        $stmt->execute([$newDate, $userId]);
        $message = "Membresía activada por $days días.";
        
    } elseif ($action === 'add' && $days > 0) {
        // EXTENDER (Sumar a la fecha actual si existe, o desde hoy)
        // Primero obtenemos la fecha actual
        $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $currentExpiry = $stmt->fetchColumn();
        
        if ($currentExpiry && strtotime($currentExpiry) > time()) {
            // Si está activa, sumamos a esa fecha
            $newDate = date('Y-m-d H:i:s', strtotime($currentExpiry . " +$days days"));
        } else {
            // Si estaba vencida, empezamos desde hoy
            $newDate = date('Y-m-d H:i:s', strtotime("+$days days"));
        }
        
        $stmt = db()->prepare("UPDATE users SET membership_expiry = ? WHERE id = ?");
        $stmt->execute([$newDate, $userId]);
        $message = "Se añadieron $days días extra.";
        
    } else {
        throw new Exception("Acción no válida o días incorrectos.");
    }

    // Devolver nuevo estado para actualizar la interfaz sin recargar
    $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $expiry = $stmt->fetchColumn();
    
    $isActive = ($expiry && strtotime($expiry) > time());
    $daysLeft = $isActive ? ceil((strtotime($expiry) - time()) / 86400) : 0;

    echo json_encode([
        'success' => true,
        'message' => $message,
        'is_active' => $isActive,
        'expiry_date' => $expiry ? date('d/m/Y', strtotime($expiry)) : 'Inactiva',
        'days_left' => $daysLeft
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>