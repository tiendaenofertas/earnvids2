<?php
// admin/videos.php - Gestión de Videos conectada a API segura
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

// Paginación simple
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Obtener videos con info de usuario
$stmt = db()->prepare("
    SELECT v.*, u.username 
    FROM videos v 
    LEFT JOIN users u ON v.user_id = u.id 
    WHERE v.status != 'deleted'
    ORDER BY v.created_at DESC 
    LIMIT ? OFFSET ?
");
// PDO necesita enteros para LIMIT/OFFSET
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$videos = $stmt->fetchAll();

// Total para paginación
$totalVideos = db()->query("SELECT COUNT(*) FROM videos WHERE status != 'deleted'")->fetchColumn();
$totalPages = ceil($totalVideos / $perPage);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Videos - Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-local { background: #3b82f6; color: white; }
        .badge-cloud { background: #8b5cf6; color: white; } /* Wasabi/Contabo */
        .action-btn { cursor: pointer; border: none; background: none; padding: 5px; }
        .btn-delete { color: #ef4444; }
        .btn-delete:hover { color: #dc2626; }
        .pagination { margin-top: 20px; display: flex; gap: 10px; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gestión de Videos (<?= $totalVideos ?>)</h1>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Miniatura</th>
                        <th>Título</th>
                        <th>Usuario</th>
                        <th>Almacenamiento</th>
                        <th>Vistas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videos as $video): ?>
                    <tr id="video-row-<?= $video['id'] ?>">
                        <td>#<?= $video['id'] ?></td>
                        <td>
                            <div style="width: 60px; height: 34px; background: #333; border-radius: 4px; overflow: hidden;">
                                <img src="/assets/img/video-placeholder.jpg" style="width:100%; height:100%; object-fit:cover;">
                            </div>
                        </td>
                        <td>
                            <a href="/watch.php?v=<?= $video['embed_code'] ?>" target="_blank" style="color: var(--text-primary); text-decoration: none;">
                                <?= htmlspecialchars($video['title']) ?>
                            </a>
                            <div style="font-size: 11px; color: var(--text-secondary);"><?= $video['filename'] ?></div>
                        </td>
                        <td><?= htmlspecialchars($video['username'] ?? 'Anónimo') ?></td>
                        <td>
                            <?php if ($video['storage_type'] === 'local'): ?>
                                <span class="badge badge-local">LOCAL</span>
                            <?php else: ?>
                                <span class="badge badge-cloud"><?= strtoupper($video['storage_type']) ?></span>
                            <?php endif; ?>
                            <div style="font-size: 11px; margin-top: 2px;"><?= formatFileSize($video['file_size']) ?></div>
                        </td>
                        <td><?= number_format($video['views']) ?></td>
                        <td>
                            <button class="action-btn btn-delete" onclick="deleteVideo(<?= $video['id'] ?>)" title="Eliminar Video">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function deleteVideo(id) {
        if (!confirm('¿Estás SEGURO de eliminar este video? Se borrará de la nube/disco y no se puede recuperar.')) return;
        
        fetch('/api/delete-video.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ video_id: id })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Eliminar fila visualmente con efecto fade
                const row = document.getElementById('video-row-' + id);
                row.style.opacity = '0.5';
                setTimeout(() => row.remove(), 500);
                alert('Video eliminado correctamente.');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => alert('Error de conexión con el servidor'));
    }
    </script>
</body>
</html>
