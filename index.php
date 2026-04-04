<?php get_header(); ?>

<?php 
if ( is_home() && ! is_paged() ) {
    // Primero carga el loader (functions) y luego la plantilla
    // Asegúrate de requerir el archivo panel en functions.php primero
    get_template_part( 'template-parts/section', 'hero' );
}
?>


<div class="site-content-wrapper">
    
    <div class="mainter-grid-layout">

	<main id="primary" class="site-main ">

    <?php if ( have_posts() ) : ?>

        <header class="page-header">
            </header>

        <div class="blog-grid-layout my-2">
            <?php
            /* Start the Loop */
            while ( have_posts() ) :
                the_post();

                // Cargamos nuestra nueva tarjeta
                get_template_part( 'template-parts/content', 'card' );

            endwhile;

            ?>
        </div><?php
        // 👇 AQUÍ VA LA PAGINACIÓN
        mainter_pagination();// Paginación
        ?>

    <?php else : ?>
        <?php endif; ?>



	</main><!-- #main -->

	     <?php 
        // Usamos la función lógica que creamos antes para decidir si cargarlo
        if ( mainter_has_sidebar() ) {
            get_sidebar(); // Esto carga sidebar.php
        }
        ?>
   

    </div>
    </div>

<?php get_footer(); ?>