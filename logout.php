<?php
// logout.php - Cierre de sesión y redirección a URL limpia
require_once 'config/app.php';
require_once 'includes/auth.php';

// Ejecuta la función de cierre de sesión (destruye variables y cookies)
Auth::logout();

// Fuerza la redirección a la URL amigable
header('Location: /login');
exit;
