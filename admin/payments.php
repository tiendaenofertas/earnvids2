<?php
// admin/payments.php - Historial de Pagos con Filtros y Limpieza
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';
requireAdmin();

$message = '';
$messageType = '';

// --- LÓGICA DE LIMPIEZA (CLEANUP) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup_waiting') {
    try {
        // Eliminar pagos en 'waiting' que tengan más de 48 horas (evita borrar pagos en curso)
        $stmt = db()->prepare("DELETE FROM payments WHERE status = 'waiting' AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        
        if ($deletedCount > 0) {
            $message = "Se eliminaron $deletedCount registros de pagos antiguos sin finalizar.";
            $messageType = "success";
        } else {
            $message = "No se encontraron pagos antiguos para limpiar.";
            $messageType = "info";
        }
    } catch (Exception $e) {
        $message = "Error al limpiar registros: " . $e->getMessage();
        $messageType = "error";
    }
}

// --- CONFIGURACIÓN DE FILTROS ---
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$allowedStatuses = ['all', 'waiting', 'confirmed', 'finished', 'failed', 'refunded', 'expired'];
if (!in_array($statusFilter, $allowedStatuses)) $statusFilter = 'all';

// Construcción de la consulta base
$whereClause = "";
$params = [];

if ($statusFilter !== 'all') {
    $whereClause = "WHERE p.status = ?";
    $params[] = $statusFilter;
}

// --- PAGINACIÓN ---
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Obtener Total de Pagos (Filtrados)
$countSql = "SELECT COUNT(*) FROM payments p $whereClause";
$countStmt = db()->prepare($countSql);
$countStmt->execute($params);
$totalPayments = $countStmt->fetchColumn();
$totalPages = ceil($totalPayments / $perPage);

// Obtener Pagos (Filtrados y Paginados)
$sql = "
    SELECT p.*, u.username, u.email, pl.name as plan_name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN plans pl ON p.plan_id = pl.id
    $whereClause
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .payments-table { background-color: var(--bg-card); border-radius: 12px; overflow: hidden; }
        .payments-table table { width: 100%; border-collapse: collapse; }
        .payments-table th { background-color: var(--bg-secondary); padding: 15px; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: var(--text-secondary); }
        .payments-table td { padding: 15px; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
        
        /* Badges de Estado */
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-waiting { background: rgba(255, 193, 7, 0.15); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .status-confirming { background: rgba(23, 162, 184, 0.15); color: #17a2b8; border: 1px solid rgba(23, 162, 184, 0.3); }
        .status-confirmed, .status-finished { background: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.3); }
        .status-sending { background: rgba(0, 123, 255, 0.15); color: #007bff; border: 1px solid rgba(0, 123, 255, 0.3); }
        .status-partially_paid { background: rgba(253, 126, 20, 0.15); color: #fd7e14; border: 1px solid rgba(253, 126, 20, 0.3); }
        .status-failed { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .status-refunded, .status-expired { background: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); }

        .transaction-id { font-family: monospace; font-size: 0.9em; color: var(--accent-green); }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state svg { width: 60px; height: 60px; margin-bottom: 20px; opacity: 0.3; }

        .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 10px; }
        .page-link { padding: 8px 12px; background: var(--bg-secondary); border-radius: 4px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
        .page-link:hover, .page-link.active { background: var(--accent-green); color: #000; }
        
        /* Barra de Herramientas */
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: var(--bg-card); padding: 15px; border-radius: 12px; border: 1px solid var(--border-color); flex-wrap: wrap; gap: 15px; }
        .filter-group { display: flex; align-items: center; gap: 10px; }
        .filter-select { padding: 8px 12px; border-radius: 6px; background: var(--bg-primary); color: #fff; border: 1px solid var(--border-color); cursor: pointer; }
        
        .btn-clean { background: rgba(255, 59, 59, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 8px 15px; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 5px; text-decoration: none; transition: all 0.2s; }
        .btn-clean:hover { background: var(--accent-red); color: #fff; }
        
        .notification { padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .notification.success { background: rgba(0, 255, 136, 0.1); color: #00ff88; border: 1px solid rgba(0, 255, 136, 0.2); }
        .notification.error { background: rgba(255, 59, 59, 0.1); color: #ff3b3b; border: 1px solid rgba(255, 59, 59, 0.2); }
        .notification.info { background: rgba(0, 168, 255, 0.1); color: #00a8ff; border: 1px solid rgba(0, 168, 255, 0.2); }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Historial de Pagos</h1>
            <div style="color: var(--text-secondary);">
                Mostrando <?= count($payments) ?> de <?= number_format($totalPayments) ?> registros
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="notification <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="toolbar">
            <form method="GET" class="filter-group">
                <label for="status" style="color: var(--text-secondary); font-size: 14px;">Filtrar por:</label>
                <select name="status" id="status" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter == 'all' ? 'selected' : '' ?>>Todos los estados</option>
                    <option value="confirmed" <?= $statusFilter == 'confirmed' ? 'selected' : '' ?>>✅ Confirmados</option>
                    <option value="finished" <?= $statusFilter == 'finished' ? 'selected' : '' ?>>✅ Finalizados</option>
                    <option value="waiting" <?= $statusFilter == 'waiting' ? 'selected' : '' ?>>⏳ En Espera</option>
                    <option value="failed" <?= $statusFilter == 'failed' ? 'selected' : '' ?>>❌ Fallidos</option>
                    <option value="expired" <?= $statusFilter == 'expired' ? 'selected' : '' ?>>⏰ Expirados</option>
                </select>
            </form>
            
            <form method="POST" onsubmit="return confirm('¿Estás seguro? Se eliminarán los registros en estado \'Waiting\' que tengan más de 48 horas de antigüedad. Esta acción no se puede deshacer.');">
                <input type="hidden" name="action" value="cleanup_waiting">
                <button type="submit" class="btn-clean" title="Eliminar pagos pendientes antiguos">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    Limpiar Pendientes Antiguos
                </button>
            </form>
        </div>
        
        <?php if (empty($payments)): ?>
            <div class="payments-table">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                    </svg>
                    <h3>No se encontraron pagos</h3>
                    <p>No hay registros que coincidan con los filtros actuales.</p>
                    <?php if ($statusFilter !== 'all'): ?>
                        <a href="payments.php" style="color: var(--accent-green); margin-top: 10px; display: inline-block;">Ver todos</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="payments-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID Transacción</th>
                            <th>Usuario</th>
                            <th>Plan</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <span class="transaction-id"><?= htmlspecialchars($payment['payment_id']) ?></span>
                            </td>
                            <td>
                                <div><strong><?= htmlspecialchars($payment['username']) ?></strong></div>
                                <div style="font-size: 0.85em; color: var(--text-secondary);"><?= htmlspecialchars($payment['email']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($payment['plan_name'] ?? 'Plan Eliminado') ?></td>
                            <td>$<?= number_format($payment['amount'], 2) ?> <?= htmlspecialchars($payment['currency']) ?></td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($payment['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($payment['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>
