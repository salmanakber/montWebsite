<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>



<div class="u-columns col2-set pd120" id="customer_login">
      <div class="container">
          <div class="row">
              <div class="col-md-12">
	

	<div class="register-form">
        <div class="row">
        <div class="col-md-3">
        	 <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
           <h2 class="create-ac">Create account</h2>
            <h2 class="login-ac">Login</h2>
            <a href="#" class="already-ac login-text">I already have an account</a>
            <a href="#" class="already-ac act-text">Create Account</a>
       <?php }else{  ?>
            <h2 class="create-ac">Opprett konto</h2>
            <h2 class="login-ac">Innlogging</h2>
            <a href="#" class="already-ac login-text">jeg har allerede en konto</a>
            <a href="#" class="already-ac act-text">Opprett konto</a>
    <?php  } ?>

           
        </div>
        
         <div class="col-md-9 ">
             <div class="create-forms">

             	 <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
          		<h2>Create account</h2>
            
       <?php }else{  ?>
            <h2 class="create-ac">Opprett konto</h2>
           
    <?php  } ?>

		 

		<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

			<?php do_action( 'woocommerce_register_form_start' ); ?>
            
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="firstname" id="" autocomplete="firstname" placeholder="*First Name"><?php // @codingStandardsIgnoreLine ?>
					<?php } else { ?>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="firstname" id="" autocomplete="firstname" placeholder="*Fornavn"><?php // @codingStandardsIgnoreLine ?>
					<?php  } ?>
				</p>
            
            
             <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="lastname" id="" autocomplete="lastname" placeholder="*Last Name"><?php // @codingStandardsIgnoreLine ?>
					<?php } else { ?>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="lastname" id="" autocomplete="lastname" placeholder="*Etternavn"><?php // @codingStandardsIgnoreLine ?>
					<?php  } ?>					
				</p>
            
              <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="" autocomplete="email" placeholder="*Email"><?php // @codingStandardsIgnoreLine ?>
					<?php } else { ?>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="" autocomplete="email" placeholder="*E-post"><?php // @codingStandardsIgnoreLine ?>
					<?php  } ?>
					
				</p>
            
            <div class="clearfix"></div>
            
              <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
              		<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="" autocomplete="password" placeholder="*Password"><?php // @codingStandardsIgnoreLine ?>
					<?php } else { ?>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="" autocomplete="password" placeholder="*passord"><?php // @codingStandardsIgnoreLine ?>
					<?php  } ?>
		
				</p>
            
              <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
              	<?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password2" id="" autocomplete="rpassword" placeholder="*Confirm new password"><?php // @codingStandardsIgnoreLine ?>
					<?php } else { ?>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password2" id="" autocomplete="rpassword" placeholder="*Bekrefte nytt passord"><?php // @codingStandardsIgnoreLine ?>
					<?php  } ?>
				
				</p>
            <div class="clearfix"></div>
            <div class="border-topp">
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide subscribe">
		        	
								<input type="checkbox" class="" name="password" id="newsletter" ><?php // @codingStandardsIgnoreLine ?>
			                   
			                   	 <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
			          		<label for="newsletter">Subscribe to newsletter </label>
			            
						       <?php }else{  ?>

						            <label for="newsletter">	Abonner på nyhetsbrev </label>
						           
						    <?php  } ?>

					</p>
            

            
            

			<?php do_action( 'woocommerce_register_form' ); ?>

			<p class="woocommerce-form-row form-row flo-right">
				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
				 <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){ ?>
            <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="Create account">Create account</button>
       <?php }else{  ?>
            <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="Create account">Opprett konto</button>
    <?php  } ?>
				
			</p>
            </div>
			<?php do_action( 'woocommerce_register_form_end' ); ?>

		</form>
             </div>
             <!-----------------------login----------------------->
             <div class="login-form">
		<h2><?php esc_html_e( 'Login', 'woocommerce' ); ?></h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post">

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="Username or email address"><?php // @codingStandardsIgnoreLine ?>
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="Password" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>
              <div class="clearfix"></div>
<div class="border-topp">
			<p class="form-row subscribe wid50">
					<input type="checkbox" class="" name="password" id="remember" ><?php // @codingStandardsIgnoreLine ?>
                   <label for="remember">Remember me </label>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
               
			</p>
     <p class="flo-right">
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
    </p>
			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>
            </div>
			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>
	</div>
             
             
        </div>
        </div>
	</div>
              </div>
    </div>
    </div>
</div>


<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
