<?php
/**
 * Vista visual del Hero en Single
 */
?>
<header class="single-hero-header">
    
    <div class="single-hero-bg">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'full' ); ?>
        <?php endif; ?>
        <div class="single-hero-overlay"></div>
    </div>

    <div class="container single-hero-container">
        <div class="single-hero-content">
            
            <?php 
            $cats = get_the_category();
            if ( ! empty( $cats ) ) : ?>
                <span class="hero-cat"><?php echo esc_html( $cats[0]->name ); ?></span>
            <?php endif; ?>

            <?php the_title( '<h1 class="entry-title single-hero-title">', '</h1>' ); ?>
            
            <div class="entry-meta single-hero-meta">
                <span><?php echo get_the_date(); ?></span>
            </div>

        </div>
    </div>
</header>