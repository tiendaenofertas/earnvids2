<?php
/**
 * mainter functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package mainter
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.1' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function mainter_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on mainter, use a find and replace
		* to change 'mainter' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'mainter', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'mainter' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);


}
add_action( 'after_setup_theme', 'mainter_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function mainter_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'mainter_content_width', 640 );
}
add_action( 'after_setup_theme', 'mainter_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function mainter_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'mainter' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'mainter' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'mainter_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function mainter_scripts() {
	wp_enqueue_style( 'mainter-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'mainter-style', 'rtl', 'replace' );

	wp_enqueue_script( 'mainter-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'mainter_scripts' );



/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}


// Cargar el núcleo del panel de administración modular
require get_template_directory() . '/inc/admin/init.php';

require get_template_directory() . '/inc/customizer/init.php';

require get_template_directory() . '/inc/layout-logic.php';

require get_template_directory() . '/inc/customizer/font-loader.php';


function mainter_custom_header_setup() {
    add_theme_support(
        'custom-header',
        array(
            'default-image' => '',
            'width'         => 1000,
            'height'        => 250,
            'flex-height'   => true,
            // ¡OJO! Aquí NO ponemos 'wp-head-callback'. 
            // Así evitamos que este archivo escriba CSS y nos arruine los colores.
        )
    );
}
add_action( 'after_setup_theme', 'mainter_custom_header_setup' );

/**
 * Añadir clase Sticky Sidebar (Nombre corregido para evitar conflictos)
 */
function mainter_add_sticky_class( $classes ) {
    // Verificamos si la opción del personalizador está activa
    if ( get_theme_mod( 'enable_sticky_sidebar', false ) ) {
        $classes[] = 'is-sidebar-sticky';
    }
    return $classes;
}
add_filter( 'body_class', 'mainter_add_sticky_class' );

/**
 * Widgets Personalizados
 */
require get_template_directory() . '/inc/widgets/init.php';

require get_template_directory() . '/inc/features/search-logic.php';

require get_template_directory() . '/inc/features/ads-manager.php';

require get_template_directory() . '/inc/features/toc-manager.php';



/**
 * Paginación Numérica Personalizada
 */

function mainter_pagination() {
    the_posts_pagination( array(
        'mid_size'  => 2,
        // 👇 AGREGAMOS TEXTO OCULTO PARA ACCESIBILIDAD
        'prev_text' => '<span class="screen-reader-text">Anterior</span><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
        'next_text' => '<span class="screen-reader-text">Siguiente</span><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>',
        'screen_reader_text' => 'Navegación de entradas',
        'class'              => 'mainter-pagination',
        'aria_label'         => 'Entradas', // Etiqueta general
    ) );
}

/**
 * Carga de Estilos (CSS) del Tema Mainter
 */
function mainter_frontend_styles() {
    
    // Definimos la versión (puedes usar la versión del tema o una fecha '20240114')
   $version = _S_VERSION;

    // 1. ARCHIVO GLOBAL (Se carga en toda la web)
    wp_enqueue_style( 
        'mainter-main-css', 
        get_template_directory_uri() . '/assets/css/main.css', 
        array(), 
        $version 
    );

    // 2. ARCHIVO CONDICIONAL (Solo Entradas y Páginas)
    // is_single() = Entradas de blog individuales
    // is_page()   = Páginas estáticas (Contacto, Quiénes somos, etc.)
    if ( is_single() || is_page() ) {
        
        wp_enqueue_style( 
            'mainter-single-css', 
            get_template_directory_uri() . '/assets/css/single.css', 
            array( 'mainter-main-css' ), // Dependencia: Se carga DESPUÉS del main
            $version 
        );
        
    }
}
// Usamos el hook para el FRONTEND (la web que ve el usuario)
add_action( 'wp_enqueue_scripts', 'mainter_frontend_styles' );


/**
 * 🚀 WIDGETS SIEMPRE VISIBLES EN EL PANEL
 * Obliga a mostrar las secciones de Sidebar y Footer en el Personalizador
 * aunque estés en una página donde no se ven.
 */
function mainter_force_widget_sections_visible( $active, $section ) {
    
    // Lista de IDs de tus secciones de widgets.
    // WordPress crea los IDs así: 'sidebar-widgets-' + 'id-de-tu-sidebar'
    $forced_sections = array(
        'sidebar-widgets-sidebar-1', // Sidebar Principal
        'sidebar-widgets-footer-1',  // Footer Col 1
        'sidebar-widgets-footer-2',  // Footer Col 2
        'sidebar-widgets-footer-3',  // Footer Col 3
        'sidebar-widgets-footer-4',  // Footer Col 4
    );

    // Si la sección actual está en nuestra lista, devolvemos TRUE (Visible)
    if ( in_array( $section->id, $forced_sections ) ) {
        return true; 
    }

    return $active;
}
// Usamos prioridad 99 para asegurarnos de que ganamos a WordPress
add_filter( 'customize_section_active', 'mainter_force_widget_sections_visible', 99, 2 );