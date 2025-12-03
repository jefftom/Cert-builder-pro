<?php
/**
 * Public verification page.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Verification;

/**
 * Class PublicPage
 *
 * Handles the public certificate verification page.
 */
class PublicPage {

	/**
	 * Initialize public page.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'handle_verification_request' ] );
		add_shortcode( 'certbuilder_verify', [ $this, 'verification_shortcode' ] );
		add_shortcode( 'my_certificates', [ $this, 'my_certificates_shortcode' ] );
	}

	/**
	 * Register rewrite rules for verification URL.
	 */
	public function register_rewrite_rules(): void {
		$slug = get_option( 'certbuilder_verification_slug', 'verify' );

		add_rewrite_rule(
			'^' . $slug . '/([a-f0-9]+)/?$',
			'index.php?certbuilder_verify=$matches[1]',
			'top'
		);
	}

	/**
	 * Add query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'certbuilder_verify';
		return $vars;
	}

	/**
	 * Handle verification request.
	 */
	public function handle_verification_request(): void {
		$verification_code = get_query_var( 'certbuilder_verify' );

		if ( empty( $verification_code ) ) {
			return;
		}

		// Verify the certificate.
		$result = certbuilder()->verifier()->verify( $verification_code );

		// Track view if valid.
		if ( $result['valid'] ) {
			$certificate = certbuilder()->certificate()->get_by_verification_code( $verification_code );
			if ( $certificate ) {
				certbuilder()->certificate()->increment_view_count( $certificate->id );
			}
		}

		// Load the verification template.
		$this->render_verification_page( $result, $verification_code );
		exit;
	}

	/**
	 * Render verification page.
	 *
	 * @param array  $result Verification result.
	 * @param string $verification_code Verification code.
	 */
	private function render_verification_page( array $result, string $verification_code ): void {
		// Allow themes to override the template.
		$template = locate_template( 'certbuilder/verify.php' );

		if ( ! $template ) {
			$template = CERTBUILDER_PATH . 'public/views/verify.php';
		}

		// Enqueue styles.
		wp_enqueue_style(
			'certbuilder-verify',
			CERTBUILDER_URL . 'assets/css/verify.css',
			[],
			CERTBUILDER_VERSION
		);

		// Set up template variables.
		$valid       = $result['valid'];
		$status      = $result['status'];
		$message     = $result['message'];
		$certificate = $result['certificate'] ?? null;

		// Include the template.
		include $template;
	}

