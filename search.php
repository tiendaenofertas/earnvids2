<?php get_header(); ?>
<div class="site-content-wrapper">
  
	<main id="primary" class="site-main">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<h1 class="page-title">
					<?php
					/* translators: %s: search query. */
					printf( esc_html__( 'Search Results for: %s', 'mainter' ), '<span>' . get_search_query() . '</span>' );
					?>
				</h1>
			</header><!-- .page-header -->

			<div class="blog-grid-layout my-2">
			<?php
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

			
				get_template_part( 'template-parts/content', 'card'  );

			endwhile;

			mainter_pagination();// Paginación

		else :

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>
		</div>
	</main><!-- #main -->

  <?php 
        // Usamos la función lógica que creamos antes para decidir si cargarlo
        if ( mainter_has_sidebar() ) {
            get_sidebar(); // Esto carga sidebar.php
        }
        ?>


</div>

<?php

get_footer();
 ?>

