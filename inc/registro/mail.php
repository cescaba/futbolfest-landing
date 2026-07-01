<?php
/**
 * Registration email template for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends the registration confirmation email to the attendee.
 *
 * @param string $email Recipient email.
 * @param string $nombre Recipient first name.
 * @param string $apellido Recipient last name.
 * @return bool
 */
function futbolfest_registro_send_confirmation_email( $email, $nombre, $apellido ) {
	$recipient = sanitize_email( $email );

	if ( ! is_email( $recipient ) ) {
		return false;
	}

	$first_name    = trim( $nombre );
	$full_name     = trim( $nombre . ' ' . $apellido );
	$greeting      = $first_name ? $first_name : 'crack';
	$site_name     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject       = 'Confirmacion de registro - Futbol Fest 2026';
	$event_url     = FUTBOLFEST_REGISTRO_PUBLIC_URL;
	$hero_url      = get_theme_file_uri( 'assets/images/SuccessModal.png' );
	$separator_url = get_theme_file_uri( 'assets/images/separador-hero.png' );
	$headers       = array( 'Content-Type: text/html; charset=UTF-8' );

	$message = sprintf(
		'<!doctype html>
		<html lang="es">
		<body style="margin:0;padding:0;background:#f5f7fc;font-family:Barlow,Arial,Helvetica,sans-serif;color:#0d1b4b;">
			<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="background:#f5f7fc;padding:24px 12px;">
				<tr>
					<td align="center">
						<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="max-width:512px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid rgba(26,63,160,0.08);box-shadow:0 32px 64px rgba(13,27,75,0.16);text-align:center;">
							<tr>
								<td style="padding:0;background:#152ca7;">
									<img src="%3$s" width="512" alt="Futbol Fest 2026" style="width:100%%;max-width:512px;height:auto;display:block;border:0;">
								</td>
							</tr>
							<tr>
								<td align="center" style="padding:24px 24px 0;background:#ffffff;">
									<img src="%4$s" width="60" height="18" alt="" style="width:60px;height:18px;display:block;border:0;margin:0 auto 12px;">
									<p style="margin:0 0 10px;color:#0d1b4b;font-family:\'Barlow Condensed\',Arial,Helvetica,sans-serif;font-size:26px;line-height:26px;font-weight:900;text-transform:uppercase;">Gracias, %2$s</p>
									<p style="margin:0 0 10px;color:#152ca7;font-family:\'Barlow Condensed\',Arial,Helvetica,sans-serif;font-size:16px;line-height:23px;font-weight:700;">Ya eres parte del Futbol Fest 2026.</p>
									<p style="max-width:300px;margin:0 auto 20px;color:#5a6a9a;font-size:14px;line-height:21px;">Pronto te enviaremos mas informacion, promociones y sorpresas para que llegues listo a jugar los desafios.</p>
								</td>
							</tr>
							<tr>
								<td style="padding:0 24px 24px;background:#ffffff;text-align:center;">
									<a href="%5$s" style="width:100%%;box-sizing:border-box;display:block;background:#152ca7;color:#ffffff;text-decoration:none;border-radius:14px;padding:13px;font-family:\'Barlow Condensed\',Arial,Helvetica,sans-serif;font-size:16px;line-height:23px;font-weight:800;">Ver Futbol Fest</a>
									<p style="margin:14px 0 0;color:#9ca3af;font-size:12px;line-height:17px;">Este correo confirma el registro de %6$s en %7$s.</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</body>
		</html>',
		esc_html( $subject ),
		esc_html( $greeting ),
		esc_url( $hero_url ),
		esc_url( $separator_url ),
		esc_url( $event_url ),
		esc_html( $full_name ? $full_name : $recipient ),
		esc_html( $site_name )
	);

	$sent = wp_mail( $recipient, $subject, $message, $headers );

	if ( ! $sent ) {
		error_log( 'Futbol Fest: no se pudo enviar correo de confirmacion a ' . $recipient ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	return $sent;
}
