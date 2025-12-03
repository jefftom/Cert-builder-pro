<?php
/**
 * Manual connector for issuing certificates without an LMS.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Connectors;

/**
 * Class ManualConnector
 *
 * Allows manual certificate issuance without an LMS.
 */
class ManualConnector extends ConnectorBase {

	/**
	 * Get connector ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'manual';
	}

	/**
	 * Get connector name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return __( 'Manual', 'certbuilder-pro' );
	}

	/**
	 * Always available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * Get certificate objects.
	 *
	 * @return array
	 */
	public function get_certificate_objects(): array {
		return [
			'manual' => __( 'Manual Certificate', 'certbuilder-pro' ),
		];
	}

	/**
	 * Get dynamic fields.
	 *
	 * @return array
	 */
	public function get_dynamic_fields(): array {
		return [
			'achievement_title' => [
				'label'       => __( 'Achievement Title', 'certbuilder-pro' ),
				'description' => __( 'Title of the achievement or course', 'certbuilder-pro' ),
				'group'       => 'achievement',
				'callback'    => [ $this, 'get_achievement_title' ],
			],
			'achievement_description' => [
				'label'       => __( 'Achievement Description', 'certbuilder-pro' ),
				'description' => __( 'Description of the achievement', 'certbuilder-pro' ),
				'group'       => 'achievement',
				'callback'    => [ $this, 'get_achievement_description' ],
			],
			'issuer_name' => [
				'label'       => __( 'Issuer Name', 'certbuilder-pro' ),
				'description' => __( 'Name of the certificate issuer', 'certbuilder-pro' ),
				'group'       => 'issuer',
				'callback'    => [ $this, 'get_issuer_name' ],
			],
			'issuer_title' => [
				'label'       => __( 'Issuer Title', 'certbuilder-pro' ),
				'description' => __( 'Title/position of the issuer', 'certbuilder-pro' ),
				'group'       => 'issuer',
				'callback'    => [ $this, 'get_issuer_title' ],
			],
			'custom_field_1' => [
				'label'       => __( 'Custom Field 1', 'certbuilder-pro' ),
				'description' => __( 'Custom field for additional data', 'certbuilder-pro' ),
				'group'       => 'custom',
				'callback'    => [ $this, 'get_custom_field_1' ],
			],
			'custom_field_2' => [
				'label'       => __( 'Custom Field 2', 'certbuilder-pro' ),
				'description' => __( 'Custom field for additional data', 'certbuilder-pro' ),
				'group'       => 'custom',
				'callback'    => [ $this, 'get_custom_field_2' ],
			],
			'custom_field_3' => [
				'label'       => __( 'Custom Field 3', 'certbuilder-pro' ),
				'description' => __( 'Custom field for additional data', 'certbuilder-pro' ),
				'group'       => 'custom',
				'callback'    => [ $this, 'get_custom_field_3' ],
			],
		];
	}

	/**
	 * Get field value.
	 *
	 * @param string $field Field key.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID (certificate record ID for manual).
	 * @return string
	 */
	public function get_field_value( string $field, int $user_id, int $object_id ): string {
		// For manual certificates, the field values are stored in certificate_data.
		$certificate = certbuilder()->certificate()->get( $object_id );

		if ( $certificate && isset( $certificate->certificate_data[ $field ] ) ) {
			return (string) $certificate->certificate_data[ $field ];
		}

		return '';
	}

	/**
	 * Manual certificates are always "earned" - they're issued by admin.
	 *
	 * @param int $user_id User ID.
	 * @param int $object_id Object ID.
	 * @return bool
	 */
	public function user_earned_certificate( int $user_id, int $object_id ): bool {
		return true;
	}

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		// Manual connector doesn't need any hooks.
		// Certificates are issued through the admin interface.
	}

	// Field callbacks - these get values from stored certificate data.

	/**
	 * Get achievement title.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_achievement_title( array $context ): string {
		return $context['achievement_title'] ?? '';
	}

	/**
	 * Get achievement description.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_achievement_description( array $context ): string {
		return $context['achievement_description'] ?? '';
	}

	/**
	 * Get issuer name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_issuer_name( array $context ): string {
		return $context['issuer_name'] ?? '';
	}

	/**
	 * Get issuer title.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_issuer_title( array $context ): string {
		return $context['issuer_title'] ?? '';
	}

	/**
	 * Get custom field 1.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_custom_field_1( array $context ): string {
		return $context['custom_field_1'] ?? '';
	}

	/**
	 * Get custom field 2.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_custom_field_2( array $context ): string {
		return $context['custom_field_2'] ?? '';
	}

	/**
	 * Get custom field 3.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_custom_field_3( array $context ): string {
		return $context['custom_field_3'] ?? '';
	}
}
