<?php
// admin/index.php - Dashboard Rediseñado con Pestañas (Conexiones y Tutoriales)
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php'; 
requireLogin();

$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();
$stats = $isAdmin ? getGlobalStats() : getUserStats($userId);

// --- LÓGICA DE USUARIO Y MEMBRESÍA ---
$stmt = db()->prepare("SELECT username, membership_expiry, license_key, license_status FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

$daysRemaining = null;
$showExpiryWarning = false;

if ($userData && !empty($userData['membership_expiry'])) {
    $expiryTimestamp = strtotime($userData['membership_expiry']);
    $currentTimestamp = time();
    
    if ($expiryTimestamp > $currentTimestamp) {
        $diff = $expiryTimestamp - $currentTimestamp;
        $daysRemaining = floor($diff / (60 * 60 * 24));
        
        if ($daysRemaining <= 3) {
            $showExpiryWarning = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        /* --- ESTILOS DE PESTAÑAS (TABS) --- */
        .dash-tabs { display: flex; gap: 15px; border-bottom: 2px solid var(--border-color); margin-bottom: 25px; padding-bottom: 0; overflow-x: auto; }
        .tab-btn { background: transparent; border: none; color: var(--text-secondary); font-size: 16px; font-weight: 600; padding: 10px 5px; cursor: pointer; position: relative; transition: color 0.3s; white-space: nowrap; }
        .tab-btn:hover { color: #fff; }
        .tab-btn.active { color: var(--accent-green); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px; background: var(--accent-green); border-radius: 3px 3px 0 0; }
        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* --- DISEÑO VISTA GENERAL --- */
        .dashboard-wrapper { display: flex; flex-direction: column; gap: 25px; }
        .welcome-banner { background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0,0,0,0.4) 100%); border: 1px solid rgba(0, 255, 136, 0.2); border-radius: 16px; padding: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; overflow: hidden; }
        .welcome-banner::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(0, 255, 136, 0.05) 0%, transparent 60%); z-index: 0; pointer-events: none; }
        .welcome-text { position: relative; z-index: 1; }
        .welcome-text h2 { font-size: 26px; color: #fff; margin-bottom: 5px; font-weight: 700; }
        .welcome-text p { color: var(--text-secondary); font-size: 16px; margin: 0; }
        .welcome-actions { position: relative; z-index: 1; }
        .btn-upload-new { background: var(--accent-green); color: #000; padding: 12px 24px; border-radius: 8px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s, box-shadow 0.2s; border: none; cursor: pointer; font-size: 15px; }
        .btn-upload-new:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3); }

        .license-banner { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; transition: 0.3s; }
        .license-banner:hover { border-color: rgba(0, 255, 136, 0.4); box-shadow: 0 10px 20px rgba(0, 255, 136, 0.05); }
        .license-info h3 { color: #fff; margin-top: 0; margin-bottom: 10px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .license-info p { color: var(--text-secondary); font-size: 14px; margin-bottom: 15px; max-width: 500px; line-height: 1.5; }
        .license-key-box { display: flex; align-items: center; gap: 10px; background: #000; padding: 10px 15px; border-radius: 8px; border: 1px solid rgba(0, 255, 136, 0.2); margin-bottom: 15px; max-width: max-content; }
        .license-key-box span { font-family: monospace; color: var(--accent-green); font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .btn-copy { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold; transition: 0.2s; }
        .btn-copy:hover { background: var(--accent-green); color: #000; }
        .license-status { font-size: 14px; font-weight: 600; background: rgba(255,255,255,0.05); padding: 5px 12px; border-radius: 20px; display: inline-block; }
        
        .license-actions { display: flex; gap: 15px; flex-direction: column; min-width: 220px; }
        .btn-download { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 20px; border-radius: 8px; font-weight: bold; text-decoration: none; transition: 0.2s; border: 1px solid transparent; font-size: 14px; }
        .script-btn { background: rgba(0, 255, 136, 0.1); color: var(--accent-green); border-color: rgba(0, 255, 136, 0.3); }
        .script-btn:hover { background: var(--accent-green); color: #000; transform: translateY(-2px); }
        .plugin-btn { background: rgba(255, 255, 255, 0.05); color: #fff; border-color: rgba(255, 255, 255, 0.1); }
        .plugin-btn:hover { background: #fff; color: #000; transform: translateY(-2px); }

        .alert-banner { background: rgba(255, 170, 0, 0.1); border-left: 5px solid #ffaa00; border-radius: 8px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; }
        .alert-content { display: flex; align-items: center; gap: 15px; }
        .alert-icon { font-size: 24px; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255, 170, 0, 0.2); border-radius: 50%; color: #ffaa00; }
        .alert-text p { color: #fff; margin: 0; font-size: 15px; line-height: 1.4; }
        .alert-btn { background: #ffaa00; color: #000; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: all 0.2s; white-space: nowrap; }

        .modern-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .modern-stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 25px; display: flex; align-items: center; gap: 20px; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .modern-stat-card:hover { transform: translateY(-5px); border-color: rgba(0, 255, 136, 0.4); box-shadow: 0 10px 20px rgba(0, 255, 136, 0.05); }
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; justify-content: center; align-items: center; background: rgba(0, 255, 136, 0.1); border: 1px solid rgba(0, 255, 136, 0.2); flex-shrink: 0; }
        .icon-box svg { width: 28px; height: 28px; fill: var(--accent-green); }
        .stat-info { display: flex; flex-direction: column; justify-content: center; }
        .stat-info h3 { font-size: 28px; color: #fff; margin: 0 0 5px 0; font-weight: 700; line-height: 1; }
        .stat-info p { margin: 0; color: var(--text-secondary); font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- ESTILOS PARA CONEXIONES Y TUTORIALES --- */
        .guide-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 30px; margin-bottom: 25px; }
        .guide-card h2 { color: var(--accent-green); margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; font-size: 22px; }
        .guide-card h3 { color: #fff; margin-top: 25px; margin-bottom: 15px; font-size: 18px; }
        .guide-card p, .guide-card li { color: var(--text-secondary); line-height: 1.6; font-size: 15px; margin-bottom: 10px; }
        .guide-card ul { padding-left: 20px; margin-bottom: 20px; }
        .guide-img { display: block; max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color); margin: 15px 0; max-height: 400px; object-fit: cover; }
        .guide-card strong { color: #fff; }
        
        .tutorial-step { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .tutorial-step h3 { color: var(--accent-green); margin-top: 0; margin-bottom: 15px; font-size: 20px; }
        .tutorial-text { color: var(--text-secondary); margin-bottom: 15px; font-size: 15px; }
        .video-responsive { position: relative; padding-bottom: 56.25%; /* 16:9 */ height: 0; overflow: hidden; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 15px; background: #000; }
        .video-responsive iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .tutorial-submsg { color: #aaa; font-size: 14px; font-style: italic; background: rgba(255,255,255,0.02); padding: 10px 15px; border-radius: 6px; border-left: 3px solid var(--accent-green); }

        @media (max-width: 768px) {
            .welcome-banner, .license-banner { flex-direction: column; align-items: flex-start; text-align: left; gap: 20px; }
            .license-actions { width: 100%; }
            .dash-tabs { font-size: 14px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content" id="dashboard-page">
        
        <div class="dash-tabs">
            <button class="tab-btn active" onclick="openTab('tab-general', this)">Vista General</button>
            <button class="tab-btn" onclick="openTab('tab-conexiones', this)">Conexiones</button>
            <button class="tab-btn" onclick="openTab('tab-tutoriales', this)">Tutoriales</button>
        </div>

        <div id="tab-general" class="tab-content active">
            <?php if ($showExpiryWarning): 
                $textoDias = ($daysRemaining == 1) ? "1 día" : "$daysRemaining días";
                $mensajeAlerta = ($daysRemaining == 0) 
                    ? "⚠️ Tu membresía vence <strong>HOY</strong>. Renuévala ahora para seguir disfrutando del servicio." 
                    : "⚠️ Tu membresía vence en <strong>$textoDias</strong>. Renuévala ahora para seguir disfrutando del servicio.";
            ?>
            <div class="alert-banner">
                <div class="alert-content">
                    <div class="alert-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>
                    <div class="alert-text"><p><?= $mensajeAlerta ?></p></div>
                </div>
                <a href="/account.php" class="alert-btn">Renovar Membresía</a>
            </div>
            <?php endif; ?>

            <div class="dashboard-wrapper">
                <div class="welcome-banner">
                    <div class="welcome-text">
                        <h2>¡Hola, <?= htmlspecialchars($userData['username'] ?? 'Usuario') ?>! 👋</h2>
                        <p>Aquí tienes el resumen del rendimiento de tu contenido.</p>
                    </div>
                    <div class="welcome-actions">
                        <button class="btn-upload-new" onclick="location.href='/upload.php'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg> Subir Nuevo Video
                        </button>
                    </div>
                </div>

                <div class="license-banner">
                    <div class="license-info">
                        <h3><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4h2.35l2-2 2 2 2-2 2 2v-4h-8.35zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/></svg> Tu Licencia XVIDSPRO</h3>
                        <p>Utiliza esta clave única para activar el reproductor en tus sitios web. Recuerda que la licencia solo funcionará en los dominios que hayas agregado a tu lista blanca.</p>
                        
                        <div class="license-key-box">
                            <span id="myLicenseKey"><?= htmlspecialchars($userData['license_key'] ?? 'No asignada') ?></span>
                            <button class="btn-copy" onclick="copyToClipboard('<?= htmlspecialchars($userData['license_key'] ?? '') ?>')">Copiar</button>
                        </div>
                        
                        <div class="license-status">
                            Estado de Licencia: 
                            <?php if($userData['license_status'] == 'active'): ?> <span style="color: #00ff88;">Activa ✅</span>
                            <?php elseif($userData['license_status'] == 'suspended'): ?> <span style="color: #ffaa00;">Suspendida ⚠️</span>
                            <?php else: ?> <span style="color: #ff3b3b;">Inactiva ❌</span> <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="license-actions">
                        <a href="/downloads/script_xvidspro.zip" target="_blank" class="btn-download script-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Descargar Script
                        </a>
                        <a href="/downloads/plugin_wordpress.zip" target="_blank" class="btn-download plugin-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg> Descargar WP Plugin
                        </a>
                    </div>
                </div>
                
                <div class="modern-stats-grid">
                    <?php if ($isAdmin): ?>
                    <div class="modern-stat-card">
                        <div class="icon-box"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
                        <div class="stat-info"><h3><?= number_format($stats['total_users'] ?? 0) ?></h3><p>Usuarios</p></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="modern-stat-card">
                        <div class="icon-box"><svg viewBox="0 0 24 24"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg></div>
                        <div class="stat-info"><h3><?= number_format($stats['total_videos'] ?? 0) ?></h3><p>Videos Totales</p></div>
                    </div>
                    
                    <div class="modern-stat-card">
                        <div class="icon-box"><svg viewBox="0 0 24 24"><path d="M3 15h18v-2H3v2zm0 4h18v-2H3v2zm0-8h18V9H3v2zm0-6v2h18V5H3z"/></svg></div>
                        <div class="stat-info"><h3><?= formatFileSize($stats['total_storage'] ?? 0) ?></h3><p>Almacenamiento</p></div>
                    </div>
                    
                    <div class="modern-stat-card">
                        <div class="icon-box"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg></div>
                        <div class="stat-info"><h3><?= number_format($stats['views_today'] ?? 0) ?></h3><p>Vistas Hoy</p></div>
                    </div>
                </div>
            </div>
        </div>

        <div id="tab-conexiones" class="tab-content">
            
            <div class="guide-card">
                <h2>🔹 Guía para Configurar Cloudflare R2 y Conectar con XVIDSPRO</h2>
                
                <h3>✅ Paso 1: Crear un Bucket</h3>
                <ul>
                    <li>Ingrese a su cuenta de Cloudflare.</li>
                    <li>Diríjase a <strong>R2 → Crear un depósito (Bucket)</strong>.</li>
                    <li>Cree un nuevo bucket vacío.</li>
                </ul>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare11.PNG" alt="Cloudflare Referencia 1" class="guide-img">

                <h3>✅ Paso 2: Configurar el Bucket</h3>
                <p>En la pantalla de creación:</p>
                <ul>
                    <li><strong>Bucket name:</strong> Escriba el nombre que desee para su bucket. Ejemplo: <code>prueba22</code> (puede ser cualquier nombre).</li>
                    <li><strong>Ubicación:</strong> Seleccione 🔘 <strong>Automático ✅ (RECOMENDADO)</strong>. Esto permite que Cloudflare optimice la ubicación automáticamente.</li>
                    <li><strong>Clase de almacenamiento:</strong> Seleccione 🔘 <strong>Estándar ✅ (RECOMENDADO)</strong>. Ideal para videos y contenido con acceso frecuente.</li>
                </ul>
                <p>Luego haga clic en <strong>Crear depósito</strong>.</p>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare22.PNG" alt="Cloudflare Referencia 2" class="guide-img">

                <h3>✅ Paso 3: Crear las Access Key (Claves de acceso)</h3>
                <p>Después de crear el bucket, en el panel derecho, ubique la sección <strong>👉 Detalles de la cuenta</strong>. Ahí verá: API Tokens, ID de cuenta, API de S3.</p>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare33.PNG" alt="Cloudflare Referencia 3" class="guide-img">

                <p><strong>🔹 Crear el Token</strong></p>
                <ul>
                    <li>Haga clic en <strong>Administrar</strong> dentro de API Tokens.</li>
                    <li>Luego seleccione <strong>Crear token</strong>.</li>
                </ul>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare44.PNG" alt="Cloudflare Referencia 4" class="guide-img">

                <p><strong>🔹 Seleccionar Permisos Correctos</strong></p>
                <ul>
                    <li>Elija la opción: 🔘 <strong>Object Read & Write ✅ (OPCIÓN CORRECTA)</strong>. Esto permitirá Subir archivos, Leer archivos, Listar archivos.</li>
                    <li>Luego seleccione: 🔘 <strong>Aplicar solo a buckets específicos</strong>. Y elija el bucket que creó anteriormente (ejemplo: <code>prueba22</code>).</li>
                </ul>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare55.PNG" alt="Cloudflare Referencia 5" class="guide-img">
                <p>Después haga clic en <strong>Crear token</strong>.</p>

                <h3>✅ Paso 4: Guardar las Claves en XVIDSPRO</h3>
                <p>Una vez creado el token, el sistema le mostrará:</p>
                <ul>
                    <li>ID de clave de acceso (Access Key ID)</li>
                    <li><strong>Secret Access Key ⚠️ (GUÁRDELA, solo se muestra una vez)</strong></li>
                </ul>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare66.PNG" alt="Cloudflare Referencia 6" class="guide-img">

                <p><strong>🔹 Ingresar los datos en el sistema</strong></p>
                <ul>
                    <li>Copie el Access Key ID.</li>
                    <li>Copie el Secret Access Key.</li>
                    <li>Ingréselos en la configuración del sistema XVIDSPRO.</li>
                    <li>Haga clic en <strong>Guardar datos</strong>.</li>
                </ul>
                <p>📷 <strong>Referencia:</strong></p>
                <img src="/assets/img/cloudflare77.PNG" alt="Cloudflare Referencia 7" class="guide-img">
                <p>El sistema verificará automáticamente la conexión y le indicará si los datos son correctos o incorrectos.</p>
            </div>

            <div class="guide-card">
                <h2>🔹 Guía paso a paso para agregar las Access Key de Backblaze en XVIDSPRO</h2>
                <p>A continuación, te explicamos cómo configurar correctamente Backblaze B2 y conectar las credenciales en tu sistema XVIDSPRO.</p>

                <h3>✅ Paso 1: Crear una cuenta en Backblaze</h3>
                <ul>
                    <li>Ingresa a Backblaze.</li>
                    <li>Crea una cuenta nueva.</li>
                    <li>Verifica tu correo electrónico.</li>
                    <li>Accede al panel de control.</li>
                </ul>

                <h3>✅ Paso 2: Crear un Bucket</h3>
                <p>Una vez dentro del panel:</p>
                <ul>
                    <li>Ve a la sección <strong>B2 Cloud Storage</strong>.</li>
                    <li>Haz clic en <strong>Create a Bucket</strong>.</li>
                    <li>Asigna un nombre al bucket (ejemplo: <code>videos-storage</code>).</li>
                    <li>Selecciona el tipo Recomendado: <strong>Private</strong></li>
                    <li>Guarda los cambios.</li>
                </ul>
                <p>📷 <strong>Imagen de referencia:</strong></p>
                <img src="/assets/img/backblaze11.PNG" alt="Backblaze Referencia 1" class="guide-img">

                <h3>✅ Paso 3: Crear las Access Key (Application Keys)</h3>
                <p>Ahora debes generar las credenciales que se usarán en XVIDSPRO.</p>
                <ul>
                    <li>Ve al menú lateral y haz clic en <strong>Application Keys</strong>.</li>
                    <li>Selecciona <strong>Create New Application Key</strong>.</li>
                    <li>Configura lo siguiente:
                        <ul>
                            <li><strong>Key Name:</strong> (Ejemplo: xvidspro-key)</li>
                            <li><strong>Allow Access To:</strong> Only this bucket (Selecciona el bucket creado)</li>
                            <li><strong>Permisos recomendados:</strong> listFiles, readFiles, writeFiles, deleteFiles</li>
                        </ul>
                    </li>
                    <li>Haz clic en <strong>Create New Key</strong>.</li>
                </ul>
                <p>Copia los siguientes datos:</p>
                <ul>
                    <li><strong>keyID</strong> → Será tu Access Key</li>
                    <li><strong>applicationKey</strong> → Será tu Secret Key</li>
                </ul>
                <p>⚠️ <strong>Importante:</strong> El applicationKey solo se muestra una vez. Guárdalo en un lugar seguro.</p>
                <p>📷 <strong>Imágenes de referencia:</strong></p>
                <img src="/assets/img/backblaze22.PNG" alt="Backblaze Referencia 2" class="guide-img">
                <img src="/assets/img/backblaze33.PNG" alt="Backblaze Referencia 3" class="guide-img">

                <h3>✅ Paso 4: Agregar las credenciales en XVIDSPRO</h3>
                <p>Ahora debes configurar los datos en tu sistema.</p>
                <ul>
                    <li>Ve al panel de administración de XVIDSPRO.</li>
                    <li>Dirígete a Configuración de almacenamiento.</li>
                    <li>Selecciona <strong>Backblaze B2</strong> como proveedor.</li>
                    <li>Completa los campos con la información obtenida:
                        <ul>
                            <li><strong>Región:</strong> (Ejemplo: us-east-005)</li>
                            <li><strong>Endpoint URL:</strong> https://s3.us-east-005.backblazeb2.com</li>
                            <li><strong>Bucket:</strong> Nombre exacto del bucket</li>
                            <li><strong>Access Key:</strong> keyID generado</li>
                            <li><strong>Secret Key:</strong> applicationKey generado</li>
                        </ul>
                    </li>
                    <li>Haz clic en <strong>Verificar y Guardar</strong>.</li>
                </ul>
                <p>📷 <strong>Imágenes de referencia:</strong></p>
                <img src="/assets/img/backblaze44.PNG" alt="Backblaze Referencia 4" class="guide-img">
                <img src="/assets/img/backblaze55.PNG" alt="Backblaze Referencia 5" class="guide-img">
                <img src="/assets/img/backblaze66.PNG" alt="Backblaze Referencia 6" class="guide-img">
                <img src="/assets/img/backblaze77.PNG" alt="Backblaze Referencia 7" class="guide-img">
            </div>

        </div>

        <div id="tab-tutoriales" class="tab-content">
            
            <div class="tutorial-step">
                <h3>Paso 1</h3>
                <p class="tutorial-text">Aquí coloque cualquier texto.</p>
                <div class="video-responsive">
                    <iframe src="https://xv.xzorra.net/embed.php?v=AiJzTEobIY" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="tutorial-submsg">Aquí va el mensaje.</div>
            </div>

            <div class="tutorial-step">
                <h3>Paso 2</h3>
                <p class="tutorial-text">Aquí coloque cualquier texto.</p>
                <div class="video-responsive">
                    <iframe src="https://xv.xzorra.net/embed.php?v=AiJzTEobIY" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="tutorial-submsg">Aquí va el mensaje.</div>
            </div>

            <div class="tutorial-step">
                <h3>Paso 3</h3>
                <p class="tutorial-text">Aquí coloque cualquier texto.</p>
                <div class="video-responsive">
                    <iframe src="https://xv.xzorra.net/embed.php?v=AiJzTEobIY" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="tutorial-submsg">Aquí va el mensaje.</div>
            </div>

            <div class="tutorial-step">
                <h3>Paso 4</h3>
                <p class="tutorial-text">Aquí coloque cualquier texto.</p>
                <div class="video-responsive">
                    <iframe src="https://xv.xzorra.net/embed.php?v=AiJzTEobIY" frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="tutorial-submsg">Aquí va el mensaje.</div>
            </div>

        </div>

    </div>
    
    <script src="/assets/js/main.js"></script>
    
    <script>
        function openTab(tabId, btnElement) {
            // Ocultar todos los contenidos de pestañas
            let contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Quitar clase active de todos los botones
            let buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Mostrar la pestaña seleccionada
            document.getElementById(tabId).classList.add('active');
            btnElement.classList.add('active');
        }
    </script>
</body>
</html>
