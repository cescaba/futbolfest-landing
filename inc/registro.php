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
const FUTBOLFEST_REGISTRO_SCHEMA_VERSION = '1.0.0';
const FUTBOLFEST_REGISTRO_MIN_SUBMIT_SECONDS = 2;
const FUTBOLFEST_REGISTRO_RATE_LIMIT_MINUTE = 120;
const FUTBOLFEST_REGISTRO_RATE_LIMIT_HOUR = 3000;
const FUTBOLFEST_REGISTRO_BOT_LOCK_SECONDS = 1800;

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
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY email (email),
		KEY dni (dni),
		KEY created_at (created_at)
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
	update_option( 'futbolfest_registro_schema_version', FUTBOLFEST_REGISTRO_SCHEMA_VERSION );
}

/**
 * Runs lightweight schema migrations only when needed.
 *
 * @return void
 */
function futbolfest_registro_maybe_create_table() {
	if ( get_option( 'futbolfest_registro_schema_version' ) === FUTBOLFEST_REGISTRO_SCHEMA_VERSION ) {
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

	$nombre   = futbolfest_registro_post_text( 'nombre' );
	$apellido = futbolfest_registro_post_text( 'apellido' );
	$dni      = futbolfest_registro_post_text( 'dni' );
	$telefono = futbolfest_registro_post_text( 'telefono' );
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

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
			'nombre'   => $nombre,
			'apellido' => $apellido,
			'dni'      => $dni,
			'telefono' => $telefono,
			'email'    => $email,
		),
		array( '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $inserted ) {
		wp_send_json_error(
			array( 'message' => 'No pudimos guardar tu registro. Inténtalo nuevamente.' ),
			500
		);
	}

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
	$total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
	$rows        = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, nombre, apellido, dni, telefono, email, created_at FROM {$table_name} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$per_page,
			$offset
		)
	);
	$export_url  = wp_nonce_url(
		admin_url( 'admin-post.php?action=futbolfest_registro_export' ),
		'futbolfest_registro_export'
	);
	?>
	<div class="wrap">
		<h1>Registros Futbol Fest</h1>

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
					<th>Fecha</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="7">Todavia no hay registros.</td>
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
	$rows       = $wpdb->get_results(
		"SELECT id, nombre, apellido, dni, telefono, email, created_at FROM {$table_name} ORDER BY created_at DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		ARRAY_A
	);
	$filename   = 'futbolfest-registros-' . gmdate( 'Y-m-d-His' ) . '.csv';

	while ( ob_get_level() ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );

	fputcsv( $output, array( 'ID', 'Nombre', 'Apellido', 'DNI', 'Telefono', 'Email', 'Fecha' ) );

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
				$row['created_at'],
			)
		);
	}

	fclose( $output );
	exit;
}
add_action( 'admin_post_futbolfest_registro_export', 'futbolfest_registro_export_csv' );
