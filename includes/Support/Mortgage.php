<?php
/**
 * Mortgage maths (mirrors src/shared/mortgage.js).
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Fixed-rate, monthly-amortised loan estimate.
 */
final class Mortgage {

	/**
	 * Calculates the monthly payment and totals.
	 *
	 * @param float $price        Home price.
	 * @param float $down_percent Down payment in percent of the price.
	 * @param float $annual_rate  Nominal annual interest rate in percent.
	 * @param int   $years        Loan term in years.
	 * @return array{downAmount: float, principal: float, monthly: float, interest: float, totalPaid: float, principalShare: int}
	 */
	public static function calculate( float $price, float $down_percent, float $annual_rate, int $years ): array {
		$price        = max( 0.0, $price );
		$down_percent = min( 100.0, max( 0.0, $down_percent ) );
		$annual_rate  = max( 0.0, $annual_rate );
		$months       = max( 1, $years * 12 );

		$down      = $price * $down_percent / 100;
		$principal = $price - $down;
		$r         = $annual_rate / 100 / 12;

		$monthly = 0.0 === $r
			? $principal / $months
			: $principal * $r * ( ( 1 + $r ) ** $months ) / ( ( ( 1 + $r ) ** $months ) - 1 );

		$total = $monthly * $months;

		return array(
			'downAmount'     => $down,
			'principal'      => $principal,
			'monthly'        => $monthly,
			'interest'       => max( 0.0, $total - $principal ),
			'totalPaid'      => $total,
			'principalShare' => $total > 0 ? (int) round( $principal / $total * 100 ) : 100,
		);
	}
}
