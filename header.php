<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<script>
    (function() {
        // Recuperar preferencia antes de pintar la web
        var savedTheme = localStorage.getItem('mainter_theme');
        
        // Si es 'dark', añadimos la clase AL INSTANTE
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    })();
</script>

<header id="masthead" class="site-header mainter-header">
    <div class="container header-container">

        <div class="site-branding">
            <?php
            if ( has_custom_logo() ) {
                the_custom_logo();
            } else {
                ?>
                <h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
                <?php
            }
            ?>
        </div>
        

        <div class="main-navigation-icon">
        <nav id="site-navigation" class="main-navigation">

            
            <button id="mobile-menu-close" class="mobile-close">×</button>

            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'menu-1', // Asegúrate de que este ID coincida con tu register_nav_menus
                    'menu_id'        => 'primary-menu',
                    'container'      => false, // Sin div extra, limpiamos el código
                    'menu_class'     => 'menu-list', // Clase para estilar UL
                )
            );
            ?>



        </nav>
        <button id="mobile-menu-toggle" class="mobile-toggle" aria-controls="site-navigation" aria-expanded="false" aria-label="Abrir menú principal">
           <svg class="i i-bars" width="23" height="23" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 6h18M3 12h18M3 18h18"></path>
            </svg>
        </button>
        <?php get_template_part( 'template-parts/search-button' ); ?>
         <?php if ( get_theme_mod( 'enable_dark_mode_btn', true ) ) : ?>
            <button id="theme-toggle" class="theme-toggle" aria-label="Cambiar modo oscuro">
                <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                
                <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
        <?php endif; ?>
        </div>
            
    </div>
    </header>

    <?php if ( function_exists('mainter_render_ad') ) mainter_render_ad( 'ad_header' ); ?>