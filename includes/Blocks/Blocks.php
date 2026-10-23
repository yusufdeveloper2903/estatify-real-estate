<?php
/**
 * Block registration and editor assets.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Blocks;

use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use Estatify\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every block from the compiled `build/` folder.
 */
final class Blocks {

	/**
	 * Hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'block_categories_all', array( $this, 'add_category' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
	}

	/**
	 * Registers blocks via the blocks manifest (WP 6.8+), falling back to
	 * scanning block.json files on WP 6.7.
	 */
	public function register_blocks(): void {
		$build    = ESTATIFY_DIR . 'build/blocks';
		$manifest = ESTATIFY_DIR . 'build/blocks-manifest.php';

		if ( ! is_dir( $build ) ) {
			return;
		}

		if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) && is_readable( $manifest ) ) {
			wp_register_block_types_from_metadata_collection( $build, $manifest );
			return;
		}

		foreach ( (array) glob( $build . '/*/block.json' ) as $block_json ) {
			register_block_type( dirname( (string) $block_json ) );
		}
	}

	/**
	 * Adds the "Estatify" inserter category.
	 *
	 * @param array<int, array<string, mixed>> $categories Categories.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_category( array $categories ): array {
		array_unshift(
			$categories,
			array(
				'slug'  => 'estatify',
				'title' => __( 'Estatify', 'estatify' ),
				'icon'  => 'building',
			)
		);
		return $categories;
	}

	/**
	 * Loads the property sidebar panel and shared editor settings.
	 */
	public function enqueue_editor_assets(): void {
		$asset_file = ESTATIFY_DIR . 'build/editor/index.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'estatify-editor',
			ESTATIFY_URL . 'build/editor/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		if ( is_readable( ESTATIFY_DIR . 'build/editor/index.css' ) ) {
			wp_enqueue_style( 'estatify-editor', ESTATIFY_URL . 'build/editor/index.css', array(), $asset['version'] );
		}

		wp_set_script_translations( 'estatify-editor', 'estatify', ESTATIFY_DIR . 'languages' );

		wp_add_inline_script(
			'estatify-editor',
			'window.estatifyEditor = ' . wp_json_encode(
				array(
					'postType' => PropertyPostType::POST_TYPE,
					'statuses' => PropertyMeta::statuses(),
					'settings' => Settings::all(),
					'metaKeys' => array(
						'price'     => PropertyMeta::PRICE,
						'bedrooms'  => PropertyMeta::BEDROOMS,
						'bathrooms' => PropertyMeta::BATHROOMS,
						'area'      => PropertyMeta::AREA,
						'address'   => PropertyMeta::ADDRESS,
						'lat'       => PropertyMeta::LAT,
						'lng'       => PropertyMeta::LNG,
						'status'    => PropertyMeta::STATUS,
						'gallery'   => PropertyMeta::GALLERY,
					),
				)
			) . ';',
			'before'
		);
	}
}
