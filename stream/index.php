<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/config.php";

class MP4FastStream {
    private string $clientIP;

    public function __construct() {
        if (ob_get_level()) ob_end_clean();
        setSecurityHeaders();
        $this->clientIP = getClientIP();
    }

    public function process(): void {
        if (!validateReferer()) { $this->showError("Acceso no autorizado", 403); return; }

        $id = preg_replace('/[^a-zA-Z0-9]/', '', $_GET["v"] ?? "");
        if (empty($id)) { $this->showError("Datos requeridos", 400); return; }

        $subDir = substr($id, 0, 2);
        $file = __DIR__ . '/../config/links/' . $subDir . '/' . $id . '.json';
        
        if (!file_exists($file)) { $this->showError("Video no encontrado", 404); return; }

        $data = json_decode(file_get_contents($file), true);
        $url = $data['link'] ?? '';

        if (!validateUrl($url)) { $this->showError("URL inválida", 400); return; }

        // REDIRECCIÓN DIRECTA (NO PROXY) - Ultra rápido, 0 consumo de ancho de banda
        header("Location: " . $url, true, 302);
        exit;
    }

    private function showError(string $message, int $code = 400): void {
        http_response_code($code); header('Content-Type: application/json');
        echo json_encode(['error' => $message]); exit;
    }
}

$stream = new MP4FastStream();
$stream->process();
?>
