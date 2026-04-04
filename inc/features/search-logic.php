<?php
/**
 * Lógica del Buscador (Overlay + JS) - Versión Elegante UI
 */
function mainter_search_functionality() {
    // Si está desactivado, no cargar nada.
    if ( ! get_theme_mod( 'enable_header_search', true ) ) return;
    ?>

    <div id="mainter-search-overlay" class="search-overlay">
        
        <button id="mainter-search-close" class="close-btn" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        
        <div class="search-container">
            <form role="search" method="get" class="mainter-clean-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="search" 
                       id="mainter-search-input" 
                       class="search-field-hero" 
                       placeholder="Escribe para buscar..." 
                       value="<?php echo get_search_query(); ?>" 
                       name="s" 
                       autocomplete="off" />
                
                <button type="submit" class="search-submit-icon" aria-label="Buscar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h13M12 5l7 7-7 7"/></svg>
                </button>
            </form>
            <p class="search-hint">Presiona <span>Enter</span> para buscar</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('mainter-search-btn');
        const overlay = document.getElementById('mainter-search-overlay');
        const close   = document.getElementById('mainter-search-close');
        const input   = document.getElementById('mainter-search-input');

        if ( trigger && overlay ) {
            // ABRIR
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                overlay.classList.add('is-active');
                document.body.style.overflow = 'hidden'; // Bloquear scroll
                if(input) setTimeout(() => input.focus(), 100); // Foco automático
            });

            // CERRAR
            function closeSearch() {
                overlay.classList.remove('is-active');
                document.body.style.overflow = '';
            }

            if(close) close.addEventListener('click', closeSearch);

            // Cerrar con ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && overlay.classList.contains('is-active')) {
                    closeSearch();
                }
            });
        }
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'mainter_search_functionality' );