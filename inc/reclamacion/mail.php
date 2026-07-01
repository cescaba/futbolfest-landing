<?php
/**
 * Complaint email delivery for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends the admin notification email for a complaint.
 *
 * @param array<string,string> $data Complaint data.
 * @return bool
 */
function futbolfest_reclamacion_send_email( $data ) {
	$subject = sprintf(
		'Libro de reclamaciones Futbol Fest X - %s %s',
		futbolfest_reclamacion_solicitud_label( $data['tipo_solicitud'] ),
		$data['documento']
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

									<h2 style="margin:22px 0 12px;font-size:18px;line-height:24px;color:#0d1b4b;">Detalle de la reclamacion</h2>
									<p><strong>Tipo de solicitud:</strong> %8$s</p>
									<p><strong>Fecha del hecho:</strong> %9$s</p>
									<p><strong>Servicio relacionado:</strong> %10$s</p>
									<p><strong>Descripcion:</strong></p>
									<div style="white-space:pre-line;background:#f7f9ff;border:1px solid #dbe4ff;border-radius:10px;padding:14px;line-height:20px;">%11$s</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>',
		esc_html( $data['nombre'] ),
		esc_html( $data['apellido'] ),
		esc_html( futbolfest_reclamacion_documento_label( $data['tipo_documento'] ) ),
		esc_html( $data['documento'] ),
		esc_html( $data['email'] ),
		esc_html( $data['celular'] ),
		esc_html( $data['domicilio'] ),
		esc_html( futbolfest_reclamacion_solicitud_label( $data['tipo_solicitud'] ) ),
		esc_html( $data['fecha'] ),
		esc_html( $data['servicio'] ),
		esc_html( $data['descripcion'] )
	);

	$headers = array(
		'Content-Type: text/html; charset=UTF-8',
		'Reply-To: ' . $data['nombre'] . ' ' . $data['apellido'] . ' <' . $data['email'] . '>',
	);

	return wp_mail( FUTBOLFEST_RECLAMACION_RECIPIENT, $subject, $body, $headers );
}
