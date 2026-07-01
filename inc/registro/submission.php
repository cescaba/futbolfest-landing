<?php
/**
 * Registration submission flow for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles AJAX registration submissions.
 *
 * @return void
 */
function futbolfest_registro_handle_submission() {
	check_ajax_referer( 'futbolfest_registro_submit', 'nonce' );
	futbolfest_registro_guard_rate_limit();
	futbolfest_registro_guard_submit_time();

	if ( '' !== futbolfest_registro_post_text( 'sitio_web' ) ) {
		futbolfest_registro_lock_client();
		wp_send_json_error(
			array( 'message' => 'No pudimos procesar tu registro.' ),
			400
		);
	}

	$nombre     = futbolfest_registro_post_text( 'nombre' );
	$apellido   = futbolfest_registro_post_text( 'apellido' );
	$dni        = futbolfest_registro_post_text( 'dni' );
	$telefono   = futbolfest_registro_post_text( 'telefono' );
	$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$ninos_raw  = isset( $_POST['ninos'] ) ? sanitize_text_field( wp_unslash( $_POST['ninos'] ) ) : '';
	$ninos      = '' !== $ninos_raw ? absint( $ninos_raw ) : null;
	$origen     = futbolfest_registro_post_text( 'origen' );
	$origen     = in_array( $origen, array( 'home', 'qr' ), true ) ? $origen : 'home';
	$ninos_data = array();

	if ( '' === $nombre || '' === $apellido || '' === $dni || '' === $telefono || '' === $email ) {
		wp_send_json_error(
			array( 'message' => 'Completa todos los campos obligatorios.' ),
			400
		);
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error(
			array( 'message' => 'Ingresa un correo electronico valido.' ),
			400
		);
	}

	$email = strtolower( $email );

	if ( ! preg_match( '/^[0-9]{8,12}$/', $dni ) ) {
		wp_send_json_error(
			array( 'message' => 'Ingresa un DNI valido.' ),
			400
		);
	}

	if ( null === $ninos || $ninos > 1 ) {
		wp_send_json_error(
			array( 'message' => 'Selecciona una opcion valida para ninos.' ),
			400
		);
	}

	if ( 1 === $ninos ) {
		$ninos_data = futbolfest_registro_post_ninos_data();

		if ( empty( $ninos_data ) ) {
			wp_send_json_error(
				array( 'message' => 'Agrega al menos un nino.' ),
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

	$duplicate_field = futbolfest_registro_find_duplicate_field( $dni, $email, $telefono );

	if ( '' !== $duplicate_field ) {
		$duplicate_messages = array(
			'dni'      => 'Este DNI ya se encuentra registrado.',
			'email'    => 'Este correo electronico ya se encuentra registrado.',
			'telefono' => 'Este telefono ya se encuentra registrado.',
			'registro' => 'Ya existe un registro con estos datos.',
		);

		wp_send_json_error(
			array( 'message' => isset( $duplicate_messages[ $duplicate_field ] ) ? $duplicate_messages[ $duplicate_field ] : $duplicate_messages['registro'] ),
			409
		);
	}

	$inserted = $wpdb->insert(
		futbolfest_registro_table_name(),
		array(
			'nombre'   => $nombre,
			'apellido' => $apellido,
			'dni'      => $dni,
			'telefono' => $telefono,
			'email'    => $email,
			'ninos'    => $ninos,
			'origen'   => $origen,
		),
		array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
	);

	if ( false === $inserted ) {
		if ( isset( $wpdb->last_error ) && false !== stripos( $wpdb->last_error, 'Duplicate' ) ) {
			wp_send_json_error(
				array( 'message' => 'Ya existe un registro con este DNI, correo o telefono.' ),
				409
			);
		}

		wp_send_json_error(
			array( 'message' => 'No pudimos guardar tu registro. Intentalo nuevamente.' ),
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
				array( 'message' => 'No pudimos guardar los datos de los ninos. Intentalo nuevamente.' ),
				500
			);
		}
	}

	futbolfest_registro_queue_confirmation_email( $registro_id, $email, $nombre, $apellido );

	wp_send_json_success(
		array( 'message' => 'Registro guardado correctamente.' )
	);
}
add_action( 'wp_ajax_futbolfest_registro_submit', 'futbolfest_registro_handle_submission' );
add_action( 'wp_ajax_nopriv_futbolfest_registro_submit', 'futbolfest_registro_handle_submission' );
