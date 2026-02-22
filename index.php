<?php
// index.php - Landing Page con Planes Premium
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Si está logueado, redirigir al dashboard (Comportamiento estándar SAAS)
if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// Obtener estadísticas reales
$stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$totalUsers = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COUNT(*) as total FROM videos WHERE status = 'active'");
$totalVideos = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COALESCE(SUM(views), 0) as total FROM videos");
$totalViews = $stmt->fetch()['total'];

// Obtener Planes Activos para mostrar en la Home
$stmt = db()->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC");
$plans = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Comparte Videos y Gana Dinero</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0a0a0a; color: #fff; overflow-x: hidden; }
        
        /* Header */
        .header { position: fixed; top: 0; width: 100%; padding: 20px 0; background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(10px); z-index: 1000; transition: all 0.3s ease; }
        .header.scrolled { padding: 15px 0; box-shadow: 0 2px 20px rgba(0, 255, 136, 0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav { display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
        .logo svg { width: 40px; height: 40px; fill: #00ff88; }
        .logo h1 { font-size: 28px; font-weight: 700; }
        .logo span { color: #00ff88; }
        .nav-buttons { display: flex; gap: 15px; }
        
        .btn { padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer;}
        .btn-primary { background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%); color: #000; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3); }
        .btn-secondary { background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); }
        .btn-secondary:hover { background: rgba(255, 255, 255, 0.15); border-color: rgba(255, 255, 255, 0.3); }
        
        /* Hero Section Mejorado con Video */
        .hero { min-height: 100vh; display: flex; align-items: center; padding: 140px 0 80px; position: relative; overflow: hidden; } /* Padding top aumentado para evitar choque con menú */
        .hero-bg { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 20% 50%, rgba(0, 255, 136, 0.15) 0%, transparent 50%), radial-gradient(circle at 80% 50%, rgba(0, 168, 255, 0.1) 0%, transparent 50%); z-index: -1; }
        .hero-content { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        
        /* CORRECCIÓN DEL TEXTO CORTADO AQUÍ */
        .hero-text h2 { 
            font-size: clamp(35px, 4vw, 55px); 
            font-weight: 800; 
            line-height: 1.4; /* Aumentado de 1.2 a 1.4 */
            padding-top: 10px; /* Padding extra para que el gradiente no corte la punta de las letras */
            padding-bottom: 5px;
            margin-bottom: 20px; 
            background: linear-gradient(135deg, #fff 0%, #00ff88 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        
        .hero-text p { font-size: 18px; color: rgba(255, 255, 255, 0.8); margin-bottom: 30px; line-height: 1.6; }
        
        /* Logos de Cloud integrados en el texto */
        .cloud-logos { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
        .cloud-tag { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #00ff88; }
        
        .hero-buttons { display: flex; gap: 20px; flex-wrap: wrap; }
        
        /* Contenedor del Iframe Responsive */
        .hero-visual { position: relative; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 50px rgba(0, 255, 136, 0.2); border: 1px solid rgba(0, 255, 136, 0.3); }
        .video-wrapper { position: relative; padding-bottom: 56.25%; /* 16:9 */ height: 0; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        
        /* Banner de Promoción */
        .promo-banner { background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 168, 255, 0.1) 100%); border: 1px solid rgba(0, 255, 136, 0.3); border-radius: 20px; padding: 40px; text-align: center; max-width: 900px; margin: 40px auto 0; position: relative; overflow: hidden; }
        .promo-banner::before { content: '🎁'; font-size: 120px; position: absolute; left: -20px; top: -30px; opacity: 0.1; transform: rotate(-15deg); }
        .promo-banner h3 { color: #00ff88; font-size: 32px; font-weight: 800; margin-bottom: 15px; }
        .promo-banner p { font-size: 18px; color: #fff; margin-bottom: 25px; line-height: 1.5; }
        
        /* Stats & Features */
        .stats { padding: 60px 0 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; text-align: center; }
        .stat-item h3 { font-size: 48px; font-weight: 700; color: #00ff88; margin-bottom: 10px; }
        .stat-item p { font-size: 18px; color: rgba(255, 255, 255, 0.7); }
        
        .features { padding: 100px 0; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 48px; font-weight: 700; margin-bottom: 20px; }
        .section-header p { font-size: 20px; color: rgba(255, 255, 255, 0.7); max-width: 700px; margin: 0 auto; }
        
        /* Ajuste de Grid a 3 columnas para que quepan 6 tarjetas bien */
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .feature-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 30px; transition: all 0.3s ease; position: relative; overflow: hidden; height: 100%; display: flex; flex-direction: column; }
        .feature-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.05); border-color: rgba(0, 255, 136, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .feature-icon { width: 70px; height: 70px; background: linear-gradient(135deg, rgba(0, 255, 136, 0.15) 0%, rgba(0, 168, 255, 0.15) 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; border: 1px solid rgba(0, 255, 136, 0.2); }
        .feature-icon svg { width: 35px; height: 35px; fill: #00ff88; }
        .feature-card h3 { font-size: 22px; margin-bottom: 15px; color: #fff; }
        .feature-card p { color: rgba(255, 255, 255, 0.7); line-height: 1.6; font-size: 15px; flex-grow: 1; }
        .feature-list { list-style: none; margin-top: 15px; padding: 0; }
        .feature-list li { font-size: 14px; color: #ccc; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .feature-list li::before { content: '✔'; color: #00ff88; font-weight: bold; }

        /* Footer Renovado */
        .footer { padding: 60px 0 40px; border-top: 1px solid rgba(255, 255, 255, 0.1); background: #050505; }
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

        .footer-bottom { text-align: center; color: rgba(255, 255, 255, 0.4); font-size: 14px; border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 20px; }
        
        @media (max-width: 900px) { 
            .hero-content { grid-template-columns: 1fr; text-align: center; } 
            .hero-visual { order: -1; } 
            .hero-buttons { justify-content: center; } 
            .cloud-logos { justify-content: center; }
            .nav-buttons { display: none; }
            .footer-content { grid-template-columns: 1fr; text-align: center; }
            .footer-telegram { flex-direction: column; text-align: center; }
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
                <div class="nav-buttons">
                    <a href="/login.php" class="btn btn-secondary">Iniciar Sesión</a>
                    <a href="/register.php" class="btn btn-primary">Registrarse</a>
                </div>
            </nav>
        </div>
    </header>
    
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h2>Sube tus videos a múltiples storage</h2>
                    <p>Disfruta de la mejor seguridad, velocidad y estabilidad, utilizando infraestructuras profesionales de alto rendimiento. Tu contenido estará siempre disponible, protegido y optimizado para una carga ultra rápida.</p>
                    
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
                            Ver Planes
                        </a>
                        <a href="/register.php" class="btn btn-secondary">Crear Cuenta</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="video-wrapper">
                        <iframe src="https://xv.xzorra.net/embed.php?v=AiJzTEobIY" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
            </div>

            <div class="promo-banner">
                <h3>🎁 Promoción Especial – 1 Mes Gratis</h3>
                <p>¿Quieres un mes totalmente GRATIS? <br>Compártenos en <strong>Facebook o Reddit</strong> y recibe 1 mes de membresía sin costo.</p>
                <a href="#footer-contact" class="btn btn-primary">Contactar para reclamar</a>
            </div>

        </div>
    </section>
    
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?= number_format($totalUsers) ?>+</h3>
                    <p>Usuarios Activos</p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format($totalVideos) ?>+</h3>
                    <p>Videos Premium</p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format($totalViews) ?>+</h3>
                    <p>Reproducciones</p>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2>¿Por qué elegirnos?</h2>
                <p>La mejor plataforma de streaming y almacenamientos diseñada para creadores exigentes.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    </div>
                    <h3>1. Seguridad Total</h3>
                    <p>Tu contenido está protegido con la mejor tecnología de encriptación y sistemas de autenticación de vanguardia.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/></svg>
                    </div>
                    <h3>2. Velocidad Rayo</h3>
                    <p>Servidores optimizados para una entrega de contenido instantánea y sin interrupciones tipo buffering.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    </div>
                    <h3>3. Hotlink Protection</h3>
                    <p>Tendrás el control total sobre qué dominios pueden reproducir tus videos. Evita que terceros roben o incrusten tu contenido sin autorización.</p>
                    <ul class="feature-list">
                        <li>Control de dominios autorizados</li>
                        <li>Protección de ancho de banda</li>
                        <li>Seguridad anti-robo</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                    </div>
                    <h3>4. Mis Conexiones</h3>
                    <p>Nadie sabrá dónde están realmente alojados tus videos. Nuestro sistema protege tu infraestructura.</p>
                    <ul class="feature-list">
                        <li>Oculta ubicaciones S3 / S4</li>
                        <li>Protege credenciales de conexión</li>
                        <li>Mantiene tu nube privada</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
                    </div>
                    <h3>5. Optimización SEO</h3>
                    <p>Al no alojar videos en tu hosting web principal, tu sitio web volará en los buscadores.</p>
                    <ul class="feature-list">
                        <li>Servidor consume menos recursos</li>
                        <li>Aumenta velocidad de tu web</li>
                        <li>Mejora experiencia de usuario</li>
                    </ul>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/></svg>
                    </div>
                    <h3>6. Multi-dispositivo</h3>
                    <p>Disfruta y administra tu contenido en tu teléfono móvil, tablet o computadora con nuestro diseño 100% adaptable y moderno.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pricing-section" id="plans">
        <div class="container">
            <div class="section-header">
                <h2>Planes de Membresía</h2>
                <p>Elige el plan perfecto para ti y disfruta sin límites</p>
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
                        <li>
                            <div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                            Acceso Total al Contenido
                        </li>
                        <li>
                            <div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                            Reproducción HD/4K
                        </li>
                        <li>
                            <div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                            Sin Anuncios
                        </li>
                        <?php if ($plan['duration_days'] > 30): ?>
                        <li>
                            <div class="check-icon"><svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg></div>
                            Soporte VIP 24/7
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <a href="/register.php" class="btn-plan <?= $isPopular ? 'btn-glow' : 'btn-outline' ?>">
                        <?= $isPopular ? 'EMPEZAR AHORA' : 'Crear Cuenta' ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <footer class="footer" id="footer-contact">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h4>Información de Ayuda</h4>
                    <div style="display: flex; gap: 40px;">
                        <ul class="footer-links">
                            <li style="color: #00ff88; font-weight: bold; margin-bottom:15px;">Preguntas Frecuentes</li>
                            <li><a href="#">Almacenamiento S3 / S4</a></li>
                            <li><a href="#">Seguridad y protección</a></li>
                            <li><a href="#">Planes de membresía</a></li>
                            <li><a href="#">Métodos de pago</a></li>
                            <li><a href="#">Soporte técnico</a></li>
                        </ul>
                        <ul class="footer-links">
                            <li style="color: #00ff88; font-weight: bold; margin-bottom:15px;">Contáctanos</li>
                            <li><a href="#">Formulario de contacto</a></li>
                            <li><a href="mailto:soporte@earnvids.com">soporte@earnvids.com</a></li>
                            <li><a href="https://t.me/+NKA-jqALfSBmZjBh" target="_blank">Chat de Telegram</a></li>
                        </ul>
                    </div>
                </div>

                <div class="footer-col">
                    <div class="footer-telegram">
                        <img src="/assets/img/qr-telegram.png" alt="Código QR Telegram">
                        <div class="telegram-info">
                            <h4 style="margin-bottom: 5px; color:#00a8ff;">Comunidad Oficial</h4>
                            <p>Únete a nuestro grupo oficial de Telegram para soporte directo, actualizaciones y promociones exclusivas.</p>
                            <a href="https://t.me/+NKA-jqALfSBmZjBh" target="_blank" class="btn-telegram">👉 Unirme a Telegram</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    
    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 50) header.classList.add('scrolled');
            else header.classList.remove('scrolled');
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
