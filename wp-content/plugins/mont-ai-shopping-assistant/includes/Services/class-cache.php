<?php
/**
 * Transient cache helpers.
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class Cache
 */
class Cache {

	/**
	 * Get or set.
	 *
	 * @param string   $key      Key.
	 * @param callable $callback Callback.
	 * @param int      $ttl      TTL seconds.
	 * @return mixed
	 */
	public static function remember( $key, $callback, $ttl = 300 ) {
		$full = 'mont_ai_' . md5( $key );
		$val  = get_transient( $full );
		if ( false !== $val ) {
			return $val;
		}
		$val = call_user_func( $callback );
		set_transient( $full, $val, $ttl );
		return $val;
	}

	/**
	 * Forget.
	 *
	 * @param string $key Key.
	 */
	public static function forget( $key ) {
		delete_transient( 'mont_ai_' . md5( $key ) );
	}
}
