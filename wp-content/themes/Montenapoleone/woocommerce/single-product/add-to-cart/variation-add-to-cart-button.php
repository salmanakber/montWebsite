<?php
/**
 * Single variation cart button
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;
global $product;
?>
<div class="block_cart">
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input(
		array(
			'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
			'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
			'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
	)
	);
	do_action( 'woocommerce_after_add_to_cart_quantity');
	?>
	<div class="cartButton">
	<button type="submit" class="single_add_to_cart_button button alt s">
		<?php //$product->single_add_to_cart_text() ?>
		<?php echo esc_html( 'LEGG I HANDLE POSEN' ); ?>
	</button>
		
    </div>
	<div class="quentity_stock">
	<?php 
		//echo "test";
		if(!empty(get_field('product_type',false)))
	{
	 global $product;
$product_id = $product->get_id();	
		$str = get_field('product_type',false);
// Get the product object
$product = wc_get_product($product_id);
$total_stock = "";
// Check if the product object exists and is a product type
if ($str == "TILGJENGELIG") {
    // Get the stock quantity
    $stock_quantity = $product->get_stock_quantity();

    // Check if the product is in stock
    if ($stock_quantity > 0) {
        $total_stock = $stock_quantity;
    } else {
        $total_stock = 0;
    }
	 echo '<span class="badges_re" style="background: #77a464;padding: 5px 12px;color: #fff;text-transform: uppercase;">'.ucfirst(mb_strtolower(strip_tags($str))).' '.$total_stock.'</span>';
}

 
	}	
	?>
	</div>


<!--    <button type="button" class="sp_back button" onclick="history.back();" style="font-size: 14px;
    line-height: 25px;
    text-align: center;
    color: #ffffff;
    padding: 11px 57px !important;
    font-weight: 300;
    text-transform: uppercase;"><?php //if(isset($_GET['lang']) && $_GET['lang']=='en'):?> Back <?php //else: ?>TILBAKE<?php //endif; ?> </button> -->
	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
	<input type="hidden" name="post_id" class="post_id" value="<?php echo get_the_ID(); ?>" />


</div>
</div>
</div>