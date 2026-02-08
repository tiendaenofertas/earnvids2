<?php
// includes/sidebar.php - Diseño Minimalista (Estilo Dashboard Pro)
$current_page = basename($_SERVER['PHP_SELF']);
// Mapeo de URLs amigables para mantener la clase 'active'
$uri = $_SERVER['REQUEST_URI'];

function isActive($pageName, $uri) {
    if (strpos($uri, $pageName) !== false) return 'active';
    // Fallback para archivos php directos
    if (strpos($_SERVER['PHP_SELF'], $pageName . '.php') !== false) return 'active';
    return '';
}

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<div class="sidebar">
    <div class="logo">
        <a href="/Dashboard" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="var(--accent-green)">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
            </svg>
            <h1>EARN<span>VIDS</span></h1>
        </a>
    </div>
    
    <ul class="nav-menu">
        <li>
            <a href="/Dashboard" class="nav-link <?= $uri == '/' || isActive('index', $uri) || isActive('Dashboard', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Dashboard
            </a>
        </li>
        <li>
            <a href="/SubirVideo" class="nav-link <?= isActive('upload', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/></svg>
                Subir
            </a>
        </li>
        <li>
            <a href="/MisVideos" class="nav-link <?= isActive('videos', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 3v2h-2V3H8v2H6V3H4v18h2v-2h2v2h8v-2h2v2h2V3h-2zM8 17H6v-2h2v2zm0-4H6v-2h2v2zm0-4H6V7h2v2zm10 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z"/></svg>
                Mis Videos
            </a>
        </li>
        <li>
            <a href="/Cuenta" class="nav-link <?= isActive('account', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Cuenta
            </a>
        </li>

        <?php if ($isAdmin): ?>
        <li class="nav-separator"></li>

        <li>
            <a href="/User" class="nav-link <?= isActive('users', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Usuarios
            </a>
        </li>
        <li>
            <a href="/Almacenamiento" class="nav-link <?= isActive('storage', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 20h20v-4H2v4zm2-3h2v2H4v-2zM2 4v4h20V4H2zm4 3H4V5h2v2zm-4 7h20v-4H2v4zm2-3h2v2H4v-2z"/></svg>
                Almacenamiento
            </a>
        </li>
        <li>
            <a href="/Configuracion" class="nav-link <?= isActive('settings', $uri) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                Configuración
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <a href="/Salir" class="nav-link logout">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/></svg>
            Cerrar Sesión
        </a>
    </div>
</div>
