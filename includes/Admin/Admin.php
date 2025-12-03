<?php
/**
 * Admin functionality.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Admin;

/**
 * Class Admin
 *
 * Handles admin functionality.
 */
class Admin {

	/**
	 * Initialize admin.
	 */
	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_init', [ $this, 'handle_redirect' ] );
	}

	/**
	 * Register admin menu.
	 */
	public function register_menu(): void {
		// Main menu.
		add_menu_page(
			__( 'CertBuilder Pro', 'certbuilder-pro' ),
			__( 'CertBuilder', 'certbuilder-pro' ),
			'manage_options',
			'certbuilder',
			[ $this, 'render_dashboard' ],
			'dashicons-awards',
			30
		);

		// Dashboard submenu (same as main).
		add_submenu_page(
			'certbuilder',
			__( 'Dashboard', 'certbuilder-pro' ),
			__( 'Dashboard', 'certbuilder-pro' ),
			'manage_options',
			'certbuilder',
			[ $this, 'render_dashboard' ]
		);

		// Templates.
		add_submenu_page(
			'certbuilder',
			__( 'Templates', 'certbuilder-pro' ),
			__( 'Templates', 'certbuilder-pro' ),
			'manage_options',
			'certbuilder-templates',
			[ $this, 'render_templates' ]
		);

		// Builder.
		add_submenu_page(
			'certbuilder',
			__( 'Builder', 'certbuilder-pro' ),
			__( 'Builder', 'certbuilder-pro' ),
			'manage_options',
			'certbuilder-builder',
			[ $this, 'render_builder' ]
		);

		// Certificates log.
		add_submenu_page(
			'certbuilder',
			__( 'Certificates', 'certbuilder-pro' ),
			__( 'Certificates', 'certbuilder-pro' ),
			'manage_options',
			'certbuilder-certificates',
			[ $this, 'render_certificates' ]
		);

		// Settings.
		add_submenu_page(
			'certbuilder',
			__( 'Settings', 'certbuilder-pro' ),
			__( 'Settings', 'certbuilder-pro' ),
			'manage_options',
			'certbuilder-settings',
			[ $this, 'render_settings' ]
		);
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( string $hook ): void {
		// Only load on our pages.
		if ( strpos( $hook, 'certbuilder' ) === false ) {
			return;
		}

		// Admin styles.
		wp_enqueue_style(
			'certbuilder-admin',
			CERTBUILDER_URL . 'admin/assets/css/admin.css',
			[],
			CERTBUILDER_VERSION
		);

		// Admin scripts.
		wp_enqueue_script(
			'certbuilder-admin',
			CERTBUILDER_URL . 'admin/assets/js/admin.js',
			[ 'jquery' ],
			CERTBUILDER_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'certbuilder-admin',
			'certbuilderAdmin',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => rest_url( 'certbuilder/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'pluginUrl' => CERTBUILDER_URL,
				'strings'   => [
					'confirmDelete' => __( 'Are you sure you want to delete this?', 'certbuilder-pro' ),
					'confirmRevoke' => __( 'Are you sure you want to revoke this certificate?', 'certbuilder-pro' ),
					'loading'       => __( 'Loading...', 'certbuilder-pro' ),
					'error'         => __( 'An error occurred.', 'certbuilder-pro' ),
				],
			]
		);

		// Builder page specific.
		if ( strpos( $hook, 'certbuilder-builder' ) !== false ) {
			$this->enqueue_builder_scripts();
		}
	}

	/**
	 * Enqueue builder scripts.
	 */
	private function enqueue_builder_scripts(): void {
		// React app.
		$asset_file = CERTBUILDER_PATH . 'builder/dist/index.asset.php';

		if ( file_exists( $asset_file ) ) {
			$asset = include $asset_file;

			wp_enqueue_script(
				'certbuilder-builder',
				CERTBUILDER_URL . 'builder/dist/index.js',
				$asset['dependencies'] ?? [ 'wp-element', 'wp-components', 'wp-i18n' ],
				$asset['version'] ?? CERTBUILDER_VERSION,
				true
			);

			wp_enqueue_style(
				'certbuilder-builder',
				CERTBUILDER_URL . 'builder/dist/index.css',
				[ 'wp-components' ],
				$asset['version'] ?? CERTBUILDER_VERSION
			);
		}

		// Builder localization.
		wp_localize_script(
			'certbuilder-builder',
			'certbuilderBuilder',
			[
				'restUrl'     => rest_url( 'certbuilder/v1/' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'templateId'  => isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'fonts'       => certbuilder()->fonts()->get_for_builder(),
				'fields'      => certbuilder()->fields()->get_grouped(),
				'connectors'  => certbuilder()->connectors()->get_for_admin(),
				'previewUser' => get_current_user_id(),
			]
		);
	}

	/**
	 * Handle activation redirect.
	 */
	public function handle_redirect(): void {
		if ( get_transient( 'certbuilder_activation_redirect' ) ) {
			delete_transient( 'certbuilder_activation_redirect' );

			if ( ! isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_safe_redirect( admin_url( 'admin.php?page=certbuilder' ) );
				exit;
			}
		}
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard(): void {
		$stats = certbuilder()->certificate()->get_stats();
		$templates = certbuilder()->template()->get_all( [ 'posts_per_page' => 5 ] );
		$recent_certs = certbuilder()->certificate()->get_all( [ 'limit' => 5 ] );

		include CERTBUILDER_PATH . 'admin/views/dashboard.php';
	}

	/**
	 * Render templates page.
	 */
	public function render_templates(): void {
		$templates = certbuilder()->template()->get_all();

		include CERTBUILDER_PATH . 'admin/views/templates.php';
	}

	/**
	 * Render builder page.
	 */
	public function render_builder(): void {
		include CERTBUILDER_PATH . 'admin/views/builder.php';
	}

	/**
	 * Render certificates page.
	 */
	public function render_certificates(): void {
		// Handle bulk actions.
		$this->handle_certificate_actions();

		// Get pagination.
		$per_page = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$offset = ( $current_page - 1 ) * $per_page;

		// Get filters.
		$filters = [
			'status'    => isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'connector' => isset( $_GET['connector'] ) ? sanitize_key( $_GET['connector'] ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'limit'     => $per_page,
			'offset'    => $offset,
		];

		$result = certbuilder()->certificate()->get_all( $filters );
		$certificates = $result['certificates'];
		$total = $result['total'];
		$total_pages = ceil( $total / $per_page );

		$connectors = certbuilder()->connectors()->get_available();

		include CERTBUILDER_PATH . 'admin/views/certificates.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings(): void {
		include CERTBUILDER_PATH . 'admin/views/settings.php';
	}

	/**
	 * Handle certificate actions.
	 */
	private function handle_certificate_actions(): void {
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		$action = sanitize_key( $_GET['action'] );
		$nonce = sanitize_key( $_GET['_wpnonce'] );

		if ( ! wp_verify_nonce( $nonce, 'certbuilder_certificate_action' ) ) {
			return;
		}

		$cert_id = isset( $_GET['certificate'] ) ? absint( $_GET['certificate'] ) : 0;

		if ( ! $cert_id ) {
			return;
		}

		switch ( $action ) {
			case 'revoke':
				certbuilder()->certificate()->revoke( $cert_id );
				$redirect = add_query_arg(
					[
						'page'    => 'certbuilder-certificates',
						'message' => 'revoked',
					],
					admin_url( 'admin.php' )
				);
				break;

			case 'reinstate':
				certbuilder()->certificate()->reinstate( $cert_id );
				$redirect = add_query_arg(
					[
						'page'    => 'certbuilder-certificates',
						'message' => 'reinstated',
					],
					admin_url( 'admin.php' )
				);
				break;

			default:
				return;
		}

		wp_safe_redirect( $redirect );
		exit;
	}
}
