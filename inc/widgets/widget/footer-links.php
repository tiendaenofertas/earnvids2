<?php
/**
 * Widget: Mainter Enlaces Pro (SVG Nativo + Sugerencias)
 */
class Mainter_Footer_Links_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'mainter_footer_links', 
            '⚡ Mainter: Enlaces Pro', 
            array( 'description' => 'Enlaces legales o redes sociales (Facebook, X, Youtube, etc) sin plugins.' )
        );
    }

    public function widget( $args, $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $style = ! empty( $instance['style'] ) ? $instance['style'] : 'text'; 

        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        $mode_class = ( $style === 'icons' ) ? 'mode-icons' : 'mode-text';
        echo '<ul class="mainter-links-list ' . $mode_class . '">';

        for ( $i = 1; $i <= 10; $i++ ) {
            $text = ! empty( $instance["text_$i"] ) ? $instance["text_$i"] : '';
            $url  = ! empty( $instance["url_$i"] ) ? $instance["url_$i"] : '';
            $icon_name = ! empty( $instance["icon_$i"] ) ? strtolower( trim( $instance["icon_$i"] ) ) : '';

            if ( ! empty( $url ) ) {
                echo '<li>';
                echo '<a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $text ) . '" target="_blank" rel="noopener noreferrer">';
                
                // LÓGICA DE ICONOS: Si es modo icono y tenemos el nombre, buscamos el SVG
                if ( $style === 'icons' && ! empty( $icon_name ) ) {
                    $svg = $this->get_social_svg( $icon_name );
                    if ( $svg ) {
                        echo $svg; // Imprimimos el SVG limpio
                    } else {
                        // Fallback: Si escribió algo raro, mostramos la primera letra
                        echo '<span class="fallback-icon">' . substr( $text, 0, 1 ) . '</span>';
                    }
                } else {
                    // MODO TEXTO
                    echo esc_html( $text );
                }
                echo '</a>';
                echo '</li>';
            }
        }
        echo '</ul>';
        echo $args['after_widget'];
    }

    // --- FORMULARIO ADMIN ---
    public function form( $instance ) {
        $title = isset( $instance['title'] ) ? $instance['title'] : '';
        $style = isset( $instance['style'] ) ? $instance['style'] : 'text';
        $widget_id = $this->get_field_id( 'wrapper' );
        $pages = get_pages();
        $datalist_id = $this->get_field_id( 'pages_list' );
        ?>
        
        <div id="<?php echo $widget_id; ?>" class="mainter-dynamic-widget">
            
            <style>
                .mainter-dynamic-widget input[list]::-webkit-calendar-picker-indicator {
                    opacity: 0.5; font-size: 14px; cursor: pointer;
                }
                .mainter-dynamic-widget .input-url {
                    padding-right: 25px !important; /* Espacio para la flecha */
                }
            </style>

            <datalist id="<?php echo $datalist_id; ?>">
                <?php foreach ( $pages as $page ) : ?>
                    <option value="<?php echo get_permalink( $page->ID ); ?>"><?php echo esc_html( $page->post_title ); ?></option>
                <?php endforeach; ?>
            </datalist>

            <p>
                <label>Título:</label>
                <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>
            <p>
                <label>Estilo:</label>
                <select class="widefat" name="<?php echo $this->get_field_name( 'style' ); ?>">
                    <option value="text" <?php selected( $style, 'text' ); ?>>Texto (Ej: Aviso Legal)</option>
                    <option value="icons" <?php selected( $style, 'icons' ); ?>>Iconos Sociales (SVG)</option>
                </select>
            </p>

            <hr style="border: 0; border-top: 1px solid #ddd; margin: 15px 0;">

            <div class="items-container">
                <?php 
                for ( $i = 1; $i <= 10; $i++ ) : 
                    $text = isset( $instance["text_$i"] ) ? $instance["text_$i"] : '';
                    $url  = isset( $instance["url_$i"] ) ? $instance["url_$i"] : '';
                    $icon = isset( $instance["icon_$i"] ) ? $instance["icon_$i"] : '';
                    
                    $is_visible = ! empty( $url ) || $i === 1;
                    $display = $is_visible ? 'block' : 'none';
                ?>
                    <div class="item-row item-<?php echo $i; ?>" style="display: <?php echo $display; ?>; background: #fff; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; border-radius: 4px; position: relative;">
                        
                        <div style="position: absolute; top: 5px; right: 5px; display: flex; gap: 5px;">
                            <span title="Subir" onclick="mainterMoveRow(this, -1)" style="cursor: pointer; font-size: 14px;">⬆️</span>
                            <span title="Bajar" onclick="mainterMoveRow(this, 1)" style="cursor: pointer; font-size: 14px;">⬇️</span>
                            <span title="Eliminar" onclick="mainterDeleteRow(this)" style="cursor: pointer; color: #d63638; font-weight: bold; margin-left: 5px;">&times;</span>
                        </div>
                        
                        <h4 style="margin: 0 0 10px; font-size: 11px; text-transform: uppercase; color: #999;">Opción #<?php echo $i; ?></h4>

                        <p style="margin-bottom: 5px;">
                            <input class="widefat" type="text" name="<?php echo $this->get_field_name( "text_$i" ); ?>" value="<?php echo esc_attr( $text ); ?>" placeholder="Nombre (Ej: Facebook)" />
                        </p>
                        <p style="margin-bottom: 5px;">
                            <input class="widefat input-url" list="<?php echo $datalist_id; ?>" type="text" name="<?php echo $this->get_field_name( "url_$i" ); ?>" value="<?php echo esc_attr( $url ); ?>" placeholder="URL (https://...)" />
                        </p>
                        <p style="margin-bottom: 0;">
                            <input class="widefat" type="text" name="<?php echo $this->get_field_name( "icon_$i" ); ?>" value="<?php echo esc_attr( $icon ); ?>" placeholder="Escribe: facebook, x, youtube, tiktok, instagram..." />
                        </p>
                    </div>
                <?php endfor; ?>
            </div>

            <button type="button" class="button button-primary" onclick="mainterAddRow('<?php echo $widget_id; ?>')">➕ Añadir</button>
            
            <script>
                if (typeof mainterMoveRow !== 'function') {
                    function mainterMoveRow(btn, direction) {
                        var row = btn.closest('.item-row');
                        var container = row.parentNode;
                        var rows = Array.from(container.querySelectorAll('.item-row')).filter(r => r.style.display !== 'none');
                        var idx = rows.indexOf(row);
                        var targetIdx = idx + direction;
                        if (targetIdx >= 0 && targetIdx < rows.length) {
                            var inputsA = row.querySelectorAll('input');
                            var inputsB = rows[targetIdx].querySelectorAll('input');
                            for(var i=0; i<inputsA.length; i++){
                                var tmp = inputsA[i].value; inputsA[i].value = inputsB[i].value; inputsB[i].value = tmp;
                            }
                            inputsA[0].dispatchEvent(new Event('change', {bubbles:true}));
                        }
                    }
                    function mainterAddRow(id) {
                        var rows = document.getElementById(id).querySelectorAll('.item-row[style*="display: none"]');
                        if(rows.length) rows[0].style.display = 'block';
                    }
                    function mainterDeleteRow(btn) {
                        if(confirm('¿Borrar?')) {
                            var row = btn.closest('.item-row');
                            row.querySelectorAll('input').forEach(i => { i.value=''; i.dispatchEvent(new Event('change',{bubbles:true})); });
                            row.style.display='none';
                            row.parentNode.appendChild(row);
                        }
                    }
                }
            </script>
        </div>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = sanitize_text_field( $new_instance['title'] );
        $instance['style'] = sanitize_text_field( $new_instance['style'] );
        for ( $i = 1; $i <= 10; $i++ ) {
            $instance["text_$i"] = sanitize_text_field( $new_instance["text_$i"] );
            $instance["url_$i"]  = sanitize_url( $new_instance["url_$i"] );
            $instance["icon_$i"] = sanitize_text_field( $new_instance["icon_$i"] );
        }
        return $instance;
    }

    // --- 🎨 BIBLIOTECA DE ICONOS SVG (Ligeros) ---
    private function get_social_svg( $name ) {
        $icons = array(
            'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" fill="currentColor"><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>',
            'x'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>',
            'twitter'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"><path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg>', // Alias para X
            'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.5 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.9 0-184.9zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>',
            'linkedin'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"><path d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.28c12.4-23.5 42.69-48.3 87.98-48.3 94.09 0 111.28 61.9 111.28 142.3V448z"/></svg>',
            'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" fill="currentColor"><path d="M496 256c0 137-111 248-248 248-25.6 0-50.2-3.9-73.4-11.1 10.1-16.5 25.2-43.5 30.8-65 3-11.6 15.4-59 15.4-59 8.1 15.4 31.7 28.5 56.8 28.5 74.8 0 128.7-68.8 128.7-154.3 0-87.7-63-149.3-156.2-149.3-107.2 0-169.3 77.8-169.3 163.2 0 32.5 12.6 67.9 39.2 87.4 4.3 3.1 9.9 2.5 11.4-2.8 1.1-3.6 3.6-13.8 4.7-17.8.6-2.5-.2-6.1-2.3-8.5-6.4-7.5-10.4-17.2-10.4-31.2 0-40.4 30-76.9 87.1-76.9 47.4 0 73.3 29.3 73.3 68.3 0 41.2-18.1 76-45.2 76-14.9 0-26.1-12.3-22.5-27.5 4.3-18.1 12.6-37.4 12.6-50.3 0-11.6-6.2-21.3-19.2-21.3-15.2 0-27.4 15.7-27.4 36.7 0 13.4 4.5 22.4 4.5 22.4l-18 76.3c-5.4 22.9-2.1 50.8-1 68.3-43.9-19-74.9-63-74.9-113.8 0-68.1 55.4-123.5 123.5-123.5 68.1 0 123.5 55.4 123.5 123.5z"/></svg>',
            'github'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" fill="currentColor"><path d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3.3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5.3-6.2 2.3zm44.2-1.7c-2.9.7-4.9 2.6-4.6 4.9.3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-63.5 0-14 5-25.5 13.2-34.5-1.3-3.5-5.7-16.2 1.2-34 0 0 10.8-3.5 35.5 13.1 10.3-2.9 21.3-4.3 32.5-4.3 11.2 0 22.2 1.5 32.5 4.3 24.7-16.6 35.5-13.1 35.5-13.1 6.9 17.9 2.6 30.7 1.4 34 8.3 9 13.2 20.5 13.2 34.5 0 49.5-56.4 57.3-112.9 63.5 9.1 7.9 17.2 22.9 17.2 46.4 0 33.7-.3 61.1-.3 69.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3.7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3.3 2.9 2.3 3.9 1.6 1 3.6.7 4.3-.7.7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3.7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3.7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z"/></svg>',
            'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"/></svg>',
            'tiktok'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"><path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z"/></svg>'
        );
        return isset( $icons[$name] ) ? $icons[$name] : false;
    }
}

function register_mainter_footer_links() {
    register_widget( 'Mainter_Footer_Links_Widget' );
}
add_action( 'widgets_init', 'register_mainter_footer_links' );