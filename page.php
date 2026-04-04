<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package mainter
 */

get_header();
?>

<div class="site-content-wrapper">
    
    <div class="mainter-grid-layout">

	<main id="primary" class="site-main ">
	

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content', get_post_type('page') );

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;

		endwhile; // End of the loop.
		?>

		

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