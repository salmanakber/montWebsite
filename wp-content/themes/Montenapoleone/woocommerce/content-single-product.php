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
 * @see     https://docs.woocommerce.com/document/template-structure/
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
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

	<?php
	/**
	 * Hook: woocommerce_before_single_product_summary.
	 *
	 * @hooked woocommerce_show_product_sale_flash - 10
	 * @hooked woocommerce_show_product_images - 20
	 */
	do_action( 'woocommerce_before_single_product_summary' );
	?>

	<div class="summary entry-summary">
		<?php
		/**
		 * Hook: woocommerce_single_product_summary.
		 *
		 * @hooked woocommerce_template_single_title - 5
		 * @hooked woocommerce_template_single_rating - 10
		 * @hooked woocommerce_template_single_price - 10
		 * @hooked woocommerce_template_single_excerpt - 20
		 * @hooked woocommerce_template_single_add_to_cart - 30
		 * @hooked woocommerce_template_single_meta - 40
		 * @hooked woocommerce_template_single_sharing - 50
		 * @hooked WC_Structured_Data::generate_product_data() - 60
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>
       <?php

  $product_tabs = apply_filters('woocommerce_product_tabs', array());



  if (!empty($product_tabs)) : ?>
<div class="tabs-cont avc">
	<div class="">
		<?php foreach ($product_tabs as $key => $product_tab) : ?>
		<?php
		if(esc_attr($key) != 'reviews' AND esc_attr($key) != 'additional_information' AND esc_attr($key) != 'details' AND  esc_attr($key) != 'description')
		{
			?>
		<div class="col-6 p-0 tabsData tabsName_<?php echo $key;?>">
			<p>
		<?php call_user_func($product_tab['callback'], $key, $product_tab); ?>
			</p>
		</div>
		<?php
		}
	?>
		 <?php endforeach; ?>
			<div class="col-6 p-0 tabsData dynamictext">
		   <?php 
      if(isset($_GET['lang']) && $_GET['lang'] =='en'){
      if( has_term( 'suits', 'product_cat' ) ) { ?>
       <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage aaa">
		   <div class="closep">X</div>
          <h6>CANCELLATION & RETURNS FOR TAILOR MADE SHIRTS.</h6>
          <p> All tailor-made shirts is 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p>
          <h6>DELIVERYTIME FOR TAILOR MADE SHIRTS.</h6>
          <p>All tailor-made shirts require more work and changing new body parts therefore we have to add up-to seven (7) days extra additional to normal delivery time. </p>
       </div>
     <?php } else{ ?>
       <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage vbs">
		   <div class="closep"><i class="fa fa-times" aria-hidden="true"></i></div>
          <h6>CANCELLATION & RETURNS FOR TAILOR MADE SHIRTS.</h6>
          <p> All tailor-made shirts are 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p>
          <h6>DELIVERYTIME FOR TAILOR MADE SHIRTS.</h6>
          <p>All tailor-made shirts require more work and changing new body parts therefore we have to add up-to seven (7) days extra additional to normal delivery time. </p>
       </div>
      <?php } ?>

    <?php  
    }else{
       if( has_term( 'suits', 'product_cat' ) ) { ?>
         <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage as">
			 <div class="closep"><i class="fa fa-times" aria-hidden="true"></i></div>
            <h6>KANSELLERING OG RETUR FOR SKREDDERSYKTE SKJORTER.</h6>
            <p> Alle skreddersydde skjorter er 100% individuelt tilpasset etter kundens preferanser. Derfor aksepterer vi IKKE returer av noen grunn bortsett fra produksjonsfeil.</p>
            <h6>LEVERINGSTID FOR SKREDDERSYKTE SKJORTER.</h6>
            <p>Alle skreddersydde skjorter krever mer arbeid og skifter av nye deler, derfor må vi legge til opptil syv (7) dager ekstra i tillegg til normal leveringstid.</p>
         </div>
       <?php } else{ ?>
         <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage vd">
			 <div class="closep"><i class="fa fa-times" aria-hidden="true"></i></div>
            <h6>KANSELLERING OG RETUR FOR SKREDDERSYKTE SKJORTER.</h6>
            <p> Alle skreddersydde skjorter er 100% individuelt tilpasset etter kundens preferanser. Derfor aksepterer vi IKKE returer av noen grunn bortsett fra produksjonsfeil.</p>
            <h6>LEVERINGSTID FOR SKREDDERSYKTE SKJORTER.</h6>
            <p>Alle skreddersydde skjorter krever mer arbeid og skifter av nye deler, derfor må vi legge til opptil syv (7) dager ekstra i tillegg til normal leveringstid.</p>
         </div>
        <?php } ?>
         <?php }
?>		
		</div>
	</div>
</div>

<!--     <div class="woocommerce-tabs wc-tabs-wrapper" style>

<!--       <ul class="tabs wc-tabs" role="talist"> -->

        <?php //foreach ($product_tabs as $key => $product_tab) : ?>
<!--           <li class="<?php //echo esc_attr($key); ?>_tab" id="tab-title-<?php //echo esc_attr($key); ?>" role="tab" aria-controls="tab-<?php //echo esc_attr($key); ?>"> -->

<!--             <a href="#tab-<?php //echo esc_attr($key); ?>"> -->

              <?php //echo wp_kses_post(apply_filters('woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key)); ?>

<!--             </a>

          </li> -->

        <?php //endforeach; ?>

<!--       </ul> -->

      <?php //foreach ($product_tabs as $key => $product_tab) : ?>

<!--         <div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr($key); ?> panel entry-content wc-tab" id="tab-<?php //echo esc_attr($key); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr($key); ?>"> -->

          <?php

//           if (isset($product_tab['callback'])) {

//             call_user_func($product_tab['callback'], $key, $product_tab);

//           }

          ?>

<!--         </div> -->

      <?php //endforeach; ?>



    <?php //do_action('woocommerce_product_after_tabs'); ?>

<!--     </div> -->



  <?php endif;


?>
	</div>

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>