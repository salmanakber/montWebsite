<?php
/**
 * Region-specific return / refund PDF forms.
 *
 * @package montTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return form definitions keyed by region slug.
 *
 * @return array<string, array{file:string,label:string}>
 */
function mont_return_form_catalog() {
	return array(
		'intl' => array(
			'file'  => 'Return English.pdf',
			'label' => 'Return form (English)',
		),
		'it'   => array(
			'file'  => 'Return English.pdf',
			'label' => 'Return form (English)',
		),
		'no'   => array(
			'file'  => 'Return English.pdf',
			'label' => 'Return form (English)',
		),
		'vn'   => array(
			'file'  => 'Return Tieng Viet.pdf',
			'label' => 'Return form (Tiếng Việt)',
		),
	);
}

/**
 * Resolve region slug for return form lookup.
 *
 * @param string|null $region_slug Optional region slug.
 * @return string
 */
function mont_return_form_region_slug( $region_slug = null ) {
	if ( null === $region_slug && class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) {
		$region_slug = \DC_Product_Manager\DC_Region_Currency::get_current_region_slug();
	}
	$region_slug = sanitize_key( (string) $region_slug );
	$catalog     = mont_return_form_catalog();
	return isset( $catalog[ $region_slug ] ) ? $region_slug : 'intl';
}

/**
 * Public URL for the return PDF for a region.
 *
 * @param string|null $region_slug Optional region slug.
 * @return string
 */
function mont_return_form_url( $region_slug = null ) {
	$slug    = mont_return_form_region_slug( $region_slug );
	$catalog = mont_return_form_catalog();
	$file    = $catalog[ $slug ]['file'];
	$base    = trailingslashit( get_template_directory_uri() ) . 'assets/returnForm/';
	return $base . rawurlencode( $file );
}

/**
 * Human label for the active return form.
 *
 * @param string|null $region_slug Optional region slug.
 * @return string
 */
function mont_return_form_label( $region_slug = null ) {
	$slug    = mont_return_form_region_slug( $region_slug );
	$catalog = mont_return_form_catalog();
	return $catalog[ $slug ]['label'];
}

/**
 * Data passed to front-end scripts.
 *
 * @return array
 */
function mont_return_form_js_config() {
	$out = array(
		'forms'   => array(),
		'current' => mont_return_form_region_slug(),
		'labels'  => array(
			'button'  => __( 'Return form', 'montenapoleone' ),
			'popupTitle' => __( 'Return form for your region', 'montenapoleone' ),
			'popupText'  => __( 'Download the return form for your selected region:', 'montenapoleone' ),
			'download'   => __( 'Download PDF', 'montenapoleone' ),
			'close'      => __( 'Close', 'montenapoleone' ),
		),
	);

	foreach ( mont_return_form_catalog() as $slug => $item ) {
		$out['forms'][ $slug ] = array(
			'url'   => mont_return_form_url( $slug ),
			'label' => $item['label'],
		);
	}

	$current = $out['current'];
	$out['url']   = $out['forms'][ $current ]['url'];
	$out['label'] = $out['forms'][ $current ]['label'];

	return $out;
}

/**
 * Enqueue return-form assets site-wide.
 */
function mont_return_form_enqueue() {
	if ( is_admin() ) {
		return;
	}

	$theme_dir = get_template_directory();
	$theme_uri = get_template_directory_uri();
	$js_path   = $theme_dir . '/assets/return-form.js';

	if ( ! file_exists( $js_path ) ) {
		return;
	}

	wp_enqueue_script(
		'mont-return-form',
		$theme_uri . '/assets/return-form.js',
		array( 'jquery' ),
		(string) filemtime( $js_path ),
		true
	);

	wp_localize_script( 'mont-return-form', 'montReturnForm', mont_return_form_js_config() );
}
add_action( 'wp_enqueue_scripts', 'mont_return_form_enqueue', 25 );

/**
 * Render return-form button (B2C / B2B product pages).
 *
 * @param array $args Optional args: class, show_icon.
 * @return string
 */
function mont_return_form_button( array $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class'     => 'mont_return-form-btn mont_size-guide-btn',
			'show_icon' => true,
		)
	);

	$url   = mont_return_form_url();
	$label = mont_return_form_label();

	$icon = '';
	if ( $args['show_icon'] ) {
		$icon = '<svg class="mont_size-guide-btn__icon" viewBox="0 0 18 18" width="14" height="14" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg">'
			. '<path d="M4 2.5h10a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.2"/>'
			. '<path d="M6 6.5h6M6 9h6M6 11.5h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>'
			. '</svg>';
	}

	return sprintf(
		'<a href="%1$s" class="%2$s" target="_blank" rel="noopener noreferrer" data-mont-return-form-link data-mont-return-form="%3$s">%4$s<span>%5$s</span></a>',
		esc_url( $url ),
		esc_attr( $args['class'] ),
		esc_attr( mont_return_form_region_slug() ),
		$icon,
		esc_html( __( 'Return form', 'montenapoleone' ) )
	);
}
