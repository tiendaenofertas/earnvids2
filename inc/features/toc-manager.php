<?php
/**
 * Generador Automático de Tabla de Contenidos (TOC)
 */

function mainter_inject_toc( $content ) {
    
    // 1. Validaciones: Solo en single, si está activo y es el loop principal
    if ( ! is_singular('post') || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    if ( ! get_theme_mod( 'enable_toc', true ) ) {
        return $content;
    }

    // 2. Buscar Encabezados (H2 y H3)
    // Regex explica: Busca <h2 o <h3, captura atributos, captura título, cierra etiqueta
    $pattern = '/<h([2-3])(.*?)>(.*?)<\/h[2-3]>/i';
    
    // Si no hay suficientes encabezados (menos de 2), no ponemos tabla
    if ( preg_match_all( $pattern, $content, $matches ) < 2 ) {
        return $content;
    }

    // 3. Preparar Variables
    $toc_html = '';
    $headers  = $matches[0]; // Tags completos <h2>Titulo</h2>
    $levels   = $matches[1]; // 2 o 3
    $titles   = $matches[3]; // El texto "Titulo"
    
    $toc_title = get_theme_mod( 'toc_title', 'Índice de contenidos' );
    $is_open   = get_theme_mod( 'toc_open_default', true ) ? 'open' : '';

    // 4. Construir HTML de la Tabla
    $toc_html .= '<div class="mainter-toc-container">';
    $toc_html .= '<details ' . $is_open . ' class="mainter-toc">';
    $toc_html .= '<summary class="toc-header">';
    $toc_html .= '<span class="toc-title-text">' . esc_html( $toc_title ) . '</span>';
    $toc_html .= '<span class="toc-toggle-icon">▼</span>';
    $toc_html .= '</summary>';
    $toc_html .= '<nav class="toc-list-wrapper"><ul>';

    foreach ( $titles as $index => $title ) {
        // Limpiamos el título para crear un ID (ej: "Mi Título" -> "mi-titulo")
        $clean_title = strip_tags( $title );
        $anchor_id   = sanitize_title( $clean_title );
        
        // Clase para indentar H3
        $class = ( $levels[$index] == '3' ) ? 'toc-subitem' : 'toc-item';

        $toc_html .= '<li class="' . $class . '"><a href="#' . $anchor_id . '">' . $clean_title . '</a></li>';

        // 5. Modificar el contenido original para agregar el ID al encabezado
        // Reemplazamos <h2>Titulo</h2> por <h2 id="mi-titulo">Titulo</h2>
        $old_tag = $headers[$index];
        $new_tag = '<h' . $levels[$index] . ' id="' . $anchor_id . '"' . $matches[2][$index] . '>' . $title . '</h' . $levels[$index] . '>';
        
        // Usamos str_replace limit 1 para no reemplazar duplicados erróneamente
        $content = preg_replace( '/' . preg_quote( $old_tag, '/' ) . '/', $new_tag, $content, 1 );
    }

    $toc_html .= '</ul></nav>';
    $toc_html .= '</details>';
    $toc_html .= '</div>';

    // 6. Inyectar después del primer párrafo
    $paragraphs = explode( '</p>', $content );
    
    // Si hay más de 1 párrafo, insertamos en medio. Si no, al principio.
    if ( count( $paragraphs ) > 1 ) {
        // Insertar en la posición 1 (después del primer <p>)
        array_splice( $paragraphs, 1, 0, $toc_html );
        $new_content = implode( '</p>', $paragraphs );
    } else {
        $new_content = $toc_html . $content;
    }

    return $new_content;
}
add_filter( 'the_content', 'mainter_inject_toc', 15 ); // Prioridad 15 para ejecutarse antes que los anuncios (si usaste 20)