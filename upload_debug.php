<?php
// upload_debug.php - Diagnóstico del sistema de subida
require_once 'config/app.php';
require_once 'includes/functions.php';
require_once 'includes/db_connect.php';

// Iniciar sesión si es necesario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Subida - EARNVIDS</title>
    <style>
        body { background: #0a0a0a; color: #fff; font-family: Arial; padding: 20px; line-height: 1.6; }
        .box { background: #1a1a1a; border: 1px solid #2a2a2a; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: #00ff88; }
        .error { color: #ff3b3b; }
        .warning { color: #ffa500; }
        code { background: #2a2a2a; padding: 2px 5px; border-radius: 3px; }
        pre { background: #2a2a2a; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #2a2a2a; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico del Sistema de Subida</h1>

    <div class="box">
        <h2>1. Estado de Sesión</h2>
        <?php if (isLoggedIn()): ?>
            <p class="success">✓ Usuario autenticado: <?= $_SESSION['username'] ?></p>
        <?php else: ?>
            <p class="error">✗ No hay sesión activa</p>
            <p><a href="/login.php" style="color: #00ff88;">Iniciar sesión</a></p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>2. Configuración PHP para Subidas</h2>
        <table>
            <tr>
                <th>Configuración</th>
                <th>Valor Actual</th>
                <th>Recomendado</th>
                <th>Estado</th>
            </tr>
            <tr>
                <td>upload_max_filesize</td>
                <td><?= ini_get('upload_max_filesize') ?></td>
                <td>5120M</td>
                <td><?= intval(ini_get('upload_max_filesize')) >= 5120 ? '<span class="success">✓</span>' : '<span class="error">✗</span>' ?></td>
            </tr>
            <tr>
                <td>post_max_size</td>
                <td><?= ini_get('post_max_size') ?></td>
                <td>5120M</td>
                <td><?= intval(ini_get('post_max_size')) >= 5120 ? '<span class="success">✓</span>' : '<span class="error">✗</span>' ?></td>
            </tr>
            <tr>
                <td>max_execution_time</td>
                <td><?= ini_get('max_execution_time') ?>s</td>
                <td>3600s</td>
                <td><?= intval(ini_get('max_execution_time')) >= 300 ? '<span class="success">✓</span>' : '<span class="warning">⚠</span>' ?></td>
            </tr>
            <tr>
                <td>max_input_time</td>
                <td><?= ini_get('max_input_time') ?>s</td>
                <td>3600s</td>
                <td><?= intval(ini_get('max_input_time')) >= 300 ? '<span class="success">✓</span>' : '<span class="warning">⚠</span>' ?></td>
            </tr>
            <tr>
                <td>memory_limit</td>
                <td><?= ini_get('memory_limit') ?></td>
                <td>512M</td>
                <td><?= intval(ini_get('memory_limit')) >= 256 ? '<span class="success">✓</span>' : '<span class="warning">⚠</span>' ?></td>
            </tr>
        </table>
        
        <?php if (intval(ini_get('upload_max_filesize')) < 5120 || intval(ini_get('post_max_size')) < 5120): ?>
            <p class="error">⚠️ Los límites de PHP son muy bajos para videos de 5GB. Contacta a tu hosting.</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h2>3. Archivos del Sistema</h2>
        <?php
        $requiredFiles = [
            'api/upload.php' => 'API de subida',
            'includes/storage_manager.php' => 'Gestor de almacenamiento',
            'includes/upload_handler.php' => 'Manejador de subidas',
            'assets/js/upload.js' => 'JavaScript de subida'
        ];
        
        foreach ($requiredFiles as $file => $desc): ?>
            <?php if (file_exists($file)): ?>
                <p class="success">✓ <?= $file ?> (<?= filesize($file) ?> bytes)</p>
            <?php else: ?>
                <p class="error">✗ <?= $file ?> - <?= $desc ?> NO EXISTE</p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="box">
        <h2>4. Almacenamiento Activo</h2>
        <?php
        try {
            $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1");
            $storage = $stmt->fetch();
            
            if ($storage): ?>
                <p class="success">✓ Almacenamiento activo: <strong><?= $storage['storage_type'] ?></strong></p>
                
                <?php if ($storage['storage_type'] === 'local'): ?>
                    <h3>Verificación de Almacenamiento Local</h3>
                    <?php
                    $uploadDir = 'uploads/videos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    ?>
                    <p>Directorio: <code><?= realpath($uploadDir) ?: $uploadDir ?></code></p>
                    <p>Permisos: <?= is_writable($uploadDir) ? '<span class="success">✓ Escribible</span>' : '<span class="error">✗ NO escribible</span>' ?></p>
                    <p>Espacio libre: <?= formatFileSize(disk_free_space('.')) ?></p>
                    
                <?php elseif ($storage['storage_type'] === 'contabo'): ?>
                    <h3>Verificación de Contabo S3</h3>
                    <p>Endpoint: <code><?= $storage['endpoint'] ?></code></p>
                    <p>Bucket: <code><?= $storage['bucket'] ?></code></p>
                    <p>Access Key: <code><?= substr($storage['access_key'], 0, 10) ?>...</code></p>
                    
                    <?php
                    // Probar conexión
                    if (file_exists('includes/storage_manager.php')) {
                        require_once 'includes/storage_manager.php';
                        try {
                            $s3 = new SimpleS3Client(
                                $storage['access_key'],
                                $storage['secret_key'],
                                $storage['endpoint'],
                                $storage['region']
                            );
                            echo '<p class="success">✓ Clase S3 cargada correctamente</p>';
                        } catch (Exception $e) {
                            echo '<p class="error">✗ Error al inicializar S3: ' . $e->getMessage() . '</p>';
                        }
                    }
                    ?>
                <?php endif; ?>
                
            <?php else: ?>
                <p class="error">✗ No hay almacenamiento activo configurado</p>
                <p><a href="/admin/storage.php" style="color: #00ff88;">Configurar almacenamiento</a></p>
            <?php endif;
        } catch (Exception $e) {
            echo '<p class="error">✗ Error al verificar almacenamiento: ' . $e->getMessage() . '</p>';
        }
        ?>
    </div>

    <div class="box">
        <h2>5. Prueba de Subida Simple</h2>
        <form id="testUploadForm" enctype="multipart/form-data">
            <input type="file" id="testFile" accept="video/*" required>
            <button type="submit" style="margin-left: 10px; padding: 10px 20px; background: #00ff88; color: #000; border: none; border-radius: 5px; cursor: pointer;">
                Probar Subida
            </button>
        </form>
        <div id="uploadResult" style="margin-top: 20px;"></div>
    </div>

    <div class="box">
        <h2>6. Logs de Error</h2>
        <p>Busca en los logs del servidor errores relacionados con:</p>
        <ul>
            <li><code>[EARNVIDS Upload]</code> - Errores del sistema de subida</li>
            <li>Errores de PHP relacionados con límites de memoria o tiempo</li>
            <li>Errores de permisos de archivos</li>
        </ul>
    </div>

    <div class="box">
        <h2>7. Soluciones Recomendadas</h2>
        <?php if ($storage && $storage['storage_type'] === 'contabo'): ?>
            <h3>Para Contabo S3:</h3>
            <ol>
                <li>Verifica que las credenciales sean correctas</li>
                <li>Asegúrate de que el bucket existe</li>
                <li>Verifica que el endpoint incluya <code>https://</code></li>
                <li>Prueba cambiar temporalmente a almacenamiento local</li>
            </ol>
        <?php else: ?>
            <h3>Para Almacenamiento Local:</h3>
            <ol>
                <li>Verifica permisos: <code>chmod -R 755 uploads/</code></li>
                <li>Asegúrate de tener espacio suficiente en disco</li>
                <li>Verifica límites de PHP con tu hosting</li>
            </ol>
        <?php endif; ?>
        
        <h3>General:</h3>
        <ol>
            <li>Si los archivos grandes fallan, el problema es de límites PHP</li>
            <li>Contacta a tu hosting para aumentar <code>upload_max_filesize</code> y <code>post_max_size</code></li>
            <li>Considera usar almacenamiento externo (Contabo) para archivos grandes</li>
        </ol>
    </div>

    <script>
        document.getElementById('testUploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const file = document.getElementById('testFile').files[0];
            const resultDiv = document.getElementById('uploadResult');
            
            if (!file) {
                resultDiv.innerHTML = '<p class="error">Selecciona un archivo</p>';
                return;
            }
            
            resultDiv.innerHTML = '<p>Subiendo archivo de prueba...</p>';
            
            const formData = new FormData();
            formData.append('video', file);
            formData.append('title', 'Test Video');
            
            try {
                const response = await fetch('/api/upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <p class="success">✓ Subida exitosa!</p>
                        <p>Video ID: ${data.video_id}</p>
                        <p>Embed Code: ${data.embed_code}</p>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="error">✗ Error: ${data.message}</p>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">✗ Error de conexión: ${error.message}</p>`;
            }
        });
    </script>
</body>
</html>