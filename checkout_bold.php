<?php
// checkout_bold.php - Pasarela Segura para Tarjetas y PSE (Bold.co)
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

requireLogin();

if (!isset($_GET['order_id'])) {
    header("Location: /account.php?payment=error");
    exit;
}

$internalOrderId = intval($_GET['order_id']);
$userId = $_SESSION['user_id'];

try {
    // 1. Obtener la orden de la base de datos (Garantiza que el precio no ha sido alterado)
    $stmt = db()->prepare("SELECT p.*, pl.name as plan_name FROM payments p JOIN plans pl ON p.plan_id = pl.id WHERE p.id = ? AND p.user_id = ? AND p.status = 'waiting'");
    $stmt->execute([$internalOrderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception("Orden no encontrada, expirada o ya pagada.");
    }

    // 2. Obtener las llaves de Bold
    $stmt = db()->query("SELECT bold_api_key, bold_secret FROM payment_settings WHERE id = 1");
    $config = $stmt->fetch();

    if (empty($config['bold_api_key']) || empty($config['bold_secret'])) {
        throw new Exception("La pasarela Bold no está configurada correctamente.");
    }

    // 3. Generar Firma de Integridad (SHA-256)
    // Bold requiere un hash exacto del formato: order_id + amount + currency + secret_key
    $currency = "COP"; // Bold usa COP por defecto
    
    // Si tu precio en BD es en USD, calculamos un aproximado a COP (Ejemplo: $1 USD = 4000 COP)
    $tasaDeCambio = 4000; 
    $montoTotalCOP = round($order['amount'] * $tasaDeCambio);

    // IMPORTANTE: Aseguramos limpiar espacios en blanco de la clave secreta con trim()
    $cadenaFirma = $internalOrderId . $montoTotalCOP . $currency . trim($config['bold_secret']);
    $firmaIntegridad = hash('sha256', $cadenaFirma);

    // URL de retorno exitoso
    $redirectionUrl = SITE_URL . "/account.php?payment=success";

} catch (Exception $e) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'><h2>Error de Checkout</h2><p>" . htmlspecialchars($e->getMessage()) . "</p><a href='/account.php'>Volver a mi cuenta</a></div>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar con Bold - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: var(--bg-body); margin: 0; }
        .checkout-box { background: var(--bg-card); padding: 40px; border-radius: 12px; border: 1px solid var(--border-color); text-align: center; max-width: 400px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .checkout-box h2 { color: var(--text-primary); margin-bottom: 10px; }
        .price { font-size: 2.5em; color: var(--accent-green); font-weight: bold; margin: 20px 0; }
        .details { color: var(--text-secondary); margin-bottom: 30px; font-size: 0.9em; line-height: 1.5; }
        .loader { border: 4px solid rgba(255,255,255,0.1); border-left-color: var(--accent-green); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .btn-cancel { display: inline-block; margin-top: 20px; color: #ff3b3b; text-decoration: none; font-size: 0.9em; }
        .btn-cancel:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="checkout-box">
        <h2>Completar Pago</h2>
        <p style="color: #aaa;">Membresía: <strong><?= htmlspecialchars($order['plan_name']) ?></strong></p>
        
        <div class="price">$<?= number_format($order['amount'], 2) ?> USD</div>
        <div class="details">
            Equivalente aproximado: $<?= number_format($montoTotalCOP, 0) ?> COP<br>
            Pago seguro procesado por <strong>Bold.co</strong>
        </div>

        <div id="bold-btn-container" style="margin-bottom: 20px;">
            <div class="loader" id="bold-loader"></div>
            <p id="bold-wait-text" style="color: var(--accent-green); font-size: 0.9em;">Cargando pasarela segura...</p>
        </div>

        <a href="/account.php?payment=cancel" class="btn-cancel">Cancelar y volver</a>
    </div>

    <script type="text/javascript" src="https://checkout.bold.co/library/boldPaymentButton.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            try {
                if (typeof BoldCheckout === 'undefined') {
                    throw new Error("El script de Bold fue bloqueado por tu navegador o AdBlocker.");
                }

                // 1. Inicializar Bold
                const boldCheckout = new BoldCheckout({
                    orderId: "<?= (string)$internalOrderId ?>",
                    currency: "<?= $currency ?>",
                    amount: "<?= $montoTotalCOP ?>",
                    apiKey: "<?= trim($config['bold_api_key']) ?>",
                    integritySignature: "<?= $firmaIntegridad ?>",
                    description: "Membresía <?= htmlspecialchars($order['plan_name']) ?>",
                    redirectionUrl: "<?= $redirectionUrl ?>",
                    renderMode: "modal", 
                    tax: "0"
                });

                // 2. Quitar el circulo de carga y texto
                document.getElementById('bold-loader').style.display = 'none';
                document.getElementById('bold-wait-text').style.display = 'none';
                
                // 3. Crear el botón seguro de pago
                const container = document.getElementById('bold-btn-container');
                container.innerHTML = '<button id="pay-bold-btn" style="background: var(--accent-green); color: #000; padding: 15px 30px; border: none; border-radius: 8px; font-weight: bold; font-size: 1.1em; cursor: pointer; transition: transform 0.2s; box-shadow: 0 5px 15px rgba(0,255,136,0.3);">💳 Abrir Pasarela de Pago</button>';
                
                const payBtn = document.getElementById('pay-bold-btn');
                
                // Efecto visual para el botón
                payBtn.addEventListener('mouseover', () => payBtn.style.transform = 'scale(1.05)');
                payBtn.addEventListener('mouseout', () => payBtn.style.transform = 'scale(1)');

                // 4. El comando CORRECTO para abrir Bold al hacer clic
                payBtn.addEventListener('click', function() {
                    boldCheckout.open();
                });
                
                // Intentar abrir automáticamente por si el navegador lo permite
                setTimeout(() => {
                    boldCheckout.open();
                }, 800);

            } catch (error) {
                console.error(error);
                document.getElementById('bold-loader').style.display = 'none';
                const waitText = document.getElementById('bold-wait-text');
                waitText.innerText = "❌ Error: " + error.message;
                waitText.style.color = "#ff3b3b";
                waitText.style.display = 'block';
            }
        });
    </script>

</body>
</html>
