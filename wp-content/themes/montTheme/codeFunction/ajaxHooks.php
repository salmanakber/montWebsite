<?php

class ajaxHooks
{
	
	function __construct()
	{
	add_action('wp_enqueue_scripts', array($this, 'mytheme_enqueue_styles'));
	add_action('admin_enqueue_scripts', array($this, 'mytheme_enqueue_styles'));
	add_action('wp_ajax_get_variation_details', array($this,'get_variation_details_by_attributes'));
	add_action('wp_ajax_nopriv_get_variation_details', array($this,'get_variation_details_by_attributes'));
	add_action("wp_ajax_update_cart_count", array($this,'update_cart_count'));
	add_action("wp_ajax_nopriv_update_cart_count", array($this,'update_cart_count')); // For guests
	add_action("wp_ajax_custom_ajax_add_to_cart", array($this,'custom_ajax_add_to_cart'));
	add_action("wp_ajax_nopriv_custom_ajax_add_to_cart", array($this,'custom_ajax_add_to_cart')); // For non-logged users
	add_action('after_setup_theme', array($this,'custom_theme_setup'));
	add_action('woocommerce_update_product', array($this, 'bust_fit_size_cache'));
	add_action('woocommerce_save_product_variation', array($this, 'bust_fit_size_cache_from_variation'), 10, 1);
	}

