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
const FUTBOLFEST_REGISTRO_SCHEMA_VERSION = '1.8.0';
const FUTBOLFEST_REGISTRO_MIN_SUBMIT_SECONDS = 2;
const FUTBOLFEST_REGISTRO_RATE_LIMIT_MINUTE = 120;
const FUTBOLFEST_REGISTRO_RATE_LIMIT_HOUR = 3000;
const FUTBOLFEST_REGISTRO_BOT_LOCK_SECONDS = 1800;
const FUTBOLFEST_REGISTRO_MAX_NINOS = 10;
const FUTBOLFEST_REGISTRO_PUBLIC_URL = 'https://futbolfestx.com/';
const FUTBOLFEST_REGISTRO_FROM_EMAIL = 'contacto@futbolfestx.com';
const FUTBOLFEST_REGISTRO_FROM_NAME = 'Fútbol Fest';

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
		KEY email (email),
		KEY dni (dni),
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
 * Creates or updates the registrations table.
 *
 * @return void
 */
function futbolfest_registro_create_table() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( futbolfest_registro_table_schema() );
	dbDelta( futbolfest_registro_ninos_table_schema() );
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
	$columns     = $wpdb->get_col( "DESC {$table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( ! is_array( $columns ) ) {
		return;
	}

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ninos_table ) ) !== $ninos_table ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( futbolfest_registro_ninos_table_schema() );
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
 * Returns a normalized client IP for abuse controls.
 *
 * @return string
 */
function futbolfest_registro_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Builds transient keys without storing raw IP addresses.
 *
 * @param string $suffix Key suffix.
 * @return string
 */
function futbolfest_registro_security_key( $suffix ) {
	return 'ff_reg_' . md5( futbolfest_registro_client_ip() . '|' . $suffix );
}

/**
 * Applies per-IP rate limits before database work.
 *
 * @return void
 */
function futbolfest_registro_guard_rate_limit() {
	if ( get_transient( futbolfest_registro_security_key( 'bot_lock' ) ) ) {
		wp_send_json_error(
			array( 'message' => 'Demasiados intentos. Intenta nuevamente en unos minutos.' ),
			429
		);
	}

	$limits = array(
		'minute' => array(
			'max' => FUTBOLFEST_REGISTRO_RATE_LIMIT_MINUTE,
			'ttl' => MINUTE_IN_SECONDS,
		),
		'hour'   => array(
			'max' => FUTBOLFEST_REGISTRO_RATE_LIMIT_HOUR,
			'ttl' => HOUR_IN_SECONDS,
		),
	);

	foreach ( $limits as $name => $limit ) {
		$key   = futbolfest_registro_security_key( 'rate_' . $name );
		$count = (int) get_transient( $key );

		if ( $count >= $limit['max'] ) {
			wp_send_json_error(
				array( 'message' => 'Demasiados registros desde esta red. Intenta nuevamente más tarde.' ),
				429
			);
		}

		set_transient( $key, $count + 1, $limit['ttl'] );
	}
}

/**
 * Temporarily locks obvious bots.
 *
 * @return void
 */
function futbolfest_registro_lock_client() {
	set_transient(
		futbolfest_registro_security_key( 'bot_lock' ),
		1,
		FUTBOLFEST_REGISTRO_BOT_LOCK_SECONDS
	);
}

/**
 * Rejects submissions sent unrealistically fast.
 *
 * @return void
 */
function futbolfest_registro_guard_submit_time() {
	$rendered_at = isset( $_POST['form_rendered_at'] ) ? absint( $_POST['form_rendered_at'] ) : 0;
	$now         = time();

	if ( ! $rendered_at || $rendered_at > $now || ( $now - $rendered_at ) < FUTBOLFEST_REGISTRO_MIN_SUBMIT_SECONDS ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos validar el formulario. Inténtalo nuevamente.' ),
			400
		);
	}
}

/**
 * Normalizes Peruvian mobile numbers to +51XXXXXXXXX.
 *
 * @param string $telefono Raw phone number.
 * @return string Empty when invalid.
 */
