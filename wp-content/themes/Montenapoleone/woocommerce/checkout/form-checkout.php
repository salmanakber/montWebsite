<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
echo get_transient('update_over_all_status');

 delete_transient( 'update_over_all_status' );
?>
 <div class="container">
 	 <?php  if ( !is_user_logged_in() ) { ?>
      <style type="text/css">
      	 .billing_box{
          display: none;
         }
         .first_next {
          display: none;
         }
     </style>

 <?php } ?>

<div class="steps-fields pt-3">
<div class="row chekout_custmize chk-pd">

	<div class="accordion col-md-6" id="steps">
		<form name="checkout" method="post" class="checkout woocommerce-checkout chkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">
	<?php if ( $checkout->get_checkout_fields() ) : ?>
		<h3 class="step_heading btn btn-step btn-secondary inactive_tab customer_btn" data-toggle="collapse" data-target="#edit-step1">
			 <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
            1. Your details
    		<span class="edit-tab-btn">Edit</span></h3>
       <?php }else{  ?>
             1. Dine detaljer
    		<span class="edit-tab-btn">Rediger</span></h3>
    <?php  } ?>

		<div id="fill_box_billing_html" class="fill_detail_item"></div>
		<div id="edit-step1" class="collapse show step_box" aria-labelledby="headingOne" data-parent="#steps">
				
		        <div class="email_chek_box">
		        	 <?php  if ( !is_user_logged_in() ) { ?>
		        	<div class="login_box">
		        		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			        		<h4>Log in or register a new user</h4>
				        <?php } else { ?>
			        		<h4>Logg inn eller registrer en ny bruker</h4>
		        <?php  } ?>
					
					<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			        		<p class="login_small">Enter your email or mobile number andd will check if you are a new or existing user</p>
				        <?php } else { ?>
			        		<p>Skriv inn e-postadressen eller mobilnummeret ditt og vil sjekke om du er en ny eller eksisterende bruker</p>
		        		<?php  } ?>

					<div class="form-field">
						<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			        		<input name="username" id="username" class="form-input" type="text" placeholder="Email or mobile number">
				        <?php } else { ?>
			        		<input name="username" id="username" class="form-input" type="text" placeholder="E-post eller mobilnummer">
		        		<?php  } ?>
		            
		             </div>
		             <div class="form-field">
		             	<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			        		<input name="password" id="password" class="form-input" type="password" placeholder="password">
				        <?php } else { ?>
			        		<input name="password" id="password" class="form-input" type="password" placeholder="passord">
		        		<?php  } ?>

		             
		             </div>
		             	<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			        		<button id="slogin_submit" class="button" type="button">Send</button>
				        <?php } else { ?>
			        		<button id="slogin_submit" class="button" type="button">Sende</button>
		        		<?php  } ?>
		        		
		        		<div class="login_error_info"></div>
		        	</div>
					
						<div class="guest-box">
							<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			        		<button id="checkout-customer-continue" class="button" type="button">Shop as a guest</button>
				        <?php } else { ?>
			        		<button id="checkout-customer-continue" class="button" type="button">Handle som gjest</button>
		        		<?php  } ?>
						</div>
                       <?php } ?>
						 <div class="billing_box">
						 	<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
				            <?php do_action( 'woocommerce_checkout_billing' ); ?>

				
					        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
							<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
						</div>
						
				</div>
              <div class="row first_next">
			                     <div class="col-sm-auto col-md-3">
			                        <button id="first-next-continue" class="btn btn-primary btn-block scroll-to" type="button" data-toggle="collapse" data-target="#edit-step2"> 
			                        	<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
										            Next
										       <?php }else{  ?>
										             neste
										    <?php  } ?>
										</button>
			                    </div>
		                       	<div class="chekout_error_info"></div>
                
			   </div>
			      <div class="clearfix"></div>

		</div>

	<?php endif; ?>
	<h3 class="step_heading btn btn-step btn-secondary inactive_tab" data-toggle="collapse" data-target="#edit-step2">
		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>   2. Delivery		
       	<?php }else{  ?> 2. Leveranse
    	<?php  } ?>
		<span class="edit-tab-btn">
			<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
		            Edit
		       <?php }else{  ?>
		             Rediger
		    <?php  } ?>	
				</span></h3>
		<div id="fill_box_shipping_html" class="fill_detail_item"></div>
 <div id="edit-step2" class="collapse step_box" aria-labelledby="headingTwo" data-parent="#steps">
	 <div class="tailor_shipping_method">
         
             <div id="shipping_method_html">
                <table class="shop_table websites-depot-checkout-review-shipping-table"></table>
            </div>
       </div>

               <div class="row btn-righht">
                     <div class="col-sm-auto">
                        <button class="btn btn-primary btn-block scroll-to" type="button" data-toggle="collapse" data-target="#edit-step3"><?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
										            Next
										       <?php }else{  ?>
										             neste
										    <?php  } ?></button>
                     </div>
                       <div class="chekout_error_info"></div>
                  </div>
     <div class="clearfix"></div>
   </div>
   <h3 class="step_heading btn btn-step btn-secondary inactive_tab" data-toggle="collapse" data-target="#edit-step3"><?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
										           3. Payment
										       <?php }else{  ?>
										           3. XNUMX. Payment Betaling
										    <?php  } ?> </h3>
    <div id="edit-step3" class="collapse step_box" aria-labelledby="headingFour" data-parent="#steps">
    	 <div class="filed_box" id="payment_wrap">
                    <?php do_action( 'sm_checkout_payment' ); ?>
                      <?php wc_get_template( 'checkout/terms.php' ); ?>
          </div>
           <div class="clearfix"></div>
          <div class="row">
                     <div class="col-sm-auto subscribe">
                     	<p class="form-row form-row privacy validate-required" id="privacy_policy_field" data-priority=""><span class="woocommerce-input-wrapper"><input type="checkbox" class="input-checkbox woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="privacy_policy" id="privacy_policy" checked value="1"><label class="checkbox woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="privacy_policy">
						 I agree to the terms of purchase.&nbsp;<abbr class="required" title="required">*</abbr></label></span></p>
                        <?php do_action( 'woocommerce_review_order_before_submit' ); ?>
                     <div class="clearfix"></div>
                <p class="goto-btn"> <?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="Go To Payment" data-value="Go To Payment">Go To Payment</button>' ); // @codingStandardsIgnoreLine ?></p>
                      

                 <?php do_action( 'woocommerce_review_order_after_submit' ); ?>
                     </div>
                       <div class="chekout_error_info"></div>
                  </div>
    </div>
      <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
    </form>
	</div>
	<div class="col-md-6">
		<div class="side_wrap sticky-top chkkright">
			<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
	<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
		<h4>My Shopping Cart</h4>
       <?php }else{  ?>
		<h4>Min Handlekurv</h4>
    <?php  } ?> 

	


	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
 
	<?php do_action( 'coupon_before_checkout_form' ); ?>

		</div>

	
	
       </div>
    </div>
</div>

</div>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
