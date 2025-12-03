<?php
/**
 * Certificate renderer for display/download.
 *
 * @package CertBuilder
 */

namespace CertBuilder\PDF;

/**
 * Class Renderer
 *
 * Handles certificate rendering and download.
 */
class Renderer {

	/**
	 * Initialize renderer.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_action( 'template_redirect', [ $this, 'handle_certificate_request' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
	}

	/**
	 * Register rewrite rules.
	 */
	public function register_rewrite_rules(): void {
		add_rewrite_rule(
			'^certificate/?$',
			'index.php?certbuilder_certificate=1',
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
		$vars[] = 'certbuilder_certificate';
		return $vars;
	}

	/**
	 * Handle certificate request.
	 */
	public function handle_certificate_request(): void {
		// Check for certificate request via query string.
		if ( ! isset( $_GET['certbuilder'] ) || '1' !== $_GET['certbuilder'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Get and validate parameters.
		$template_id  = isset( $_GET['template'] ) ? absint( $_GET['template'] ) : 0;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$object_id    = isset( $_GET['object'] ) ? absint( $_GET['object'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$user_id      = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$connector_id = isset( $_GET['connector'] ) ? sanitize_key( $_GET['connector'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$nonce        = isset( $_GET['nonce'] ) ? sanitize_key( $_GET['nonce'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$download     = isset( $_GET['download'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Validate required parameters.
		if ( ! $template_id || ! $object_id || ! $user_id || ! $connector_id ) {
			wp_die( esc_html__( 'Invalid certificate request.', 'certbuilder-pro' ), 400 );
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'certbuilder_' . $user_id . '_' . $object_id ) ) {
			wp_die( esc_html__( 'Invalid or expired certificate link.', 'certbuilder-pro' ), 403 );
		}

		// Check if user can view this certificate.
		if ( ! $this->can_view_certificate( $user_id ) ) {
			wp_die( esc_html__( 'You do not have permission to view this certificate.', 'certbuilder-pro' ), 403 );
		}

		// Check if user earned the certificate.
		$connector = certbuilder()->connectors()->get( $connector_id );
		if ( ! $connector || ! $connector->user_earned_certificate( $user_id, $object_id ) ) {
			wp_die( esc_html__( 'Certificate not earned yet.', 'certbuilder-pro' ), 403 );
		}

		// Check if certificate already exists, if not create it.
		$certificate = certbuilder()->certificate()->get_for_user_object( $user_id, $object_id, get_post_type( $object_id ) );

		$extra_data = [];
		if ( $certificate ) {
			// Add certificate data to context.
			$extra_data = array_merge(
				$certificate->certificate_data ?? [],
				[
					'certificate_id'    => $certificate->certificate_id,
					'verification_code' => $certificate->verification_code,
					'issued_at'         => $certificate->issued_at,
					'expires_at'        => $certificate->expires_at,
				]
			);

			// Track download/view.
			if ( $download ) {
				certbuilder()->certificate()->increment_download_count( $certificate->id );
			} else {
				certbuilder()->certificate()->increment_view_count( $certificate->id );
			}
		} else {
			// Issue new certificate.
			$new_cert_id = $this->issue_new_certificate( $template_id, $user_id, $object_id, $connector_id );
			if ( $new_cert_id ) {
				$certificate = certbuilder()->certificate()->get( $new_cert_id );
				$extra_data  = array_merge(
					$certificate->certificate_data ?? [],
					[
						'certificate_id'    => $certificate->certificate_id,
						'verification_code' => $certificate->verification_code,
						'issued_at'         => $certificate->issued_at,
						'expires_at'        => $certificate->expires_at,
					]
				);
			}
		}

		// Generate PDF.
		try {
			$pdf_content = certbuilder()->pdf_generator()->generate(
				$template_id,
				$user_id,
				$object_id,
				$connector_id,
				$extra_data
			);
		} catch ( \Exception $e ) {
			wp_die( esc_html__( 'Error generating certificate.', 'certbuilder-pro' ) . ' ' . esc_html( $e->getMessage() ), 500 );
		}

		// Generate filename.
		$user     = get_userdata( $user_id );
		$username = $user ? sanitize_file_name( $user->display_name ) : 'certificate';
		$filename = 'certificate-' . $username . '-' . gmdate( 'Y-m-d' ) . '.pdf';

		// Set headers.
		header( 'Content-Type: application/pdf' );
		header( 'Content-Length: ' . strlen( $pdf_content ) );

		if ( $download ) {
			header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		} else {
			header( 'Content-Disposition: inline; filename="' . $filename . '"' );
		}

		header( 'Cache-Control: private, max-age=0, must-revalidate' );
		header( 'Pragma: public' );

		// Output PDF.
		echo $pdf_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Check if current user can view a certificate.
	 *
	 * @param int $certificate_user_id Certificate owner's user ID.
	 * @return bool Whether current user can view.
	 */
	private function can_view_certificate( int $certificate_user_id ): bool {
		// Admins can view any certificate.
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Users can view their own certificates.
		if ( get_current_user_id() === $certificate_user_id ) {
			return true;
		}

		/**
		 * Filter whether a user can view a certificate.
		 *
		 * @param bool $can_view Whether user can view.
		 * @param int  $certificate_user_id Certificate owner's ID.
		 */
		return apply_filters( 'certbuilder_can_view_certificate', false, $certificate_user_id );
	}

	/**
	 * Issue a new certificate.
	 *
	 * @param int    $template_id Template ID.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param string $connector_id Connector ID.
	 * @return int|false Certificate record ID or false.
	 */
	private function issue_new_certificate( int $template_id, int $user_id, int $object_id, string $connector_id ) {
		$connector = certbuilder()->connectors()->get( $connector_id );

		if ( ! $connector ) {
			return false;
		}

		// Get field values from connector.
		$field_values = [];
		foreach ( array_keys( $connector->get_dynamic_fields() ) as $field ) {
			$field_values[ $field ] = $connector->get_field_value( $field, $user_id, $object_id );
		}

		// Calculate expiration.
		$expires_at   = null;
		$default_days = (int) get_option( 'certbuilder_default_expiration', 0 );
		if ( $default_days > 0 ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( "+{$default_days} days" ) );
		}

		// Issue certificate.
		return certbuilder()->certificate()->issue(
			[
				'template_id'      => $template_id,
				'user_id'          => $user_id,
				'object_id'        => $object_id,
				'object_type'      => get_post_type( $object_id ),
				'connector'        => $connector_id,
				'certificate_data' => $field_values,
				'expires_at'       => $expires_at,
			]
		);
	}
}
