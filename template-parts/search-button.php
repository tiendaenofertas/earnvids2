<?php
// Si está desactivado, no mostrar el botón
if ( ! get_theme_mod( 'enable_header_search', true ) ) return;
?>
<button id="mainter-search-btn" class="header-search-icon" aria-label="Buscar">
    <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="11" cy="11" r="8"></circle>
        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
</button>