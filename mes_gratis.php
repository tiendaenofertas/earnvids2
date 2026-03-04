<?php
// mes_gratis.php - Sistema Seguro de Recompensas Sociales (Textos Optimizados TW/FB)
require_once 'config/app.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

$userId = $_SESSION['user_id'];

// --- ⚙️ MOTOR BACKEND (AJAX): PROCESAR LA RECOMPENSA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'claim_free_month') {
        header('Content-Type: application/json');
        
        try {
            $stmt = db()->prepare("SELECT mes_gratis_usado, membership_expiry FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Usuario no válido.']);
                exit;
            }

            if ($user['mes_gratis_usado'] == 1) {
                echo json_encode(['success' => false, 'message' => 'Ya has utilizado tu beneficio de 1 mes gratis.']);
                exit;
            }

            $currentExpiry = $user['membership_expiry'] ? strtotime($user['membership_expiry']) : time();
            $baseTime = max($currentExpiry, time()); 
            $newExpiryDate = date('Y-m-d H:i:s', strtotime('+30 days', $baseTime));

            db()->beginTransaction();
            $update = db()->prepare("UPDATE users SET membership_expiry = ?, mes_gratis_usado = 1 WHERE id = ?");
            $update->execute([$newExpiryDate, $userId]);
            
            $insertPayment = db()->prepare("INSERT INTO payments (user_id, plan_id, amount, gateway, status, payment_id) VALUES (?, ?, ?, ?, ?, ?)");
            $insertPayment->execute([$userId, 0, 0.00, 'promo_social', 'completed', 'PROMO-FREE-'.time()]);
            
            db()->commit();

            echo json_encode([
                'success' => true, 
                'message' => '¡Felicidades! Se ha añadido 1 mes gratis a tu cuenta.',
                'new_date' => date('d/m/Y', strtotime($newExpiryDate))
            ]);

        } catch (Exception $e) {
            db()->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor. Inténtalo más tarde.']);
        }
        exit;
    }
}

