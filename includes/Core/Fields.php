<?php
/**
 * Dynamic fields registry.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Core;

/**
 * Class Fields
 *
 * Registry for dynamic fields available in certificate templates.
 */
class Fields {

	/**
	 * Registered fields.
	 *
	 * @var array
	 */
	private array $fields = [];

	/**
	 * Initialize fields registry.
	 */
	public function init(): void {
		$this->register_core_fields();

		/**
		 * Fires after core fields are registered.
		 *
		 * Use this hook to register additional dynamic fields.
		 *
		 * @param Fields $fields Fields instance.
		 */
		do_action( 'certbuilder_register_fields', $this );
	}

	/**
	 * Register core fields available to all connectors.
	 */
	private function register_core_fields(): void {
		// Certificate fields.
		$this->register(
			'certificate_id',
			[
				'label'       => __( 'Certificate ID', 'certbuilder-pro' ),
				'description' => __( 'Unique certificate identifier (e.g., CB-2025-00001)', 'certbuilder-pro' ),
				'group'       => 'certificate',
				'callback'    => [ $this, 'get_certificate_id' ],
			]
		);

		$this->register(
			'verify_url',
			[
				'label'       => __( 'Verification URL', 'certbuilder-pro' ),
				'description' => __( 'Public URL to verify this certificate', 'certbuilder-pro' ),
				'group'       => 'certificate',
				'callback'    => [ $this, 'get_verify_url' ],
			]
		);

		$this->register(
			'qr_code',
			[
				'label'       => __( 'QR Code', 'certbuilder-pro' ),
				'description' => __( 'QR code image linking to verification page', 'certbuilder-pro' ),
				'group'       => 'certificate',
				'type'        => 'image',
				'callback'    => [ $this, 'get_qr_code' ],
			]
		);

		$this->register(
			'issue_date',
			[
				'label'       => __( 'Issue Date', 'certbuilder-pro' ),
				'description' => __( 'Date the certificate was issued', 'certbuilder-pro' ),
				'group'       => 'certificate',
				'callback'    => [ $this, 'get_issue_date' ],
			]
		);

		$this->register(
			'expiry_date',
			[
				'label'       => __( 'Expiry Date', 'certbuilder-pro' ),
				'description' => __( 'Date the certificate expires (if applicable)', 'certbuilder-pro' ),
				'group'       => 'certificate',
				'callback'    => [ $this, 'get_expiry_date' ],
			]
		);

		// User fields.
		$this->register(
			'student_name',
			[
				'label'       => __( 'Student Name', 'certbuilder-pro' ),
				'description' => __( 'Display name of the certificate recipient', 'certbuilder-pro' ),
				'group'       => 'user',
				'callback'    => [ $this, 'get_student_name' ],
			]
		);

		$this->register(
			'student_first',
			[
				'label'       => __( 'First Name', 'certbuilder-pro' ),
				'description' => __( 'First name of the recipient', 'certbuilder-pro' ),
				'group'       => 'user',
				'callback'    => [ $this, 'get_student_first' ],
			]
		);

		$this->register(
			'student_last',
			[
				'label'       => __( 'Last Name', 'certbuilder-pro' ),
				'description' => __( 'Last name of the recipient', 'certbuilder-pro' ),
				'group'       => 'user',
				'callback'    => [ $this, 'get_student_last' ],
			]
		);

		$this->register(
			'student_email',
			[
				'label'       => __( 'Student Email', 'certbuilder-pro' ),
				'description' => __( 'Email address of the recipient', 'certbuilder-pro' ),
				'group'       => 'user',
				'callback'    => [ $this, 'get_student_email' ],
			]
		);

		// Site fields.
		$this->register(
			'site_name',
			[
				'label'       => __( 'Site Name', 'certbuilder-pro' ),
				'description' => __( 'Name of the WordPress site', 'certbuilder-pro' ),
				'group'       => 'site',
				'callback'    => [ $this, 'get_site_name' ],
			]
		);

		$this->register(
			'site_url',
			[
				'label'       => __( 'Site URL', 'certbuilder-pro' ),
				'description' => __( 'URL of the WordPress site', 'certbuilder-pro' ),
				'group'       => 'site',
				'callback'    => [ $this, 'get_site_url' ],
			]
		);

		$this->register(
			'current_date',
			[
				'label'       => __( 'Current Date', 'certbuilder-pro' ),
				'description' => __( "Today's date", 'certbuilder-pro' ),
				'group'       => 'site',
				'callback'    => [ $this, 'get_current_date' ],
			]
		);
	}

	/**
	 * Register a dynamic field.
	 *
	 * @param string $key Field key.
	 * @param array  $args Field arguments.
	 */
	public function register( string $key, array $args ): void {
		$defaults = [
			'label'       => $key,
			'description' => '',
			'group'       => 'general',
			'type'        => 'text',
			'callback'    => null,
			'connector'   => null, // null = available to all.
		];

		$this->fields[ $key ] = array_merge( $defaults, $args );
	}

