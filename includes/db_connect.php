<?php
// includes/db_connect.php
// Detectar la configuración de base de datos sin importar desde dónde se llame
$dbConfigFile = __DIR__ . '/../config/database.php';

if (!file_exists($dbConfigFile)) {
    die("Error Crítico: No se encuentra config/database.php en: " . $dbConfigFile);
}

require_once $dbConfigFile;

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            // Usamos 127.0.0.1 para evitar latencia DNS en localhost (Error 504)
            $host = (defined('DB_HOST') && DB_HOST === 'localhost') ? '127.0.0.1' : DB_HOST;
            
            $this->conn = new PDO(
                "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5 // Timeout rápido para no colgar el servidor
                ]
            );
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("<h1>Error 500</h1><p>Error de conexión a la base de datos.</p>");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

function db() {
    return Database::getInstance()->getConnection();
}
?>
