<?php
declare(strict_types=1);

require_once __DIR__ . "/config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$self = $_SERVER["REQUEST_URI"];
$licenseKey = file_exists(LICENSE_FILE) ? trim(file_get_contents(LICENSE_FILE)) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_license'])) {
    $newLicense = trim($_POST['license_key']);
    $verify = checkLicenseStatus($newLicense);
    
    if ($verify['status'] === 'success') {
        file_put_contents(LICENSE_FILE, $newLicense);
        $_SESSION['license_last_check'] = time(); 
        header("Location: " . strtok($self, "?"));
        exit;
    } else {
        showLicenseForm($verify['message']);
        exit;
    }
}

if (empty($licenseKey)) {
    showLicenseForm();
    exit;
}

if (!isset($_SESSION['license_last_check']) || time() - $_SESSION['license_last_check'] > 1800) {
    $verify = checkLicenseStatus($licenseKey);
    if ($verify['status'] !== 'success') {
        @unlink(LICENSE_FILE); 
        unset($_SESSION["login"]); 
        showLicenseForm($verify['message']);
        exit;
    }
    $_SESSION['license_last_check'] = time();
}

const USERNAME = "admin";
const PASSWORD = "xxxx"; // <--- TU CONTRASEÑA
const SECRET_KEY_1 = "xzorra_key_elegant_2025";
const SECRET_KEY_2 = "premium_panel_access";

$hash = hash('sha256', SECRET_KEY_1 . PASSWORD . SECRET_KEY_2);

if (isset($_GET["logout"])) {
    unset($_SESSION["login"]);
    header("Location: " . strtok($self, "?"));
    exit;
}

if (isset($_SESSION["login"]) && $_SESSION["login"] === $hash) {
    showMainPanel();
} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {
    $u = htmlspecialchars(trim($_POST["username"] ?? ""));
    $p = $_POST["password"] ?? "";
    
    if ($u === USERNAME && $p === PASSWORD) {
        $_SESSION["login"] = $hash;
        header("Location: " . strtok($self, "?"));
        exit;
    } else {
        showLoginForm(true);
    }
} else {
    showLoginForm();
}

