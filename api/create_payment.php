<?php
// api/create_payment.php - Motor Principal de Pagos Multi-Pasarela
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
$gateway = isset($input['gateway']) ? strtolower(trim($input['gateway'])) : 'nowpayments'; // nowpayments por defecto

// Validar pasarelas permitidas
$allowedGateways = ['nowpayments', 'paypal', 'bold'];
if (!in_array($gateway, $allowedGateways)) {
    echo json_encode(['success' => false, 'message' => 'Pasarela de pago no válida o alterada.']);
    exit;
}

if ($planId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Plan inválido.']);
    exit;
}

try {
    // 3. Obtener configuración de Pagos
    $stmt = db()->query("SELECT * FROM payment_settings WHERE id = 1");
    $config = $stmt->fetch();

    if (!$config) {
        throw new Exception("El sistema de pagos no ha sido configurado.");
    }

    // 4. Validar que la pasarela elegida esté activa
    if ($gateway === 'nowpayments' && (!$config['is_active'] || empty($config['api_key']))) {
        throw new Exception("La pasarela NowPayments no está activa en este momento.");
    }
    if ($gateway === 'paypal' && (empty($config['paypal_active']) || empty($config['paypal_client_id']))) {
        throw new Exception("La pasarela PayPal no está activa en este momento.");
    }
    if ($gateway === 'bold' && (empty($config['bold_active']) || empty($config['bold_api_key']))) {
        throw new Exception("La pasarela Bold no está activa en este momento.");
    }

    // 5. Obtener datos del Plan
    $stmt = db()->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();

    if (!$plan) {
        throw new Exception("El plan seleccionado no existe o está inactivo.");
    }

    // 6. Crear registro de pago "Pendiente" en nuestra BD
    // Insertamos un valor temporal único en payment_id que luego actualizaremos con el ID real de la pasarela.
    $tempPaymentId = 'TEMP_' . time() . '_' . bin2hex(random_bytes(4));
    
    $stmt = db()->prepare("INSERT INTO payments (user_id, plan_id, amount, currency, status, payment_id, gateway, created_at) VALUES (?, ?, ?, 'USD', 'waiting', ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $plan['id'], $plan['price'], $tempPaymentId, $gateway]);
    $internalOrderId = db()->lastInsertId();

    // ==========================================
    // 🚀 ENRUTADOR DE PASARELAS (GATEWAY ROUTER)
    // ==========================================

    if ($gateway === 'nowpayments') {
        // --- Lógica NOWPAYMENTS ---
        $apiUrl = 'https://api.nowpayments.io/v1/invoice';
        $data = [
            'price_amount' => (float)$plan['price'],
            'price_currency' => 'usd',
            'pay_currency' => 'btc',
            'order_id' => (string)$internalOrderId,
            'order_description' => 'Membresía: ' . $plan['name'],
            'ipn_callback_url' => SITE_URL . '/api/ipn.php',
            'success_url' => SITE_URL . '/account.php?payment=success',
            'cancel_url' => SITE_URL . '/account.php?payment=cancel'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-api-key: ' . $config['api_key'],
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($result['invoice_url'])) {
            $stmt = db()->prepare("UPDATE payments SET payment_id = ? WHERE id = ?");
            $stmt->execute([$result['id'], $internalOrderId]);

            echo json_encode(['success' => true, 'redirect_url' => $result['invoice_url']]);
        } else {
            db()->prepare("DELETE FROM payments WHERE id = ?")->execute([$internalOrderId]);
            throw new Exception("Error de NowPayments: " . ($result['message'] ?? 'Desconocido'));
        }

    } elseif ($gateway === 'paypal') {
        // --- Lógica PAYPAL (REST API v2) ---
        $clientId = $config['paypal_client_id'];
        $secret = $config['paypal_secret'];
        
        // Entorno: Cambiar a api-m.paypal.com para PRODUCCIÓN. Usamos sandbox por defecto para seguridad inicial.
        $paypalBaseUrl = 'https://api-m.paypal.com'; // O https://api-m.sandbox.paypal.com

        // A. Obtener Access Token
        $ch = curl_init("$paypalBaseUrl/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: es_ES']);
        $tokenRes = curl_exec($ch);
        curl_close($ch);
        $tokenData = json_decode($tokenRes, true);

        if (!isset($tokenData['access_token'])) {
            db()->prepare("DELETE FROM payments WHERE id = ?")->execute([$internalOrderId]);
            throw new Exception("Error de autenticación con PayPal. Revisa las credenciales.");
        }

        // B. Crear Orden
        $orderData = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => (string)$internalOrderId,
                "amount" => [
                    "currency_code" => "USD",
                    "value" => number_format($plan['price'], 2, '.', '')
                ],
                "description" => "Membresía: " . $plan['name']
            ]],
            "application_context" => [
                "brand_name" => SITE_NAME,
                "return_url" => SITE_URL . '/api/paypal_capture.php?order_id=' . $internalOrderId, // Archivo que capturará el pago
                "cancel_url" => SITE_URL . '/account.php?payment=cancel'
            ]
        ];

        $ch2 = curl_init("$paypalBaseUrl/v2/checkout/orders");
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($orderData));
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $tokenData['access_token']
        ]);
        $orderRes = curl_exec($ch2);
        curl_close($ch2);
        $orderResult = json_decode($orderRes, true);

        if (isset($orderResult['id'])) {
            $stmt = db()->prepare("UPDATE payments SET payment_id = ? WHERE id = ?");
            $stmt->execute([$orderResult['id'], $internalOrderId]);

            // Buscar link de aprobación
            $approveUrl = '';
            foreach ($orderResult['links'] as $link) {
                if ($link['rel'] === 'approve') $approveUrl = $link['href'];
            }
            echo json_encode(['success' => true, 'redirect_url' => $approveUrl]);
        } else {
            db()->prepare("DELETE FROM payments WHERE id = ?")->execute([$internalOrderId]);
            throw new Exception("Error al crear la orden en PayPal.");
        }

    } elseif ($gateway === 'bold') {
        // --- Lógica BOLD ---
        // Bold usa un Web Widget y requiere generar un "Integrity Signature" en PHP antes de mostrar la pasarela.
        // Por seguridad, redirigimos a una página local específica que se encargará de renderizar el Widget.
        $redirectUrl = SITE_URL . '/checkout_bold.php?order_id=' . $internalOrderId;
        
        echo json_encode(['success' => true, 'redirect_url' => $redirectUrl]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
