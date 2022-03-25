const config = require( '@barn2/webpack-config' );
const WPDependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

module.exports = {
	...config,
	plugins: [
		...config.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WPDependencyExtractionWebpackPlugin({
			outputFormat: 'json',
			combineAssets: true,
			combinedOutputFile: './assets/js/wp-dependencies.json'
		}),
	],
};
