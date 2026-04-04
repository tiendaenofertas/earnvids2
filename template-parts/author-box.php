<?php
/**
 * Caja de Autor SIMPLE (Solo Foto + Bio)
 */

// 1. Si está desactivado en el panel, salir.
if ( ! get_theme_mod( 'enable_author_box', true ) ) return;

// 2. Obtener la descripción. Si no hay, NO mostrar la caja.
$author_bio = get_the_author_meta( 'description' );
if ( empty( $author_bio ) ) return;

// 3. Datos básicos
$author_id   = get_the_author_meta( 'ID' );
$author_name = get_the_author();
$author_url  = get_author_posts_url( $author_id );
$author_avatar = get_avatar( $author_id, 100 ); // Foto de 100px

// Título del panel
$box_title = get_theme_mod( 'author_box_title', 'Sobre el autor' );
?>

<section class="author-box-simple" itemscope itemtype="http://schema.org/Person">
    
    <div class="author-simple-avatar">
        <a href="<?php echo esc_url( $author_url ); ?>">
            <?php echo $author_avatar; ?>
        </a>
    </div>

    <div class="author-simple-content">
        <h4 class="author-simple-name">
            <span class="small-label"><?php echo esc_html( $box_title ); ?>:</span>
            <a href="<?php echo esc_url( $author_url ); ?>" itemprop="name">
                <?php echo esc_html( $author_name ); ?>
            </a>
        </h4>

        <div class="author-simple-bio" itemprop="description">
            <?php echo wp_kses_post( $author_bio ); ?>
        </div>
    </div>

</section>