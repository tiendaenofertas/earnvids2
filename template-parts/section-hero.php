<?php
/**
 * Hero Section: Versión 1 (Lógica Simple y Limpia)
 */

// 1. Si no está activo en el personalizador, no cargar nada.
if ( ! get_theme_mod( 'hero_enable', false ) ) return;

// 2. Configuración
$cat_id = get_theme_mod( 'hero_category', '0' );
$title  = get_theme_mod( 'hero_title', 'Destacados' );

// 3. La Consulta (Query)
$args = array(
    'posts_per_page'      => 5, // 1 Grande + 4 Pequeños
    'ignore_sticky_posts' => 1,
    'post_status'         => 'publish',
);

// Si eligió una categoría específica
if ( $cat_id != '0' ) { 
    $args['cat'] = $cat_id; 
}

$hero_query = new WP_Query( $args );

// 4. Renderizado
if ( $hero_query->have_posts() ) :
?>
    <section class="hero-bento-section">
        <div class="container">
            
            <?php if ( $title ) : ?>
                <h2 class="section-title">
                    <span style="margin-right:5px;">🔥</span> <?php echo esc_html( $title ); ?>
                </h2>
            <?php endif; ?>

            <div class="bento-grid-wrapper">
                <?php 
                $count = 0;
                while ( $hero_query->have_posts() ) : $hero_query->the_post(); 
                    $count++;
                    
                    // CLASES LÓGICAS:
                    // El primero ($count === 1) es 'bento-big'
                    // Los demás son 'bento-small'
                    $grid_class = ( $count === 1 ) ? 'bento-big' : 'bento-small';
                ?>
                    
                    <article class="bento-item <?php echo $grid_class; ?>">
                        <a href="<?php the_permalink(); ?>" class="bento-link">
                            
                            <div class="bento-image-wrap">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'large' ); ?>
                                <?php else : ?>
                                    <img src="https://via.placeholder.com/800x600?text=Sin+Imagen" alt="Placeholder">
                                <?php endif; ?>
                                <div class="bento-overlay"></div>
                            </div>
                            <?php
                          
                            $hide_badge = get_theme_mod( 'hero_hide_badge', false );

                            // Solo mostramos si es el primero Y NO está oculta la opción
                            if ( $count === 1 && ! $hide_badge ) : ?>
                            <span class="badge-featured">Destacado</span>
                            <?php endif; ?>

                            <div class="bento-content">
                                
                                <?php 
                                $cats = get_the_category();
                                if ( ! empty( $cats ) ) : ?>
                                    <span class="amazon-cat"><?php echo esc_html( $cats[0]->name ); ?></span>
                                <?php endif; ?>

                                <h3 class="bento-title"><?php the_title(); ?></h3>
                                
                                <div class="bento-meta">
                                    <?php echo get_the_date(); ?>
                                </div>
                            </div>

                        </a>
                    </article>

                <?php endwhile; wp_reset_postdata(); ?>
            </div></div>
    </section>
<?php endif; ?>