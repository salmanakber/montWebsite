<?php
/**
 * Per-language WooCommerce product long descriptions (CRM + storefront).
 *
 * Meta: `_dc_product_descriptions` => [ 'nb' => html, 'en' => ..., 'it' => ..., 'vi' => ... ]
 * Norwegian (`nb`) is also synced to `post_content` for WC compatibility.
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DC_Product_Descriptions {

	const META_KEY = '_dc_product_descriptions';

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

	/**
	 * @param int $product_id
	 * @return array<string,string>
	 */
	public static function get_map( $product_id ) {
		$product_id = (int) $product_id;
		$map        = get_post_meta( $product_id, self::META_KEY, true );
		if ( ! is_array( $map ) ) {
			$map = array();
		}

		$out = array();
		foreach ( array_keys( self::get_languages() ) as $lang ) {
			$out[ $lang ] = isset( $map[ $lang ] ) ? (string) $map[ $lang ] : '';
		}

		// Seed nb from post_content when CRM map is empty.
		if ( $out['nb'] === '' ) {
			$post = get_post( $product_id );
			if ( $post && $post->post_content !== '' ) {
				$out['nb'] = (string) $post->post_content;
			}
		}

		return $out;
	}

	/**
	 * @param int                  $product_id
	 * @param array<string,string> $map
	 */
	public static function save_map( $product_id, $map ) {
		$product_id = (int) $product_id;
		$clean      = array();

		foreach ( array_keys( self::get_languages() ) as $lang ) {
			$raw = isset( $map[ $lang ] ) ? $map[ $lang ] : '';
			if ( is_string( $raw ) ) {
				$clean[ $lang ] = wp_kses_post( wp_unslash( $raw ) );
			} else {
				$clean[ $lang ] = '';
			}
		}

		update_post_meta( $product_id, self::META_KEY, $clean );

		// Keep WC long description in sync with Norwegian (source of truth).
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
	 * Parse descriptions from AJAX POST (supports descriptions[nb] or descriptions_json).
	 *
	 * @param array $request
	 * @return array<string,string>
	 */
	public static function parse_from_request( $request ) {
		$map = array();

		if ( ! empty( $request['descriptions_json'] ) && is_string( $request['descriptions_json'] ) ) {
			$decoded = json_decode( wp_unslash( $request['descriptions_json'] ), true );
			if ( is_array( $decoded ) ) {
				$map = $decoded;
			}
		} elseif ( ! empty( $request['descriptions'] ) && is_array( $request['descriptions'] ) ) {
			$map = $request['descriptions'];
		}

		$out = array();
		foreach ( array_keys( self::get_languages() ) as $lang ) {
			$out[ $lang ] = isset( $map[ $lang ] ) ? (string) $map[ $lang ] : '';
		}
		return $out;
	}

	/**
	 * Resolve description HTML for a language (fallback chain).
	 *
	 * @param int         $product_id
	 * @param string|null $lang
	 * @return string
	 */
	public static function get_for_lang( $product_id, $lang = null ) {
		$map = self::get_map( $product_id );

		if ( $lang === null || $lang === '' ) {
			$lang = class_exists( __NAMESPACE__ . '\\DC_Region_Currency' )
				? DC_Region_Currency::get_current_lang()
				: 'nb';
		}
		$lang = strtolower( sanitize_key( $lang ) );

		if ( ! empty( $map[ $lang ] ) ) {
			return $map[ $lang ];
		}

		foreach ( array( 'nb', 'en', 'it', 'vi' ) as $fallback ) {
			if ( ! empty( $map[ $fallback ] ) ) {
				return $map[ $fallback ];
			}
		}

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		return $product ? (string) $product->get_description() : '';
	}

	/**
	 * Whether the returned string is a curated CRM translation for the active lang.
	 *
	 * @param int         $product_id
	 * @param string|null $lang
	 */
	public static function has_curated_for_lang( $product_id, $lang = null ) {
		$map = self::get_map( $product_id );
		if ( $lang === null || $lang === '' ) {
			$lang = class_exists( __NAMESPACE__ . '\\DC_Region_Currency' )
				? DC_Region_Currency::get_current_lang()
				: 'nb';
		}
		$lang = strtolower( sanitize_key( $lang ) );
		return ! empty( $map[ $lang ] );
	}
}
