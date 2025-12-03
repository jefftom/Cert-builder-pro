<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>
		<?php
		if ( $valid ) {
			printf(
				/* translators: %s: Certificate ID */
				esc_html__( 'Certificate %s Verified', 'certbuilder-pro' ),
				esc_html( $certificate['certificate_id'] )
			);
		} else {
			esc_html_e( 'Certificate Verification', 'certbuilder-pro' );
		}
		?>
		- <?php bloginfo( 'name' ); ?>
	</title>
	<?php wp_head(); ?>
	<style>
		:root {
			--cb-success: #22c55e;
			--cb-error: #ef4444;
			--cb-warning: #f59e0b;
			--cb-primary: #3b82f6;
			--cb-text: #1f2937;
			--cb-text-light: #6b7280;
			--cb-bg: #f9fafb;
			--cb-white: #ffffff;
			--cb-border: #e5e7eb;
		}

		* {
			box-sizing: border-box;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
			background: var(--cb-bg);
			color: var(--cb-text);
			margin: 0;
			padding: 0;
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}

		.cb-verify-container {
			flex: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 2rem;
		}

		.cb-verify-card {
			background: var(--cb-white);
			border-radius: 12px;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
			max-width: 600px;
			width: 100%;
			overflow: hidden;
		}

		.cb-verify-header {
			padding: 2rem;
			text-align: center;
			border-bottom: 1px solid var(--cb-border);
		}

		.cb-verify-header.cb-valid {
			background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
			color: white;
		}

		.cb-verify-header.cb-invalid {
			background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
			color: white;
		}

		.cb-verify-header.cb-expired {
			background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
			color: white;
		}

		.cb-status-icon {
			font-size: 4rem;
			margin-bottom: 1rem;
		}

		.cb-status-title {
			font-size: 1.5rem;
			font-weight: 600;
			margin: 0 0 0.5rem;
		}

		.cb-status-message {
			opacity: 0.9;
			margin: 0;
		}

		.cb-verify-body {
			padding: 2rem;
		}

		.cb-info-grid {
			display: grid;
			gap: 1.5rem;
		}

		.cb-info-item {
			display: flex;
			flex-direction: column;
		}

		.cb-info-label {
			font-size: 0.75rem;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: var(--cb-text-light);
			margin-bottom: 0.25rem;
		}

		.cb-info-value {
			font-size: 1rem;
			color: var(--cb-text);
		}

		.cb-cert-id {
			font-family: 'Monaco', 'Menlo', monospace;
			font-size: 1.25rem;
			font-weight: 600;
			color: var(--cb-primary);
		}

		.cb-verify-footer {
			padding: 1.5rem 2rem;
			background: var(--cb-bg);
			border-top: 1px solid var(--cb-border);
			text-align: center;
		}

		.cb-verify-footer a {
			color: var(--cb-primary);
			text-decoration: none;
		}

		.cb-verify-footer a:hover {
			text-decoration: underline;
		}

		.cb-site-badge {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			font-size: 0.875rem;
			color: var(--cb-text-light);
		}

		.cb-download-btn {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			background: var(--cb-primary);
			color: white;
			padding: 0.75rem 1.5rem;
			border-radius: 6px;
			text-decoration: none;
			font-weight: 500;
			margin-top: 1rem;
			transition: background 0.2s;
		}

		.cb-download-btn:hover {
			background: #2563eb;
			color: white;
		}

		@media (max-width: 640px) {
			.cb-verify-container {
				padding: 1rem;
			}

			.cb-verify-header,
			.cb-verify-body {
				padding: 1.5rem;
			}
		}
	</style>
