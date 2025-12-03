<?php
/**
 * Plugin activator.
 *
 * @package CertBuilder
 */

namespace CertBuilder;

use CertBuilder\Database\Schema;

/**
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * Creates database tables, sets default options, and schedules cron jobs.
	 */
	public static function activate(): void {
		// Create database tables.
		self::create_tables();

		// Set default options.
		self::set_default_options();

		// Create verification page.
		self::create_verification_page();

		// Set activation flag for welcome screen.
		set_transient( 'certbuilder_activation_redirect', true, 30 );

		// Store version for upgrade routines.
		update_option( 'certbuilder_version', CERTBUILDER_VERSION );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Create database tables.
	 */
	private static function create_tables(): void {
		$schema = new Schema();
		$schema->create_tables();
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options(): void {
		$defaults = [
			'certbuilder_certificate_id_prefix' => 'CB',
			'certbuilder_certificate_id_year'   => true,
			'certbuilder_verification_slug'     => 'verify',
			'certbuilder_default_expiration'    => 0, // 0 = never expires.
			'certbuilder_secret_key'            => wp_generate_password( 64, true, true ),
			'certbuilder_email_enabled'         => true,
			'certbuilder_email_subject'         => __( 'Your Certificate is Ready!', 'certbuilder-pro' ),
		];

		foreach ( $defaults as $option => $value ) {
			if ( false === get_option( $option ) ) {
				add_option( $option, $value );
			}
		}
	}

	/**
	 * Create the verification page.
	 */
	private static function create_verification_page(): void {
		$page_id = get_option( 'certbuilder_verification_page_id' );

		// Check if page already exists.
		if ( $page_id && get_post( $page_id ) ) {
			return;
		}

		// Create the page.
		$page_data = [
			'post_title'   => __( 'Certificate Verification', 'certbuilder-pro' ),
			'post_content' => '<!-- CertBuilder Pro Verification Page -->',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_name'    => 'certificate-verification',
		];

		$page_id = wp_insert_post( $page_data );

		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_option( 'certbuilder_verification_page_id', $page_id );
		}
	}
}
