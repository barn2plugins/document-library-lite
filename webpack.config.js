const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const Barn2Configuration = require( '@barn2plugins/webpack-config' );

const config = new Barn2Configuration(
	[
		'admin/document-library-post/index.js',
		'admin/document-library-settings/index.js',
		'admin/document-library-popover/index.js',
		'admin/document-library-block-editor/index.js', 
		'document-library-main.js',
		'document-library-grid.js'
	],
	[
		'admin/document-library-import.scss',
		'admin/document-library-post.scss',
		'admin/document-library-settings.scss',
		'document-library-main.scss',
		'document-library-grid.scss'
	],
	defaultConfig
);

module.exports = config.getWebpackConfig();