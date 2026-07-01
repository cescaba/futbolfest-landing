<?php
/**
 * Registration CSV export for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloads all registrations as CSV.
 *
 * @return void
 */
function futbolfest_registro_export_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para descargar registros.', 'futbolfest-landing' ) );
	}

	check_admin_referer( 'futbolfest_registro_export' );
	futbolfest_registro_maybe_create_table();

	global $wpdb;

	$table_name = futbolfest_registro_table_name();
	$origen     = isset( $_GET['origen'] ) ? sanitize_key( wp_unslash( $_GET['origen'] ) ) : '';
	$origen     = in_array( $origen, array( 'home', 'qr' ), true ) ? $origen : '';
	$query      = "SELECT id, nombre, apellido, dni, telefono, email, ninos, origen, created_at FROM {$table_name}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$args       = array();

	if ( $origen ) {
		$query .= ' WHERE origen = %s';
		$args[] = $origen;
	}

	$query .= ' ORDER BY created_at DESC, id DESC';
	$rows   = $origen
		? $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		: $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$ninos_map = futbolfest_registro_get_ninos_map( wp_list_pluck( $rows, 'id' ) );
	$filename  = 'futbolfest-registros' . ( $origen ? '-' . $origen : '' ) . '-' . gmdate( 'Y-m-d-His' ) . '.csv';

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );

	fputcsv( $output, array( 'ID', 'Nombre', 'Apellido', 'DNI', 'Telefono', 'Email', 'Ninos', 'Detalle ninos', 'Origen', 'Fecha' ) );

	foreach ( $rows as $row ) {
		fputcsv(
			$output,
			array(
				$row['id'],
				$row['nombre'],
				$row['apellido'],
				$row['dni'],
				$row['telefono'],
				$row['email'],
				futbolfest_registro_ninos_label( $row['ninos'] ),
				futbolfest_registro_format_ninos_data( isset( $ninos_map[ $row['id'] ] ) ? $ninos_map[ $row['id'] ] : array() ),
				futbolfest_registro_origen_label( $row['origen'] ),
				$row['created_at'],
			)
		);
	}

	fclose( $output );
	exit;
}
add_action( 'admin_post_futbolfest_registro_export', 'futbolfest_registro_export_csv' );