	/**
	 * Verification form shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function verification_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'title' => __( 'Verify Certificate', 'certbuilder-pro' ),
			],
			$atts,
			'certbuilder_verify'
		);

		// Handle form submission.
		$result = null;
		if ( isset( $_POST['certbuilder_verify_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['certbuilder_verify_nonce'] ), 'certbuilder_verify' ) ) {
			$code = isset( $_POST['verification_code'] ) ? sanitize_text_field( wp_unslash( $_POST['verification_code'] ) ) : '';

			if ( $code ) {
				// Try verification code first, then certificate ID.
				$result = certbuilder()->verifier()->verify( $code );
				if ( 'not_found' === $result['status'] ) {
					$result = certbuilder()->verifier()->verify_by_id( $code );
				}
			}
		}

		ob_start();
		?>
		<div class="certbuilder-verify-form">
			<?php if ( $atts['title'] ) : ?>
				<h3><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( $result ) : ?>
				<div class="certbuilder-verify-result certbuilder-status-<?php echo esc_attr( $result['status'] ); ?>">
					<?php if ( $result['valid'] ) : ?>
						<div class="certbuilder-valid">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php echo esc_html( $result['message'] ); ?>
						</div>
						<?php if ( ! empty( $result['certificate'] ) ) : ?>
							<div class="certbuilder-certificate-info">
								<p><strong><?php esc_html_e( 'Certificate ID:', 'certbuilder-pro' ); ?></strong> <?php echo esc_html( $result['certificate']['certificate_id'] ); ?></p>
								<p><strong><?php esc_html_e( 'Recipient:', 'certbuilder-pro' ); ?></strong> <?php echo esc_html( $result['certificate']['recipient']['name'] ); ?></p>
								<p><strong><?php esc_html_e( 'Achievement:', 'certbuilder-pro' ); ?></strong> <?php echo esc_html( $result['certificate']['achievement']['title'] ); ?></p>
								<p><strong><?php esc_html_e( 'Issued:', 'certbuilder-pro' ); ?></strong> <?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $result['certificate']['issued_at'] ) ) ); ?></p>
							</div>
						<?php endif; ?>
					<?php else : ?>
						<div class="certbuilder-invalid">
							<span class="dashicons dashicons-warning"></span>
							<?php echo esc_html( $result['message'] ); ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post" class="certbuilder-form">
				<?php wp_nonce_field( 'certbuilder_verify', 'certbuilder_verify_nonce' ); ?>
				<div class="certbuilder-form-field">
					<label for="verification_code"><?php esc_html_e( 'Certificate ID or Verification Code', 'certbuilder-pro' ); ?></label>
					<input type="text" name="verification_code" id="verification_code" placeholder="<?php esc_attr_e( 'e.g., CB-2025-00001', 'certbuilder-pro' ); ?>" required>
				</div>
				<button type="submit" class="certbuilder-button"><?php esc_html_e( 'Verify', 'certbuilder-pro' ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * My certificates shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function my_certificates_shortcode( $atts ): string {
		$atts = shortcode_atts(
			[
				'title' => __( 'My Certificates', 'certbuilder-pro' ),
				'limit' => 20,
			],
			$atts,
			'my_certificates'
		);

		// Must be logged in.
		if ( ! is_user_logged_in() ) {
			return '<p class="certbuilder-login-required">' . esc_html__( 'Please log in to view your certificates.', 'certbuilder-pro' ) . '</p>';
		}

		$user_id      = get_current_user_id();
		$certificates = certbuilder()->certificate()->get_for_user( $user_id, [ 'limit' => (int) $atts['limit'] ] );

		ob_start();
		?>
		<div class="certbuilder-my-certificates">
			<?php if ( $atts['title'] ) : ?>
				<h3><?php echo esc_html( $atts['title'] ); ?></h3>
			<?php endif; ?>

			<?php if ( empty( $certificates ) ) : ?>
				<p class="certbuilder-no-certificates"><?php esc_html_e( 'You have not earned any certificates yet.', 'certbuilder-pro' ); ?></p>
			<?php else : ?>
				<div class="certbuilder-certificates-list">
					<?php foreach ( $certificates as $cert ) : ?>
						<?php
						$object_title = get_the_title( $cert->object_id );
						$verify_url   = certbuilder()->verifier()->get_verification_url( $cert->verification_code );
						$download_url = add_query_arg(
							[
								'certbuilder' => 1,
								'template'    => $cert->template_id,
								'object'      => $cert->object_id,
								'user'        => $cert->user_id,
								'connector'   => $cert->connector,
								'nonce'       => wp_create_nonce( 'certbuilder_' . $cert->user_id . '_' . $cert->object_id ),
								'download'    => 1,
							],
							home_url( '/certificate/' )
						);
						?>
						<div class="certbuilder-certificate-card">
							<div class="certbuilder-certificate-info">
								<h4><?php echo esc_html( $object_title ); ?></h4>
								<p class="certbuilder-certificate-id"><?php echo esc_html( $cert->certificate_id ); ?></p>
								<p class="certbuilder-certificate-date">
									<?php
									printf(
										/* translators: %s: Issue date */
										esc_html__( 'Issued: %s', 'certbuilder-pro' ),
										esc_html( wp_date( get_option( 'date_format' ), strtotime( $cert->issued_at ) ) )
									);
									?>
								</p>
								<?php if ( 'revoked' === $cert->status ) : ?>
									<span class="certbuilder-status-badge certbuilder-status-revoked"><?php esc_html_e( 'Revoked', 'certbuilder-pro' ); ?></span>
								<?php elseif ( $cert->expires_at && strtotime( $cert->expires_at ) < time() ) : ?>
									<span class="certbuilder-status-badge certbuilder-status-expired"><?php esc_html_e( 'Expired', 'certbuilder-pro' ); ?></span>
								<?php else : ?>
									<span class="certbuilder-status-badge certbuilder-status-active"><?php esc_html_e( 'Active', 'certbuilder-pro' ); ?></span>
								<?php endif; ?>
							</div>
							<div class="certbuilder-certificate-actions">
								<a href="<?php echo esc_url( $download_url ); ?>" class="certbuilder-button certbuilder-button-primary">
									<?php esc_html_e( 'Download PDF', 'certbuilder-pro' ); ?>
								</a>
								<a href="<?php echo esc_url( $verify_url ); ?>" class="certbuilder-button certbuilder-button-secondary" target="_blank">
									<?php esc_html_e( 'Verify', 'certbuilder-pro' ); ?>
								</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
