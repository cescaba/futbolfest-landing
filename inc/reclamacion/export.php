<?php
/**
 * Complaint CSV export for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloads complaints as CSV.
 *
 * @return void
 */
function futbolfest_reclamacion_export_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para descargar reclamaciones.', 'futbolfest-landing' ) );
	}

	check_admin_referer( 'futbolfest_reclamacion_export' );
	futbolfest_reclamacion_maybe_create_table();

	global $wpdb;

	$table_name     = futbolfest_reclamacion_table_name();
	$reclamacion_id = isset( $_GET['reclamacion_id'] ) ? absint( $_GET['reclamacion_id'] ) : 0;
	$query          = "SELECT id, nombre_completo, apellido_completo, tipo_documento, documento, email, celular, domicilio, tipo_solicitud, fecha_hecha, servicios_relacionado, descripcion, email_sent, created_at FROM {$table_name}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $reclamacion_id > 0 ) {
		$query .= ' WHERE id = %d';
		$rows   = $wpdb->get_results( $wpdb->prepare( $query, $reclamacion_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $rows ) ) {
			wp_die( esc_html__( 'No existe la reclamacion solicitada.', 'futbolfest-landing' ) );
		}
	} else {
		$query .= ' ORDER BY created_at DESC, id DESC';
		$rows   = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	$filename = $reclamacion_id > 0
		? 'futbolfest-reclamacion-' . $reclamacion_id . '-' . gmdate( 'Y-m-d-His' ) . '.csv'
		: 'futbolfest-reclamaciones-' . gmdate( 'Y-m-d-His' ) . '.csv';

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );

	fputcsv(
		$output,
		array(
			'ID',
			'NombreCompleto',
			'ApellidoCompleto',
			'TipoDocumento',
			'Documento',
			'Email',
			'Celular',
			'Domicilio',
			'TipoSolicitud',
			'FechaHecha',
			'ServiciosRelacionado',
			'Descripcion',
			'CorreoAviso',
			'FechaRegistro',
		)
	);

	foreach ( $rows as $row ) {
		fputcsv(
			$output,
			array(
				$row['id'],
				$row['nombre_completo'],
				$row['apellido_completo'],
				futbolfest_reclamacion_documento_label( $row['tipo_documento'] ),
				$row['documento'],
				$row['email'],
				$row['celular'],
				$row['domicilio'],
				futbolfest_reclamacion_solicitud_label( $row['tipo_solicitud'] ),
				$row['fecha_hecha'],
				$row['servicios_relacionado'],
				$row['descripcion'],
				futbolfest_reclamacion_email_sent_label( $row['email_sent'] ),
				$row['created_at'],
			)
		);
	}

	fclose( $output );
	exit;
}
add_action( 'admin_post_futbolfest_reclamacion_export', 'futbolfest_reclamacion_export_csv' );
