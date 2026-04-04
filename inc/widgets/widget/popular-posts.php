<?php
/**
 * Widget: Entradas Populares (Integrado con Diseño Global)
 */
class Mainter_Popular_Posts_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'mainter_popular_posts',
            '🔥 Mainter: Entradas Populares',
            array( 'description' => 'Muestra las entradas más comentadas usando el diseño global de tarjetas.' )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 3;
        
        $pop_args = array(
            'posts_per_page'      => $number,
            'no_found_rows'       => true,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'orderby'             => 'comment_count', 
            'order'               => 'DESC',
        );

        $pop_query = new WP_Query( $pop_args );

        if ( $pop_query->have_posts() ) : ?>
            
            <div class="mainter-widget-stack">
                
                <?php while ( $pop_query->have_posts() ) : $pop_query->the_post(); ?>
                    
                    <?php get_template_part( 'template-parts/content-card' ); ?>

                <?php endwhile; ?>
                
            </div>

            <?php
            wp_reset_postdata();
        endif;

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title  = isset( $instance['title'] ) ? $instance['title'] : 'Populares';
        $number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 3;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">Título:</label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>">Cantidad:</label>
            <input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title']  = sanitize_text_field( $new_instance['title'] );
        $instance['number'] = absint( $new_instance['number'] );
        return $instance;
    }
}