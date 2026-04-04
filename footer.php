<?php
/**
 * Footer 100% Widgets (Izquierda y Derecha)
 */
?>

    <footer id="colophon" class="site-footer">

        <?php if ( get_theme_mod( 'enable_lower_footer', true ) ) : ?>
            
            <div class="footer-lower">
                <div class="container footer-lower-content">
                    
                    <div class="footer-bottom-left-area">
                        <?php if ( is_active_sidebar( 'footer-bottom-left' ) ) : ?>
                            <?php dynamic_sidebar( 'footer-bottom-left' ); ?>
                        <?php else : ?>
                            <span>© <?php echo date('Y'); ?> <?php bloginfo('name'); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="footer-bottom-right-area">
                        <?php if ( is_active_sidebar( 'footer-bottom-right' ) ) : ?>
                            <?php dynamic_sidebar( 'footer-bottom-right' ); ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        <?php endif; ?>

    </footer>

</div><?php wp_footer(); ?>

</body>
</html>