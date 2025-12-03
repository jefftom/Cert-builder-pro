<?php
/**
 * Settings management.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Admin;

/**
 * Class Settings
 *
 * Handles plugin settings.
 */
class Settings {

	/**
	 * Initialize settings.
	 */
	public function init(): void {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		// Certificate ID settings.
		register_setting(
			'certbuilder_settings',
			'certbuilder_certificate_id_prefix',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'CB',
			]
		);

		register_setting(
			'certbuilder_settings',
			'certbuilder_certificate_id_year',
			[
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			]
		);

		// Verification settings.
		register_setting(
			'certbuilder_settings',
			'certbuilder_verification_slug',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_title',
				'default'           => 'verify',
			]
		);

		// Expiration settings.
		register_setting(
			'certbuilder_settings',
			'certbuilder_default_expiration',
			[
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			]
		);

		// Email settings.
		register_setting(
			'certbuilder_settings',
			'certbuilder_email_enabled',
			[
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			]
		);

		register_setting(
			'certbuilder_settings',
			'certbuilder_email_subject',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => __( 'Your Certificate is Ready!', 'certbuilder-pro' ),
			]
		);

		// Add settings sections.
		add_settings_section(
			'certbuilder_certificate_settings',
			__( 'Certificate Settings', 'certbuilder-pro' ),
			[ $this, 'render_certificate_section' ],
			'certbuilder_settings'
		);

		add_settings_section(
			'certbuilder_verification_settings',
			__( 'Verification Settings', 'certbuilder-pro' ),
			[ $this, 'render_verification_section' ],
			'certbuilder_settings'
		);

		add_settings_section(
			'certbuilder_email_settings',
			__( 'Email Settings', 'certbuilder-pro' ),
			[ $this, 'render_email_section' ],
			'certbuilder_settings'
		);

		// Add settings fields.
		add_settings_field(
			'certbuilder_certificate_id_prefix',
			__( 'Certificate ID Prefix', 'certbuilder-pro' ),
			[ $this, 'render_prefix_field' ],
			'certbuilder_settings',
			'certbuilder_certificate_settings'
		);

		add_settings_field(
			'certbuilder_certificate_id_year',
			__( 'Include Year', 'certbuilder-pro' ),
			[ $this, 'render_year_field' ],
			'certbuilder_settings',
			'certbuilder_certificate_settings'
		);

		add_settings_field(
			'certbuilder_default_expiration',
			__( 'Default Expiration', 'certbuilder-pro' ),
			[ $this, 'render_expiration_field' ],
			'certbuilder_settings',
			'certbuilder_certificate_settings'
		);

		add_settings_field(
			'certbuilder_verification_slug',
			__( 'Verification URL Slug', 'certbuilder-pro' ),
			[ $this, 'render_slug_field' ],
			'certbuilder_settings',
			'certbuilder_verification_settings'
		);

		add_settings_field(
			'certbuilder_email_enabled',
			__( 'Email Notifications', 'certbuilder-pro' ),
			[ $this, 'render_email_enabled_field' ],
			'certbuilder_settings',
			'certbuilder_email_settings'
		);

		add_settings_field(
			'certbuilder_email_subject',
			__( 'Email Subject', 'certbuilder-pro' ),
			[ $this, 'render_email_subject_field' ],
			'certbuilder_settings',
			'certbuilder_email_settings'
		);
	}

	/**
	 * Render certificate section description.
	 */
	public function render_certificate_section(): void {
		echo '<p>' . esc_html__( 'Configure how certificate IDs are generated and certificate defaults.', 'certbuilder-pro' ) . '</p>';
	}

	/**
	 * Render verification section description.
	 */
	public function render_verification_section(): void {
		echo '<p>' . esc_html__( 'Configure the public verification page settings.', 'certbuilder-pro' ) . '</p>';
	}

	/**
	 * Render email section description.
	 */
	public function render_email_section(): void {
		echo '<p>' . esc_html__( 'Configure email notifications sent when certificates are issued.', 'certbuilder-pro' ) . '</p>';
	}

	/**
	 * Render prefix field.
	 */
	public function render_prefix_field(): void {
		$value = get_option( 'certbuilder_certificate_id_prefix', 'CB' );
		?>
		<input type="text" name="certbuilder_certificate_id_prefix" value="<?php echo esc_attr( $value ); ?>" class="regular-text" maxlength="10">
		<p class="description">
			<?php esc_html_e( 'Prefix for certificate IDs (e.g., CB, CERT, DIPLOMA).', 'certbuilder-pro' ); ?>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: Example certificate ID */
				esc_html__( 'Example: %s', 'certbuilder-pro' ),
				'<code>' . esc_html( $value ) . '-2025-00001</code>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render year field.
	 */
	public function render_year_field(): void {
		$value = get_option( 'certbuilder_certificate_id_year', true );
		?>
		<label>
			<input type="checkbox" name="certbuilder_certificate_id_year" value="1" <?php checked( $value ); ?>>
			<?php esc_html_e( 'Include year in certificate ID (e.g., CB-2025-00001 vs CB-00001)', 'certbuilder-pro' ); ?>
		</label>
		<?php
	}

	/**
	 * Render expiration field.
	 */
	public function render_expiration_field(): void {
		$value = get_option( 'certbuilder_default_expiration', 0 );
		?>
		<input type="number" name="certbuilder_default_expiration" value="<?php echo esc_attr( $value ); ?>" class="small-text" min="0">
		<?php esc_html_e( 'days', 'certbuilder-pro' ); ?>
		<p class="description">
			<?php esc_html_e( 'Default number of days until certificates expire. Set to 0 for certificates that never expire.', 'certbuilder-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Render slug field.
	 */
	public function render_slug_field(): void {
		$value = get_option( 'certbuilder_verification_slug', 'verify' );
		?>
		<input type="text" name="certbuilder_verification_slug" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
		<p class="description">
			<?php
			printf(
				/* translators: %s: Example verification URL */
				esc_html__( 'Verification URL: %s', 'certbuilder-pro' ),
				'<code>' . esc_url( home_url( '/' . $value . '/abc123...' ) ) . '</code>'
			);
			?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Note: After changing this, go to Settings → Permalinks and click Save to refresh rewrite rules.', 'certbuilder-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Render email enabled field.
	 */
	public function render_email_enabled_field(): void {
		$value = get_option( 'certbuilder_email_enabled', true );
		?>
		<label>
			<input type="checkbox" name="certbuilder_email_enabled" value="1" <?php checked( $value ); ?>>
			<?php esc_html_e( 'Send email notification when a certificate is issued', 'certbuilder-pro' ); ?>
		</label>
		<?php
	}

	/**
	 * Render email subject field.
	 */
	public function render_email_subject_field(): void {
		$value = get_option( 'certbuilder_email_subject', __( 'Your Certificate is Ready!', 'certbuilder-pro' ) );
		?>
		<input type="text" name="certbuilder_email_subject" value="<?php echo esc_attr( $value ); ?>" class="large-text">
		<p class="description">
			<?php esc_html_e( 'Subject line for certificate notification emails.', 'certbuilder-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Get all settings.
	 *
	 * @return array Settings array.
	 */
	public function get_all(): array {
		return [
			'certificate_id_prefix' => get_option( 'certbuilder_certificate_id_prefix', 'CB' ),
			'certificate_id_year'   => get_option( 'certbuilder_certificate_id_year', true ),
			'verification_slug'     => get_option( 'certbuilder_verification_slug', 'verify' ),
			'default_expiration'    => get_option( 'certbuilder_default_expiration', 0 ),
			'email_enabled'         => get_option( 'certbuilder_email_enabled', true ),
			'email_subject'         => get_option( 'certbuilder_email_subject', __( 'Your Certificate is Ready!', 'certbuilder-pro' ) ),
		];
	}
}
