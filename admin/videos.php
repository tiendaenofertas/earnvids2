<?php
// admin/videos.php - Gestor de Videos Premium
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

requireLogin();
$userId = $_SESSION['user_id'];

// ==========================================
// 🚀 SISTEMA DE PAGINACIÓN (30 videos por página)
// ==========================================
$limit = 30; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

if (isAdmin()) {
    $countStmt = db()->query("SELECT COUNT(*) FROM videos");
    $totalVideos = $countStmt->fetchColumn();
    
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v 
        LEFT JOIN users u ON v.user_id = u.id 
        ORDER BY v.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $countStmt = db()->prepare("SELECT COUNT(*) FROM videos WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $totalVideos = $countStmt->fetchColumn();
    
    $stmt = db()->prepare("
        SELECT v.*, u.username 
        FROM videos v 
        LEFT JOIN users u ON v.user_id = u.id 
        WHERE v.user_id = :userid 
        ORDER BY v.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':userid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
}

$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = ceil($totalVideos / $limit);
// ==========================================

function renderStorageBadge($type) {
    $type = strtolower(trim($type ?? ''));
    $bg = 'rgba(255,255,255,0.05)'; $color = '#aaa'; $name = 'Desconocido'; $icon = '☁️';

    if ($type === 'wasabi') { $bg = 'rgba(16, 185, 129, 0.1)'; $color = '#10b981'; $name = 'Wasabi'; $icon = '🟢'; } 
    elseif ($type === 'contabo') { $bg = 'rgba(59, 130, 246, 0.1)'; $color = '#3b82f6'; $name = 'Contabo'; $icon = '🔵'; } 
    elseif ($type === 'cloudflare' || $type === 'r2') { $bg = 'rgba(245, 158, 11, 0.1)'; $color = '#f59e0b'; $name = 'Cloudflare R2'; $icon = '🟠'; } 
    elseif ($type === 'aws' || $type === 's3') { $bg = 'rgba(249, 115, 22, 0.1)'; $color = '#f97316'; $name = 'Amazon S3'; $icon = '📦'; } 
    elseif ($type === 'backblaze' || $type === 'b2') { $bg = 'rgba(239, 68, 68, 0.1)'; $color = '#ef4444'; $name = 'Backblaze B2'; $icon = '🔴'; } 
    elseif ($type === 'local') { $bg = 'rgba(139, 92, 246, 0.1)'; $color = '#8b5cf6'; $name = 'Servidor Local'; $icon = '🖥️'; } 
    else { $name = ucfirst($type); }

    return "<span class='storage-badge' style='background: {$bg}; color: {$color}; border: 1px solid {$bg};'>{$icon} {$name}</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Videos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .controls-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .search-box { position: relative; width: 100%; max-width: 350px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); opacity: 0.5; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: #fff; font-size: 14px; transition: 0.3s; outline: none; }
        .search-box input:focus { border-color: var(--accent-green); box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1); }
        
        .video-table-container { width: 100%; overflow-x: auto; padding-bottom: 20px; }
        .video-table { width: 100%; border-collapse: separate; border-spacing: 0 12px; min-width: 800px; }
        .video-table th { color: var(--text-secondary); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; padding: 0 20px 5px; border-bottom: none; text-align: left; font-weight: 600; }
        .video-table td { background: var(--bg-card); padding: 16px 20px; vertical-align: middle; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); transition: background 0.3s; }
        .video-table td:first-child { border-left: 1px solid var(--border-color); border-radius: 12px 0 0 12px; }
        .video-table td:last-child { border-right: 1px solid var(--border-color); border-radius: 0 12px 12px 0; }
        .video-row:hover td { background: rgba(255,255,255,0.02); }
        
        .title-cell { display: flex; align-items: center; gap: 15px; }
        .video-icon { width: 48px; height: 48px; background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .video-info { display: flex; flex-direction: column; gap: 6px; overflow: hidden; }
        
        a.video-title { font-weight: bold; color: #fff; font-size: 15px; max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; text-decoration: none; display: block; transition: color 0.2s; }
        a.video-title:hover { color: var(--accent-green); text-decoration: underline; }
        
        .badge-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .storage-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; width: max-content; }
        .user-badge { display: inline-flex; align-items: center; background: rgba(255,255,255,0.05); color: #aaa; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; border: 1px solid rgba(255,255,255,0.1); }
        .user-badge strong { color: var(--accent-green); margin-left: 4px; }

        .stat-text { color: var(--text-secondary); font-size: 14px; font-weight: 500; }
        .stat-highlight { color: #fff; font-weight: 600; }
        
        .action-buttons { display: flex; gap: 8px; }
        .btn-action { width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border-color); background: transparent; color: var(--text-secondary); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .btn-action:hover { background: var(--bg-secondary); color: #fff; border-color: #fff; transform: translateY(-2px); }
        .btn-action.copy:hover { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border-color: var(--accent-green); }
        .btn-action.delete:hover { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border-color: #ff3b3b; }
        
        .empty-state { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 16px; padding: 50px 20px; text-align: center; color: var(--text-secondary); }
        .empty-state svg { width: 64px; height: 64px; opacity: 0.5; margin-bottom: 15px; }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; padding-bottom: 30px; flex-wrap: wrap; }
        .btn-page { background: var(--bg-card); border: 1px solid var(--border-color); color: #fff; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; }
        .btn-page:hover { background: var(--bg-secondary); border-color: var(--accent-green); color: var(--accent-green); }
        .btn-page.active { background: var(--accent-green); color: #000; border-color: var(--accent-green); pointer-events: none; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header" style="border-bottom: none; margin-bottom: 10px;">
            <h1 class="page-title">Mis Videos 🎬</h1>
        </div>

        <div class="controls-bar">
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="position: absolute; left: 12px; top: 12px; color: #888;"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" id="searchInput" placeholder="Buscar en esta página...">
            </div>
            
            <a href="/upload.php" style="background: var(--accent-green); color: #000; padding: 12px 20px; border-radius: 10px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                Subir Video
            </a>
        </div>

        <?php if (empty($videos)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>
                <h3>No hay videos en esta página</h3>
                <p style="margin-top: 10px;">Comienza subiendo tu primer contenido a la plataforma.</p>
            </div>
        <?php else: ?>
            <div class="video-table-container">
                <table class="video-table" id="videoTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Archivo de Video</th>
                            <th>Rendimiento</th>
                            <th>Tamaño</th>
                            <th>Fecha de Subida</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($videos as $v): ?>
                        <?php 
                            $embedLink = SITE_URL . '/embed.php?v=' . $v['embed_code'];
                            
                            $sizeFormatted = 'Desconocido';
                            if (!empty($v['size']) && is_numeric($v['size']) && $v['size'] > 0) {
                                $sizeFormatted = formatFileSize($v['size']);
                            } elseif (!empty($v['file_size']) && is_numeric($v['file_size']) && $v['file_size'] > 0) {
                                $sizeFormatted = formatFileSize($v['file_size']);
                            } elseif ($v['storage_type'] === 'local' && !empty($v['storage_path'])) {
                                $realPath = __DIR__ . '/../uploads/' . ltrim($v['storage_path'], '/');
                                if (file_exists($realPath)) {
                                    $sizeFormatted = formatFileSize(filesize($realPath));
                                } else {
                                    $realPathAlt = __DIR__ . '/../' . ltrim($v['storage_path'], '/');
                                    if (file_exists($realPathAlt)) {
                                        $sizeFormatted = formatFileSize(filesize($realPathAlt));
                                    }
                                }
                            }
                        ?>
                        <tr class="video-row" id="row-<?= $v['id'] ?>">
                            <td>
                                <div class="title-cell">
                                    <div class="video-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                    <div class="video-info">
                                        <a href="<?= $embedLink ?>" target="_blank" class="video-title" title="<?= htmlspecialchars($v['title']) ?>">
                                            <?= htmlspecialchars($v['title']) ?>
                                        </a>
                                        
                                        <div class="badge-group">
                                            <?= renderStorageBadge($v['storage_type']) ?>
                                            <?php if (isAdmin()): ?>
                                                <span class="user-badge">👤 Usuario: <strong><?= htmlspecialchars($v['username'] ?? 'Eliminado') ?></strong></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="stat-text"><span class="stat-highlight"><?= number_format($v['views'] ?? 0) ?></span> Vistas</div>
                            </td>
                            <td>
                                <div class="stat-text"><?= $sizeFormatted ?></div>
                            </td>
                            <td>
                                <div class="stat-text"><?= date('d M Y', strtotime($v['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="action-buttons" style="justify-content: flex-end;">
                                    <a href="<?= $embedLink ?>" target="_blank" class="btn-action" title="Probar Video">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    </a>
                                    
                                    <button onclick="copyToClipboard('<?= $embedLink ?>')" class="btn-action copy" title="Copiar Enlace">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                                    </button>
                                    
                                    <button onclick="deleteVideo(<?= $v['id'] ?>)" class="btn-action delete" title="Eliminar de la Nube">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="btn-page">&laquo; Anterior</a>
                <?php endif; ?>
                
                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) { echo '<span style="color:#666;">...</span>'; }
                
                for ($i = $startPage; $i <= $endPage; $i++): 
                    $activeClass = ($i === $page) ? 'active' : '';
                ?>
                    <a href="?page=<?= $i ?>" class="btn-page <?= $activeClass ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages) { echo '<span style="color:#666;">...</span>'; } ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" class="btn-page">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        // BUSCADOR EN VIVO
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.video-row');
            
            rows.forEach(row => {
                let title = row.querySelector('.video-title').textContent.toLowerCase();
                let author = row.querySelector('.user-badge') ? row.querySelector('.user-badge').textContent.toLowerCase() : '';
                
                if (title.includes(filter) || author.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // ⚠️ SOLUCIÓN CRÍTICA: Restauración del motor de borrado JavaScript hacia la API de la nube
        function deleteVideo(videoId) {
            if (confirm('¿Estás seguro de eliminar este video permanentemente? Esto lo borrará también del servidor en la nube.')) {
                fetch('/api/delete-video.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ video_id: videoId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (typeof showNotification === 'function') {
                            showNotification('Video eliminado exitosamente de la nube y del sistema');
                        } else {
                            alert('Video eliminado exitosamente');
                        }
                        // Recargar la página después de 1 segundo para actualizar la lista
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        if (typeof showNotification === 'function') {
                            showNotification(data.message || 'Error al eliminar', 'error');
                        } else {
                            alert(data.message || 'Error al eliminar');
                        }
                    }
                })
                .catch(err => {
                    console.error('Error de conexión:', err);
                    alert('Ocurrió un error de conexión al intentar borrar el archivo. Verifica tu internet o los logs del servidor.');
                });
            }
        }
    </script>
</body>
</html>
