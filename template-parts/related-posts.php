<?php
/**
 * LÓGICA DE RELACIONADOS
 * 1. Verifica si el usuario activó la opción.
 * 2. Busca posts de la misma categoría.
 * 3. Muestra las tarjetas usando 'content-card.php'.
 */

// A. Si en el panel está desactivado, NO hacemos nada.
if ( ! get_theme_mod( 'enable_related_posts', true ) ) return;

global $post;

// B. Obtener categorías del post actual
$cats = get_the_category( $post->ID );

if ( $cats ) {
    $cat_ids = array();
    foreach ( $cats as $individual_cat ) {
        $cat_ids[] = $individual_cat->term_id;
    }

    // C. La Consulta (Query)
    $args = array(
        'category__in'     => $cat_ids,        // Misma categoría
        'post__not_in'     => array( $post->ID ), // No mostrar el post que ya estamos leyendo
        'posts_per_page'   => 6,               // Mostrar 3 tarjetas
        'orderby'          => 'rand',          // Orden aleatorio
        'ignore_sticky_posts' => 1,
    );

    $related_query = new WP_Query( $args );

    // D. Si encontramos posts, mostramos la sección
    if ( $related_query->have_posts() ) :
        $title = get_theme_mod( 'related_posts_title', 'Te podría interesar' );
        ?>

        <section class="related-posts-section">
            <?php if ( $title ) : ?>
                <h3 class="related-title"><?php echo esc_html( $title ); ?></h3>
            <?php endif; ?>

            <div class="related-grid-wrapper">
                <?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
                    
                    <div class="related-item">
                        <?php get_template_part( 'template-parts/content', 'card' ); ?>
                    </div>

                <?php endwhile; ?>
            </div>
        </section>

        <?php
        wp_reset_postdata(); // Importante: Limpiar datos después del query
    endif;
}
?>