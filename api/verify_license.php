<?php
// api/verify_license.php - Servidor Central de Validación de Licencias
require_once '../config/app.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite que los scripts externos consulten
header('Access-Control-Allow-Methods: POST');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$license = trim($input['license_key'] ?? '');
$domain = trim($input['domain'] ?? '');

if (empty($license) || empty($domain)) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos de validación (Licencia o Dominio).']);
    exit;
}

try {
    // 1. Buscar la licencia en la base de datos
    $stmt = db()->prepare("SELECT id, license_status FROM users WHERE license_key = ? LIMIT 1");
    $stmt->execute([$license]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Licencia inválida o no registrada.']);
        exit;
    }

    // 2. Verificar el estado de la licencia
    if ($user['license_status'] === 'suspended') {
        echo json_encode(['status' => 'error', 'message' => 'Licencia suspendida temporalmente. Contacte a soporte.']);
        exit;
    }
    
    if ($user['license_status'] === 'inactive') {
        echo json_encode(['status' => 'error', 'message' => 'Licencia desactivada/eliminada por el administrador.']);
        exit;
    }

    // 3. Verificar que el dominio esté en la lista blanca del usuario
    // Limpiamos el dominio que envía el script (quitamos http, https, www)
    $cleanDomain = strtolower(preg_replace('/^www\./', '', parse_url((strpos($domain, 'http') === false ? 'http://' : '') . $domain, PHP_URL_HOST) ?? $domain));
    
    $stmt = db()->prepare("SELECT domain FROM user_domains WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $userDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $domainMatch = false;
    foreach ($userDomains as $d) {
        $dbDomain = strtolower(trim($d));
        if ($cleanDomain === $dbDomain || substr($cleanDomain, -strlen(".".$dbDomain)) === ".".$dbDomain) {
            $domainMatch = true;
            break;
        }
    }

    if (!$domainMatch) {
        echo json_encode(['status' => 'error', 'message' => "El dominio ($cleanDomain) no está autorizado para esta licencia. Añádelo en tu panel de XVIDSPRO."]);
        exit;
    }

    // Si pasa todas las pruebas, la licencia es válida
    echo json_encode(['status' => 'success', 'message' => 'Licencia verificada correctamente.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error en el servidor central de licencias.']);
}
?>