// --- 🎨 CARGAR ESTADO PARA LA VISTA ---
$stmt = db()->prepare("SELECT mes_gratis_usado FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();
$yaUsado = ($userData['mes_gratis_usado'] == 1);

// Generar URL base dinámica
$shareUrl = "https://" . $_SERVER['HTTP_HOST'];

// --- 📦 DATOS DINÁMICOS DE LAS TARJETAS (SEPARADOS FB y TW) ---
$cards = [
    [
        'id' => 1, 
        'title' => 'Mes Gratis Premium', 
        'desc' => '¡Lleva tus videos al siguiente nivel sin gastar un centavo! 🚀 En XVIDSPRO te regalamos 1 MES TOTALMENTE GRATIS.', 
        'img' => '/assets/img/Gemini_Generated_11.png',
        'share_text_fb' => "¡Lleva tus videos al siguiente nivel sin gastar un centavo! 🚀\n\nEn XVIDSPRO te regalamos 1 MES TOTALMENTE GRATIS de nuestra plataforma de Streaming Premium. 🎬✨\n\n✅ Sube videos sin límites de velocidad.\n✅ Conecta tu propio almacenamiento (Wasabi, AWS, Contabo).\n✅ Protección total anti-robos (Hotlink).\n\n👉 ¿Cómo reclamarlo? Solo comparte esta publicación y regístrate en nuestro sitio. ¡Así de fácil!\n\n#XVIDSPRO #Streaming #Hosting #VideoHosting #MesGratis #WebMaster",
        'share_text_tw' => "¡Lleva tus videos al siguiente nivel! 🚀 En XVIDSPRO regalamos 1 MES GRATIS de Streaming. 🎬✨ Sube sin límites y protege tu contenido. 👉 Reclámalo aquí:\n\n#XVIDSPRO #Streaming #WebMaster"
    ],
    [
        'id' => 2, 
        'title' => 'Protección Total', 
        'desc' => '¿Cansado de que otros sitios web usen tus videos sin permiso? 😡 Nuestra tecnología Anti Hotlink te da el control total.', 
        'img' => '/assets/img/XVIDSPRO222.jpg',
        'share_text_fb' => "¿Cansado de que otros sitios web usen tus videos sin permiso? 😡\n\n¡Con XVIDSPRO, eso se acabó! 🛡️ Nuestra tecnología de Protección Hotlink Avanzada te da el control total.\n\n✅ Decide qué dominios pueden reproducir tu contenido.\n✅ Bloquea automáticamente a los ladrones de ancho de banda.\n✅ Asegura tu contenido Premium al 100%.\n\n👉 ¡Toma el control y asegura tu trabajo! Descubre cómo proteger tus videos hoy mismo.\n\n#XVIDSPRO #SeguridadVideo #AntiHotlink #ProteccionContenido #WebmasterPro",
        'share_text_tw' => "¿Cansado de que roben tus videos? 😡 Con XVIDSPRO tienes Protección Hotlink Avanzada. Toma el control y asegura tu contenido al 100%. 🛡️ Descubre cómo aquí:\n\n#SeguridadVideo #WebmasterPro"
    ],
    [
        'id' => 3, 
        'title' => 'Máxima Velocidad y SEO', 
        'desc' => '¿Tu web carga lento por culpa de los videos? 🐢 Con XVIDSPRO, tus videos se alojan externamente y cargan al instante.', 
        'img' => '/assets/img/XVIDSPRO333.jpg',
        'share_text_fb' => "¿Tu web carga lento por culpa de los videos? 🐢\n\n¡Eso afecta tu SEO y espanta a tus visitas! Con XVIDSPRO, tus videos se alojan externamente y cargan al instante. 🚀\n\n✅ Mejora la velocidad de tu sitio web.\n✅ Escala posiciones en Google (SEO friendly).\n✅ Ofrece una experiencia de usuario fluida.\n\n👉 ¡Deja de ralentizar tu web! Descubre cómo optimizar tu sitio con nuestro hosting de video profesional.\n\n#XVIDSPRO #SEO #VelocidadWeb #Webmaster #Optimización",
        'share_text_tw' => "¿Tu web carga lento por los videos? 🐢 Mejora tu SEO y velocidad alojando externamente con XVIDSPRO. Carga al instante. 🚀 Únete hoy:\n\n#SEO #VelocidadWeb #XVIDSPRO"
    ],
    [
        'id' => 4, 
        'title' => 'Paquete Definitivo', 
        'desc' => '¡Todo lo que necesitas en un solo lugar! 🎁🛡️🚀 GRATIS, SEGURO y RÁPIDO. Obtén la solución completa hoy.', 
        'img' => '/assets/img/XVIDSPRO444.png',
        'share_text_fb' => "¡Todo lo que necesitas en un solo lugar! 🎁🛡️🚀\n\n¿Por qué elegir? Con XVIDSPRO obtienes el paquete definitivo para tus videos.\n\n✅ GRATIS: Empieza con 1 MES de prueba sin costo.\n✅ SEGURO: Protección total contra robos y Hotlink.\n✅ RÁPIDO: Carga instantánea y optimización SEO.\n\n👉 No te conformes con menos. ¡Obtén la solución completa que potenciará tu sitio web hoy mismo!\n\n#XVIDSPRO #TodoEnUno #Streaming #SeguridadWeb #Velocidad #OfertaEspecial",
        'share_text_tw' => "¡Todo en un solo lugar! 🎁🛡️🚀 Streaming GRATIS por 1 mes, SEGURO contra robos y RÁPIDO para tu SEO. La solución definitiva para tus videos está en XVIDSPRO. 👉 Empieza ya:\n\n#VideoHosting"
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mes Gratis - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .page-container { max-width: 1200px; margin: 0 auto; padding: 20px; text-align: center; }
        .promo-header { background: linear-gradient(135deg, rgba(0,255,136,0.1) 0%, rgba(0,0,0,0) 100%); padding: 40px 20px; border-radius: 12px; margin-bottom: 40px; border: 1px solid var(--accent-green); box-shadow: 0 0 20px rgba(0,255,136,0.05); }
        .promo-title { font-size: 2.5em; color: var(--accent-green); margin-bottom: 10px; }
        .promo-subtitle { color: var(--text-secondary); font-size: 1.1em; max-width: 600px; margin: 0 auto; line-height: 1.5; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-top: 30px; }
        
        .promo-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s; position: relative; display: flex; flex-direction: column; }
        .promo-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.5); border-color: rgba(255,255,255,0.1); }
        
        .card-image { width: 100%; height: 160px; background: var(--bg-secondary); object-fit: cover; border-bottom: 1px solid var(--border-color); }
        .card-content { padding: 25px 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-content h3 { margin-top: 0; margin-bottom: 10px; font-size: 1.2em; color: var(--text-primary); }
        .card-content p { color: var(--text-secondary); font-size: 0.9em; line-height: 1.4; margin-bottom: 20px; flex-grow: 1; text-align: left; }
        
        .social-buttons { display: flex; gap: 10px; margin-bottom: 15px; }
        .btn-social { flex: 1; padding: 10px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; color: #fff; transition: opacity 0.2s, filter 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 0.9em; }
        .btn-social:hover { opacity: 0.9; }
        .btn-social.clicked { filter: grayscale(100%); opacity: 0.5; pointer-events: none; }
        
        .btn-fb { background: #1877F2; }
        .btn-tw { background: #000000; border: 1px solid #333; }
        
        .btn-claim { width: 100%; padding: 12px; background: var(--accent-green); color: #000; font-weight: bold; font-size: 1em; border: none; border-radius: 8px; cursor: pointer; display: none; transition: transform 0.2s; animation: popIn 0.4s ease-out forwards; }
        .btn-claim:hover { transform: scale(1.02); }
        .btn-claim:disabled { background: #555; cursor: not-allowed; transform: none; }
        
        .claimed-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(3px); display: flex; flex-direction: column; justify-content: center; align-items: center; z-index: 10; padding: 20px; text-align: center; }
        .claimed-overlay h2 { color: var(--accent-green); margin-bottom: 10px; }
        
        @keyframes popIn { 0% { opacity: 0; transform: scale(0.9); } 100% { opacity: 1; transform: scale(1); } }
        .icon { width: 18px; height: 18px; fill: currentColor; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-container">
            
            <div class="promo-header">
                <h1 class="promo-title">🎁 Obtén 1 Mes GRATIS</h1>
                <p class="promo-subtitle">
                    ¿Quieres disfrutar de todos los beneficios premium sin pagar? ¡Es muy fácil! <br>
                    Elige una de las plantillas abajo, compártela en tus redes sociales y tu mes gratis se activará automáticamente. 
                    <strong>(Válido una sola vez por usuario).</strong>
                </p>
            </div>

            <?php if ($yaUsado): ?>
                <div class="claimed-overlay" style="position: relative; border-radius: 12px; height: auto; padding: 40px; background: var(--bg-card); border: 1px solid var(--accent-green);">
                    <svg viewBox="0 0 24 24" width="60" height="60" fill="var(--accent-green)" style="margin-bottom: 15px;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <h2>¡Beneficio Utilizado!</h2>
                    <p style="color: #aaa; font-size: 1.1em;">Ya has reclamado tu mes gratis anteriormente. ¡Sigue disfrutando de la plataforma!</p>
                </div>
            <?php else: ?>
                
                <div class="cards-grid">
                    <?php foreach ($cards as $card): ?>
                    <div class="promo-card" id="card-<?= $card['id'] ?>">
                        <img src="<?= $card['img'] ?>" alt="Promo <?= $card['id'] ?>" class="card-image">
                        <div class="card-content">
                            <h3><?= $card['title'] ?></h3>
                            <p><?= $card['desc'] ?></p>
                            
                            <div class="social-buttons" id="social-group-<?= $card['id'] ?>">
                                <button class="btn-social btn-fb" onclick="shareNetwork('facebook', <?= $card['id'] ?>)">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M12 2.04C6.5 2.04 2 6.53 2 12.06C2 17.06 5.66 21.21 10.44 21.96V14.96H7.9V12.06H10.44V9.85C10.44 7.34 11.93 5.96 14.22 5.96C15.31 5.96 16.45 6.15 16.45 6.15V8.62H15.19C13.95 8.62 13.56 9.39 13.56 10.18V12.06H16.34L15.89 14.96H13.56V21.96A10 10 0 0 0 22 12.06C22 6.53 17.5 2.04 12 2.04Z"/></svg>
                                    Compartir
                                </button>
                                
                                <button class="btn-social btn-tw" onclick="shareNetwork('twitter', <?= $card['id'] ?>)">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                    Postear
                                </button>
                            </div>
                            
                            <button class="btn-claim" id="claim-btn-<?= $card['id'] ?>" onclick="claimFreeMonth(<?= $card['id'] ?>)">
                                🎁 RECLAMAR MES GRATIS
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script>
        // 1. Exportamos los textos desde PHP (Separados por red social)
        const shareTextsFB = <?= json_encode(array_column($cards, 'share_text_fb', 'id')) ?>;
        const shareTextsTW = <?= json_encode(array_column($cards, 'share_text_tw', 'id')) ?>;
        const mainUrl = "<?= $shareUrl ?>";

        const cardStates = {
            1: { fb: false, tw: false },
            2: { fb: false, tw: false },
            3: { fb: false, tw: false },
            4: { fb: false, tw: false }
        };

        function shareNetwork(network, cardId) {
            let popupUrl = '';
            
            if (network === 'facebook') {
                const textFB = shareTextsFB[cardId];
                popupUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(mainUrl)}&quote=${encodeURIComponent(textFB)}`;
                cardStates[cardId].fb = true;
                event.currentTarget.classList.add('clicked');
                event.currentTarget.innerHTML = '✅ Listo';
            } 
            else if (network === 'twitter') {
                const textTW = shareTextsTW[cardId];
                // En Twitter usamos el parámetro "url" para adjuntar el enlace sin comer caracteres del texto principal
                popupUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(textTW)}&url=${encodeURIComponent(mainUrl)}`;
                cardStates[cardId].tw = true;
                event.currentTarget.classList.add('clicked');
                event.currentTarget.innerHTML = '✅ Listo';
            }

            // Abrir ventana emergente
            window.open(popupUrl, '_blank', 'width=600,height=550,scrollbars=yes');

            // Comprobar si completó ambos en esta tarjeta
            checkCardCompletion(cardId);
        }

        function checkCardCompletion(cardId) {
            if (cardStates[cardId].fb && cardStates[cardId].tw) {
                document.getElementById('social-group-' + cardId).style.display = 'none';
                const claimBtn = document.getElementById('claim-btn-' + cardId);
                claimBtn.style.display = 'block';
            }
        }

        async function claimFreeMonth(cardId) {
            const btn = document.getElementById('claim-btn-' + cardId);
            const originalText = btn.innerText;
            
            btn.innerText = '⏳ PROCESANDO...';
            btn.disabled = true;

            try {
                const response = await fetch('/mes_gratis.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ action: 'claim_free_month' })
                });

                const data = await response.json();

                if (data.success) {
                    btn.style.background = '#00ff88';
                    btn.style.color = '#000';
                    btn.innerText = '✅ ¡MES ACTIVADO!';
                    
                    alert(data.message + '\nNueva fecha de vencimiento: ' + data.new_date);
                    setTimeout(() => { window.location.reload(); }, 2000);
                } else {
                    alert('Error: ' + data.message);
                    btn.innerText = originalText;
                    btn.disabled = false;
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error de conexión. Intenta de nuevo.');
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
