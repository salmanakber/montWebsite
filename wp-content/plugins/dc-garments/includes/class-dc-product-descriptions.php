<?php
/**
 * Per-language WooCommerce product titles + long descriptions (CRM + storefront).
 *
 * Meta:
 * - `_dc_product_titles`       => [ 'nb' => '', 'en' => '', 'it' => '', 'vi' => '' ]
 * - `_dc_product_descriptions` => [ 'nb' => '', 'en' => '', 'it' => '', 'vi' => '' ]
 *
 * Norwegian (`nb`) syncs to `post_title` / `post_content` for WC compatibility.
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DC_Product_Descriptions {

	const META_TITLES = '_dc_product_titles';
	const META_KEY    = '_dc_product_descriptions';

	/** @deprecated Use META_TITLES */
	const META_TITLES_LEGACY = '_dc_product_titles';

	public static function init() {
		add_filter( 'woocommerce_product_get_name', array( __CLASS__, 'filter_product_name' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_name', array( __CLASS__, 'filter_product_name' ), 20, 2 );
		add_filter( 'the_title', array( __CLASS__, 'filter_the_title' ), 20, 2 );
		add_filter( 'woocommerce_product_get_description', array( __CLASS__, 'filter_product_description' ), 20, 2 );
		add_filter( 'woocommerce_product_get_short_description', array( __CLASS__, 'filter_product_description' ), 20, 2 );
	}

	/** Language codes aligned with region switcher. */
	public static function get_languages() {
		return array(
			'nb' => array(
				'label'    => 'Norwegian',
				'native'   => 'Norsk',
				'region'   => 'Norway',
				'currency' => 'kr NOK',
				'code'     => 'nb',
			),
			'en' => array(
				'label'    => 'English',
				'native'   => 'English',
				'region'   => 'International',
				'currency' => '$ USD',
				'code'     => 'en',
			),
			'it' => array(
				'label'    => 'Italian',
				'native'   => 'Italiano',
				'region'   => 'Italy',
				'currency' => '€ EUR',
				'code'     => 'it',
			),
			'vi' => array(
				'label'    => 'Vietnamese',
				'native'   => 'Tiếng Việt',
				'region'   => 'Việt Nam',
				'currency' => '₫ VND',
				'code'     => 'vi',
			),
		);
	}

	public static function current_lang() {
		if ( class_exists( __NAMESPACE__ . '\\DC_Region_Currency' ) ) {
			return DC_Region_Currency::get_current_lang();
		}
		return 'nb';
	}

	/**
	 * @param int    $product_id
	 * @param string $meta_key
	 * @return array<string,string>
	 */
	private static function get_meta_map( $product_id, $meta_key ) {
		$product_id = (int) $product_id;
		$map        = get_post_meta( $product_id, $meta_key, true );
		if ( ! is_array( $map ) ) {
			$map = array();
		}

		$out = array();
		foreach ( array_keys( self::get_languages() ) as $lang ) {
			$out[ $lang ] = isset( $map[ $lang ] ) ? (string) $map[ $lang ] : '';
		}
		return $out;
	}

	/**
	 * @param int $product_id
	 * @return array<string,string>
	 */
	public static function get_title_map( $product_id ) {
		$out = self::get_meta_map( $product_id, self::META_TITLES );
		if ( $out['nb'] === '' ) {
			$post = get_post( $product_id );
			if ( $post && $post->post_title !== '' ) {
				$out['nb'] = (string) $post->post_title;
			}
		}
		return $out;
	}

	/**
	 * @param int $product_id
	 * @return array<string,string>
	 */
	public static function get_map( $product_id ) {
		$out = self::get_meta_map( $product_id, self::META_KEY );
		if ( $out['nb'] === '' ) {
			$post = get_post( $product_id );
			if ( $post && $post->post_content !== '' ) {
				$out['nb'] = (string) $post->post_content;
			}
		}
		return $out;
	}

	/**
	 * @param array  $map
	 * @param string $type title|description
	 * @return array<string,string>
	 */
	private static function sanitize_map( $map, $type = 'description' ) {
		$clean = array();
		foreach ( array_keys( self::get_languages() ) as $lang ) {
			$raw = isset( $map[ $lang ] ) ? $map[ $lang ] : '';
			if ( ! is_string( $raw ) ) {
				$clean[ $lang ] = '';
				continue;
			}
			$raw = wp_unslash( $raw );
			$clean[ $lang ] = ( $type === 'title' )
				? sanitize_text_field( $raw )
				: wp_kses_post( $raw );
		}
		return $clean;
	}

	/**
	 * @param int                  $product_id
	 * @param array<string,string> $map
	 */
	public static function save_title_map( $product_id, $map ) {
		$product_id = (int) $product_id;
		$clean      = self::sanitize_map( $map, 'title' );
		update_post_meta( $product_id, self::META_TITLES, $clean );

		// Keep WP title in sync with Norwegian (source of truth).
		if ( $clean['nb'] !== '' ) {
			// Avoid infinite loops from title filters while saving.
			remove_filter( 'the_title', array( __CLASS__, 'filter_the_title' ), 20 );
			wp_update_post(
				array(
					'ID'         => $product_id,
					'post_title' => $clean['nb'],
				)
			);
			add_filter( 'the_title', array( __CLASS__, 'filter_the_title' ), 20, 2 );
		}

		return $clean;
	}

	/**
	 * @param int                  $product_id
	 * @param array<string,string> $map
	 */
	public static function save_map( $product_id, $map ) {
		$product_id = (int) $product_id;
		$clean      = self::sanitize_map( $map, 'description' );
		update_post_meta( $product_id, self::META_KEY, $clean );

		if ( $clean['nb'] !== '' ) {
			wp_update_post(
				array(
					'ID'           => $product_id,
					'post_content' => $clean['nb'],
				)
			);
		}

		return $clean;
	}

	/**
	 * @param array  $request
	 * @param string $field titles|descriptions
	 * @return array<string,string>
	 */
	public static function parse_field_from_request( $request, $field ) {
		$map     = array();
		$json_key = $field . '_json';

		if ( ! empty( $request[ $json_key ] ) && is_string( $request[ $json_key ] ) ) {
			$decoded = json_decode( wp_unslash( $request[ $json_key ] ), true );
			if ( is_array( $decoded ) ) {
				$map = $decoded;
			}
		} elseif ( ! empty( $request[ $field ] ) && is_array( $request[ $field ] ) ) {
			$map = $request[ $field ];
		}

		$out = array();
		foreach ( array_keys( self::get_languages() ) as $lang ) {
			$out[ $lang ] = isset( $map[ $lang ] ) ? (string) $map[ $lang ] : '';
		}
		return $out;
	}

	/** @deprecated */
	public static function parse_from_request( $request ) {
		return self::parse_field_from_request( $request, 'descriptions' );
	}

	/**
	 * @param int         $product_id
	 * @param string|null $lang
	 * @return string
	 */
	public static function get_title_for_lang( $product_id, $lang = null ) {
		$map  = self::get_title_map( $product_id );
		$lang = $lang ? strtolower( sanitize_key( $lang ) ) : self::current_lang();

		if ( ! empty( $map[ $lang ] ) ) {
			return $map[ $lang ];
		}
		foreach ( array( 'nb', 'en', 'it', 'vi' ) as $fallback ) {
			if ( ! empty( $map[ $fallback ] ) ) {
				return $map[ $fallback ];
			}
		}
		$post = get_post( $product_id );
		return $post ? (string) $post->post_title : '';
	}

	/**
	 * @param int         $product_id
	 * @param string|null $lang
	 * @return string
	 */
	public static function get_for_lang( $product_id, $lang = null ) {
		$map  = self::get_map( $product_id );
		$lang = $lang ? strtolower( sanitize_key( $lang ) ) : self::current_lang();

		if ( ! empty( $map[ $lang ] ) ) {
			return $map[ $lang ];
		}
		foreach ( array( 'nb', 'en', 'it', 'vi' ) as $fallback ) {
			if ( ! empty( $map[ $fallback ] ) ) {
				return $map[ $fallback ];
			}
		}
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		return $product ? (string) $product->get_description( 'edit' ) : '';
	}

	public static function has_curated_title_for_lang( $product_id, $lang = null ) {
		$map  = self::get_title_map( $product_id );
		$lang = $lang ? strtolower( sanitize_key( $lang ) ) : self::current_lang();
		return ! empty( $map[ $lang ] );
	}

	public static function has_curated_for_lang( $product_id, $lang = null ) {
		$map  = self::get_map( $product_id );
		$lang = $lang ? strtolower( sanitize_key( $lang ) ) : self::current_lang();
		return ! empty( $map[ $lang ] );
	}

	private static function should_filter_front() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return false;
		}
		// CRM screens
		if ( function_exists( 'get_query_var' ) && get_query_var( 'dc_crm' ) ) {
			return false;
		}
		return true;
	}

	public static function filter_product_name( $name, $product ) {
		if ( ! self::should_filter_front() || ! $product || ! is_object( $product ) ) {
			return $name;
		}
		$id = method_exists( $product, 'get_id' ) ? (int) $product->get_id() : 0;
		if ( $id <= 0 ) {
			return $name;
		}
		$translated = self::get_title_for_lang( $id );
		return $translated !== '' ? $translated : $name;
	}

	public static function filter_the_title( $title, $post_id = 0 ) {
		if ( ! self::should_filter_front() || ! $post_id ) {
			return $title;
		}
		if ( get_post_type( $post_id ) !== 'product' ) {
			return $title;
		}
		$translated = self::get_title_for_lang( (int) $post_id );
		return $translated !== '' ? $translated : $title;
	}

	public static function filter_product_description( $description, $product ) {
		if ( ! self::should_filter_front() || ! $product || ! is_object( $product ) ) {
			return $description;
		}
		$id = method_exists( $product, 'get_id' ) ? (int) $product->get_id() : 0;
		if ( $id <= 0 ) {
			return $description;
		}
		// Only replace when curated text exists for active lang (or fallback chain returns something).
		$translated = self::get_for_lang( $id );
		return $translated !== '' ? $translated : $description;
	}
}
