<?php
/**
 * Registration helpers for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	return absint( $ninos ) ? 'Si' : 'No';
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
		$items[] = sprintf( '%s (%d anos)', $nino['nombre'], absint( $nino['edad'] ) );
	}

	return implode( ', ', $items );
}
