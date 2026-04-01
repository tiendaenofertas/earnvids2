<?php
if (!defined('ABSPATH')) exit;

class XvidsPro_Admin {

    private $api_url = 'https://xvidspro.com/api/verify_license.php';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_xvidspro_save_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_xvidspro_generate_link', [$this, 'ajax_generate_link']);
    }

    public function add_admin_menu() {
        add_menu_page('XVIDSPRO', 'XVIDSPRO', 'manage_options', 'xvidspro-panel', [$this, 'admin_page_html'], 'dashicons-shield', 30);
    }

    private function check_license($key) {
        $domain = $_SERVER['HTTP_HOST'] ?? 'unknown';
        $response = wp_remote_post($this->api_url, [
            'body' => json_encode(['license_key' => $key, 'domain' => $domain]),
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'timeout' => 10,
            'sslverify' => false
        ]);

        if (is_wp_error($response)) {
            return ['status' => 'http_error', 'message' => 'Error de conexión con el servidor de licencias.'];
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($http_code != 200 || !$data) {
             return ['status' => 'http_error', 'message' => 'El servidor de licencias no responde correctamente.'];
        }
        return $data; 
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) return;

        // 1. PROCESAR EL FORMULARIO DE LICENCIA ORIGINAL
        if (isset($_POST['xvidspro_license_key'])) {
            $key = sanitize_text_field($_POST['xvidspro_license_key']);
            $verify = $this->check_license($key);
            
            if (isset($verify['status']) && $verify['status'] === 'success') {
                update_option('xvidspro_license_key', $key);
                update_option('xvidspro_license_data', $verify);
                echo '<div class="notice notice-success is-dismissible"><p>✅ Licencia validada. ¡Bienvenido a XVIDSPRO!</p></div>';
            } else {
                $msg = isset($verify['message']) ? $verify['message'] : 'Licencia inválida.';
                $this->render_license_form($msg);
                return; // Detener para que el usuario pueda intentar de nuevo
            }
        }

        $license = get_option('xvidspro_license_key', '');
        $settings = get_option('xvidspro_settings', [
            'brand_name' => 'XZORRA',
            'brand_color' => '#00ff88',
            'play_color' => '#00ff88',
            'autoplay' => 0,
            'enable_download' => 0,
            'show_player_dl_icon' => 1, 
            'download_timer' => 10,
            'ad_top' => '',
            'ad_bottom' => '',
            'require_woo' => 0,
            'woo_id_1' => '', 'woo_price_1' => '18',
            'woo_id_2' => '', 'woo_price_2' => '27',
            'woo_id_3' => '', 'woo_price_3' => '45'
        ]);
        
        if (empty($license)) {
            $this->render_license_form();
            return;
        }

        // VERIFICACIÓN EN TIEMPO REAL
        $verify = $this->check_license($license);
        
        if (isset($verify['status']) && $verify['status'] === 'error') {
            update_option('xvidspro_license_key', ''); 
            update_option('xvidspro_license_data', []);
            $this->render_license_form('Tu licencia ha sido suspendida o es inválida. Por favor, actívala nuevamente.');
            return;
        }
        
        if (isset($verify['status']) && $verify['status'] === 'success') {
            update_option('xvidspro_license_data', $verify);
        }

        $licData = get_option('xvidspro_license_data', []);
        
        $daysRemaining = 999;
        if (isset($licData['days_remaining'])) {
            $daysRemaining = (int)$licData['days_remaining'];
        } elseif (isset($licData['days_left'])) {
            $daysRemaining = (int)$licData['days_left'];
        } elseif (isset($licData['expiry_date'])) {
            $daysRemaining = max(0, ceil((strtotime($licData['expiry_date']) - time()) / 86400));
        }

        ?>
        <div class="wrap xvidspro-wrap">
            <style>
                .xvidspro-wrap { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin-top: 20px; }
                .xvidspro-header { background: #0f172a; padding: 20px; border-radius: 12px 12px 0 0; color: #fff; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
                .xvidspro-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
                .xv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
                .xv-group { margin-bottom: 20px; }
                .xv-group label.xv-label { display: block; font-weight: 600; margin-bottom: 8px; color: #334155; }
                .xv-input { width: 100%; padding: 10px 15px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: 0.3s; }
                .xv-input:focus { border-color: #00ff88; }
                .xv-btn { background: #0f172a; color: #00ff88; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 15px; display: inline-flex; align-items: center; gap: 8px; width: 100%; justify-content: center; }
                .xv-btn:hover { background: #1e293b; transform: translateY(-2px); }
                .xv-btn-green { background: #00ff88; color: #000; }
                .xv-btn-green:hover { background: #00cc6a; }
                .xv-color-picker { display: flex; align-items: center; gap: 10px; }
                .xv-color-picker input[type="color"] { width: 50px; height: 40px; padding: 0; border: none; border-radius: 8px; cursor: pointer; }
                
                .xv-toggle { position: relative; display: inline-block; width: 46px; height: 24px; flex-shrink: 0;}
                .xv-toggle input { opacity: 0; width: 0; height: 0; }
                .xv-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px; }
                .xv-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                .xv-toggle input:checked + .xv-slider { background-color: #00ff88; }
                .xv-toggle input:checked + .xv-slider:before { transform: translateX(22px); }
                .xv-toggle-wrap { display: flex; align-items: center; gap: 12px; cursor: pointer; margin-top: 5px; background: #f8fafc; padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0; width: fit-content;}

                .xv-results { display: none; background: #f8fafc; border: 2px dashed #00ff88; padding: 20px; border-radius: 12px; margin-top: 20px; }
                .xv-results textarea { width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; font-family: monospace; margin-bottom: 10px; height: 80px; resize: none; background: #fff; }
                .xv-alert { padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; display: none; font-weight: bold; }
                
                .xv-expiry-banner { background: rgba(255, 59, 59, 0.1); border: 2px solid rgba(255, 59, 59, 0.5); color: #b91c1c; padding: 15px 20px; border-radius: 12px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; font-size: 16px; margin-bottom: 25px; }
                .xv-expiry-btn { background: #ff3b3b; color: #fff !important; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-size: 14px; font-weight: bold; transition: 0.2s; box-shadow: 0 4px 10px rgba(255,59,59,0.3); }
                .xv-expiry-btn:hover { background: #e60000; transform: scale(1.05); }

                #wpbody-content > h2 { display: none; }
            </style>

            <div class="xvidspro-header">
                <a href="https://xvidspro.com" target="_blank" style="display: flex; align-items: center; text-decoration: none;">
                    <img src="<?php echo esc_url(plugins_url('../img/XVIDSPRO-Panel-Securit.png', __FILE__)); ?>" alt="XVIDSPRO" style="max-height: 45px; display: block;">
                </a>
            </div>

            <?php if ($daysRemaining <= 3 && $daysRemaining >= 0): ?>
            <div class="xv-expiry-banner">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    ⚠ Tu membresía vence en <?php echo $daysRemaining; ?> <?php echo $daysRemaining == 1 ? 'día' : 'días'; ?>. Renuévala ahora para evitar la suspensión del servicio.
                </div>
                <a href="https://xvidspro.com/cuenta" target="_blank" class="xv-expiry-btn">Renovar</a>
            </div>
            <?php endif; ?>

            <div class="xv-alert" id="settingsAlert"></div>
            
            <form id="xvSettingsForm">
                
                <div class="xvidspro-card">
                    <h2 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🛒 Integración WooCommerce (Membresías)</h2>
                    <div class="xv-group">
                        <label class="xv-label">Requerir Membresía para Descargar</label>
                        <label class="xv-toggle-wrap">
                            <div class="xv-toggle">
                                <input type="checkbox" name="require_woo" value="1" <?php echo !empty($settings['require_woo']) ? 'checked' : ''; ?>>
                                <span class="xv-slider"></span>
                            </div>
                            <span style="font-size: 14px; font-weight: 600; color: #475569;">Activar Muro de Pago (Paywall)</span>
                        </label>
                        <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Si se activa, el usuario deberá comprar uno de estos planes en WooCommerce antes de ver el contador de descarga.</p>
                    </div>

                    <div class="xv-grid">
                        <div class="xv-group">
                            <label class="xv-label">Plan 1: ID de Producto WooCommerce</label>
                            <input type="number" name="woo_id_1" class="xv-input" placeholder="Ej: 152" value="<?php echo esc_attr($settings['woo_id_1'] ?? ''); ?>">
                            <input type="text" name="woo_price_1" class="xv-input" placeholder="Precio visible (Ej: 18)" value="<?php echo esc_attr($settings['woo_price_1'] ?? '18'); ?>" style="margin-top:5px;">
                        </div>
                        <div class="xv-group">
                            <label class="xv-label">Plan 2: ID de Producto WooCommerce</label>
                            <input type="number" name="woo_id_2" class="xv-input" placeholder="Ej: 153" value="<?php echo esc_attr($settings['woo_id_2'] ?? ''); ?>">
                            <input type="text" name="woo_price_2" class="xv-input" placeholder="Precio visible (Ej: 27)" value="<?php echo esc_attr($settings['woo_price_2'] ?? '27'); ?>" style="margin-top:5px;">
                        </div>
                        <div class="xv-group">
                            <label class="xv-label">Plan 3: ID de Producto WooCommerce</label>
                            <input type="number" name="woo_id_3" class="xv-input" placeholder="Ej: 154" value="<?php echo esc_attr($settings['woo_id_3'] ?? ''); ?>">
                            <input type="text" name="woo_price_3" class="xv-input" placeholder="Precio visible (Ej: 45)" value="<?php echo esc_attr($settings['woo_price_3'] ?? '45'); ?>" style="margin-top:5px;">
                        </div>
                    </div>
                </div>

                <div class="xvidspro-card">
                    <h2 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🎨 Apariencia y Reproducción</h2>
                    <div class="xv-grid">
                        <div class="xv-group">
                            <label class="xv-label">Nombre de la Marca (Watermark)</label>
                            <input type="text" name="brand_name" class="xv-input" value="<?php echo esc_attr($settings['brand_name']); ?>" required>
                        </div>
                        
                        <div class="xv-group">
                            <label class="xv-label">Reproducción Automática</label>
                            <label class="xv-toggle-wrap">
                                <div class="xv-toggle">
                                    <input type="checkbox" name="autoplay" value="1" id="xvAutoplayToggle" <?php echo !empty($settings['autoplay']) ? 'checked' : ''; ?>>
                                    <span class="xv-slider"></span>
                                </div>
                                <span id="xvAutoplayText" style="font-size: 14px; font-weight: 600; color: #475569;">
                                    <?php echo !empty($settings['autoplay']) ? 'Autoplay Activado' : 'Autoplay Desactivado'; ?>
                                </span>
                            </label>
                        </div>

                        <div class="xv-group">
                            <label class="xv-label">Color del Texto de la Marca</label>
                            <div class="xv-color-picker">
                                <input type="color" name="brand_color" value="<?php echo esc_attr($settings['brand_color']); ?>">
                            </div>
                        </div>
                        <div class="xv-group">
                            <label class="xv-label">Color del Botón Play</label>
                            <div class="xv-color-picker">
                                <input type="color" name="play_color" value="<?php echo esc_attr($settings['play_color']); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xvidspro-card">
                    <h2 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">📥 Sistema de Descargas y Publicidad</h2>
                    <div class="xv-grid">
                        <div class="xv-group">
                            <label class="xv-label">Habilitar Página de Descargas</label>
                            <label class="xv-toggle-wrap">
                                <div class="xv-toggle">
                                    <input type="checkbox" name="enable_download" value="1" <?php echo !empty($settings['enable_download']) ? 'checked' : ''; ?>>
                                    <span class="xv-slider"></span>
                                </div>
                                <span style="font-size: 14px; font-weight: 600; color: #475569;">Activar Descargas</span>
                            </label>
                        </div>
                        
                        <div class="xv-group">
                            <label class="xv-label">Mostrar Icono en el Reproductor</label>
                            <label class="xv-toggle-wrap">
                                <div class="xv-toggle">
                                    <input type="checkbox" name="show_player_dl_icon" value="1" <?php echo !empty($settings['show_player_dl_icon']) ? 'checked' : ''; ?>>
                                    <span class="xv-slider"></span>
                                </div>
                                <span style="font-size: 14px; font-weight: 600; color: #475569;">Icono Visible</span>
                            </label>
                        </div>

                        <div class="xv-group">
                            <label class="xv-label">Tiempo del Contador (Segundos)</label>
                            <input type="number" name="download_timer" class="xv-input" value="<?php echo esc_attr($settings['download_timer'] ?? 10); ?>" min="0">
                        </div>
                    </div>
                    <div class="xv-grid" style="margin-top: 15px;">
                        <div class="xv-group">
                            <label class="xv-label">Banner Superior HTML (250x300)</label>
                            <textarea name="ad_top" class="xv-input" style="height:100px;" placeholder="Pega aquí tu código HTML..."><?php echo esc_textarea($settings['ad_top'] ?? ''); ?></textarea>
                        </div>
                        <div class="xv-group">
                            <label class="xv-label">Banner Inferior HTML (250x300)</label>
                            <textarea name="ad_bottom" class="xv-input" style="height:100px;" placeholder="Pega aquí tu código HTML..."><?php echo esc_textarea($settings['ad_bottom'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="xv-btn xv-btn-green" style="width: auto; margin-top: 10px; margin-bottom: 30px;">Guardar Toda La Configuración</button>
            </form>

            <div class="xvidspro-card">
                <h2 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 10px;">🔒 Encriptar Video</h2>
                <form id="xvGenerateForm">
                    <div class="xv-grid">
                        <div class="xv-group">
                            <label class="xv-label">URL del Video (MP4)</label>
                            <input type="url" name="link" class="xv-input" placeholder="https://ejemplo.com/video.mp4" required>
                        </div>
                        <div class="xv-group">
                            <label class="xv-label">Imagen Poster (Opcional)</label>
                            <input type="url" name="poster" class="xv-input" placeholder="https://ejemplo.com/portada.jpg">
                        </div>
                    </div>
                    <button type="submit" class="xv-btn" id="btnGen">Generar Enlace Seguro</button>
                </form>

                <div class="xv-results" id="xvRes">
                    <label class="xv-label" style="margin-bottom:5px;">URL Directa del Reproductor:</label>
                    <textarea id="xvOutUrl" readonly></textarea>
                    
                    <label class="xv-label" style="margin-bottom:5px; margin-top:15px;">Código Iframe (Embed):</label>
                    <textarea id="xvOutIframe" readonly></textarea>
                    
                    <div id="xvDownloadWrap" style="display:none;">
                        <label class="xv-label" style="margin-bottom:5px; margin-top:15px; color:#059669;">URL de la Página de Descarga Monetizada:</label>
                        <textarea id="xvOutDownload" readonly style="border-color:#059669;"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#xvSettingsForm').submit(function(e) {
                    e.preventDefault();
                    let data = $(this).serialize() + '&action=xvidspro_save_settings';
                    $.post(ajaxurl, data, function(res) {
                        if(res.success) {
                            $('#settingsAlert').text('¡Configuración Guardada Exitosamente!').css({display:'block', background:'#dcfce7', color:'#059669'});
                            setTimeout(()=> $('#settingsAlert').fadeOut(), 3000);
                        }
                    });
                });

                $('#xvGenerateForm').submit(function(e) {
                    e.preventDefault();
                    $('#btnGen').text('Procesando...').prop('disabled', true);
                    $('#xvRes').slideUp();

                    let data = $(this).serialize() + '&action=xvidspro_generate_link';
                    $.post(ajaxurl, data, function(res) {
                        $('#btnGen').text('Generar Enlace Seguro').prop('disabled', false);
                        if(res.success) {
                            $('#xvOutUrl').val(res.data.url);
                            $('#xvOutIframe').val('<iframe src="'+res.data.url+'" width="100%" height="100%" frameborder="0" allowfullscreen></iframe>');
                            if (res.data.dl_url) { $('#xvOutDownload').val(res.data.dl_url); $('#xvDownloadWrap').slideDown(); } 
                            $('#xvRes').slideDown();
                        } else { alert(res.data); }
                    });
                });
            });
        </script>
        <?php
    }

    // 1. FORMULARIO RESTAURADO PARA FUNCIONAR SIN AJAX (CARGA NATIVA)
    private function render_license_form($error = '') {
        ?>
        <div class="wrap" style="max-width: 500px; margin: 50px auto; text-align: center; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 20px rgba(0,0,0,0.05);">
            <img src="<?php echo esc_url(plugins_url('../img/XVIDSPRO-Panel-Securit.png', __FILE__)); ?>" alt="XVIDSPRO" style="max-height: 50px; margin-bottom: 20px;">
            <h1 style="font-size: 24px; font-weight:bold; margin-bottom: 10px;">Licencia Requerida</h1>
            <p style="color: #64748b; margin-bottom: 20px;">Ingresa tu clave de XVIDSPRO para activar el plugin.</p>
            <?php if($error): ?><div style="background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;"><?php echo esc_html($error); ?></div><?php endif; ?>
            
            <form method="post" action="">
                <input type="text" name="xvidspro_license_key" placeholder="XVIDS-XXXXXXXXXXXX" required style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; text-align: center; font-family: monospace; font-size: 16px;">
                <button type="submit" style="width: 100%; background: #00ff88; color: #000; border: none; padding: 15px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px;">VERIFICAR Y ACTIVAR</button>
            </form>
        </div>
        <?php
    }

    // 2. FUNCIÓN AJAX AHORA SOLO GUARDA LOS AJUSTES, NO SE METE CON LA LICENCIA
    public function ajax_save_settings() {
        if (!current_user_can('manage_options')) wp_send_json_error('No autorizado');

        $settings = [
            'brand_name' => sanitize_text_field($_POST['brand_name'] ?? 'XZORRA'),
            'brand_color' => sanitize_hex_color($_POST['brand_color'] ?? '#00ff88'),
            'play_color' => sanitize_hex_color($_POST['play_color'] ?? '#00ff88'),
            'autoplay' => isset($_POST['autoplay']) ? 1 : 0,
            'enable_download' => isset($_POST['enable_download']) ? 1 : 0,
            'show_player_dl_icon' => isset($_POST['show_player_dl_icon']) ? 1 : 0,
            'download_timer' => intval($_POST['download_timer'] ?? 10),
            'ad_top' => stripslashes($_POST['ad_top'] ?? ''),
            'ad_bottom' => stripslashes($_POST['ad_bottom'] ?? ''),
            'require_woo' => isset($_POST['require_woo']) ? 1 : 0,
            'woo_id_1' => intval($_POST['woo_id_1'] ?? 0),
            'woo_price_1' => sanitize_text_field($_POST['woo_price_1'] ?? '18'),
            'woo_id_2' => intval($_POST['woo_id_2'] ?? 0),
            'woo_price_2' => sanitize_text_field($_POST['woo_price_2'] ?? '27'),
            'woo_id_3' => intval($_POST['woo_id_3'] ?? 0),
            'woo_price_3' => sanitize_text_field($_POST['woo_price_3'] ?? '45')
        ];
        
        update_option('xvidspro_settings', $settings);
        wp_send_json_success('Guardado');
    }

    public function ajax_generate_link() {
        if (!current_user_can('manage_options')) wp_send_json_error('No autorizado');
        $link = sanitize_url($_POST['link'] ?? '');
        $poster = sanitize_url($_POST['poster'] ?? '');
        if (strpos($link, '/embed.php?v=') !== false) { $link = str_replace('/embed.php?v=', '/sdnx?v=', $link); }
        if (empty($link) || strpos($link, 'http') !== 0) wp_send_json_error('URL de video inválida.');

        $videoData = ['link' => $link, 'poster' => $poster, 'created_at' => time()];
        $shortId = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
        update_option('xv_vid_' . $shortId, $videoData, false);

        $embedUrl = site_url('/?xvid_embed=' . $shortId);
        $settings = get_option('xvidspro_settings', []);
        $dlUrl = !empty($settings['enable_download']) ? site_url('/?xvid_download=' . $shortId) : '';

        wp_send_json_success(['url' => $embedUrl, 'id' => $shortId, 'dl_url' => $dlUrl]);
    }
}
?>