function showLicenseForm(string $errorMsg = ''): void {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Activación de Software</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Montserrat', sans-serif; background: radial-gradient(circle at top right, #1a1a1a, #000); min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #fff; }
            .login-box { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); padding: 3rem; border-radius: 24px; border: 1px solid rgba(0,255,136,0.2); width: 90%; max-width: 450px; box-shadow: 0 25px 50px -12px rgba(0,255,136,0.1); }
            .login-header { text-align: center; margin-bottom: 2rem; }
            .login-header i { font-size: 3.5rem; color: #00ff88; margin-bottom: 1rem; animation: pulse 2s infinite; }
            .login-header h2 { font-size: 1.5rem; margin-bottom: 10px; }
            .login-header p { font-size: 0.9rem; color: #94a3b8; }
            .input-group { position: relative; margin-bottom: 1.5rem; width: 100%; }
            .input-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
            input { width: 100%; padding: 1rem 1rem 1rem 3rem; background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; outline: none; font-size: 1rem; font-family: monospace; letter-spacing: 1px; }
            input:focus { border-color: #00ff88; }
            button { width: 100%; padding: 1rem; background: #00ff88; border: none; border-radius: 12px; color: #000; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 1.1rem; }
            button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0, 255, 136, 0.3); }
            .error { background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 0.9rem; }
            @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="login-header"><i class="fas fa-key"></i><h2>Licencia Requerida</h2><p>Ingresa tu clave de XVIDSPRO para activar este script en este dominio.</p></div>
            <?php if ($errorMsg): ?><div class="error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>
            <form method="post">
                <div class="input-group"><i class="fas fa-qrcode"></i><input type="text" name="license_key" placeholder="XVIDS-XXXXXXXXXXXX" required autocomplete="off"></div>
                <button type="submit" name="save_license">VERIFICAR Y ACTIVAR</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function showLoginForm(bool $showError = false): void {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Seguro</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <style>
            *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Montserrat', sans-serif; background: radial-gradient(circle at top right, #1e293b, #0f172a); min-height: 100vh; display: flex; align-items: center; justify-content: center; color: #fff; }
            .login-box { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); padding: 3rem; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); width: 90%; max-width: 420px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            .login-header { text-align: center; margin-bottom: 2.5rem; }
            .login-header i { font-size: 3.5rem; background: linear-gradient(135deg, #cba32a, #fbf2c0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 1rem; }
            .login-header h2 { font-family: 'Playfair Display', serif; margin: 0; font-size: 1.8rem; }
            .input-group { position: relative; margin-bottom: 1.5rem; width: 100%; }
            .input-group i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
            input { width: 100%; padding: 1rem 1rem 1rem 3rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 1rem; }
            input:focus { border-color: #cba32a; background: rgba(255,255,255,0.1); }
            button { width: 100%; padding: 1rem; background: linear-gradient(135deg, #cba32a, #b88a1e); border: none; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 1.1rem; }
            button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(212, 175, 55, 0.3); }
            .error { background: rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3); }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="login-header"><i class="fas fa-shield-halved"></i><h2>Acceso Privado</h2></div>
            <?php if ($showError): ?><div class="error"><i class="fas fa-circle-exclamation"></i> Datos incorrectos</div><?php endif; ?>
            <form method="post">
                <div class="input-group"><i class="fas fa-user"></i><input type="text" name="username" placeholder="Usuario" required autocomplete="off"></div>
                <div class="input-group"><i class="fas fa-lock"></i><input type="password" name="password" placeholder="Contraseña" required></div>
                <button type="submit" name="submit">INGRESAR</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function showMainPanel(): void {
    $csrfToken = function_exists('generateCSRFToken') ? generateCSRFToken() : '';
    $serverUrl = (isset($_SERVER["HTTPS"]) ? "https" : "http") . "://" . $_SERVER["SERVER_NAME"] . dirname($_SERVER["PHP_SELF"]);
    if (substr($serverUrl, -1) === '/') $serverUrl = rtrim($serverUrl, '/');

    // 🎨 Cargar Ajustes Actuales
    $settingsFile = __DIR__ . '/config/player_settings.json';
    $pSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
    
    $brandName = htmlspecialchars($pSettings['brand_name'] ?? 'XZORRA');
    $brandColor = htmlspecialchars($pSettings['brand_color'] ?? '#ff0000');
    $playColor = htmlspecialchars($pSettings['play_color'] ?? '#e50914');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panel Premium</title>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <style>
            *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
            :root { --bg: #f8fafc; --nav-bg: #0f172a; --accent: #cba32a; --card: #ffffff; --text: #1e293b; }
            body { font-family: 'Montserrat', sans-serif; background: var(--bg); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
            .navbar { background: var(--nav-bg); color: white; padding: 1rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
            .nav-container { max-width: 1000px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
            .brand { display: flex; align-items: center; gap: 12px; font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--accent); }
            .btn-logout { background: rgba(255,255,255,0.1); color: white; text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-size: 0.9rem; font-weight: bold; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
            .btn-logout:hover { background: #ef4444; }
            .main-container { max-width: 900px; width: 100%; margin: 40px auto; padding: 0 20px; flex: 1; }
            .page-header { text-align: center; margin-bottom: 3rem; }
            .page-header h2 { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: var(--text); margin-bottom: 0.5rem; }
            .card { background: var(--card); border-radius: 20px; padding: 2.5rem; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; margin-bottom: 2rem; width: 100%; }
            .card-title { display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; font-size: 1.2rem; font-weight: bold; color: var(--text); }
            .card-title i { color: var(--accent); font-size: 1.4rem; }
            .form-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
            @media(min-width: 768px) { .form-grid { grid-template-columns: 1fr 1fr; } }
            label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 0.95rem; }
            .input-box { position: relative; width: 100%; }
            .input-box i { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
            input[type="url"], input[type="text"], select { width: 100%; padding: 12px 12px 12px 45px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 1rem; transition: 0.3s; background: #f8fafc; outline: none; }
            input:focus, select:focus { border-color: var(--accent); background: white; }
            
            .color-picker-box { display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 6px; }
            input[type="color"] { width: 45px; height: 40px; border: none; cursor: pointer; border-radius: 8px; background: transparent; padding: 0; }
            input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
            input[type="color"]::-webkit-color-swatch { border: none; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
            .color-hex { font-family: monospace; font-weight: bold; font-size: 1.1rem; color: #475569; }

            .btn-submit { background: linear-gradient(135deg, var(--accent), #b08d26); color: white; border: none; padding: 1.2rem; width: 100%; border-radius: 14px; font-size: 1.2rem; font-weight: bold; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 25px -5px rgba(203, 163, 42, 0.4); display: flex; justify-content: center; gap: 10px; }
            .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 35px -5px rgba(203, 163, 42, 0.5); }
            
            .btn-save-brand { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4); margin-top: 1.5rem; }
            .btn-save-brand:hover { box-shadow: 0 15px 35px -5px rgba(16, 185, 129, 0.5); }

            .results { display: none; background: #f0f9ff; border: 2px solid var(--accent); }
            textarea { width: 100%; padding: 15px; border: 1px solid #cbd5e1; border-radius: 12px; min-height: 90px; font-family: monospace; margin-bottom: 10px; }
            .action-row { display: flex; gap: 10px; }
            .btn-copy { background: #334155; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; display: flex; gap: 8px; }
            .btn-test { background: var(--accent); }
            footer { text-align: center; padding: 2rem; color: #94a3b8; }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <div class="nav-container">
                <div class="brand"><i class="fas fa-shield-halved"></i> Panel Premium</div>
                <a href="?logout=true" class="btn-logout"><i class="fas fa-power-off"></i> Salir</a>
            </div>
        </nav>

        <main class="main-container">
            <div class="page-header">
                <h2>Protección de Video</h2>
                <p>Genera enlaces seguros y personaliza tu marca.</p>
            </div>

            <div class="card">
                <div class="card-title" style="color: #10b981;"><i class="fas fa-paint-brush" style="color: #10b981;"></i> Configuración Global del Reproductor</div>
                
                <form id="settingsForm">
                    <input type="hidden" name="action_type" value="save_settings">
                    
                    <div class="form-grid">
                        <div>
                            <label>Nombre de la Marca (Watermark)</label>
                            <div class="input-box"><i class="fas fa-tag"></i><input type="text" name="brand_name" value="<?= $brandName ?>" required></div>
                        </div>
                        <div>
                            <label>Color del Texto de la Marca</label>
                            <div class="color-picker-box">
                                <input type="color" name="brand_color" value="<?= $brandColor ?>" id="bc_input">
                                <span class="color-hex" id="bc_val"><?= strtoupper($brandColor) ?></span>
                            </div>
                        </div>
                        <div>
                            <label>Color del Botón Play y Barra</label>
                            <div class="color-picker-box">
                                <input type="color" name="play_color" value="<?= $playColor ?>" id="pc_input">
                                <span class="color-hex" id="pc_val"><?= strtoupper($playColor) ?></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit btn-save-brand" id="btnSettings">
                        <i class="fas fa-save"></i> GUARDAR CONFIGURACIÓN
                    </button>
                </form>
            </div>

            <form id="genForm">
                <div class="card">
                    <div class="card-title"><i class="fas fa-film"></i> Encriptar Video</div>
                    <div class="form-grid">
                        <div>
                            <label>URL del Video (MP4)</label>
                            <div class="input-box"><i class="fas fa-link"></i><input type="url" name="link" required placeholder="https://ejemplo.com/video.mp4"></div>
                        </div>
                        <div>
                            <label>Imagen Poster (Opcional)</label>
                            <div class="input-box"><i class="fas fa-image"></i><input type="url" name="poster" placeholder="https://ejemplo.com/portada.jpg"></div>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit" id="btnSubmit" style="margin-top:20px;">
                        <i class="fas fa-lock"></i> GENERAR ENLACE SEGURO
                    </button>
                </div>
            </form>

            <div class="card results" id="resCard">
                <div class="card-title" style="color: var(--accent); border-bottom: none;"><i class="fas fa-check-circle"></i> ¡Enlaces Generados!</div>
                <div style="margin-bottom: 2rem;">
                    <label>URL Directa del Reproductor:</label>
                    <textarea id="outUrl" readonly></textarea>
                    <div class="action-row">
                        <button class="btn-copy" onclick="copy('outUrl', this)"><i class="fas fa-copy"></i> Copiar</button>
                        <button class="btn-copy btn-test" id="btnTest"><i class="fas fa-play"></i> Probar Visual</button>
                    </div>
                </div>
                <div>
                    <label>Código Iframe (Embed):</label>
                    <textarea id="outIframe" readonly></textarea>
                    <button class="btn-copy" onclick="copy('outIframe', this)"><i class="fas fa-code"></i> Copiar Código</button>
                </div>
            </div>
        </main>

        <footer>&copy; <?= date('Y') ?> XVIDSPRO. Todos los derechos reservados.</footer>

        <script>
            $('#bc_input').on('input', function() { $('#bc_val').text($(this).val().toUpperCase()); });
            $('#pc_input').on('input', function() { $('#pc_val').text($(this).val().toUpperCase()); });

            $('#settingsForm').submit(function(e){
                e.preventDefault();
                let btn = $('#btnSettings');
                let old = btn.html();
                btn.html('<i class="fas fa-circle-notch fa-spin"></i> GUARDANDO...').prop('disabled',true);

                $.ajax({
                    url: 'action.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function() {
                        btn.html('<i class="fas fa-check"></i> ¡CONFIGURACIÓN GUARDADA!').css('background', '#059669');
                        setTimeout(() => { btn.html(old).prop('disabled',false).css('background', ''); }, 2000);
                    },
                    error: function(xhr) {
                        btn.html(old).prop('disabled',false);
                        let msg = "Error al guardar.";
                        try { let r = JSON.parse(xhr.responseText); if(r.error) msg = r.error; } catch(e){}
                        alert(msg);
                    }
                });
            });

            $('#genForm').submit(function(e){
                e.preventDefault();
                let btn = $('#btnSubmit');
                let old = btn.html();
                btn.html('<i class="fas fa-circle-notch fa-spin"></i> PROCESANDO...').prop('disabled',true);
                $('#resCard').slideUp();

                $.ajax({
                    url: 'action.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(d) {
                        btn.html(old).prop('disabled',false);
                        try {
                            let data = (typeof d === 'string') ? d : (d.data || d);
                            if(typeof d === 'object' && d.error) throw new Error(d.error);

                            let base = "<?= $serverUrl ?>";
                            let link = `${base}/embed.php?v=${data}`;
                            let iframe = `<iframe src="${link}" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>`;

                            $('#outUrl').val(link); $('#outIframe').val(iframe);
                            $('#btnTest').data('url', link);
                            $('#resCard').slideDown();
                            $('html,body').animate({scrollTop: $('#resCard').offset().top - 50}, 500);
                        } catch(err) { alert('Error: ' + err.message); }
                    },
                    error: function() {
                        btn.html(old).prop('disabled',false);
                        alert("Error de conexión. Revisa que el enlace sea correcto.");
                    }
                });
            });

            function copy(id, el) {
                document.getElementById(id).select(); document.execCommand('copy');
                let t = el.innerHTML; el.innerHTML = '<i class="fas fa-check"></i> Copiado'; el.style.background = '#10b981';
                setTimeout(()=>{ el.innerHTML = t; el.style.background = ''; }, 2000);
            }

            $('#btnTest').click(function(){ window.open($(this).data('url'), '_blank', 'width=1000,height=600'); });
        </script>
    </body>
    </html>
    <?php
}
?>
