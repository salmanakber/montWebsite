<?php
namespace FikenBilag;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fiken_Status {
	/**
	 * Fetch live status from the central server.
	 * @return array|\WP_Error
	 */
	public static function fetch_live() {
		// Use Fiken-specific GET endpoint with current shop URL to ensure correct plan mapping
		$shop = (string) home_url();
		$qs   = rawurlencode( $shop );
		$url  = 'https://peki.no/stripe-connect/fiken/status.php?shop=' . $qs . '&shop_url=' . $qs . '&v=3';
		$company_slug = (string) get_option( 'pekifiken_company_slug', (string) get_option( 'fiken_company_slug', '' ) );
		if ( $company_slug !== '' ) {
			$url .= '&company_slug=' . rawurlencode( $company_slug );
		}
		$connection_id = (string) get_option( 'pekifiken_connection_id', '' );
		if ( $connection_id !== '' ) {
			$url .= '&connection_id=' . rawurlencode( $connection_id );
		}

		$response = wp_remote_get( $url, array( 'timeout' => 12 ) );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );
		$body = json_decode( $body_raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$body = null;
		}
		if ( $code >= 200 && $code < 300 && is_array( $body ) ) {
			return $body;
		}
		return new \WP_Error( 'status_bad_http', 'Status endpoint HTTP ' . $code, array( 'body' => $body_raw ) );
	}

	/**
	 * Get cached status or fetch live if cache too old. Cache for $max_age seconds.
	 */
	public static function fetch_cached_or_live( int $max_age = 300 ) {
		$cached = get_transient( 'fiken_status_cache' );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$live = self::fetch_live();
		if ( is_wp_error( $live ) ) {
			return $live;
		}
		self::update_cache_from_array( $live, $max_age );
		return $live;
	}

	/**
	 * Update local cache/options from status array.
	 */
	public static function update_cache_from_array( array $status, int $ttl = 300 ): void {
		set_transient( 'fiken_status_cache', $status, $ttl );
		update_option( 'fiken_last_status', $status, false );
		if ( isset( $status['quota'] ) && is_array( $status['quota'] ) && isset( $status['quota']['used'] ) ) {
			update_option( 'fiken_used_count', (int) $status['quota']['used'], false );
		}
	}

	/** Cron: refresh live status and update cache */
	public static function cron_refresh(): void {
		$live = self::fetch_live();
		if ( ! is_wp_error( $live ) && is_array( $live ) ) {
			self::update_cache_from_array( $live, 300 );
		}
	}
}


