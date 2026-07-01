<?php
/**
 * Complaint helpers for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and sanitizes a complaint text field from POST.
 *
 * @param string $key Field key.
 * @return string
 */
function futbolfest_reclamacion_post_text( $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}

	return sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
}

/**
 * Reads and sanitizes a complaint textarea field from POST.
 *
 * @param string $key Field key.
 * @return string
 */
function futbolfest_reclamacion_post_textarea( $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}

	return sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
}

/**
 * Returns a readable label for known select values.
 *
 * @param string $value Raw value.
 * @param array  $labels Allowed labels.
 * @return string
 */
function futbolfest_reclamacion_label( $value, $labels ) {
	return isset( $labels[ $value ] ) ? $labels[ $value ] : $value;
}

/**
 * Returns a readable label for document types.
 *
 * @param string $tipo_documento Document type.
 * @return string
 */
function futbolfest_reclamacion_documento_label( $tipo_documento ) {
	$labels = array(
		'dni'       => 'DNI',
		'ce'        => 'Carnet de extranjería',
		'pasaporte' => 'Pasaporte',
	);

	return futbolfest_reclamacion_label( $tipo_documento, $labels );
}

/**
 * Returns a readable label for request types.
 *
 * @param string $tipo_solicitud Request type.
 * @return string
 */
function futbolfest_reclamacion_solicitud_label( $tipo_solicitud ) {
	$labels = array(
		'reclamo' => 'Reclamo',
		'queja'   => 'Queja',
	);

	return futbolfest_reclamacion_label( $tipo_solicitud, $labels );
}

/**
 * Returns a readable label for email delivery status.
 *
 * @param int $email_sent Whether the notification email was sent.
 * @return string
 */
function futbolfest_reclamacion_email_sent_label( $email_sent ) {
	return absint( $email_sent ) ? 'Enviado' : 'Pendiente';
}
