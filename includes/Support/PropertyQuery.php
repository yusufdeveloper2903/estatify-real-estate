<?php
/**
 * Shared listing query: used by the REST controller and by block render callbacks.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Support;

use Estatify\Meta\PropertyMeta;
use Estatify\PostTypes\PropertyPostType;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a normalized filter set into a WP_Query and a JSON-friendly result.
 */
final class PropertyQuery {

	public const MAX_PER_PAGE = 50;

	/**
	 * Sort keys accepted by `orderby`, mapped to meta keys (or null for core fields).
	 */
	public const ORDERBY = array(
		'date'     => null,
		'title'    => null,
		'price'    => PropertyMeta::PRICE,
		'area'     => PropertyMeta::AREA,
		'bedrooms' => PropertyMeta::BEDROOMS,
	);

	/**
	 * Default filter values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'type'      => array(),
			'location'  => array(),
			'feature'   => array(),
			'status'    => '',
			'min_price' => 0,
			'max_price' => 0,
			'beds'      => 0,
			'baths'     => 0,
			'search'    => '',
			'orderby'   => 'date',
			'order'     => 'desc',
			'page'      => 1,
			'per_page'  => 9,
			'exclude'   => array(),
		);
	}

	/**
	 * Normalizes untrusted input (REST params, $_GET, block attributes).
	 *
	 * @param array<string, mixed> $raw Raw values.
	 * @return array<string, mixed>
	 */
	public static function normalize( array $raw ): array {
		$f = self::defaults();

		foreach ( array( 'type', 'location', 'feature' ) as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$f[ $key ] = self::slug_list( $raw[ $key ] );
			}
		}

		if ( isset( $raw['status'] ) ) {
			$status      = sanitize_key( (string) $raw['status'] );
			$f['status'] = array_key_exists( $status, PropertyMeta::statuses() ) ? $status : '';
		}

		foreach ( array( 'min_price', 'max_price' ) as $key ) {
			if ( isset( $raw[ $key ] ) && is_numeric( $raw[ $key ] ) ) {
				$f[ $key ] = max( 0, (int) $raw[ $key ] );
			}
		}

		foreach ( array( 'beds', 'baths' ) as $key ) {
			if ( isset( $raw[ $key ] ) ) {
				$f[ $key ] = min( 20, absint( $raw[ $key ] ) );
			}
		}

		if ( isset( $raw['search'] ) ) {
			$f['search'] = mb_substr( sanitize_text_field( (string) $raw['search'] ), 0, 100 );
		}

		if ( isset( $raw['orderby'] ) && array_key_exists( (string) $raw['orderby'], self::ORDERBY ) ) {
			$f['orderby'] = (string) $raw['orderby'];
		}

		if ( isset( $raw['order'] ) ) {
			$f['order'] = 'asc' === strtolower( (string) $raw['order'] ) ? 'asc' : 'desc';
		}

		if ( isset( $raw['page'] ) ) {
			$f['page'] = max( 1, absint( $raw['page'] ) );
		}

		if ( isset( $raw['per_page'] ) ) {
			$f['per_page'] = max( 1, min( self::MAX_PER_PAGE, absint( $raw['per_page'] ) ) );
		}

		if ( isset( $raw['exclude'] ) ) {
			$f['exclude'] = array_values( array_filter( array_map( 'absint', (array) $raw['exclude'] ) ) );
		}

		return $f;
	}

	/**
	 * Builds WP_Query arguments from normalized filters.
	 *
	 * @param array<string, mixed> $f Normalized filters.
	 * @return array<string, mixed>
	 */
	public static function query_args( array $f ): array {
		$args = array(
			'post_type'           => PropertyPostType::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => $f['per_page'],
			'paged'               => $f['page'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		);

		if ( '' !== $f['search'] ) {
			$args['s'] = $f['search'];
		}

		if ( ! empty( $f['exclude'] ) ) {
			$args['post__not_in'] = $f['exclude'];
		}

		$tax_query = array();
		$tax_map   = array(
			'type'     => PropertyPostType::TAX_TYPE,
			'location' => PropertyPostType::TAX_LOCATION,
			'feature'  => PropertyPostType::TAX_FEATURE,
		);
		foreach ( $tax_map as $key => $taxonomy ) {
			if ( ! empty( $f[ $key ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $f[ $key ],
					'operator' => 'feature' === $key ? 'AND' : 'IN',
				);
			}
		}
		if ( $tax_query ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$meta_query = array( 'relation' => 'AND' );
		if ( '' !== $f['status'] ) {
			$meta_query[] = array(
				'key'   => PropertyMeta::STATUS,
				'value' => $f['status'],
			);
		}
		if ( $f['min_price'] > 0 ) {
			$meta_query[] = array(
				'key'     => PropertyMeta::PRICE,
				'value'   => $f['min_price'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $f['max_price'] > 0 ) {
			$meta_query[] = array(
				'key'     => PropertyMeta::PRICE,
				'value'   => $f['max_price'],
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $f['beds'] > 0 ) {
			$meta_query[] = array(
				'key'     => PropertyMeta::BEDROOMS,
				'value'   => $f['beds'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $f['baths'] > 0 ) {
			$meta_query[] = array(
				'key'     => PropertyMeta::BATHROOMS,
				'value'   => $f['baths'],
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}

		$order    = strtoupper( $f['order'] );
		$meta_key = self::ORDERBY[ $f['orderby'] ] ?? null;

		if ( null !== $meta_key ) {
			$meta_query['estatify_sort'] = array(
				'key'  => $meta_key,
				'type' => 'NUMERIC',
			);
			$args['orderby']             = array(
				'estatify_sort' => $order,
				'date'          => 'DESC',
			);
		} else {
			$args['orderby'] = array( $f['orderby'] => $order );
		}

		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		/**
		 * Filters the WP_Query arguments used for property listings.
		 *
		 * @param array $args WP_Query arguments.
		 * @param array $f    Normalized filters.
		 */
		return (array) apply_filters( 'estatify_property_query_args', $args, $f );
	}

	/**
	 * Runs the query and returns items, totals and (optionally) map markers.
	 *
	 * @param array<string, mixed> $f            Normalized filters.
	 * @param bool                 $with_markers Also return lightweight markers for all matches.
	 * @return array<string, mixed>
	 */
	public static function run( array $f, bool $with_markers = false ): array {
		$query = new WP_Query( self::query_args( $f ) );
		$items = array_map( array( Presenter::class, 'card' ), $query->posts );

		$result = array(
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'total_pages' => (int) $query->max_num_pages,
			'page'        => (int) $f['page'],
			'per_page'    => (int) $f['per_page'],
		);

		if ( $with_markers ) {
			$result['markers'] = self::markers( $f );
		}

		return $result;
	}

	/**
	 * Markers for every match (not just the current page).
	 *
	 * @param array<string, mixed> $f Normalized filters.
	 * @return array<int, array<string, mixed>>
	 */
	public static function markers( array $f ): array {
		$args                   = self::query_args( array_merge( $f, array( 'page' => 1 ) ) );
		$args['posts_per_page'] = 200;
		$args['no_found_rows']  = true;

		$query = new WP_Query( $args );

		return array_values(
			array_filter(
				array_map( array( Presenter::class, 'marker' ), $query->posts )
			)
		);
	}

	/**
	 * Accepts "a,b" strings or arrays and returns sanitized slugs.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	private static function slug_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}

		return array_values( array_unique( array_filter( array_map( 'sanitize_title', (array) $value ) ) ) );
	}
}
