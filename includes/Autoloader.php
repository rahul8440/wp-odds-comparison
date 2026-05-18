<?php
/**
 * PSR-4 style autoloader for plugin classes.
 *
 * @package WPOddsComparison
 */

declare(strict_types=1);

namespace WPOddsComparison;

/**
 * Registers a simple PSR-4 autoloader.
 */
final class Autoloader {

	/**
	 * Register autoloader for a namespace prefix.
	 *
	 * @param string $prefix    Namespace prefix (e.g. WPOddsComparison).
	 * @param string $base_dir  Base directory for class files.
	 */
	public static function register( string $prefix, string $base_dir ): void {
		$prefix    = rtrim( $prefix, '\\' ) . '\\';
		$base_dir  = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;

		spl_autoload_register(
			static function ( string $class ) use ( $prefix, $base_dir ): void {
				if ( strpos( $class, $prefix ) !== 0 ) {
					return;
				}

				$relative = substr( $class, strlen( $prefix ) );
				$file     = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';

				if ( is_readable( $file ) ) {
					require $file;
				}
			}
		);
	}
}
