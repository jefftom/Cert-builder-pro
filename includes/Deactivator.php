<?php
/**
 * Plugin deactivator.
 *
 * @package CertBuilder
 */

namespace CertBuilder;

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Cleans up temporary data but preserves user data and settings.
	 */
	public static function deactivate(): void {
		// Clear any scheduled cron jobs.
		self::clear_scheduled_events();

		// Clear transients.
		self::clear_transients();

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Clear scheduled cron events.
	 */
	private static function clear_scheduled_events(): void {
		$events = [
			'certbuilder_daily_cleanup',
			'certbuilder_check_expirations',
		];

		foreach ( $events as $event ) {
			$timestamp = wp_next_scheduled( $event );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event );
			}
		}
	}

	/**
	 * Clear plugin transients.
	 */
	private static function clear_transients(): void {
		global $wpdb;

		// Delete all certbuilder transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'%_transient_certbuilder_%',
				'%_transient_timeout_certbuilder_%'
			)
		);
	}
}
