<?php
/**
 * Connector interface.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Connectors;

/**
 * Interface ConnectorInterface
 *
 * Defines the contract for LMS connectors.
 */
interface ConnectorInterface {

	/**
	 * Get connector ID.
	 *
	 * @return string Unique connector identifier (e.g., 'learndash').
	 */
	public function get_id(): string;

	/**
	 * Get connector name.
	 *
	 * @return string Human-readable connector name.
	 */
	public function get_name(): string;

	/**
	 * Check if the LMS plugin is available/active.
	 *
	 * @return bool True if the LMS is active.
	 */
	public function is_available(): bool;

	/**
	 * Get dynamic fields provided by this connector.
	 *
	 * @return array Array of field definitions.
	 */
	public function get_dynamic_fields(): array;

	/**
	 * Get a field value.
	 *
	 * @param string $field Field key.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID (course, quiz, etc.).
	 * @return string Field value.
	 */
	public function get_field_value( string $field, int $user_id, int $object_id ): string;

	/**
	 * Check if user has earned a certificate for an object.
	 *
	 * @param int $user_id User ID.
	 * @param int $object_id Object ID.
	 * @return bool True if user earned certificate.
	 */
	public function user_earned_certificate( int $user_id, int $object_id ): bool;

	/**
	 * Register hooks into the LMS.
	 */
	public function register_hooks(): void;

	/**
	 * Get objects that can have certificates.
	 *
	 * @return array Array of object types with labels.
	 */
	public function get_certificate_objects(): array;
}
