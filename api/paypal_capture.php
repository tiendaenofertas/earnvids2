<?php
// api/paypal_capture.php - Verificación y Captura Segura de Pagos PayPal
require_once '../config/app.php';
require_once '../includes/db_connect.php';

// Validar que PayPal haya enviado los parámetros necesarios
if (!isset($_GET['token']) || !isset($_GET['order_id'])) {
    header("Location: /account.php?payment=error");
    exit;
}

$paypalToken = $_GET['token']; // ID de la orden en PayPal
$internalOrderId = intval($_GET['order_id']); // Nuestro ID interno

try {
    // 1. Obtener credenciales de PayPal desde la BD
    $stmt = db()->query("SELECT paypal_client_id, paypal_secret FROM payment_settings WHERE id = 1");
    $config = $stmt->fetch();

    if (empty($config['paypal_client_id']) || empty($config['paypal_secret'])) {
        throw new Exception("PayPal no está configurado correctamente en el servidor.");
    }

    // Nota: Para pasar a producción, cambia 'api-m.sandbox.paypal.com' por 'api-m.paypal.com'
    $paypalBaseUrl = 'https://api-m.paypal.com'; 

    // 2. Autenticación: Obtener Token de Acceso de PayPal
    $ch = curl_init("$paypalBaseUrl/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $config['paypal_client_id'] . ":" . $config['paypal_secret']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: es_ES']);
    $tokenRes = curl_exec($ch);
    $httpCodeAuth = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $tokenData = json_decode($tokenRes, true);

    if ($httpCodeAuth !== 200 || !isset($tokenData['access_token'])) {
        throw new Exception("Fallo en la autenticación con PayPal.");
    }

    // 3. Capturar el Pago (Ejecutar la transacción)
    $ch2 = curl_init("$paypalBaseUrl/v2/checkout/orders/$paypalToken/capture");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $tokenData['access_token']
    ]);
    $captureRes = curl_exec($ch2);
    $httpCodeCapture = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    $captureData = json_decode($captureRes, true);

    // 4. Procesar el Resultado
    // PayPal devuelve 'COMPLETED' si el dinero fue transferido con éxito
    if ($httpCodeCapture >= 200 && $httpCodeCapture < 300 && isset($captureData['status']) && $captureData['status'] === 'COMPLETED') {
        
        db()->beginTransaction();
        
        // A. Actualizar estado del pago a completado
        $stmt = db()->prepare("UPDATE payments SET status = 'completed' WHERE id = ? AND status = 'waiting'");
        $stmt->execute([$internalOrderId]);
        
        if ($stmt->rowCount() > 0) { // Solo si realmente se actualizó (evita doble proceso)
            
            // B. Obtener datos del usuario y del plan
            $stmt = db()->prepare("SELECT user_id, plan_id FROM payments WHERE id = ?");
            $stmt->execute([$internalOrderId]);
            $paymentInfo = $stmt->fetch();
            
            $stmt = db()->prepare("SELECT duration_days FROM plans WHERE id = ?");
            $stmt->execute([$paymentInfo['plan_id']]);
            $planInfo = $stmt->fetch();
            
            $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
            $stmt->execute([$paymentInfo['user_id']]);
            $user = $stmt->fetch();
            
            // C. Calcular nueva fecha de expiración
            $currentExpiry = $user['membership_expiry'] ? strtotime($user['membership_expiry']) : time();
            $baseTime = max($currentExpiry, time());
            $newExpiryDate = date('Y-m-d H:i:s', strtotime('+' . $planInfo['duration_days'] . ' days', $baseTime));
            
            // D. Actualizar al usuario
            $stmt = db()->prepare("UPDATE users SET membership_expiry = ? WHERE id = ?");
            $stmt->execute([$newExpiryDate, $paymentInfo['user_id']]);
        }
        
        db()->commit();
        
        // Redirigir con éxito
        header("Location: /account.php?payment=success");
        exit;
        
    } else {
        // Si el pago no fue procesado o fue declinado por el banco
        $stmt = db()->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
        $stmt->execute([$internalOrderId]);
        
        header("Location: /account.php?payment=cancel");
        exit;
    }

} catch (Exception $e) {
    error_log("Error en captura PayPal: " . $e->getMessage());
    header("Location: /account.php?payment=error");
    exit;
}
?>