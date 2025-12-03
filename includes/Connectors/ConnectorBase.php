<?php
/**
 * Base connector class.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Connectors;

/**
 * Class ConnectorBase
 *
 * Abstract base class for LMS connectors.
 */
abstract class ConnectorBase implements ConnectorInterface {

	/**
	 * Cached field values.
	 *
	 * @var array
	 */
	protected array $field_cache = [];

	/**
	 * Get dynamic fields provided by this connector.
	 *
	 * @return array Array of field definitions.
	 */
	public function get_dynamic_fields(): array {
		return [];
	}

	/**
	 * Get a cached field value or compute it.
	 *
	 * @param string   $field Field key.
	 * @param int      $user_id User ID.
	 * @param int      $object_id Object ID.
	 * @param callable $callback Callback to compute value.
	 * @return string Field value.
	 */
	protected function get_cached_field( string $field, int $user_id, int $object_id, callable $callback ): string {
		$cache_key = "{$field}_{$user_id}_{$object_id}";

		if ( ! isset( $this->field_cache[ $cache_key ] ) ) {
			$this->field_cache[ $cache_key ] = $callback();
		}

		return $this->field_cache[ $cache_key ];
	}

	/**
	 * Clear the field cache.
	 */
	public function clear_cache(): void {
		$this->field_cache = [];
	}

	/**
	 * Format a date for display.
	 *
	 * @param int|string $date Unix timestamp or date string.
	 * @return string Formatted date.
	 */
	protected function format_date( $date ): string {
		if ( empty( $date ) ) {
			return '';
		}

		$timestamp = is_numeric( $date ) ? $date : strtotime( $date );
		$format    = get_option( 'date_format' );

		return wp_date( $format, $timestamp );
	}

	/**
	 * Get user display name.
	 *
	 * @param int $user_id User ID.
	 * @return string User display name.
	 */
	protected function get_user_name( int $user_id ): string {
		$user = get_userdata( $user_id );

		return $user ? $user->display_name : '';
	}

	/**
	 * Register connector fields with the global fields registry.
	 */
	protected function register_connector_fields(): void {
		$fields = $this->get_dynamic_fields();

		foreach ( $fields as $key => $field ) {
			$field['connector'] = $this->get_id();

			certbuilder()->fields()->register( $key, $field );
		}
	}
}
