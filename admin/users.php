<?php
// admin/users.php - Gestión de Usuarios con Control de Membresía y Dominios
require_once '../config/app.php';
require_once '../includes/functions.php';
requireAdmin();

// --- NUEVO: Interceptar petición AJAX para ELIMINAR USUARIO ---
$input = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['action']) && $input['action'] === 'delete_user') {
    header('Content-Type: application/json');
    $userIdToDelete = intval($input['user_id']);
    
    // Seguridad: Evitar que el administrador se borre a sí mismo por accidente
    if ($userIdToDelete === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propia cuenta']);
        exit;
    }
    
    try {
        // Gracias al ON DELETE CASCADE que configuramos en MySQL, esto borrará todo de forma limpia
        db()->prepare("DELETE FROM users WHERE id = ?")->execute([$userIdToDelete]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos al eliminar.']);
    }
    exit;
}
// --------------------------------------------------------------

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30; // CAMBIADO A 30
$offset = ($page - 1) * $perPage;

$countStmt = db()->query("SELECT COUNT(*) FROM users");
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

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

        /* Filtros y Buscador */
        .filter-container { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; flex-wrap: wrap; }
        .search-box { position: relative; width: 100%; max-width: 350px; margin-bottom: 0; }
        .search-box input { width: 100%; padding: 12px 15px 12px 40px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: #fff; outline: none; }
        .search-box input:focus { border-color: var(--accent-green); }
        .search-box svg { position: absolute; left: 12px; top: 12px; color: #888; }
        
        .select-filter { padding: 11px 15px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; color: #fff; outline: none; cursor: pointer; font-size: 14px; }
        .select-filter:focus { border-color: var(--accent-green); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Gestión de Usuarios</h1>
            <div class="header-stats" style="color: var(--text-secondary);">Total: <strong><?= $totalUsers ?></strong> usuarios</div>
        </div>

        <div class="filter-container">
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                <input type="text" id="searchInput" placeholder="Buscar por nombre o correo...">
            </div>
            
            <select id="membershipFilter" class="select-filter">
                <option value="all">Filtro: Todas las Membresías</option>
                <option value="active">Solo Membresía Activa</option>
                <option value="inactive">Sin Membresía</option>
            </select>
        </div>
        
        <div class="users-table" style="overflow-x: auto;">
            <table style="min-width: 900px;">
                <thead>
                    <tr>
                        <th>Usuario / Email</th>
                        <th>Fecha Registro</th> <th>Rol</th>
                        <th>Almacenamiento</th>
                        <th>Límite Dominios</th>
                        <th>Membresía (Días)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $isMember = !empty($user['membership_expiry']) && strtotime($user['membership_expiry']) > time();
                        $daysLeft = $isMember ? ceil((strtotime($user['membership_expiry']) - time()) / 86400) : 0;
                        $expiryText = $isMember ? "Vence: " . date('d/m/y', strtotime($user['membership_expiry'])) : "Sin membresía";
                        $domainLimit = $user['domain_limit'] ?? 3;
                    ?>
                    <tr class="search-row" id="row-<?= $user['id'] ?>" data-membership="<?= $isMember ? 'active' : 'inactive' ?>">
                        <td>
                            <div style="font-weight: bold; font-size: 14px;"><?= htmlspecialchars($user['username']) ?></div>
                            <div style="font-size: 12px; color: var(--text-secondary);"><?= htmlspecialchars($user['email']) ?></div>
                        </td>
                        
                        <td>
                            <div style="font-size: 13px; color: var(--text-secondary);">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </div>
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
                            <div style="display: flex; gap: 5px; flex-direction: column;">
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button onclick="updateUserStatus(<?= $user['id'] ?>, 'suspended')" class="btn-icon danger" style="width: 100%; padding: 0 10px;" title="Suspender acceso">Suspender</button>
                                    <?php else: ?>
                                        <button onclick="updateUserStatus(<?= $user['id'] ?>, 'active')" class="btn-icon" style="width: 100%; padding: 0 10px;" title="Reactivar acceso">Activar</button>
                                    <?php endif; ?>
                                    
                                    <button onclick="deleteUser(<?= $user['id'] ?>)" class="btn-icon danger" style="width: 100%; padding: 0 10px; border-color: #ff3b3b; color: #ff3b3b;" title="Eliminar Permanentemente">
                                        Eliminar
                                    </button>
                                    
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 11px;">Tu cuenta</span>
                                <?php endif; ?>
                            </div>
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
        // 3️⃣ NUEVO: Lógica combinada de Buscador y Filtro de Membresía
        const searchInput = document.getElementById('searchInput');
        const membershipFilter = document.getElementById('membershipFilter');

        function filterTable() {
            const textFilter = searchInput.value.toLowerCase();
            const memFilter = membershipFilter.value;
            
            document.querySelectorAll('.search-row').forEach(row => {
                const matchesText = row.textContent.toLowerCase().includes(textFilter);
                const matchesMem = (memFilter === 'all') || (row.getAttribute('data-membership') === memFilter);
                
                row.style.display = (matchesText && matchesMem) ? '' : 'none';
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        membershipFilter.addEventListener('change', filterTable);

        // 1️⃣ NUEVO: Función para eliminar usuario de forma segura
        function deleteUser(userId) {
            if (!confirm('⚠️ ¡ADVERTENCIA EXTREMA!\n\n¿Estás completamente seguro de que deseas ELIMINAR a este usuario?\n\nEsta acción NO se puede deshacer y borrará permanentemente sus dominios, videos y configuración.')) {
                return;
            }

            fetch('', { // Se envía la petición a este mismo archivo
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'delete_user', user_id: userId }) 
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    showNotification('Usuario eliminado correctamente');
                    // Ocultamos la fila visualmente sin recargar la página entera
                    const row = document.getElementById('row-' + userId);
                    if(row) row.remove(); 
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(err => { showNotification('Error de conexión', 'error'); });
        }


        // --- FUNCIONES ORIGINALES (INTACTAS) ---
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
                    action: 'update_limit',
                    domain_limit: limit 
                })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) { showNotification(data.message); } else { showNotification(data.message, 'error'); }
            }).catch(() => showNotification('Error de conexión', 'error'));
        }

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
                body: JSON.stringify({ user_id: userId, action: action, days: days })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message);
                    const dot = document.getElementById(`dot-${userId}`);
                    const text = document.getElementById(`days-display-${userId}`);
                    const subtext = document.getElementById(`expiry-text-${userId}`);
                    const row = document.getElementById(`row-${userId}`);
                    
                    if (data.is_active) {
                        dot.className = 'status-dot status-active';
                        text.className = 'active-text';
                        text.textContent = data.days_left + ' días restantes';
                        subtext.textContent = 'Vence: ' + data.expiry_date;
                        row.setAttribute('data-membership', 'active');
                    } else {
                        dot.className = 'status-dot status-inactive';
                        text.className = '';
                        text.textContent = 'Inactiva';
                        subtext.textContent = 'Sin membresía';
                        row.setAttribute('data-membership', 'inactive');
                    }
                    filterTable(); // Actualiza el filtro visualmente
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(err => { showNotification('Error de conexión', 'error'); });
        }

        function updateUserStatus(userId, status) {
            if(status === 'suspended' && !confirm('¿Suspender acceso al panel de este usuario?')) return;
            fetch('/api/update-user.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
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
