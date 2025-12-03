<?php
/**
 * LearnDash connector.
 *
 * @package CertBuilder
 */

namespace CertBuilder\Connectors;

/**
 * Class LearnDashConnector
 *
 * Integrates CertBuilder with LearnDash LMS.
 */
class LearnDashConnector extends ConnectorBase {

	/**
	 * Get connector ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'learndash';
	}

	/**
	 * Get connector name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'LearnDash LMS';
	}

	/**
	 * Check if LearnDash is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return defined( 'LEARNDASH_VERSION' ) && function_exists( 'learndash_course_completed' );
	}

	/**
	 * Get certificate objects.
	 *
	 * @return array
	 */
	public function get_certificate_objects(): array {
		return [
			'sfwd-courses' => __( 'Courses', 'certbuilder-pro' ),
			'sfwd-quiz'    => __( 'Quizzes', 'certbuilder-pro' ),
		];
	}

	/**
	 * Get dynamic fields.
	 *
	 * @return array
	 */
	public function get_dynamic_fields(): array {
		return [
			// Course fields.
			'course_title'       => [
				'label'       => __( 'Course Title', 'certbuilder-pro' ),
				'description' => __( 'Title of the completed course', 'certbuilder-pro' ),
				'group'       => 'course',
				'callback'    => [ $this, 'get_course_title' ],
			],
			'course_points'      => [
				'label'       => __( 'Course Points', 'certbuilder-pro' ),
				'description' => __( 'Points assigned to the course', 'certbuilder-pro' ),
				'group'       => 'course',
				'callback'    => [ $this, 'get_course_points' ],
			],

			// Completion fields.
			'completion_date'    => [
				'label'       => __( 'Completion Date', 'certbuilder-pro' ),
				'description' => __( 'Date the course/quiz was completed', 'certbuilder-pro' ),
				'group'       => 'completion',
				'callback'    => [ $this, 'get_completion_date' ],
			],
			'enrollment_date'    => [
				'label'       => __( 'Enrollment Date', 'certbuilder-pro' ),
				'description' => __( 'Date the user enrolled in the course', 'certbuilder-pro' ),
				'group'       => 'completion',
				'callback'    => [ $this, 'get_enrollment_date' ],
			],

			// Quiz fields.
			'quiz_title'         => [
				'label'       => __( 'Quiz Title', 'certbuilder-pro' ),
				'description' => __( 'Title of the completed quiz', 'certbuilder-pro' ),
				'group'       => 'quiz',
				'callback'    => [ $this, 'get_quiz_title' ],
			],
			'quiz_score'         => [
				'label'       => __( 'Quiz Score', 'certbuilder-pro' ),
				'description' => __( 'Points scored on the quiz', 'certbuilder-pro' ),
				'group'       => 'quiz',
				'callback'    => [ $this, 'get_quiz_score' ],
			],
			'quiz_percentage'    => [
				'label'       => __( 'Quiz Percentage', 'certbuilder-pro' ),
				'description' => __( 'Percentage score on the quiz', 'certbuilder-pro' ),
				'group'       => 'quiz',
				'callback'    => [ $this, 'get_quiz_percentage' ],
			],
			'quiz_points'        => [
				'label'       => __( 'Quiz Points Earned', 'certbuilder-pro' ),
				'description' => __( 'Total points earned on the quiz', 'certbuilder-pro' ),
				'group'       => 'quiz',
				'callback'    => [ $this, 'get_quiz_points' ],
			],
			'quiz_total_points'  => [
				'label'       => __( 'Quiz Total Points', 'certbuilder-pro' ),
				'description' => __( 'Maximum points possible on the quiz', 'certbuilder-pro' ),
				'group'       => 'quiz',
				'callback'    => [ $this, 'get_quiz_total_points' ],
			],
			'quiz_pass_score'    => [
				'label'       => __( 'Quiz Pass Score', 'certbuilder-pro' ),
				'description' => __( 'Minimum score required to pass', 'certbuilder-pro' ),
				'group'       => 'quiz',
				'callback'    => [ $this, 'get_quiz_pass_score' ],
			],

			// Instructor fields.
			'instructor_name'    => [
				'label'       => __( 'Instructor Name', 'certbuilder-pro' ),
				'description' => __( 'Name of the course author/instructor', 'certbuilder-pro' ),
				'group'       => 'instructor',
				'callback'    => [ $this, 'get_instructor_name' ],
			],
			'instructor_email'   => [
				'label'       => __( 'Instructor Email', 'certbuilder-pro' ),
				'description' => __( 'Email of the course instructor', 'certbuilder-pro' ),
				'group'       => 'instructor',
				'callback'    => [ $this, 'get_instructor_email' ],
			],

			// Group fields.
			'group_name'         => [
				'label'       => __( 'Group Name', 'certbuilder-pro' ),
				'description' => __( 'Name of the user\'s LearnDash group', 'certbuilder-pro' ),
				'group'       => 'group',
				'callback'    => [ $this, 'get_group_name' ],
			],

			// Cumulative fields.
			'total_courses'      => [
				'label'       => __( 'Total Courses Completed', 'certbuilder-pro' ),
				'description' => __( 'Number of courses the user has completed', 'certbuilder-pro' ),
				'group'       => 'cumulative',
				'callback'    => [ $this, 'get_total_courses' ],
			],
			'total_points'       => [
				'label'       => __( 'Total Points Earned', 'certbuilder-pro' ),
				'description' => __( 'Total points earned by the user', 'certbuilder-pro' ),
				'group'       => 'cumulative',
				'callback'    => [ $this, 'get_total_points' ],
			],
		];
	}

