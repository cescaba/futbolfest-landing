<?php
/**
 * Registration database layer for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	 exit;
}

/* ============================================
   DATABASE TABLE NAMES
   ============================================ */

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
 * Returns the full database table name for children attached to registrations.
 *
 * @return string
 */
function futbolfest_registro_ninos_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'futbolfest_registro_ninos';
}

/**
 * Returns the full database table name for queued confirmation emails.
 *
 * @return string
 */
function futbolfest_registro_email_queue_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'futbolfest_registro_email_queue';
}

/* ============================================
   DATABASE SCHEMAS
   ============================================ */

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
		ninos tinyint(1) unsigned NOT NULL DEFAULT 0,
		origen varchar(20) NOT NULL DEFAULT 'home',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY unique_email (email),
		UNIQUE KEY unique_dni (dni),
		UNIQUE KEY unique_telefono (telefono),
		KEY origen (origen),
		KEY created_at (created_at)
	) {$charset_collate};";
}

/**
 * Returns the SQL schema for children attached to registrations.
 *
 * @return string
 */
function futbolfest_registro_ninos_table_schema() {
	global $wpdb;

	$table_name      = futbolfest_registro_ninos_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	return "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		registro_id bigint(20) unsigned NOT NULL,
		nombre varchar(120) NOT NULL,
		edad tinyint(2) unsigned NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY registro_id (registro_id),
		KEY edad (edad)
	) {$charset_collate};";
}

/**
 * Returns the SQL schema for queued confirmation emails.
 *
 * @return string
 */
function futbolfest_registro_email_queue_table_schema() {
	global $wpdb;

	$table_name      = futbolfest_registro_email_queue_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	return "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		registro_id bigint(20) unsigned NOT NULL,
		email varchar(190) NOT NULL,
		nombre varchar(120) NOT NULL,
		apellido varchar(120) NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'pending',
		attempts tinyint(2) unsigned NOT NULL DEFAULT 0,
		last_error text NULL,
		available_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		sent_at datetime NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY registro_id (registro_id),
		KEY status_available (status, available_at),
		KEY email (email),
		KEY created_at (created_at)
	) {$charset_collate};";
}

/* ============================================
   DATABASE MIGRATIONS
   ============================================ */

/**
 * Checks whether a table column contains duplicate values.
 *
 * @param string $table_name Database table name.
 * @param string $column Column name.
 * @return bool
 */
function futbolfest_registro_column_has_duplicates( $table_name, $column ) {
	global $wpdb;

	$allowed_columns = array( 'dni', 'email', 'telefono' );

	if ( ! in_array( $column, $allowed_columns, true ) ) {
		return true;
	}

	$duplicate = $wpdb->get_var(
		"SELECT {$column} FROM {$table_name} GROUP BY {$column} HAVING COUNT(*) > 1 LIMIT 1" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	);

	return null !== $duplicate;
}

/**
 * Ensures a unique index exists when current data allows it.
 *
 * @param string $table_name Database table name.
 * @param string $column Column name.
 * @param array<string> $indexes Existing index names.
 * @return void
 */