</head>
<body>
	<div class="cb-verify-container">
		<div class="cb-verify-card">
			<div class="cb-verify-header <?php echo $valid ? 'cb-valid' : ( 'expired' === $status ? 'cb-expired' : 'cb-invalid' ); ?>">
				<div class="cb-status-icon">
					<?php if ( $valid ) : ?>
						✓
					<?php elseif ( 'expired' === $status ) : ?>
						⏰
					<?php else : ?>
						✕
					<?php endif; ?>
				</div>
				<h1 class="cb-status-title">
					<?php
					if ( $valid ) {
						esc_html_e( 'Certificate Verified', 'certbuilder-pro' );
					} elseif ( 'expired' === $status ) {
						esc_html_e( 'Certificate Expired', 'certbuilder-pro' );
					} elseif ( 'revoked' === $status ) {
						esc_html_e( 'Certificate Revoked', 'certbuilder-pro' );
					} else {
						esc_html_e( 'Certificate Not Found', 'certbuilder-pro' );
					}
					?>
				</h1>
				<p class="cb-status-message"><?php echo esc_html( $message ); ?></p>
			</div>

			<?php if ( $certificate ) : ?>
				<div class="cb-verify-body">
					<div class="cb-info-grid">
						<div class="cb-info-item">
							<span class="cb-info-label"><?php esc_html_e( 'Certificate ID', 'certbuilder-pro' ); ?></span>
							<span class="cb-info-value cb-cert-id"><?php echo esc_html( $certificate['certificate_id'] ); ?></span>
						</div>

						<div class="cb-info-item">
							<span class="cb-info-label"><?php esc_html_e( 'Recipient', 'certbuilder-pro' ); ?></span>
							<span class="cb-info-value"><?php echo esc_html( $certificate['recipient']['name'] ); ?></span>
						</div>

						<div class="cb-info-item">
							<span class="cb-info-label"><?php esc_html_e( 'Achievement', 'certbuilder-pro' ); ?></span>
							<span class="cb-info-value"><?php echo esc_html( $certificate['achievement']['title'] ); ?></span>
						</div>

						<div class="cb-info-item">
							<span class="cb-info-label"><?php esc_html_e( 'Issue Date', 'certbuilder-pro' ); ?></span>
							<span class="cb-info-value">
								<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $certificate['issued_at'] ) ) ); ?>
							</span>
						</div>

						<?php if ( $certificate['expires_at'] ) : ?>
							<div class="cb-info-item">
								<span class="cb-info-label"><?php esc_html_e( 'Expiry Date', 'certbuilder-pro' ); ?></span>
								<span class="cb-info-value">
									<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $certificate['expires_at'] ) ) ); ?>
								</span>
							</div>
						<?php endif; ?>

						<div class="cb-info-item">
							<span class="cb-info-label"><?php esc_html_e( 'Issued By', 'certbuilder-pro' ); ?></span>
							<span class="cb-info-value"><?php bloginfo( 'name' ); ?></span>
						</div>
					</div>

					<?php if ( $valid && is_user_logged_in() && get_current_user_id() === (int) $certificate['recipient']['id'] ) : ?>
						<?php
						// Get certificate record for download link.
						$cert_record = certbuilder()->certificate()->get_by_verification_code( $verification_code );
						if ( $cert_record ) :
							$download_url = add_query_arg(
								[
									'certbuilder' => 1,
									'template'    => $cert_record->template_id,
									'object'      => $cert_record->object_id,
									'user'        => $cert_record->user_id,
									'connector'   => $cert_record->connector,
									'nonce'       => wp_create_nonce( 'certbuilder_' . $cert_record->user_id . '_' . $cert_record->object_id ),
									'download'    => 1,
								],
								home_url( '/certificate/' )
							);
							?>
							<div style="text-align: center;">
								<a href="<?php echo esc_url( $download_url ); ?>" class="cb-download-btn">
									📄 <?php esc_html_e( 'Download PDF', 'certbuilder-pro' ); ?>
								</a>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="cb-verify-footer">
				<div class="cb-site-badge">
					<?php esc_html_e( 'Verified by', 'certbuilder-pro' ); ?>
					<a href="<?php echo esc_url( home_url() ); ?>"><?php bloginfo( 'name' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