	/**
	 * Drop cached fit→size maps when variations change.
	 */
	public function bust_fit_size_cache( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id > 0 ) {
			delete_transient( 'mont_fitsizes_v1_' . $product_id );
		}
	}

	public function bust_fit_size_cache_from_variation( $variation_id ) {
		$parent = wp_get_post_parent_id( (int) $variation_id );
		if ( $parent ) {
			$this->bust_fit_size_cache( $parent );
		}
	}

	/**
	 * Fast fit_slug => [ size_slug, ... ] map via one SQL query (no wc_get_product loop).
	 *
	 * @param int $product_id
	 * @return array
	 */
	public static function get_fit_size_map( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return array();
		}

		$cache_key = 'mont_fitsizes_v1_' . $product_id;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$children = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_parent = %d
				   AND post_type = 'product_variation'
				   AND post_status IN ('publish','private')",
				$product_id
			)
		);

		if ( empty( $children ) ) {
			set_transient( $cache_key, array(), 12 * HOUR_IN_SECONDS );
			return array();
		}

		$ids_sql = implode( ',', array_map( 'intval', $children ) );
		$rows    = $wpdb->get_results(
			"SELECT post_id, meta_key, meta_value
			 FROM {$wpdb->postmeta}
			 WHERE post_id IN ({$ids_sql})
			   AND meta_key IN ('attribute_pa_body-fit', 'attribute_pa_size')"
		);

		$by_var = array();
		foreach ( (array) $rows as $row ) {
			$by_var[ (int) $row->post_id ][ $row->meta_key ] = $row->meta_value;
		}

		$map = array();
		foreach ( $by_var as $meta ) {
			$fit  = isset( $meta['attribute_pa_body-fit'] ) ? (string) $meta['attribute_pa_body-fit'] : '';
			$size = isset( $meta['attribute_pa_size'] ) ? (string) $meta['attribute_pa_size'] : '';
			if ( $fit === '' || $size === '' ) {
				continue;
			}
			if ( ! isset( $map[ $fit ] ) ) {
				$map[ $fit ] = array();
			}
			$map[ $fit ][ $size ] = true;
		}

		foreach ( $map as $fit => $sizes ) {
			$map[ $fit ] = array_keys( $sizes );
		}

		set_transient( $cache_key, $map, 12 * HOUR_IN_SECONDS );
		return $map;
	}

	/**
	 * Global fit___size → measurement numbers (no images). Small payload, cached.
	 *
	 * @return array
	 */
	public static function get_size_chart_map() {
		$cache_key = 'mont_all_charts_meas_v2';
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'variation_settings';
		// Table may not exist on fresh installs.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			set_transient( $cache_key, array(), HOUR_IN_SECONDS );
			return array();
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT attributes, body_fit, size_slug, shirt_length, sleeve_length, shoulder, half_chest, half_waist, half_bottom
			 FROM {$table}"
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$key = ! empty( $row->attributes ) ? $row->attributes : '';
			if ( ! $key && ! empty( $row->body_fit ) && ! empty( $row->size_slug ) ) {
				$key = $row->body_fit . '___' . $row->size_slug;
			}
			if ( ! $key ) {
				continue;
			}
			$map[ $key ] = array(
				'attributes'    => $key,
				'body_fit'      => $row->body_fit,
				'size_slug'     => $row->size_slug,
				'shirt_length'  => $row->shirt_length,
				'sleeve_length' => $row->sleeve_length,
				'shoulder'      => $row->shoulder,
				'half_chest'    => $row->half_chest,
				'half_waist'    => $row->half_waist,
				'half_bottom'   => $row->half_bottom,
				'images'        => array(),
			);
			// Also index by body_fit___size_slug so JS keys always resolve.
			$alt = ( ! empty( $row->body_fit ) && ! empty( $row->size_slug ) )
				? $row->body_fit . '___' . $row->size_slug
				: '';
			if ( $alt && $alt !== $key ) {
				$map[ $alt ] = $map[ $key ];
			}
		}

		set_transient( $cache_key, $map, 12 * HOUR_IN_SECONDS );
		return $map;
	}

	public function addingToCart()
	{

	}

	public function mytheme_enqueue_styles() {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    wp_enqueue_style('mont-style', $theme_uri . '/assets/style.css', array(), filemtime($theme_dir . '/assets/style.css'));
    wp_enqueue_style('mont-style-product-page', $theme_uri . '/assets/product-page.css', array(), filemtime($theme_dir . '/assets/product-page.css'));
    wp_enqueue_style('mont-style-gallery', $theme_uri . '/assets/productGallery.css', array(), filemtime($theme_dir . '/assets/productGallery.css'));
    wp_enqueue_style('mont-category-tabs', $theme_uri . '/assets/category-tabs.css', array('mont-style'), filemtime($theme_dir . '/assets/category-tabs.css'));
    wp_enqueue_script('mont-category-tabs', $theme_uri . '/assets/category-tabs.js', array(), filemtime($theme_dir . '/assets/category-tabs.js'), true);

    wp_enqueue_style('googleFonts', 'https://fonts.googleapis.com');
    wp_enqueue_style('googleFontspre', 'https://fonts.gstatic.com');
    wp_enqueue_style('googleFonts-fonts', 'https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap');
 	

    wp_enqueue_script('mont-gallery-js', $theme_uri . '/assets/productGallery.js', array('jquery'), filemtime($theme_dir . '/assets/productGallery.js'), true);
    wp_enqueue_script('mont-product-gallery-slider', $theme_uri . '/assets/product-gallery-slider.js', array(), filemtime($theme_dir . '/assets/product-gallery-slider.js'), true);
    wp_enqueue_script('mont-header-js', $theme_uri . '/assets/header.js', array('jquery'), filemtime($theme_dir . '/assets/header.js'), true);
    wp_enqueue_script('mont-discount-js', $theme_uri . '/assets/discount.js', array('jquery'), filemtime($theme_dir . '/assets/discount.js'), true);
    wp_enqueue_script('mont-gallery-size-js', $theme_uri . '/assets/custom-sizes.js', array('jquery'), filemtime($theme_dir . '/assets/custom-sizes.js'), true);
    wp_enqueue_script('mont-gallery-size-javascript', $theme_uri . '/assets/custom-sizes-javascript.js', array('jquery'), filemtime($theme_dir . '/assets/custom-sizes-javascript.js'), true);
    wp_enqueue_script('mont-custom-jquery', $theme_uri . '/assets/custom.js', array('jquery'), filemtime($theme_dir . '/assets/custom.js'), true);
    wp_enqueue_script('mont-cart-js', $theme_uri . '/assets/cart.js', array('jquery'), filemtime($theme_dir . '/assets/cart.js'), true);
    wp_enqueue_script('lucide-icon', 'https://unpkg.com/lucide@latest');

    wp_enqueue_script('mont-variation-ajax', $theme_uri . '/assets/variation-ajax.js', array('jquery'), filemtime($theme_dir . '/assets/variation-ajax.js'), true);
    $localize = array(
        'url'       => admin_url('admin-ajax.php'),
        'fitSizes'  => new stdClass(),
        'charts'    => new stdClass(),
        'diagrams'  => new stdClass(),
    );
    $is_product_page = ! is_admin()
        && (
            ( function_exists( 'is_product' ) && is_product() )
            || ( function_exists( 'is_singular' ) && is_singular( 'product' ) )
        );
    if ( $is_product_page ) {
        $pid = get_queried_object_id();
        if ( $pid ) {
            $localize['productId'] = (int) $pid;
            $localize['fitSizes']  = self::get_fit_size_map( $pid );
            $localize['charts']    = self::get_size_chart_map();
            // All fit×size diagram URLs for client-side cache + browser preload.
            if ( class_exists( 'Mont_Size_Diagram_Helper' ) ) {
                $diag = Mont_Size_Diagram_Helper::get_diagram_embed_map();
                // Guarantee keys match this product's WC fit/size slugs.
                foreach ( (array) $localize['fitSizes'] as $fit_slug => $size_slugs ) {
                    foreach ( (array) $size_slugs as $size_slug ) {
                        $key = $fit_slug . '___' . $size_slug;
                        if ( ! empty( $diag[ $key ] ) ) {
                            continue;
                        }
                        $images = Mont_Size_Diagram_Helper::lean_images_for_js(
                            Mont_Size_Diagram_Helper::get_frontend_images(
                                (string) $fit_slug,
                                (string) $size_slug,
                                '{}',
                                true
                            )
                        );
                        if ( ! empty( $images ) ) {
                            $diag[ $key ] = $images;
                        }
                    }
                }
                $localize['diagrams'] = ! empty( $diag ) ? $diag : new stdClass();
            }
        }
    }
    wp_localize_script('mont-variation-ajax', 'ajaxurl', $localize);

}

