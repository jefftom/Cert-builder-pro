<?php
/**
 * Plugin Name: CertBuilder Pro
 * Plugin URI: https://certbuilderpro.com
 * Description: A modern visual certificate builder for WordPress LMS plugins. Replace LearnDash's certificate system with a drag-drop builder, QR verification, and beautiful templates.
 * Version: 1.0.0
 * Author: CertBuilder
 * Author URI: https://certbuilderpro.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: certbuilder-pro
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package CertBuilder
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version.
define( 'CERTBUILDER_VERSION', '1.0.0' );

// Plugin file.
define( 'CERTBUILDER_FILE', __FILE__ );

// Plugin directory path.
define( 'CERTBUILDER_PATH', plugin_dir_path( __FILE__ ) );

// Plugin directory URL.
define( 'CERTBUILDER_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename.
define( 'CERTBUILDER_BASENAME', plugin_basename( __FILE__ ) );

// Minimum PHP version.
define( 'CERTBUILDER_MIN_PHP', '7.4' );

// Minimum WordPress version.
define( 'CERTBUILDER_MIN_WP', '5.8' );

/**
 * Check plugin requirements before loading.
 *
 * @return bool True if requirements are met.
 */
function certbuilder_check_requirements() {
	$errors = [];

	// Check PHP version.
	if ( version_compare( PHP_VERSION, CERTBUILDER_MIN_PHP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Current PHP version, 2: Required PHP version */
			__( 'CertBuilder Pro requires PHP %2$s or higher. You are running PHP %1$s.', 'certbuilder-pro' ),
			PHP_VERSION,
			CERTBUILDER_MIN_PHP
		);
	}

	// Check WordPress version.
	global $wp_version;
	if ( version_compare( $wp_version, CERTBUILDER_MIN_WP, '<' ) ) {
		$errors[] = sprintf(
			/* translators: 1: Current WordPress version, 2: Required WordPress version */
			__( 'CertBuilder Pro requires WordPress %2$s or higher. You are running WordPress %1$s.', 'certbuilder-pro' ),
			$wp_version,
			CERTBUILDER_MIN_WP
		);
	}

	if ( ! empty( $errors ) ) {
		add_action(
			'admin_notices',
			function () use ( $errors ) {
				?>
				<div class="notice notice-error">
					<p><strong><?php esc_html_e( 'CertBuilder Pro', 'certbuilder-pro' ); ?></strong></p>
					<?php foreach ( $errors as $error ) : ?>
						<p><?php echo esc_html( $error ); ?></p>
					<?php endforeach; ?>
				</div>
				<?php
			}
		);
		return false;
	}

	return true;
}

/**
 * Load the Composer autoloader.
 *
 * @return bool True if autoloader loaded successfully.
 */
function certbuilder_load_autoloader() {
	$autoloader = CERTBUILDER_PATH . 'vendor/autoload.php';

	if ( ! file_exists( $autoloader ) ) {
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'CertBuilder Pro', 'certbuilder-pro' ); ?></strong>
					</p>
					<p>
						<?php esc_html_e( 'The Composer autoloader is missing. Please run "composer install" in the plugin directory.', 'certbuilder-pro' ); ?>
					</p>
				</div>
				<?php
			}
		);
		return false;
	}

	require_once $autoloader;
	return true;
}

/**
 * Plugin activation hook.
 */
function certbuilder_activate() {
	if ( ! certbuilder_check_requirements() ) {
		deactivate_plugins( CERTBUILDER_BASENAME );
		wp_die(
			esc_html__( 'CertBuilder Pro cannot be activated. Please check the plugin requirements.', 'certbuilder-pro' ),
			'Plugin Activation Error',
			[ 'back_link' => true ]
		);
	}

	if ( ! certbuilder_load_autoloader() ) {
		return;
	}

	CertBuilder\Activator::activate();
}

/**
 * Plugin deactivation hook.
 */
function certbuilder_deactivate() {
	if ( certbuilder_load_autoloader() ) {
		CertBuilder\Deactivator::deactivate();
	}
}

register_activation_hook( __FILE__, 'certbuilder_activate' );
register_deactivation_hook( __FILE__, 'certbuilder_deactivate' );

/**
 * Initialize the plugin.
 */
function certbuilder_init() {
	// Check requirements.
	if ( ! certbuilder_check_requirements() ) {
		return;
	}

	// Load autoloader.
	if ( ! certbuilder_load_autoloader() ) {
		return;
	}

	// Boot the plugin.
	CertBuilder\Plugin::instance()->init();
}

add_action( 'plugins_loaded', 'certbuilder_init' );

/**
 * Get the main plugin instance.
 *
 * @return CertBuilder\Plugin
 */
function certbuilder() {
	return CertBuilder\Plugin::instance();
}
