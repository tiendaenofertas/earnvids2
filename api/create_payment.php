<?php
// api/create_payment.php - Generar pago con NowPayments (Corregido Error 1364)
require_once '../config/app.php';
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// 1. Verificar Sesión
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 2. Obtener datos de entrada
$input = json_decode(file_get_contents('php://input'), true);
$planId = isset($input['plan_id']) ? intval($input['plan_id']) : 0;

if ($planId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Plan inválido.']);
    exit;
}

try {
    // 3. Obtener configuración de Pagos
    $stmt = db()->query("SELECT api_key, is_active FROM payment_settings WHERE id = 1");
    $config = $stmt->fetch();

    if (!$config || !$config['is_active'] || empty($config['api_key'])) {
        throw new Exception("La pasarela de pagos no está configurada o activa.");
    }

    // 4. Obtener datos del Plan
    $stmt = db()->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();

    if (!$plan) {
        throw new Exception("El plan seleccionado no existe o está inactivo.");
    }

    // 5. Crear registro de pago "Pendiente" en nuestra BD
    // --- CORRECCIÓN ERROR 1364 ---
    // La base de datos no permite 'payment_id' vacío. Insertamos un valor temporal único
    // que luego actualizaremos con el ID real que nos devuelva NowPayments.
    $tempPaymentId = 'TEMP_' . time() . '_' . uniqid();
    
    $stmt = db()->prepare("INSERT INTO payments (user_id, plan_id, amount, currency, status, payment_id, created_at) VALUES (?, ?, ?, 'USD', 'waiting', ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $plan['id'], $plan['price'], $tempPaymentId]);
    $internalOrderId = db()->lastInsertId();

    // 6. Preparar petición a NowPayments
    $apiUrl = 'https://api.nowpayments.io/v1/invoice';
    $apiKey = $config['api_key'];

    $data = [
        'price_amount' => (float)$plan['price'],
        'price_currency' => 'usd', // Moneda base del plan
        'pay_currency' => 'btc',   // Moneda cripto por defecto (el usuario puede cambiarla)
        'order_id' => (string)$internalOrderId, // Nuestro ID interno
        'order_description' => 'Membresía: ' . $plan['name'],
        'ipn_callback_url' => SITE_URL . '/api/ipn.php',
        'success_url' => SITE_URL . '/account.php?payment=success',
        'cancel_url' => SITE_URL . '/account.php?payment=cancel'
    ];

    // 7. Enviar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Error de conexión con pasarela: " . $curlError);
    }

    $result = json_decode($response, true);

    // 8. Verificar respuesta
    if ($httpCode >= 200 && $httpCode < 300 && isset($result['invoice_url'])) {
        
        // --- ACTUALIZACIÓN CRÍTICA ---
        // Ahora sí tenemos el ID real de NowPayments, actualizamos la base de datos
        $npId = $result['id']; 
        $stmt = db()->prepare("UPDATE payments SET payment_id = ? WHERE id = ?");
        $stmt->execute([$npId, $internalOrderId]);

        echo json_encode([
            'success' => true,
            'redirect_url' => $result['invoice_url']
        ]);

    } else {
        // Error de la API (ej: API Key inválida)
        $msg = isset($result['message']) ? $result['message'] : 'Error desconocido de la pasarela.';
        
        // Si falló, borramos el registro temporal para mantener la BD limpia
        db()->prepare("DELETE FROM payments WHERE id = ?")->execute([$internalOrderId]);
        
        throw new Exception("NowPayments Error ($httpCode): " . $msg);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
