<?php get_header();

// 1. ¿Debemos mostrar el Hero? 
// (Solo si está activo en el panel Y tiene imagen destacada)
$show_hero = get_theme_mod( 'single_hero_enable', false ) && has_post_thumbnail();

// 2. SI es verdad, cargamos el archivo del Paso 2
if ( $show_hero ) {
    get_template_part( 'template-parts/hero', 'single' );
}
?>

<div class="site-content-wrapper">
    
    <div class="mainter-grid-layout">

	<main id="primary" class="site-main ">
  

        <?php
        while ( have_posts() ) :
            the_post();



            // 3. TRUCO IMPORTANTE:
            // Pasamos un aviso (variable) a la siguiente parte para decirle
            // "Oye, ya mostré el título arriba, escóndelo abajo".
            set_query_var( 'suppress_header', $show_hero );

            // 1. AQUÍ CARGAMOS LAS MIGAS DE PAN
            get_template_part( 'template-parts/breadcrumbs' );

            get_template_part( 'template-parts/content', 'single' );

            get_template_part( 'template-parts/social', 'share' ); 

           get_template_part( 'template-parts/related', 'posts' );


            get_template_part( 'template-parts/author', 'box' );

            // Comentarios, navegación, etc...
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;

        endwhile; 
        ?>

    </main>
      <?php 
        // Usamos la función lógica que creamos antes para decidir si cargarlo
        if ( mainter_has_sidebar() ) {
            get_sidebar(); // Esto carga sidebar.php
        }
        ?>

</div>
</div>
<?php get_footer(); ?>
