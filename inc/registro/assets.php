<?php
/**
 * Registration frontend assets for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the registration AJAX script.
 *
 * @return void
 */
function futbolfest_registro_enqueue_scripts() {
	wp_enqueue_script(
		'futbolfest-registro',
		get_theme_file_uri( 'assets/js/registro.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_localize_script(
		'futbolfest-registro',
		'FutbolFestRegistro',
		array(
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'futbolfest_registro_submit' ),
			'reclamacionNonce' => wp_create_nonce( 'futbolfest_reclamacion_submit' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'futbolfest_registro_enqueue_scripts' );
