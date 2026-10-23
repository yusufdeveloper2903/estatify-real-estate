<?php
/**
 * Shared Interactivity API state for the Search, Grid and Map blocks.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Blocks;

use Estatify\Support\PropertyQuery;
use Estatify\Support\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Reads filters from the URL, runs the first query on the server and seeds
 * the `estatify` store, so the page is fully rendered before JS loads and
 * shareable URLs ("?ptype=apartment&beds=2") work.
 */
final class SearchState {

	/**
	 * URL parameter => filter key.
	 */
	public const URL_PARAMS = array(
		'q'         => 'search',
		'ptype'     => 'type',
		'loc'       => 'location',
		'status'    => 'status',
		'min_price' => 'min_price',
		'max_price' => 'max_price',
		'beds'      => 'beds',
		'sort'      => 'sort',
		'pg'        => 'page',
	);

	/**
	 * Sort options: key => [ orderby, order ].
	 */
	public const SORTS = array(
		'newest'     => array( 'date', 'desc' ),
		'price_asc'  => array( 'price', 'asc' ),
		'price_desc' => array( 'price', 'desc' ),
		'area_desc'  => array( 'area', 'desc' ),
	);

	/**
	 * Per-page value the state was last computed with.
	 *
	 * @var int|null
	 */
	private static ?int $per_page = null;

	/**
	 * Filters as the front end sees them (strings, as in form controls).
	 *
	 * @return array<string, string>
	 */
	public static function url_filters(): array {
		$filters = array(
			'search'    => '',
			'type'      => '',
			'location'  => '',
			'status'    => '',
			'min_price' => '',
			'max_price' => '',
			'beds'      => '',
			'sort'      => 'newest',
			'page'      => '1',
		);

		// Read-only filtering of public listings: no nonce needed.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		foreach ( self::URL_PARAMS as $param => $key ) {
			if ( isset( $_GET[ $param ] ) && is_scalar( $_GET[ $param ] ) ) {
				$filters[ $key ] = sanitize_text_field( wp_unslash( (string) $_GET[ $param ] ) );
			}
		}
		// phpcs:enable

		$normalized = PropertyQuery::normalize( self::to_query( $filters, 9 ) );

		return array(
			'search'    => $normalized['search'],
			'type'      => $normalized['type'][0] ?? '',
			'location'  => $normalized['location'][0] ?? '',
			'status'    => $normalized['status'],
			'min_price' => $normalized['min_price'] > 0 ? (string) $normalized['min_price'] : '',
			'max_price' => $normalized['max_price'] > 0 ? (string) $normalized['max_price'] : '',
			'beds'      => $normalized['beds'] > 0 ? (string) $normalized['beds'] : '',
			'sort'      => array_key_exists( $filters['sort'], self::SORTS ) ? $filters['sort'] : 'newest',
			'page'      => (string) $normalized['page'],
		);
	}

	/**
	 * Converts front-end filters into PropertyQuery input.
	 *
	 * @param array<string, string> $filters  Front-end filters.
	 * @param int                   $per_page Items per page.
	 * @return array<string, mixed>
	 */
	public static function to_query( array $filters, int $per_page ): array {
		$sort = self::SORTS[ $filters['sort'] ?? 'newest' ] ?? self::SORTS['newest'];

		return array(
			'search'    => $filters['search'] ?? '',
			'type'      => $filters['type'] ?? '',
			'location'  => $filters['location'] ?? '',
			'status'    => $filters['status'] ?? '',
			'min_price' => $filters['min_price'] ?? '',
			'max_price' => $filters['max_price'] ?? '',
			'beds'      => $filters['beds'] ?? '',
			'orderby'   => $sort[0],
			'order'     => $sort[1],
			'page'      => $filters['page'] ?? 1,
			'per_page'  => $per_page,
		);
	}

	/**
	 * Seeds (or re-seeds with a new page size) the global store state.
	 *
	 * @param int|null $per_page Items per page; null keeps the current value.
	 */
	public static function bootstrap( ?int $per_page = null ): void {
		if ( null === $per_page ) {
			if ( null !== self::$per_page ) {
				return;
			}
			$per_page = 9;
		}

		if ( self::$per_page === $per_page ) {
			return;
		}
		self::$per_page = $per_page;

		// wp_interactivity_state() merges recursively: clear the lists first so a
		// re-seed with a smaller page size doesn't keep stale trailing items.
		wp_interactivity_state(
			'estatify',
			array(
				'results' => null,
				'markers' => null,
			)
		);

		$filters = self::url_filters();
		$result  = PropertyQuery::run( PropertyQuery::normalize( self::to_query( $filters, $per_page ) ), true );

		wp_interactivity_state(
			'estatify',
			array_merge(
				self::labels(),
				self::derived( $result ),
				array(
					'restUrl'   => esc_url_raw( rest_url( 'estatify/v1/properties' ) ),
					'filters'   => $filters,
					'results'   => $result['items'],
					'markers'   => $result['markers'],
					'perPage'   => $per_page,
					'loading'   => false,
					'error'     => '',
					'mapCenter' => array( (float) Settings::get( 'map_lat' ), (float) Settings::get( 'map_lng' ) ),
					'mapZoom'   => (int) Settings::get( 'map_zoom' ),
				)
			)
		);
	}

	/**
	 * Values the front end recomputes after every fetch.
	 *
	 * @param array<string, mixed> $result PropertyQuery::run() result.
	 * @return array<string, mixed>
	 */
	public static function derived( array $result ): array {
		$total = (int) $result['total'];
		$pages = max( 1, (int) $result['total_pages'] );
		$page  = min( (int) $result['page'], $pages );

		return array(
			'total'        => $total,
			'totalPages'   => $pages,
			'page'         => $page,
			'hasResults'   => $total > 0,
			'isFirstPage'  => $page <= 1,
			'isLastPage'   => $page >= $pages,
			'hidePager'    => $pages <= 1,
			'resultsLabel' => self::results_label( $total ),
			/* translators: 1: current page, 2: total pages. */
			'pageLabel'    => sprintf( __( 'Page %1$d of %2$d', 'estatify' ), $page, $pages ),
		);
	}

	/**
	 * "12 properties found".
	 *
	 * @param int $total Total matches.
	 */
	public static function results_label( int $total ): string {
		if ( 0 === $total ) {
			return __( 'No properties match your filters', 'estatify' );
		}
		/* translators: %d: number of matching properties. */
		return sprintf( _n( '%d property found', '%d properties found', $total, 'estatify' ), $total );
	}

	/**
	 * Translated templates the view script uses to rebuild labels client side.
	 *
	 * @return array<string, string>
	 */
	private static function labels(): array {
		return array(
			'i18nNone'  => __( 'No properties match your filters', 'estatify' ),
			'i18nOne'   => __( '1 property found', 'estatify' ),
			/* translators: %d: number of matching properties (plural). */
			'i18nMany'  => __( '%d properties found', 'estatify' ),
			/* translators: 1: current page, 2: total pages. */
			'i18nPage'  => __( 'Page %1$d of %2$d', 'estatify' ),
			'i18nError' => __( 'Could not load listings. Please try again.', 'estatify' ),
		);
	}
}
