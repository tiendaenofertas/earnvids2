<?php
// admin/licenses.php - Gestor Global de Licencias
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

requireAdmin();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $userId = intval($_POST['user_id']);
        $status = $_POST['license_status']; // active, suspended, inactive
        
        db()->prepare("UPDATE users SET license_status = ? WHERE id = ?")->execute([$status, $userId]);
        $message = "Estado de la licencia actualizado correctamente.";
        $messageType = "success";
        
    } elseif (isset($_POST['regenerate'])) {
        $userId = intval($_POST['user_id']);
        $newKey = 'XVIDS-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
        
        db()->prepare("UPDATE users SET license_key = ? WHERE id = ?")->execute([$newKey, $userId]);
        $message = "La licencia ha sido regenerada. La clave anterior ya no funcionará.";
        $messageType = "success";
    }
}

// --- PAGINACIÓN 30 RESULTADOS ---
$limit = 30;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$totalUsers = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

$stmt = db()->prepare("
    SELECT id, username, email, license_key, license_status, created_at 
    FROM users 
    ORDER BY id DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Licencias - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .table-container { overflow-x: auto; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-color); margin-top: 20px; }
        .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { background: var(--bg-secondary); padding: 15px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 12px; }
        .data-table td { padding: 15px; border-bottom: 1px solid var(--border-color); font-size: 14px; vertical-align: middle; }
        .data-table tr:hover td { background: rgba(255,255,255,0.02); }
        
        .license-key { font-family: monospace; background: #000; padding: 6px 10px; border-radius: 6px; color: var(--accent-green); font-size: 13px; letter-spacing: 1px; border: 1px solid rgba(0,255,136,0.2); }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-active { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border: 1px solid rgba(0, 255, 136, 0.2); }
        .badge-suspended { background: rgba(255, 170, 0, 0.1); color: #ffaa00; border: 1px solid rgba(255, 170, 0, 0.2); }
        .badge-inactive { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); }
        
        .action-form { display: flex; gap: 10px; align-items: center; }
        .form-select { background: var(--bg-primary); color: #fff; border: 1px solid var(--border-color); padding: 6px; border-radius: 6px; font-size: 13px; }
        .btn-sm { background: var(--bg-secondary); color: #fff; border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-size: 12px; }
        .btn-sm:hover { background: var(--accent-green); color: #000; border-color: var(--accent-green); }
        .btn-danger-sm { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); }
        .btn-danger-sm:hover { background: #ff3b3b; color: #fff; }

        /* Estilos Buscador y Paginación */
        .search-box { position: relative; width: 100%; max-width: 350px; margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: #fff; outline: none; }
        .search-box input:focus { border-color: var(--accent-green); }
        .search-box svg { position: absolute; left: 12px; top: 12px; color: #888; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
        .btn-page { background: var(--bg-card); border: 1px solid var(--border-color); color: #fff; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-weight: bold; }
        .btn-page.active, .btn-page:hover { background: var(--accent-green); color: #000; border-color: var(--accent-green); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gestor de Licencias 🔐</h1>
        </div>
        
        <?php if($message): ?>
            <div class="notification <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <p style="color: var(--text-secondary);">Administra el acceso a los scripts y plugins de tus usuarios.</p>

        <div class="search-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            <input type="text" id="searchInput" placeholder="Buscar usuario o licencia...">
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Clave de Licencia</th>
                        <th>Estado</th>
                        <th>Control de Acceso</th>
                        <th>Seguridad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr class="search-row">
                        <td>
                            <strong><?= htmlspecialchars($u['username']) ?></strong><br>
                            <span style="color: #888; font-size: 12px;"><?= htmlspecialchars($u['email']) ?></span>
                        </td>
                        <td>
                            <?php if($u['license_key']): ?>
                                <span class="license-key"><?= htmlspecialchars($u['license_key']) ?></span>
                            <?php else: ?>
                                <span style="color: #ff3b3b;">Sin licencia</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                if($u['license_status'] == 'active') echo '<span class="badge badge-active">Activa</span>';
                                elseif($u['license_status'] == 'suspended') echo '<span class="badge badge-suspended">Suspendida</span>';
                                else echo '<span class="badge badge-inactive">Inactiva (Eliminada)</span>';
                            ?>
                        </td>
                        <td>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="license_status" class="form-select">
                                    <option value="active" <?= $u['license_status'] == 'active' ? 'selected' : '' ?>>Activar</option>
                                    <option value="suspended" <?= $u['license_status'] == 'suspended' ? 'selected' : '' ?>>Suspender</option>
                                    <option value="inactive" <?= $u['license_status'] == 'inactive' ? 'selected' : '' ?>>Eliminar (Inactiva)</option>
                                </select>
                                <button type="submit" class="btn-sm">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" onsubmit="return confirm('¿Seguro que deseas regenerar la clave?');" style="margin:0;">
                                <input type="hidden" name="regenerate" value="1">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-sm btn-danger-sm">Revocar y Regenerar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="btn-page <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.search-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>