<?php
/**
 * Region-specific return / refund PDF forms.
 *
 * Only regions listed in the catalog have a form (intl, vn).
 *
 * @package montTheme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Regions that have a return PDF.
 *
 * @return array<string, array{file:string}>
 */
function mont_return_form_catalog() {
	return array(
		'intl' => array(
			'file' => 'Return English.pdf',
		),
		'vn'   => array(
			'file' => 'Return Tieng Viet.pdf',
		),
		'no'   => array(
			'file' => 'Return Norwegian.pdf', // Return Norwegian.pdf
		),
	);
}

/**
 * UI copy in the active site language.
 *
 * @param string|null $lang Language code (en, nb, it, vi).
 * @return array<string, string>
 */
function mont_return_form_labels( $lang = null ) {
	if ( null === $lang && class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) {
		$lang = \DC_Product_Manager\DC_Region_Currency::get_current_lang();
	}
	$lang = strtolower( sanitize_key( (string) $lang ) );

	$strings = array(
		'en' => array(
			'button'     => 'Return form',
			'popupTitle' => 'Return & exchange form',
			'popupText'  => 'Please review the form below. You can download it for your records.',
			'download'   => 'Download PDF',
			'close'      => 'Close',
			'viewerTitle'=> 'Return form preview',
		),
		'nb' => array(
			'button'     => 'Returskjema',
			'popupTitle' => 'Retur- og bytteskjema',
			'popupText'  => 'Se skjemaet nedenfor. Du kan laste det ned til dine arkiver.',
			'download'   => 'Last ned PDF',
			'close'      => 'Lukk',
			'viewerTitle'=> 'Forhåndsvisning av returskjema',
		),
		'it' => array(
			'button'     => 'Modulo reso',
			'popupTitle' => 'Modulo reso e cambio',
			'popupText'  => 'Consulta il modulo qui sotto. Puoi scaricarlo per i tuoi archivi.',
			'download'   => 'Scarica PDF',
			'close'      => 'Chiudi',
			'viewerTitle'=> 'Anteprima modulo reso',
		),
		'vi' => array(
			'button'     => 'Mẫu trả hàng',
			'popupTitle' => 'Mẫu trả hàng & đổi hàng',
			'popupText'  => 'Xem mẫu bên dưới. Bạn có thể tải xuống để lưu.',
			'download'   => 'Tải PDF',
			'close'      => 'Đóng',
			'viewerTitle'=> 'Xem trước mẫu trả hàng',
		),
	);

	return isset( $strings[ $lang ] ) ? $strings[ $lang ] : $strings['en'];
}

/**
 * @param string|null $region_slug Region slug.
 * @return string
 */
function mont_return_form_current_region( $region_slug = null ) {
	if ( null === $region_slug && class_exists( 'DC_Product_Manager\\DC_Region_Currency' ) ) {
		$region_slug = \DC_Product_Manager\DC_Region_Currency::get_current_region_slug();
	}
	return sanitize_key( (string) $region_slug );
}

/**
 * Whether this region has a return PDF.
 *
 * @param string|null $region_slug Region slug.
 * @return bool
 */
function mont_return_form_has_form( $region_slug = null ) {
	$slug    = mont_return_form_current_region( $region_slug );
	$catalog = mont_return_form_catalog();
	return isset( $catalog[ $slug ] );
}

/**
 * Public URL for the return PDF.
 *
 * @param string|null $region_slug Region slug.
 * @return string
 */
function mont_return_form_url( $region_slug = null ) {
	$slug = mont_return_form_current_region( $region_slug );
	if ( ! mont_return_form_has_form( $slug ) ) {
		return '';
	}
	$file = mont_return_form_catalog()[ $slug ]['file'];
	$base = trailingslashit( get_template_directory_uri() ) . 'assets/returnForm/';
	return $base . rawurlencode( $file );
}

/**
 * Data passed to front-end scripts.
 *
 * @return array
 */
function mont_return_form_js_config() {
	$lang    = class_exists( 'DC_Product_Manager\\DC_Region_Currency' )
		? \DC_Product_Manager\DC_Region_Currency::get_current_lang()
		: 'en';
	$current = mont_return_form_current_region();
	$labels  = mont_return_form_labels( $lang );

	$out = array(
		'forms'   => array(),
		'current' => $current,
		'hasForm' => mont_return_form_has_form( $current ),
		'lang'    => $lang,
		'labels'  => $labels,
		'url'     => '',
		'label'   => '',
	);

	foreach ( mont_return_form_catalog() as $slug => $item ) {
		$out['forms'][ $slug ] = array(
			'url' => mont_return_form_url( $slug ),
		);
	}

	if ( $out['hasForm'] ) {
		$out['url'] = mont_return_form_url( $current );
	}

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
 * Render return-form button (B2C / B2B product pages). Empty when region has no form.
 *
 * @param array $args Optional args.
 * @return string
 */
function mont_return_form_button( array $args = array() ) {
	if ( ! mont_return_form_has_form() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'class'     => 'mont_return-form-btn mont_doc-btn',
			'show_icon' => true,
		)
	);

	$labels = mont_return_form_labels();
	$region = mont_return_form_current_region();
	$url    = mont_return_form_url( $region );

	$icon = '';
	if ( $args['show_icon'] ) {
		$icon = '<svg class="mont_size-guide-btn__icon" viewBox="0 0 18 18" width="14" height="14" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg">'
			. '<path d="M4 2.5h10a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.2"/>'
			. '<path d="M6 6.5h6M6 9h6M6 11.5h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>'
			. '</svg>';
	}

	return sprintf(
		'<button type="button" class="%1$s" data-mont-return-open data-mont-return-form="%2$s" data-mont-return-url="%3$s">%4$s<span>%5$s</span></button>',
		esc_attr( $args['class'] ),
		esc_attr( $region ),
		esc_url( $url ),
		$icon,
		esc_html( $labels['button'] )
	);
}
