<?php
// api/domains.php - Backend para gestión de dominios permitidos
require_once '../config/app.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// 1. Verificar Sesión
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    // --- ACCIÓN: AGREGAR DOMINIO ---
    if ($action === 'add') {
        $rawDomain = trim($input['domain'] ?? '');
        
        if (empty($rawDomain)) {
            throw new Exception('El dominio no puede estar vacío.');
        }

        // Limpieza agresiva para guardar solo el host (ej: "misitio.com")
        // Paso 1: Si no tiene protocolo, agregar http:// temporalmente para que parse_url funcione
        if (!preg_match("~^https?://~i", $rawDomain)) {
            $rawDomain = "http://" . $rawDomain;
        }
        
        // Paso 2: Extraer host
        $parsed = parse_url($rawDomain);
        $domain = isset($parsed['host']) ? strtolower($parsed['host']) : '';
        
        // Paso 3: Eliminar 'www.' opcional si se desea (opcional, pero recomendado para consistencia)
        // $domain = preg_replace('/^www\./', '', $domain);

        if (empty($domain) || !strpos($domain, '.')) {
            throw new Exception('Formato de dominio inválido. Usa formato: ejemplo.com');
        }

        // Validar Límite del Usuario
        // 1. Obtener límite asignado
        $stmt = db()->prepare("SELECT domain_limit FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $limitRow = $stmt->fetch();
        $limit = ($limitRow && $limitRow['domain_limit'] !== null) ? intval($limitRow['domain_limit']) : 3;

        // 2. Contar dominios actuales
        $stmt = db()->prepare("SELECT COUNT(*) FROM user_domains WHERE user_id = ?");
        $stmt->execute([$userId]);
        $currentCount = $stmt->fetchColumn();

        if ($currentCount >= $limit) {
            throw new Exception("Has alcanzado tu límite de $limit dominios permitidos.");
        }

        // Insertar (Ignorar si ya existe gracias a UNIQUE KEY en DB)
        // Usamos INSERT IGNORE o ON DUPLICATE KEY UPDATE para no fallar feo si ya existe
        $stmt = db()->prepare("INSERT INTO user_domains (user_id, domain) VALUES (?, ?)");
        try {
            $stmt->execute([$userId, $domain]);
            
            // Log de actividad
            logActivity('add_domain', ['domain' => $domain]);
            
            echo json_encode(['success' => true, 'message' => 'Dominio agregado exitosamente', 'domain' => $domain]);
        } catch (PDOException $e) {
            // Error 1062 es Duplicate entry
            if ($e->getCode() == 23000) { 
                throw new Exception('Este dominio ya está en tu lista.');
            } else {
                throw $e;
            }
        }
    }

    // --- ACCIÓN: ELIMINAR DOMINIO ---
    elseif ($action === 'delete') {
        $id = intval($input['id'] ?? 0);
        
        if ($id <= 0) throw new Exception('ID inválido');

        // Borrar asegurando que pertenezca al usuario actual
        $stmt = db()->prepare("DELETE FROM user_domains WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        if ($stmt->rowCount() > 0) {
            logActivity('delete_domain', ['domain_id' => $id]);
            echo json_encode(['success' => true, 'message' => 'Dominio eliminado']);
        } else {
            throw new Exception('No se pudo eliminar (o no existe)');
        }
    }

    else {
        throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
