<?php
/**
 * Registration feature loader for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const FUTBOLFEST_REGISTRO_SCHEMA_VERSION = '1.10.0';
const FUTBOLFEST_REGISTRO_MIN_SUBMIT_SECONDS = 2;
const FUTBOLFEST_REGISTRO_RATE_LIMIT_MINUTE = 120;
const FUTBOLFEST_REGISTRO_RATE_LIMIT_HOUR = 3000;
const FUTBOLFEST_REGISTRO_BOT_LOCK_SECONDS = 1800;
const FUTBOLFEST_REGISTRO_MAX_NINOS = 10;
const FUTBOLFEST_REGISTRO_EMAIL_BATCH_SIZE = 40;
const FUTBOLFEST_REGISTRO_EMAIL_MAX_ATTEMPTS = 4;
const FUTBOLFEST_REGISTRO_PUBLIC_URL = 'https://futbolfestx.com/';
const FUTBOLFEST_REGISTRO_FROM_NAME = 'Futbol Fest';

require_once get_theme_file_path( 'inc/registro/database.php' );
require_once get_theme_file_path( 'inc/registro/assets.php' );
require_once get_theme_file_path( 'inc/registro/helpers.php' );
require_once get_theme_file_path( 'inc/registro/security.php' );
require_once get_theme_file_path( 'inc/registro/validation.php' );
require_once get_theme_file_path( 'inc/registro/mail.php' );
require_once get_theme_file_path( 'inc/registro/email-queue.php' );
require_once get_theme_file_path( 'inc/registro/submission.php' );
require_once get_theme_file_path( 'inc/registro/admin.php' );
require_once get_theme_file_path( 'inc/registro/export.php' );
