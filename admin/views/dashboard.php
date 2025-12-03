<?php
/**
 * Admin dashboard view.
 *
 * @package CertBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap certbuilder-admin">
	<h1><?php esc_html_e( 'CertBuilder Pro', 'certbuilder-pro' ); ?></h1>

	<div class="certbuilder-dashboard">
		<!-- Stats Cards -->
		<div class="certbuilder-stats-grid">
			<div class="certbuilder-stat-card">
				<div class="stat-icon">
					<span class="dashicons dashicons-awards"></span>
				</div>
				<div class="stat-content">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Total Certificates', 'certbuilder-pro' ); ?></div>
				</div>
			</div>

			<div class="certbuilder-stat-card">
				<div class="stat-icon stat-success">
					<span class="dashicons dashicons-yes-alt"></span>
				</div>
				<div class="stat-content">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( $stats['active'] ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Active Certificates', 'certbuilder-pro' ); ?></div>
				</div>
			</div>

			<div class="certbuilder-stat-card">
				<div class="stat-icon stat-info">
					<span class="dashicons dashicons-calendar-alt"></span>
				</div>
				<div class="stat-content">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( $stats['monthly'] ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'This Month', 'certbuilder-pro' ); ?></div>
				</div>
			</div>

			<div class="certbuilder-stat-card">
				<div class="stat-icon stat-warning">
					<span class="dashicons dashicons-visibility"></span>
				</div>
				<div class="stat-content">
					<div class="stat-number"><?php echo esc_html( number_format_i18n( $stats['total_views'] ) ); ?></div>
					<div class="stat-label"><?php esc_html_e( 'Verifications', 'certbuilder-pro' ); ?></div>
				</div>
			</div>
		</div>

		<div class="certbuilder-dashboard-grid">
			<!-- Quick Actions -->
			<div class="certbuilder-card">
				<h2><?php esc_html_e( 'Quick Actions', 'certbuilder-pro' ); ?></h2>
				<div class="certbuilder-quick-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder' ) ); ?>" class="button button-primary button-hero">
						<span class="dashicons dashicons-plus-alt2"></span>
						<?php esc_html_e( 'Create New Template', 'certbuilder-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-templates' ) ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-media-document"></span>
						<?php esc_html_e( 'View All Templates', 'certbuilder-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-certificates' ) ); ?>" class="button button-secondary">
						<span class="dashicons dashicons-awards"></span>
						<?php esc_html_e( 'View All Certificates', 'certbuilder-pro' ); ?>
					</a>
				</div>
			</div>

			<!-- Recent Templates -->
			<div class="certbuilder-card">
				<h2><?php esc_html_e( 'Recent Templates', 'certbuilder-pro' ); ?></h2>
				<?php if ( empty( $templates ) ) : ?>
					<p class="certbuilder-empty-state">
						<?php esc_html_e( 'No templates yet.', 'certbuilder-pro' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder' ) ); ?>">
							<?php esc_html_e( 'Create your first template', 'certbuilder-pro' ); ?>
						</a>
					</p>
				<?php else : ?>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Template', 'certbuilder-pro' ); ?></th>
								<th><?php esc_html_e( 'Usage', 'certbuilder-pro' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'certbuilder-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $templates as $template ) : ?>
								<?php $usage = certbuilder()->template()->get_usage_count( $template['id'] ); ?>
								<tr>
									<td>
										<strong><?php echo esc_html( $template['title'] ); ?></strong>
									</td>
									<td>
										<?php
										printf(
											/* translators: %d: Number of certificates */
											esc_html( _n( '%d certificate', '%d certificates', $usage, 'certbuilder-pro' ) ),
											esc_html( $usage )
										);
										?>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder&template_id=' . $template['id'] ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'certbuilder-pro' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Recent Certificates -->
			<div class="certbuilder-card">
				<h2><?php esc_html_e( 'Recent Certificates', 'certbuilder-pro' ); ?></h2>
				<?php if ( empty( $recent_certs['certificates'] ) ) : ?>
					<p class="certbuilder-empty-state">
						<?php esc_html_e( 'No certificates issued yet.', 'certbuilder-pro' ); ?>
					</p>
				<?php else : ?>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Certificate ID', 'certbuilder-pro' ); ?></th>
								<th><?php esc_html_e( 'Recipient', 'certbuilder-pro' ); ?></th>
								<th><?php esc_html_e( 'Issued', 'certbuilder-pro' ); ?></th>
								<th><?php esc_html_e( 'Status', 'certbuilder-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_certs['certificates'] as $cert ) : ?>
								<?php $user = get_userdata( $cert->user_id ); ?>
								<tr>
									<td>
										<code><?php echo esc_html( $cert->certificate_id ); ?></code>
									</td>
									<td>
										<?php echo esc_html( $user ? $user->display_name : __( 'Unknown', 'certbuilder-pro' ) ); ?>
									</td>
									<td>
										<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $cert->issued_at ) ) ); ?>
									</td>
									<td>
										<span class="certbuilder-status certbuilder-status-<?php echo esc_attr( $cert->status ); ?>">
											<?php echo esc_html( ucfirst( $cert->status ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- LMS Status -->
			<div class="certbuilder-card">
				<h2><?php esc_html_e( 'Connected LMS', 'certbuilder-pro' ); ?></h2>
				<div class="certbuilder-lms-status">
					<?php foreach ( certbuilder()->connectors()->get_all() as $id => $connector ) : ?>
						<?php if ( 'manual' === $id ) continue; ?>
						<div class="certbuilder-lms-item">
							<span class="lms-name"><?php echo esc_html( $connector->get_name() ); ?></span>
							<?php if ( $connector->is_available() ) : ?>
								<span class="lms-status lms-active">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Active', 'certbuilder-pro' ); ?>
								</span>
							<?php else : ?>
								<span class="lms-status lms-inactive">
									<span class="dashicons dashicons-minus"></span>
									<?php esc_html_e( 'Not Installed', 'certbuilder-pro' ); ?>
								</span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</div>
