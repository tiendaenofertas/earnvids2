<?php
// admin/domains.php - Gestión de Dominios con Validación de Membresía
require_once '../config/app.php';
require_once '../includes/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];

// 1. Obtener Límite, Uso actual y Estado de Membresía
// Optimizamos la consulta para traer todo en una sola petición
$stmt = db()->prepare("SELECT domain_limit, membership_expiry FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userSchema = $stmt->fetch();

$userLimit = ($userSchema && $userSchema['domain_limit'] !== null) ? intval($userSchema['domain_limit']) : 3;

// Verificar si la membresía es válida
$isMembershipActive = false;
if ($userSchema && !empty($userSchema['membership_expiry'])) {
    if (strtotime($userSchema['membership_expiry']) > time()) {
        $isMembershipActive = true;
    }
}

// 2. Obtener Dominios registrados
$stmt = db()->prepare("SELECT * FROM user_domains WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$domains = $stmt->fetchAll();

$count = count($domains);
$limitReached = ($count >= $userLimit);
$percent = ($userLimit > 0) ? min(100, round(($count / $userLimit) * 100)) : 100;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Dominios - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .domains-container { max-width: 800px; margin: 0 auto; }
        
        /* Tarjeta de Límite */
        .limit-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .limit-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .limit-title { font-size: 18px; font-weight: 600; color: var(--text-primary); margin: 0; }
        .limit-value { font-weight: bold; color: var(--accent-green); }
        
        .progress-bg { width: 100%; height: 10px; background: var(--bg-secondary); border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--accent-green); transition: width 0.3s ease; }
        .progress-fill.full { background: var(--accent-red); }

        /* Formulario Agregar */
        .add-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .add-form { display: flex; gap: 15px; }
        .add-input { flex-grow: 1; }
        
        .alert-limit { background: rgba(255, 59, 59, 0.1); color: var(--accent-red); padding: 15px; border-radius: 8px; border: 1px solid rgba(255, 59, 59, 0.2); text-align: center; }
        .alert-membership { background: rgba(255, 193, 7, 0.1); color: #ffc107; padding: 15px; border-radius: 8px; border: 1px solid rgba(255, 193, 7, 0.2); text-align: center; margin-bottom: 25px; }

        /* Lista */
        .domain-list { background: var(--bg-card); border-radius: 12px; overflow: hidden; border: 1px solid var(--border-color); }
        .domain-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid var(--border-color); transition: background 0.2s; }
        .domain-item:last-child { border-bottom: none; }
        .domain-item:hover { background: var(--bg-hover); }
        
        /* Estados visuales */
        .domain-url { font-family: monospace; font-size: 1.1em; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
        .domain-date { font-size: 0.85em; color: var(--text-secondary); }
        
        .status-indicator { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .status-active { background-color: var(--accent-green); box-shadow: 0 0 5px var(--accent-green); }
        .status-inactive { background-color: var(--accent-red); box-shadow: 0 0 5px var(--accent-red); }
        
        .domain-row-inactive { opacity: 0.6; }
        .domain-row-inactive .domain-url { color: var(--accent-red); text-decoration: line-through; }

        .empty-state { padding: 40px; text-align: center; color: var(--text-secondary); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Dominios Permitidos</h1>
        </div>
        
        <div class="domains-container">
            
            <?php if (!$isMembershipActive): ?>
            <div class="alert-membership">
                <strong>⚠️ Membresía Inactiva o Vencida</strong>
                <br>Tus dominios permitidos están desactivados temporalmente. Renueva tu plan para reactivar la protección.
            </div>
            <?php endif; ?>

            <div class="limit-card">
                <div class="limit-header">
                    <h3 class="limit-title">Uso de Dominios</h3>
                    <span class="limit-value"><?= $count ?> / <?= $userLimit ?></span>
                </div>
                <div class="progress-bg">
                    <div class="progress-fill <?= $limitReached ? 'full' : '' ?>" style="width: <?= $percent ?>%"></div>
                </div>
                <p style="font-size: 0.9em; color: var(--text-secondary); margin-top: 10px;">
                    Estos son los sitios web autorizados para reproducir tus videos (Hotlink Protection).
                </p>
            </div>

            <div class="add-card">
                <?php if ($isMembershipActive): ?>
                    <?php if (!$limitReached): ?>
                        <h3 style="margin-bottom: 15px;">Agregar Nuevo Dominio</h3>
                        <form id="addDomainForm" class="add-form" onsubmit="addDomain(event)">
                            <input type="text" id="newDomain" class="form-control add-input" placeholder="ejemplo.com" required autocomplete="off">
                            <button type="submit" class="btn" id="btnAdd">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                Agregar
                            </button>
                        </form>
                        <small style="display: block; margin-top: 10px; color: var(--text-secondary);">
                            No incluyas "http://" o "https://". Solo el nombre del dominio (ej: <code>misitio.net</code>).
                        </small>
                    <?php else: ?>
                        <div class="alert-limit">
                            <strong>Has alcanzado el límite de dominios permitidos.</strong>
                            <br>Elimina uno existente para agregar otro, o contacta a soporte para aumentar tu cupo.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="text-align: center; color: var(--text-secondary);">
                        <p>No puedes agregar nuevos dominios hasta que actives tu membresía.</p>
                        <a href="/account.php" class="btn btn-sm" style="margin-top:10px; display:inline-block;">Ir a Pagos</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="domain-list">
                <?php if ($count > 0): ?>
                    <?php foreach ($domains as $domain): ?>
                    <div class="domain-item <?= !$isMembershipActive ? 'domain-row-inactive' : '' ?>" id="domain-row-<?= $domain['id'] ?>">
                        <div>
                            <div class="domain-url">
                                <span class="status-indicator <?= $isMembershipActive ? 'status-active' : 'status-inactive' ?>" 
                                      title="<?= $isMembershipActive ? 'Dominio Activo' : 'Dominio Inactivo por falta de pago' ?>"></span>
                                <?= htmlspecialchars($domain['domain']) ?>
                            </div>
                            <span class="domain-date">Agregado: <?= date('d/m/Y', strtotime($domain['created_at'])) ?></span>
                            <?php if (!$isMembershipActive): ?>
                                <span style="font-size: 0.8em; color: var(--accent-red); margin-left: 10px; font-weight: bold;">(Suspendido)</span>
                            <?php endif; ?>
                        </div>
                        <button onclick="deleteDomain(<?= $domain['id'] ?>)" class="btn-icon danger" style="background:transparent; border:none; color:var(--text-secondary); cursor:pointer;" title="Eliminar">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                            </svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" style="opacity:0.3; margin-bottom:10px;">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                        </svg>
                        <p>No tienes dominios configurados.</p>
                        <p style="font-size: 0.9em;">Tus videos podrían no reproducirse si habilitas la protección global sin agregar dominios aquí.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        function addDomain(e) {
            e.preventDefault();
            const input = document.getElementById('newDomain');
            const btn = document.getElementById('btnAdd');
            const domain = input.value.trim();
            
            if(!domain) return;
            
            btn.disabled = true;
            btn.innerHTML = 'Guardando...';
            
            fetch('/api/domains.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'add', domain: domain })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    showNotification('Dominio agregado correctamente');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(data.message, 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Agregar';
                }
            })
            .catch(() => {
                showNotification('Error de conexión', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Agregar';
            });
        }
        
        function deleteDomain(id) {
            if(!confirm('¿Eliminar este dominio de la lista permitida?')) return;
            
            fetch('/api/domains.php', {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'delete', id: id })
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    const row = document.getElementById('domain-row-' + id);
                    row.style.opacity = '0.2';
                    showNotification('Dominio eliminado');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }
    </script>
</body>
</html>
