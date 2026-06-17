<?php
/**
 * Front-end routes for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FUTBOLFEST_ROUTES_VERSION = '1.0.0';

/**
 * Registers clean front-end routes owned by the theme.
 *
 * @return void
 */
function futbolfest_register_routes() {
	add_rewrite_rule(
		'^registro-qr/?$',
		'index.php?futbolfest_route=registro_qr',
		'top'
	);
}
add_action( 'init', 'futbolfest_register_routes' );

/**
 * Allows custom route query vars.
 *
 * @param array $vars Public query vars.
 * @return array
 */
function futbolfest_register_query_vars( $vars ) {
	$vars[] = 'futbolfest_route';

	return $vars;
}
add_filter( 'query_vars', 'futbolfest_register_query_vars' );

/**
 * Flushes rewrite rules only when route definitions change.
 *
 * @return void
 */
function futbolfest_maybe_flush_routes() {
	if ( get_option( 'futbolfest_routes_version' ) === FUTBOLFEST_ROUTES_VERSION ) {
		return;
	}

	futbolfest_register_routes();
	flush_rewrite_rules( false );
	update_option( 'futbolfest_routes_version', FUTBOLFEST_ROUTES_VERSION );
}
add_action( 'init', 'futbolfest_maybe_flush_routes', 20 );
add_action( 'after_switch_theme', 'futbolfest_maybe_flush_routes' );

/**
 * Renders the standalone QR registration page.
 *
 * @return void
 */
function futbolfest_render_registro_qr_route() {
	if ( 'registro_qr' !== get_query_var( 'futbolfest_route' ) ) {
		return;
	}

	$template_path = get_theme_file_path( 'templates/registro-qr.html' );

	if ( ! file_exists( $template_path ) ) {
		status_header( 404 );
		return;
	}

	status_header( 200 );
	nocache_headers();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php wp_head(); ?>
	</head>
	<body <?php body_class( 'futbolfest-registro-qr-route' ); ?>>
		<?php wp_body_open(); ?>
		<?php echo do_blocks( file_get_contents( $template_path ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'futbolfest_render_registro_qr_route' );
