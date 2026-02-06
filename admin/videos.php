<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
requireLogin();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

if (isAdmin()) {
    $countStmt = db()->query("SELECT COUNT(*) FROM videos WHERE status = 'active'");
    $totalVideos = $countStmt->fetchColumn();
    
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.status = 'active'
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$perPage, $offset]);
} else {
    $countStmt = db()->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ? AND status = 'active'");
    $countStmt->execute([$_SESSION['user_id']]);
    $totalVideos = $countStmt->fetchColumn();
    
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v
        JOIN users u ON v.user_id = u.id
        WHERE v.user_id = ? AND v.status = 'active'
        ORDER BY v.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$_SESSION['user_id'], $perPage, $offset]);
}

$videos = $stmt->fetchAll();
$totalPages = ceil($totalVideos / $perPage);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Videos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .videos-table { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; }
        .videos-table table { width: 100%; border-collapse: collapse; }
        .videos-table th { background-color: var(--bg-secondary); padding: 15px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: var(--text-secondary); }
        .videos-table td { padding: 15px; border-bottom: 1px solid var(--border-color); }
        .video-title-cell { display: flex; align-items: center; gap: 15px; }
        .video-thumb-small { width: 80px; height: 45px; background-color: var(--bg-secondary); border-radius: 6px; overflow: hidden; }
        .video-thumb-small img { width: 100%; height: 100%; object-fit: cover; }
        .action-buttons { display: flex; gap: 10px; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state svg { width: 80px; height: 80px; margin-bottom: 20px; opacity: 0.3; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Mis Videos</h1>
            <button class="btn" onclick="location.href='/upload.php'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
                </svg>
                Subir Video
            </button>
        </div>
        
        <?php if (empty($videos)): ?>
            <div class="videos-table">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                    </svg>
                    <h3>No tienes videos subidos aún</h3>
                    <p>Comienza subiendo tu primer video</p>
                    <a href="/upload.php" class="btn" style="margin-top: 20px;">Subir Video</a>
                </div>
            </div>
        <?php else: ?>
            <div class="videos-table">
                <table>
                    <thead>
                        <tr>
                            <th>Video</th>
                            <th>Vistas</th>
                            <th>Tamaño</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($videos as $video): ?>
                        <tr>
                            <td>
                                <div class="video-title-cell">
                                    <div class="video-thumb-small">
                                        <div style="width: 100%; height: 100%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center;">
                                            <svg width="30" height="30" viewBox="0 0 24 24" fill="var(--text-secondary)">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($video['title']) ?></strong>
                                        <?php if (isAdmin()): ?>
                                            <br><small style="color: var(--text-secondary);">por <?= htmlspecialchars($video['username']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= number_format($video['views']) ?></td>
                            <td><?= formatFileSize($video['file_size']) ?></td>
                            <td><?= date('d/m/Y', strtotime($video['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="/watch.php?v=<?= $video['embed_code'] ?>" target="_blank" class="btn btn-secondary" style="padding: 6px 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                        </svg>
                                    </a>
                                    <button onclick="copyToClipboard('<?= SITE_URL ?>/watch.php?v=<?= $video['embed_code'] ?>')" class="btn btn-secondary" style="padding: 6px 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
                                        </svg>
                                    </button>
                                    <button onclick="deleteVideo(<?= $video['id'] ?>)" class="btn btn-danger" style="padding: 6px 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function deleteVideo(videoId) {
            if (confirm('¿Estás seguro de eliminar este video?')) {
                fetch('/api/delete-video.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ video_id: videoId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Video eliminado exitosamente');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showNotification(data.message || 'Error al eliminar', 'error');
                    }
                });
            }
        }
    </script>
</body>
</html>