function futbolfest_registro_normalize_peru_phone( $telefono ) {
	$digits = preg_replace( '/\D+/', '', $telefono );

	if ( ! is_string( $digits ) || '' === $digits ) {
		return '';
	}

	if ( 9 === strlen( $digits ) && '9' === $digits[0] ) {
		return '+51' . $digits;
	}

	if ( 11 === strlen( $digits ) && '51' === substr( $digits, 0, 2 ) && '9' === $digits[2] ) {
		return '+' . $digits;
	}

	return '';
}

/**
 * Reads and validates children data from POST.
 *
 * @return array<int, array{nombre:string, edad:int}>
 */
function futbolfest_registro_post_ninos_data() {
	$nombres = isset( $_POST['nino_nombre'] ) && is_array( $_POST['nino_nombre'] ) ? wp_unslash( $_POST['nino_nombre'] ) : array();
	$edades  = isset( $_POST['nino_edad'] ) && is_array( $_POST['nino_edad'] ) ? wp_unslash( $_POST['nino_edad'] ) : array();
	$ninos   = array();
	$total   = min( count( $nombres ), count( $edades ), FUTBOLFEST_REGISTRO_MAX_NINOS );

	for ( $i = 0; $i < $total; $i++ ) {
		$nombre_raw = is_scalar( $nombres[ $i ] ) ? (string) $nombres[ $i ] : '';
		$edad_raw   = is_scalar( $edades[ $i ] ) ? (string) $edades[ $i ] : '';
		$nombre     = sanitize_text_field( $nombre_raw );
		$edad       = is_numeric( $edad_raw ) ? absint( $edad_raw ) : null;

		if ( '' === $nombre && null === $edad ) {
			continue;
		}

		if ( '' === $nombre || null === $edad || $edad > 17 ) {
			wp_send_json_error(
				array( 'message' => 'Completa correctamente los datos de cada niño.' ),
				400
			);
		}

		$ninos[] = array(
			'nombre' => $nombre,
			'edad'   => $edad,
		);
	}

	return $ninos;
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

	$first_name = trim( $nombre );
	$full_name  = trim( $nombre . ' ' . $apellido );
	$greeting   = $first_name ? $first_name : 'crack';
	$site_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject       = 'Confirmación de registro - Fútbol Fest 2026';
	$event_url     = FUTBOLFEST_REGISTRO_PUBLIC_URL;
	$hero_url      = get_theme_file_uri( 'assets/images/SuccessModal.png' );
	$separator_url = get_theme_file_uri( 'assets/images/separador-hero.png' );
	$from_email    = sanitize_email( FUTBOLFEST_REGISTRO_FROM_EMAIL );
	$from_name     = wp_specialchars_decode( FUTBOLFEST_REGISTRO_FROM_NAME, ENT_QUOTES );
	$headers       = array(
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . $from_name . ' <' . $from_email . '>',
		'Reply-To: ' . $from_name . ' <' . $from_email . '>',
	);

	$message = sprintf(
		'<!doctype html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>%1$s</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fc;font-family:Barlow,Arial,Helvetica,sans-serif;color:#0d1b4b;">
	<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="background:#f5f7fc;padding:24px 12px;">
		<tr>
			<td align="center">
				<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="max-width:512px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid rgba(26,63,160,0.08);box-shadow:0 32px 64px rgba(13,27,75,0.16);text-align:center;">
					<tr>
						<td style="padding:0;background:#152ca7;">
							<img src="%3$s" width="512" alt="Fútbol Fest 2026" style="width:100%%;max-width:512px;height:auto;min-height:192px;display:block;border:0;">
						</td>
					</tr>
					<tr>
						<td align="center" style="padding:24px 24px 0;background:#ffffff;">
							<img src="%4$s" width="60" height="18" alt="" style="width:60px;height:18px;display:block;border:0;margin:0 auto 12px;">
							<p style="margin:0 0 10px;color:#0d1b4b;font-family:\'Barlow Condensed\',Arial,Helvetica,sans-serif;font-size:26px;line-height:26px;font-weight:900;letter-spacing:0;text-transform:uppercase;">¡GRACIAS, %2$s!</p>
							<p style="margin:0 0 10px;color:#152ca7;font-family:\'Barlow Condensed\',Arial,Helvetica,sans-serif;font-size:16px;line-height:23px;font-weight:700;letter-spacing:0;">¡Ya eres parte del Fútbol Fest 2026! 🎉</p>
							<p style="max-width:270px;margin:0 auto 20px;color:#5a6a9a;font-size:14px;line-height:21px;font-weight:400;letter-spacing:0;">Pronto te enviaremos más información, <strong style="color:#0d1b4b;font-weight:700;">promociones y sorpresas</strong> para que llegues listo a jugar los desafíos. ⚽🏆</p>
						</td>
					</tr>
					<tr>
						<td style="padding:0 24px 24px;background:#ffffff;">
							<table role="presentation" width="100%%" cellspacing="0" cellpadding="0" style="background:#f5f7fc;border-radius:14px;border:1px solid rgba(21,44,167,0.10);">
								<tr>
									<td align="center" style="padding:12px 16px;">
										<table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto;">
											<tr>
												<td width="30" align="center" valign="middle" style="padding:0;color:#0d1b4b;font-size:18px;line-height:27px;">📍</td>
												<td align="left" valign="middle" style="padding:0 0 0 12px;">
													<p style="margin:0 0 2px;color:#5a6a9a;font-size:12px;line-height:12px;font-weight:400;letter-spacing:0;">Nos vemos en</p>
													<p style="margin:0;color:#0d1b4b;font-size:13px;line-height:20px;font-weight:700;letter-spacing:0;">Jockey Plaza — Puerta 4, Lima</p>
													<p style="margin:0;color:#152ca7;font-size:12px;line-height:17px;font-weight:600;letter-spacing:0;">17 de Julio hasta el 31 de Agosto 2026</p>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:0 24px 24px;background:#ffffff;text-align:center;">
							<a href="%5$s" style="width:100%%;box-sizing:border-box;display:block;background:#152ca7;color:#ffffff;text-decoration:none;border-radius:14px;padding:13px;font-family:\'Barlow Condensed\',Arial,Helvetica,sans-serif;font-size:16px;line-height:23px;font-weight:800;letter-spacing:1px;">¡A JUGAR! ⚽</a>
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
		error_log( 'Futbol Fest: no se pudo enviar correo de confirmación a ' . $recipient ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	return $sent;
}

/**
 * Queues the confirmation email so the AJAX registration response is not delayed.
 *
 * @param string $email Recipient email.
 * @param string $nombre Recipient first name.
 * @param string $apellido Recipient last name.
 * @return void
 */
function futbolfest_registro_queue_confirmation_email( $email, $nombre, $apellido ) {
	$args = array(
		sanitize_email( $email ),
		sanitize_text_field( $nombre ),
		sanitize_text_field( $apellido ),
	);

	if ( ! is_email( $args[0] ) ) {
		return;
	}

	$scheduled = wp_schedule_single_event(
		time() + 1,
		'futbolfest_registro_send_confirmation_email',
		$args
	);

	if ( ! $scheduled ) {
		futbolfest_registro_send_confirmation_email( $args[0], $args[1], $args[2] );
	}
}
add_action( 'futbolfest_registro_send_confirmation_email', 'futbolfest_registro_send_confirmation_email', 10, 3 );

/**
 * Handles AJAX registration submissions.
 *
 * @return void
 */
function futbolfest_registro_handle_submission() {
	check_ajax_referer( 'futbolfest_registro_submit', 'nonce' );
	futbolfest_registro_guard_rate_limit();
	futbolfest_registro_guard_submit_time();

	$honeypot = futbolfest_registro_post_text( 'sitio_web' );

	if ( '' !== $honeypot ) {
		futbolfest_registro_lock_client();
		wp_send_json_error(
			array( 'message' => 'No pudimos procesar tu registro.' ),
			400
		);
	}

	$nombre       = futbolfest_registro_post_text( 'nombre' );
	$apellido     = futbolfest_registro_post_text( 'apellido' );
	$dni          = futbolfest_registro_post_text( 'dni' );
	$telefono     = futbolfest_registro_post_text( 'telefono' );
	$email        = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$ninos_raw    = isset( $_POST['ninos'] ) ? sanitize_text_field( wp_unslash( $_POST['ninos'] ) ) : '';
	$ninos        = '' !== $ninos_raw ? absint( $ninos_raw ) : null;
	$ninos_data   = array();
	$origen       = futbolfest_registro_post_text( 'origen' );
	$origen       = in_array( $origen, array( 'home', 'qr' ), true ) ? $origen : 'home';

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

	if ( ! preg_match( '/^[0-9]{8,12}$/', $dni ) ) {
		wp_send_json_error(
			array( 'message' => 'Ingresa un DNI válido.' ),
			400
		);
	}

	if ( null === $ninos || $ninos > 1 ) {
		wp_send_json_error(
			array( 'message' => 'Selecciona una opción válida para niños.' ),
			400
		);
	}

	if ( 1 === $ninos ) {
		$ninos_data = futbolfest_registro_post_ninos_data();

		if ( empty( $ninos_data ) ) {
			wp_send_json_error(
				array( 'message' => 'Agrega al menos un niño.' ),
				400
			);
		}
	}

	$telefono_normalizado = futbolfest_registro_normalize_peru_phone( $telefono );

	if ( '' === $telefono_normalizado ) {
		wp_send_json_error(
			array( 'message' => 'Ingresa un celular peruano valido.' ),
			400
		);
	}

	$telefono = $telefono_normalizado;

	futbolfest_registro_maybe_create_table();

	global $wpdb;

	$inserted = $wpdb->insert(
		futbolfest_registro_table_name(),
		array(
			'nombre'       => $nombre,
			'apellido'     => $apellido,
			'dni'          => $dni,
			'telefono'     => $telefono,
			'email'        => $email,
			'ninos'        => $ninos,
			'origen'       => $origen,
		),
		array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
	);

	if ( false === $inserted ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos guardar tu registro. Inténtalo nuevamente.' ),
			500
		);
	}

	$registro_id = (int) $wpdb->insert_id;

	foreach ( $ninos_data as $nino ) {
		$child_inserted = $wpdb->insert(
			futbolfest_registro_ninos_table_name(),
			array(
				'registro_id' => $registro_id,
				'nombre'      => $nino['nombre'],
				'edad'        => $nino['edad'],
			),
			array( '%d', '%s', '%d' )
		);

		if ( false === $child_inserted ) {
			$wpdb->delete( futbolfest_registro_ninos_table_name(), array( 'registro_id' => $registro_id ), array( '%d' ) );
			$wpdb->delete( futbolfest_registro_table_name(), array( 'id' => $registro_id ), array( '%d' ) );

			wp_send_json_error(
				array( 'message' => 'No pudimos guardar los datos de los niños. Inténtalo nuevamente.' ),
				500
			);
		}
	}

	futbolfest_registro_queue_confirmation_email( $email, $nombre, $apellido );

	wp_send_json_success(
		array( 'message' => 'Registro guardado correctamente.' )
	);
}
add_action( 'wp_ajax_futbolfest_registro_submit', 'futbolfest_registro_handle_submission' );
add_action( 'wp_ajax_nopriv_futbolfest_registro_submit', 'futbolfest_registro_handle_submission' );

/**
 * Adds the registrations admin page.
 *
 * @return void
 */
function futbolfest_registro_admin_menu() {
	add_menu_page(
		'Registros Futbol Fest',
		'Registros FF',
		'manage_options',
		'futbolfest-registros',
		'futbolfest_registro_render_admin_page',
		'dashicons-list-view',
		30
	);
}
add_action( 'admin_menu', 'futbolfest_registro_admin_menu' );

/**
 * Returns a readable label for a registration source.
 *
 * @param string $origen Registration source.
 * @return string
 */
function futbolfest_registro_origen_label( $origen ) {
	$labels = array(
		'home' => 'Home',
		'qr'   => 'QR',
	);

	return isset( $labels[ $origen ] ) ? $labels[ $origen ] : 'Home';
}

/**
 * Returns a readable label for whether children are coming with a registration.
 *
 * @param int $ninos Whether children are coming.
 * @return string
 */
function futbolfest_registro_ninos_label( $ninos ) {
	$ninos = absint( $ninos );

	if ( 0 === $ninos ) {
		return 'No';
	}

	return 'Sí';
}

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

/**
 * Formats children data for admin tables and exports.
 *
 * @param array<int, array{nombre:string, edad:int}> $ninos_data Children data.
 * @return string
 */
function futbolfest_registro_format_ninos_data( $ninos_data ) {
	if ( empty( $ninos_data ) ) {
		return 'No';
	}

	$items = array();

	foreach ( $ninos_data as $nino ) {
		$items[] = sprintf( '%s (%d años)', $nino['nombre'], absint( $nino['edad'] ) );
	}

	return implode( ', ', $items );
}

/**
 * Renders the registrations admin table.
 *
 * @return void
 */
function futbolfest_registro_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta pagina.', 'futbolfest-landing' ) );
	}

	futbolfest_registro_maybe_create_table();

	global $wpdb;

	$table_name  = futbolfest_registro_table_name();
	$per_page    = 50;
	$current     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$offset      = ( $current - 1 ) * $per_page;
	$origen      = isset( $_GET['origen'] ) ? sanitize_key( wp_unslash( $_GET['origen'] ) ) : '';
	$origen      = in_array( $origen, array( 'home', 'qr' ), true ) ? $origen : '';
	$count_home  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE origen = 'home'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count_qr    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE origen = 'qr'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$count_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total_items = $origen ? ( 'qr' === $origen ? $count_qr : $count_home ) : $count_total;
	$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
	$query       = "SELECT id, nombre, apellido, dni, telefono, email, ninos, origen, created_at FROM {$table_name}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$args        = array();

	if ( $origen ) {
		$query .= ' WHERE origen = %s';
		$args[] = $origen;
	}

	$query .= ' ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d';
	$args[] = $per_page;
	$args[] = $offset;

	$rows       = $wpdb->get_results( $wpdb->prepare( $query, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$ninos_map  = futbolfest_registro_get_ninos_map( wp_list_pluck( $rows, 'id' ) );
	$export_url = wp_nonce_url(
		add_query_arg(
			array_filter(
				array(
					'action' => 'futbolfest_registro_export',
					'origen' => $origen,
				)
			),
			admin_url( 'admin-post.php' )
		),
		'futbolfest_registro_export'
	);
	$base_url   = admin_url( 'admin.php?page=futbolfest-registros' );
	?>
	<div class="wrap">
		<h1>Registros Futbol Fest</h1>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo '' === $origen ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $base_url ); ?>">
				Todos <span class="count">(<?php echo esc_html( number_format_i18n( $count_total ) ); ?>)</span>
			</a>
			<a class="nav-tab <?php echo 'home' === $origen ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'origen', 'home', $base_url ) ); ?>">
				Home <span class="count">(<?php echo esc_html( number_format_i18n( $count_home ) ); ?>)</span>
			</a>
			<a class="nav-tab <?php echo 'qr' === $origen ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'origen', 'qr', $base_url ) ); ?>">
				QR <span class="count">(<?php echo esc_html( number_format_i18n( $count_qr ) ); ?>)</span>
			</a>
		</h2>

		<p>
			<a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>">Descargar CSV</a>
		</p>

		<p>
			<strong>Total de registros:</strong> <?php echo esc_html( number_format_i18n( $total_items ) ); ?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Nombre</th>
					<th>Apellido</th>
					<th>DNI</th>
					<th>Telefono</th>
					<th>Email</th>
					<th>Niños</th>
					<th>Detalle niños</th>
					<th>Origen</th>
					<th>Fecha</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="10">Todavia no hay registros.</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row->id ); ?></td>
							<td><?php echo esc_html( $row->nombre ); ?></td>
							<td><?php echo esc_html( $row->apellido ); ?></td>
							<td><?php echo esc_html( $row->dni ); ?></td>
							<td><?php echo esc_html( $row->telefono ); ?></td>
							<td>
								<a href="mailto:<?php echo esc_attr( $row->email ); ?>">
									<?php echo esc_html( $row->email ); ?>
								</a>
							</td>
							<td><?php echo esc_html( futbolfest_registro_ninos_label( $row->ninos ) ); ?></td>
							<td><?php echo esc_html( futbolfest_registro_format_ninos_data( isset( $ninos_map[ $row->id ] ) ? $ninos_map[ $row->id ] : array() ) ); ?></td>
							<td><?php echo esc_html( futbolfest_registro_origen_label( $row->origen ) ); ?></td>
							<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $row->created_at ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'current'   => $current,
								'total'     => $total_pages,
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php
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

	$filename = 'futbolfest-registros' . ( $origen ? '-' . $origen : '' ) . '-' . gmdate( 'Y-m-d-His' ) . '.csv';

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );

	fputcsv( $output, array( 'ID', 'Nombre', 'Apellido', 'DNI', 'Telefono', 'Email', 'Niños', 'Detalle niños', 'Origen', 'Fecha' ) );

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
