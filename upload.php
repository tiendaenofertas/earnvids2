<?php
require_once 'config/app.php';
require_once 'includes/functions.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Video - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .upload-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            fill: var(--text-secondary);
        }
        #upload-progress {
            margin-top: 30px;
        }
        .progress-bar {
            width: 100%;
            height: 30px;
            background-color: var(--bg-hover);
            border-radius: 15px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background-color: var(--accent-green);
            width: 0%;
            transition: width 0.3s ease;
        }
        .uploaded-file-item {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Subir Video</h1>
        </div>
        
        <div class="upload-container">
            <div class="upload-area" id="upload-area">
                <svg class="upload-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
                </svg>
                <h3>Arrastra y suelta tu video aquí</h3>
                <p>o haz clic para seleccionar</p>
                <p style="font-size: 14px; color: var(--text-secondary); margin-top: 10px;">
                    Formatos permitidos: MP4, WebM, MKV, AVI, MOV, FLV, WMV<br>
                    Tamaño máximo: 5GB
                </p>
                <input type="file" id="file-input" accept="video/*" style="display: none;">
            </div>
            
            <div id="upload-progress" style="display: none;">
                <h4>Subiendo video...</h4>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-bar"></div>
                </div>
                <p id="progress-text" style="text-align: center; margin-top: 10px;">0%</p>
            </div>
            
            <div id="uploaded-files"></div>
        </div>
    </div>
    
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/upload.js"></script>
</body>
</html>