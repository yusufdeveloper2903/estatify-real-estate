<?php
/**
 * Block templates for single properties and property archives.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Blocks;

use Estatify\PostTypes\PropertyPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin templates with `register_block_template()` (WP 6.7+).
 *
 * Templates use the active theme's `header` and `footer` template parts, so
 * they blend into any block theme. A theme can still override them by
 * shipping its own `single-property.html` / `archive-property.html`, and
 * users can customise them in the Site Editor.
 */
final class Templates {

	/**
	 * Hooks.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers the templates.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		$archive = $this->load( 'archive-property' );

		$templates = array(
			'single-property'                                  => array(
				'title'       => __( 'Single Property', 'estatify' ),
				'description' => __( 'Displays a single property with gallery, specs, map and mortgage calculator.', 'estatify' ),
				'content'     => $this->load( 'single-property' ),
				'post_types'  => array( PropertyPostType::POST_TYPE ),
			),
			'archive-property'                                 => array(
				'title'       => __( 'Property Archive', 'estatify' ),
				'description' => __( 'Displays the property search with a results grid.', 'estatify' ),
				'content'     => $archive,
			),
			'taxonomy-' . PropertyPostType::TAX_TYPE           => array(
				'title'       => __( 'Property Type Archive', 'estatify' ),
				'description' => __( 'Displays properties of one type.', 'estatify' ),
				'content'     => $this->load( 'taxonomy-property' ),
			),
			'taxonomy-' . PropertyPostType::TAX_LOCATION       => array(
				'title'       => __( 'Property Location Archive', 'estatify' ),
				'description' => __( 'Displays properties in one location.', 'estatify' ),
				'content'     => $this->load( 'taxonomy-property' ),
			),
			'taxonomy-' . PropertyPostType::TAX_FEATURE        => array(
				'title'       => __( 'Property Feature Archive', 'estatify' ),
				'description' => __( 'Displays properties with one feature.', 'estatify' ),
				'content'     => $this->load( 'taxonomy-property' ),
			),
		);

		foreach ( $templates as $slug => $args ) {
			register_block_template( 'estatify//' . $slug, $args );
		}
	}

	/**
	 * Loads block markup from /templates. Template files are PHP so their
	 * headings and copy go through the translation functions.
	 *
	 * @param string $name File name without extension.
	 */
	private function load( string $name ): string {
		$file = ESTATIFY_DIR . 'templates/' . $name . '.php';
		if ( ! is_readable( $file ) ) {
			return '';
		}

		$markup = require $file;
		return is_string( $markup ) ? $markup : '';
	}
}
