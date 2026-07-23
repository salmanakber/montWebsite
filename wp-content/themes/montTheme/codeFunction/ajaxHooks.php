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

    wp_enqueue_style('googleFonts', 'https://fonts.googleapis.com');
    wp_enqueue_style('googleFontspre', 'https://fonts.gstatic.com');
    wp_enqueue_style('googleFonts-fonts', 'https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap');
 	

    wp_enqueue_script('mont-gallery-js', $theme_uri . '/assets/productGallery.js', array('jquery'), filemtime($theme_dir . '/assets/productGallery.js'), true);
    wp_enqueue_script('mont-product-gallery-slider', $theme_uri . '/assets/product-gallery-slider.js', array(), filemtime($theme_dir . '/assets/product-gallery-slider.js'), true);
    wp_enqueue_script('mont-header-js', $theme_uri . '/assets/header.js', array('jquery'), filemtime($theme_dir . '/assets/header.js'), true);
    wp_enqueue_script('mont-discount-js', $theme_uri . '/assets/discount.js', array('jquery'), null, true);
    wp_enqueue_script('mont-gallery-size-js', $theme_uri . '/assets/custom-sizes.js', array('jquery'), null, true);
    wp_enqueue_script('mont-gallery-size-javascript', $theme_uri . '/assets/custom-sizes-javascript.js', array('jquery'), null, true);
    wp_enqueue_script('mont-custom-jquery', $theme_uri . '/assets/custom.js', array('jquery'), null, true);
    wp_enqueue_script('mont-cart-js', $theme_uri . '/assets/cart.js', array('jquery'), filemtime($theme_dir . '/assets/cart.js'), true);
    wp_enqueue_script('lucide-icon', 'https://unpkg.com/lucide@latest');

    wp_enqueue_script('mont-variation-ajax', $theme_uri . '/assets/variation-ajax.js', array('jquery'), filemtime($theme_dir . '/assets/variation-ajax.js'), true);
    wp_localize_script('mont-variation-ajax', 'ajaxurl', array('url' => admin_url('admin-ajax.php')));

}

public function custom_theme_setup() {
    register_nav_menus([
        'primary' => __('Primary Menu', 'your-theme-montenapoleone'),
        'footer' => __('Footer Menu', 'your-theme-montenapoleone'),
    ]);
}


public function get_variation_details_by_attributes() {
    
	$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $attribute = isset($_POST['attributes']) ? sanitize_text_field($_POST['attributes']) : '';
    $value = isset($_POST['slugValue']) ? sanitize_text_field($_POST['slugValue']) : '';

    $product = wc_get_product($_POST['product_id']);

    if (!$product || !$product->is_type('variable')) {
        wp_send_json_error('Invalid product');
    }

    $variations = $product->get_available_variations();
    $filtered_variations = array();

    foreach ($variations as $variation) {
        if (isset($variation['attributes']["attribute_$attribute"]) && $variation['attributes']["attribute_$attribute"] === $value) {
            $filtered_variations[] = $variation;
        }
    }

    wp_send_json_success($filtered_variations);

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