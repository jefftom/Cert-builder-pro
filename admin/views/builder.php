<?php
/**
 * Builder view.
 *
 * @package CertBuilder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$template_id = isset( $_GET['template_id'] ) ? absint( $_GET['template_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$template    = $template_id ? certbuilder()->template()->get( $template_id ) : null;
?>
<div class="wrap certbuilder-admin certbuilder-builder-wrap">
	<div id="certbuilder-builder-root" data-template-id="<?php echo esc_attr( $template_id ); ?>">
		<!-- React app loads here -->
		<div class="certbuilder-builder-loading">
			<span class="spinner is-active"></span>
			<p><?php esc_html_e( 'Loading builder...', 'certbuilder-pro' ); ?></p>
		</div>
	</div>
</div>

<!-- Fallback for when React app hasn't been built yet -->
<script>
(function() {
	// Check if React app loaded after 2 seconds
	setTimeout(function() {
		var root = document.getElementById('certbuilder-builder-root');
		var loading = root.querySelector('.certbuilder-builder-loading');

		if (loading && root.children.length === 1) {
			loading.innerHTML = `
				<div class="certbuilder-builder-fallback">
					<h2><?php echo esc_js( __( 'Builder Not Available', 'certbuilder-pro' ) ); ?></h2>
					<p><?php echo esc_js( __( 'The visual builder requires building the React application.', 'certbuilder-pro' ) ); ?></p>
					<p><?php echo esc_js( __( 'Run the following commands in the plugin directory:', 'certbuilder-pro' ) ); ?></p>
					<pre>cd builder
npm install
npm run build</pre>
					<p><?php echo esc_js( __( 'In the meantime, you can create templates via the REST API.', 'certbuilder-pro' ) ); ?></p>
				</div>
			`;
		}
	}, 2000);
})();
</script>

<style>
.certbuilder-builder-wrap {
	margin: 0;
	padding: 0;
	max-width: none;
}

.certbuilder-builder-loading {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-height: 80vh;
	text-align: center;
}

.certbuilder-builder-fallback {
	max-width: 600px;
	padding: 40px;
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.certbuilder-builder-fallback pre {
	background: #f5f5f5;
	padding: 15px;
	border-radius: 4px;
	overflow-x: auto;
}
</style>
