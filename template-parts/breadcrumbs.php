<?php
/**
 * Módulo Visual: Migas de Pan (Breadcrumbs)
 * Se llama desde single.php
 */

// 1. Control de Seguridad: Usamos TU ID 'enable_single_breadcrumbs'
if ( ! get_theme_mod( 'enable_single_breadcrumbs', true ) ) {
    return;
}

// 2. Solo mostrar en entradas (ya que este panel es de entradas)
if ( ! is_single() ) {
    return;
}

// Variables
$home_text = get_theme_mod( 'breadcrumbs_home_text', 'Inicio' );
$sep       = '<span class="sep">/</span>';

?>

<nav class="mainter-breadcrumbs" aria-label="Breadcrumb">
    
        
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="crumb-link home-link">
            <?php echo esc_html( $home_text ); ?>
        </a>
        <?php echo $sep; ?>

        <?php 
        $cats = get_the_category();
        if ( ! empty( $cats ) ) {
            $cat = $cats[0];
            echo '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" class="crumb-link">' . esc_html( $cat->name ) . '</a>';
            echo $sep;
        }
        ?>

        <span class="crumb-current" aria-current="page"><?php the_title(); ?></span>

 
</nav>