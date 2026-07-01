<?php
/**
 * Complaint submission flow for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects sanitized complaint data from POST.
 *
 * @return array<string,string>
 */
function futbolfest_reclamacion_collect_submission_data() {
	return array(
		'nombre'         => futbolfest_reclamacion_post_text( 'reclamacion_nombre' ),
		'apellido'       => futbolfest_reclamacion_post_text( 'reclamacion_apellido' ),
		'tipo_documento' => futbolfest_reclamacion_post_text( 'reclamacion_tipo_documento' ),
		'documento'      => futbolfest_reclamacion_post_text( 'reclamacion_documento' ),
		'email'          => sanitize_email( futbolfest_reclamacion_post_text( 'reclamacion_email' ) ),
		'celular'        => futbolfest_reclamacion_post_text( 'reclamacion_celular' ),
		'domicilio'      => futbolfest_reclamacion_post_text( 'reclamacion_domicilio' ),
		'tipo_solicitud' => futbolfest_reclamacion_post_text( 'reclamacion_tipo_solicitud' ),
		'fecha'          => futbolfest_reclamacion_post_text( 'reclamacion_fecha' ),
		'servicio'       => futbolfest_reclamacion_post_text( 'reclamacion_servicio' ),
		'descripcion'    => futbolfest_reclamacion_post_textarea( 'reclamacion_descripcion' ),
	);
}

/**
 * Validates required complaint fields.
 *
 * @param array<string,string> $data Complaint data.
 * @return void
 */
function futbolfest_reclamacion_validate_submission( $data ) {
	foreach ( $data as $value ) {
		if ( '' === $value ) {
			wp_send_json_error(
				array( 'message' => 'Completa todos los campos obligatorios.' ),
				400
			);
		}
	}

	if ( ! is_email( $data['email'] ) ) {
		wp_send_json_error(
			array( 'message' => 'Ingresa un correo electronico valido.' ),
			400
		);
	}
}

/**
 * Handles complaint form submissions.
 *
 * @return void
 */
function futbolfest_reclamacion_handle_submission() {
	check_ajax_referer( 'futbolfest_reclamacion_submit', 'nonce' );
	futbolfest_reclamacion_guard_rate_limit();
	futbolfest_reclamacion_guard_submit_time();

	if ( '' !== futbolfest_reclamacion_post_text( 'sitio_web' ) ) {
		futbolfest_reclamacion_lock_client();
		wp_send_json_error(
			array( 'message' => 'No pudimos procesar tu reclamacion.' ),
			400
		);
	}

	$data = futbolfest_reclamacion_collect_submission_data();
	futbolfest_reclamacion_validate_submission( $data );

	$reclamacion_id = futbolfest_reclamacion_save_submission( $data );

	if ( $reclamacion_id <= 0 ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos guardar tu reclamacion. Intentalo nuevamente.' ),
			500
		);
	}

	if ( ! futbolfest_reclamacion_send_email( $data ) ) {
		wp_send_json_error(
			array( 'message' => 'Guardamos tu reclamacion, pero no pudimos enviar el correo de aviso.' ),
			500
		);
	}

	futbolfest_reclamacion_mark_email_sent( $reclamacion_id );

	wp_send_json_success(
		array( 'message' => 'Tu reclamacion fue enviada correctamente.' )
	);
}
add_action( 'wp_ajax_futbolfest_reclamacion_submit', 'futbolfest_reclamacion_handle_submission' );
add_action( 'wp_ajax_nopriv_futbolfest_reclamacion_submit', 'futbolfest_reclamacion_handle_submission' );
