<?php
/**
 * Polylang-style language URL prefixes: /en/, /it/, /nb/, /vi/
 *
 * Controlled by Mont DeepL setting `polylang_style` (works even when DeepL
 * translation itself is disabled).
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DC_Language_Urls {

	/** @var string|null Detected lang from request prefix */
	private static $request_lang = null;

	/** @var bool Whether this request originally had a lang prefix */
	private static $had_prefix = false;

	/** @var bool Guard against recursive home_url filtering */
	private static $filtering = false;

	public static function init() {
		// After all plugins load (DeepL settings available), strip /{lang}/ before WP routing.
		add_action( 'plugins_loaded', array( __CLASS__, 'bootstrap_request' ), 1 );
		add_action( 'init', array( __CLASS__, 'maybe_redirect_unprefixed' ), 1 );

		add_filter( 'home_url', array( __CLASS__, 'filter_home_url' ), 20, 4 );
		add_filter( 'post_link', array( __CLASS__, 'filter_link' ), 20, 2 );
		add_filter( 'page_link', array( __CLASS__, 'filter_link' ), 20, 2 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_link' ), 20, 2 );
		add_filter( 'term_link', array( __CLASS__, 'filter_link' ), 20, 2 );
		add_filter( 'attachment_link', array( __CLASS__, 'filter_link' ), 20, 2 );
		add_filter( 'author_link', array( __CLASS__, 'filter_link' ), 20, 2 );
		add_filter( 'year_link', array( __CLASS__, 'filter_link' ), 20, 1 );
		add_filter( 'month_link', array( __CLASS__, 'filter_link' ), 20, 1 );
		add_filter( 'day_link', array( __CLASS__, 'filter_link' ), 20, 1 );
		add_filter( 'search_link', array( __CLASS__, 'filter_link' ), 20, 1 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'filter_redirect_canonical' ), 20, 2 );

		add_action( 'update_option_mont_deepl_settings', array( __CLASS__, 'on_settings_saved' ), 10, 2 );
	}

	public static function enabled() {
		return class_exists( __NAMESPACE__ . '\\DC_Region_Currency' )
			&& DC_Region_Currency::polylang_style_enabled();
	}

	/** @return string[] */
	public static function get_lang_codes() {
		$codes = array();
		if ( class_exists( __NAMESPACE__ . '\\DC_Region_Currency' ) ) {
			foreach ( DC_Region_Currency::get_regions() as $region ) {
				if ( ! empty( $region['lang'] ) ) {
					$codes[] = strtolower( $region['lang'] );
				}
			}
		}
		$codes = array_values( array_unique( array_filter( $codes ) ) );
		return $codes ? $codes : array( 'en', 'it', 'nb', 'vi' );
	}

	/** Unfiltered site path prefix (no /en/ injected). */
	private static function site_home_path() {
		self::$filtering = true;
		$path            = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		self::$filtering = false;
		return untrailingslashit( $path );
	}

	public static function get_request_lang() {
		if ( self::$request_lang ) {
			return self::$request_lang;
		}
		if ( class_exists( __NAMESPACE__ . '\\DC_Region_Currency' ) ) {
			return DC_Region_Currency::get_current_lang();
		}
		return 'en';
	}

	/**
	 * Strip /{lang}/ from REQUEST_URI before WordPress routing.
	 */
	public static function bootstrap_request() {
		if ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		if ( ! self::enabled() ) {
			return;
		}
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$uri  = wp_unslash( $_SERVER['REQUEST_URI'] );
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$query = wp_parse_url( $uri, PHP_URL_QUERY );

		$home_path = self::site_home_path();
		$home_path = untrailingslashit( $home_path );
		if ( $home_path && $home_path !== '/' && strpos( $path, $home_path ) === 0 ) {
			$rel = substr( $path, strlen( $home_path ) );
		} else {
			$rel = $path;
		}
		$rel = '/' . ltrim( (string) $rel, '/' );

		$codes = self::get_lang_codes();
		$re    = '#^/(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(/|$|\?)#i';

		if ( ! preg_match( $re, $rel, $m ) ) {
			self::$had_prefix   = false;
			self::$request_lang = null;
			return;
		}

		$lang = strtolower( $m[1] );
		self::$had_prefix   = true;
		self::$request_lang = $lang;

		// Persist region from URL language.
		if ( class_exists( __NAMESPACE__ . '\\DC_Region_Currency' ) ) {
			$slug = DC_Region_Currency::lang_to_region( $lang );
			if ( $slug ) {
				DC_Region_Currency::set_region_cookie( $slug );
			}
		}

		// Path without language segment.
		$stripped_rel = preg_replace( $re, '/', $rel, 1 );
		if ( $stripped_rel === '' || $stripped_rel === false ) {
			$stripped_rel = '/';
		}
		// Collapse duplicate slashes.
		$stripped_rel = preg_replace( '#/+#', '/', $stripped_rel );

		$new_path = ( $home_path && $home_path !== '/' )
			? untrailingslashit( $home_path ) . $stripped_rel
			: $stripped_rel;
		$new_path = preg_replace( '#/+#', '/', $new_path );
		if ( $new_path !== '/' ) {
			$new_path = untrailingslashit( $new_path ) . ( substr( $path, -1 ) === '/' ? '/' : '' );
		}

		$new_uri = $new_path;
		if ( $query ) {
			$new_uri .= '?' . $query;
		}

		$_SERVER['REQUEST_URI'] = $new_uri;
	}

	/**
	 * Redirect bare URLs → /{lang}/… so language is always visible in the path.
	 */
	public static function maybe_redirect_unprefixed() {
		if ( ! self::enabled() || is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent() ) {
			return;
		}
		if ( self::$had_prefix ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		if ( self::should_skip_path( $path ) ) {
			return;
		}

		// ?lang=xx → pretty /xx/...
		if ( isset( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
			if ( in_array( $lang, self::get_lang_codes(), true ) ) {
				$clean = remove_query_arg( 'lang', home_url( $uri ) );
				$target = self::add_lang_prefix( $clean, $lang );
				wp_safe_redirect( $target, 302 );
				exit;
			}
		}

		$lang = self::get_request_lang();
		if ( ! $lang ) {
			return;
		}

		$current = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . $uri;
		$target  = self::add_lang_prefix( $current, $lang );

		// Avoid redirect loops.
		if ( untrailingslashit( $target ) === untrailingslashit( $current ) ) {
			return;
		}
		// Only redirect GET.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
		if ( $method !== 'GET' && $method !== 'HEAD' ) {
			return;
		}

		wp_safe_redirect( $target, 302 );
		exit;
	}

	public static function should_skip_path( $path ) {
		$path = (string) $path;
		$skip = array(
			'/wp-admin',
			'/wp-json',
			'/wp-login.php',
			'/wp-cron.php',
			'/xmlrpc.php',
			'/wp-content',
			'/wp-includes',
			'/favicon.ico',
			'/robots.txt',
			'/sitemap',
			'/feed',
			'/crm',
		);
		foreach ( $skip as $prefix ) {
			if ( strpos( $path, $prefix ) !== false ) {
				return true;
			}
		}
		// File-like requests.
		if ( preg_match( '/\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|map|txt|xml)$/i', $path ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Prepend /{lang} to a full URL or path.
	 *
	 * @param string $url
	 * @param string $lang
	 * @return string
	 */
	public static function add_lang_prefix( $url, $lang = null ) {
		if ( ! $url ) {
			return $url;
		}
		$lang = $lang ? strtolower( sanitize_key( $lang ) ) : self::get_request_lang();
		if ( ! in_array( $lang, self::get_lang_codes(), true ) ) {
			return $url;
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return $url;
		}

		$path = isset( $parts['path'] ) ? $parts['path'] : '/';
		if ( self::should_skip_path( $path ) ) {
			return $url;
		}

		$home_path = self::site_home_path();

		if ( $home_path && $home_path !== '/' && strpos( $path, $home_path ) === 0 ) {
			$rel = substr( $path, strlen( $home_path ) );
			$prefix_base = $home_path;
		} else {
			$rel = $path;
			$prefix_base = '';
		}
		$rel = '/' . ltrim( (string) $rel, '/' );

		// Already prefixed?
		$codes = self::get_lang_codes();
		$re    = '#^/(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(/|$)#i';
		if ( preg_match( $re, $rel ) ) {
			// Replace existing lang segment.
			$rel = preg_replace( $re, '/' . $lang . '$2', $rel, 1 );
		} else {
			if ( $rel === '/' ) {
				$rel = '/' . $lang . '/';
			} else {
				$rel = '/' . $lang . $rel;
			}
		}

		$new_path = $prefix_base . $rel;
		$new_path = preg_replace( '#/+#', '/', $new_path );

		$out = '';
		if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
			$out .= $parts['scheme'] . '://' . $parts['host'];
			if ( ! empty( $parts['port'] ) ) {
				$out .= ':' . $parts['port'];
			}
		}
		$out .= $new_path;
		if ( ! empty( $parts['query'] ) ) {
			// Drop legacy ?lang=
			parse_str( $parts['query'], $q );
			unset( $q['lang'], $q['dc_region'] );
			if ( $q ) {
				$out .= '?' . http_build_query( $q );
			}
		}
		if ( ! empty( $parts['fragment'] ) ) {
			$out .= '#' . $parts['fragment'];
		}
		return $out;
	}

	/**
	 * Swap language prefix on a URL (used by region switcher).
	 */
	public static function convert_url_lang( $url, $lang ) {
		$url = self::strip_lang_prefix( $url );
		return self::add_lang_prefix( $url, $lang );
	}

	public static function strip_lang_prefix( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return $url;
		}
		$path = isset( $parts['path'] ) ? $parts['path'] : '/';

		$home_path = self::site_home_path();
		if ( $home_path && $home_path !== '/' && strpos( $path, $home_path ) === 0 ) {
			$rel = substr( $path, strlen( $home_path ) );
			$prefix_base = $home_path;
		} else {
			$rel = $path;
			$prefix_base = '';
		}
		$rel = '/' . ltrim( (string) $rel, '/' );

		$codes = self::get_lang_codes();
		$re    = '#^/(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(/|$)#i';
		$rel   = preg_replace( $re, '/', $rel, 1 );
		$rel   = preg_replace( '#/+#', '/', $rel );
		if ( $rel === '' ) {
			$rel = '/';
		}

		$new_path = $prefix_base . $rel;
		$new_path = preg_replace( '#/+#', '/', $new_path );

		$out = '';
		if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
			$out .= $parts['scheme'] . '://' . $parts['host'];
			if ( ! empty( $parts['port'] ) ) {
				$out .= ':' . $parts['port'];
			}
		} elseif ( strpos( $url, '/' ) === 0 ) {
			// Relative — return path only.
			$out = $new_path;
			if ( ! empty( $parts['query'] ) ) {
				$out .= '?' . $parts['query'];
			}
			return $out;
		}
		$out .= $new_path;
		if ( ! empty( $parts['query'] ) ) {
			$out .= '?' . $parts['query'];
		}
		if ( ! empty( $parts['fragment'] ) ) {
			$out .= '#' . $parts['fragment'];
		}
		return $out;
	}

	public static function filter_home_url( $url, $path, $scheme, $blog_id ) {
		if ( ! self::enabled() || self::$filtering || is_admin() ) {
			return $url;
		}
		// Don’t prefix admin / rest home_url quirks.
		if ( is_string( $path ) && self::should_skip_path( '/' . ltrim( $path, '/' ) ) ) {
			return $url;
		}
		self::$filtering = true;
		$url = self::add_lang_prefix( $url, self::get_request_lang() );
		self::$filtering = false;
		return $url;
	}

	public static function filter_link( $url ) {
		if ( ! self::enabled() || self::$filtering || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $url;
		}
		self::$filtering = true;
		$url = self::add_lang_prefix( $url, self::get_request_lang() );
		self::$filtering = false;
		return $url;
	}

	public static function filter_redirect_canonical( $redirect_url, $requested_url ) {
		if ( ! self::enabled() || ! $redirect_url ) {
			return $redirect_url;
		}
		// Keep language prefix on canonical redirects.
		return self::add_lang_prefix( $redirect_url, self::get_request_lang() );
	}

	public static function on_settings_saved( $old, $new ) {
		$old_on = is_array( $old ) && ! empty( $old['polylang_style'] );
		$new_on = is_array( $new ) && ! empty( $new['polylang_style'] );
		if ( $old_on !== $new_on ) {
			update_option( 'dc_lang_urls_needs_flush', 1 );
		}
	}
}