	/**
	 * Get field value.
	 *
	 * @param string $field Field key.
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @return string
	 */
	public function get_field_value( string $field, int $user_id, int $object_id ): string {
		$fields  = $this->get_dynamic_fields();
		$context = [
			'user_id'   => $user_id,
			'object_id' => $object_id,
		];

		if ( isset( $fields[ $field ] ) && is_callable( $fields[ $field ]['callback'] ) ) {
			return (string) call_user_func( $fields[ $field ]['callback'], $context );
		}

		return '';
	}

	/**
	 * Check if user earned certificate.
	 *
	 * @param int $user_id User ID.
	 * @param int $object_id Object ID.
	 * @return bool
	 */
	public function user_earned_certificate( int $user_id, int $object_id ): bool {
		$post_type = get_post_type( $object_id );

		if ( 'sfwd-courses' === $post_type ) {
			return $this->user_completed_course( $user_id, $object_id );
		}

		if ( 'sfwd-quiz' === $post_type ) {
			return $this->user_passed_quiz( $user_id, $object_id );
		}

		return false;
	}

	/**
	 * Check if user completed a course.
	 *
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 * @return bool
	 */
	private function user_completed_course( int $user_id, int $course_id ): bool {
		if ( ! function_exists( 'learndash_course_completed' ) ) {
			return false;
		}

		$progress = learndash_course_progress(
			[
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'array'     => true,
			]
		);

		return isset( $progress['status'] ) && 'completed' === $progress['status'];
	}

