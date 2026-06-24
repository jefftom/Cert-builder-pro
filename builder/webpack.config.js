/**
 * Webpack configuration for the CertBuilder Pro visual builder.
 *
 * Extends the default @wordpress/scripts config with two project-specific
 * adjustments:
 *   1. The entry point is `src/index.jsx` (wp-scripts defaults to index.js).
 *   2. The bundle is emitted to `dist/` so it matches what the plugin
 *      enqueues in includes/Admin/Admin.php (builder/dist/index.{js,css}
 *      and builder/dist/index.asset.php).
 *
 * Fabric.js conditionally references a handful of Node-only modules
 * (canvas, jsdom, fs) that are not used in the browser build; we stub them
 * out so the bundle resolves cleanly.
 */

const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( __dirname, 'src', 'index.jsx' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: [ '.js', '.jsx', '.ts', '.tsx', '...' ],
		fallback: {
			...( defaultConfig.resolve && defaultConfig.resolve.fallback ),
			canvas: false,
			jsdom: false,
			fs: false,
		},
	},
};
