<?php echo do_shortcode('[custom_elementor_template id="20388"]'); ?>


<!-- Announcement Bar -->
<div id="announcement-bar" style="display: none;">
    🎉 Discount Applied! Your coupon has been automatically added.
</div>

<!-- Popup Container -->
<?php 
$discount = new Custom_WooCommerce_Coupon();
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
            <h2 class="popup-title">FÅ  <?php echo $discount->discount_amount; ?>% RABATT PÅ DIN FØRSTE ORDRE!
</h2>
            <p class="popup-description">
               Legg igjen din e-post og får vårt nyhetsbrev og tilbud. 
            </p>
            <form id="subscribe-form">
				<span class="error-code"></span>
            <input type="email" id="email-input" class="popup-input" placeholder="Your email">
            <button class="popup-button">Subscribe & Get Discount</button>
            </button>
            <p class="popup-terms">
                *I agree to receive updates from Monte Napoleone including exclusive offers and new collection announcements. Consent is not a condition to purchase. Msg & data rates may apply. You can unsubscribe at any time. View our Terms of Service and Privacy Policy.
            </p>
        </div>
        
        <div class="popup-right">
            <div class="overlay"></div>
            <div class="discount-text"><?php //echo $discount->discount_amount; ?></div>
            <img src="<?php echo home_url() ?>/wp-content/uploads/2025/08/IMG_4560_4-5.jpg" alt="Monte Napoleone Exclusive Offer" class="popup-image">
        </div>
    </div>
    </div>
<div id="sticky-popup-btn" style="display: none;"><?php echo $discount->discount_amount; ?>% rabatt første kjøp</div>
<?php wp_footer(); ?>
</body>
</html>




