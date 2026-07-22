<?php 

require_once get_template_directory(). '/codeFunction/ajaxHooks.php';
require_once get_template_directory(). '/codeFunction/custom-variation.php';
require_once get_template_directory(). '/codeFunction/add-to-cart.php';
require_once get_template_directory(). '/codeFunction/product-helper.php';
require_once get_template_directory(). '/codeFunction/load-more-scroll-class.php';
require_once get_template_directory(). '/codeFunction/discount.php';

function mont_theme_register_acf_options_page() {
    if (!function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page(array(
        'page_title' => 'Product Setting',
        'menu_title' => 'Product Setting',
        'menu_slug'  => 'product-page-setting',
        'capability' => 'manage_options',
        'redirect'   => false,
    ));
}
add_action('acf/init', 'mont_theme_register_acf_options_page');


function dynamic_b2b_b2c_menu() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            var currentUrl = window.location.href;
            if (currentUrl.includes("/monte-connected-b2b")) {
                $(".b2b-b2c-switch a").attr("href", "<?php echo esc_url(home_url('/product-category/linskjorte/')); ?>").text("B2C");
            } else if (currentUrl.includes("/product-category/linskjorte/")) {
                $(".b2b-b2c-switch a").attr("href", "<?php echo esc_url(home_url('/monte-connected-b2b')); ?>").text("B2B");
            }
        });
		
jQuery(document).ready(function($){
const firstLink=$('.category-slider a').first();
const text=$.trim(firstLink.text());
firstLink.text(text+'r');
});
    </script>

<style>

a.button.vipps-express-checkout img {
    border-radius: 42px;
}
a.button.vipps-express-checkout {
    background: transparent !important;
}
</style>
    <?php
}
add_action('wp_footer', 'dynamic_b2b_b2c_menu'); // Use wp_footer to inject script into the page




add_filter('woocommerce_price_format', 'custom_woocommerce_price_format', 10, 2);

function custom_woocommerce_price_format($format, $currency_pos) {
    $currency = class_exists('DC_Product_Manager\\DC_Region_Currency')
        ? \DC_Product_Manager\DC_Region_Currency::get_current_currency()
        : get_woocommerce_currency();

    if ($currency === 'NOK') {
        return '%2$s %1$s';
    }
    return $format;
}


add_filter('woocommerce_currency_symbol', 'change_currency_symbol', 10, 2);
add_filter('woocommerce_get_price_html', 'move_currency_symbol_to_end', 10, 2);

function change_currency_symbol($currency_symbol, $currency) {
    if ($currency === 'NOK') {
        return '';
    }
    return $currency_symbol;
}

function move_currency_symbol_to_end($price, $product) {
	if (get_woocommerce_currency() == 'NOK') {
        // Move the currency symbol (NOK) to the end of the price
		//$price = preg_replace('/([0-9,]+)/', '$1 ' . 'NOK', $price);
	}
	return $price;
}



function custom_elementor_template_shortcode($atts) {
    // Check if Elementor is in edit mode or doing AJAX preview
	if (defined('ELEMENTOR_VERSION') && (\Elementor\Plugin::$instance->editor->is_edit_mode() || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor_ajax'))) {
		return '<div style="border: 1px dashed red; padding: 10px; text-align: center;">Template Preview</div>';
	}

    // Extract the ID attribute from the shortcode
	$atts = shortcode_atts(['id' => ''], $atts, 'custom_elementor_template');

    // Check if Elementor is active and ID is provided
	if (!empty($atts['id']) && class_exists('\Elementor\Plugin')) {
		return \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($atts['id']);
	}

	return '';
}
add_shortcode('custom_elementor_template', 'custom_elementor_template_shortcode');


function custom_admin_assets($hook) {
	if (!is_admin()) return;



	wp_enqueue_script('variation-settings-script', plugin_dir_url(__FILE__) . 'assets/custom-sizes.js', ['jquery'], '1.0', true);

	$script_data = array(
		'ajaxurl' => admin_url('admin-ajax.php'),
		'nonce'   => wp_create_nonce('variation-settings-nonce')
	);
	wp_localize_script('variation-settings-script', 'variationSettings', $script_data);
}
add_action('admin_enqueue_scripts', 'custom_admin_assets', 99);


add_filter( 'template_include', function( $template ) {
	if ( is_product_category() ) {
		return get_template_directory() . '/woocommerce/archive-product.php';
	}
	return $template;
});


// Add a video upload field in WooCommerce product editor
add_action('woocommerce_product_options_general_product_data', function () {
    $video_url = get_post_meta(get_the_ID(), '_product_video', true);
    ?>
    <div class="options_group">
        <p class="form-field">
            <label for="product_video">Product Video</label>
            <input type="hidden" id="product_video" name="product_video" value="<?php echo esc_attr($video_url); ?>" />

            <button type="button" class="button upload_product_video_button">Upload / Select Video</button>
            <button type="button" class="button remove_product_video_button" style="<?php echo $video_url ? '' : 'display:none;'; ?>">Remove Video</button>

            <span class="video-preview" style="display:block;margin-top:10px;">
                <?php if ($video_url): ?>
                    <video controls style="max-width:300px;margin-top:10px;">
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </span>
        </p>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            let fileFrame;

            $(".upload_product_video_button").on("click", function (e) {
                e.preventDefault();

                // Create frame if not exists
                if (!fileFrame) {
                    fileFrame = wp.media({
                        title: "Select or Upload Video",
                        button: { text: "Use this video" },
                        multiple: false,
                        library: { type: "video" }
                    });

                    fileFrame.on("select", function () {
                        const attachment = fileFrame.state().get("selection").first().toJSON();
                        $("#product_video").val(attachment.url);
                        $(".video-preview").html(`<video controls style="max-width:300px;margin-top:10px;"><source src="${attachment.url}" type="video/mp4"></video>`);
                        $(".remove_product_video_button").show();
                    });
                }

                fileFrame.open();
            });

            // Remove video handler
            $(".remove_product_video_button").on("click", function () {
                $("#product_video").val('');
                $(".video-preview").html('');
                $(this).hide();
            });
        });
    </script>
    <?php
});

// Save the video field
add_action('woocommerce_process_product_meta', function ($post_id) {
    if (isset($_POST['product_video'])) {
        update_post_meta($post_id, '_product_video', esc_url_raw($_POST['product_video']));
    }
});

add_action('woocommerce_order_status_processing', 'send_admin_processing_email', 10, 1);

function send_admin_processing_email($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    $mailer = WC()->mailer();
    $mails = $mailer->get_emails();

    if (!empty($mails['WC_Email_New_Order'])) {
        $mails['WC_Email_New_Order']->trigger($order_id);
    }
}