	/**
	 * Get a registered field.
	 *
	 * @param string $key Field key.
	 * @return array|null Field definition or null.
	 */
	public function get( string $key ): ?array {
		return $this->fields[ $key ] ?? null;
	}

	/**
	 * Get all registered fields.
	 *
	 * @param string|null $connector Filter by connector.
	 * @return array Array of fields.
	 */
	public function get_all( ?string $connector = null ): array {
		if ( null === $connector ) {
			return $this->fields;
		}

		return array_filter(
			$this->fields,
			function ( $field ) use ( $connector ) {
				return null === $field['connector'] || $field['connector'] === $connector;
			}
		);
	}

	/**
	 * Get fields grouped by group name.
	 *
	 * @param string|null $connector Filter by connector.
	 * @return array Grouped fields.
	 */
	public function get_grouped( ?string $connector = null ): array {
		$fields  = $this->get_all( $connector );
		$grouped = [];

		foreach ( $fields as $key => $field ) {
			$group = $field['group'];

			if ( ! isset( $grouped[ $group ] ) ) {
				$grouped[ $group ] = [];
			}

			$grouped[ $group ][ $key ] = $field;
		}

		return $grouped;
	}

	/**
	 * Resolve a field value.
	 *
	 * @param string $key Field key.
	 * @param array  $context Context data.
	 * @return string Resolved value.
	 */
	public function resolve( string $key, array $context ): string {
		$field = $this->get( $key );

		if ( ! $field || ! is_callable( $field['callback'] ) ) {
			return '';
		}

		return (string) call_user_func( $field['callback'], $context );
	}

	/**
	 * Resolve all fields for a context.
	 *
	 * @param array $context Context data.
	 * @return array Resolved field values.
	 */
	public function resolve_all( array $context ): array {
		$resolved = [];

		foreach ( $this->fields as $key => $field ) {
			$resolved[ $key ] = $this->resolve( $key, $context );
		}

		return $resolved;
	}

	// Core field callbacks.

	/**
	 * Get certificate ID.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_certificate_id( array $context ): string {
		return $context['certificate_id'] ?? '';
	}

	/**
	 * Get verification URL.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_verify_url( array $context ): string {
		if ( empty( $context['verification_code'] ) ) {
			return '';
		}

		$slug = get_option( 'certbuilder_verification_slug', 'verify' );

		return home_url( '/' . $slug . '/' . $context['verification_code'] );
	}

	/**
	 * Get QR code.
	 *
	 * @param array $context Context data.
	 * @return string QR code path or data.
	 */
	public function get_qr_code( array $context ): string {
		// QR code generation is handled by the PDF generator.
		return $context['qr_code'] ?? '';
	}

	/**
	 * Get issue date.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_issue_date( array $context ): string {
		if ( empty( $context['issued_at'] ) ) {
			return '';
		}

		$format = get_option( 'date_format' );

		return wp_date( $format, strtotime( $context['issued_at'] ) );
	}

	/**
	 * Get expiry date.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_expiry_date( array $context ): string {
		if ( empty( $context['expires_at'] ) ) {
			return __( 'Never', 'certbuilder-pro' );
		}

		$format = get_option( 'date_format' );

		return wp_date( $format, strtotime( $context['expires_at'] ) );
	}

	/**
	 * Get student name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_student_name( array $context ): string {
		if ( empty( $context['user_id'] ) ) {
			return '';
		}

		$user = get_userdata( $context['user_id'] );

		return $user ? $user->display_name : '';
	}

	/**
	 * Get student first name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_student_first( array $context ): string {
		if ( empty( $context['user_id'] ) ) {
			return '';
		}

		$user = get_userdata( $context['user_id'] );

		return $user ? $user->first_name : '';
	}

	/**
	 * Get student last name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_student_last( array $context ): string {
		if ( empty( $context['user_id'] ) ) {
			return '';
		}

		$user = get_userdata( $context['user_id'] );

		return $user ? $user->last_name : '';
	}

	/**
	 * Get student email.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_student_email( array $context ): string {
		if ( empty( $context['user_id'] ) ) {
			return '';
		}

		$user = get_userdata( $context['user_id'] );

		return $user ? $user->user_email : '';
	}

	/**
	 * Get site name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_site_name( array $context ): string {
		return get_bloginfo( 'name' );
	}

	/**
	 * Get site URL.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_site_url( array $context ): string {
		return home_url();
	}

	/**
	 * Get current date.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_current_date( array $context ): string {
		$format = get_option( 'date_format' );

		return wp_date( $format );
	}
}
