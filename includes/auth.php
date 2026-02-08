<?php
// includes/auth.php - Autenticación con URLs Limpias
session_start();

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para requerir login en páginas protegidas
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Login'); // Redirección limpia
        exit;
    }
}

// Función para redirigir si ya está logueado (ej: al entrar a login.php)
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header('Location: /Dashboard'); // Redirección limpia
        exit;
    }
}

// Función para verificar rol de administrador
function requireAdmin() {
    requireLogin();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        echo "Acceso denegado. Se requieren permisos de administrador.";
        exit;
    }
}
?>
