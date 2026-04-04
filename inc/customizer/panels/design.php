<?php
/**
 * Panel de Diseño: Layout, Tamaños y Bordes
 */

// Clase Control Híbrido (Slider + Input)
if ( class_exists( 'WP_Customize_Control' ) ) {
    class Mainter_Range_Text_Control extends WP_Customize_Control {
        public $type = 'mainter_slider_text';
        public function render_content() {
            ?>
            <label>
                <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <?php if ( ! empty( $this->description ) ) : ?><span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span><?php endif; ?>
                <div style="display:flex;align-items:center;gap:10px;margin-top:5px;">
                    <input type="range" value="<?php echo esc_attr($this->value()); ?>" min="<?php echo esc_attr($this->input_attrs['min']); ?>" max="<?php echo esc_attr($this->input_attrs['max']); ?>" step="<?php echo esc_attr($this->input_attrs['step']); ?>" style="flex-grow:1;margin:0;" oninput="this.nextElementSibling.value=this.value;jQuery(this.nextElementSibling).trigger('change');">
                    <input type="number" <?php $this->link(); ?> value="<?php echo esc_attr($this->value()); ?>" min="<?php echo esc_attr($this->input_attrs['min']); ?>" max="<?php echo esc_attr($this->input_attrs['max']); ?>" step="<?php echo esc_attr($this->input_attrs['step']); ?>" style="width:70px;text-align:center;" oninput="this.previousElementSibling.value=this.value;">
                </div>
            </label>
            <?php
        }
    }
}

function mainter_customize_design( $wp_customize ) {

    $wp_customize->add_section( 'mainter_design_section', array(
        'title'    => __( 'Layout', 'mainter' ),
        'priority' => 22,
        'panel'    => 'mainter_options_panel',
    ));

    // 1. ANCHO CONTENEDOR
    $wp_customize->add_setting( 'container_width', array( 'default' => 1200, 'sanitize_callback' => 'absint', 'transport' => 'refresh' ));
    $wp_customize->add_control( new Mainter_Range_Text_Control( $wp_customize, 'container_width', array(
        'label' => 'Ancho Máximo (px)',
        'section' => 'mainter_design_section',
        'input_attrs' => array( 'min' => 600, 'max' => 2500, 'step' => 10 ),
    )));

    // 2. ANCHO DEL SIDEBAR (Nuevo)
    $wp_customize->add_setting( 'sidebar_width_pct', array( 'default' => 30, 'sanitize_callback' => 'absint' ));
    $wp_customize->add_control( new Mainter_Range_Text_Control( $wp_customize, 'sidebar_width_pct', array(
        'label' => 'Ancho del Sidebar (%)',
        'description' => 'Porcentaje que ocupa la barra lateral en escritorio.',
        'section' => 'mainter_design_section',
        'input_attrs' => array( 'min' => 20, 'max' => 50, 'step' => 1 ), // Entre 20% y 50%
    )));

    // 3. FUENTE BASE
    $wp_customize->add_setting( 'body_font_size', array( 'default' => 16, 'sanitize_callback' => 'absint' ));
    $wp_customize->add_control( new Mainter_Range_Text_Control( $wp_customize, 'body_font_size', array(
        'label' => 'Tamaño Fuente Base (px)',
        'section' => 'mainter_design_section',
        'input_attrs' => array( 'min' => 12, 'max' => 24, 'step' => 1 ),
    )));

    // 4. BORDE IMÁGENES (Nuevo)
    $wp_customize->add_setting( 'img_radius', array( 'default' => 8, 'sanitize_callback' => 'absint' ));
    $wp_customize->add_control( new Mainter_Range_Text_Control( $wp_customize, 'img_radius', array(
        'label' => 'Redondeo de Imágenes (px)',
        'section' => 'mainter_design_section',
        'input_attrs' => array( 'min' => 0, 'max' => 50, 'step' => 1 ),
    )));

    // 5. BORDE BOTONES
    $wp_customize->add_setting( 'btn_radius', array( 'default' => 4, 'sanitize_callback' => 'absint' ));
    $wp_customize->add_control( new Mainter_Range_Text_Control( $wp_customize, 'btn_radius', array(
        'label' => 'Redondeo de Botones (px)',
        'section' => 'mainter_design_section',
        'input_attrs' => array( 'min' => 0, 'max' => 50, 'step' => 1 ),
    )));

    // 6. ESCALA BOTONES
    $wp_customize->add_setting( 'btn_padding_factor', array( 'default' => 1, 'sanitize_callback' => 'mainter_sanitize_float' ));
    $wp_customize->add_control( new Mainter_Range_Text_Control( $wp_customize, 'btn_padding_factor', array(
        'label' => 'Escala de Botones',
        'section' => 'mainter_design_section',
        'input_attrs' => array( 'min' => 0.6, 'max' => 2.0, 'step' => 0.1 ),
    )));
}
add_action( 'customize_register', 'mainter_customize_design' );

if ( ! function_exists( 'mainter_sanitize_float' ) ) {
    function mainter_sanitize_float( $input ) { return filter_var( $input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ); }
}