<?php
/**
 * Complaint feature loader for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FUTBOLFEST_RECLAMACION_RECIPIENT = 'alfredhuarcaya30@gmail.com';
const FUTBOLFEST_RECLAMACION_SCHEMA_VERSION = '1.1.0';
const FUTBOLFEST_RECLAMACION_MIN_SUBMIT_SECONDS = 2;
const FUTBOLFEST_RECLAMACION_RATE_LIMIT_MINUTE = 60;
const FUTBOLFEST_RECLAMACION_RATE_LIMIT_HOUR = 300;
const FUTBOLFEST_RECLAMACION_BOT_LOCK_SECONDS = 1800;

require_once get_theme_file_path( 'inc/reclamacion/helpers.php' );
require_once get_theme_file_path( 'inc/reclamacion/database.php' );
require_once get_theme_file_path( 'inc/reclamacion/security.php' );
require_once get_theme_file_path( 'inc/reclamacion/mail.php' );
require_once get_theme_file_path( 'inc/reclamacion/submission.php' );
require_once get_theme_file_path( 'inc/reclamacion/admin.php' );
require_once get_theme_file_path( 'inc/reclamacion/export.php' );
