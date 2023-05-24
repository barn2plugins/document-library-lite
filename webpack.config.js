const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const Barn2Configuration = require( '@barn2media/webpack-config' );

const config = new Barn2Configuration(
	[
		'admin/document-library-post/index.js', 
		'admin/document-library-settings/index.js', 
		'document-library-main.js'
	],
	[
		'admin/document-library-import.scss', 
		'admin/document-library-post.scss', 
		'admin/document-library-settings.scss', 
		'document-library-main.scss'
	],
	defaultConfig
);

module.exports = config.getWebpackConfig();