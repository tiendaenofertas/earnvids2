<?php
if (!defined('ABSPATH')) exit;

class XvidsPro_Public {

    public function __construct() {
        add_action('parse_request', [$this, 'handle_endpoints']);
    }

    public function handle_endpoints($wp) {
        if (isset($wp->query_vars['xvid_embed']) || isset($wp->query_vars['xvid_stream']) || isset($wp->query_vars['xvid_download']) || isset($wp->query_vars['xvid_force_dl'])) {
            if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
        }

        if (isset($wp->query_vars['xvid_embed'])) { $this->render_player(sanitize_text_field($wp->query_vars['xvid_embed'])); exit; }
        if (isset($wp->query_vars['xvid_download'])) { $this->render_download(sanitize_text_field($wp->query_vars['xvid_download'])); exit; }
        if (isset($wp->query_vars['xvid_stream'])) { $this->render_stream(sanitize_text_field($wp->query_vars['xvid_stream'])); exit; }
        if (isset($wp->query_vars['xvid_force_dl'])) { $this->force_download(sanitize_text_field($wp->query_vars['xvid_force_dl'])); exit; }
    }

    // =================================================================================
    // 🚀 DESCARGA OPTIMIZADA POR REDIRECCIÓN (Sin colapsos ni errores 504)
    // =================================================================================
    public function force_download($id) {
        $license = get_option('xvidspro_license_key', '');
        if (empty($license)) wp_die("Licencia no configurada.", "Acceso Denegado", ['response' => 403]);

        $videoData = get_option('xv_vid_' . $id);
        if (!$videoData || empty($videoData['link'])) wp_die("Enlace no encontrado.", "Error", ['response' => 404]);

        $url = str_replace(
            ['/api/download.php?v=', '/embed.php?v=', '/stream.php?v='], 
            '/s3-proxy.php?v=', 
            $videoData['link']
        );

        // --- 🛡️ INICIO BLINDAJE POR TOKEN DE IP PARA DESCARGA DIRECTA ---
        $viewer_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $viewer_ip = trim(explode(',', $viewer_ip)[0]);
        
        if (strpos($url, 's3-proxy') !== false || strpos($url, 'sdnx') !== false || strpos($url, 'stream.php') !== false) {
            $parsed_url = parse_url($url);
            parse_str($parsed_url['query'] ?? '', $query_params);
            
            if (isset($query_params['v'])) {
                $embedCode = $query_params['v'];
                $expires = time() + 10800; // Expira en 3 horas
                $secret_key = "TokeN_Sup3r_S3gur0_XvidsPr0_2026!"; // CLAVE MAESTRA
                
                $token = hash_hmac('sha256', $embedCode . $viewer_ip . $expires, $secret_key);
                $baseUrl = explode('?', $url)[0];
                
                // Agregamos el parámetro &dl=1 para indicar que es una descarga
                $url = $baseUrl . '?v=' . $embedCode . '&e=' . $expires . '&t=' . $token . '&dl=1';
            }
        }
        // --- 🛡️ FIN BLINDAJE ---

        // Redirección ultra rápida. Evitamos usar fopen() que satura la RAM del servidor.
        header("Location: " . $url, true, 302);
        exit;
    }

