<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
// do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>


<div class="mont_single_product_container">
	<div class="mont_top_layout">
		<div class="mont_layout_fifty">
			<div class="mont_gallery_flex_desplay">
				<!-- gallery  loop will go here-->
			</div>
		</div>

		<div class="mont_layout_fifty">
			<div class="mont_product_des">
				<p>
					<!-- product description will go here -->
					<span class="mont_show_hide_desc_text">Show</span>
				</p>
			</div>
			<div class="mont_custom_options">
				<div class="mont_custom_option_list_loop">
					<div class="mont_custom_single_loop_item">
						<!-- Custom product blocks will go here -->
					</div>
				</div>
			</div>
			<div class="mont_add_to_cart_button_and_alert">
				<div class="mont_cart_button"> 
					<!-- CArt button go here -->
					<a href="?add-to-cart=62" 
					data-quantity="1" 
					class="button add_to_cart_button ajax_add_to_cart" 
					data-product_id="62">
					Add to Cart
				</a>

			</div>
			<div class="mont_alerts">
				<!-- alert go here -->
			</div>
		</div>
	</div>
</div>

<div class="mont_bottom_layout">
	<div class="mont_related_products_slider">
		<div class="mont_slider_single_product_page">
			<!-- loop slider items -->
		</div>
	</div>
</div>

</div>


