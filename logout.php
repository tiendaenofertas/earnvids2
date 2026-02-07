<?php
// logout.php - Cierre de sesión seguro y absoluto
require_once 'config/app.php';
require_once 'includes/auth.php'; // Para acceder a helpers de sesión

// 1. Iniciar sesión si no está iniciada (para poder destruirla)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Destruir todas las variables de sesión
$_SESSION = [];

// 3. Borrar la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redirigir al login con mensaje limpio
header("Location: /login.php?msg=logged_out");
exit;
?>
