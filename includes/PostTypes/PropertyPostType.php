<?php
/**
 * Property custom post type and taxonomies.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `property` CPT plus type, location and feature taxonomies.
 */
final class PropertyPostType {

	public const POST_TYPE    = 'property';
	public const TAX_TYPE     = 'property_type';
	public const TAX_LOCATION = 'property_location';
	public const TAX_FEATURE  = 'property_feature';

	/**
	 * Hooks registration into `init`.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers the post type and taxonomies.
	 */
	public function register(): void {
		$this->register_post_type();
		$this->register_taxonomies();
	}

	/**
	 * The `property` post type.
	 */
	private function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => _x( 'Properties', 'post type general name', 'estatify' ),
					'singular_name'      => _x( 'Property', 'post type singular name', 'estatify' ),
					'menu_name'          => _x( 'Properties', 'admin menu', 'estatify' ),
					'add_new'            => __( 'Add New', 'estatify' ),
					'add_new_item'       => __( 'Add New Property', 'estatify' ),
					'edit_item'          => __( 'Edit Property', 'estatify' ),
					'new_item'           => __( 'New Property', 'estatify' ),
					'view_item'          => __( 'View Property', 'estatify' ),
					'view_items'         => __( 'View Properties', 'estatify' ),
					'search_items'       => __( 'Search Properties', 'estatify' ),
					'not_found'          => __( 'No properties found.', 'estatify' ),
					'not_found_in_trash' => __( 'No properties found in Trash.', 'estatify' ),
					'all_items'          => __( 'All Properties', 'estatify' ),
					'archives'           => __( 'Property Archives', 'estatify' ),
					'featured_image'     => __( 'Cover photo', 'estatify' ),
					'set_featured_image' => __( 'Set cover photo', 'estatify' ),
				),
				'public'          => true,
				'has_archive'     => 'properties',
				'rewrite'         => array(
					'slug'       => 'property',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-building',
				'menu_position'   => 20,
				'show_in_rest'    => true,
				'rest_base'       => 'properties',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'revisions', 'author' ),
				'template'        => array(
					array(
						'core/paragraph',
						array( 'placeholder' => __( 'Describe the property: light, layout, neighbourhood…', 'estatify' ) ),
					),
				),
			)
		);
	}

	/**
	 * Type, location and feature taxonomies.
	 */
	private function register_taxonomies(): void {
		$common = array(
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
		);

		register_taxonomy(
			self::TAX_TYPE,
			self::POST_TYPE,
			$common + array(
				'hierarchical' => true,
				'rewrite'      => array( 'slug' => 'property-type' ),
				'labels'       => array(
					'name'          => _x( 'Property Types', 'taxonomy general name', 'estatify' ),
					'singular_name' => _x( 'Property Type', 'taxonomy singular name', 'estatify' ),
					'all_items'     => __( 'All Types', 'estatify' ),
					'edit_item'     => __( 'Edit Type', 'estatify' ),
					'add_new_item'  => __( 'Add New Type', 'estatify' ),
					'menu_name'     => __( 'Types', 'estatify' ),
				),
			)
		);

		register_taxonomy(
			self::TAX_LOCATION,
			self::POST_TYPE,
			$common + array(
				'hierarchical' => true,
				'rewrite'      => array( 'slug' => 'location' ),
				'labels'       => array(
					'name'          => _x( 'Locations', 'taxonomy general name', 'estatify' ),
					'singular_name' => _x( 'Location', 'taxonomy singular name', 'estatify' ),
					'all_items'     => __( 'All Locations', 'estatify' ),
					'edit_item'     => __( 'Edit Location', 'estatify' ),
					'add_new_item'  => __( 'Add New Location', 'estatify' ),
					'menu_name'     => __( 'Locations', 'estatify' ),
				),
			)
		);

		register_taxonomy(
			self::TAX_FEATURE,
			self::POST_TYPE,
			$common + array(
				'hierarchical' => false,
				'rewrite'      => array( 'slug' => 'feature' ),
				'labels'       => array(
					'name'          => _x( 'Features', 'taxonomy general name', 'estatify' ),
					'singular_name' => _x( 'Feature', 'taxonomy singular name', 'estatify' ),
					'all_items'     => __( 'All Features', 'estatify' ),
					'edit_item'     => __( 'Edit Feature', 'estatify' ),
					'add_new_item'  => __( 'Add New Feature', 'estatify' ),
					'menu_name'     => __( 'Features', 'estatify' ),
				),
			)
		);
	}
}
