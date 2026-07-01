<?php
/**
 * Complaint anti-abuse guards for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns a normalized client IP for abuse controls.
 *
 * @return string
 */
function futbolfest_reclamacion_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';

	return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

/**
 * Builds transient keys without storing raw IP addresses.
 *
 * @param string $suffix Key suffix.
 * @return string
 */
function futbolfest_reclamacion_security_key( $suffix ) {
	return 'ff_rec_' . md5( futbolfest_reclamacion_client_ip() . '|' . $suffix );
}

/**
 * Applies per-IP rate limits before database work.
 *
 * @return void
 */
function futbolfest_reclamacion_guard_rate_limit() {
	if ( get_transient( futbolfest_reclamacion_security_key( 'bot_lock' ) ) ) {
		wp_send_json_error(
			array( 'message' => 'Demasiados intentos. Intenta nuevamente en unos minutos.' ),
			429
		);
	}

	$limits = array(
		'minute' => array(
			'max' => FUTBOLFEST_RECLAMACION_RATE_LIMIT_MINUTE,
			'ttl' => MINUTE_IN_SECONDS,
		),
		'hour'   => array(
			'max' => FUTBOLFEST_RECLAMACION_RATE_LIMIT_HOUR,
			'ttl' => HOUR_IN_SECONDS,
		),
	);

	foreach ( $limits as $name => $limit ) {
		$key   = futbolfest_reclamacion_security_key( 'rate_' . $name );
		$count = (int) get_transient( $key );

		if ( $count >= $limit['max'] ) {
			wp_send_json_error(
				array( 'message' => 'Demasiadas reclamaciones desde esta red. Intenta nuevamente más tarde.' ),
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
function futbolfest_reclamacion_lock_client() {
	set_transient(
		futbolfest_reclamacion_security_key( 'bot_lock' ),
		1,
		FUTBOLFEST_RECLAMACION_BOT_LOCK_SECONDS
	);
}

/**
 * Rejects submissions sent unrealistically fast.
 *
 * @return void
 */
function futbolfest_reclamacion_guard_submit_time() {
	$rendered_at = isset( $_POST['form_rendered_at'] ) ? absint( $_POST['form_rendered_at'] ) : 0;
	$now         = time();

	if ( ! $rendered_at || $rendered_at > $now || ( $now - $rendered_at ) < FUTBOLFEST_RECLAMACION_MIN_SUBMIT_SECONDS ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos validar el formulario. Inténtalo nuevamente.' ),
			400
		);
	}
}
