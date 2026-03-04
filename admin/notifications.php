<?php
// admin/notifications.php - Centro de Mensajes Global y Historial
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

requireAdmin(); // Solo tú puedes entrar aquí

$message = '';
$messageType = '';

// --- 1. PROCESAR ENVÍO DE NOTIFICACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $target = $_POST['target']; 

    if (empty($title) || empty($content)) {
        $message = "El título y el mensaje son obligatorios.";
        $messageType = "error";
    } else {
        try {
            db()->beginTransaction();
            
            if ($target === 'all') {
                $stmt = db()->query("SELECT id FROM users WHERE status = 'active'");
                $users = $stmt->fetchAll();
                
                $insert = db()->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                foreach ($users as $u) {
                    $insert->execute([$u['id'], $title, $content]);
                }
                $message = "Notificación enviada a " . count($users) . " usuarios.";
            } else {
                $userId = intval($target);
                db()->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")->execute([$userId, $title, $content]);
                $message = "Notificación enviada al usuario seleccionado.";
            }
            
            db()->commit();
            $messageType = "success";
            
        } catch (Exception $e) {
            db()->rollBack();
            $message = "Error al enviar: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// --- 2. PROCESAR ELIMINACIÓN DE NOTIFICACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_notif_id'])) {
    $delId = intval($_POST['delete_notif_id']);
    try {
        db()->prepare("DELETE FROM notifications WHERE id = ?")->execute([$delId]);
        $message = "Notificación eliminada correctamente.";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Error al eliminar: " . $e->getMessage();
        $messageType = "error";
    }
}

// --- CARGAR DATOS PARA LA VISTA ---
// Lista de usuarios para el selector
$usersList = db()->query("SELECT id, username FROM users WHERE status = 'active' ORDER BY username ASC")->fetchAll();

// Historial de notificaciones (Últimas 100 para no saturar la página)
$historyStmt = db()->query("
    SELECT n.*, u.username 
    FROM notifications n 
    JOIN users u ON n.user_id = u.id 
    ORDER BY n.created_at DESC 
    LIMIT 100
");
$notifHistory = $historyStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Centro de Notificaciones - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .notif-card { background: var(--bg-card); padding: 30px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-secondary); }
        .form-control { width: 100%; padding: 12px; background: var(--bg-primary); border: 1px solid var(--border-color); color: #fff; border-radius: 8px; }
        .form-control:focus { border-color: var(--accent-green); outline: none; }
        .btn-send { background: var(--accent-green); color: #000; padding: 12px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1em; transition: 0.2s; }
        .btn-send:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,255,136,0.3); }
        
        .notification { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .notification.success { background: rgba(0,255,136,0.1); color: #00ff88; border: 1px solid rgba(0,255,136,0.2); }
        .notification.error { background: rgba(255,59,59,0.1); color: #ff3b3b; border: 1px solid rgba(255,59,59,0.2); }

        /* Estilos de la tabla de historial */
        .table-container { overflow-x: auto; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { background: var(--bg-secondary); padding: 15px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); }
        .data-table td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; vertical-align: middle; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }
        
        /* Badges de estado */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-read { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 136, 0.2); }
        .badge-unread { background: rgba(255, 170, 0, 0.1); color: #ffaa00; border: 1px solid rgba(255, 170, 0, 0.2); }
        
        .btn-delete { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; transition: all 0.2s; }
        .btn-delete:hover { background: #ff3b3b; color: #fff; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-secondary); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Centro de Mensajes 📢</h1>
        </div>
        
        <?php if($message): ?>
            <div class="notification <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="notif-card" style="max-width: 800px;">
            <p style="color: #aaa; margin-bottom: 25px;">Envía avisos, actualizaciones o promociones directamente al panel de tus usuarios.</p>
            
            <form method="POST">
                <input type="hidden" name="send_notification" value="1">
                
                <div class="form-group">
                    <label>Destinatario</label>
                    <select name="target" class="form-control" required>
                        <option value="all">🌐 Enviar a TODOS los usuarios</option>
                        <optgroup label="Usuarios Específicos">
                            <?php foreach($usersList as $u): ?>
                                <option value="<?= $u['id'] ?>">👤 <?= htmlspecialchars($u['username']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label>Título de la Notificación</label>
                    <input type="text" name="title" class="form-control" placeholder="Ej: ¡Nueva actualización disponible!" required>
                </div>

                <div class="form-group">
                    <label>Contenido del Mensaje</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Escribe tu mensaje aquí..." required></textarea>
                </div>

                <button type="submit" class="btn-send">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: text-bottom; margin-right: 5px;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    Enviar Notificación
                </button>
            </form>
        </div>

        <h3 style="margin-bottom: 15px; color: #fff;">Historial de Envíos <span style="font-size: 14px; color: #aaa; font-weight: normal;">(Últimas 100)</span></h3>
        
        <div class="table-container">
            <?php if (empty($notifHistory)): ?>
                <div class="empty-state">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5; margin-bottom: 10px;"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    <p>Aún no has enviado ninguna notificación.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Destinatario</th>
                            <th>Título</th>
                            <th style="width: 35%;">Mensaje</th>
                            <th>Estado</th>
                            <th>Fecha de Envío</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($notifHistory as $n): ?>
                        <tr>
                            <td style="font-weight: bold; color: var(--accent-green);">@<?= htmlspecialchars($n['username']) ?></td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($n['title']) ?></td>
                            <td style="color: #aaa;"><?= htmlspecialchars(mb_strimwidth($n['message'], 0, 60, "...")) ?></td>
                            <td>
                                <?php if($n['is_read']): ?>
                                    <span class="badge badge-read">Leído</span>
                                <?php else: ?>
                                    <span class="badge badge-unread">No Leído</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #888; font-size: 13px;">
                                <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta notificación del panel del usuario?');" style="margin: 0;">
                                    <input type="hidden" name="delete_notif_id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn-delete">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <br><br>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