public function custom_theme_setup() {
    register_nav_menus([
        'primary' => __('Primary Menu', 'your-theme-montenapoleone'),
        'footer' => __('Footer Menu', 'your-theme-montenapoleone'),
    ]);
}


public function get_variation_details_by_attributes() {
	$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
	$value      = isset( $_POST['slugValue'] ) ? sanitize_text_field( wp_unslash( $_POST['slugValue'] ) ) : '';

	if ( ! $product_id || $value === '' ) {
		wp_send_json_error( 'Invalid product' );
	}

	$map   = self::get_fit_size_map( $product_id );
	$sizes = isset( $map[ $value ] ) ? $map[ $value ] : array();

	// Fallback: some stores pass display name; try case-insensitive slug match.
	if ( empty( $sizes ) ) {
		$needle = strtolower( $value );
		foreach ( $map as $fit => $fit_sizes ) {
			if ( strtolower( (string) $fit ) === $needle ) {
				$sizes = $fit_sizes;
				break;
			}
		}
	}

	$filtered = array();
	foreach ( (array) $sizes as $size ) {
		$filtered[] = array(
			'attributes' => array(
				'attribute_pa_size' => (string) $size,
			),
		);
	}

	wp_send_json_success( $filtered );
}

public function update_cart_count() {
    wp_send_json_success(["count" => WC()->cart->get_cart_contents_count()]);
}


public function custom_ajax_add_to_cart() {
    // if (isset($_POST['product_id'])) {
    //     $product_id = absint($_POST['product_id']);
    //     $quantity = 1;
    //     $added = WC()->cart->add_to_cart($product_id, $quantity);

    //     if ($added) {
    //         wp_send_json_success(["message" => "Product added to cart"]);
    //     } else {
    //         wp_send_json_error(["message" => "Failed to add to cart"]);
    //     }
    // }
    // wp_die();
}






}



new ajaxHooks();



?>