<?php
/**
 * Polylang-style language URL prefixes: /en/, /it/, /nb/, /vi/
 *
 * Controlled by Mont DeepL setting `polylang_style`.
 *
 * Important: we intentionally do NOT filter `home_url` and we cancel
 * `redirect_canonical` when a language prefix was stripped — otherwise
 * WordPress redirects / ↔ /en/ in an infinite loop.
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

	/** @var string Original REQUEST_URI before stripping */
	private static $original_uri = '';

	/** @var bool Guard against recursive URL filtering */
	private static $filtering = false;

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'bootstrap_request' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_unprefixed' ), 0 );

		// Do NOT filter home_url — that causes redirect loops with canonical.
		add_filter( 'post_link', array( __CLASS__, 'filter_link' ), 20 );
		add_filter( 'page_link', array( __CLASS__, 'filter_link' ), 20 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_link' ), 20 );
		add_filter( 'term_link', array( __CLASS__, 'filter_link' ), 20 );
		// Never prefix attachment/file URLs.
		add_filter( 'wp_get_attachment_url', array( __CLASS__, 'strip_lang_from_asset_url' ), 99 );
		add_filter( 'wp_get_attachment_image_src', array( __CLASS__, 'filter_attachment_image_src' ), 99 );
		add_filter( 'wp_calculate_image_srcset', array( __CLASS__, 'filter_image_srcset' ), 99 );
		add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'filter_image_attributes' ), 99 );
		add_filter( 'the_content', array( __CLASS__, 'filter_content_asset_urls' ), 99 );
		add_filter( 'upload_dir', array( __CLASS__, 'filter_upload_dir' ), 99 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'filter_redirect_canonical' ), 1, 2 );
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

	/** Unfiltered site path (subdirectory installs). */
	private static function site_home_path() {
		self::$filtering = true;
		$path            = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		self::$filtering = false;
		$path            = untrailingslashit( $path );
		return ( $path === '' ) ? '' : $path;
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

	public static function had_prefix() {
		return self::$had_prefix;
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

		self::$original_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
		$uri                = self::$original_uri;
		$path               = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$query              = wp_parse_url( $uri, PHP_URL_QUERY );

		$home_path = self::site_home_path();
		if ( $home_path && $home_path !== '/' && strpos( $path, $home_path ) === 0 ) {
			$rel = substr( $path, strlen( $home_path ) );
		} else {
			$rel = $path;
		}
		$rel = '/' . ltrim( (string) $rel, '/' );

		$codes = self::get_lang_codes();
		$re    = '#^/(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(/|$)#i';

		if ( ! preg_match( $re, $rel, $m ) ) {
			self::$had_prefix   = false;
			self::$request_lang = null;
			return;
		}

		$lang                   = strtolower( $m[1] );
		self::$had_prefix       = true;
		self::$request_lang     = $lang;

		if ( class_exists( __NAMESPACE__ . '\\DC_Region_Currency' ) ) {
			$slug = DC_Region_Currency::lang_to_region( $lang );
			if ( $slug ) {
				DC_Region_Currency::set_region_cookie( $slug );
			}
		}

		$stripped_rel = preg_replace( $re, '/', $rel, 1 );
		if ( ! is_string( $stripped_rel ) || $stripped_rel === '' ) {
			$stripped_rel = '/';
		}
		$stripped_rel = preg_replace( '#/+#', '/', $stripped_rel );

		$new_path = ( $home_path && $home_path !== '/' )
			? $home_path . ( $stripped_rel === '/' ? '/' : $stripped_rel )
			: $stripped_rel;
		$new_path = preg_replace( '#/+#', '/', $new_path );

		// Preserve trailing slash only when useful (not bare home).
		if ( $new_path !== '/' && substr( $path, -1 ) === '/' ) {
			$new_path = trailingslashit( $new_path );
		}

		$new_uri = $new_path;
		if ( $query ) {
			$new_uri .= '?' . $query;
		}

		$_SERVER['REQUEST_URI'] = $new_uri;
	}

	/**
	 * Optional: send bare front URLs to /{lang}/… once.
	 * Runs late and only when we did NOT already strip a prefix.
	 */
	public static function maybe_redirect_unprefixed() {
		if ( ! self::enabled() || is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent() ) {
			return;
		}
		if ( self::$had_prefix ) {
			return;
		}
		if ( is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';
		if ( $method !== 'GET' && $method !== 'HEAD' ) {
			return;
		}

		$uri  = self::$original_uri ? self::$original_uri : ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' );
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		if ( self::should_skip_path( $path ) ) {
			return;
		}

		// Legacy ?lang=xx → /xx/...
		if ( isset( $_GET['lang'] ) ) {
			$lang = sanitize_key( wp_unslash( $_GET['lang'] ) );
			if ( in_array( $lang, self::get_lang_codes(), true ) ) {
				$host    = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
				$current = ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
				$clean   = remove_query_arg( 'lang', $current );
				$target  = self::add_lang_prefix( $clean, $lang );
				if ( self::urls_differ( $current, $target ) ) {
					wp_safe_redirect( $target, 302 );
					exit;
				}
			}
			return;
		}

		$lang = self::get_request_lang();
		if ( ! $lang || ! in_array( $lang, self::get_lang_codes(), true ) ) {
			return;
		}

		$host    = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		$current = ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
		$target  = self::add_lang_prefix( $current, $lang );

		if ( ! self::urls_differ( $current, $target ) ) {
			return;
		}

		wp_safe_redirect( $target, 302 );
		exit;
	}

	private static function urls_differ( $a, $b ) {
		$norm = static function ( $url ) {
			$parts = wp_parse_url( $url );
			if ( ! is_array( $parts ) ) {
				return untrailingslashit( (string) $url );
			}
			$path = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';
			if ( $path === '' ) {
				$path = '/';
			}
			$host = isset( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
			return $host . $path;
		};
		return $norm( $a ) !== $norm( $b );
	}

	public static function should_skip_path( $path ) {
		$path = '/' . ltrim( str_replace( '\\', '/', (string) $path ), '/' );
		$skip = array(
			'/wp-admin',
			'/wp-json',
			'/wp-login.php',
			'/wp-cron.php',
			'/xmlrpc.php',
			'/wp-content',
			'/wp-includes',
			'/uploads/',
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
		if ( preg_match( '/\.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|map|txt|xml|mp4|webm|pdf)$/i', $path ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Remove /en|/it|/nb|/vi in front of static asset paths.
	 * Example: /it/wp-content/uploads/x.jpg → /wp-content/uploads/x.jpg
	 */
	public static function strip_lang_from_asset_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return $url;
		}
		$codes = self::get_lang_codes();
		if ( ! $codes ) {
			return $url;
		}
		$alt = implode( '|', array_map( 'preg_quote', $codes ) );
		$fixed = preg_replace(
			'#(^|://[^/]+)/(?:' . $alt . ')/(wp-content|wp-includes|wp-admin|wp-json|uploads)/#i',
			'$1/$2/',
			$url
		);
		return is_string( $fixed ) ? $fixed : $url;
	}

	public static function filter_attachment_image_src( $image ) {
		if ( is_array( $image ) && ! empty( $image[0] ) ) {
			$image[0] = self::strip_lang_from_asset_url( $image[0] );
		}
		return $image;
	}

	public static function filter_image_srcset( $sources ) {
		if ( ! is_array( $sources ) ) {
			return $sources;
		}
		foreach ( $sources as $width => $source ) {
			if ( ! empty( $source['url'] ) ) {
				$sources[ $width ]['url'] = self::strip_lang_from_asset_url( $source['url'] );
			}
		}
		return $sources;
	}

	public static function filter_image_attributes( $attr ) {
		if ( ! is_array( $attr ) ) {
			return $attr;
		}
		foreach ( array( 'src', 'srcset', 'data-src', 'data-srcset', 'data-large_image' ) as $key ) {
			if ( ! empty( $attr[ $key ] ) ) {
				$attr[ $key ] = self::strip_lang_from_asset_url( $attr[ $key ] );
			}
		}
		return $attr;
	}

	public static function filter_content_asset_urls( $content ) {
		if ( ! is_string( $content ) || $content === '' ) {
			return $content;
		}
		$codes = self::get_lang_codes();
		if ( ! $codes ) {
			return $content;
		}
		$alt = implode( '|', array_map( 'preg_quote', $codes ) );
		$fixed = preg_replace(
			'#(https?://[^/]+)/(?:' . $alt . ')/(wp-content|wp-includes)/#i',
			'$1/$2/',
			$content
		);
		return is_string( $fixed ) ? $fixed : $content;
	}

	public static function filter_upload_dir( $uploads ) {
		if ( is_array( $uploads ) ) {
			if ( ! empty( $uploads['url'] ) ) {
				$uploads['url'] = self::strip_lang_from_asset_url( $uploads['url'] );
			}
			if ( ! empty( $uploads['baseurl'] ) ) {
				$uploads['baseurl'] = self::strip_lang_from_asset_url( $uploads['baseurl'] );
			}
		}
		return $uploads;
	}

	/**
	 * @param string      $url
	 * @param string|null $lang
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
			$rel         = substr( $path, strlen( $home_path ) );
			$prefix_base = $home_path;
		} else {
			$rel         = $path;
			$prefix_base = '';
		}
		$rel = '/' . ltrim( (string) $rel, '/' );

		$codes = self::get_lang_codes();
		$re    = '#^/(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(/|$)#i';
		if ( preg_match( $re, $rel ) ) {
			$rel = preg_replace( $re, '/' . $lang . '$2', $rel, 1 );
		} elseif ( $rel === '/' ) {
			$rel = '/' . $lang . '/';
		} else {
			$rel = '/' . $lang . $rel;
		}

		$new_path = preg_replace( '#/+#', '/', $prefix_base . $rel );

		$out = '';
		if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
			$out .= $parts['scheme'] . '://' . $parts['host'];
			if ( ! empty( $parts['port'] ) ) {
				$out .= ':' . $parts['port'];
			}
		}
		$out .= $new_path;
		if ( ! empty( $parts['query'] ) ) {
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

	public static function convert_url_lang( $url, $lang ) {
		return self::add_lang_prefix( self::strip_lang_prefix( $url ), $lang );
	}

	public static function strip_lang_prefix( $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return $url;
		}
		$path = isset( $parts['path'] ) ? $parts['path'] : '/';

		$home_path = self::site_home_path();
		if ( $home_path && $home_path !== '/' && strpos( $path, $home_path ) === 0 ) {
			$rel         = substr( $path, strlen( $home_path ) );
			$prefix_base = $home_path;
		} else {
			$rel         = $path;
			$prefix_base = '';
		}
		$rel = '/' . ltrim( (string) $rel, '/' );

		$codes = self::get_lang_codes();
		$re    = '#^/(' . implode( '|', array_map( 'preg_quote', $codes ) ) . ')(/|$)#i';
		$rel   = preg_replace( $re, '/', $rel, 1 );
		$rel   = preg_replace( '#/+#', '/', (string) $rel );
		if ( $rel === '' ) {
			$rel = '/';
		}

		$new_path = preg_replace( '#/+#', '/', $prefix_base . $rel );

		$out = '';
		if ( ! empty( $parts['scheme'] ) && ! empty( $parts['host'] ) ) {
			$out .= $parts['scheme'] . '://' . $parts['host'];
			if ( ! empty( $parts['port'] ) ) {
				$out .= ':' . $parts['port'];
			}
		} elseif ( strpos( $url, '/' ) === 0 ) {
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

	public static function filter_link( $url ) {
		if ( ! self::enabled() || self::$filtering || ( is_admin() && ! wp_doing_ajax() ) ) {
			return $url;
		}
		$url = self::strip_lang_from_asset_url( $url );
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( self::should_skip_path( $path ) ) {
			return $url;
		}
		self::$filtering = true;
		$url             = self::add_lang_prefix( $url, self::get_request_lang() );
		self::$filtering = false;
		return $url;
	}

	/**
	 * Critical loop breaker: after stripping /en/, WP sees "/" and tries to
	 * canonical-redirect to home_url('/en/') → infinite redirects.
	 */
	public static function filter_redirect_canonical( $redirect_url, $requested_url ) {
		if ( ! self::enabled() ) {
			return $redirect_url;
		}
		if ( self::$had_prefix ) {
			return false;
		}
		return $redirect_url;
	}
}
