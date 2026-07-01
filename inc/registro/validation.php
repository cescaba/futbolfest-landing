<?php
/**
 * Registration validation helpers for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
				array( 'message' => 'Completa correctamente los datos de cada nino.' ),
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
 * Returns the first duplicated registration field found.
 *
 * @param string $dni Documento de identidad.
 * @param string $email Email address.
 * @param string $telefono Normalized phone number.
 * @return string
 */
function futbolfest_registro_find_duplicate_field( $dni, $email, $telefono ) {
	global $wpdb;

	$table_name = futbolfest_registro_table_name();

	$duplicate = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT dni, email, telefono FROM {$table_name} WHERE dni = %s OR LOWER(email) = LOWER(%s) OR telefono = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$dni,
			$email,
			$telefono
		),
		ARRAY_A
	);

	if ( ! is_array( $duplicate ) ) {
		return '';
	}

	if ( isset( $duplicate['dni'] ) && $duplicate['dni'] === $dni ) {
		return 'dni';
	}

	if ( isset( $duplicate['email'] ) && strtolower( $duplicate['email'] ) === strtolower( $email ) ) {
		return 'email';
	}

	if ( isset( $duplicate['telefono'] ) && $duplicate['telefono'] === $telefono ) {
		return 'telefono';
	}

	return 'registro';
}
