<?php
/**
 * Contenido del post
 */

// LEER EL AVISO: ¿Debemos esconder la cabecera normal?
$suppress_header = get_query_var( 'suppress_header', false );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <?php if ( ! $suppress_header ) : ?>
        <header class="entry-header">
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
            <div class="entry-meta">
                <?php echo get_the_date(); ?>
            </div>
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                    <?php the_post_thumbnail(); ?>
                </div>
            <?php endif; ?>
        </header>
    <?php endif; ?>
    <div class="entry-content">
        <?php the_content(); ?>
    </div>

</article>