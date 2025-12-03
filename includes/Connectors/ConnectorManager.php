<?php
/**
 * Connector manager.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Connectors;

/**
 * Class ConnectorManager
 *
 * Manages LMS connectors.
 */
class ConnectorManager {

	/**
	 * Registered connectors.
	 *
	 * @var array<string, ConnectorInterface>
	 */
	private array $connectors = [];

	/**
	 * Initialize the connector manager.
	 */
	public function init(): void {
		// Register built-in connectors.
		$this->register_builtin_connectors();

		/**
		 * Fires after built-in connectors are registered.
		 *
		 * Use this hook to register custom connectors.
		 *
		 * @param ConnectorManager $manager Connector manager instance.
		 */
		do_action( 'certbuilder_register_connectors', $this );

		// Initialize available connectors.
		$this->init_available_connectors();
	}

	/**
	 * Register built-in connectors.
	 */
	private function register_builtin_connectors(): void {
		// LearnDash connector.
		$this->register( new LearnDashConnector() );

		// Manual connector (always available).
		$this->register( new ManualConnector() );
	}

	/**
	 * Initialize connectors that are available.
	 */
	private function init_available_connectors(): void {
		foreach ( $this->connectors as $connector ) {
			if ( $connector->is_available() ) {
				$connector->register_hooks();
			}
		}
	}

	/**
	 * Register a connector.
	 *
	 * @param ConnectorInterface $connector Connector instance.
	 */
	public function register( ConnectorInterface $connector ): void {
		$this->connectors[ $connector->get_id() ] = $connector;
	}

	/**
	 * Get a connector by ID.
	 *
	 * @param string $id Connector ID.
	 * @return ConnectorInterface|null Connector or null.
	 */
	public function get( string $id ): ?ConnectorInterface {
		return $this->connectors[ $id ] ?? null;
	}

	/**
	 * Get all registered connectors.
	 *
	 * @return array<string, ConnectorInterface> All connectors.
	 */
	public function get_all(): array {
		return $this->connectors;
	}

	/**
	 * Get all available (active) connectors.
	 *
	 * @return array<string, ConnectorInterface> Available connectors.
	 */
	public function get_available(): array {
		return array_filter(
			$this->connectors,
			function ( ConnectorInterface $connector ) {
				return $connector->is_available();
			}
		);
	}

	/**
	 * Get connectors for admin display.
	 *
	 * @return array Connector data for admin.
	 */
	public function get_for_admin(): array {
		$result = [];

		foreach ( $this->connectors as $id => $connector ) {
			$result[] = [
				'id'        => $id,
				'name'      => $connector->get_name(),
				'available' => $connector->is_available(),
				'objects'   => $connector->is_available() ? $connector->get_certificate_objects() : [],
			];
		}

		return $result;
	}

	/**
	 * Get dynamic fields from all available connectors.
	 *
	 * @return array All dynamic fields.
	 */
	public function get_all_fields(): array {
		$fields = [];

		foreach ( $this->get_available() as $connector ) {
			$connector_fields = $connector->get_dynamic_fields();

			foreach ( $connector_fields as $key => $field ) {
				$field['connector']    = $connector->get_id();
				$fields[ $key ] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Resolve field values for a certificate.
	 *
	 * @param string $connector_id Connector ID.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param array  $fields Fields to resolve.
	 * @return array Resolved field values.
	 */
	public function resolve_fields( string $connector_id, int $user_id, int $object_id, array $fields = [] ): array {
		$connector = $this->get( $connector_id );

		if ( ! $connector || ! $connector->is_available() ) {
			return [];
		}

		// If no specific fields requested, get all.
		if ( empty( $fields ) ) {
			$fields = array_keys( $connector->get_dynamic_fields() );
		}

		$resolved = [];

		foreach ( $fields as $field ) {
			$resolved[ $field ] = $connector->get_field_value( $field, $user_id, $object_id );
		}

		return $resolved;
	}
}
