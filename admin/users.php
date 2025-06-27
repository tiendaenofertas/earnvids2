<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
requireAdmin();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = db()->query("SELECT COUNT(*) FROM users");
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

$stmt = db()->prepare("
    SELECT u.*, 
           COUNT(DISTINCT v.id) as video_count,
           COALESCE(SUM(v.file_size), 0) as total_storage
    FROM users u
    LEFT JOIN videos v ON u.id = v.user_id AND v.status = 'active'
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .users-table { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; }
        .users-table table { width: 100%; border-collapse: collapse; }
        .users-table th { background-color: var(--bg-secondary); padding: 15px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: var(--text-secondary); }
        .users-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
        .user-role { display: inline-block; padding: 4px 12px; background-color: rgba(0, 168, 255, 0.1); color: var(--accent-blue); font-size: 12px; font-weight: 600; border-radius: 20px; }
        .user-role.admin { background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); }
        .user-status { display: inline-block; padding: 4px 12px; background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); font-size: 12px; font-weight: 600; border-radius: 20px; }
        .user-status.suspended { background-color: rgba(255, 59, 59, 0.1); color: var(--accent-red); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gestión de Usuarios</h1>
            <div class="header-stats">Total: <?= $totalUsers ?> usuarios</div>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Videos</th>
                        <th>Almacenamiento</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><span class="user-role <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span></td>
                        <td><?= number_format($user['video_count']) ?></td>
                        <td><?= formatFileSize($user['total_storage']) ?></td>
                        <td><span class="user-status <?= $user['status'] ?>"><?= ucfirst($user['status']) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        <td>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <?php if ($user['status'] === 'active'): ?>
                                    <button onclick="suspendUser(<?= $user['id'] ?>)" class="btn btn-danger" style="padding: 6px 12px; font-size: 14px;">Suspender</button>
                                <?php else: ?>
                                    <button onclick="activateUser(<?= $user['id'] ?>)" class="btn btn-secondary" style="padding: 6px 12px; font-size: 14px;">Activar</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 14px;">Tu cuenta</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function suspendUser(userId) {
            if (confirm('¿Estás seguro de suspender este usuario?')) {
                updateUserStatus(userId, 'suspended');
            }
        }
        
        function activateUser(userId) {
            updateUserStatus(userId, 'active');
        }
        
        function updateUserStatus(userId, status) {
            fetch('/api/update-user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ user_id: userId, status: status })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Usuario actualizado');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Error', 'error');
                }
            });
        }
    </script>
</body>
</html>