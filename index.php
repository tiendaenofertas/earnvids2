<?php
// index.php - Landing Page Completa (Con Menú Hamburguesa, FAQ y URLs Limpias)
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// ==========================================
// 🌍 SISTEMA DE MULTI-IDIOMA (ES / EN)
// ==========================================
$lang = $_COOKIE['lang'] ?? 'es'; 
if (isset($_GET['lang']) && in_array($_GET['lang'], ['es', 'en'])) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, time() + (86400 * 30), "/"); 
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); 
    exit;
}

$t = [
    'es' => [
        'login' => 'Iniciar Sesión', 'register' => 'Registrarse',
        'hero_title' => 'Sube tus videos a múltiples storage',
        'hero_desc' => 'Disfruta de la mejor seguridad, velocidad y estabilidad, utilizando infraestructuras profesionales de alto rendimiento. Tu contenido estará siempre disponible, protegido y optimizado para una carga ultra rápida.',
        'btn_plans' => 'Ver Planes', 'btn_account' => 'Crear Cuenta',
        'promo_title' => '🎁 Promoción Especial – 1 Mes Gratis',
        'promo_desc' => '¿Quieres un mes totalmente GRATIS? <br>Compártenos en <strong>Facebook o Reddit</strong> y recibe 1 mes de membresía sin costo.',
        'promo_btn' => 'Contactar para reclamar',
        'multi_storage' => 'Utiliza múltiples almacenamientos en un solo lugar',
        'stat_users' => 'Usuarios Activos', 'stat_videos' => 'Videos Premium', 'stat_views' => 'Reproducciones',
        'why_title' => '¿Por qué elegirnos?', 'why_subtitle' => 'La mejor plataforma de streaming y almacenamientos diseñada para creadores exigentes.',
        'testi_tag' => 'TESTIMONIOS', 'testi_title' => 'Lo que dicen nuestros usuarios', 'testi_desc' => 'Miles de desarrolladores y agencias confían en XVIDSPRO a diario.',
        'plan_title' => 'Planes de Membresía', 'plan_desc' => 'Elige el plan perfecto para ti y disfruta sin límites',
        'faq_title' => 'Preguntas que nos hacen', 'faq_subtitle' => 'Resolvemos las dudas más comunes sobre nuestra plataforma',
        'faq_q1' => '¿Qué es XVIDSPRO y para qué sirve?', 'faq_a1' => 'Es una plataforma SaaS que te permite alojar, administrar y proteger tus videos conectando tu propio almacenamiento en la nube (como Wasabi, AWS S3 o Contabo). Nosotros nos encargamos de proveerte un reproductor HTML5 ultra rápido y seguro.',
        'faq_q2' => '¿Puedo proteger mis videos contra robos?', 'faq_a2' => 'Sí, contamos con un sistema de protección Anti-Hotlink estricto. Tú defines qué dominios están autorizados para reproducir tu contenido; cualquier otro sitio que intente incrustar tu video será bloqueado automáticamente.',
        'faq_q3' => '¿Qué pasa si tengo millones de reproducciones?', 'faq_a3' => 'Nuestra infraestructura está diseñada para escalar. Al conectarte a proveedores de almacenamiento en la nube, el ancho de banda fluye directamente desde ellos hacia el reproductor (muchos con transferencia gratuita o costos mínimos), evitando que tu sitio web se caiga.',
        'faq_q4' => '¿Tengo que saber programar para usarlo?', 'faq_a4' => 'No. XVIDSPRO te ofrece un panel de control intuitivo. Solo configuras tus credenciales de almacenamiento una vez y puedes empezar a subir y gestionar tus videos con un par de clics.',
        'footer_help' => 'Información de Ayuda', 'footer_contact' => 'Contáctanos', 'footer_community' => 'Comunidad Oficial'
    ],
    'en' => [
        'login' => 'Login', 'register' => 'Sign Up',
        'hero_title' => 'Upload your videos to multiple storages',
        'hero_desc' => 'Enjoy the best security, speed, and stability using high-performance professional infrastructures. Your content will always be available, protected, and optimized for ultra-fast loading.',
        'btn_plans' => 'View Plans', 'btn_account' => 'Create Account',
        'promo_title' => '🎁 Special Promotion – 1 Month Free',
        'promo_desc' => 'Want a completely FREE month? <br>Share us on <strong>Facebook or Reddit</strong> and get 1 month of membership at no cost.',
        'promo_btn' => 'Contact to claim',
        'multi_storage' => 'Use multiple cloud storages in one single place',
        'stat_users' => 'Active Users', 'stat_videos' => 'Premium Videos', 'stat_views' => 'Total Views',
        'why_title' => 'Why choose us?', 'why_subtitle' => 'The best streaming and storage platform designed for demanding creators.',
        'testi_tag' => 'TESTIMONIALS', 'testi_title' => 'What our users say', 'testi_desc' => 'Thousands of developers and agencies trust XVIDSPRO daily.',
        'plan_title' => 'Membership Plans', 'plan_desc' => 'Choose the perfect plan for you and enjoy without limits',
        'faq_title' => 'Frequently Asked Questions', 'faq_subtitle' => 'We answer the most common doubts about our platform',
        'faq_q1' => 'What is XVIDSPRO and what is it for?', 'faq_a1' => 'It is a SaaS platform that allows you to host, manage, and protect your videos by connecting your own cloud storage (like Wasabi, AWS S3, or Contabo). We provide an ultra-fast and secure HTML5 player.',
        'faq_q2' => 'Can I protect my videos from being stolen?', 'faq_a2' => 'Yes, we have a strict Anti-Hotlink protection system. You define which domains are authorized to play your content; any other site attempting to embed your video will be automatically blocked.',
        'faq_q3' => 'What happens if I get millions of views?', 'faq_a3' => 'Our infrastructure is designed to scale. By connecting to cloud storage providers, bandwidth flows directly from them to the player (many with free transfer or minimal costs), preventing your website from crashing.',
        'faq_q4' => 'Do I need to know how to code to use it?', 'faq_a4' => 'No. XVIDSPRO offers an intuitive control panel. You only configure your storage credentials once and you can start uploading and managing your videos with a few clicks.',
        'footer_help' => 'Help Information', 'footer_contact' => 'Contact Us', 'footer_community' => 'Official Community'
    ]
];
function __($key) { global $t, $lang; return $t[$lang][$key] ?? $t['es'][$key] ?? $key; }

