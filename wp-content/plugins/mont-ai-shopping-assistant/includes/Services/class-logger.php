<?php
/**
 * Simple file/option logger.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

use Mont_AI_Assistant\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 */
class Logger {

	/**
	 * Log a message when logging is enabled.
	 *
	 * @param string $message Message.
	 * @param array  $context Context.
	 */
	public static function log( $message, $context = array() ) {
		$settings = Plugin::settings();
		if ( empty( $settings['enable_logging'] ) ) {
			return;
		}

		$line = sprintf(
			'[%s] %s %s',
			gmdate( 'Y-m-d H:i:s' ),
			$message,
			$context ? wp_json_encode( $context ) : ''
		);

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Mont AI] ' . $line );
		}

		$logs   = get_option( 'mont_ai_logs', array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		array_unshift( $logs, $line );
		$logs = array_slice( $logs, 0, 200 );
		update_option( 'mont_ai_logs', $logs, false );
	}

	/**
	 * Recent log lines.
	 *
	 * @param int $limit Limit.
	 * @return array
	 */
	public static function recent( $limit = 50 ) {
		$logs = get_option( 'mont_ai_logs', array() );
		return array_slice( is_array( $logs ) ? $logs : array(), 0, $limit );
	}
}
