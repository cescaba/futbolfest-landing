<?php
/**
 * Registration admin screen for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
					<th>Ninos</th>
					<th>Detalle ninos</th>
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
