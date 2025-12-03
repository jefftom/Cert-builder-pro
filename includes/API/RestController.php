<?php
/**
 * REST API controller.
 *
 * @package CertBuilder
 */

namespace CertBuilder\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class RestController
 *
 * Handles REST API endpoints.
 */
class RestController extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'certbuilder/v1';

	/**
	 * Initialize REST controller.
	 */
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		// Templates.
		register_rest_route(
			$this->namespace,
			'/templates',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_templates' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'create_template' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/templates/(?P<id>\d+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'get_template' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
				[
					'methods'             => 'PUT',
					'callback'            => [ $this, 'update_template' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
				[
					'methods'             => 'DELETE',
					'callback'            => [ $this, 'delete_template' ],
					'permission_callback' => [ $this, 'admin_permission_check' ],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/templates/(?P<id>\d+)/duplicate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'duplicate_template' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Certificates.
		register_rest_route(
			$this->namespace,
			'/certificates',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_certificates' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/certificates/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_certificate' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/certificates/(?P<id>\d+)/revoke',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'revoke_certificate' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/certificates/(?P<id>\d+)/reinstate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'reinstate_certificate' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Verification (public).
		register_rest_route(
			$this->namespace,
			'/verify/(?P<code>[a-f0-9]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'verify_certificate' ],
				'permission_callback' => '__return_true',
			]
		);

		// Connectors.
		register_rest_route(
			$this->namespace,
			'/connectors',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_connectors' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/connectors/(?P<id>[a-z_]+)/fields',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_connector_fields' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Fonts.
		register_rest_route(
			$this->namespace,
			'/fonts',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_fonts' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Fields.
		register_rest_route(
			$this->namespace,
			'/fields',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_fields' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Stats.
		register_rest_route(
			$this->namespace,
			'/stats',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_stats' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);
	}

	/**
	 * Check admin permission.
	 *
	 * @return bool|WP_Error
	 */
	public function admin_permission_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access this resource.', 'certbuilder-pro' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	// Template endpoints.

	/**
	 * Get all templates.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_templates( WP_REST_Request $request ): WP_REST_Response {
		$templates = certbuilder()->template()->get_all();

		return new WP_REST_Response( $templates, 200 );
	}

	/**
	 * Get single template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_template( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$template = certbuilder()->template()->get( $id );

		if ( ! $template ) {
			return new WP_Error( 'not_found', __( 'Template not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( $template, 200 );
	}

	/**
	 * Create template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_template( WP_REST_Request $request ) {
		$title = $request->get_param( 'title' ) ?: __( 'New Template', 'certbuilder-pro' );
		$data  = $request->get_param( 'data' ) ?: [];

		$id = certbuilder()->template()->create( $title, $data );

		if ( ! $id ) {
			return new WP_Error( 'create_failed', __( 'Failed to create template.', 'certbuilder-pro' ), [ 'status' => 500 ] );
		}

		$template = certbuilder()->template()->get( $id );

		return new WP_REST_Response( $template, 201 );
	}

	/**
	 * Update template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_template( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$template = certbuilder()->template()->get( $id );

		if ( ! $template ) {
			return new WP_Error( 'not_found', __( 'Template not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		$update_data = [];

		if ( $request->has_param( 'title' ) ) {
			$update_data['title'] = $request->get_param( 'title' );
		}

		if ( $request->has_param( 'data' ) ) {
			$update_data['data'] = $request->get_param( 'data' );
		}

		if ( $request->has_param( 'thumbnail' ) ) {
			$update_data['thumbnail'] = $request->get_param( 'thumbnail' );
		}

		if ( $request->has_param( 'category' ) ) {
			$update_data['category'] = $request->get_param( 'category' );
		}

		$success = certbuilder()->template()->update( $id, $update_data );

		if ( ! $success ) {
			return new WP_Error( 'update_failed', __( 'Failed to update template.', 'certbuilder-pro' ), [ 'status' => 500 ] );
		}

		$updated = certbuilder()->template()->get( $id );

		return new WP_REST_Response( $updated, 200 );
	}

	/**
	 * Delete template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_template( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$template = certbuilder()->template()->get( $id );

		if ( ! $template ) {
			return new WP_Error( 'not_found', __( 'Template not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		// Check if template is in use.
		$usage = certbuilder()->template()->get_usage_count( $id );
		if ( $usage > 0 ) {
			return new WP_Error(
				'in_use',
				sprintf(
					/* translators: %d: Number of certificates using this template */
					__( 'Cannot delete template. It is used by %d certificate(s).', 'certbuilder-pro' ),
					$usage
				),
				[ 'status' => 400 ]
			);
		}

		$success = certbuilder()->template()->delete( $id );

		if ( ! $success ) {
			return new WP_Error( 'delete_failed', __( 'Failed to delete template.', 'certbuilder-pro' ), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * Duplicate template.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function duplicate_template( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		$new_id = certbuilder()->template()->duplicate( $id );

		if ( ! $new_id ) {
			return new WP_Error( 'duplicate_failed', __( 'Failed to duplicate template.', 'certbuilder-pro' ), [ 'status' => 500 ] );
		}

		$template = certbuilder()->template()->get( $new_id );

		return new WP_REST_Response( $template, 201 );
	}

	// Certificate endpoints.

	/**
	 * Get certificates.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_certificates( WP_REST_Request $request ): WP_REST_Response {
		$args = [
			'status'    => $request->get_param( 'status' ) ?: '',
			'connector' => $request->get_param( 'connector' ) ?: '',
			'user_id'   => $request->get_param( 'user_id' ) ?: 0,
			'search'    => $request->get_param( 'search' ) ?: '',
			'limit'     => $request->get_param( 'per_page' ) ?: 20,
			'offset'    => ( ( $request->get_param( 'page' ) ?: 1 ) - 1 ) * ( $request->get_param( 'per_page' ) ?: 20 ),
		];

		$result = certbuilder()->certificate()->get_all( $args );

		$response = new WP_REST_Response( $result['certificates'], 200 );
		$response->header( 'X-WP-Total', $result['total'] );
		$response->header( 'X-WP-TotalPages', ceil( $result['total'] / $args['limit'] ) );

		return $response;
	}

	/**
	 * Get single certificate.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_certificate( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$certificate = certbuilder()->certificate()->get( $id );

		if ( ! $certificate ) {
			return new WP_Error( 'not_found', __( 'Certificate not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		return new WP_REST_Response( $certificate, 200 );
	}

	/**
	 * Revoke certificate.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function revoke_certificate( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );
		$reason = $request->get_param( 'reason' ) ?: '';

		$certificate = certbuilder()->certificate()->get( $id );

		if ( ! $certificate ) {
			return new WP_Error( 'not_found', __( 'Certificate not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		$success = certbuilder()->certificate()->revoke( $id, $reason );

		if ( ! $success ) {
			return new WP_Error( 'revoke_failed', __( 'Failed to revoke certificate.', 'certbuilder-pro' ), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'revoked' => true ], 200 );
	}

	/**
	 * Reinstate certificate.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function reinstate_certificate( WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		$certificate = certbuilder()->certificate()->get( $id );

		if ( ! $certificate ) {
			return new WP_Error( 'not_found', __( 'Certificate not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		$success = certbuilder()->certificate()->reinstate( $id );

		if ( ! $success ) {
			return new WP_Error( 'reinstate_failed', __( 'Failed to reinstate certificate.', 'certbuilder-pro' ), [ 'status' => 500 ] );
		}

		return new WP_REST_Response( [ 'reinstated' => true ], 200 );
	}

	/**
	 * Verify certificate (public).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function verify_certificate( WP_REST_Request $request ): WP_REST_Response {
		$code = $request->get_param( 'code' );

		$result = certbuilder()->verifier()->verify( $code );

		return new WP_REST_Response( $result, $result['valid'] ? 200 : 404 );
	}

	// Other endpoints.

	/**
	 * Get connectors.
	 *
	 * @return WP_REST_Response
	 */
	public function get_connectors(): WP_REST_Response {
		$connectors = certbuilder()->connectors()->get_for_admin();

		return new WP_REST_Response( $connectors, 200 );
	}

	/**
	 * Get connector fields.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_connector_fields( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );
		$connector = certbuilder()->connectors()->get( $id );

		if ( ! $connector ) {
			return new WP_Error( 'not_found', __( 'Connector not found.', 'certbuilder-pro' ), [ 'status' => 404 ] );
		}

		$fields = $connector->get_dynamic_fields();

		return new WP_REST_Response( $fields, 200 );
	}

	/**
	 * Get fonts.
	 *
	 * @return WP_REST_Response
	 */
	public function get_fonts(): WP_REST_Response {
		$fonts = certbuilder()->fonts()->get_for_builder();

		return new WP_REST_Response( $fonts, 200 );
	}

	/**
	 * Get fields.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_fields( WP_REST_Request $request ): WP_REST_Response {
		$connector = $request->get_param( 'connector' );

		$fields = certbuilder()->fields()->get_grouped( $connector );

		return new WP_REST_Response( $fields, 200 );
	}

	/**
	 * Get stats.
	 *
	 * @return WP_REST_Response
	 */
	public function get_stats(): WP_REST_Response {
		$stats = certbuilder()->certificate()->get_stats();

		return new WP_REST_Response( $stats, 200 );
	}
}
