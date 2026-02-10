<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/functions.php';

class Auth {
    public static function login($username, $password) {
        $stmt = db()->prepare("
            SELECT id, username, email, password, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            $stmt = db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            logActivity('login', ['username' => $user['username']]);
            return true;
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
            logActivity('register', ['username' => $username]);
            return ['success' => true, 'user_id' => db()->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error al crear usuario'];
        }
    }
    
    public static function logout() {
        logActivity('logout', ['username' => $_SESSION['username'] ?? '']);
        session_destroy();
        header('Location: /login.php');
        exit;
    }
    
    public static function verifyApiKey($apiKey) {
        $stmt = db()->prepare("SELECT id, username FROM users WHERE api_key = ? AND status = 'active'");
        $stmt->execute([$apiKey]);
        return $stmt->fetch();
    }
}