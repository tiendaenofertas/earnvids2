<?php
// includes/auth.php - Autenticación Blindada con Rate Limiting
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

class Auth {
    
    // Función para obtener la IP Real (Traspasa Cloudflare y Proxys)
    private static function getRealIp() {
        return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function login($username, $password) {
        $ip = self::getRealIp();
        
        // 1. VERIFICAR RATE LIMITING (Anti Fuerza-Bruta)
        $stmt = db()->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip]);
        $attemptData = $stmt->fetch();
        
        if ($attemptData) {
            $lastAttemptTime = strtotime($attemptData['last_attempt']);
            $minutesPassed = (time() - $lastAttemptTime) / 60;
            
            // Si tiene 5 o más intentos fallidos y no han pasado 15 minutos -> BLOQUEAR
            if ($attemptData['attempts'] >= 5 && $minutesPassed < 15) {
                throw new Exception("Por seguridad, tu acceso ha sido bloqueado temporalmente. Intenta en 15 minutos.");
            }
            
            // Si ya pasaron los 15 minutos de castigo, perdonar y resetear el contador
            if ($minutesPassed >= 15) {
                db()->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip_address = ?")->execute([$ip]);
            }
        }

        // 2. PROCESO DE LOGIN NORMAL
        $stmt = db()->prepare("
            SELECT id, username, email, password, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // LOGIN EXITOSO: Limpiar el historial de errores de esta IP
            db()->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            $stmt = db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            if (function_exists('logActivity')) {
                logActivity('login', ['username' => $user['username']]);
            }
            return true;
        }
        
        // 3. LOGIN FALLIDO: Registrar el error en la base de datos para esta IP
        if ($attemptData) {
            db()->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = ?")->execute([$ip]);
        } else {
            db()->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW())")->execute([$ip]);
        }
        
        return false;
    }
    
    public static function register($username, $email, $password) {
        $stmt = db()->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Usuario o email ya existe'];
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $apiKey = md5($username . time());
        
        $stmt = db()->prepare("
            INSERT INTO users (username, email, password, api_key)
            VALUES (?, ?, ?, ?)
        ");
        
        try {
            $stmt->execute([$username, $email, $hashedPassword, $apiKey]);
            if (function_exists('logActivity')) {
                logActivity('register', ['username' => $username]);
            }
            return ['success' => true, 'user_id' => db()->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al crear usuario'];
        }
    }
        
    public static function logout() {
        // Ya no guardamos el logout en la Base de Datos para ahorrar espacio
        session_destroy();

        // Redirige a la URL limpia de inicio de sesión
        header('Location: /login');
        exit;
    }
    
    public static function verifyApiKey($apiKey) {
        $stmt = db()->prepare("SELECT id, username FROM users WHERE api_key = ? AND status = 'active'");
        $stmt->execute([$apiKey]);
        return $stmt->fetch();
    }
}
