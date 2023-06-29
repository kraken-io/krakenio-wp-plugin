const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const { resolve } = require( 'path' );

const config = {
	...defaultConfig,
	entry: {
		kraken: resolve( process.cwd(), 'assets/src/kraken.js' ),
	},
	output: {
		filename: '[name].js',
		path: resolve( process.cwd(), 'assets', 'dist' ),
	},
};

module.exports = config;
