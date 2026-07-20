<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.5
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<table class="variations" cellspacing="0">
			<tbody>
				<?php foreach ( $attributes as $attribute_name => $options ) : ?>
					<tr>
						<td class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></td>
						<td class="value">
							<?php
								wc_dropdown_variation_attribute_options(
									array(
										'options'   => $options,
										'attribute' => $attribute_name,
										'product'   => $product,
									)
								);
								echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="single_variation_wrap">
			<?php
				/**
				 * Hook: woocommerce_before_single_variation.
				 */
				do_action( 'woocommerce_before_single_variation' );

				/**
				 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
				 *
				 * @since 2.4.0
				 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
				 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
				 */
				do_action( 'woocommerce_single_variation' );

				/**
				 * Hook: woocommerce_after_single_variation.
				 */
				do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery(document).on('change', 'form.variations_form table.variations tr td select', function(){
			setTimeout(function(){ 
				var image = jQuery('.woocommerce-variation-image').attr('data-image');

				if (typeof image !== 'undefined' && image !== '' && image !== null){

	 				jQuery(".slick-list .slick-track .slick-slide").each(function() {
					   var $imag = jQuery(this).find("img[src='"+image+"']");
					    if($imag.hasClass('zoom_image'))
					    {
					          console.log('count1'+count1);
					        var count1 = $imag.parent().parent().attr('data-slick-index');
					        if (typeof count1 !== 'undefined'){
					        		 console.log('select count1');
					        		count1 = parseInt(count1);
					             jQuery('#slide-nav-vertical .draggable .slick-track').find(".single_thumb_box[data-slick-index='"+count1+"']").trigger('click');
					                return false;
					        }
					    }
					    
					    if($imag.hasClass('zoom_image'))
					    {
	 				        var count2 = $imag.parent().attr('data-slick-index');
					          console.log('count2'+count2);
					         if (typeof count2 !== 'undefined'){
					         	 console.log('select count2');
					         	 count2 = parseInt(count2);
					             jQuery('#slide-nav-vertical .draggable .slick-track').find(".single_thumb_box[data-slick-index='"+count2+"']").trigger('click');
					                return false;
					        }
					    }  
					});
	 			}

			}, 300);
		})
	});
</script>
<?php
do_action( 'woocommerce_after_add_to_cart_form' );