function futbolfest_registro_ensure_unique_index( $table_name, $column, $indexes ) {
	global $wpdb;

	$index_name = 'unique_' . $column;

	if ( in_array( $index_name, $indexes, true ) ) {
		return;
	}

	if ( futbolfest_registro_column_has_duplicates( $table_name, $column ) ) {
		error_log( 'Futbol Fest: no se pudo agregar indice unico para ' . $column . ' porque existen duplicados.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		return;
	}

	$wpdb->query( "ALTER TABLE {$table_name} ADD UNIQUE KEY {$index_name} ({$column})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

/**
 * Creates or updates the registrations table.
 *
 * @return void
 */
function futbolfest_registro_create_table() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( futbolfest_registro_table_schema() );
	dbDelta( futbolfest_registro_ninos_table_schema() );
	dbDelta( futbolfest_registro_email_queue_table_schema() );
	futbolfest_registro_ensure_columns();
	update_option( 'futbolfest_registro_schema_version', FUTBOLFEST_REGISTRO_SCHEMA_VERSION );
}

/**
 * Ensures columns added after the first table creation exist.
 *
 * @return void
 */
function futbolfest_registro_ensure_columns() {
	global $wpdb;

	$table_name  = futbolfest_registro_table_name();
	$ninos_table = futbolfest_registro_ninos_table_name();
	$email_queue = futbolfest_registro_email_queue_table_name();
	$columns     = $wpdb->get_col( "DESC {$table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! is_array( $columns ) ) {
		return;
	}

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ninos_table ) ) !== $ninos_table ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( futbolfest_registro_ninos_table_schema() );
	}

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $email_queue ) ) !== $email_queue ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( futbolfest_registro_email_queue_table_schema() );
	}

	$ninos_columns = $wpdb->get_col( "DESC {$ninos_table}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( is_array( $ninos_columns ) && in_array( 'apellido', $ninos_columns, true ) ) {
		$wpdb->query( "ALTER TABLE {$ninos_table} DROP COLUMN apellido" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( is_array( $ninos_columns ) && in_array( 'sexo', $ninos_columns, true ) ) {
		$wpdb->query( "ALTER TABLE {$ninos_table} DROP COLUMN sexo" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( ! in_array( 'ninos', $columns, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN ninos tinyint(1) unsigned NOT NULL DEFAULT 0 AFTER email" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( in_array( 'acompanantes', $columns, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_name} DROP COLUMN acompanantes" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	$indexes = $wpdb->get_col( "SHOW INDEX FROM {$table_name}", 2 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( is_array( $indexes ) && ! in_array( 'telefono', $indexes, true ) ) {
		$wpdb->query( "ALTER TABLE {$table_name} ADD INDEX telefono (telefono)" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	if ( is_array( $indexes ) ) {
		futbolfest_registro_ensure_unique_index( $table_name, 'dni', $indexes );
		futbolfest_registro_ensure_unique_index( $table_name, 'email', $indexes );
		futbolfest_registro_ensure_unique_index( $table_name, 'telefono', $indexes );
	}
}

/**
 * Runs lightweight schema migrations only when needed.
 *
 * @return void
 */
function futbolfest_registro_maybe_create_table() {
	if ( get_option( 'futbolfest_registro_schema_version' ) === FUTBOLFEST_REGISTRO_SCHEMA_VERSION ) {
		futbolfest_registro_ensure_columns();
		return;
	}

	futbolfest_registro_create_table();
}
add_action( 'after_switch_theme', 'futbolfest_registro_create_table' );
add_action( 'admin_init', 'futbolfest_registro_maybe_create_table' );

/**
 * Returns children grouped by registration ID.
 *
 * @param array<int> $registro_ids Registration IDs.
 * @return array<int, array<int, array{nombre:string, edad:int}>>
 */
function futbolfest_registro_get_ninos_map( $registro_ids ) {
	global $wpdb;

	$registro_ids = array_values( array_unique( array_filter( array_map( 'absint', $registro_ids ) ) ) );

	if ( empty( $registro_ids ) ) {
		return array();
	}

	$table_name   = futbolfest_registro_ninos_table_name();
	$placeholders = implode( ',', array_fill( 0, count( $registro_ids ), '%d' ) );
	$query        = "SELECT registro_id, nombre, edad FROM {$table_name} WHERE registro_id IN ({$placeholders}) ORDER BY id ASC"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows         = $wpdb->get_results( $wpdb->prepare( $query, $registro_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$map          = array();

	foreach ( $rows as $row ) {
		$registro_id = absint( $row['registro_id'] );

		if ( ! isset( $map[ $registro_id ] ) ) {
			$map[ $registro_id ] = array();
		}

		$map[ $registro_id ][] = array(
			'nombre' => $row['nombre'],
			'edad'   => absint( $row['edad'] ),
		);
	}

	return $map;
}

