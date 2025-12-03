<?php
/**
 * Templates list view.
 *
 * @package CertBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap certbuilder-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Certificate Templates', 'certbuilder-pro' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'certbuilder-pro' ); ?>
	</a>
	<hr class="wp-header-end">

	<?php if ( empty( $templates ) ) : ?>
		<div class="certbuilder-empty-state-large">
			<span class="dashicons dashicons-media-document"></span>
			<h2><?php esc_html_e( 'No Templates Yet', 'certbuilder-pro' ); ?></h2>
			<p><?php esc_html_e( 'Create your first certificate template to get started.', 'certbuilder-pro' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder' ) ); ?>" class="button button-primary button-hero">
				<?php esc_html_e( 'Create Template', 'certbuilder-pro' ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="certbuilder-templates-grid">
			<?php foreach ( $templates as $template ) : ?>
				<?php $usage = certbuilder()->template()->get_usage_count( $template['id'] ); ?>
				<div class="certbuilder-template-card">
					<div class="template-preview">
						<?php if ( $template['thumbnail'] ) : ?>
							<img src="<?php echo esc_url( $template['thumbnail'] ); ?>" alt="<?php echo esc_attr( $template['title'] ); ?>">
						<?php else : ?>
							<div class="template-preview-placeholder">
								<span class="dashicons dashicons-media-document"></span>
							</div>
						<?php endif; ?>
					</div>
					<div class="template-info">
						<h3><?php echo esc_html( $template['title'] ); ?></h3>
						<p class="template-meta">
							<?php
							printf(
								/* translators: %d: Number of certificates */
								esc_html( _n( '%d certificate', '%d certificates', $usage, 'certbuilder-pro' ) ),
								esc_html( $usage )
							);
							?>
							&bull;
							<?php
							printf(
								/* translators: %s: Date */
								esc_html__( 'Modified %s', 'certbuilder-pro' ),
								esc_html( wp_date( get_option( 'date_format' ), strtotime( $template['modified'] ) ) )
							);
							?>
						</p>
					</div>
					<div class="template-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder&template_id=' . $template['id'] ) ); ?>" class="button button-primary">
							<?php esc_html_e( 'Edit', 'certbuilder-pro' ); ?>
						</a>
						<button type="button" class="button certbuilder-duplicate-template" data-template-id="<?php echo esc_attr( $template['id'] ); ?>">
							<?php esc_html_e( 'Duplicate', 'certbuilder-pro' ); ?>
						</button>
						<?php if ( 0 === $usage ) : ?>
							<button type="button" class="button certbuilder-delete-template" data-template-id="<?php echo esc_attr( $template['id'] ); ?>">
								<?php esc_html_e( 'Delete', 'certbuilder-pro' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
