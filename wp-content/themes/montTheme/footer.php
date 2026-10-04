<?php echo do_shortcode('[custom_elementor_template id="20388"]'); ?>


<!-- Announcement Bar -->
<div id="announcement-bar" style="display: none;">
    <?php echo esc_html( function_exists( 'mont_pdp_t' ) ? mont_pdp_t( 'discount_applied' ) : 'Discount Applied! Your coupon has been automatically added.' ); ?>
</div>

<!-- Popup Container -->
<?php 
$discount = new Custom_WooCommerce_Coupon();
$disc_pct = isset( $discount->discount_amount ) ? $discount->discount_amount : '';
$disc_title = function_exists( 'mont_pdp_t' )
	? sprintf( mont_pdp_t( 'discount_popup_title' ), $disc_pct )
	: sprintf( 'GET %s%% OFF YOUR FIRST ORDER!', $disc_pct );
?>
<style>
span.error-code {
    color: red;
    font-size: 14px;
    font-weight: 300;
    margin-bottom: 30px;
    line-height: 1.5;
}
</style>

 <div class="popup-container" id="discount-popup" style="display:none;">
        <button class="popup-close" id="close-popup">×</button>
        <div class="popup-left">
            <h2 class="popup-title"><?php echo esc_html( $disc_title ); ?>
</h2>
            <p class="popup-description">
               <?php echo esc_html( function_exists( 'mont_pdp_t' ) ? mont_pdp_t( 'discount_popup_desc' ) : 'Leave your email and get our newsletter and offers.' ); ?>
            </p>
            <form id="subscribe-form">
				<span class="error-code"></span>
            <input type="email" id="email-input" class="popup-input" placeholder="<?php echo esc_attr( function_exists( 'mont_pdp_t' ) ? mont_pdp_t( 'your_email' ) : 'Your email' ); ?>">
            <button class="popup-button"><?php echo esc_html( function_exists( 'mont_pdp_t' ) ? mont_pdp_t( 'subscribe_discount' ) : 'Subscribe & Get Discount' ); ?></button>
            </button>
            <p class="popup-terms">
                <?php echo esc_html( function_exists( 'mont_pdp_t' ) ? mont_pdp_t( 'subscribe_terms' ) : '' ); ?>
            </p>
        </div>
        
        <div class="popup-right">
            <div class="overlay"></div>
            <div class="discount-text"><?php //echo $discount->discount_amount; ?></div>
            <img src="<?php echo esc_url( content_url( 'uploads/2025/08/IMG_4560_4-5.jpg' ) ); ?>" alt="Monte Napoleone Exclusive Offer" class="popup-image">
        </div>
    </div>
    </div>
<div id="sticky-popup-btn" style="display: none;"><?php echo esc_html( sprintf( function_exists( 'mont_pdp_t' ) ? mont_pdp_t( 'sticky_discount' ) : '%s%% off first purchase', $disc_pct ) ); ?></div>
<?php wp_footer(); ?>
</body>
</html>




