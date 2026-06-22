<?php
/**
 * Complaint form handling for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FUTBOLFEST_RECLAMACION_RECIPIENT = 'alfredhuarcaya30@gmail.com';

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
 * Handles complaint form submissions.
 *
 * @return void
 */
function futbolfest_reclamacion_handle_submission() {
	check_ajax_referer( 'futbolfest_reclamacion_submit', 'nonce' );

	$nombre          = futbolfest_reclamacion_post_text( 'reclamacion_nombre' );
	$apellido        = futbolfest_reclamacion_post_text( 'reclamacion_apellido' );
	$tipo_documento  = futbolfest_reclamacion_post_text( 'reclamacion_tipo_documento' );
	$documento       = futbolfest_reclamacion_post_text( 'reclamacion_documento' );
	$email           = sanitize_email( futbolfest_reclamacion_post_text( 'reclamacion_email' ) );
	$celular         = futbolfest_reclamacion_post_text( 'reclamacion_celular' );
	$domicilio       = futbolfest_reclamacion_post_text( 'reclamacion_domicilio' );
	$tipo_solicitud  = futbolfest_reclamacion_post_text( 'reclamacion_tipo_solicitud' );
	$fecha           = futbolfest_reclamacion_post_text( 'reclamacion_fecha' );
	$servicio        = futbolfest_reclamacion_post_text( 'reclamacion_servicio' );
	$descripcion     = futbolfest_reclamacion_post_textarea( 'reclamacion_descripcion' );

	if (
		'' === $nombre ||
		'' === $apellido ||
		'' === $tipo_documento ||
		'' === $documento ||
		'' === $email ||
		'' === $celular ||
		'' === $domicilio ||
		'' === $tipo_solicitud ||
		'' === $fecha ||
		'' === $servicio ||
		'' === $descripcion
	) {
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

	$document_labels = array(
		'dni'       => 'DNI',
		'ce'        => 'Carnet de extranjería',
		'pasaporte' => 'Pasaporte',
	);

	$request_labels = array(
		'reclamo' => 'Reclamo',
		'queja'   => 'Queja',
	);

	$subject = sprintf(
		'Libro de reclamaciones Futbol Fest X - %s %s',
		futbolfest_reclamacion_label( $tipo_solicitud, $request_labels ),
		$documento
	);

	$body = sprintf(
		'<!doctype html>
		<html lang="es">
		<body style="margin:0;padding:0;background:#f5f7fc;font-family:Arial,sans-serif;color:#0d1b4b;">
			<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="background:#f5f7fc;padding:24px 0;">
				<tr>
					<td align="center">
						<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:620px;max-width:94%%;background:#ffffff;border:1px solid #dbe4ff;border-radius:14px;overflow:hidden;">
							<tr>
								<td style="padding:22px 24px;background:#152ca7;color:#ffffff;">
									<h1 style="margin:0;font-size:24px;line-height:30px;">Libro de reclamaciones</h1>
									<p style="margin:6px 0 0;font-size:14px;line-height:20px;">Nueva solicitud recibida desde Futbol Fest X.</p>
								</td>
							</tr>
							<tr>
								<td style="padding:22px 24px;">
									<h2 style="margin:0 0 12px;font-size:18px;line-height:24px;color:#0d1b4b;">Datos del consumidor</h2>
									<p><strong>Nombre:</strong> %1$s %2$s</p>
									<p><strong>Documento:</strong> %3$s - %4$s</p>
									<p><strong>Email:</strong> %5$s</p>
									<p><strong>Celular:</strong> %6$s</p>
									<p><strong>Domicilio:</strong> %7$s</p>

									<h2 style="margin:22px 0 12px;font-size:18px;line-height:24px;color:#0d1b4b;">Detalle de la reclamación</h2>
									<p><strong>Tipo de solicitud:</strong> %8$s</p>
									<p><strong>Fecha del hecho:</strong> %9$s</p>
									<p><strong>Servicio relacionado:</strong> %10$s</p>
									<p><strong>Descripción:</strong></p>
									<div style="white-space:pre-line;background:#f7f9ff;border:1px solid #dbe4ff;border-radius:10px;padding:14px;line-height:20px;">%11$s</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>',
		esc_html( $nombre ),
		esc_html( $apellido ),
		esc_html( futbolfest_reclamacion_label( $tipo_documento, $document_labels ) ),
		esc_html( $documento ),
		esc_html( $email ),
		esc_html( $celular ),
		esc_html( $domicilio ),
		esc_html( futbolfest_reclamacion_label( $tipo_solicitud, $request_labels ) ),
		esc_html( $fecha ),
		esc_html( $servicio ),
		esc_html( $descripcion )
	);

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $nombre . ' ' . $apellido . ' <' . $email . '>',
	);

	$sent = wp_mail( FUTBOLFEST_RECLAMACION_RECIPIENT, $subject, $body, $headers );

	if ( ! $sent ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos enviar tu reclamación. Inténtalo nuevamente.' ),
			500
		);
	}

	wp_send_json_success(
		array( 'message' => 'Tu reclamación fue enviada correctamente.' )
	);
}
add_action( 'wp_ajax_futbolfest_reclamacion_submit', 'futbolfest_reclamacion_handle_submission' );
add_action( 'wp_ajax_nopriv_futbolfest_reclamacion_submit', 'futbolfest_reclamacion_handle_submission' );
