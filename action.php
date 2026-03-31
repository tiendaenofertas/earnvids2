<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

try {
    $configFile = __DIR__ . '/config/config.php';
    if (!file_exists($configFile)) throw new Exception("Error crítico de configuración.");
    require_once $configFile;

    if (session_status() === PHP_SESSION_NONE) session_start();
    $is_authorized = isset($_SESSION['login']) || (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true);
    if (!$is_authorized) throw new Exception("Sesión expirada.");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método no permitido.");

    // ==========================================
    // 🎨 GUARDAR PERSONALIZACIÓN (SIN VAST)
    // ==========================================
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'save_settings') {
        $settings = [
            'brand_name' => strip_tags($_POST['brand_name'] ?? 'XZORRA'),
            'brand_color' => strip_tags($_POST['brand_color'] ?? '#ff0000'),
            'play_color' => strip_tags($_POST['play_color'] ?? '#e50914')
        ];
        $file = __DIR__ . '/config/player_settings.json';
        if (file_put_contents($file, json_encode($settings)) !== false) {
            ob_end_clean(); header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success' => true]); exit;
        } else {
            throw new Exception("Error de permisos en /config/.");
        }
    }

    $licenseKey = file_exists(LICENSE_FILE) ? trim(file_get_contents(LICENSE_FILE)) : '';
    if (empty($licenseKey)) throw new Exception("Licencia no configurada.");

    if (!isset($_SESSION['license_last_check']) || time() - $_SESSION['license_last_check'] > 1800) {
        $verify = checkLicenseStatus($licenseKey);
        if ($verify['status'] !== 'success') {
            @unlink(LICENSE_FILE); throw new Exception("Licencia bloqueada: " . $verify['message']);
        }
        $_SESSION['license_last_check'] = time();
    }

    $link = sanitizeInput($_POST['link'] ?? '');
    if (strpos($link, '/embed.php?v=') !== false) {
        $link = str_replace('/embed.php?v=', '/s3-proxy.php?v=', $link);
    }
    if (empty($link) || strpos($link, 'http') !== 0) throw new Exception("URL de video inválida.");

    $poster = sanitizeInput($_POST['poster'] ?? '');
    $subtitles = [];
    $subs = $_POST['sub'] ?? [];
    $labels = $_POST['label'] ?? [];

    if (is_array($subs) && is_array($labels)) {
        foreach ($subs as $key => $url) {
            $url = trim((string)$url);
            if (empty($url)) continue;
            $label = sanitizeInput($labels[$key] ?? "Idioma " . ($key + 1));
            $subtitles[$label] = sanitizeInput($url);
        }
    }

    $videoData = [
        'link' => $link,
        'poster' => $poster,
        'sub' => $subtitles,
        'created_at' => time()
    ];

    // 🌟 GENERADOR DE ENLACE CORTO Y CARPETAS
    $shortId = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    $subDir = substr($shortId, 0, 2); 
    $linksDir = __DIR__ . '/config/links/' . $subDir . '/';
    
    if (!is_dir($linksDir)) {
        if (!@mkdir($linksDir, 0775, true)) throw new Exception("Error al crear carpeta. Revisa permisos.");
    }
    
    if (file_put_contents($linksDir . $shortId . '.json', json_encode($videoData)) === false) {
        throw new Exception("Error al guardar enlace. Revisa los permisos.");
    }

    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($shortId);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
