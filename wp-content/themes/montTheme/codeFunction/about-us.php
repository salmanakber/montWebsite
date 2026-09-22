<?php
/**
 * Multilingual About Us page shortcode.
 *
 * Shortcode: [mont_about_us]
 * Optional:
 *   [mont_about_us img1="URL" img2="URL" img3="URL"]
 *   [mont_about_us img1="URL" video2="URL" img3="URL"]
 *   [mont_about_us img1="URL" img2="URL" media2_type="image|video|auto" img3="URL"]
 *
 * Center slot (media2): video (muted autoplay loop) by default when video2 or
 * a video URL is provided; use media2_type="image" to force an image.
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
 * Detect whether a URL looks like a video file.
 *
 * @param string $url Media URL.
 * @return bool
 */
function mont_about_us_is_video_url( $url ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( ! is_string( $path ) || $path === '' ) {
		return false;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	return in_array( $ext, array( 'mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v' ), true );
}

/**
 * Build center media markup (slot 2): muted autoplay video or image.
 *
 * @param string $url  Media URL.
 * @param string $type auto|image|video.
 * @return array{html:string,empty:bool}
 */
function mont_about_us_build_media2( $url, $type = 'auto' ) {
	$url  = is_string( $url ) ? trim( $url ) : '';
	$type = strtolower( sanitize_key( (string) $type ) );
	if ( ! in_array( $type, array( 'auto', 'image', 'video' ), true ) ) {
		$type = 'auto';
	}

	if ( $url === '' ) {
		return array(
			'html'  => '<img src="' . esc_url( mont_about_us_empty_img() ) . '" alt="" loading="lazy" />',
			'empty' => true,
		);
	}

	$safe = esc_url( $url );
	$use_video = ( 'video' === $type ) || ( 'auto' === $type && mont_about_us_is_video_url( $url ) );

	if ( $use_video ) {
		$html = sprintf(
			'<video src="%1$s" autoplay muted loop playsinline preload="metadata" aria-label="%2$s"></video>',
			$safe,
			esc_attr__( 'Monte Napoleone', 'montTheme' )
		);
	} else {
		$html = sprintf(
			'<img src="%1$s" alt="%2$s" loading="lazy" />',
			$safe,
			esc_attr__( 'Monte Napoleone', 'montTheme' )
		);
	}

	return array(
		'html'  => $html,
		'empty' => false,
	);
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
			'img1'         => '',
			'img2'         => '', // center image (or video URL when media2_type=auto/video)
			'video2'       => '', // preferred center video URL
			'img3'         => '',
			'media2_type'  => 'auto', // auto|image|video
			'lang'         => '', // optional override for testing
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
		'{{img3}}' => $atts['img3'] ? esc_url( $atts['img3'] ) : $empty,
	);
	$html = strtr( $html, $imgs );

	// Center slot: video2 preferred, else img2.
	$media2_url = $atts['video2'] !== '' ? $atts['video2'] : $atts['img2'];
	$media2     = mont_about_us_build_media2( $media2_url, $atts['media2_type'] );
	$html       = str_replace( '{{media2}}', $media2['html'], $html );
	// Back-compat if an old partial still uses {{img2}}.
	$html = str_replace( '{{img2}}', $media2['html'], $html );

	// Mark empty placeholders so CSS can show "Replace image".
	foreach ( array( 1, 3 ) as $i ) {
		$key = 'img' . $i;
		if ( empty( $atts[ $key ] ) ) {
			$html = str_replace(
				'data-mont-about-img="' . $i . '"',
				'data-mont-about-img="' . $i . '" data-empty="1"',
				$html
			);
		}
	}
	if ( $media2['empty'] ) {
		$html = str_replace(
			'data-mont-about-media="2"',
			'data-mont-about-media="2" data-empty="1"',
			$html
		);
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
