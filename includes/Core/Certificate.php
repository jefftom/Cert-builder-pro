<?php
/**
 * Certificate model and management.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Core;

/**
 * Class Certificate
 *
 * Handles issued certificate records.
 */
class Certificate {

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'certbuilder_certificates';
	}

	/**
	 * Initialize.
	 */
	public function init(): void {
		// No hooks needed for now.
	}

	/**
	 * Issue a new certificate.
	 *
	 * @param array $data Certificate data.
	 * @return int|false Certificate record ID or false on failure.
	 */
	public function issue( array $data ) {
		global $wpdb;

		$certificate_id    = $this->generate_certificate_id();
		$verification_code = $this->generate_verification_code( $certificate_id, $data['user_id'] );

		$insert_data = [
			'certificate_id'    => $certificate_id,
			'verification_code' => $verification_code,
			'template_id'       => $data['template_id'],
			'user_id'           => $data['user_id'],
			'object_id'         => $data['object_id'],
			'object_type'       => $data['object_type'],
			'connector'         => $data['connector'],
			'certificate_data'  => wp_json_encode( $data['certificate_data'] ?? [] ),
			'status'            => 'active',
			'issued_at'         => current_time( 'mysql' ),
			'expires_at'        => $data['expires_at'] ?? null,
		];

		$result = $wpdb->insert( $this->table, $insert_data );

		if ( false === $result ) {
			return false;
		}

		$record_id = $wpdb->insert_id;

		/**
		 * Fires after a certificate is issued.
		 *
		 * @param int    $record_id Certificate record ID.
		 * @param string $certificate_id Public certificate ID.
		 * @param array  $data Certificate data.
		 */
		do_action( 'certbuilder_certificate_issued', $record_id, $certificate_id, $data );

		return $record_id;
	}

	/**
	 * Get certificate by record ID.
	 *
	 * @param int $id Certificate record ID.
	 * @return object|null Certificate record or null.
	 */
	public function get( int $id ): ?object {
		global $wpdb;

		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);

		if ( $certificate ) {
			$certificate->certificate_data = json_decode( $certificate->certificate_data, true );
		}

		return $certificate;
	}

	/**
	 * Get certificate by certificate ID.
	 *
	 * @param string $certificate_id Certificate ID (e.g., CB-2025-00001).
	 * @return object|null Certificate record or null.
	 */
	public function get_by_certificate_id( string $certificate_id ): ?object {
		global $wpdb;

		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE certificate_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$certificate_id
			)
		);

		if ( $certificate ) {
			$certificate->certificate_data = json_decode( $certificate->certificate_data, true );
		}

		return $certificate;
	}

	/**
	 * Get certificate by verification code.
	 *
	 * @param string $verification_code Verification code.
	 * @return object|null Certificate record or null.
	 */
	public function get_by_verification_code( string $verification_code ): ?object {
		global $wpdb;

		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE verification_code = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$verification_code
			)
		);

		if ( $certificate ) {
			$certificate->certificate_data = json_decode( $certificate->certificate_data, true );
		}

		return $certificate;
	}

	/**
	 * Get certificates for a user.
	 *
	 * @param int   $user_id User ID.
	 * @param array $args Query arguments.
	 * @return array Array of certificate records.
	 */
	public function get_for_user( int $user_id, array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'status'   => 'active',
			'order_by' => 'issued_at',
			'order'    => 'DESC',
			'limit'    => 100,
			'offset'   => 0,
		];

		$args = array_merge( $defaults, $args );

		$where = [ 'user_id = %d' ];
		$values = [ $user_id ];

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );
		$order_by     = sanitize_sql_orderby( $args['order_by'] . ' ' . $args['order'] ) ?: 'issued_at DESC';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$certificates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$order_by} LIMIT %d OFFSET %d",
				array_merge( $values, [ $args['limit'], $args['offset'] ] )
			)
		);

		foreach ( $certificates as $certificate ) {
			$certificate->certificate_data = json_decode( $certificate->certificate_data, true );
		}

		return $certificates;
	}

	/**
	 * Get certificate for user and object.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @return object|null Certificate record or null.
	 */
	public function get_for_user_object( int $user_id, int $object_id, string $object_type ): ?object {
		global $wpdb;

		$certificate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d AND object_id = %d AND object_type = %s AND status = 'active' ORDER BY issued_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$object_id,
				$object_type
			)
		);

		if ( $certificate ) {
			$certificate->certificate_data = json_decode( $certificate->certificate_data, true );
		}

		return $certificate;
	}

	/**
	 * Get all certificates with pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array Array with 'certificates' and 'total'.
	 */
	public function get_all( array $args = [] ): array {
		global $wpdb;

		$defaults = [
			'status'     => '',
			'connector'  => '',
			'user_id'    => 0,
			'search'     => '',
			'order_by'   => 'issued_at',
			'order'      => 'DESC',
			'limit'      => 20,
			'offset'     => 0,
		];

		$args = array_merge( $defaults, $args );

		$where  = [ '1=1' ];
		$values = [];

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['connector'] ) ) {
			$where[]  = 'connector = %s';
			$values[] = $args['connector'];
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = $args['user_id'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(certificate_id LIKE %s OR verification_code LIKE %s)';
			$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_clause = implode( ' AND ', $where );
		$order_by     = sanitize_sql_orderby( $args['order_by'] . ' ' . $args['order'] ) ?: 'issued_at DESC';

		// Get total count.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			empty( $values )
				? "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}"
				: $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}", $values )
		);

		// Get certificates.
		$query_values = array_merge( $values, [ $args['limit'], $args['offset'] ] );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$certificates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$order_by} LIMIT %d OFFSET %d",
				$query_values
			)
		);

		foreach ( $certificates as $certificate ) {
			$certificate->certificate_data = json_decode( $certificate->certificate_data, true );
		}

		return [
			'certificates' => $certificates,
			'total'        => $total,
		];
	}

	/**
	 * Revoke a certificate.
	 *
	 * @param int    $id Certificate record ID.
	 * @param string $reason Revoke reason.
	 * @return bool Success.
	 */
	public function revoke( int $id, string $reason = '' ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			[
				'status'        => 'revoked',
				'revoked_at'    => current_time( 'mysql' ),
				'revoke_reason' => $reason,
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);

		if ( false !== $result ) {
			do_action( 'certbuilder_certificate_revoked', $id, $reason );
		}

		return false !== $result;
	}

	/**
	 * Reinstate a revoked certificate.
	 *
	 * @param int $id Certificate record ID.
	 * @return bool Success.
	 */
	public function reinstate( int $id ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			[
				'status'        => 'active',
				'revoked_at'    => null,
				'revoke_reason' => null,
			],
			[ 'id' => $id ],
			[ '%s', null, null ],
			[ '%d' ]
		);

		if ( false !== $result ) {
			do_action( 'certbuilder_certificate_reinstated', $id );
		}

		return false !== $result;
	}

	/**
	 * Increment view count.
	 *
	 * @param int $id Certificate record ID.
	 */
	public function increment_view_count( int $id ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);
	}

	/**
	 * Increment download count.
	 *
	 * @param int $id Certificate record ID.
	 */
	public function increment_download_count( int $id ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table} SET download_count = download_count + 1 WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			)
		);
	}

	/**
	 * Generate a unique certificate ID.
	 *
	 * @return string Certificate ID (e.g., CB-2025-00001).
	 */
	private function generate_certificate_id(): string {
		global $wpdb;

		$prefix       = get_option( 'certbuilder_certificate_id_prefix', 'CB' );
		$include_year = get_option( 'certbuilder_certificate_id_year', true );
		$year         = gmdate( 'Y' );

		// Get the next sequence number.
		if ( $include_year ) {
			$pattern = $prefix . '-' . $year . '-%';
			$last    = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT certificate_id FROM {$this->table} WHERE certificate_id LIKE %s ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$pattern
				)
			);

			if ( $last ) {
				$parts   = explode( '-', $last );
				$sequence = (int) end( $parts ) + 1;
			} else {
				$sequence = 1;
			}

			return sprintf( '%s-%s-%05d', $prefix, $year, $sequence );
		}

		// Without year.
		$last = $wpdb->get_var(
			"SELECT certificate_id FROM {$this->table} ORDER BY id DESC LIMIT 1" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		if ( $last ) {
			$parts   = explode( '-', $last );
			$sequence = (int) end( $parts ) + 1;
		} else {
			$sequence = 1;
		}

		return sprintf( '%s-%05d', $prefix, $sequence );
	}

	/**
	 * Generate verification code.
	 *
	 * @param string $certificate_id Certificate ID.
	 * @param int    $user_id User ID.
	 * @return string Verification code (64 char hex).
	 */
	private function generate_verification_code( string $certificate_id, int $user_id ): string {
		$secret = get_option( 'certbuilder_secret_key', '' );

		return hash( 'sha256', $certificate_id . $secret . $user_id . wp_generate_password( 16, false ) );
	}

	/**
	 * Get statistics.
	 *
	 * @return array Statistics array.
	 */
	public function get_stats(): array {
		global $wpdb;

		$stats = [];

		// Total certificates.
		$stats['total'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->table}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		// Active certificates.
		$stats['active'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'active'
			)
		);

		// Revoked certificates.
		$stats['revoked'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'revoked'
			)
		);

		// Certificates this month.
		$first_of_month   = gmdate( 'Y-m-01 00:00:00' );
		$stats['monthly'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE issued_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$first_of_month
			)
		);

		// Total views.
		$stats['total_views'] = (int) $wpdb->get_var(
			"SELECT SUM(view_count) FROM {$this->table}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		// Total downloads.
		$stats['total_downloads'] = (int) $wpdb->get_var(
			"SELECT SUM(download_count) FROM {$this->table}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $stats;
	}
}
