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
        
        /* Hero Section */
        .hero { min-height: 100vh; display: flex; align-items: center; padding: 120px 0 80px; position: relative; overflow: hidden; }
        .hero-bg { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at 20% 50%, rgba(0, 255, 136, 0.15) 0%, transparent 50%), radial-gradient(circle at 80% 50%, rgba(0, 168, 255, 0.1) 0%, transparent 50%); z-index: -1; }
        .hero-content { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .hero-text h2 { font-size: clamp(40px, 5vw, 60px); font-weight: 800; line-height: 1.1; margin-bottom: 20px; background: linear-gradient(135deg, #fff 0%, #00ff88 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-text p { font-size: 20px; color: rgba(255, 255, 255, 0.7); margin-bottom: 40px; line-height: 1.6; }
        .hero-buttons { display: flex; gap: 20px; flex-wrap: wrap; }
        .hero-visual { position: relative; display: flex; justify-content: center; align-items: center; }
        .hero-image { width: 100%; max-width: 500px; position: relative; animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        
        /* Stats & Features */
        .stats { padding: 40px 0; background: rgba(255, 255, 255, 0.02); border-top: 1px solid rgba(255, 255, 255, 0.1); border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; text-align: center; }
        .stat-item h3 { font-size: 48px; font-weight: 700; color: #00ff88; margin-bottom: 10px; }
        .stat-item p { font-size: 18px; color: rgba(255, 255, 255, 0.7); }
        
        .features { padding: 100px 0; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-size: 48px; font-weight: 700; margin-bottom: 20px; }
        .section-header p { font-size: 20px; color: rgba(255, 255, 255, 0.7); }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; }
        .feature-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 30px; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .feature-card:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.05); }
        .feature-icon { width: 80px; height: 80px; background: linear-gradient(135deg, rgba(0, 255, 136, 0.2) 0%, rgba(0, 168, 255, 0.2) 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; margin-bottom: 25px; }
        .feature-icon svg { width: 40px; height: 40px; fill: #00ff88; }
        .feature-card h3 { font-size: 24px; margin-bottom: 15px; }
        .feature-card p { color: rgba(255, 255, 255, 0.7); line-height: 1.6; }

        .cta { padding: 100px 0; text-align: center; position: relative; }
        .cta-bg { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(circle at center, rgba(0, 255, 136, 0.1) 0%, transparent 70%); z-index: -1; }
        .cta h2 { font-size: 48px; margin-bottom: 20px; }
        .cta p { font-size: 20px; color: rgba(255, 255, 255, 0.7); margin-bottom: 40px; }
        
        .footer { padding: 40px 0; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center; color: rgba(255, 255, 255, 0.5); }
        
        @media (max-width: 768px) { 
            .hero-content { grid-template-columns: 1fr; text-align: center; } 
            .hero-visual { order: -1; } 
            .hero-buttons { justify-content: center; } 
            .nav-buttons { display: none; }
        }
    </style>
</head>
<body>
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="logo">
                    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                    <h1>EARN<span>VIDS</span></h1>
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
                    <h2>Contenido Exclusivo y Premium</h2>
                    <p>Accede a la mejor biblioteca de videos de alta calidad. Suscríbete hoy para desbloquear todo el potencial de nuestra plataforma.</p>
                    <div class="hero-buttons">
                        <a href="#plans" class="btn btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            Ver Planes
                        </a>
                        <a href="/register.php" class="btn btn-secondary">Crear Cuenta</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-image">
                        <svg viewBox="0 0 500 400" xmlns="http://www.w3.org/2000/svg">
                            <path d="M350 100c0-27.614-22.386-50-50-50-5.217 0-10.24.81-14.96 2.302C276.295 31.514 254.87 20 230 20c-33.137 0-60 26.863-60 60 0 2.025.108 4.025.303 6-27.29 0-49.303 22.014-49.303 49.303 0 27.29 22.014 49.697 49.303 49.697h149.697c27.614 0 50-22.386 50-50s-22.386-50-50-50z" fill="#00ff88" opacity="0.2"/>
                            <rect x="150" y="150" width="200" height="150" rx="10" fill="#1a1a1a" stroke="#00ff88" stroke-width="2"/>
                            <rect x="160" y="160" width="180" height="120" rx="5" fill="#0a0a0a"/>
                            <circle cx="250" cy="220" r="30" fill="#00ff88" opacity="0.2"/>
                            <path d="M240 205 v30 l25-15z" fill="#00ff88"/>
                            <g transform="translate(395, 190)">
                                <circle cx="20" cy="10" r="3" fill="#fff" opacity="0.8"/>
                                <path d="M10 10 l5-5 l5 5" stroke="#fff" stroke-width="2" fill="none" opacity="0.8"/>
                            </g>
                        </svg>
                    </div>
                </div>
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
    
    <section class="features">
        <div class="container">
            <div class="section-header">
                <h2>¿Por qué elegirnos?</h2>
                <p>La mejor plataforma de streaming y hosting</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    </div>
                    <h3>Seguridad Total</h3>
                    <p>Tu contenido está protegido con la mejor tecnología de encriptación.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/></svg>
                    </div>
                    <h3>Velocidad Rayo</h3>
                    <p>Servidores optimizados para una entrega de contenido instantánea.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14z"/></svg>
                    </div>
                    <h3>Multi-dispositivo</h3>
                    <p>Disfruta del contenido en tu móvil, tablet o computadora.</p>
                </div>
            </div>
        </div>
    </section>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
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
