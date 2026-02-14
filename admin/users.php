<?php
// admin/users.php - Gestión de Usuarios con Control de Membresía y Dominios
require_once '../config/app.php';
require_once '../includes/functions.php';
requireAdmin();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countStmt = db()->query("SELECT COUNT(*) FROM users");
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

// Consultar usuarios incluyendo fecha de expiración, estadísticas y datos de dominios
// NOTA: Se asume que has ejecutado el SQL para añadir 'domain_limit' a users y crear la tabla 'user_domains'
$stmt = db()->prepare("
    SELECT u.*, 
           COUNT(DISTINCT v.id) as video_count,
           COALESCE(SUM(v.file_size), 0) as total_storage,
           (SELECT COUNT(*) FROM user_domains ud WHERE ud.user_id = u.id) as domain_count
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
        .users-table { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .users-table table { width: 100%; border-collapse: collapse; }
        .users-table th { background-color: var(--bg-secondary); padding: 15px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: var(--text-secondary); }
        .users-table td { padding: 15px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        
        .user-role { display: inline-block; padding: 4px 12px; background-color: rgba(0, 168, 255, 0.1); color: var(--accent-blue); font-size: 11px; font-weight: 700; border-radius: 20px; text-transform: uppercase;}
        .user-role.admin { background-color: rgba(0, 255, 136, 0.1); color: var(--accent-green); }
        
        /* Controles de Membresía y Dominios */
        .control-group { display: flex; align-items: center; gap: 8px; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 6px; border: 1px solid var(--border-color); width: fit-content; }
        .input-mini { width: 50px; background: var(--bg-primary); border: 1px solid var(--border-color); color: #fff; padding: 5px; border-radius: 4px; text-align: center; font-size: 13px; }
        .input-mini:focus { outline: none; border-color: var(--accent-green); }
        
        .btn-icon { background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary); width: 28px; height: 28px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
        .btn-icon:hover { background: var(--accent-green); color: #000; border-color: var(--accent-green); }
        .btn-icon.danger:hover { background: var(--accent-red); color: #fff; border-color: var(--accent-red); }
        
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .status-active { background-color: var(--accent-green); box-shadow: 0 0 5px var(--accent-green); }
        .status-inactive { background-color: var(--text-secondary); }
        
        .expiry-text { font-size: 11px; color: var(--text-secondary); display: block; margin-top: 4px; }
        .active-text { color: var(--accent-green); font-weight: bold; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gestión de Usuarios</h1>
            <div class="header-stats" style="color: var(--text-secondary);">Total: <strong><?= $totalUsers ?></strong> usuarios</div>
        </div>
        
        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Usuario / Email</th>
                        <th>Rol</th>
                        <th>Almacenamiento</th>
                        <th>Límite Dominios</th>
                        <th>Membresía (Días)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        // Calcular estado de membresía
                        $isMember = !empty($user['membership_expiry']) && strtotime($user['membership_expiry']) > time();
                        $daysLeft = $isMember ? ceil((strtotime($user['membership_expiry']) - time()) / 86400) : 0;
                        $expiryText = $isMember ? "Vence: " . date('d/m/y', strtotime($user['membership_expiry'])) : "Sin membresía";
                        
                        // Límite de dominios (default 3 si es null)
                        $domainLimit = $user['domain_limit'] ?? 3;
                    ?>
                    <tr id="row-<?= $user['id'] ?>">
                        <td>
                            <div style="font-weight: bold; font-size: 14px;"><?= htmlspecialchars($user['username']) ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?= htmlspecialchars($user['email']) ?></div>
                        </td>
                        <td><span class="user-role <?= $user['role'] ?>"><?= $user['role'] ?></span></td>
                        <td>
                            <?= formatFileSize($user['total_storage']) ?>
                            <div style="font-size: 11px; color: var(--text-secondary);"><?= $user['video_count'] ?> videos</div>
                        </td>
                        
                        <td>
                            <div class="control-group">
                                <input type="number" id="domain-limit-<?= $user['id'] ?>" class="input-mini" value="<?= $domainLimit ?>" min="0">
                                <button class="btn-icon" onclick="updateDomainLimit(<?= $user['id'] ?>)" title="Guardar Límite">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                </button>
                            </div>
                            <small class="expiry-text"><?= $user['domain_count'] ?> registrados</small>
                        </td>

                        <td>
                            <div style="margin-bottom: 5px;">
                                <span class="status-dot <?= $isMember ? 'status-active' : 'status-inactive' ?>" id="dot-<?= $user['id'] ?>"></span>
                                <span style="font-size: 13px;" id="days-display-<?= $user['id'] ?>" class="<?= $isMember ? 'active-text' : '' ?>">
                                    <?= $isMember ? $daysLeft . ' días restantes' : 'Inactiva' ?>
                                </span>
                            </div>
                            
                            <div class="control-group">
                                <input type="number" id="input-days-<?= $user['id'] ?>" class="input-mini" placeholder="Días" value="30" min="1">
                                
                                <button class="btn-icon" onclick="updateMembership(<?= $user['id'] ?>, 'set')" title="Establecer/Activar">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                </button>
                                
                                <button class="btn-icon danger" onclick="updateMembership(<?= $user['id'] ?>, 'remove')" title="Desactivar Membresía">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                </button>
                            </div>
                            <small class="expiry-text" id="expiry-text-<?= $user['id'] ?>"><?= $expiryText ?></small>
                        </td>
                        
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span style="color: var(--accent-green); font-size: 12px; font-weight: bold;">Activo</span>
                            <?php else: ?>
                                <span style="color: var(--accent-red); font-size: 12px; font-weight: bold;">Suspendido</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <?php if ($user['status'] === 'active'): ?>
                                    <button onclick="updateUserStatus(<?= $user['id'] ?>, 'suspended')" class="btn-icon danger" style="width: auto; padding: 0 10px;" title="Suspender acceso">Suspender</button>
                                <?php else: ?>
                                    <button onclick="updateUserStatus(<?= $user['id'] ?>, 'active')" class="btn-icon" style="width: auto; padding: 0 10px;" title="Reactivar acceso">Activar</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-size: 11px;">Tu cuenta</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
            <div style="margin-top: 20px; display: flex; justify-content: center; gap: 5px;">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="btn-icon" style="text-decoration: none; width: 30px; height: 30px; <?= $page==$i ? 'background:var(--accent-green);color:#000;' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        // Función para actualizar límite de dominios (NUEVA)
        function updateDomainLimit(userId) {
            const input = document.getElementById(`domain-limit-${userId}`);
            const limit = parseInt(input.value);

            if (isNaN(limit) || limit < 0) {
                showNotification('Ingresa un número válido', 'error');
                return;
            }

            fetch('/api/update-user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    user_id: userId, 
                    action: 'update_limit', // Acción específica
                    domain_limit: limit 
                })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    showNotification(data.message);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(() => showNotification('Error de conexión', 'error'));
        }

        // Función para membresías (Existente)
        function updateMembership(userId, action) {
            const daysInput = document.getElementById(`input-days-${userId}`);
            const days = parseInt(daysInput.value);
            
            if (action === 'set' && (isNaN(days) || days <= 0)) {
                showNotification('Por favor ingresa una cantidad válida de días.', 'error');
                return;
            }
            
            if (action === 'remove' && !confirm('¿Estás seguro de desactivar la membresía de este usuario?')) {
                return;
            }

            fetch('/api/membership_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ 
                    user_id: userId, 
                    action: action, 
                    days: days 
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message);
                    
                    const dot = document.getElementById(`dot-${userId}`);
                    const text = document.getElementById(`days-display-${userId}`);
                    const subtext = document.getElementById(`expiry-text-${userId}`);
                    
                    if (data.is_active) {
                        dot.className = 'status-dot status-active';
                        text.className = 'active-text';
                        text.textContent = data.days_left + ' días restantes';
                        subtext.textContent = 'Vence: ' + data.expiry_date;
                    } else {
                        dot.className = 'status-dot status-inactive';
                        text.className = '';
                        text.textContent = 'Inactiva';
                        subtext.textContent = 'Sin membresía';
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showNotification('Error de conexión', 'error');
            });
        }

        // Función para suspender usuarios (Existente)
        function updateUserStatus(userId, status) {
            if(status === 'suspended' && !confirm('¿Suspender acceso al panel de este usuario?')) return;
            
            fetch('/api/update-user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                // Se envía action implícito o null para compatibilidad
                body: JSON.stringify({ user_id: userId, status: status }) 
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) location.reload();
                else showNotification(data.message, 'error');
            });
        }
    </script>
</body>
</html>
