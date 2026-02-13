<?php
// admin/payments.php - Historial de Pagos y Transacciones
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';
requireAdmin();

// Configuración de Paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Obtener Total de Pagos
$countStmt = db()->query("SELECT COUNT(*) FROM payments");
$totalPayments = $countStmt->fetchColumn();
$totalPages = ceil($totalPayments / $perPage);

// Obtener Pagos con detalles de Usuario y Plan
$stmt = db()->prepare("
    SELECT p.*, u.username, u.email, pl.name as plan_name
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN plans pl ON p.plan_id = pl.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
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
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .status-waiting { background: rgba(255, 193, 7, 0.15); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.3); }
        .status-confirming { background: rgba(23, 162, 184, 0.15); color: #17a2b8; border: 1px solid rgba(23, 162, 184, 0.3); }
        .status-confirmed { background: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.3); } /* Verde NowPayments */
        .status-sending { background: rgba(0, 123, 255, 0.15); color: #007bff; border: 1px solid rgba(0, 123, 255, 0.3); }
        .status-partially_paid { background: rgba(253, 126, 20, 0.15); color: #fd7e14; border: 1px solid rgba(253, 126, 20, 0.3); }
        .status-finished { background: rgba(40, 167, 69, 0.15); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.3); } /* Estado final exitoso */
        .status-failed { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .status-refunded { background: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); }
        .status-expired { background: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.3); }

        .transaction-id { font-family: monospace; font-size: 0.9em; color: var(--accent-green); }
        
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-secondary); }
        .empty-state svg { width: 60px; height: 60px; margin-bottom: 20px; opacity: 0.3; }

        .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 10px; }
        .page-link { padding: 8px 12px; background: var(--bg-secondary); border-radius: 4px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
        .page-link:hover, .page-link.active { background: var(--accent-green); color: #000; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Historial de Pagos</h1>
            <div style="color: var(--text-secondary);">Total: <?= number_format($totalPayments) ?> transacciones</div>
        </div>
        
        <?php if (empty($payments)): ?>
            <div class="payments-table">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                    </svg>
                    <h3>No hay pagos registrados aún</h3>
                    <p>Las transacciones aparecerán aquí cuando los usuarios compren membresías.</p>
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
                    <a href="?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>