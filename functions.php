<?php
/**
 * Futbol Fest Landing Theme Functions
 */

require_once get_theme_file_path( 'inc/registro.php' );
require_once get_theme_file_path( 'inc/routes.php' );

// Cargar el CSS del tema
function futbolfest_landing_enqueue_styles() {
	wp_enqueue_style(
		'futbolfest-landing-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	// Cargar CSS personalizado
	wp_enqueue_style(
		'futbolfest-landing-futbolfest',
		get_theme_file_uri( 'assets/css/futbolfest.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'futbolfest_landing_enqueue_styles' );

// Habilitar características del tema
function futbolfest_landing_setup() {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'futbolfest_landing_setup' );

// Registrar menús
function futbolfest_landing_register_menus() {
	register_nav_menus( array(
		'primary' => esc_html__( 'Primary Menu', 'futbolfest-landing' ),
		'footer'  => esc_html__( 'Footer Menu', 'futbolfest-landing' ),
	) );
}
add_action( 'init', 'futbolfest_landing_register_menus' );
