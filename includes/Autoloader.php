<?php
/**
 * Minimal PSR-4 autoloader (no Composer needed at runtime).
 *
 * @package Estatify
 */

declare( strict_types=1 );

namespace Estatify;

defined( 'ABSPATH' ) || exit;

/**
 * Maps `Estatify\Foo\Bar` to `includes/Foo/Bar.php`.
 */
final class Autoloader {

	/**
	 * Registers the autoloader for a namespace prefix.
	 *
	 * @param string $prefix   Namespace prefix, without trailing backslash.
	 * @param string $base_dir Directory that holds the classes.
	 */
	public static function register( string $prefix, string $base_dir ): void {
		$prefix = trim( $prefix, '\\' ) . '\\';

		spl_autoload_register(
			static function ( string $class ) use ( $prefix, $base_dir ): void {
				if ( ! str_starts_with( $class, $prefix ) ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';

				if ( is_readable( $file ) ) {
					require_once $file;
				}
			}
		);
	}
}
