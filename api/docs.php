<?php
require_once '../config/app.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .api-docs {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .endpoint {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            margin-right: 10px;
        }
        .method.get { background-color: #00a8ff; color: white; }
        .method.post { background-color: #00ff88; color: var(--bg-primary); }
        .method.delete { background-color: #ff3b3b; color: white; }
        .code-block {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 15px 0;
        }
        .param-table {
            width: 100%;
            margin: 15px 0;
            border-collapse: collapse;
        }
        .param-table th {
            background-color: var(--bg-secondary);
            padding: 10px;
            text-align: left;
            font-weight: 600;
        }
        .param-table td {
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="api-docs">
        <div class="logo" style="text-align: center; margin-bottom: 40px;">
            <h1 style="font-size: 48px;">EARN<span style="color: var(--accent-green);">VIDS</span> API</h1>
            <p style="color: var(--text-secondary); font-size: 18px;">Documentación de la API REST</p>
        </div>
        
        <div class="endpoint">
            <h2>Autenticación</h2>
            <p>Todas las solicitudes a la API requieren un API Key válido en el header:</p>
            <div class="code-block">
X-API-Key: tu_api_key_aqui
            </div>
        </div>
        
        <div class="endpoint">
            <h3><span class="method get">GET</span> /api/videos</h3>
            <p>Obtener lista de videos del usuario</p>
            
            <h4>Parámetros</h4>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>page</td>
                        <td>integer</td>
                        <td>Número de página (default: 1)</td>
                    </tr>
                    <tr>
                        <td>limit</td>
                        <td>integer</td>
                        <td>Videos por página (max: 100)</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Respuesta</h4>
            <div class="code-block">
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Mi Video",
            "embed_code": "abc123",
            "views": 150,
            "created_at": "2024-01-15",
            "url": "https://earnvids.com/watch/abc123"
        }
    ],
    "pagination": {
        "page": 1,
        "total_pages": 5,
        "total_videos": 48
    }
}
            </div>
        </div>
        
        <div class="endpoint">
            <h3><span class="method post">POST</span> /api/upload</h3>
            <p>Subir un nuevo video</p>
            
            <h4>Parámetros (multipart/form-data)</h4>
            <table class="param-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>video</td>
                        <td>file</td>
                        <td>Archivo de video (requerido)</td>
                    </tr>
                    <tr>
                        <td>title</td>
                        <td>string</td>
                        <td>Título del video</td>
                    </tr>
                </tbody>
            </table>
            
            <h4>Ejemplo cURL</h4>
            <div class="code-block">
curl -X POST https://earnvids.com/api/upload \
  -H "X-API-Key: tu_api_key" \
  -F "video=@/path/to/video.mp4" \
  -F "title=Mi Video Increíble"
            </div>
        </div>
        
        <div class="endpoint">
            <h3><span class="method delete">DELETE</span> /api/videos/{embed_code}</h3>
            <p>Eliminar un video</p>
            
            <h4>Respuesta</h4>
            <div class="code-block">
{
    "success": true,
    "message": "Video eliminado exitosamente"
}
            </div>
        </div>
        
        <div class="endpoint">
            <h3><span class="method get">GET</span> /api/stats</h3>
            <p>Obtener estadísticas de la cuenta</p>
            
            <h4>Respuesta</h4>
            <div class="code-block">
{
    "success": true,
    "stats": {
        "total_videos": 48,
        "total_views": 15420,
        "total_storage": 52428800000,
        "total_downloads": 342
    }
}
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 60px; padding: 40px; background-color: var(--bg-card); border-radius: 12px;">
            <h3>¿Necesitas ayuda?</h3>
            <p style="color: var(--text-secondary); margin: 20px 0;">
                Encuentra tu API Key en tu panel de cuenta
            </p>
            <a href="/account.php" class="btn">Ir a Mi Cuenta</a>
        </div>
    </div>
</body>
</html>