<?php
/**
 * Certificates list view.
 *
 * @package CertBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Messages.
$message = isset( $_GET['message'] ) ? sanitize_key( $_GET['message'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap certbuilder-admin">
	<h1><?php esc_html_e( 'Issued Certificates', 'certbuilder-pro' ); ?></h1>

	<?php if ( 'revoked' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Certificate revoked successfully.', 'certbuilder-pro' ); ?></p>
		</div>
	<?php elseif ( 'reinstated' === $message ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Certificate reinstated successfully.', 'certbuilder-pro' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Filters -->
	<form method="get" class="certbuilder-filters">
		<input type="hidden" name="page" value="certbuilder-certificates">

		<select name="status">
			<option value=""><?php esc_html_e( 'All Statuses', 'certbuilder-pro' ); ?></option>
			<option value="active" <?php selected( $filters['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'certbuilder-pro' ); ?></option>
			<option value="revoked" <?php selected( $filters['status'], 'revoked' ); ?>><?php esc_html_e( 'Revoked', 'certbuilder-pro' ); ?></option>
			<option value="expired" <?php selected( $filters['status'], 'expired' ); ?>><?php esc_html_e( 'Expired', 'certbuilder-pro' ); ?></option>
		</select>

		<select name="connector">
			<option value=""><?php esc_html_e( 'All Connectors', 'certbuilder-pro' ); ?></option>
			<?php foreach ( $connectors as $id => $connector ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $filters['connector'], $id ); ?>>
					<?php echo esc_html( $connector->get_name() ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input type="search" name="s" value="<?php echo esc_attr( $filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search certificates...', 'certbuilder-pro' ); ?>">

		<button type="submit" class="button"><?php esc_html_e( 'Filter', 'certbuilder-pro' ); ?></button>
	</form>

	<?php if ( empty( $certificates ) ) : ?>
		<div class="certbuilder-empty-state-large">
			<span class="dashicons dashicons-awards"></span>
			<h2><?php esc_html_e( 'No Certificates Found', 'certbuilder-pro' ); ?></h2>
			<p><?php esc_html_e( 'Certificates will appear here when users complete courses or quizzes with assigned templates.', 'certbuilder-pro' ); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th class="column-certificate-id"><?php esc_html_e( 'Certificate ID', 'certbuilder-pro' ); ?></th>
					<th class="column-recipient"><?php esc_html_e( 'Recipient', 'certbuilder-pro' ); ?></th>
					<th class="column-achievement"><?php esc_html_e( 'Achievement', 'certbuilder-pro' ); ?></th>
					<th class="column-connector"><?php esc_html_e( 'Source', 'certbuilder-pro' ); ?></th>
					<th class="column-issued"><?php esc_html_e( 'Issued', 'certbuilder-pro' ); ?></th>
					<th class="column-status"><?php esc_html_e( 'Status', 'certbuilder-pro' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'certbuilder-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $certificates as $cert ) : ?>
					<?php
					$user         = get_userdata( $cert->user_id );
					$object_title = get_the_title( $cert->object_id );
					$connector    = certbuilder()->connectors()->get( $cert->connector );
					$verify_url   = certbuilder()->verifier()->get_verification_url( $cert->verification_code );
					?>
					<tr>
						<td class="column-certificate-id">
							<strong>
								<a href="<?php echo esc_url( $verify_url ); ?>" target="_blank">
									<?php echo esc_html( $cert->certificate_id ); ?>
								</a>
							</strong>
						</td>
						<td class="column-recipient">
							<?php if ( $user ) : ?>
								<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>">
									<?php echo esc_html( $user->display_name ); ?>
								</a>
								<br>
								<small><?php echo esc_html( $user->user_email ); ?></small>
							<?php else : ?>
								<?php esc_html_e( 'Unknown User', 'certbuilder-pro' ); ?>
							<?php endif; ?>
						</td>
						<td class="column-achievement">
							<?php if ( $object_title ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $cert->object_id ) ); ?>">
									<?php echo esc_html( $object_title ); ?>
								</a>
							<?php else : ?>
								<?php esc_html_e( 'Deleted', 'certbuilder-pro' ); ?>
							<?php endif; ?>
						</td>
						<td class="column-connector">
							<?php echo esc_html( $connector ? $connector->get_name() : $cert->connector ); ?>
						</td>
						<td class="column-issued">
							<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $cert->issued_at ) ) ); ?>
							<br>
							<small><?php echo esc_html( wp_date( get_option( 'time_format' ), strtotime( $cert->issued_at ) ) ); ?></small>
						</td>
						<td class="column-status">
							<?php if ( 'revoked' === $cert->status ) : ?>
								<span class="certbuilder-status certbuilder-status-revoked"><?php esc_html_e( 'Revoked', 'certbuilder-pro' ); ?></span>
							<?php elseif ( $cert->expires_at && strtotime( $cert->expires_at ) < time() ) : ?>
								<span class="certbuilder-status certbuilder-status-expired"><?php esc_html_e( 'Expired', 'certbuilder-pro' ); ?></span>
							<?php else : ?>
								<span class="certbuilder-status certbuilder-status-active"><?php esc_html_e( 'Active', 'certbuilder-pro' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="column-actions">
							<a href="<?php echo esc_url( $verify_url ); ?>" target="_blank" class="button button-small">
								<?php esc_html_e( 'View', 'certbuilder-pro' ); ?>
							</a>
							<?php if ( 'revoked' === $cert->status ) : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=certbuilder-certificates&action=reinstate&certificate=' . $cert->id ), 'certbuilder_certificate_action' ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Reinstate', 'certbuilder-pro' ); ?>
								</a>
							<?php else : ?>
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=certbuilder-certificates&action=revoke&certificate=' . $cert->id ), 'certbuilder_certificate_action' ) ); ?>" class="button button-small certbuilder-revoke-btn">
									<?php esc_html_e( 'Revoke', 'certbuilder-pro' ); ?>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<!-- Pagination -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						printf(
							/* translators: %s: Number of items */
							esc_html( _n( '%s item', '%s items', $total, 'certbuilder-pro' ) ),
							esc_html( number_format_i18n( $total ) )
						);
						?>
					</span>
					<span class="pagination-links">
						<?php
						echo wp_kses_post(
							paginate_links(
								[
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => $total_pages,
									'current'   => $current_page,
								]
							)
						);
						?>
					</span>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
