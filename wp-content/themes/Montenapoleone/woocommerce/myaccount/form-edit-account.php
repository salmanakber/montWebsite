<?php
/**
 * Edit account form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-account.php.
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

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' ); ?>

<form class="woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?> >

<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
               <h3>Change Personal information</h3>
           <?php }else{  ?>
                <h3>Endre personlig informasjon</h3>
        <?php  } ?>
	<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

	<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
		<label for="account_first_name"><?php esc_html_e( 'First name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" placeholder="First Name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>"  />
	        <?php } else { ?>
        		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" placeholder="Fornavn" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>"  />
   		<?php  } ?>
		
	</p>
	<p class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last">
		<label for="account_last_name"><?php esc_html_e( 'Last name', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" placeholder="Last Name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name );  ?>" />
	        <?php } else { ?>
        		<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" placeholder="Etternavn" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name );  ?>" />
   		<?php  } ?>
		
	</p>
	<div class="clear"></div>


	<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
		<label for="account_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" placeholder="Email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
	        <?php } else { ?>
        		<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" placeholder="E-post" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
   		<?php  } ?>
		
	</p>

	<fieldset>
		<legend><?php esc_html_e( 'Password change', 'woocommerce' ); ?></legend>

		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_current"><?php esc_html_e( 'Current password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" placeholder="Current Password" id="password_current" autocomplete="off" />
	        <?php } else { ?>
        		<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" placeholder="Nåværende passord" id="password_current" autocomplete="off" />
   		<?php  } ?>
			
		</p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_1"><?php esc_html_e( 'New password (leave blank to leave unchanged)', 'woocommerce' ); ?></label>
			<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" placeholder="New password" autocomplete="off" />
	        <?php } else { ?>
        		<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" placeholder="Nytt passord" autocomplete="off" />
   		<?php  } ?>
			
		</p>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			<label for="password_2"><?php esc_html_e( 'Confirm new password', 'woocommerce' ); ?></label>
			<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" placeholder="Confirm new password" autocomplete="off" />
	        <?php } else { ?>
        		<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" placeholder="Bekrefte nytt passord" autocomplete="off" />
   		<?php  } ?>
			
		</p>
	</fieldset>
	<div class="clear"></div>

	<?php do_action( 'woocommerce_edit_account_form' ); ?>

	<p>
		<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
        		<button type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
	        <?php } else { ?>
        		<button type="submit" class="woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Lagre endringer', 'woocommerce' ); ?></button>
   		<?php  } ?>
		
		<input type="hidden" name="action" value="save_account_details" />
	</p>

	<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
</form>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
