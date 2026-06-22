<?php
/**
 * Front-end routes for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FUTBOLFEST_ROUTES_VERSION = '1.0.3';

/**
 * Registers clean front-end routes owned by the theme.
 *
 * @return void
 */
function futbolfest_register_routes() {
	add_rewrite_rule(
		'^formulario-registro/?$',
		'index.php?futbolfest_route=registro_qr',
		'top'
	);

	add_rewrite_rule(
		'^reclamacion/?$',
		'index.php?futbolfest_route=reclamacion',
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
 * Renders standalone theme routes.
 *
 * @return void
 */
function futbolfest_render_theme_route() {
	$route = get_query_var( 'futbolfest_route' );

	if ( ! in_array( $route, array( 'registro_qr', 'reclamacion' ), true ) ) {
		return;
	}

	$template_file = 'reclamacion' === $route ? 'templates/reclamacion.html' : 'templates/registro-qr.html';
	$template_path = get_theme_file_path( $template_file );

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
	<body <?php body_class( 'reclamacion' === $route ? 'futbolfest-reclamacion-route' : 'futbolfest-registro-qr-route' ); ?>>
		<?php wp_body_open(); ?>
		<?php echo do_blocks( file_get_contents( $template_path ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php wp_footer(); ?>
	</body>
	</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'futbolfest_render_theme_route' );

/**
 * Sets a clean document title for standalone theme routes.
 *
 * @param string $title Current document title.
 * @return string
 */
function futbolfest_route_document_title( $title ) {
	if ( 'reclamacion' === get_query_var( 'futbolfest_route' ) ) {
		return 'Libro de reclamaciones - Futbol Fest X';
	}

	return $title;
}
add_filter( 'pre_get_document_title', 'futbolfest_route_document_title' );