	/**
	 * Check if user passed a quiz.
	 *
	 * @param int $user_id User ID.
	 * @param int $quiz_id Quiz ID.
	 * @return bool
	 */
	private function user_passed_quiz( int $user_id, int $quiz_id ): bool {
		if ( ! function_exists( 'learndash_get_user_quiz_attempt' ) ) {
			return false;
		}

		$attempts = learndash_get_user_quiz_attempt( $user_id, [ 'quiz' => $quiz_id ] );

		if ( empty( $attempts ) ) {
			return false;
		}

		// Get the latest passing attempt.
		foreach ( array_reverse( $attempts ) as $attempt ) {
			if ( ! empty( $attempt['pass'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register LearnDash hooks.
	 */
	public function register_hooks(): void {
		// Intercept certificate links.
		add_filter( 'learndash_course_certificate_link', [ $this, 'filter_certificate_link' ], 999, 3 );
		add_filter( 'learndash_quiz_certificate_link', [ $this, 'filter_certificate_link' ], 999, 3 );

		// Add metaboxes to course/quiz edit screens.
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ], 10, 2 );

		// Register connector fields.
		add_action( 'certbuilder_register_fields', [ $this, 'register_learndash_fields' ] );

		// Handle certificate generation on completion.
		add_action( 'learndash_course_completed', [ $this, 'on_course_completed' ], 10, 2 );
		add_action( 'learndash_quiz_completed', [ $this, 'on_quiz_completed' ], 10, 2 );
	}

	/**
	 * Filter certificate link to use CertBuilder.
	 *
	 * @param string $url Original certificate URL.
	 * @param int    $object_id Course or quiz ID.
	 * @param int    $user_id User ID.
	 * @return string Modified URL.
	 */
	public function filter_certificate_link( $url, $object_id, $user_id ): string {
		// Check if our template is assigned to this course/quiz.
		$template_id = get_post_meta( $object_id, '_certbuilder_template', true );

		if ( empty( $template_id ) ) {
			return $url; // Fall back to LearnDash default.
		}

		// Verify the template exists.
		$template = certbuilder()->template()->get( (int) $template_id );
		if ( ! $template ) {
			return $url;
		}

		// Check if user earned the certificate.
		if ( ! $this->user_earned_certificate( $user_id, $object_id ) ) {
			return $url;
		}

		// Generate nonce for security.
		$nonce = wp_create_nonce( 'certbuilder_' . $user_id . '_' . $object_id );

		// Build our certificate URL.
		return add_query_arg(
			[
				'certbuilder' => 1,
				'template'    => $template_id,
				'object'      => $object_id,
				'user'        => $user_id,
				'connector'   => $this->get_id(),
				'nonce'       => $nonce,
			],
			home_url( '/certificate/' )
		);
	}

	/**
	 * Add meta boxes to course/quiz edit screens.
	 */
	public function add_meta_boxes(): void {
		$post_types = [ 'sfwd-courses', 'sfwd-quiz' ];

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'certbuilder_template',
				__( 'CertBuilder Pro', 'certbuilder-pro' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$template_id = get_post_meta( $post->ID, '_certbuilder_template', true );
		$templates   = certbuilder()->template()->get_all();

		wp_nonce_field( 'certbuilder_meta_box', 'certbuilder_meta_box_nonce' );
		?>
		<p>
			<label for="certbuilder_template">
				<?php esc_html_e( 'Certificate Template:', 'certbuilder-pro' ); ?>
			</label>
		</p>
		<select name="certbuilder_template" id="certbuilder_template" class="widefat">
			<option value=""><?php esc_html_e( '— Use LearnDash Default —', 'certbuilder-pro' ); ?></option>
			<?php foreach ( $templates as $template ) : ?>
				<option value="<?php echo esc_attr( $template['id'] ); ?>" <?php selected( $template_id, $template['id'] ); ?>>
					<?php echo esc_html( $template['title'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select a CertBuilder template to replace LearnDash certificates.', 'certbuilder-pro' ); ?>
		</p>
		<?php if ( ! empty( $templates ) ) : ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-templates' ) ); ?>">
					<?php esc_html_e( 'Manage Templates →', 'certbuilder-pro' ); ?>
				</a>
			</p>
		<?php else : ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=certbuilder-builder' ) ); ?>">
					<?php esc_html_e( 'Create Your First Template →', 'certbuilder-pro' ); ?>
				</a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	public function save_meta_boxes( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST['certbuilder_meta_box_nonce'] ) ||
			! wp_verify_nonce( sanitize_key( $_POST['certbuilder_meta_box_nonce'] ), 'certbuilder_meta_box' ) ) {
			return;
		}

		// Check autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check post type.
		if ( ! in_array( $post->post_type, [ 'sfwd-courses', 'sfwd-quiz' ], true ) ) {
			return;
		}

		// Save template selection.
		if ( isset( $_POST['certbuilder_template'] ) ) {
			$template_id = sanitize_text_field( wp_unslash( $_POST['certbuilder_template'] ) );

			if ( empty( $template_id ) ) {
				delete_post_meta( $post_id, '_certbuilder_template' );
			} else {
				update_post_meta( $post_id, '_certbuilder_template', absint( $template_id ) );
			}
		}
	}

	/**
	 * Register LearnDash fields with the global registry.
	 *
	 * @param \CertBuilder\Core\Fields $fields Fields instance.
	 */
	public function register_learndash_fields( $fields ): void {
		foreach ( $this->get_dynamic_fields() as $key => $field ) {
			$field['connector'] = $this->get_id();
			$fields->register( $key, $field );
		}
	}

	/**
	 * Handle course completion.
	 *
	 * @param array $data Completion data.
	 * @param mixed $user User object or ID.
	 */
	public function on_course_completed( array $data, $user ): void {
		// Auto-issue certificate if template is assigned.
		$course_id   = $data['course']->ID ?? 0;
		$user_id     = is_object( $user ) ? $user->ID : $user;
		$template_id = get_post_meta( $course_id, '_certbuilder_template', true );

		if ( ! $course_id || ! $user_id || ! $template_id ) {
			return;
		}

		// Check if certificate already exists.
		$existing = certbuilder()->certificate()->get_for_user_object( $user_id, $course_id, 'sfwd-courses' );
		if ( $existing ) {
			return;
		}

		// Issue the certificate.
		$this->issue_certificate( $user_id, $course_id, 'sfwd-courses', (int) $template_id );
	}

	/**
	 * Handle quiz completion.
	 *
	 * @param array $data Quiz data.
	 * @param mixed $user User object or ID.
	 */
	public function on_quiz_completed( array $data, $user ): void {
		// Only issue certificate if quiz was passed.
		if ( empty( $data['pass'] ) ) {
			return;
		}

		$quiz_id     = $data['quiz'] ?? 0;
		$user_id     = is_object( $user ) ? $user->ID : $user;
		$template_id = get_post_meta( $quiz_id, '_certbuilder_template', true );

		if ( ! $quiz_id || ! $user_id || ! $template_id ) {
			return;
		}

		// Check if certificate already exists.
		$existing = certbuilder()->certificate()->get_for_user_object( $user_id, $quiz_id, 'sfwd-quiz' );
		if ( $existing ) {
			return;
		}

		// Issue the certificate.
		$this->issue_certificate( $user_id, $quiz_id, 'sfwd-quiz', (int) $template_id );
	}

	/**
	 * Issue a certificate.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $object_id Object ID.
	 * @param string $object_type Object type.
	 * @param int    $template_id Template ID.
	 */
	private function issue_certificate( int $user_id, int $object_id, string $object_type, int $template_id ): void {
		// Resolve field values.
		$field_values = [];
		foreach ( array_keys( $this->get_dynamic_fields() ) as $field ) {
			$field_values[ $field ] = $this->get_field_value( $field, $user_id, $object_id );
		}

		// Calculate expiration.
		$expires_at       = null;
		$default_days = (int) get_option( 'certbuilder_default_expiration', 0 );
		if ( $default_days > 0 ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( "+{$default_days} days" ) );
		}

		// Issue the certificate.
		certbuilder()->certificate()->issue(
			[
				'template_id'      => $template_id,
				'user_id'          => $user_id,
				'object_id'        => $object_id,
				'object_type'      => $object_type,
				'connector'        => $this->get_id(),
				'certificate_data' => $field_values,
				'expires_at'       => $expires_at,
			]
		);
	}

	// Field value callbacks.

	/**
	 * Get course title.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_course_title( array $context ): string {
		$object_id = $context['object_id'];
		$post_type = get_post_type( $object_id );

		// If this is a quiz, get the associated course.
		if ( 'sfwd-quiz' === $post_type ) {
			$course_id = learndash_get_course_id( $object_id );
			if ( $course_id ) {
				return get_the_title( $course_id );
			}
		}

		if ( 'sfwd-courses' === $post_type ) {
			return get_the_title( $object_id );
		}

		return '';
	}

	/**
	 * Get course points.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_course_points( array $context ): string {
		$object_id = $context['object_id'];
		$post_type = get_post_type( $object_id );

		$course_id = $object_id;
		if ( 'sfwd-quiz' === $post_type ) {
			$course_id = learndash_get_course_id( $object_id );
		}

		if ( ! $course_id ) {
			return '';
		}

		$points = learndash_get_course_points( $course_id );

		return $points ? (string) $points : '';
	}

	/**
	 * Get completion date.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_completion_date( array $context ): string {
		$user_id   = $context['user_id'];
		$object_id = $context['object_id'];
		$post_type = get_post_type( $object_id );

		if ( 'sfwd-courses' === $post_type ) {
			$activity = learndash_get_user_activity(
				[
					'user_id'       => $user_id,
					'post_id'       => $object_id,
					'activity_type' => 'course',
				]
			);

			if ( $activity && ! empty( $activity->activity_completed ) ) {
				return $this->format_date( $activity->activity_completed );
			}
		}

		if ( 'sfwd-quiz' === $post_type ) {
			$attempts = learndash_get_user_quiz_attempt( $user_id, [ 'quiz' => $object_id ] );
			if ( ! empty( $attempts ) ) {
				// Get the latest passing attempt.
				foreach ( array_reverse( $attempts ) as $attempt ) {
					if ( ! empty( $attempt['pass'] ) && ! empty( $attempt['time'] ) ) {
						return $this->format_date( $attempt['time'] );
					}
				}
			}
		}

		return '';
	}

	/**
	 * Get enrollment date.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_enrollment_date( array $context ): string {
		$user_id   = $context['user_id'];
		$object_id = $context['object_id'];

		$course_id = learndash_get_course_id( $object_id );
		if ( ! $course_id ) {
			$course_id = $object_id;
		}

		$activity = learndash_get_user_activity(
			[
				'user_id'       => $user_id,
				'post_id'       => $course_id,
				'activity_type' => 'course',
			]
		);

		if ( $activity && ! empty( $activity->activity_started ) ) {
			return $this->format_date( $activity->activity_started );
		}

		return '';
	}

	/**
	 * Get quiz title.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_quiz_title( array $context ): string {
		$object_id = $context['object_id'];

		if ( 'sfwd-quiz' === get_post_type( $object_id ) ) {
			return get_the_title( $object_id );
		}

		return '';
	}

	/**
	 * Get quiz score.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_quiz_score( array $context ): string {
		$attempt = $this->get_latest_passing_attempt( $context );

		if ( $attempt && isset( $attempt['score'] ) ) {
			return (string) $attempt['score'];
		}

		return '';
	}

	/**
	 * Get quiz percentage.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_quiz_percentage( array $context ): string {
		$attempt = $this->get_latest_passing_attempt( $context );

		if ( $attempt && isset( $attempt['percentage'] ) ) {
			return round( $attempt['percentage'], 1 ) . '%';
		}

		return '';
	}

	/**
	 * Get quiz points earned.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_quiz_points( array $context ): string {
		$attempt = $this->get_latest_passing_attempt( $context );

		if ( $attempt && isset( $attempt['points'] ) ) {
			return (string) $attempt['points'];
		}

		return '';
	}

	/**
	 * Get quiz total points.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_quiz_total_points( array $context ): string {
		$attempt = $this->get_latest_passing_attempt( $context );

		if ( $attempt && isset( $attempt['total_points'] ) ) {
			return (string) $attempt['total_points'];
		}

		return '';
	}

	/**
	 * Get quiz pass score.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_quiz_pass_score( array $context ): string {
		$object_id = $context['object_id'];

		if ( 'sfwd-quiz' !== get_post_type( $object_id ) ) {
			return '';
		}

		$settings = learndash_get_setting( $object_id );

		if ( isset( $settings['passingpercentage'] ) ) {
			return $settings['passingpercentage'] . '%';
		}

		return '';
	}

	/**
	 * Get instructor name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_instructor_name( array $context ): string {
		$object_id = $context['object_id'];

		$course_id = learndash_get_course_id( $object_id );
		if ( ! $course_id ) {
			$course_id = $object_id;
		}

		$course = get_post( $course_id );
		if ( ! $course ) {
			return '';
		}

		$author = get_userdata( $course->post_author );

		return $author ? $author->display_name : '';
	}

	/**
	 * Get instructor email.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_instructor_email( array $context ): string {
		$object_id = $context['object_id'];

		$course_id = learndash_get_course_id( $object_id );
		if ( ! $course_id ) {
			$course_id = $object_id;
		}

		$course = get_post( $course_id );
		if ( ! $course ) {
			return '';
		}

		$author = get_userdata( $course->post_author );

		return $author ? $author->user_email : '';
	}

	/**
	 * Get group name.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_group_name( array $context ): string {
		$user_id = $context['user_id'];

		$groups = learndash_get_users_group_ids( $user_id );
		if ( empty( $groups ) ) {
			return '';
		}

		// Return first group name.
		return get_the_title( $groups[0] );
	}

	/**
	 * Get total completed courses.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_total_courses( array $context ): string {
		$user_id = $context['user_id'];

		$courses = learndash_user_get_enrolled_courses( $user_id );
		$count   = 0;

		foreach ( $courses as $course_id ) {
			if ( learndash_course_completed( $user_id, $course_id ) ) {
				++$count;
			}
		}

		return (string) $count;
	}

	/**
	 * Get total points earned.
	 *
	 * @param array $context Context data.
	 * @return string
	 */
	public function get_total_points( array $context ): string {
		$user_id = $context['user_id'];

		$points = learndash_get_user_course_points( $user_id );

		return (string) $points;
	}

	/**
	 * Get latest passing quiz attempt.
	 *
	 * @param array $context Context data.
	 * @return array|null
	 */
	private function get_latest_passing_attempt( array $context ): ?array {
		$user_id   = $context['user_id'];
		$object_id = $context['object_id'];

		if ( 'sfwd-quiz' !== get_post_type( $object_id ) ) {
			return null;
		}

		$attempts = learndash_get_user_quiz_attempt( $user_id, [ 'quiz' => $object_id ] );
		if ( empty( $attempts ) ) {
			return null;
		}

		// Get the latest passing attempt.
		foreach ( array_reverse( $attempts ) as $attempt ) {
			if ( ! empty( $attempt['pass'] ) ) {
				return $attempt;
			}
		}

		return null;
	}
}
