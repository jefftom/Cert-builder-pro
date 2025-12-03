<?php
/**
 * Database schema management.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Database;

/**
 * Class Schema
 *
 * Handles database table creation and upgrades.
 */
class Schema {

	/**
	 * WordPress database instance.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Table name for certificates.
	 *
	 * @var string
	 */
	private string $certificates_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb               = $wpdb;
		$this->certificates_table = $wpdb->prefix . 'certbuilder_certificates';
	}

	/**
	 * Create all plugin tables.
	 */
	public function create_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_certificates_table();

		// Store schema version for future upgrades.
		update_option( 'certbuilder_db_version', '1.0.0' );
	}

	/**
	 * Create the certificates table.
	 */
	private function create_certificates_table(): void {
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->certificates_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			certificate_id VARCHAR(50) NOT NULL,
			verification_code VARCHAR(64) NOT NULL,
			template_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			object_type VARCHAR(50) NOT NULL,
			connector VARCHAR(50) NOT NULL,
			certificate_data LONGTEXT NOT NULL,
			status VARCHAR(20) DEFAULT 'active',
			issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME DEFAULT NULL,
			revoked_at DATETIME DEFAULT NULL,
			revoke_reason TEXT DEFAULT NULL,
			view_count INT UNSIGNED DEFAULT 0,
			download_count INT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY certificate_id (certificate_id),
			UNIQUE KEY verification_code (verification_code),
			KEY idx_user (user_id),
			KEY idx_object (object_type, object_id),
			KEY idx_template (template_id),
			KEY idx_status (status),
			KEY idx_connector (connector)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Get the certificates table name.
	 *
	 * @return string
	 */
	public function get_certificates_table(): string {
		return $this->certificates_table;
	}

	/**
	 * Drop all plugin tables.
	 *
	 * Used during uninstall.
	 */
	public function drop_tables(): void {
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->certificates_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Check if tables exist.
	 *
	 * @return bool
	 */
	public function tables_exist(): bool {
		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->certificates_table
			)
		);

		return $result === $this->certificates_table;
	}
}
