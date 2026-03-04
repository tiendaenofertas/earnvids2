<?php
// cron/update_stats.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Contar datos reales (esto solo se hace una vez por hora, no afecta al usuario)
$users = db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$videos = db()->query("SELECT COUNT(*) FROM videos WHERE status = 'active'")->fetchColumn();
$views = db()->query("SELECT SUM(views) FROM videos")->fetchColumn();

// Guardar en la tabla de caché
$stmt = db()->prepare("UPDATE system_stats SET stat_value = ? WHERE stat_name = ?");
$stmt->execute([$users, 'total_users_active']);
$stmt->execute([$videos, 'total_videos_active']);
$stmt->execute([$views, 'total_views_global']);

echo "Estadísticas actualizadas con éxito.";