<?php
/**
 * Main plugin class.
 *
 * @package CertBuilder
 */

namespace CertBuilder;

use CertBuilder\Core\Template;
use CertBuilder\Core\Certificate;
use CertBuilder\Core\Fields;
use CertBuilder\Core\Fonts;
use CertBuilder\Connectors\ConnectorManager;
use CertBuilder\Builder\BuilderController;
use CertBuilder\PDF\Generator;
use CertBuilder\Verification\Verifier;
use CertBuilder\Verification\PublicPage;
use CertBuilder\Admin\Admin;
use CertBuilder\Admin\Settings;
use CertBuilder\API\RestController;

/**
 * Class Plugin
 *
 * Main plugin singleton class that bootstraps all functionality.
 */
class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Template manager instance.
	 *
	 * @var Template|null
	 */
	private ?Template $template = null;

	/**
	 * Certificate manager instance.
	 *
	 * @var Certificate|null
	 */
	private ?Certificate $certificate = null;

	/**
	 * Fields registry instance.
	 *
	 * @var Fields|null
	 */
	private ?Fields $fields = null;

	/**
	 * Fonts manager instance.
	 *
	 * @var Fonts|null
	 */
	private ?Fonts $fonts = null;

	/**
	 * Connector manager instance.
	 *
	 * @var ConnectorManager|null
	 */
	private ?ConnectorManager $connectors = null;

	/**
	 * Builder controller instance.
	 *
	 * @var BuilderController|null
	 */
	private ?BuilderController $builder = null;

	/**
	 * PDF generator instance.
	 *
	 * @var Generator|null
	 */
	private ?Generator $pdf_generator = null;

	/**
	 * Verifier instance.
	 *
	 * @var Verifier|null
	 */
	private ?Verifier $verifier = null;

	/**
	 * Admin instance.
	 *
	 * @var Admin|null
	 */
	private ?Admin $admin = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * REST controller instance.
	 *
	 * @var RestController|null
	 */
	private ?RestController $rest_controller = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception When trying to unserialize.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}

	/**
	 * Initialize the plugin.
	 */
	public function init(): void {
		// Load text domain.
		$this->load_textdomain();

		// Initialize core components.
		$this->init_core();

		// Initialize connectors.
		$this->init_connectors();

		// Initialize admin.
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Initialize public-facing functionality.
		$this->init_public();

		// Initialize REST API.
		$this->init_rest_api();

		// Fire action for other components to hook into.
		do_action( 'certbuilder_loaded', $this );
	}

	/**
	 * Load plugin text domain.
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'certbuilder-pro',
			false,
			dirname( CERTBUILDER_BASENAME ) . '/languages'
		);
	}

	/**
	 * Initialize core components.
	 */
	private function init_core(): void {
		$this->template    = new Template();
		$this->certificate = new Certificate();
		$this->fields      = new Fields();
		$this->fonts       = new Fonts();

		$this->template->init();
		$this->certificate->init();
		$this->fields->init();
		$this->fonts->init();
	}

	/**
	 * Initialize connector system.
	 */
	private function init_connectors(): void {
		$this->connectors = new ConnectorManager();
		$this->connectors->init();
	}

	/**
	 * Initialize admin components.
	 */
	private function init_admin(): void {
		$this->admin    = new Admin();
		$this->settings = new Settings();
		$this->builder  = new BuilderController();

		$this->admin->init();
		$this->settings->init();
		$this->builder->init();
	}

	/**
	 * Initialize public-facing components.
	 */
	private function init_public(): void {
		$this->verifier      = new Verifier();
		$this->pdf_generator = new Generator();

		$public_page = new PublicPage();

		$this->verifier->init();
		$public_page->init();
	}

	/**
	 * Initialize REST API.
	 */
	private function init_rest_api(): void {
		$this->rest_controller = new RestController();
		$this->rest_controller->init();
	}

	/**
	 * Get template manager.
	 *
	 * @return Template
	 */
	public function template(): Template {
		return $this->template;
	}

	/**
	 * Get certificate manager.
	 *
	 * @return Certificate
	 */
	public function certificate(): Certificate {
		return $this->certificate;
	}

	/**
	 * Get fields registry.
	 *
	 * @return Fields
	 */
	public function fields(): Fields {
		return $this->fields;
	}

	/**
	 * Get fonts manager.
	 *
	 * @return Fonts
	 */
	public function fonts(): Fonts {
		return $this->fonts;
	}

	/**
	 * Get connector manager.
	 *
	 * @return ConnectorManager
	 */
	public function connectors(): ConnectorManager {
		return $this->connectors;
	}

	/**
	 * Get PDF generator.
	 *
	 * @return Generator
	 */
	public function pdf_generator(): Generator {
		return $this->pdf_generator;
	}

	/**
	 * Get verifier.
	 *
	 * @return Verifier
	 */
	public function verifier(): Verifier {
		return $this->verifier;
	}

	/**
	 * Get settings.
	 *
	 * @return Settings|null
	 */
	public function settings(): ?Settings {
		return $this->settings;
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function version(): string {
		return CERTBUILDER_VERSION;
	}

	/**
	 * Get plugin path.
	 *
	 * @param string $path Optional path to append.
	 * @return string
	 */
	public function path( string $path = '' ): string {
		return CERTBUILDER_PATH . ltrim( $path, '/' );
	}

	/**
	 * Get plugin URL.
	 *
	 * @param string $path Optional path to append.
	 * @return string
	 */
	public function url( string $path = '' ): string {
		return CERTBUILDER_URL . ltrim( $path, '/' );
	}
}
