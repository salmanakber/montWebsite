<?php

/**

 * The Template for displaying all single products

 *

 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.

 *

 * HOWEVER, on occasion WooCommerce will need to update template files and you

 * (the theme developer) will need to copy the new files to your theme to

 * maintain compatibility. We try to do this as little as possible, but it does

 * happen. When this occurs the version of the template file will be bumped and

 * the readme will list any important changes.

 *

 * @see         https://docs.woocommerce.com/document/template-structure/

 * @package     WooCommerce/Templates

 * @version     1.6.4

 */



if ( ! defined( 'ABSPATH' ) ) {

	exit; // Exit if accessed directly

}



get_header( 'shop' ); ?>

<div class="main-shop pd120">

<div class="container">

	   <div class="row">

	   	<!-- <div class="col-md-1">

	   	</div> -->

        <div class="col-md-7" style="text-align: center; color: #77a464 !important;">

        	<?php 

if( has_term( 'suits', 'product_cat' ) ) {

	//if(isset($_GET['lang']) && $_GET['lang']=='en'): ?>
<!-- 		<div class="cls-ship-text" style="margin-top: 10px; margin-bottom: 30px; font-size: 12px;"><span>Free shipping worldwide.<br> Home delivery in <?php //echo get_field("delivery_days"); ?> working days.
	<?php
	//else:  ?>
			<div class="cls-ship-text" style="margin-top: 10px; margin-bottom: 30px; font-size: 12px;"><span>Gratis frakt over hele verden.<br> Hjemlevering innen <?php //echo get_field("delivery_days"); ?> virkedager.
	<?php //endif;?> -->


<!-- </span> -->

</div>

<?php }

else{ 

	//if(isset($_GET['lang']) && $_GET['lang']=='en'): ?>
<!-- 		<div class="cls-ship-text" style="margin-top: 10px; margin-bottom: 30px; font-size: 12px;"><span>Free shipping worldwide.<br> Home delivery in  <?php// echo get_field("delivery_days"); ?> working days. -->

	<?php
	//else:  ?>
<!-- 		<div class="cls-ship-text" style="margin-top: 10px; margin-bottom: 30px; font-size: 12px;"><span>Gratis frakt over hele verden.<br> Hjemlevering innen <?php //echo get_field("delivery_days"); ?> virkedager.

	<?php //endif; ?>
	
</span> -->




	

<?php 
}

?>



</div>

 </div>

    <div class="row">

        <div class="col-md-12 cccvvddd">

	<?php

		/**

		 * woocommerce_before_main_content hook.

		 *

		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)

		 * @hooked woocommerce_breadcrumb - 20

		 */

		do_action( 'woocommerce_before_main_content' );

	?>



		<?php while ( have_posts() ) : ?>

			<?php the_post(); ?>



			<?php wc_get_template_part( 'content', 'single-product' ); ?>



		<?php endwhile; // end of the loop. ?>



	<?php

		/**

		 * woocommerce_after_main_content hook.

		 *

		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)

		 */

		do_action( 'woocommerce_after_main_content' );

	?>

	<?php

		/**

		 * woocommerce_sidebar hook.

		 *

		 * @hooked woocommerce_get_sidebar - 10

		 */

		do_action( 'woocommerce_sidebar' );

	?>

</div>

    </div>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get the previous URL (referrer)
    const previousUrl = document.referrer;

    // Check if the previous URL is the specified category page and it's a desktop viewport
//    if ((previousUrl.includes("flanell-skjorter") || previousUrl.includes("linskjorte")) && window.innerWidth > 1024) {
//         // Select the .cartButton element and apply styles
//         const cartButton = document.querySelector(".cartButton");
//         if (cartButton) {
//             cartButton.style.cssText = "position: absolute !important; top: 685px !important; width: 461px !important;";
//         }
//     }
});
</script>

<?php

get_footer( 'shop' );



/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */