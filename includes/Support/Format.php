<?php
/**
 * Price and area formatting.
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Support;

use Estatify\Meta\PropertyMeta;

defined( 'ABSPATH' ) || exit;

/**
 * Formats values according to the plugin settings.
 */
final class Format {

	private const SQFT_PER_SQM = 10.7639;

	/**
	 * Formats an amount with the configured currency symbol.
	 *
	 * @param float $amount Amount.
	 */
	public static function money( float $amount ): string {
		$settings = Settings::all();
		$number   = number_format_i18n( $amount, 0 );
		$symbol   = (string) $settings['currency_symbol'];

		return 'after' === $settings['currency_position']
			? $number . ' ' . $symbol
			: $symbol . $number;
	}

	/**
	 * Formats a listing price; rentals get a "/mo" suffix.
	 *
	 * @param float  $amount Amount.
	 * @param string $status Listing status.
	 */
	public static function price( float $amount, string $status ): string {
		if ( $amount <= 0 ) {
			return __( 'Price on request', 'estatify' );
		}

		$money = self::money( $amount );

		if ( PropertyMeta::STATUS_RENT === $status ) {
			/* translators: %s: monthly rent, e.g. €1,800. */
			return sprintf( __( '%s /mo', 'estatify' ), $money );
		}

		return $money;
	}

	/**
	 * Converts a stored m² value to the display unit and formats it.
	 *
	 * @param float $sqm Area in square metres.
	 */
	public static function area( float $sqm ): string {
		if ( 'sqft' === Settings::get( 'area_unit' ) ) {
			/* translators: %s: area in square feet. */
			return sprintf( __( '%s ft²', 'estatify' ), number_format_i18n( $sqm * self::SQFT_PER_SQM, 0 ) );
		}

		/* translators: %s: area in square metres. */
		return sprintf( __( '%s m²', 'estatify' ), number_format_i18n( $sqm, 0 ) );
	}
}
