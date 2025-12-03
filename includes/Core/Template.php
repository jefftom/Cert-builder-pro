<?php
/**
 * Template CPT management.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Core;

/**
 * Class Template
 *
 * Handles certificate template custom post type.
 */
class Template {

	/**
	 * Post type slug.
	 */
	public const POST_TYPE = 'cb_template';

	/**
	 * Initialize the template system.
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_meta' ] );
	}

	/**
	 * Register the template post type.
	 */
	public function register_post_type(): void {
		$labels = [
			'name'                  => _x( 'Certificate Templates', 'Post type general name', 'certbuilder-pro' ),
			'singular_name'         => _x( 'Certificate Template', 'Post type singular name', 'certbuilder-pro' ),
			'menu_name'             => _x( 'Templates', 'Admin Menu text', 'certbuilder-pro' ),
			'add_new'               => __( 'Add New', 'certbuilder-pro' ),
			'add_new_item'          => __( 'Add New Template', 'certbuilder-pro' ),
			'edit_item'             => __( 'Edit Template', 'certbuilder-pro' ),
			'new_item'              => __( 'New Template', 'certbuilder-pro' ),
			'view_item'             => __( 'View Template', 'certbuilder-pro' ),
			'search_items'          => __( 'Search Templates', 'certbuilder-pro' ),
			'not_found'             => __( 'No templates found', 'certbuilder-pro' ),
			'not_found_in_trash'    => __( 'No templates found in Trash', 'certbuilder-pro' ),
			'all_items'             => __( 'All Templates', 'certbuilder-pro' ),
			'archives'              => __( 'Template Archives', 'certbuilder-pro' ),
			'attributes'            => __( 'Template Attributes', 'certbuilder-pro' ),
			'insert_into_item'      => __( 'Insert into template', 'certbuilder-pro' ),
			'uploaded_to_this_item' => __( 'Uploaded to this template', 'certbuilder-pro' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false, // We use our own UI.
			'show_in_menu'        => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'capabilities'        => [
				'edit_post'          => 'manage_options',
				'read_post'          => 'manage_options',
				'delete_post'        => 'manage_options',
				'edit_posts'         => 'manage_options',
				'edit_others_posts'  => 'manage_options',
				'publish_posts'      => 'manage_options',
				'read_private_posts' => 'manage_options',
			],
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => null,
			'supports'            => [ 'title' ],
			'show_in_rest'        => true,
			'rest_base'           => 'cb-templates',
			'rest_namespace'      => 'certbuilder/v1',
		];

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Register post meta for templates.
	 */
	public function register_meta(): void {
		register_post_meta(
			self::POST_TYPE,
			'_cb_template_data',
			[
				'type'          => 'string',
				'description'   => 'Certificate template JSON data',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_post_meta(
			self::POST_TYPE,
			'_cb_template_thumbnail',
			[
				'type'          => 'string',
				'description'   => 'Template thumbnail URL',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_post_meta(
			self::POST_TYPE,
			'_cb_template_category',
			[
				'type'          => 'string',
				'description'   => 'Template category',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			]
		);
	}

	/**
	 * Get a template by ID.
	 *
	 * @param int $template_id Template post ID.
	 * @return array|null Template data or null if not found.
	 */
	public function get( int $template_id ): ?array {
		$post = get_post( $template_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		$template_data = get_post_meta( $template_id, '_cb_template_data', true );

		return [
			'id'        => $post->ID,
			'title'     => $post->post_title,
			'data'      => $template_data ? json_decode( $template_data, true ) : [],
			'thumbnail' => get_post_meta( $template_id, '_cb_template_thumbnail', true ),
			'category'  => get_post_meta( $template_id, '_cb_template_category', true ),
			'created'   => $post->post_date,
			'modified'  => $post->post_modified,
		];
	}

	/**
	 * Get all templates.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of templates.
	 */
	public function get_all( array $args = [] ): array {
		$defaults = [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		$query = new \WP_Query( array_merge( $defaults, $args ) );

		$templates = [];
		foreach ( $query->posts as $post ) {
			$templates[] = $this->get( $post->ID );
		}

		return $templates;
	}

	/**
	 * Create a new template.
	 *
	 * @param string $title Template title.
	 * @param array  $data Template data.
	 * @return int|false Template ID or false on failure.
	 */
	public function create( string $title, array $data = [] ) {
		$post_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_title'  => $title,
				'post_status' => 'publish',
			]
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		if ( ! empty( $data ) ) {
			update_post_meta( $post_id, '_cb_template_data', wp_json_encode( $data ) );
		}

		return $post_id;
	}

	/**
	 * Update a template.
	 *
	 * @param int   $template_id Template ID.
	 * @param array $data Data to update.
	 * @return bool Success.
	 */
	public function update( int $template_id, array $data ): bool {
		$post = get_post( $template_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		// Update title if provided.
		if ( isset( $data['title'] ) ) {
			wp_update_post(
				[
					'ID'         => $template_id,
					'post_title' => $data['title'],
				]
			);
		}

		// Update template data if provided.
		if ( isset( $data['data'] ) ) {
			update_post_meta( $template_id, '_cb_template_data', wp_json_encode( $data['data'] ) );
		}

		// Update thumbnail if provided.
		if ( isset( $data['thumbnail'] ) ) {
			update_post_meta( $template_id, '_cb_template_thumbnail', $data['thumbnail'] );
		}

		// Update category if provided.
		if ( isset( $data['category'] ) ) {
			update_post_meta( $template_id, '_cb_template_category', $data['category'] );
		}

		return true;
	}

	/**
	 * Delete a template.
	 *
	 * @param int $template_id Template ID.
	 * @return bool Success.
	 */
	public function delete( int $template_id ): bool {
		$post = get_post( $template_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		return (bool) wp_delete_post( $template_id, true );
	}

	/**
	 * Duplicate a template.
	 *
	 * @param int $template_id Template ID to duplicate.
	 * @return int|false New template ID or false on failure.
	 */
	public function duplicate( int $template_id ) {
		$template = $this->get( $template_id );

		if ( ! $template ) {
			return false;
		}

		$new_title = sprintf(
			/* translators: %s: Original template title */
			__( '%s (Copy)', 'certbuilder-pro' ),
			$template['title']
		);

		$new_id = $this->create( $new_title, $template['data'] );

		if ( $new_id ) {
			if ( $template['thumbnail'] ) {
				update_post_meta( $new_id, '_cb_template_thumbnail', $template['thumbnail'] );
			}
			if ( $template['category'] ) {
				update_post_meta( $new_id, '_cb_template_category', $template['category'] );
			}
		}

		return $new_id;
	}

	/**
	 * Get usage count for a template.
	 *
	 * @param int $template_id Template ID.
	 * @return int Number of certificates using this template.
	 */
	public function get_usage_count( int $template_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'certbuilder_certificates';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE template_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$template_id
			)
		);
	}
}
