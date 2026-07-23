<?php
/**
 * PSR-4 style autoloader for Mont_AI_Assistant namespace.
 *
 * Maps Mont_AI_Assistant\Admin\Settings → includes/Admin/class-settings.php
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant;

defined( 'ABSPATH' ) || exit;

/**
 * Class Autoloader
 */
class Autoloader {

	/**
	 * Register spl autoload.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class Fully qualified class name.
	 */
	public static function load( $class ) {
		$prefix = __NAMESPACE__ . '\\';
		if ( strpos( $class, $prefix ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$parts    = explode( '\\', $relative );
		$class_name = array_pop( $parts );

		// Convert Class_Name → class-class-name.php
		$file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		$dir = MONT_AI_PATH . 'includes/';
		if ( ! empty( $parts ) ) {
			$dir .= implode( '/', $parts ) . '/';
		}

		$path = $dir . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
