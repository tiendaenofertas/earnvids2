<?php
require_once '../config/app.php';
require_once '../includes/functions.php';
requireAdmin();

$message = '';
$error = '';

// Cargar configuración actual
$configFile = '../config/app.php';

// Verificamos si la constante ya existe (para evitar errores undefined)
$currentAllowedDomains = defined('ALLOWED_DOMAINS') ? implode(', ', ALLOWED_DOMAINS) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName = $_POST['site_name'] ?? SITE_NAME;
    $siteUrl = rtrim($_POST['site_url'] ?? SITE_URL, '/');
    $maxUploadSize = intval($_POST['max_upload_size'] ?? 5) * 1024 * 1024 * 1024;
    $extensions = array_map('trim', explode(',', $_POST['allowed_extensions'] ?? ''));
    
    // Procesar dominios permitidos
    $rawDomains = $_POST['allowed_domains'] ?? '';
    $domainList = array_filter(array_map(function($domain) {
        // Limpiamos protocolo y espacios, nos quedamos solo con el host
        $domain = str_replace(['http://', 'https://', '/'], '', strtolower(trim($domain)));
        return $domain;
    }, explode(',', $rawDomains)));
    
    // Convertimos el array a string para guardarlo en el archivo config
    $domainsExport = var_export(array_values($domainList), true);

    // Generar nuevo archivo de configuración
    $newConfig = "<?php
define('SITE_NAME', '$siteName');
define('SITE_URL', '$siteUrl');
define('SITE_VERSION', '1.0.0');
define('DEBUG_MODE', false);

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

date_default_timezone_set('America/Sao_Paulo');

define('MAX_UPLOAD_SIZE', $maxUploadSize);
define('ALLOWED_EXTENSIONS', ['" . implode("', '", $extensions) . "']);
define('ALLOWED_DOMAINS', $domainsExport);
define('THUMBNAIL_WIDTH', 320);
define('THUMBNAIL_HEIGHT', 180);
";
    
    if (file_put_contents($configFile, $newConfig)) {
        logActivity('update_settings', [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'allowed_domains' => $domainList
        ]);
        $message = 'Configuración actualizada exitosamente';
        // Actualizamos la variable visual para que refleje el cambio inmediato
        $currentAllowedDomains = implode(', ', $domainList);
    } else {
        $error = 'Error al guardar la configuración. Verifique permisos de escritura en config/app.php';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración General - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Configuración General</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="notification success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="notification error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="settings-container" style="max-width: 800px;">
            <form method="POST">
                <div class="account-section">
                    <h3 class="section-title">Información del Sitio</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Nombre del Sitio</label>
                        <input type="text" name="site_name" class="form-control" 
                               value="<?= htmlspecialchars(SITE_NAME) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">URL del Sitio</label>
                        <input type="url" name="site_url" class="form-control" 
                               value="<?= htmlspecialchars(SITE_URL) ?>" required>
                        <small style="color: var(--text-secondary);">Sin barra diagonal al final</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dominios Permitidos (CORS / Hotlink Protection)</label>
                        <input type="text" name="allowed_domains" class="form-control" 
                               value="<?= htmlspecialchars($currentAllowedDomains) ?>" 
                               placeholder="ejemplo.com, misitio.net">
                        <small style="color: var(--text-secondary);">
                            Separa los dominios por comas. Déjalo vacío para permitir todos.
                            <br><strong>Nota:</strong> Tu propio dominio se incluirá automáticamente en la validación.
                        </small>
                    </div>
                </div>
                
                <div class="account-section" style="margin-top: 20px;">
                    <h3 class="section-title">Configuración de Subida</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Tamaño Máximo de Archivo (GB)</label>
                        <input type="number" name="max_upload_size" class="form-control" 
                               value="<?= MAX_UPLOAD_SIZE / (1024*1024*1024) ?>" min="1" max="50" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Extensiones Permitidas</label>
                        <input type="text" name="allowed_extensions" class="form-control" 
                               value="<?= implode(', ', ALLOWED_EXTENSIONS) ?>" required>
                        <small style="color: var(--text-secondary);">Separadas por comas</small>
                    </div>
                </div>
                
                <div class="account-section" style="margin-top: 20px;">
                    <h3 class="section-title">Mantenimiento</h3>
                    
                    <div class="info-row">
                        <span class="info-label">Versión del Sistema</span>
                        <span><?= SITE_VERSION ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Versión PHP</span>
                        <span><?= PHP_VERSION ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Espacio en Disco</span>
                        <span><?= formatFileSize(disk_free_space('/')) ?> libres</span>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <a href="/api/docs.php" class="btn btn-secondary" target="_blank">
                            Ver Documentación API
                        </a>
                    </div>
                </div>
                
                <button type="submit" class="btn" style="margin-top: 20px;">
                    Guardar Cambios
                </button>
            </form>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
</body>
</html>