<?php
/**
 * Simple plugin autoloader.
 *
 * @package WP3DS
 */

namespace WP3DS;

defined( 'ABSPATH' ) || exit;

class Autoloader {
	public static function register(): void {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	public static function autoload( string $class ): void {
		if ( 0 !== strpos( $class, 'WP3DS\\' ) ) {
			return;
		}

		$relative = str_replace( 'WP3DS\\', '', $class );
		$relative = str_replace( '\\', '/', $relative );
		$file     = WP3DS_PATH . 'includes/' . $relative . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
