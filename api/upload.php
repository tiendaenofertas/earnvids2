<?php
// api/upload.php - Sistema completo de subida de videos
require_once '../config/app.php';
require_once '../includes/functions.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Debugging inicial
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en JSON

// Log de errores
function logError($message) {
    error_log("[EARNVIDS Upload] " . $message);
}

// Verificar autenticación
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar si hay archivo
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'No se recibió ningún archivo';
    if (isset($_FILES['video']['error'])) {
        switch ($_FILES['video']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage = 'El archivo excede el tamaño máximo permitido por PHP';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'El archivo excede el tamaño máximo del formulario';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'El archivo se subió parcialmente';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No se seleccionó ningún archivo';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage = 'Falta carpeta temporal en el servidor';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage = 'Error al escribir el archivo en el servidor';
                break;
        }
    }
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}

$file = $_FILES['video'];
$title = $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME);

// Validar extensión
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ALLOWED_EXTENSIONS)) {
    echo json_encode(['success' => false, 'message' => 'Formato de archivo no permitido']);
    exit;
}

// Validar tamaño
if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['success' => false, 'message' => 'El archivo excede el tamaño máximo de 5GB']);
    exit;
}

try {
    // Generar nombres únicos
    $embedCode = generateEmbedCode();
    $fileName = $embedCode . '_' . time() . '.' . $extension;
    
    // Obtener configuración de almacenamiento activo
    $stmt = db()->query("SELECT * FROM storage_config WHERE is_active = 1 LIMIT 1");
    $storage = $stmt->fetch();
    
    if (!$storage) {
        throw new Exception('No hay almacenamiento activo configurado');
    }
    
    logError("Almacenamiento activo: " . $storage['storage_type']);
    
    $uploadSuccess = false;
    $storagePath = '';
    
    // Procesar según tipo de almacenamiento
    switch ($storage['storage_type']) {
        case 'local':
            // Almacenamiento local
            $uploadDir = '../uploads/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $uploadSuccess = true;
                $storagePath = 'videos/' . $fileName;
                logError("Archivo subido localmente: " . $targetPath);
            } else {
                throw new Exception('Error al mover el archivo');
            }
            break;
            
        case 'contabo':
            // Subida a Contabo S3
            require_once '../includes/storage_manager.php';
            
            try {
                $s3Client = new SimpleS3Client(
                    $storage['access_key'],
                    $storage['secret_key'],
                    $storage['endpoint'],
                    $storage['region']
                );
                
                $key = 'videos/' . $fileName;
                $result = $s3Client->putObject(
                    $storage['bucket'],
                    $key,
                    $file['tmp_name'],
                    'video/' . $extension
                );
                
                if ($result) {
                    $uploadSuccess = true;
                    $storagePath = $key;
                    logError("Archivo subido a Contabo: " . $key);
                } else {
                    throw new Exception('Error al subir a Contabo S3');
                }
            } catch (Exception $e) {
                logError("Error Contabo: " . $e->getMessage());
                throw new Exception('Error al subir a almacenamiento externo: ' . $e->getMessage());
            }
            break;
            
        default:
            throw new Exception('Tipo de almacenamiento no soportado');
    }
    
    if (!$uploadSuccess) {
        throw new Exception('Error al procesar la subida del archivo');
    }
    
    // Guardar en base de datos
    $stmt = db()->prepare("
        INSERT INTO videos (user_id, title, filename, original_name, file_size, 
                          storage_type, storage_path, embed_code, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $title,
        $fileName,
        $file['name'],
        $file['size'],
        $storage['storage_type'],
        $storagePath,
        $embedCode
    ]);
    
    $videoId = db()->lastInsertId();
    
    // Actualizar estadísticas del usuario
    $stmt = db()->prepare("
        UPDATE users 
        SET total_files = total_files + 1,
            total_storage = total_storage + ?
        WHERE id = ?
    ");
    $stmt->execute([$file['size'], $_SESSION['user_id']]);
    
    // Log de actividad
    logActivity('upload_video', [
        'video_id' => $videoId,
        'filename' => $fileName,
        'size' => $file['size']
    ]);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'video_id' => $videoId,
        'embed_code' => $embedCode,
        'title' => $title,
        'message' => 'Video subido exitosamente'
    ]);
    
} catch (Exception $e) {
    logError("Error general: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
