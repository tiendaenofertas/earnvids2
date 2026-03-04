<?php
// api/notifications.php - Motor AJAX de Notificaciones
require_once '../config/app.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
// Obtener el input JSON o POST normal
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? '';

try {
    if ($action === 'fetch') {
        // Obtener últimas 30 notificaciones
        $stmt = db()->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Contar no leídas
        $stmt2 = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt2->execute([$userId]);
        $unreadCount = $stmt2->fetchColumn();

        echo json_encode(['success' => true, 'notifications' => $notifications, 'unread' => $unreadCount]);
        
    } elseif ($action === 'mark_read') {
        $notifId = $input['id'] ?? 0;
        if ($notifId === 'all') {
            db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
        } else {
            db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([intval($notifId), $userId]);
        }
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $notifId = intval($input['id'] ?? 0);
        db()->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([$notifId, $userId]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción inválida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de servidor']);
}
?>