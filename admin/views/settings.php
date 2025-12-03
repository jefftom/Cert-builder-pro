<?php
/**
 * Settings view.
 *
 * @package CertBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap certbuilder-admin">
	<h1><?php esc_html_e( 'CertBuilder Pro Settings', 'certbuilder-pro' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'certbuilder_settings' ); ?>
		<?php do_settings_sections( 'certbuilder_settings' ); ?>
		<?php submit_button(); ?>
	</form>

	<hr>

	<h2><?php esc_html_e( 'Tools', 'certbuilder-pro' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Flush Rewrite Rules', 'certbuilder-pro' ); ?></th>
			<td>
				<form method="post" action="">
					<?php wp_nonce_field( 'certbuilder_flush_rewrite', 'certbuilder_flush_nonce' ); ?>
					<button type="submit" name="certbuilder_flush_rewrite" class="button">
						<?php esc_html_e( 'Flush Rules', 'certbuilder-pro' ); ?>
					</button>
					<p class="description">
						<?php esc_html_e( 'Click this if verification URLs are not working properly.', 'certbuilder-pro' ); ?>
					</p>
				</form>
				<?php
				if ( isset( $_POST['certbuilder_flush_rewrite'] ) && wp_verify_nonce( sanitize_key( $_POST['certbuilder_flush_nonce'] ?? '' ), 'certbuilder_flush_rewrite' ) ) {
					flush_rewrite_rules();
					echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Rewrite rules flushed successfully.', 'certbuilder-pro' ) . '</p></div>';
				}
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Verification Page', 'certbuilder-pro' ); ?></th>
			<td>
				<?php
				$page_id = get_option( 'certbuilder_verification_page_id' );
				if ( $page_id && get_post( $page_id ) ) :
					?>
					<a href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" class="button">
						<?php esc_html_e( 'Edit Verification Page', 'certbuilder-pro' ); ?>
					</a>
					<a href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" class="button" target="_blank">
						<?php esc_html_e( 'View Page', 'certbuilder-pro' ); ?>
					</a>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'Verification page not found.', 'certbuilder-pro' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<hr>

	<h2><?php esc_html_e( 'Shortcodes', 'certbuilder-pro' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><code>[my_certificates]</code></th>
			<td>
				<p class="description">
					<?php esc_html_e( 'Displays a list of certificates for the logged-in user.', 'certbuilder-pro' ); ?>
				</p>
				<p><strong><?php esc_html_e( 'Attributes:', 'certbuilder-pro' ); ?></strong></p>
				<ul>
					<li><code>title</code> - <?php esc_html_e( 'Section title (default: "My Certificates")', 'certbuilder-pro' ); ?></li>
					<li><code>limit</code> - <?php esc_html_e( 'Number of certificates to show (default: 20)', 'certbuilder-pro' ); ?></li>
				</ul>
			</td>
		</tr>
		<tr>
			<th scope="row"><code>[certbuilder_verify]</code></th>
			<td>
				<p class="description">
					<?php esc_html_e( 'Displays a certificate verification form.', 'certbuilder-pro' ); ?>
				</p>
				<p><strong><?php esc_html_e( 'Attributes:', 'certbuilder-pro' ); ?></strong></p>
				<ul>
					<li><code>title</code> - <?php esc_html_e( 'Form title (default: "Verify Certificate")', 'certbuilder-pro' ); ?></li>
				</ul>
			</td>
		</tr>
	</table>

	<hr>

	<h2><?php esc_html_e( 'System Information', 'certbuilder-pro' ); ?></h2>

	<table class="form-table">
		<tr>
			<th scope="row"><?php esc_html_e( 'Plugin Version', 'certbuilder-pro' ); ?></th>
			<td><?php echo esc_html( CERTBUILDER_VERSION ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'PHP Version', 'certbuilder-pro' ); ?></th>
			<td><?php echo esc_html( PHP_VERSION ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'WordPress Version', 'certbuilder-pro' ); ?></th>
			<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Active Connectors', 'certbuilder-pro' ); ?></th>
			<td>
				<?php
				$active = [];
				foreach ( certbuilder()->connectors()->get_available() as $connector ) {
					$active[] = $connector->get_name();
				}
				echo esc_html( implode( ', ', $active ) ?: __( 'None', 'certbuilder-pro' ) );
				?>
			</td>
		</tr>
	</table>
</div>
