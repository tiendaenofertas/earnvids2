<?php
// api/ipn.php - Instant Payment Notification de NowPayments
// Este archivo recibe las confirmaciones de pago en segundo plano

require_once '../config/app.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 1. Obtener Configuración de Seguridad
$stmt = db()->query("SELECT ipn_secret, is_active FROM payment_settings WHERE id = 1");
$config = $stmt->fetch();

// Si la pasarela está desactivada o no configurada, rechazamos
if (!$config || !$config['is_active'] || empty($config['ipn_secret'])) {
    http_response_code(503); 
    die('Payment gateway disabled or not configured');
}

$ipnSecret = $config['ipn_secret'];

// 2. Leer y Validar la Notificación
$error_msg = "Unknown error";
$auth_ok = false;
$request_data = null;

// NowPayments envía la firma en este header
if (isset($_SERVER['HTTP_X_NOWPAYMENTS_SIG']) && !empty($_SERVER['HTTP_X_NOWPAYMENTS_SIG'])) {
    $recived_sig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'];
    $request_data = json_decode(file_get_contents('php://input'), true);

    if (!empty($request_data)) {
        // Ordenar parámetros alfabéticamente para la validación
        ksort($request_data);
        $sorted_request_json = json_encode($request_data, JSON_UNESCAPED_SLASHES);
        
        if ($request_data !== []) {
            // Calcular firma esperada
            $calc_sig = hash_hmac('sha512', $sorted_request_json, $ipnSecret);
            
            if ($recived_sig === $calc_sig) {
                $auth_ok = true;
            } else {
                $error_msg = "HMAC signature does not match";
            }
        } else {
             $error_msg = "Error decoding the JSON";
        }
    } else {
        $error_msg = "No request data received";
    }
} else {
    $error_msg = "No signature header received";
}

// Si la validación falla, registrar el intento y detener
if (!$auth_ok) {
    // Loguear solo si parece un intento real (con datos)
    if ($request_data) {
        logActivity('ipn_failed', ['msg' => $error_msg, 'ip' => $_SERVER['REMOTE_ADDR']]);
    }
    http_response_code(403);
    die($error_msg);
}

// 3. Procesar el Pago Validado
$payment_id = $request_data['payment_id']; // ID de NowPayments
$payment_status = $request_data['payment_status']; // waiting, confirming, confirmed, etc.
$order_id = $request_data['order_id'] ?? null; // ID interno nuestro (si se envió)

// Buscar el pago en nuestra base de datos
// Buscamos primero por ID de transacción externa
$stmt = db()->prepare("SELECT * FROM payments WHERE payment_id = ? LIMIT 1");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

// Si no se encuentra por payment_id, intentamos por order_id (fallback)
if (!$payment && $order_id) {
    $stmt = db()->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch();
    
    // Si lo encontramos así, vinculamos el ID externo para futuras referencias
    if ($payment && empty($payment['payment_id'])) {
         db()->prepare("UPDATE payments SET payment_id = ? WHERE id = ?")->execute([$payment_id, $payment['id']]);
    }
}

if (!$payment) {
    // Si el pago no existe en nuestra BD, no podemos procesarlo (quizás se creó manual en el panel de NowPayments)
    die('Payment record not found in system');
}

// Verificar estado anterior para evitar duplicidad (Idempotencia)
$oldStatus = $payment['status'];

// Actualizar estado del pago en BD
$stmt = db()->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$payment_status, $payment['id']]);

// 4. Activar/Extender Membresía (Solo si pasa a confirmado y no estaba ya confirmado)
if (($payment_status === 'confirmed' || $payment_status === 'finished') && 
    ($oldStatus !== 'confirmed' && $oldStatus !== 'finished')) {
    
    // Obtener detalles del plan comprado
    $stmt = db()->prepare("SELECT duration_days FROM plans WHERE id = ?");
    $stmt->execute([$payment['plan_id']]);
    $plan = $stmt->fetch();
    
    if ($plan) {
        $userId = $payment['user_id'];
        $days = (int)$plan['duration_days'];
        
        // Obtener fecha de expiración actual del usuario
        $stmt = db()->prepare("SELECT membership_expiry FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $currentExpiry = $user['membership_expiry'];
        $newExpiry = '';
        
        // Lógica de fechas:
        if ($currentExpiry && strtotime($currentExpiry) > time()) {
            // El usuario ya tiene tiempo activo: Sumamos los días al final
            $newExpiry = date('Y-m-d H:i:s', strtotime($currentExpiry . " + $days days"));
        } else {
            // El usuario no tiene tiempo o ya venció: Empieza desde ahora
            $newExpiry = date('Y-m-d H:i:s', strtotime("now + $days days"));
        }
        
        // Guardar nueva fecha en usuario
        $stmt = db()->prepare("UPDATE users SET membership_expiry = ? WHERE id = ?");
        $stmt->execute([$newExpiry, $userId]);
        
        // Registrar actividad
        logActivity('membership_purchase', [
            'user_id' => $userId, 
            'plan_id' => $payment['plan_id'], 
            'days_added' => $days,
            'payment_id' => $payment_id
        ]);
    }
}

echo "OK"; // Respuesta exitosa a NowPayments
?>