<?php
/**
 * Registro form infrastructure.
 *
 * This file owns the database/form layer for Futbol Fest registrations.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema version for future registration table migrations.
 */
const FUTBOLFEST_REGISTRO_SCHEMA_VERSION = '1.0.0';

/**
 * Returns the full database table name for registrations.
 *
 * @return string
 */
function futbolfest_registro_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'futbolfest_registros';
}

/**
 * Returns the SQL schema for the registrations table.
 *
 * @return string
 */
function futbolfest_registro_table_schema() {
	global $wpdb;

	$table_name      = futbolfest_registro_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	return "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		nombre varchar(120) NOT NULL,
		apellido varchar(120) NOT NULL,
		dni varchar(20) NOT NULL,
		telefono varchar(30) NOT NULL,
		email varchar(190) NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY email (email),
		KEY dni (dni),
		KEY created_at (created_at)
	) {$charset_collate};";
}

/**
 * Creates or updates the registrations table.
 *
 * @return void
 */
function futbolfest_registro_create_table() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( futbolfest_registro_table_schema() );
	update_option( 'futbolfest_registro_schema_version', FUTBOLFEST_REGISTRO_SCHEMA_VERSION );
}

/**
 * Runs lightweight schema migrations only when needed.
 *
 * @return void
 */
function futbolfest_registro_maybe_create_table() {
	if ( get_option( 'futbolfest_registro_schema_version' ) === FUTBOLFEST_REGISTRO_SCHEMA_VERSION ) {
		return;
	}

	futbolfest_registro_create_table();
}
add_action( 'after_switch_theme', 'futbolfest_registro_create_table' );
add_action( 'admin_init', 'futbolfest_registro_maybe_create_table' );

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
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'futbolfest_registro_submit' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'futbolfest_registro_enqueue_scripts' );

/**
 * Reads and sanitizes a text field from POST.
 *
 * @param string $key Field key.
 * @return string
 */
function futbolfest_registro_post_text( $key ) {
	return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
}

/**
 * Handles AJAX registration submissions.
 *
 * @return void
 */
function futbolfest_registro_handle_submission() {
	check_ajax_referer( 'futbolfest_registro_submit', 'nonce' );

	$honeypot = futbolfest_registro_post_text( 'sitio_web' );

	if ( '' !== $honeypot ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos procesar tu registro.' ),
			400
		);
	}

	$nombre   = futbolfest_registro_post_text( 'nombre' );
	$apellido = futbolfest_registro_post_text( 'apellido' );
	$dni      = futbolfest_registro_post_text( 'dni' );
	$telefono = futbolfest_registro_post_text( 'telefono' );
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( '' === $nombre || '' === $apellido || '' === $dni || '' === $telefono || '' === $email ) {
		wp_send_json_error(
			array( 'message' => 'Completa todos los campos obligatorios.' ),
			400
		);
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error(
			array( 'message' => 'Ingresa un correo electrónico válido.' ),
			400
		);
	}

	futbolfest_registro_maybe_create_table();

	global $wpdb;

	$inserted = $wpdb->insert(
		futbolfest_registro_table_name(),
		array(
			'nombre'   => $nombre,
			'apellido' => $apellido,
			'dni'      => $dni,
			'telefono' => $telefono,
			'email'    => $email,
		),
		array( '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $inserted ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos guardar tu registro. Inténtalo nuevamente.' ),
			500
		);
	}

	wp_send_json_success(
		array( 'message' => 'Registro guardado correctamente.' )
	);
}
add_action( 'wp_ajax_futbolfest_registro_submit', 'futbolfest_registro_handle_submission' );
add_action( 'wp_ajax_nopriv_futbolfest_registro_submit', 'futbolfest_registro_handle_submission' );
