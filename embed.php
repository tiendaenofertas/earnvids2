<?php
declare(strict_types=1);

try {
    require_once __DIR__ . "/config/config.php";
    if (session_status() === PHP_SESSION_NONE) session_start();

    class MP4SecurePlayer {
        private string $videoId = "";
        private array $videoData = [];

        public function render(): void {
            if (!$this->verifyLicense()) { $this->showError("Licencia Inactiva."); return; }
            if (!validateReferer()) { $this->showError("Acceso no autorizado."); return; }

            // LEER ID CORTO
            $this->videoId = preg_replace('/[^a-zA-Z0-9]/', '', $_GET["v"] ?? "");
            if (empty($this->videoId)) { $this->showError("Video no encontrado."); return; }

            // BUSCAR ARCHIVO DEL VIDEO
            $subDir = substr($this->videoId, 0, 2);
            $file = __DIR__ . '/config/links/' . $subDir . '/' . $this->videoId . '.json';
            
            if (!file_exists($file)) { $this->showError("Enlace caducado o inexistente."); return; }

            $this->videoData = json_decode(file_get_contents($file), true);
            if (empty($this->videoData['link'])) { $this->showError("Fuente de video inválida."); return; }

            $this->renderInterface();
        }

        private function verifyLicense(): bool {
            if (!defined('LICENSE_FILE')) return true; 
            $licenseKey = file_exists(LICENSE_FILE) ? trim(file_get_contents(LICENSE_FILE)) : '';
            if (empty($licenseKey)) return false;

            $cacheFile = __DIR__ . '/config/license.status';
            if (file_exists($cacheFile)) {
                $cache = json_decode(file_get_contents($cacheFile), true);
                if ($cache && isset($cache['status'], $cache['time']) && (time() - $cache['time']) < 3600) return $cache['status'] === 'success';
            }

            if (function_exists('checkLicenseStatus')) {
                $verify = checkLicenseStatus($licenseKey);
                $status = ($verify['status'] === 'success') ? 'success' : 'error';
                @file_put_contents($cacheFile, json_encode(['status' => $status, 'time' => time()]));
                return $status === 'success';
            }
            return true; 
        }

        private function renderInterface(): void {
            $poster = $this->videoData["poster"] ?? "";
            $subs = $this->videoData["sub"] ?? [];
            $streamUrl = "stream/index.php?v=" . $this->videoId;

            // 🎨 LEER CONFIGURACIÓN VISUAL (Colores rápidos)
            $settingsFile = __DIR__ . '/config/player_settings.json';
            $pSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) : [];
            $brandName = htmlspecialchars($pSettings['brand_name'] ?? 'XZORRA');
            $brandColor = htmlspecialchars($pSettings['brand_color'] ?? '#ff0000');
            $playColor = htmlspecialchars($pSettings['play_color'] ?? '#e50914');
            
            // Extraer dominio base para la preconexión
            $baseVideoDomain = parse_url($this->videoData['link'], PHP_URL_SCHEME) . '://' . parse_url($this->videoData['link'], PHP_URL_HOST);
            ?>
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
                <title>Reproductor XZORRA</title>
                <meta name="robots" content="noindex, nofollow">
                
                <link rel="preconnect" href="<?= $baseVideoDomain ?>" crossorigin>
                
                <style>
                    /* CSS ULTRA-MINIFICADO Y OPTIMIZADO */
                    *{margin:0;padding:0;box-sizing:border-box}
                    html,body{width:100%;height:100%;background:#000;overflow:hidden;font-family:system-ui,-apple-system,sans-serif;-webkit-tap-highlight-color:transparent}
                    .pw{position:relative;width:100%;height:100%;display:flex;justify-content:center;align-items:center;background:#000}
                    video{width:100%;height:100%;object-fit:contain;display:block}
                    .wm{position:absolute;top:25px;left:25px;display:flex;align-items:center;gap:10px;padding:6px 14px;background:rgba(12,12,18,.8);border:1px solid rgba(255,255,255,.1);border-radius:10px;backdrop-filter:blur(4px);z-index:20;pointer-events:none;box-shadow:0 4px 15px rgba(0,0,0,.5)}
                    .wm-i{font-size:16px}
                    .wm-t{font-family:Impact,sans-serif;font-size:20px;color:<?= $brandColor ?>;letter-spacing:1px;text-transform:uppercase;text-shadow:0 2px 4px rgba(0,0,0,.8);font-weight:700}
                    .po{position:absolute;top:0;left:0;width:100%;height:100%;display:flex;justify-content:center;align-items:center;background:rgba(0,0,0,.3);cursor:pointer;z-index:10;transition:opacity .2s}
                    .pc{width:80px;height:80px;border-radius:50%;background:rgba(30,30,30,.7);border:2px solid rgba(255,255,255,.5);backdrop-filter:blur(2px);display:flex;justify-content:center;align-items:center;box-shadow:0 0 20px rgba(0,0,0,.5);transition:transform .2s}
                    .pt{width:0;height:0;border-top:16px solid transparent;border-bottom:16px solid transparent;border-left:28px solid <?= $playColor ?>;margin-left:6px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.5))}
                    @media(hover:hover){.po:hover .pc{transform:scale(1.1);background:rgba(50,50,50,.8);border-color:#fff}}
                    .is-p .po{opacity:0;visibility:hidden;pointer-events:none}
                    @media(max-width:768px){.wm{top:15px;left:15px;padding:5px 10px}.wm-t{font-size:16px}.pc{width:64px;height:64px}.pt{border-top-width:12px;border-bottom-width:12px;border-left-width:22px;margin-left:4px}}
                </style>
            </head>
            <body oncontextmenu="return false;">
                <div class="pw" id="wrp">
                    <div class="wm"><div class="wm-i">🛡️</div><div class="wm-t"><?= $brandName ?></div></div>
                    
                    <div class="po" id="btn"><div class="pc"><div class="pt"></div></div></div>
                    
                    <video id="vid" playsinline webkit-playsinline preload="metadata" controlsList="nodownload" <?= $poster ? 'poster="'.htmlspecialchars($poster).'"' : '' ?>>
                        <source src="<?= htmlspecialchars($streamUrl) ?>" type="video/mp4">
                        <?php if (!empty($subs)): ?>
                            <?php foreach ($subs as $label => $url): ?>
                                <track label="<?= htmlspecialchars($label) ?>" kind="captions" srclang="es" src="<?= htmlspecialchars($url) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </video>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const v = document.getElementById('vid'), b = document.getElementById('btn'), w = document.getElementById('wrp');
                        
                        // Lógica de Play ultra rápida
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
                        
                        // Bloquear consola F12
                        document.addEventListener('keydown', e => { 
                            if(e.key==='F12'||(e.ctrlKey&&e.shiftKey&&e.key==='I')) e.preventDefault(); 
                        });
                    });
                </script>
            </body>
            </html>
            <?php
        }

        private function showError(string $msg): void {
            http_response_code(400); echo "<div style='color:#fff;background:#000;height:100vh;display:flex;justify-content:center;align-items:center;font-family:sans-serif;'>⚠️ " . htmlspecialchars($msg) . "</div>"; exit;
        }
    }

    $player = new MP4SecurePlayer(); $player->render();

} catch (Throwable $e) {
    http_response_code(500); echo "<div style='color:#fff;background:#000;height:100vh;display:flex;justify-content:center;align-items:center;text-align:center;'>Error de sistema</div>";
}
?>
