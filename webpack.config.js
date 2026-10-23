/**
 * Extends the default @wordpress/scripts config with the non-block editor
 * entry (the property meta sidebar panel). Blocks are discovered through
 * their block.json files; view scripts are built as ES modules.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const [ scriptConfig, moduleConfig ] = Array.isArray( defaultConfig )
	? defaultConfig
	: [ defaultConfig ];

module.exports = [
	{
		...scriptConfig,
		entry: async () => ( {
			...( await ( typeof scriptConfig.entry === 'function'
				? scriptConfig.entry()
				: scriptConfig.entry ) ),
			'editor/index': './src/editor/index.js',
		} ),
	},
	moduleConfig,
].filter( Boolean );
