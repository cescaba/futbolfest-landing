<?php
/**
 * Complaint admin screen for Futbol Fest.
 *
 * @package Futbol_Fest_Landing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the complaints admin page.
 *
 * @return void
 */
function futbolfest_reclamacion_admin_menu() {
	add_menu_page(
		'Reclamaciones Futbol Fest',
		'Reclamaciones FF',
		'manage_options',
		'futbolfest-reclamaciones',
		'futbolfest_reclamacion_render_admin_page',
		'dashicons-feedback',
		31
	);
}
add_action( 'admin_menu', 'futbolfest_reclamacion_admin_menu' );

/**
 * Renders the complaints admin table.
 *
 * @return void
 */
function futbolfest_reclamacion_render_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta pagina.', 'futbolfest-landing' ) );
	}

	futbolfest_reclamacion_maybe_create_table();

	global $wpdb;

	$table_name  = futbolfest_reclamacion_table_name();
	$detail_id   = isset( $_GET['reclamacion_id'] ) ? absint( $_GET['reclamacion_id'] ) : 0;
	$base_url    = admin_url( 'admin.php?page=futbolfest-reclamaciones' );

	if ( $detail_id > 0 ) {
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, nombre_completo, apellido_completo, tipo_documento, documento, email, celular, domicilio, tipo_solicitud, fecha_hecha, servicios_relacionado, descripcion, email_sent, created_at FROM {$table_name} WHERE id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$detail_id
			)
		);

		?>
		<div class="wrap">
			<h1>Detalle de reclamacion</h1>

			<p>
				<a class="button" href="<?php echo esc_url( $base_url ); ?>">Volver a reclamaciones</a>
			</p>

			<?php if ( empty( $row ) ) : ?>
				<div class="notice notice-error">
					<p>No existe la reclamacion solicitada.</p>
				</div>
			<?php else : ?>
				<table class="widefat striped">
					<tbody>
						<tr>
							<th>ID</th>
							<td><?php echo esc_html( $row->id ); ?></td>
						</tr>
						<tr>
							<th>Nombre completo</th>
							<td><?php echo esc_html( trim( $row->nombre_completo . ' ' . $row->apellido_completo ) ); ?></td>
						</tr>
						<tr>
							<th>Documento</th>
							<td><?php echo esc_html( futbolfest_reclamacion_documento_label( $row->tipo_documento ) . ' - ' . $row->documento ); ?></td>
						</tr>
						<tr>
							<th>Email</th>
							<td><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a></td>
						</tr>
						<tr>
							<th>Celular</th>
							<td><?php echo esc_html( $row->celular ); ?></td>
						</tr>
						<tr>
							<th>Domicilio</th>
							<td><?php echo esc_html( $row->domicilio ); ?></td>
						</tr>
						<tr>
							<th>Tipo</th>
							<td><?php echo esc_html( futbolfest_reclamacion_solicitud_label( $row->tipo_solicitud ) ); ?></td>
						</tr>
						<tr>
							<th>Fecha hecho</th>
							<td><?php echo esc_html( $row->fecha_hecha ? mysql2date( 'd/m/Y', $row->fecha_hecha ) : '' ); ?></td>
						</tr>
						<tr>
							<th>Servicio</th>
							<td><?php echo esc_html( $row->servicios_relacionado ); ?></td>
						</tr>
						<tr>
							<th>Correo</th>
							<td><?php echo esc_html( futbolfest_reclamacion_email_sent_label( $row->email_sent ) ); ?></td>
						</tr>
						<tr>
							<th>Fecha registro</th>
							<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $row->created_at ) ); ?></td>
						</tr>
						<tr>
							<th>Descripcion</th>
							<td style="white-space:pre-wrap;"><?php echo esc_html( $row->descripcion ); ?></td>
						</tr>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
		return;
	}

	$per_page    = 50;
	$current     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$offset      = ( $current - 1 ) * $per_page;
	$count_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$total_pages = max( 1, (int) ceil( $count_total / $per_page ) );
	$query       = "SELECT id, nombre_completo, apellido_completo, tipo_documento, documento, email, celular, domicilio, tipo_solicitud, fecha_hecha, servicios_relacionado, descripcion, email_sent, created_at FROM {$table_name} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$rows        = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$export_url  = wp_nonce_url(
		add_query_arg(
			array( 'action' => 'futbolfest_reclamacion_export' ),
			admin_url( 'admin-post.php' )
		),
		'futbolfest_reclamacion_export'
	);
	?>
	<div class="wrap">
		<h1>Reclamaciones Futbol Fest</h1>

		<p>
			<a class="button button-primary" href="<?php echo esc_url( $export_url ); ?>">Descargar todos CSV</a>
		</p>

		<p>
			<strong>Total de reclamaciones:</strong> <?php echo esc_html( number_format_i18n( $count_total ) ); ?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Nombre completo</th>
					<th>Documento</th>
					<th>Email</th>
					<th>Celular</th>
					<th>Tipo</th>
					<th>Fecha hecho</th>
					<th>Servicio</th>
					<th>Correo</th>
					<th>Fecha registro</th>
					<th>Acciones</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="11">Todavia no hay reclamaciones.</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$row_detail_url = add_query_arg(
							array(
								'page'           => 'futbolfest-reclamaciones',
								'reclamacion_id' => absint( $row->id ),
							),
							admin_url( 'admin.php' )
						);
						$row_export_url = wp_nonce_url(
							add_query_arg(
								array(
									'action'         => 'futbolfest_reclamacion_export',
									'reclamacion_id' => absint( $row->id ),
								),
								admin_url( 'admin-post.php' )
							),
							'futbolfest_reclamacion_export'
						);
						?>
						<tr>
							<td><?php echo esc_html( $row->id ); ?></td>
							<td><?php echo esc_html( trim( $row->nombre_completo . ' ' . $row->apellido_completo ) ); ?></td>
							<td><?php echo esc_html( futbolfest_reclamacion_documento_label( $row->tipo_documento ) . ' - ' . $row->documento ); ?></td>
							<td>
								<a href="mailto:<?php echo esc_attr( $row->email ); ?>">
									<?php echo esc_html( $row->email ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $row->celular ); ?></td>
							<td><?php echo esc_html( futbolfest_reclamacion_solicitud_label( $row->tipo_solicitud ) ); ?></td>
							<td><?php echo esc_html( $row->fecha_hecha ? mysql2date( 'd/m/Y', $row->fecha_hecha ) : '' ); ?></td>
							<td><?php echo esc_html( $row->servicios_relacionado ); ?></td>
							<td><?php echo esc_html( futbolfest_reclamacion_email_sent_label( $row->email_sent ) ); ?></td>
							<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $row->created_at ) ); ?></td>
							<td>
								<a class="button button-small" href="<?php echo esc_url( $row_detail_url ); ?>">Ver detalle</a>
								<a class="button button-small" href="<?php echo esc_url( $row_export_url ); ?>">Descargar</a>
							</td>
						</tr>
						<tr>
							<td></td>
							<td colspan="10">
								<strong>Descripcion:</strong>
								<?php echo esc_html( wp_trim_words( $row->descripcion, 45, '...' ) ); ?>
							</td>
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
