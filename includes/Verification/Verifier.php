<?php
/**
 * Certificate verifier.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Verification;

/**
 * Class Verifier
 *
 * Handles certificate verification logic.
 */
class Verifier {

	/**
	 * Initialize verifier.
	 */
	public function init(): void {
		// Nothing to initialize for now.
	}

	/**
	 * Verify a certificate by verification code.
	 *
	 * @param string $verification_code Verification code.
	 * @return array Verification result.
	 */
	public function verify( string $verification_code ): array {
		$certificate = certbuilder()->certificate()->get_by_verification_code( $verification_code );

		if ( ! $certificate ) {
			return [
				'valid'   => false,
				'status'  => 'not_found',
				'message' => __( 'Certificate not found. Please check the verification code and try again.', 'certbuilder-pro' ),
			];
		}

		// Check status.
		if ( 'revoked' === $certificate->status ) {
			return [
				'valid'       => false,
				'status'      => 'revoked',
				'message'     => __( 'This certificate has been revoked.', 'certbuilder-pro' ),
				'certificate' => $this->format_certificate_data( $certificate ),
				'revoked_at'  => $certificate->revoked_at,
				'revoke_reason' => $certificate->revoke_reason,
			];
		}

		// Check expiration.
		if ( $certificate->expires_at && strtotime( $certificate->expires_at ) < time() ) {
			return [
				'valid'       => false,
				'status'      => 'expired',
				'message'     => __( 'This certificate has expired.', 'certbuilder-pro' ),
				'certificate' => $this->format_certificate_data( $certificate ),
				'expired_at'  => $certificate->expires_at,
			];
		}

		// Certificate is valid.
		return [
			'valid'       => true,
			'status'      => 'active',
			'message'     => __( 'This certificate is valid.', 'certbuilder-pro' ),
			'certificate' => $this->format_certificate_data( $certificate ),
		];
	}

	/**
	 * Verify a certificate by certificate ID.
	 *
	 * @param string $certificate_id Certificate ID (e.g., CB-2025-00001).
	 * @return array Verification result.
	 */
	public function verify_by_id( string $certificate_id ): array {
		$certificate = certbuilder()->certificate()->get_by_certificate_id( $certificate_id );

		if ( ! $certificate ) {
			return [
				'valid'   => false,
				'status'  => 'not_found',
				'message' => __( 'Certificate not found.', 'certbuilder-pro' ),
			];
		}

		return $this->verify( $certificate->verification_code );
	}

	/**
	 * Format certificate data for display.
	 *
	 * @param object $certificate Certificate record.
	 * @return array Formatted certificate data.
	 */
	private function format_certificate_data( object $certificate ): array {
		// Get user data.
		$user = get_userdata( $certificate->user_id );

		// Get object title.
		$object_title = get_the_title( $certificate->object_id );

		// Get connector name.
		$connector      = certbuilder()->connectors()->get( $certificate->connector );
		$connector_name = $connector ? $connector->get_name() : $certificate->connector;

		// Get template name.
		$template      = certbuilder()->template()->get( $certificate->template_id );
		$template_name = $template ? $template['title'] : '';

		return [
			'certificate_id' => $certificate->certificate_id,
			'recipient'      => [
				'name'  => $user ? $user->display_name : __( 'Unknown', 'certbuilder-pro' ),
				'email' => $user ? $user->user_email : '',
			],
			'achievement'    => [
				'title' => $object_title,
				'type'  => $certificate->object_type,
			],
			'connector'      => $connector_name,
			'template'       => $template_name,
			'issued_at'      => $certificate->issued_at,
			'expires_at'     => $certificate->expires_at,
			'custom_data'    => $certificate->certificate_data,
		];
	}

	/**
	 * Generate verification URL for a certificate.
	 *
	 * @param string $verification_code Verification code.
	 * @return string Verification URL.
	 */
	public function get_verification_url( string $verification_code ): string {
		$slug = get_option( 'certbuilder_verification_slug', 'verify' );

		return home_url( '/' . $slug . '/' . $verification_code );
	}

	/**
	 * Generate QR code data URL for a certificate.
	 *
	 * @param string $verification_code Verification code.
	 * @return string QR code data URL (base64 PNG).
	 */
	public function get_qr_code_data_url( string $verification_code ): string {
		$verify_url = $this->get_verification_url( $verification_code );

		// Use the PHP QR code library.
		if ( ! class_exists( '\chillerlan\QRCode\QRCode' ) ) {
			return '';
		}

		try {
			$options = new \chillerlan\QRCode\QROptions(
				[
					'outputType'   => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
					'eccLevel'     => \chillerlan\QRCode\QRCode::ECC_L,
					'imageBase64'  => true,
					'scale'        => 5,
					'quietzone'    => 2,
				]
			);

			$qrcode = new \chillerlan\QRCode\QRCode( $options );

			return $qrcode->render( $verify_url );
		} catch ( \Exception $e ) {
			return '';
		}
	}
}
