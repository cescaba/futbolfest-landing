<?php
/**
 * Registration email queue for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the internal token used by the non-blocking email queue trigger.
 *
 * @return string
 */
function futbolfest_registro_email_queue_token() {
	return hash_hmac( 'sha256', 'futbolfest_registro_email_queue', wp_salt( 'auth' ) );
}

/**
 * Schedules a fallback queue processor in case the async request is delayed.
 *
 * @return void
 */
function futbolfest_registro_schedule_email_queue_fallback() {
	if ( ! wp_next_scheduled( 'futbolfest_registro_process_email_queue' ) ) {
		wp_schedule_single_event( time() + 60, 'futbolfest_registro_process_email_queue' );
	}
}

/**
 * Triggers queue processing without making the registration request wait.
 *
 * @return void
 */
function futbolfest_registro_dispatch_email_queue() {
	futbolfest_registro_schedule_email_queue_fallback();

	wp_remote_post(
		admin_url( 'admin-ajax.php' ),
		array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			'body'      => array(
				'action' => 'futbolfest_registro_process_email_queue',
				'token'  => futbolfest_registro_email_queue_token(),
			),
		)
	);
}

/**
 * Queues the confirmation email so the AJAX registration response stays fast.
 *
 * @param int    $registro_id Registration ID.
 * @param string $email Recipient email.
 * @param string $nombre Recipient first name.
 * @param string $apellido Recipient last name.
 * @return void
 */
function futbolfest_registro_queue_confirmation_email( $registro_id, $email, $nombre, $apellido ) {
	global $wpdb;

	$recipient = sanitize_email( $email );

	if ( ! $registro_id || ! is_email( $recipient ) ) {
		return;
	}

	$wpdb->replace(
		futbolfest_registro_email_queue_table_name(),
		array(
			'registro_id'  => absint( $registro_id ),
			'email'        => $recipient,
			'nombre'       => sanitize_text_field( $nombre ),
			'apellido'     => sanitize_text_field( $apellido ),
			'status'       => 'pending',
			'attempts'     => 0,
			'last_error'   => null,
			'available_at' => current_time( 'mysql' ),
			'created_at'   => current_time( 'mysql' ),
			'sent_at'      => null,
		),
		array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
	);

	futbolfest_registro_dispatch_email_queue();
}

/**
 * Processes queued confirmation emails in controlled batches.
 *
 * @param int $limit Batch size.
 * @return int Number of processed rows.
 */
function futbolfest_registro_process_email_queue_batch( $limit = FUTBOLFEST_REGISTRO_EMAIL_BATCH_SIZE ) {
	global $wpdb;

	$table_name = futbolfest_registro_email_queue_table_name();
	$now        = current_time( 'mysql' );
	$limit      = max( 1, min( 100, absint( $limit ) ) );
	$rows       = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE ((status IN ('pending', 'failed') AND attempts < %d) OR (status = 'processing' AND attempts < %d AND available_at <= %s)) AND available_at <= %s ORDER BY created_at ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			FUTBOLFEST_REGISTRO_EMAIL_MAX_ATTEMPTS,
			FUTBOLFEST_REGISTRO_EMAIL_MAX_ATTEMPTS,
			$now,
			$now,
			$limit
		),
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		return 0;
	}

	foreach ( $rows as $row ) {
		$queue_id = absint( $row['id'] );
		$attempts = absint( $row['attempts'] ) + 1;

		$locked = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name} SET status = 'processing', attempts = %d, available_at = %s WHERE id = %d AND status IN ('pending', 'failed', 'processing') AND available_at <= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$attempts,
				date_i18n( 'Y-m-d H:i:s', time() + 600 ),
				$queue_id,
				$now
			)
		);

		if ( ! $locked ) {
			continue;
		}

		$sent = futbolfest_registro_send_confirmation_email( $row['email'], $row['nombre'], $row['apellido'] );

		if ( $sent ) {
			$wpdb->update(
				$table_name,
				array(
					'status'     => 'sent',
					'last_error' => null,
					'sent_at'    => current_time( 'mysql' ),
				),
				array( 'id' => $queue_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
			continue;
		}

		$retry_delay = min( 3600, 60 * $attempts * $attempts );
		$next_status = $attempts >= FUTBOLFEST_REGISTRO_EMAIL_MAX_ATTEMPTS ? 'failed' : 'pending';

		$wpdb->update(
			$table_name,
			array(
				'status'       => $next_status,
				'last_error'   => 'wp_mail returned false',
				'available_at' => date_i18n( 'Y-m-d H:i:s', time() + $retry_delay ),
			),
			array( 'id' => $queue_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	if ( futbolfest_registro_email_queue_has_pending() ) {
		futbolfest_registro_schedule_email_queue_fallback();
	}

	return count( $rows );
}

/**
 * Checks if pending email queue rows remain.
 *
 * @return bool
 */
function futbolfest_registro_email_queue_has_pending() {
	global $wpdb;

	$table_name = futbolfest_registro_email_queue_table_name();
	$pending    = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE status IN ('pending', 'failed') AND attempts < %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			FUTBOLFEST_REGISTRO_EMAIL_MAX_ATTEMPTS
		)
	);

	return (int) $pending > 0;
}

/**
 * Handles async queue processing requests.
 *
 * @return void
 */
function futbolfest_registro_process_email_queue_ajax() {
	$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

	if ( ! hash_equals( futbolfest_registro_email_queue_token(), $token ) ) {
		wp_send_json_error( array( 'message' => 'Invalid queue token.' ), 403 );
	}

	$processed = futbolfest_registro_process_email_queue_batch();

	wp_send_json_success( array( 'processed' => $processed ) );
}
add_action( 'wp_ajax_futbolfest_registro_process_email_queue', 'futbolfest_registro_process_email_queue_ajax' );
add_action( 'wp_ajax_nopriv_futbolfest_registro_process_email_queue', 'futbolfest_registro_process_email_queue_ajax' );
add_action( 'futbolfest_registro_process_email_queue', 'futbolfest_registro_process_email_queue_batch' );
