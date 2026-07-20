<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce/Templates
 * @version     2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
 $user_id = get_current_user_id(); 
  $billing_email = get_user_meta( $user_id, 'billing_email' , true );
   $mailchimp_woocommerce_is_subscribed = get_user_meta( $user_id, 'mailchimp_woocommerce_is_subscribed' , true );
            $user_info  = get_userdata($user_id);
             $first_name = $user_info->first_name;
             $last_name = $user_info->last_name;
?>
<div class="wrap-div dashboard-content">
    <div class="dash-block first-db">
<div class="row">
    <div class="col-md-6 cnter-div">
        <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
            <h3> Create account </h3>
             <p>Here you can keep track of your recent activity, request return/exchange authorizations for orders you have received, and view and edit your account information or list of favorite items.</p>
             <a href="#">Contact</a>
       <?php }else{  ?>
            <h3> Opprett konto</h3>
            <p>Her kan du følge med på den siste aktiviteten din, be om retur-/bytteautorisasjoner for bestillinger du har mottatt, og se og redigere kontoinformasjonen din eller listen over favorittvarer.</p>
            <a href="#">Kontakt Oss</a>
    <?php  } ?>

   
    </div>
        <div class="col-md-6 dashboard-right">
             <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
               <h3>Contact information</h3>
           <?php }else{  ?>
                <h3>Kontaktinformasjon</h3>
        <?php  } ?>
       
        <ul>
          <li><?php echo $first_name.' '.$last_name; ?> </li>
            <li><a href="mailto:<?php echo $billing_email ?>"><?php echo $billing_email ?></a> </li>
        </ul>
         <ul>
             <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
                <li><a href="<?php echo site_url(); ?>/my-account/edit-account/">Edit info</a> </li>
                <li><a href="<?php echo site_url(); ?>/my-account/edit-account/">Change password</a> </li>
           <?php }else{  ?>
                 <li><a href="<?php echo site_url(); ?>/my-account/edit-account/">Rediger info</a> </li>
                <li><a href="<?php echo site_url(); ?>/my-account/edit-account/">Bytt passord</a> </li>
        <?php  } ?>
            
        </ul>
    </div>
    </div>
    </div>
   
    <!------------------------------->
     <div class="dash-block contact-info-block">
            <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
               <h3>Contact information</h3>
           <?php }else{  ?>
                <h3>Kontaktinformasjon</h3>
        <?php  } ?>
<div class="row">
    <div class="col-md-6 cnter-div adress_dash billing_dash">

         <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
             <h4>Billing Adress</h4>
           <?php }else{  ?>
            <h4>Faktureringsadresse</h4>
        <?php  } ?>
   
      <?php wc_get_template( 'myaccount/my-address.php' ); ?>
        
          <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
             <a href="<?php echo site_url(); ?>/my-account/edit-address/billing/" class="edit-address-click">Edit address</a>
           <?php }else{  ?>
            <a href="<?php echo site_url(); ?>/my-account/edit-address/billing/" class="edit-address-click">Rediger info</a>
        <?php  } ?>
    
    </div>
    <div class="col-md-6 dashboard-right adress_dash shipping_dash">
         <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
               <h4> Shipping Adress</h4>
           <?php }else{  ?>
             <h4> Leveringsadresse</h4>
        <?php  } ?>
     
        <?php wc_get_template( 'myaccount/my-address.php' ); ?>
    
     <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
             <a href="<?php echo site_url(); ?>/my-account/edit-address/shipping/">Edit adress</a>
           <?php }else{  ?>
            <a href="<?php echo site_url(); ?>/my-account/edit-address/shipping/">Rediger info</a>
        <?php  } ?>
    
    </div>
    </div>
    </div>
    <!------------------------------->
     <div class="dash-block contact-info-block">
           
           <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
              <h3> Orders </h3>
           <?php }else{  ?>
            <h3> Ordrer </h3>
        <?php  } ?>
<div class="row">
    <div class="col-md-12 cnter-div">

       <!--  <p>You currently have no orders</p> -->
         <?php wc_get_template( 'myaccount/orders.php' ); ?>
    
    
    </div>
    
    </div>
    </div>
        <!------------------------------->
     <div class="dash-block contact-info-block">
            <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
              <h3> Newsletters </h3>
           <?php }else{  ?>
            <h3> nyhetsbrev </h3>
        <?php  } ?>
           
<div class="row">
    <div class="col-md-12 cnter-div newlettr-txt my_account_newlettr">
        <?php
        if ($mailchimp_woocommerce_is_subscribed) {
            $sub_chek = 'checked';
        }else{
           $sub_chek = '';
        }
        
        ?>

     <div class="form-group">
         <label class="switchs" for="snewsletter" >
            <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
             <p> You currently have no orders Get all the news &amp; updates from our store</p>
           <?php }else{  ?>
            <p> Du har for øyeblikket ingen bestillinger Få alle nyhetene og oppdateringene fra butikken vår</p>
            <?php  } ?>
            
            <input id="snewsletter" name="snewsletter" type="checkbox"  <?php echo $sub_chek ?> data-user_id="<?php echo $user_id; ?>" class="slide-switch" value="1"/>
            <div class="slider round"></div>
        </label>
    </div>
    
    
    </div>
    
    </div>
    </div>
<?php
	/**
	 * My Account dashboard.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_dashboard' );

	/**
	 * Deprecated woocommerce_before_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_before_my_account' );

	/**
	 * Deprecated woocommerce_after_my_account action.
	 *
	 * @deprecated 2.6.0
	 */
	do_action( 'woocommerce_after_my_account' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
?>
</div>