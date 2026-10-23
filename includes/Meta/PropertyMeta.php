<?php
/**
 * Registered post meta for properties.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Meta;

use Estatify\PostTypes\PropertyPostType;

defined( 'ABSPATH' ) || exit;

/**
 * Registers typed, sanitized, REST-exposed meta fields.
 *
 * Keys are prefixed with an underscore so they stay out of the classic
 * "Custom Fields" box; an auth callback grants access to editors.
 */
final class PropertyMeta {

	public const PRICE     = '_estatify_price';
	public const BEDROOMS  = '_estatify_bedrooms';
	public const BATHROOMS = '_estatify_bathrooms';
	public const AREA      = '_estatify_area';
	public const ADDRESS   = '_estatify_address';
	public const LAT       = '_estatify_lat';
	public const LNG       = '_estatify_lng';
	public const STATUS    = '_estatify_status';
	public const GALLERY   = '_estatify_gallery';

	public const STATUS_SALE = 'for_sale';
	public const STATUS_RENT = 'for_rent';
	public const STATUS_SOLD = 'sold';

	/**
	 * Hooks registration into `init`.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Allowed listing statuses with their labels.
	 *
	 * @return array<string, string>
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_SALE => __( 'For sale', 'estatify' ),
			self::STATUS_RENT => __( 'For rent', 'estatify' ),
			self::STATUS_SOLD => __( 'Sold', 'estatify' ),
		);
	}

	/**
	 * Field definitions: key => [ type, default, sanitize callback, extra schema ].
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(): array {
		return array(
			self::PRICE     => array(
				'type'              => 'number',
				'default'           => 0,
				'description'       => __( 'Asking price (sale) or monthly rent.', 'estatify' ),
				'sanitize_callback' => array( self::class, 'sanitize_non_negative_number' ),
				'schema'            => array( 'minimum' => 0 ),
			),
			self::BEDROOMS  => array(
				'type'              => 'integer',
				'default'           => 0,
				'description'       => __( 'Number of bedrooms.', 'estatify' ),
				'sanitize_callback' => 'absint',
				'schema'            => array(
					'minimum' => 0,
					'maximum' => 50,
				),
			),
			self::BATHROOMS => array(
				'type'              => 'integer',
				'default'           => 0,
				'description'       => __( 'Number of bathrooms.', 'estatify' ),
				'sanitize_callback' => 'absint',
				'schema'            => array(
					'minimum' => 0,
					'maximum' => 50,
				),
			),
			self::AREA      => array(
				'type'              => 'number',
				'default'           => 0,
				'description'       => __( 'Living area in square metres.', 'estatify' ),
				'sanitize_callback' => array( self::class, 'sanitize_non_negative_number' ),
				'schema'            => array( 'minimum' => 0 ),
			),
			self::ADDRESS   => array(
				'type'              => 'string',
				'default'           => '',
				'description'       => __( 'Street address.', 'estatify' ),
				'sanitize_callback' => 'sanitize_text_field',
				'schema'            => array(),
			),
			self::LAT       => array(
				'type'              => 'number',
				'default'           => 0,
				'description'       => __( 'Latitude (WGS84).', 'estatify' ),
				'sanitize_callback' => array( self::class, 'sanitize_latitude' ),
				'schema'            => array(
					'minimum' => -90,
					'maximum' => 90,
				),
			),
			self::LNG       => array(
				'type'              => 'number',
				'default'           => 0,
				'description'       => __( 'Longitude (WGS84).', 'estatify' ),
				'sanitize_callback' => array( self::class, 'sanitize_longitude' ),
				'schema'            => array(
					'minimum' => -180,
					'maximum' => 180,
				),
			),
			self::STATUS    => array(
				'type'              => 'string',
				'default'           => self::STATUS_SALE,
				'description'       => __( 'Listing status.', 'estatify' ),
				'sanitize_callback' => array( self::class, 'sanitize_status' ),
				'schema'            => array( 'enum' => array_keys( self::statuses() ) ),
			),
		);
	}

	/**
	 * Registers every field with `register_post_meta()`.
	 */
	public function register(): void {
		foreach ( self::fields() as $key => $field ) {
			register_post_meta(
				PropertyPostType::POST_TYPE,
				$key,
				array(
					'type'              => $field['type'],
					'description'       => $field['description'],
					'single'            => true,
					'default'           => $field['default'],
					'sanitize_callback' => $field['sanitize_callback'],
					'auth_callback'     => array( self::class, 'can_edit' ),
					'show_in_rest'      => array(
						'schema' => array_merge(
							array(
								'type'    => $field['type'],
								'default' => $field['default'],
							),
							$field['schema']
						),
					),
				)
			);
		}

		register_post_meta(
			PropertyPostType::POST_TYPE,
			self::GALLERY,
			array(
				'type'              => 'array',
				'description'       => __( 'Gallery attachment IDs.', 'estatify' ),
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitize_id_list' ),
				'auth_callback'     => array( self::class, 'can_edit' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
			)
		);
	}

	/**
	 * Only users who can edit the post may write its meta.
	 *
	 * @param bool   $allowed  Whether the user can add the meta.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 */
	public static function can_edit( $allowed, $meta_key, $post_id ): bool {
		return current_user_can( 'edit_post', (int) $post_id );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_non_negative_number( $value ): float {
		return max( 0.0, round( (float) $value, 2 ) );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_latitude( $value ): float {
		return max( -90.0, min( 90.0, round( (float) $value, 7 ) ) );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_longitude( $value ): float {
		return max( -180.0, min( 180.0, round( (float) $value, 7 ) ) );
	}

	/**
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_status( $value ): string {
		$value = sanitize_key( (string) $value );
		return array_key_exists( $value, self::statuses() ) ? $value : self::STATUS_SALE;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	public static function sanitize_id_list( $value ): array {
		return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
	}
}
