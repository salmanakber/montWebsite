<?php
/**
 * Multilingual About Us page shortcode.
 *
 * Shortcode: [mont_about_us]
 * Optional:  [mont_about_us img1="URL" img2="URL" img3="URL"]
 *
 * Language follows the active region switcher (en / it / nb / vi).
 *
 * @package montTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve current About Us language code.
 *
 * @return string en|it|nb|vi
 */
function mont_about_us_lang() {
	$lang = 'en';
	if ( class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) {
		$lang = \DC_Product_Manager\DC_Region_Currency::get_current_lang();
	}
	$lang = strtolower( sanitize_key( (string) $lang ) );
	$ok   = array( 'en', 'it', 'nb', 'vi' );
	return in_array( $lang, $ok, true ) ? $lang : 'en';
}

/**
 * Absolute path to a language HTML partial.
 *
 * @param string $lang Language code.
 * @return string
 */
function mont_about_us_partial_path( $lang ) {
	$dir  = trailingslashit( get_template_directory() ) . 'assets/about-us/lang/';
	$file = $dir . $lang . '.html';
	if ( ! file_exists( $file ) ) {
		$file = $dir . 'en.html';
	}
	return $file;
}

/**
 * Tiny transparent PNG used when no image URL is provided (CSS shows placeholder).
 *
 * @return string
 */
function mont_about_us_empty_img() {
	return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
}

/**
 * Enqueue About Us styles when shortcode is present.
 */
function mont_about_us_enqueue() {
	$path = get_template_directory() . '/assets/about-us/about-us.css';
	if ( ! file_exists( $path ) ) {
		return;
	}
	wp_enqueue_style(
		'mont-about-us',
		get_template_directory_uri() . '/assets/about-us/about-us.css',
		array(),
		(string) filemtime( $path )
	);
}

/**
 * Render [mont_about_us] shortcode.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string
 */
function mont_about_us_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'img1' => '',
			'img2' => '',
			'img3' => '',
			'lang' => '', // optional override for testing
		),
		$atts,
		'mont_about_us'
	);

	mont_about_us_enqueue();

	$lang = $atts['lang'] ? strtolower( sanitize_key( $atts['lang'] ) ) : mont_about_us_lang();
	$ok   = array( 'en', 'it', 'nb', 'vi' );
	if ( ! in_array( $lang, $ok, true ) ) {
		$lang = 'en';
	}

	$path = mont_about_us_partial_path( $lang );
	$html = file_exists( $path ) ? file_get_contents( $path ) : '';
	if ( ! is_string( $html ) || $html === '' ) {
		return '';
	}

	$empty = mont_about_us_empty_img();
	$imgs  = array(
		'{{img1}}' => $atts['img1'] ? esc_url( $atts['img1'] ) : $empty,
		'{{img2}}' => $atts['img2'] ? esc_url( $atts['img2'] ) : $empty,
		'{{img3}}' => $atts['img3'] ? esc_url( $atts['img3'] ) : $empty,
	);
	$html = strtr( $html, $imgs );

	// Mark empty placeholders so CSS can show "Replace image".
	for ( $i = 1; $i <= 3; $i++ ) {
		$key = 'img' . $i;
		if ( empty( $atts[ $key ] ) ) {
			$html = str_replace(
				'data-mont-about-img="' . $i . '"',
				'data-mont-about-img="' . $i . '" data-empty="1"',
				$html
			);
		}
	}

	return '<div class="mont-about notranslate" data-lang="' . esc_attr( $lang ) . '" lang="' . esc_attr( $lang ) . '">' . $html . '</div>';
}
add_shortcode( 'mont_about_us', 'mont_about_us_shortcode' );

/**
 * Elementor often strips shortcodes unless processed — ensure content filter runs.
 */
add_filter(
	'widget_text',
	static function ( $text ) {
		if ( is_string( $text ) && false !== strpos( $text, '[mont_about_us' ) ) {
			$text = do_shortcode( $text );
		}
		return $text;
	}
);