// ==========================================
// 🚀 ESTADÍSTICAS (Con Auto-Rescate Dinámico)
// ==========================================
$totalUsers = 0; $totalVideos = 0; $totalViews = 0;

try {
    $stats_query = db()->query("SELECT stat_name, stat_value FROM system_stats")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($stats_query)) {
        $totalUsers  = $stats_query['total_users_active'] ?? 0;
        $totalVideos = $stats_query['total_videos_active'] ?? 0;
        $totalViews  = $stats_query['total_views_global'] ?? 0;
    }
} catch (Exception $e) {}

if ($totalUsers == 0 && $totalVideos == 0) {
    $totalUsers = db()->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $totalVideos = db()->query("SELECT COUNT(*) FROM videos WHERE status = 'active'")->fetchColumn();
    $totalViews = db()->query("SELECT COALESCE(SUM(views), 0) FROM videos")->fetchColumn();
}

$stmt = db()->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Comparte Videos y Gana Dinero</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0a; color: #fff; overflow-x: hidden; }
        
        /* Header y Navegación */
        .header { position: fixed; top: 0; width: 100%; padding: 20px 0; background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(10px); z-index: 1000; transition: all 0.3s ease; }
        .header.scrolled { padding: 15px 0; box-shadow: 0 2px 20px rgba(0, 255, 136, 0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
        .logo svg { width: 40px; height: 40px; fill: #00ff88; }
        .logo h1 { font-size: 28px; font-weight: 700; }
        .logo span { color: #00ff88; }
        
        /* Botón Menú Hamburguesa (Oculto en Desktop) */
        .mobile-menu-btn { display: none; background: none; border: none; color: #fff; cursor: pointer; padding: 5px; transition: color 0.3s; }
        .mobile-menu-btn:hover { color: #00ff88; }

        .nav-buttons { display: flex; gap: 15px; align-items: center; transition: all 0.3s ease-in-out; }
        
        .lang-switch { display: inline-flex; background: rgba(255,255,255,0.1); border-radius: 30px; padding: 4px; align-items: center; margin-right: 5px; }
        .lang-btn { color: rgba(255,255,255,0.6); text-decoration: none; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; transition: 0.3s; }
        .lang-btn:hover { color: #fff; }
        .lang-btn.active { background: #fff; color: #000; box-shadow: 0 2px 10px rgba(0,0,0,0.2); pointer-events: none; }

        .btn { padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer; white-space: nowrap; }
        .btn-primary { background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3); }
        .btn-secondary { background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.15); border-color: rgba(255, 255, 255, 0.3); }
        
        /* Hero Section */
        .hero { min-height: 100vh; display: flex; align-items: center; padding: 140px 0 80px; position: relative; overflow: hidden; }
        .hero-bg { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 20% 50%, rgba(0, 255, 136, 0.15) 0%, transparent 50%), radial-gradient(circle at 80% 50%, rgba(0, 168, 255, 0.1) 0%, transparent 50%); z-index: -1; }
        .hero-content { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .hero-text h2 { font-size: clamp(35px, 4vw, 55px); font-weight: 800; line-height: 1.4; padding-top: 10px; padding-bottom: 5px; margin-bottom: 20px; background: linear-gradient(135deg, #fff 0%, #00ff88 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-text p { font-size: 18px; color: rgba(255, 255, 255, 0.8); margin-bottom: 30px; line-height: 1.6; }
        .cloud-logos { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
        .cloud-tag { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #00ff88; }
        .hero-buttons { display: flex; gap: 20px; flex-wrap: wrap; }
        .hero-visual { position: relative; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px rgba(0, 255, 136, 0.2); border: 1px solid rgba(0, 255, 136, 0.3); }
        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        
        /* Banner Promo */
        .promo-banner { background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 168, 255, 0.1) 100%); border: 1px solid rgba(0, 255, 136, 0.3); border-radius: 20px; padding: 40px; text-align: center; max-width: 900px; margin: 40px auto 0; position: relative; overflow: hidden; }
        .promo-banner::before { content: '🎁'; font-size: 120px; position: absolute; left: -20px; top: -30px; opacity: 0.1; transform: rotate(-15deg); }
        .promo-banner h3 { color: #00ff88; font-size: 32px; font-weight: 800; margin-bottom: 15px; }
        .promo-banner p { font-size: 18px; color: #fff; margin-bottom: 25px; line-height: 1.5; }

        /* Logos de Almacenamiento */
        .storage-integration { text-align: center; margin-top: 60px; padding-top: 40px; border-top: 1px solid rgba(255,255,255,0.05); }
        .storage-integration h4 { color: rgba(255,255,255,0.6); font-size: 18px; margin-bottom: 35px; font-weight: 500; letter-spacing: 0.5px; }
        .storage-grid { display: flex; justify-content: center; align-items: center; gap: 40px; flex-wrap: wrap; }
        .storage-grid img { height: 40px; object-fit: contain; filter: grayscale(100%) opacity(0.5); transition: all 0.3s ease; }
        .storage-grid img:hover { filter: grayscale(0%) opacity(1); transform: scale(1.05); }
        
        /* Stats & Features */
        .stats { padding: 60px 0 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; text-align: center; }
        .stat-item h3 { font-size: 48px; font-weight: 700; color: #00ff88; margin-bottom: 10px; }
        .stat-item p { font-size: 18px; color: rgba(255, 255, 255, 0.7); }
        
        .features { padding: 80px 0; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 42px; font-weight: 700; margin-bottom: 15px; }
        .section-header p { font-size: 18px; color: rgba(255, 255, 255, 0.7); max-width: 700px; margin: 0 auto; line-height: 1.6; }
        
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .feature-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 30px; transition: all 0.3s ease; display: flex; flex-direction: column; }
        .feature-card:hover { transform: translateY(-5px); border-color: rgba(0, 255, 136, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .feature-icon { width: 70px; height: 70px; background: linear-gradient(135deg, rgba(0, 255, 136, 0.15) 0%, rgba(0, 168, 255, 0.15) 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; border: 1px solid rgba(0, 255, 136, 0.2); }
        .feature-icon svg { width: 35px; height: 35px; fill: #00ff88; }
        .feature-card h3 { font-size: 22px; margin-bottom: 15px; color: #fff; }
        .feature-card p { color: rgba(255, 255, 255, 0.7); line-height: 1.6; font-size: 15px; flex-grow: 1; }

        /* Testimonios */
        .testimonials { padding: 80px 0; background: #080808; border-top: 1px solid rgba(255,255,255,0.05); }
        .tagline { color: #00ff88; font-size: 13px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 10px; display: block; text-align: center; }
        .testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 40px; }
        .testi-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; padding: 30px; position: relative; transition: 0.3s ease; display: flex; flex-direction: column; justify-content: space-between; }
        .testi-card:hover { border-color: rgba(0, 255, 136, 0.3); transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,255,136,0.05); }
        .stars { color: #ffaa00; font-size: 18px; margin-bottom: 15px; letter-spacing: 2px; }
        .testi-text { font-size: 15px; color: rgba(255,255,255,0.8); line-height: 1.6; margin-bottom: 25px; font-style: italic; flex-grow: 1; }
        .user-profile { display: flex; align-items: center; gap: 15px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px; }
        .user-profile img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        .user-info-text h5 { color: #fff; font-size: 16px; margin: 0 0 3px 0; font-weight: 600; }
        .user-info-text span { color: rgba(255,255,255,0.5); font-size: 13px; }

        /* FAQ Section */
        .faq-section { padding: 80px 0; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .faq-grid { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 15px; }
        .faq-item { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; overflow: hidden; transition: all 0.3s ease; }
        .faq-item:hover { border-color: rgba(0, 255, 136, 0.3); }
        .faq-summary { padding: 20px 25px; font-size: 18px; font-weight: 600; color: #fff; cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none; user-select: none; }
        .faq-summary::-webkit-details-marker { display: none; } 
        .faq-summary::after { content: '+'; font-size: 24px; color: #00ff88; transition: transform 0.3s ease; font-weight: normal; }
        details[open] .faq-summary::after { transform: rotate(45deg); color: #ff3b3b; }
        details[open] .faq-item { border-color: rgba(0, 255, 136, 0.4); box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .faq-content { padding: 0 25px 25px; color: rgba(255, 255, 255, 0.7); line-height: 1.6; font-size: 16px; border-top: 1px solid transparent; }
        details[open] .faq-content { border-top-color: rgba(255, 255, 255, 0.05); padding-top: 20px; }

        /* Pricing */
        .pricing-section { padding: 80px 0; border-top: 1px solid rgba(255,255,255,0.05); }

        /* Footer */
        .footer { padding: 60px 0 30px; border-top: 1px solid rgba(255, 255, 255, 0.1); background: #050505; }
        .footer-content { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .footer-col h4 { font-size: 20px; color: #fff; margin-bottom: 20px; font-weight: 600; }
        .footer-links { list-style: none; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: rgba(255, 255, 255, 0.6); text-decoration: none; transition: color 0.3s ease; font-size: 16px; }
        .footer-links a:hover { color: #00ff88; padding-left: 5px; }
        .footer-telegram { background: linear-gradient(135deg, rgba(0, 168, 255, 0.1) 0%, rgba(0, 255, 136, 0.05) 100%); border: 1px solid rgba(0, 168, 255, 0.2); border-radius: 16px; padding: 25px; display: flex; align-items: center; gap: 20px; }
        .footer-telegram img { width: 120px; height: 120px; border-radius: 10px; background: #fff; padding: 5px; }
        .telegram-info p { color: rgba(255, 255, 255, 0.8); font-size: 15px; margin-bottom: 15px; line-height: 1.5; }
        .btn-telegram { background: #0088cc; color: #fff; padding: 8px 20px; border-radius: 30px; text-decoration: none; font-weight: bold; display: inline-block; font-size: 14px; transition: all 0.3s; }
        .btn-telegram:hover { background: #00a8ff; box-shadow: 0 5px 15px rgba(0, 168, 255, 0.4); }
        .footer-bottom { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; }
        .footer-copy p { color: rgba(255, 255, 255, 0.4); font-size: 14px; margin: 0; }
        .footer-logo img { max-height: 35px; opacity: 0.6; transition: opacity 0.3s ease; }
        .footer-logo img:hover { opacity: 1; }
        
        /* 📱 CORRECCIONES PARA MÓVILES (Menú Hamburguesa) */
        @media (max-width: 900px) { 
            .header { padding: 15px 0; }
            .mobile-menu-btn { display: block; } /* Mostrar botón de hamburguesa */
            
            /* Ocultar menú por defecto y darle diseño de panel desplegable */
            .nav-buttons { 
                display: none; 
                width: 100%; 
                flex-direction: column; 
                padding-top: 15px; 
                margin-top: 15px; 
                border-top: 1px solid rgba(255, 255, 255, 0.1); 
            }
            .nav-buttons.active { display: flex; } /* Clase que lo activa */
            
            .lang-switch { margin-bottom: 10px; justify-content: center; }
            .btn { width: 100%; text-align: center; }
            
            .hero { padding: 130px 0 60px; }
            .hero-content { grid-template-columns: 1fr; text-align: center; } 
            .hero-visual { order: -1; } 
            .hero-buttons { justify-content: center; } 
            .cloud-logos { justify-content: center; }
            .footer-content { grid-template-columns: 1fr; text-align: center; }
            .footer-col div { flex-direction: column; gap: 20px; align-items: center; }
            .footer-telegram { flex-direction: column; text-align: center; }
            .footer-bottom { flex-direction: column; gap: 15px; text-align: center; }
        }
    </style>
</head>
<body>
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="logo">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                    <h1>XVIDS<span>PRO</span></h1>
                </a>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menú">
                    <svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor">
                        <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
                    </svg>
                </button>

                <div class="nav-buttons" id="navButtons">
                    <div class="lang-switch">
                        <a href="?lang=es" class="lang-btn <?= $lang == 'es' ? 'active' : '' ?>">ES</a>
                        <a href="?lang=en" class="lang-btn <?= $lang == 'en' ? 'active' : '' ?>">EN</a>
                    </div>
                    <a href="/login" class="btn btn-secondary"><?= __('login') ?></a>
                    <a href="/register" class="btn btn-primary"><?= __('register') ?></a>
                </div>
            </nav>
        </div>
    </header>
    
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h2><?= __('hero_title') ?></h2>
                    <p><?= __('hero_desc') ?></p>
                    
                    <div class="cloud-logos">
                        <span class="cloud-tag">Wasabi</span>
                        <span class="cloud-tag">Cloudflare</span>
                        <span class="cloud-tag">Amazon S3</span>
                        <span class="cloud-tag">DigitalOcean</span>
                        <span class="cloud-tag">Backblaze</span>
                        <span class="cloud-tag">Contabo</span>
                    </div>

                    <div class="hero-buttons">
                        <a href="#plans" class="btn btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            <?= __('btn_plans') ?>
                        </a>
                        <a href="/register" class="btn btn-secondary"><?= __('btn_account') ?></a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="video-wrapper">
                        <iframe src="https://xv.xzorra.net/embed.php?v=7I3TUKUOvW" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
            </div>

            <div class="promo-banner">
                <h3><?= __('promo_title') ?></h3>
                <p><?= __('promo_desc') ?></p>
                <a href="#footer-contact" class="btn btn-primary"><?= __('promo_btn') ?></a>
            </div>

            <div class="storage-integration">
                <h4><?= __('multi_storage') ?></h4>
                <div class="storage-grid">
                    <img src="/assets/img/contabo.webp" alt="Contabo">
                    <img src="/assets/img/CLOUDFLARE.png" alt="Cloudflare R2">
                    <img src="/assets/img/amazon-s3-.png" alt="Amazon S3">
                    <img src="/assets/img/DO_Log.png" alt="DigitalOcean Spaces">
                    <img src="/assets/img/backblaze.png" alt="Backblaze B2">
                    <img src="/assets/img/wasabi.png" alt="Wasabi">
                </div>
            </div>

        </div>
    </section>
    
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?= number_format($totalUsers) ?>+</h3>
                    <p><?= __('stat_users') ?></p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format($totalVideos) ?>+</h3>
                    <p><?= __('stat_videos') ?></p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format($totalViews) ?>+</h3>
                    <p><?= __('stat_views') ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2><?= __('why_title') ?></h2>
                <p><?= __('why_subtitle') ?></p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg></div>
                    <h3>1. Seguridad Total</h3>
                    <p>Tu contenido está protegido con la mejor tecnología de encriptación y sistemas de autenticación de vanguardia.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/></svg></div>
                    <h3>2. Velocidad Rayo</h3>
                    <p>Servidores optimizados para una entrega de contenido instantánea y sin interrupciones tipo buffering.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg></div>
                    <h3>3. Hotlink Protection</h3>
                    <p>Tendrás el control total sobre qué dominios pueden reproducir tus videos. Evita que terceros roben o incrusten tu contenido sin autorización.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg></div>
                    <h3>4. Mis Conexiones</h3>
                    <p>Nadie sabrá dónde están realmente alojados tus videos. Nuestro sistema protege tu infraestructura.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg></div>
                    <h3>5. Optimización SEO</h3>
                    <p>Al no alojar videos en tu hosting web principal, tu sitio web volará en los buscadores.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/></svg></div>
                    <h3>6. Multi-dispositivo</h3>
                    <p>Disfruta y administra tu contenido en tu teléfono móvil, tablet o computadora con nuestro diseño 100% adaptable.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <span class="tagline"><?= __('testi_tag') ?></span>
                <h2><?= __('testi_title') ?></h2>
                <p><?= __('testi_desc') ?></p>
            </div>
            
            <div class="testi-grid">
                <div class="testi-card">
                    <div class="stars">★★★★★</div>
                    <p class="testi-text">"Instalé XVIDSPRO en mis sitios y en 20 minutos ya estaba subiendo contenido. Las conexiones a S3 funcionan increíble, mis videos cargan sin pausas."</p>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Carlos+Mendoza&background=0D8ABC&color=fff&rounded=true" alt="Carlos">
                        <div class="user-info-text"><h5>Carlos Mendoza</h5><span>Desarrollador Web</span></div>
                    </div>
                </div>
                <div class="testi-card">
                    <div class="stars">★★★★★</div>
                    <p class="testi-text">"El mejor script que he probado. La posibilidad de ocultar mis buckets de Wasabi me da la tranquilidad que necesitaba contra los baneos."</p>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Ana+Torres&background=00ff88&color=000&rounded=true" alt="Ana">
                        <div class="user-info-text"><h5>Ana Torres</h5><span>Directora de E-commerce</span></div>
                    </div>
                </div>
                <div class="testi-card">
                    <div class="stars">★★★★★</div>
                    <p class="testi-text">"La velocidad del reproductor HTML5 es brutal. Mis métricas de Core Web Vitals subieron muchísimo porque el Iframe carga al instante."</p>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Raul+Fernandez&background=ffaa00&color=000&rounded=true" alt="Raul">
                        <div class="user-info-text"><h5>Raul Fernandez</h5><span>Especialista SEO</span></div>
                    </div>
                </div>
                <div class="testi-card">
                    <div class="stars">★★★★★</div>
                    <p class="testi-text">"Me ahorré cientos de dólares al mes en ancho de banda. Cloudflare R2 junto con este sistema es la combinación definitiva para el éxito."</p>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Laura+Sanchez&background=f53b57&color=fff&rounded=true" alt="Laura">
                        <div class="user-info-text"><h5>Laura Sanchez</h5><span>Blogger y Creadora</span></div>
                    </div>
                </div>
                <div class="testi-card">
                    <div class="stars">★★★★★</div>
                    <p class="testi-text">"Excelente plataforma. Pude migrar todos los videos de mis cursos sin preocuparme por la piratería. El Hotlink Protection funciona a la perfección."</p>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Miguel+Angel&background=5a29e4&color=fff&rounded=true" alt="Miguel">
                        <div class="user-info-text"><h5>Miguel Ángel</h5><span>Instructor Online</span></div>
                    </div>
                </div>
                <div class="testi-card">
                    <div class="stars">★★★★★</div>
                    <p class="testi-text">"Como agencia manejamos decenas de clientes. Este script nos permitió centralizar todo el contenido audiovisual en Contabo de forma segura y económica."</p>
                    <div class="user-profile">
                        <img src="https://ui-avatars.com/api/?name=Elena+Castro&background=e42971&color=fff&rounded=true" alt="Elena">
                        <div class="user-info-text"><h5>Elena Castro</h5><span>Directora Agencia Digital</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing-section" id="plans">
        <div class="container">
            <div class="section-header">
                <h2><?= __('plan_title') ?></h2>
                <p><?= __('plan_desc') ?></p>
            </div>
            
            <div class="pricing-grid">
                <?php foreach ($plans as $plan): 
                    $isPopular = ($plan['price'] >= 15 && $plan['price'] <= 25);
                ?>
                <div class="price-card <?= $isPopular ? 'featured' : '' ?>">
                    <?php if ($isPopular): ?>
                        <div class="popular-badge">RECOMENDADO</div>
                    <?php endif; ?>
                    
                    <div class="plan-header">
                        <div class="plan-name"><?= htmlspecialchars($plan['name']) ?></div>
                        <div class="plan-price">
                            <span class="currency">$</span>
                            <?= number_format($plan['price'], 0) ?>
                            <span class="period">/ <?= $plan['duration_days'] ?> días</span>
                        </div>
                    </div>
                    
                    <ul class="plan-features">
                        <li><div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>Acceso Total al Contenido</li>
                        <li><div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>Reproducción HD/4K</li>
                        <li><div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>Sin Anuncios</li>
                        <?php if ($plan['duration_days'] > 30): ?>
                        <li><div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>Soporte VIP 24/7</li>
                        <?php endif; ?>
                    </ul>
                    <a href="/register" class="btn-plan <?= $isPopular ? 'btn-glow' : 'btn-outline' ?>">
                        <?= $isPopular ? 'EMPEZAR AHORA' : __('btn_account') ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="faq-section" id="faq">
        <div class="container">
            <div class="section-header">
                <h2><?= __('faq_title') ?></h2>
                <p><?= __('faq_subtitle') ?></p>
            </div>
            
            <div class="faq-grid">
                <details class="faq-item">
                    <summary class="faq-summary"><?= __('faq_q1') ?></summary>
                    <div class="faq-content"><?= __('faq_a1') ?></div>
                </details>
                
                <details class="faq-item">
                    <summary class="faq-summary"><?= __('faq_q2') ?></summary>
                    <div class="faq-content"><?= __('faq_a2') ?></div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary"><?= __('faq_q3') ?></summary>
                    <div class="faq-content"><?= __('faq_a3') ?></div>
                </details>

                <details class="faq-item">
                    <summary class="faq-summary"><?= __('faq_q4') ?></summary>
                    <div class="faq-content"><?= __('faq_a4') ?></div>
                </details>
            </div>
        </div>
    </section>
    
    <footer class="footer" id="footer-contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4><?= __('footer_help') ?></h4>
                    <div style="display: flex; gap: 40px;">
                        <ul class="footer-links">
                            <li><a href="#">Almacenamiento S3 / S4</a></li>
                            <li><a href="#">Seguridad y protección</a></li>
                            <li><a href="#plans">Planes de membresía</a></li>
                            <li><a href="#faq">Preguntas Frecuentes</a></li>
                        </ul>
                        <ul class="footer-links">
                            <li style="color: #00ff88; font-weight: bold; margin-bottom:15px;"><?= __('footer_contact') ?></li>
                            <li><a href="mailto:soporte@xvidspro.com">soporte@xvidspro.com</a></li>
                            <li><a href="https://t.me/+NKA-jqALfSBmZjBh" target="_blank">Chat de Telegram</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-col">
                    <div class="footer-telegram">
                        <img src="/assets/img/qr-telegram.png" alt="Código QR Telegram">
                        <div class="telegram-info">
                            <h4 style="margin-bottom: 5px; color:#00a8ff;"><?= __('footer_community') ?></h4>
                            <p>Únete a nuestro grupo oficial de Telegram para soporte directo, actualizaciones y promociones exclusivas.</p>
                            <a href="https://t.me/+NKA-jqALfSBmZjBh" target="_blank" class="btn-telegram">👉 Unirme a Telegram</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-copy">
                    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
                </div>
                <div class="footer-logo">
                    <img src="/assets/img/XVIDSPROx.png" alt="XVIDSPRO">
                </div>
            </div>
        </div>
    </footer>
    
    <script>
        // Lógica del Menú Hamburguesa en Móviles
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const navButtons = document.getElementById('navButtons');

        if(mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                navButtons.classList.toggle('active');
                
                // Animación simple del botón al hacer click
                if(navButtons.classList.contains('active')) {
                    mobileMenuBtn.innerHTML = '<svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>';
                } else {
                    mobileMenuBtn.innerHTML = '<svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>';
                }
            });
        }

        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 50) header.classList.add('scrolled');
            else header.classList.remove('scrolled');
        });
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                // Cerrar el menú móvil si se hace clic en un enlace de anclaje
                if(navButtons.classList.contains('active')) {
                    navButtons.classList.remove('active');
                    mobileMenuBtn.innerHTML = '<svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>';
                }
            });
        });

        // Cerrar otros acordeones cuando uno se abre (estilo acordeón único)
        const details = document.querySelectorAll('.faq-item');
        details.forEach((targetDetail) => {
            targetDetail.addEventListener('click', () => {
                details.forEach((detail) => {
                    if (detail !== targetDetail) {
                        detail.removeAttribute('open');
                    }
                });
            });
        });
    </script>
</body>
</html>
