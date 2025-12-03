<?php
/**
 * Uninstall CertBuilder Pro
 *
 * Fired when the plugin is uninstalled.
 *
 * @package CertBuilder
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if we should delete data on uninstall.
$delete_data = get_option( 'certbuilder_delete_data_on_uninstall', false );

if ( ! $delete_data ) {
	return;
}

global $wpdb;

// Delete options.
$options = [
	'certbuilder_version',
	'certbuilder_db_version',
	'certbuilder_certificate_id_prefix',
	'certbuilder_certificate_id_year',
	'certbuilder_verification_slug',
	'certbuilder_verification_page_id',
	'certbuilder_default_expiration',
	'certbuilder_secret_key',
	'certbuilder_email_enabled',
	'certbuilder_email_subject',
	'certbuilder_delete_data_on_uninstall',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete custom table.
$table_name = $wpdb->prefix . 'certbuilder_certificates';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Delete template CPT posts.
$templates = get_posts(
	[
		'post_type'      => 'cb_template',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]
);

foreach ( $templates as $template_id ) {
	wp_delete_post( $template_id, true );
}

// Delete verification page.
$verification_page_id = get_option( 'certbuilder_verification_page_id' );
if ( $verification_page_id ) {
	wp_delete_post( $verification_page_id, true );
}

// Delete transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_certbuilder_%' OR option_name LIKE '%_transient_timeout_certbuilder_%'"
);

// Delete post meta.
$wpdb->query(
	"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_certbuilder_%' OR meta_key LIKE '_cb_%'"
);

// Clear any scheduled cron events.
$cron_events = [
	'certbuilder_daily_cleanup',
	'certbuilder_check_expirations',
];

foreach ( $cron_events as $event ) {
	$timestamp = wp_next_scheduled( $event );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, $event );
	}
}

// Flush rewrite rules.
flush_rewrite_rules();
