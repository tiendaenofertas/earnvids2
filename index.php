<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Si está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: /admin/');
    exit;
}

// Obtener estadísticas
$stmt = db()->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$totalUsers = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COUNT(*) as total FROM videos WHERE status = 'active'");
$totalVideos = $stmt->fetch()['total'];

$stmt = db()->query("SELECT COALESCE(SUM(views), 0) as total FROM videos");
$totalViews = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Comparte Videos y Gana Dinero</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a0a;
            color: #fff;
            overflow-x: hidden;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 20px 0;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .header.scrolled {
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0, 255, 136, 0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #fff;
        }
        
        .logo svg {
            width: 40px;
            height: 40px;
            fill: #00ff88;
        }
        
        .logo h1 {
            font-size: 28px;
            font-weight: 700;
        }
        
        .logo span {
            color: #00ff88;
        }
        
        .nav-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00ff88 0%, #00cc6a 100%);
            color: #000;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(0, 255, 136, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 80% 50%, rgba(0, 168, 255, 0.1) 0%, transparent 50%);
            z-index: -1;
        }
        
        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .hero-text h2 {
            font-size: clamp(40px, 5vw, 60px);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, #00ff88 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-text p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .hero-visual {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .hero-image {
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .hero-image svg {
            width: 100%;
            height: auto;
        }
        
        /* Stats Section */
        .stats {
            padding: 40px 0;
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }
        
        .stat-item h3 {
            font-size: 48px;
            font-weight: 700;
            color: #00ff88;
            margin-bottom: 10px;
        }
        
        .stat-item p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Features Section */
        .features {
            padding: 100px 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .section-header p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #00ff88, #00a8ff);
            border-radius: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
        }
        
        .feature-card:hover::before {
            opacity: 0.1;
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.2) 0%, rgba(0, 168, 255, 0.2) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }
        
        .feature-icon svg {
            width: 40px;
            height: 40px;
            fill: #00ff88;
        }
        
        .feature-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .feature-card p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta {
            padding: 100px 0;
            text-align: center;
            position: relative;
        }
        
        .cta-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(0, 255, 136, 0.1) 0%, transparent 70%);
            z-index: -1;
        }
        
        .cta h2 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .cta p {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
        }
        
        /* Footer */
        .footer {
            padding: 40px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .hero-visual {
                order: -1;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                    <h1>EARN<span>VIDS</span></h1>
                </a>
                <div class="nav-buttons">
                    <a href="/login.php" class="btn btn-secondary">Iniciar Sesión</a>
                    <a href="/register.php" class="btn btn-primary">Registrarse</a>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h2>Comparte Videos y Gana Dinero</h2>
                    <p>Tus videos son almacenados de forma segura y rápida. Gana dinero real compartiendo tu contenido con el mundo.</p>
                    <div class="hero-buttons">
                        <a href="/register.php" class="btn btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Comenzar Ahora
                        </a>
                        <a href="#features" class="btn btn-secondary">Conocer Más</a>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-image">
                        <svg viewBox="0 0 500 400" xmlns="http://www.w3.org/2000/svg">
                            <!-- Nube -->
                            <path d="M350 100c0-27.614-22.386-50-50-50-5.217 0-10.24.81-14.96 2.302C276.295 31.514 254.87 20 230 20c-33.137 0-60 26.863-60 60 0 2.025.108 4.025.303 6-27.29 0-49.303 22.014-49.303 49.303 0 27.29 22.014 49.697 49.303 49.697h149.697c27.614 0 50-22.386 50-50s-22.386-50-50-50z" fill="#00ff88" opacity="0.2"/>
                            
                            <!-- Monitor -->
                            <rect x="150" y="150" width="200" height="150" rx="10" fill="#1a1a1a" stroke="#00ff88" stroke-width="2"/>
                            <rect x="160" y="160" width="180" height="120" rx="5" fill="#0a0a0a"/>
                            
                            <!-- Play button -->
                            <circle cx="250" cy="220" r="30" fill="#00ff88" opacity="0.2"/>
                            <path d="M240 205 v30 l25-15z" fill="#00ff88"/>
                            
                            <!-- Base del monitor -->
                            <rect x="220" y="300" width="60" height="40" fill="#1a1a1a"/>
                            <rect x="200" y="340" width="100" height="10" rx="5" fill="#1a1a1a"/>
                            
                            <!-- Elementos flotantes -->
                            <rect x="50" y="100" width="60" height="40" rx="5" fill="#00ff88" opacity="0.6">
                                <animateTransform attributeName="transform" type="translate" values="0,0; 0,-10; 0,0" dur="3s" repeatCount="indefinite"/>
                            </rect>
                            
                            <rect x="390" y="180" width="50" height="35" rx="5" fill="#00a8ff" opacity="0.6">
                                <animateTransform attributeName="transform" type="translate" values="0,0; 0,-15; 0,0" dur="4s" repeatCount="indefinite"/>
                            </rect>
                            
                            <!-- Iconos -->
                            <g transform="translate(60, 110)">
                                <rect width="40" height="5" fill="#fff" opacity="0.8"/>
                                <rect y="8" width="30" height="5" fill="#fff" opacity="0.6"/>
                                <rect y="16" width="35" height="5" fill="#fff" opacity="0.4"/>
                            </g>
                            
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
    
    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3><?= number_format($totalUsers) ?>+</h3>
                    <p>Usuarios Activos</p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format($totalVideos) ?>+</h3>
                    <p>Videos Subidos</p>
                </div>
                <div class="stat-item">
                    <h3><?= number_format($totalViews) ?>+</h3>
                    <p>Reproducciones</p>
                </div>
                <div class="stat-item">
                    <h3>100%</h3>
                    <p>Seguro y Confiable</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Nuestras Características</h2>
                <p>Todo lo que necesitas para compartir y monetizar tu contenido</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/>
                        </svg>
                    </div>
                    <h3>Almacenamiento en la Nube</h3>
                    <p>Tecnología HLS de última generación para streaming rápido. Múltiples servidores en la nube para redundancia y seguridad, asegurando una experiencia perfecta para tu contenido.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M13 3h-2v10h2V3zm4.83 2.17l-1.42 1.42C17.99 7.86 19 9.81 19 12c0 3.87-3.13 7-7 7s-7-3.13-7-7c0-2.19 1.01-4.14 2.58-5.42L6.17 5.17C4.23 6.82 3 9.26 3 12c0 4.97 4.03 9 9 9s9-4.03 9-9c0-2.74-1.23-5.18-3.17-6.83z"/>
                        </svg>
                    </div>
                    <h3>Velocidad Máxima</h3>
                    <p>Servidores de alto rendimiento optimizados para transferencias rápidas. Sin límites de velocidad, adaptándose automáticamente a tu conexión para la mejor experiencia.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                        </svg>
                    </div>
                    <h3>100% Seguro</h3>
                    <p>Encriptación de nivel militar con SSL/TLS. Protección 24/7 contra malware y virus. Tus archivos están seguros y protegidos con tecnología de punta.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/>
                        </svg>
                    </div>
                    <h3>Encoder Premium</h3>
                    <p>Tecnología de codificación avanzada que asegura distribución rápida y eficiente. Compatible con todos los dispositivos y navegadores modernos.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM6.5 9L10 5.5 13.5 9H11v4H9V9H6.5zm11 6L14 18.5 10.5 15H13v-4h2v4h2.5z"/>
                        </svg>
                    </div>
                    <h3>Sin Límites</h3>
                    <p>Almacenamiento y ancho de banda ilimitados. Sube todos los videos que quieras sin restricciones. Tu creatividad no tiene límites con nosotros.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                        </svg>
                    </div>
                    <h3>Reportes Completos</h3>
                    <p>Sistema detallado de reportes y estadísticas. Rastrea fácilmente tus descargas, vistas y ganancias. Toda la información que necesitas para tomar decisiones informadas.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-bg"></div>
        <div class="container">
            <h2>¿Listo para Empezar?</h2>
            <p>Únete a miles de creadores que ya están ganando dinero con sus videos</p>
            <a href="/register.php" class="btn btn-primary">Crear Cuenta Gratis</a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.</p>
        </div>
    </footer>
    
    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all feature cards
        document.querySelectorAll('.feature-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `all 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>
