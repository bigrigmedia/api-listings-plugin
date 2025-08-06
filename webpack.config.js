// ./webpack.config.js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

frontend = {};

if (process.env.npm_lifecycle_event === 'build' || process.env.npm_lifecycle_event === 'start') {
	frontend = {
		frontend: './src/frontend.js',
	};
}

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		...frontend,
	},
};