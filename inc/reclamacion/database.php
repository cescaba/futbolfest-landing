<?php
/**
 * Complaint database layer for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the full database table name for complaints.
 *
 * @return string
 */
function futbolfest_reclamacion_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'futbolfest_reclamaciones';
}

/**
 * Returns the SQL schema for the complaints table.
 *
 * @return string
 */
function futbolfest_reclamacion_table_schema() {
	global $wpdb;

	$table_name      = futbolfest_reclamacion_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	return "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		nombre_completo varchar(120) NOT NULL,
		apellido_completo varchar(120) NOT NULL,
		tipo_documento varchar(30) NOT NULL,
		documento varchar(30) NOT NULL,
		email varchar(190) NOT NULL,
		celular varchar(30) NOT NULL,
		domicilio varchar(255) NOT NULL,
		tipo_solicitud varchar(30) NOT NULL,
		fecha_hecha date NOT NULL,
		servicios_relacionado varchar(190) NOT NULL,
		descripcion text NOT NULL,
		email_sent tinyint(1) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY tipo_solicitud (tipo_solicitud),
		KEY documento (documento),
		KEY email (email),
		KEY created_at (created_at)
	) {$charset_collate};";
}

/**
 * Creates or updates the complaints table.
 *
 * @return void
 */
function futbolfest_reclamacion_create_table() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( futbolfest_reclamacion_table_schema() );
	futbolfest_reclamacion_ensure_columns();
	update_option( 'futbolfest_reclamacion_schema_version', FUTBOLFEST_RECLAMACION_SCHEMA_VERSION );
}

/**
 * Ensures columns added after the first table creation exist.
 *
 * @return void
 */
function futbolfest_reclamacion_ensure_columns() {
	global $wpdb;

	$table_name = futbolfest_reclamacion_table_name();
	$columns    = $wpdb->get_col( "DESC {$table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! is_array( $columns ) ) {
		return;
	}

	$column_definitions = array(
		'nombre_completo'       => "ALTER TABLE {$table_name} ADD COLUMN nombre_completo varchar(120) NOT NULL DEFAULT '' AFTER id",
		'apellido_completo'     => "ALTER TABLE {$table_name} ADD COLUMN apellido_completo varchar(120) NOT NULL DEFAULT '' AFTER nombre_completo",
		'fecha_hecha'           => "ALTER TABLE {$table_name} ADD COLUMN fecha_hecha date NULL DEFAULT NULL AFTER tipo_solicitud",
		'servicios_relacionado' => "ALTER TABLE {$table_name} ADD COLUMN servicios_relacionado varchar(190) NOT NULL DEFAULT '' AFTER fecha_hecha",
	);

	foreach ( $column_definitions as $column => $query ) {
		if ( ! in_array( $column, $columns, true ) ) {
			$wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}

	if ( in_array( 'nombre', $columns, true ) ) {
		$wpdb->query( "UPDATE {$table_name} SET nombre_completo = nombre WHERE nombre_completo = ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN nombre" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( in_array( 'apellido', $columns, true ) ) {
		$wpdb->query( "UPDATE {$table_name} SET apellido_completo = apellido WHERE apellido_completo = ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN apellido" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( in_array( 'fecha_hecho', $columns, true ) ) {
		$wpdb->query( "UPDATE {$table_name} SET fecha_hecha = fecha_hecho WHERE fecha_hecha IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN fecha_hecho" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( in_array( 'servicio', $columns, true ) ) {
		$wpdb->query( "UPDATE {$table_name} SET servicios_relacionado = servicio WHERE servicios_relacionado = ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN servicio" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}

/**
 * Runs lightweight schema migrations only when needed.
 *
 * @return void
 */
function futbolfest_reclamacion_maybe_create_table() {
	if ( get_option( 'futbolfest_reclamacion_schema_version' ) === FUTBOLFEST_RECLAMACION_SCHEMA_VERSION ) {
		futbolfest_reclamacion_ensure_columns();
		return;
	}

	futbolfest_reclamacion_create_table();
}
add_action( 'after_switch_theme', 'futbolfest_reclamacion_create_table' );
add_action( 'admin_init', 'futbolfest_reclamacion_maybe_create_table' );

/**
 * Saves a complaint submission in the database.
 *
 * @param array<string,string> $data Complaint data.
 * @return int Saved complaint ID.
 */
function futbolfest_reclamacion_save_submission( $data ) {
	global $wpdb;

	futbolfest_reclamacion_maybe_create_table();

	$table_name = futbolfest_reclamacion_table_name();
	$columns    = $wpdb->get_col( "DESC {$table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! is_array( $columns ) ) {
		return 0;
	}

	$insert_data = array(
		'nombre_completo'       => $data['nombre'],
		'apellido_completo'     => $data['apellido'],
		'tipo_documento'        => $data['tipo_documento'],
		'documento'             => $data['documento'],
		'email'                 => $data['email'],
		'celular'               => $data['celular'],
		'domicilio'             => $data['domicilio'],
		'tipo_solicitud'        => $data['tipo_solicitud'],
		'fecha_hecha'           => $data['fecha'],
		'servicios_relacionado' => $data['servicio'],
		'descripcion'           => $data['descripcion'],
		'email_sent'            => 0,
	);

	$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );

	$inserted = $wpdb->insert( $table_name, $insert_data, $formats );

	return false === $inserted ? 0 : (int) $wpdb->insert_id;
}

/**
 * Marks a complaint as emailed.
 *
 * @param int $reclamacion_id Complaint ID.
 * @return void
 */
function futbolfest_reclamacion_mark_email_sent( $reclamacion_id ) {
	if ( $reclamacion_id <= 0 ) {
		return;
	}

	global $wpdb;

	$wpdb->update(
		futbolfest_reclamacion_table_name(),
		array( 'email_sent' => 1 ),
		array( 'id' => $reclamacion_id ),
		array( '%d' ),
		array( '%d' )
	);
}
