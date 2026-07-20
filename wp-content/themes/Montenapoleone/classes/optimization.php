<?php
/* optimization code */

add_action('wp_enqueue_scripts','remove_unused_css_and_js',PHP_INT_MAX);
function remove_unused_css_and_js(){

	if( is_front_page() ){

		wp_dequeue_style('tailor_css');
		wp_deregister_style('tailor_css');

	    wp_dequeue_style('woo-variation-swatches');
		wp_deregister_style('woo-variation-swatches');

	    wp_dequeue_style('woo-variation-swatches-theme-override');
		wp_deregister_style('woo-variation-swatches-theme-override');

	    wp_dequeue_style('woo-variation-swatches-tooltip');
		wp_deregister_style('woo-variation-swatches-tooltip');

	    wp_dequeue_style('woocommerce-layout');
		wp_deregister_style('woocommerce-layout');

	    wp_dequeue_style('woocommerce-smallscreen');
		wp_deregister_style('woocommerce-smallscreen');

	    wp_dequeue_style('woocommerce-general');
		wp_deregister_style('woocommerce-general');

	    wp_dequeue_style('woocommerce-inline');
		wp_deregister_style('woocommerce-inline');

	    wp_dequeue_style('all');
		wp_deregister_style('all');


		wp_dequeue_script('wc-add-to-cart');
		wp_deregister_script('wc-add-to-cart');

		wp_dequeue_script('tailor_ajax-js');
		wp_deregister_script('tailor_ajax-js');

		wp_dequeue_script('woo-variation-swatches');
		wp_deregister_script('woo-variation-swatches');

		wp_dequeue_script('woo-variation-swatches');
		wp_deregister_script('woo-variation-swatches');
		
		wp_dequeue_script('sweetalert-js');
		wp_deregister_script('sweetalert-js');

	}
	// if( is_page_template( 'tailor-template.php' ) )
	// {
	//     wp_dequeue_style('woo-variation-swatches');
	// 	wp_deregister_style('woo-variation-swatches');

	//     wp_dequeue_style('woo-variation-swatches-theme-override');
	// 	wp_deregister_style('woo-variation-swatches-theme-override');

	//     wp_dequeue_style('woo-variation-swatches-tooltip');
	// 	wp_deregister_style('woo-variation-swatches-tooltip');



	// 	wp_dequeue_script('woo-variation-swatches');
	// 	wp_deregister_script('woo-variation-swatches');

	// 	wp_dequeue_script('woo-variation-swatches');
	// 	wp_deregister_script('woo-variation-swatches');		

	// 	wp_dequeue_script('all-js');
	// 	wp_deregister_script('all-js');
	// }

}


add_action('wp_footer','defer_load_banner_images');
function defer_load_banner_images(){
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			//jQuery('.main-slder').find('#myVideo source').attr('src','/wp-content/uploads/2020/08/main-banner-video.mp4');
			//jQuery('.main-slder').find('.banner3').css('background-image','url(/wp-content/themes/dc-garments/images/banner-Img.jpg)');
			//jQuery('.main-slder').find('.banner2').css('background-image','url(/wp-content/uploads/2020/08/banner-3rd.jpg)');
			jQuery('.home-last-sec').find('.facebook_wrap .shirt-itm').css('background-image','url(/wp-content/uploads/2020/07/facebook-img.jpg)');
			jQuery('.home-last-sec').find('.insta_wrap .shirt-itm').css('background-image','url(/wp-content/uploads/2020/07/insta-img.jpg)');
		})
	</script>
	<?php
}



?>