<?php
/**
 * WP-CLI commands.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\CLI;

use Estatify\Demo\Seeder;
use Estatify\Support\PropertyQuery;
use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Manage Estatify demo content and listings.
 */
final class Command {

	/**
	 * Imports the demo listings, photos, pages and menu.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : Import again even if demo content was already imported.
	 *
	 * ## EXAMPLES
	 *
	 *     wp estatify seed
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function seed( array $args, array $assoc_args ): void {
		( new Seeder( static fn( string $message ) => WP_CLI::log( $message ) ) )
			->seed( (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false ) );

		WP_CLI::success( 'Demo content ready.' );
	}

	/**
	 * Lists properties using the same filters as the REST endpoint.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<slug>]
	 * : Property type slug.
	 *
	 * [--location=<slug>]
	 * : Location slug.
	 *
	 * [--status=<status>]
	 * : for_sale, for_rent or sold.
	 *
	 * [--max_price=<amount>]
	 * : Maximum price.
	 *
	 * [--format=<format>]
	 * : table, csv or json.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Associative arguments.
	 */
	public function list( array $args, array $assoc_args ): void {
		$result = PropertyQuery::run( PropertyQuery::normalize( array_merge( $assoc_args, array( 'per_page' => 50 ) ) ) );

		WP_CLI\Utils\format_items(
			$assoc_args['format'] ?? 'table',
			$result['items'],
			array( 'id', 'title', 'priceFormatted', 'statusLabel', 'bedrooms', 'areaFormatted', 'location' )
		);
	}
}
