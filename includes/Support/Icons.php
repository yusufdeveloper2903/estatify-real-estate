<?php
/**
 * Inline SVG icons (stroke icons, 24×24 grid).
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Returns decorative inline SVGs. Output is static markup we control.
 */
final class Icons {

	private const PATHS = array(
		'bed'      => '<path d="M3 18v-6.5A2.5 2.5 0 0 1 5.5 9h13a2.5 2.5 0 0 1 2.5 2.5V18"/><path d="M3 15h18"/><path d="M5 9V6.5A1.5 1.5 0 0 1 6.5 5h11A1.5 1.5 0 0 1 19 6.5V9"/><path d="M9 9V7.5h6V9"/><path d="M3 18v1.5M21 18v1.5"/>',
		'bath'     => '<path d="M4 12h16v2a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5v-2Z"/><path d="M6 12V5.5A1.5 1.5 0 0 1 7.5 4h0A1.5 1.5 0 0 1 9 5.5V6"/><path d="M7 19l-1 2M17 19l1 2"/>',
		'area'     => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/><rect x="8" y="8" width="8" height="8" rx="1"/>',
		'pin'      => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'home'     => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6h-4v6H5a1 1 0 0 1-1-1v-9.5Z"/>',
		'tag'      => '<path d="M3 12.6V4a1 1 0 0 1 1-1h8.6a1 1 0 0 1 .7.3l7.4 7.4a1 1 0 0 1 0 1.4l-8.6 8.6a1 1 0 0 1-1.4 0L3.3 13.3a1 1 0 0 1-.3-.7Z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
		'check'    => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'chevronL' => '<path d="m14.5 6-6 6 6 6"/>',
		'chevronR' => '<path d="m9.5 6 6 6-6 6"/>',
		'ruler'    => '<path d="M4 16 16 4l4 4L8 20l-4-4Z"/><path d="m8 12 2 2M11 9l2 2M14 6l2 2"/>',
	);

	/**
	 * Returns an icon's SVG markup.
	 *
	 * @param string $name Icon name.
	 * @param int    $size Pixel size.
	 */
	public static function get( string $name, int $size = 18 ): string {
		if ( ! isset( self::PATHS[ $name ] ) ) {
			return '';
		}

		return sprintf(
			'<svg class="estatify-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
			$size,
			self::PATHS[ $name ]
		);
	}

	/**
	 * Allowed SVG tags for wp_kses().
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function kses(): array {
		$common = array(
			'd'               => true,
			'x'               => true,
			'y'               => true,
			'cx'              => true,
			'cy'              => true,
			'r'               => true,
			'rx'              => true,
			'width'           => true,
			'height'          => true,
			'class'           => true,
			'viewbox'         => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'aria-hidden'     => true,
			'focusable'       => true,
		);

		return array(
			'svg'    => $common,
			'path'   => $common,
			'circle' => $common,
			'rect'   => $common,
		);
	}

	/**
	 * Echoes an icon through wp_kses.
	 *
	 * @param string $name Icon name.
	 * @param int    $size Pixel size.
	 */
	public static function render( string $name, int $size = 18 ): void {
		echo wp_kses( self::get( $name, $size ), self::kses() );
	}
}
