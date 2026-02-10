<?php
require_once '../config/app.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$stats = isAdmin() ? getGlobalStats() : getUserStats($_SESSION['user_id']);

// Obtener top videos
$userId = isAdmin() ? null : $_SESSION['user_id'];
$topVideosQuery = "
    SELECT title, views, 
           ROUND((views / (SELECT SUM(views) FROM videos WHERE status = 'active' " . 
           ($userId ? "AND user_id = ?" : "") . ")) * 100, 2) as percentage
    FROM videos 
    WHERE status = 'active' " . ($userId ? "AND user_id = ?" : "") . "
    ORDER BY views DESC 
    LIMIT 5
";

$stmt = db()->prepare($topVideosQuery);
if ($userId) {
    $stmt->execute([$userId, $userId]);
} else {
    $stmt->execute();
}
$topVideos = $stmt->fetchAll();

// Datos del gráfico (últimos 7 días)
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = db()->prepare("
        SELECT COALESCE(SUM(s.views), 0) as views
        FROM statistics s
        JOIN videos v ON s.video_id = v.id
        WHERE s.date = ? " . ($userId ? "AND v.user_id = ?" : "")
    );
    
    if ($userId) {
        $stmt->execute([$date, $userId]);
    } else {
        $stmt->execute([$date]);
    }
    
    $chartData[] = [
        'date' => $date,
        'views' => $stmt->fetchColumn()
    ];
}

echo json_encode([
    'success' => true,
    'stats' => $stats,
    'topVideos' => $topVideos,
    'chartData' => $chartData
]);