    // =================================================================================
    // 🛡️ REPRODUCTOR (CON 100% DE FUNCIONES DE SEGURIDAD)
    // =================================================================================
    public function render_player($id) {
        $license = get_option('xvidspro_license_key', '');
        if (empty($license)) { 
            wp_die("Licencia de XVIDSPRO no configurada o suspendida.", "Acceso Denegado", ['response' => 403]); 
        }

        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'");

        $videoData = get_option('xv_vid_' . $id);
        if (!$videoData || empty($videoData['link'])) { 
            wp_die("Video no encontrado o enlace caducado.", "Error", ['response' => 404]); 
        }

        $settings = get_option('xvidspro_settings', [
            'brand_name'=>'XZORRA', 
            'brand_color'=>'#00ff88', 
            'play_color'=>'#00ff88', 
            'autoplay' => 0, 
            'enable_download' => 0, 
            'show_player_dl_icon' => 1
        ]);

        $streamUrl = site_url('/?xvid_stream=' . $id);
        $baseVideoDomain = parse_url($videoData['link'], PHP_URL_SCHEME) . '://' . parse_url($videoData['link'], PHP_URL_HOST);
        $encodedUrl = base64_encode($streamUrl);
        $autoplayAttr = !empty($settings['autoplay']) ? 'autoplay muted' : '';
        
        $downloadBtn = '';
        if (!empty($settings['enable_download']) && !empty($settings['show_player_dl_icon'])) {
            $dlUrl = site_url('/?xvid_download=' . $id);
            $downloadBtn = '<a href="'.esc_url($dlUrl).'" target="_blank" class="wm-dl-br" title="Descargar Video">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
            </a>';
        }
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <title>Reproductor <?php echo esc_attr($settings['brand_name']); ?></title>
            <meta name="robots" content="noindex, nofollow">
            <link rel="preconnect" href="<?php echo esc_url($baseVideoDomain); ?>" crossorigin>
            
            <script>
                if (window.top !== window.self) {
                    try {
                        if (window.top.location.hostname !== window.location.hostname) { throw new Error("CORS Frame"); }
                    } catch (e) {
                        document.documentElement.innerHTML = '<body style="background:#000; display:flex; justify-content:center; align-items:center; height:100vh; margin:0;"><div style="text-align:center; padding: 40px; background:#111; border: 1px solid #333; border-radius:10px; font-family:sans-serif;"><h2 style="color:#ff3b3b; margin:0 0 10px 0;">Dominio No Autorizado</h2><p style="color:#fff; margin:0;">Este video solo puede reproducirse en dominios autorizados.</p></div></body>';
                        throw new Error("Ejecución detenida por seguridad.");
                    }
                }
            </script>
            
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                html, body { width:100%; height:100%; background:#000; overflow:hidden; font-family:system-ui,-apple-system,sans-serif; -webkit-tap-highlight-color:transparent; }
                .pw { position:relative; width:100%; height:100%; display:flex; justify-content:center; align-items:center; background:#000; }
                video { width:100%; height:100%; object-fit:contain; display:block; }
                
                .wm { position:absolute; top:25px; left:25px; display:flex; align-items:center; gap:10px; padding:6px 14px; background:rgba(12,12,18,.8); border:1px solid rgba(255,255,255,.1); border-radius:10px; backdrop-filter:blur(4px); z-index:20; pointer-events:none; box-shadow:0 4px 15px rgba(0,0,0,.5); }
                .wm-i { font-size:16px; }
                .wm-t { font-family:Impact,sans-serif; font-size:20px; color:<?php echo esc_attr($settings['brand_color']); ?>; letter-spacing:1px; text-transform:uppercase; text-shadow:0 2px 4px rgba(0,0,0,.8); font-weight:700; }
                
                .wm-dl-br { position:absolute; bottom:25px; right:25px; color:#fff; opacity:0.7; transition:0.3s; display:flex; align-items:center; justify-content:center; text-decoration:none; padding:10px; border-radius:12px; background:rgba(12,12,18,.8); border:1px solid rgba(255,255,255,.1); backdrop-filter:blur(4px); z-index:20; pointer-events:auto; box-shadow:0 4px 15px rgba(0,0,0,.5); }
                .wm-dl-br:hover { opacity:1; color:<?php echo esc_attr($settings['play_color']); ?>; transform:scale(1.1); background:rgba(255,255,255,0.2); }
                
                .po { position:absolute; top:0; left:0; width:100%; height:100%; display:flex; justify-content:center; align-items:center; background:rgba(0,0,0,.3); cursor:pointer; z-index:10; transition:opacity .2s; }
                .pc { width:80px; height:80px; border-radius:50%; background:rgba(30,30,30,.7); border:2px solid rgba(255,255,255,.5); backdrop-filter:blur(2px); display:flex; justify-content:center; align-items:center; box-shadow:0 0 20px rgba(0,0,0,.5); transition:transform .2s; }
                .pt { width:0; height:0; border-top:16px solid transparent; border-bottom:16px solid transparent; border-left:28px solid <?php echo esc_attr($settings['play_color']); ?>; margin-left:6px; filter:drop-shadow(0 2px 4px rgba(0,0,0,.5)); }
                
                @media(hover:hover) { .po:hover .pc { transform:scale(1.1); background:rgba(50,50,50,.8); border-color:#fff; } }
                .is-p .po { opacity:0; visibility:hidden; pointer-events:none; }
                
                @media(max-width:768px) { 
                    .wm { top:15px; left:15px; padding:5px 10px; }
                    .wm-t { font-size:16px; }
                    .pc { width:64px; height:64px; }
                    .pt { border-top-width:12px; border-bottom-width:12px; border-left-width:22px; margin-left:4px; } 
                    .wm-dl-br { bottom:15px; right:15px; padding:8px; }
                }
            </style>
        </head>
        <body oncontextmenu="return false;">
            <div class="pw" id="wrp">
                <div class="wm">
                    <div class="wm-i">🛡️</div>
                    <div class="wm-t"><?php echo esc_html($settings['brand_name']); ?></div>
                </div>
                
                <div class="po" id="btn"><div class="pc"><div class="pt"></div></div></div>
                
                <?php echo $downloadBtn; ?>
                
                <video id="vid" playsinline webkit-playsinline preload="metadata" controlsList="nodownload" <?php echo $autoplayAttr; ?> <?php echo !empty($videoData['poster']) ? 'poster="'.esc_url($videoData['poster']).'"' : ''; ?>>
                </video>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const v = document.getElementById('vid'), b = document.getElementById('btn'), w = document.getElementById('wrp');
                    
                    const _0x1a2b = atob('<?php echo $encodedUrl; ?>');
                    setTimeout(() => {
                        if(!v.src) { v.src = _0x1a2b; v.load(); }
                    }, 100);

                    const play = () => {
                        v.play().then(() => { 
                            w.classList.add('is-p'); v.controls = true; 
                        }).catch(e => { 
                            v.muted = true; 
                            v.play().then(() => { w.classList.add('is-p'); v.controls = true; v.muted = false; }); 
                        });
                    };
                    
                    b.addEventListener('click', play);
                    v.addEventListener('play', () => { w.classList.add('is-p'); v.controls = true; });

                    // SEGURIDAD: Trampa Anti-DevTools
                    setInterval(function() {
                        const before = new Date().getTime();
                        debugger;
                        const after = new Date().getTime();
                        if (after - before > 100) {
                            document.body.innerHTML = "<div style='display:flex; height:100vh; background:#000; align-items:center; justify-content:center;'><h2 style='color:#ff3b3b; font-family:sans-serif;'>Acceso Denegado. Herramientas de depuración detectadas.</h2></div>";
                        }
                    }, 1000);

                    // SEGURIDAD: Bloqueo de Teclas de Inspección
                    document.addEventListener('keydown', e => { 
                        if(e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'C' || e.key === 'J')) || (e.ctrlKey && e.key === 'U')) {
                            e.preventDefault(); 
                        }
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }

    // =================================================================================
    // 🛡️ STREAMING POR TOKEN DE IP
    // =================================================================================
    public function render_stream($id) {
        $license = get_option('xvidspro_license_key', '');
        if (empty($license)) { http_response_code(403); exit; }
        
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'");
        
        $videoData = get_option('xv_vid_' . $id);
        if (!$videoData || empty($videoData['link'])) { http_response_code(404); exit; }
        
        $url = $videoData['link'];

        // --- 🛡️ INICIO BLINDAJE POR TOKEN DE IP ---
        if (strpos($url, 's3-proxy') !== false || strpos($url, 'sdnx') !== false || strpos($url, 'stream.php') !== false) {
            $parsed_url = parse_url($url);
            parse_str($parsed_url['query'] ?? '', $query_params);
            
            if (isset($query_params['v'])) {
                $embedCode = $query_params['v'];
                $viewer_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                $viewer_ip = trim(explode(',', $viewer_ip)[0]);
                
                $expires = time() + 10800; // Expira en 3 horas
                $secret_key = "TokeN_Sup3r_S3gur0_XvidsPr0_2026!"; // CLAVE MAESTRA
                
                $token = hash_hmac('sha256', $embedCode . $viewer_ip . $expires, $secret_key);
                
                $baseUrl = explode('?', $url)[0];
                $url = $baseUrl . '?v=' . $embedCode . '&e=' . $expires . '&t=' . $token;
            }
        }
        // --- 🛡️ FIN BLINDAJE ---

        header("Location: " . $url, true, 302);
        exit;
    }

    // =================================================================================
    // 🛒 PANTALLA DE DESCARGAS
    // =================================================================================
    public function render_download($id) {
        $license = get_option('xvidspro_license_key', '');
        if (empty($license)) wp_die("Licencia no configurada.", "Acceso Denegado", ['response' => 403]);

        $videoData = get_option('xv_vid_' . $id);
        if (!$videoData || empty($videoData['link'])) wp_die("Enlace caducado o no encontrado.", "Error", ['response' => 404]);

        $settings = get_option('xvidspro_settings', []);
        
        if (empty($settings['enable_download'])) {
            wp_die("Las descargas están desactivadas temporalmente.", "Descargas Desactivadas", ['response' => 403]);
        }

        $timer = isset($settings['download_timer']) ? intval($settings['download_timer']) : 10;
        $adTop = $settings['ad_top'] ?? '';
        $adBottom = $settings['ad_bottom'] ?? '';
        
        $forceDlUrl = site_url('/?xvid_force_dl=' . $id);

        $user_has_access = true;
        $show_woo_plans = false;
        
        if (!empty($settings['require_woo'])) {
            $user_has_access = false;
            
            if (is_user_logged_in() && function_exists('wc_customer_bought_product')) {
                $uid = get_current_user_id();
                if (
                    (!empty($settings['woo_id_1']) && wc_customer_bought_product('', $uid, $settings['woo_id_1'])) ||
                    (!empty($settings['woo_id_2']) && wc_customer_bought_product('', $uid, $settings['woo_id_2'])) ||
                    (!empty($settings['woo_id_3']) && wc_customer_bought_product('', $uid, $settings['woo_id_3'])) ||
                    current_user_can('administrator')
                ) {
                    $user_has_access = true;
                }
            }
            
            if (!$user_has_access) {
                $show_woo_plans = true;
            }
        }

        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Descargar Archivo - <?php echo esc_html($settings['brand_name']); ?></title>
            <style>
                * { box-sizing: border-box; }
                body { background: #0b1120; color: #fff; font-family: system-ui, sans-serif; display: flex; flex-direction: column; align-items: center; min-height: 100vh; margin: 0; padding: 40px 20px; }
                .ad-wrapper { width: 300px; min-height: 250px; background: transparent; border: 1px dashed rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; margin: 30px 0; overflow: hidden; border-radius: 8px; text-align: center; color: rgba(255,255,255,0.3); font-size: 12px; cursor: pointer; }
                
                .dl-wrapper { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 40px 30px; text-align: center; width: 100%; max-width: 420px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); position: relative; overflow: hidden; }
                .dl-wrapper::before { content:''; position:absolute; top:0; left:0; width:100%; height:4px; background: #dc2626; } 
                
                .dl-title { margin: 0 0 10px 0; font-size: 24px; color: #fff; font-weight: bold; transition: 0.3s; }
                .dl-desc { color: #94a3b8; font-size: 15px; margin: 0 0 35px 0; transition: 0.3s; line-height: 1.5; }
                
                .timer-circle { width: 110px; height: 110px; border-radius: 50%; border: 4px solid #334155; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px auto; font-size: 40px; font-weight: 800; color: #dc2626; background: rgba(0,0,0,0.1); transition: 0.3s; }
                
                .ad-instruction { color: #facc15; border: 1px dashed #facc15; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; font-size: 14px; display: none; }
                
                .btn-dl { margin: 0 auto; display: none; align-items: center; justify-content: center; gap: 10px; width: 100%; max-width: 300px; padding: 16px; border-radius: 10px; font-size: 18px; font-weight: bold; text-decoration: none; transition: all 0.3s; background: <?php echo esc_attr($settings['play_color']); ?>; color: #000; box-shadow: 0 5px 20px rgba(0,255,136, 0.2); border: none; cursor: pointer; }
                .btn-dl.ready { display: inline-flex; animation: popIn 0.5s ease; }
                .btn-dl.ready:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,255,136, 0.4); }
                @keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
                
                .footer-brand { margin-top: 30px; color: #475569; font-size: 12px; }

                .woo-plans-container { width: 100%; max-width: 600px; text-align: center; }
                .woo-plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 20px; }
                .woo-plan-card { background: #1e293b; border: 1px solid #334155; padding: 25px 15px; border-radius: 12px; transition: 0.3s; }
                .woo-plan-card:hover { border-color: <?php echo esc_attr($settings['play_color']); ?>; transform: translateY(-5px); }
                .woo-plan-title { font-size: 18px; font-weight: bold; margin-bottom: 10px; color: #fff; }
                .woo-plan-price { font-size: 28px; font-weight: 800; color: <?php echo esc_attr($settings['play_color']); ?>; margin-bottom: 15px; }
                .woo-btn { display: inline-block; background: <?php echo esc_attr($settings['play_color']); ?>; color: #000; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; transition: 0.2s; }
                .woo-btn:hover { opacity: 0.9; }
                .woo-login-msg { margin-top: 25px; font-size: 14px; color: #94a3b8; }
                .woo-login-msg a { color: <?php echo esc_attr($settings['play_color']); ?>; text-decoration: none; font-weight: bold; }
            </style>
        </head>
        <body>
            <?php if ($show_woo_plans): ?>
                
                <div class="woo-plans-container">
                    <h2 style="color:#fff; font-size: 28px; margin-bottom: 10px;">Acceso Premium Requerido</h2>
                    <p style="color:#94a3b8; font-size: 16px;">Adquiere una membresía para desbloquear las descargas en alta velocidad.</p>
                    
                    <div class="woo-plans-grid">
                        <?php if(!empty($settings['woo_id_1'])): ?>
                        <div class="woo-plan-card">
                            <div class="woo-plan-title">4 Meses</div>
                            <div class="woo-plan-price">$<?php echo esc_html($settings['woo_price_1']); ?></div>
                            <a href="<?php echo site_url('/?add-to-cart='.$settings['woo_id_1']); ?>" class="woo-btn">Comprar</a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($settings['woo_id_2'])): ?>
                        <div class="woo-plan-card">
                            <div class="woo-plan-title">Semestral</div>
                            <div class="woo-plan-price">$<?php echo esc_html($settings['woo_price_2']); ?></div>
                            <a href="<?php echo site_url('/?add-to-cart='.$settings['woo_id_2']); ?>" class="woo-btn">Comprar</a>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($settings['woo_id_3'])): ?>
                        <div class="woo-plan-card">
                            <div class="woo-plan-title">Anual</div>
                            <div class="woo-plan-price">$<?php echo esc_html($settings['woo_price_3']); ?></div>
                            <a href="<?php echo site_url('/?add-to-cart='.$settings['woo_id_3']); ?>" class="woo-btn">Comprar</a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if(!is_user_logged_in()): ?>
                        <div class="woo-login-msg">¿Ya tienes una cuenta? <a href="<?php echo wp_login_url(site_url('/?xvid_download='.$id)); ?>">Inicia sesión aquí</a></div>
                    <?php endif; ?>
                </div>

            <?php else: ?>

                <div class="ad-wrapper">
                    <?php echo $adTop ? do_shortcode(stripslashes($adTop)) : 'Espacio Publicitario<br>250x300'; ?>
                </div>

                <div class="dl-wrapper">
                    <h2 class="dl-title" id="mainTitle">Generando Enlace</h2>
                    <p class="dl-desc" id="mainDesc">Tu descarga segura estará lista en unos segundos.</p>
                    
                    <div class="timer-circle" id="time"><?php echo $timer; ?></div>
                    
                    <div id="ad-instruction" class="ad-instruction"></div>

                    <a href="<?php echo esc_url($forceDlUrl); ?>" id="dlBtn" class="btn-dl">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                        <span id="btnText">Descargar Video</span>
                    </a>
                </div>

                <div class="ad-wrapper">
                    <?php echo $adBottom ? do_shortcode(stripslashes($adBottom)) : 'Espacio Publicitario<br>250x300'; ?>
                </div>
                
                <div class="footer-brand">Powered by <?php echo esc_html($settings['brand_name']); ?></div>

                <script>
                    let timeLeft = <?php echo $timer; ?>; let timerFinished = false; let adClicked = false;
                    const timerEl = document.getElementById('time'), btnEl = document.getElementById('dlBtn'), btnText = document.getElementById('btnText'), adInstruction = document.getElementById('ad-instruction'), mainTitle = document.getElementById('mainTitle'), mainDesc = document.getElementById('mainDesc');
                    let isHoveringAd = false; const ads = document.querySelectorAll('.ad-wrapper');
                    
                    ads.forEach(ad => {
                        ad.addEventListener('mouseenter', () => isHoveringAd = true);
                        ad.addEventListener('mouseleave', () => isHoveringAd = false);
                        ad.addEventListener('touchstart', () => isHoveringAd = true);
                        ad.addEventListener('touchend', () => setTimeout(() => isHoveringAd = false, 500));
                        ad.addEventListener('click', registerAdClick);
                    });
                    window.addEventListener('blur', () => { if (isHoveringAd) registerAdClick(); });

                    function registerAdClick() { if (!adClicked) { adClicked = true; checkUnlockState(); } }
                    
                    function checkUnlockState() {
                        if (timerFinished && adClicked) {
                            timerEl.style.display = 'none'; 
                            adInstruction.style.display = 'none';
                            btnEl.classList.add('ready'); 
                            mainTitle.innerText = "¡Enlace Listo!"; 
                            mainDesc.innerText = "Haz clic en el botón de abajo para iniciar la descarga.";
                        } else if (timerFinished && !adClicked) {
                            timerEl.innerHTML = '<svg width="40" height="40" viewBox="0 0 24 24" fill="#fbbf24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>'; 
                            timerEl.style.borderColor = "#ef4444"; 
                            
                            mainTitle.innerHTML = '<span style="background:#1d4ed8; padding:2px 8px; border-radius:4px; font-size:20px;">Desbloquea tu enlace Haz clic<br>en un anuncio</span>'; 
                            mainDesc.innerHTML = "Nos financiamos con publicidad. Por favor,<br>colabora con nosotros."; 
                            
                            adInstruction.innerHTML = '👇 Haz clic en un anuncio arriba o abajo para<br>habilitar la descarga 👇';
                            adInstruction.style.display = 'block';
                        }
                    }

                    const countdown = setInterval(() => {
                        timeLeft--; if (timeLeft > 0) { timerEl.innerText = timeLeft; } else { clearInterval(countdown); timerFinished = true; checkUnlockState(); }
                    }, 1000);
                    
                    btnEl.addEventListener('click', function() {
                        btnText.innerText = "¡Iniciando descarga...!";
                        setTimeout(() => { btnText.innerText = "Descargar Archivo"; }, 3000);
                    });
                </script>
            <?php endif; ?>
        </body>
        </html>
        <?php
    }
}
?>
