<?php
/**
 * Builder controller.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Builder;

/**
 * Class BuilderController
 *
 * Handles the certificate builder interface.
 */
class BuilderController {

	/**
	 * Initialize builder controller.
	 */
	public function init(): void {
		// The builder is rendered through the admin page.
		// This controller can handle any additional AJAX actions if needed.
		add_action( 'wp_ajax_certbuilder_preview', [ $this, 'ajax_preview' ] );
	}

	/**
	 * AJAX handler for live preview.
	 */
	public function ajax_preview(): void {
		check_ajax_referer( 'wp_rest', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$template_data = isset( $_POST['template'] ) ? json_decode( wp_unslash( $_POST['template'] ), true ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$user_id       = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : get_current_user_id();
		$object_id     = isset( $_POST['object_id'] ) ? absint( $_POST['object_id'] ) : 0;
		$connector_id  = isset( $_POST['connector'] ) ? sanitize_key( $_POST['connector'] ) : 'manual';

		if ( empty( $template_data ) ) {
			wp_send_json_error( [ 'message' => 'No template data provided' ] );
		}

		// Create a temporary template for preview.
		$temp_template_id = wp_insert_post(
			[
				'post_type'   => 'cb_template',
				'post_status' => 'draft',
				'post_title'  => 'Preview Template',
			]
		);

		update_post_meta( $temp_template_id, '_cb_template_data', wp_json_encode( $template_data ) );

		try {
			// Generate sample context.
			$context = $this->get_preview_context( $user_id, $object_id, $connector_id );

			// Generate PDF.
			$pdf_content = certbuilder()->pdf_generator()->generate(
				$temp_template_id,
				$user_id,
				$object_id ?: 1,
				$connector_id,
				$context
			);

			// Return as base64.
			$base64 = base64_encode( $pdf_content ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

			wp_delete_post( $temp_template_id, true );

			wp_send_json_success(
				[
					'pdf' => 'data:application/pdf;base64,' . $base64,
				]
			);
		} catch ( \Exception $e ) {
			wp_delete_post( $temp_template_id, true );
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * Get preview context with sample data.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param string $connector_id Connector ID.
	 * @return array Context array.
	 */
	private function get_preview_context( int $user_id, int $object_id, string $connector_id ): array {
		$user = get_userdata( $user_id );

		$context = [
			'user_id'           => $user_id,
			'object_id'         => $object_id ?: 1,
			'certificate_id'    => 'CB-' . gmdate( 'Y' ) . '-PREVIEW',
			'verification_code' => str_repeat( 'a', 64 ),
			'issued_at'         => current_time( 'mysql' ),
			'expires_at'        => null,
		];

		// Add user data.
		if ( $user ) {
			$context['student_name']  = $user->display_name;
			$context['student_first'] = $user->first_name ?: 'John';
			$context['student_last']  = $user->last_name ?: 'Doe';
			$context['student_email'] = $user->user_email;
		} else {
			$context['student_name']  = 'John Doe';
			$context['student_first'] = 'John';
			$context['student_last']  = 'Doe';
			$context['student_email'] = 'john@example.com';
		}

		// Add sample connector data.
		if ( 'learndash' === $connector_id ) {
			$context['course_title']     = $object_id ? get_the_title( $object_id ) : 'Sample Course Title';
			$context['course_points']    = '100';
			$context['completion_date']  = wp_date( get_option( 'date_format' ) );
			$context['enrollment_date']  = wp_date( get_option( 'date_format' ), strtotime( '-30 days' ) );
			$context['quiz_title']       = 'Final Assessment';
			$context['quiz_score']       = '95';
			$context['quiz_percentage']  = '95%';
			$context['quiz_points']      = '95';
			$context['quiz_total_points'] = '100';
			$context['instructor_name']  = 'Jane Smith';
			$context['group_name']       = 'Premium Members';
			$context['total_courses']    = '5';
			$context['total_points']     = '500';
		} else {
			$context['achievement_title']       = 'Course Completion';
			$context['achievement_description'] = 'Successfully completed all requirements';
			$context['issuer_name']             = 'Jane Smith';
			$context['issuer_title']            = 'Program Director';
			$context['custom_field_1']          = 'Custom Value 1';
			$context['custom_field_2']          = 'Custom Value 2';
			$context['custom_field_3']          = 'Custom Value 3';
		}

		// Add site data.
		$context['site_name']    = get_bloginfo( 'name' );
		$context['site_url']     = home_url();
		$context['current_date'] = wp_date( get_option( 'date_format' ) );
		$context['issue_date']   = wp_date( get_option( 'date_format' ) );
		$context['expiry_date']  = __( 'Never', 'certbuilder-pro' );

		return $context;
	}
}
