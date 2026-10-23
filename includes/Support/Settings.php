<?php
/**
 * Typed access to plugin options.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the `estatify_settings` option with defaults applied.
 */
final class Settings {

	public const OPTION = 'estatify_settings';

	/**
	 * Default values for every setting.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'currency_symbol'   => '€',
			'currency_position' => 'before',
			'area_unit'         => 'sqm',
			'mortgage_rate'     => 3.5,
			'map_lat'           => 38.7223,
			'map_lng'           => -9.1393,
			'map_zoom'          => 13,
		);
	}

	/**
	 * All settings, merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * A single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	/**
	 * Sanitizes a raw settings array (Settings API callback).
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();

		$symbol = isset( $input['currency_symbol'] ) ? sanitize_text_field( wp_unslash( (string) $input['currency_symbol'] ) ) : $defaults['currency_symbol'];
		$symbol = mb_substr( $symbol, 0, 5 );

		$position = isset( $input['currency_position'] ) && 'after' === $input['currency_position'] ? 'after' : 'before';
		$unit     = isset( $input['area_unit'] ) && 'sqft' === $input['area_unit'] ? 'sqft' : 'sqm';

		$rate = isset( $input['mortgage_rate'] ) ? (float) $input['mortgage_rate'] : $defaults['mortgage_rate'];
		$lat  = isset( $input['map_lat'] ) ? (float) $input['map_lat'] : $defaults['map_lat'];
		$lng  = isset( $input['map_lng'] ) ? (float) $input['map_lng'] : $defaults['map_lng'];
		$zoom = isset( $input['map_zoom'] ) ? absint( $input['map_zoom'] ) : $defaults['map_zoom'];

		return array(
			'currency_symbol'   => '' === $symbol ? $defaults['currency_symbol'] : $symbol,
			'currency_position' => $position,
			'area_unit'         => $unit,
			'mortgage_rate'     => max( 0.0, min( 25.0, round( $rate, 2 ) ) ),
			'map_lat'           => max( -90.0, min( 90.0, $lat ) ),
			'map_lng'           => max( -180.0, min( 180.0, $lng ) ),
			'map_zoom'          => max( 2, min( 19, $zoom ) ),
		);
	}
}
