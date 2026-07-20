<?php
class Custom_Add_To_Cart {

    public function __construct() {
        add_action('wp_ajax_custom_add_to_cart', [$this, 'custom_add_to_cart']);
        add_action('wp_ajax_nopriv_custom_add_to_cart', [$this, 'custom_add_to_cart']);
        
        add_filter('woocommerce_get_item_data', [$this, 'custom_display_cart_item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'custom_save_order_item_meta'], 10, 3);

        // Add additional hook to handle custom price adjustment
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_custom_price_to_cart_item'], 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', [$this, 'adjust_custom_price_from_session'], 10, 2);
        add_filter('woocommerce_cart_item_price', [$this, 'display_correct_price_in_cart'], 10, 3);

    }

    // Add custom price to the cart item data
public function add_custom_price_to_cart_item($cart_item_data, $product_id, $variation_id) {
    if (!empty($_POST['added_price'])) {
        $custom_price = floatval(sanitize_text_field($_POST['added_price']));

        // Ensure custom_data array exists
        if (!isset($cart_item_data['custom_data'])) {
            $cart_item_data['custom_data'] = [];
        }

        // Add custom price to the cart item data
        $cart_item_data['custom_data']['custom_price'] = $custom_price;
    }

    return $cart_item_data;
}


    // Adjust the cart item's price based on custom price data stored in session
public function adjust_custom_price_from_session($cart_item, $values) {
    if (isset($values['custom_data']['custom_price'])) {
        $custom_price = floatval($values['custom_data']['custom_price']);

        // Get the original product price as float
        $product_price = floatval($cart_item['data']->get_price());

        // Set new price as original price + custom price
        $cart_item['data']->set_price($product_price + $custom_price);
    }

    return $cart_item;
}



    // Custom function to add a product to the cart with custom data
public function custom_add_to_cart() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $custom_price = isset($_POST['added_price']) ? floatval($_POST['added_price']) : 0;

    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
    }

    // Get custom data
    $custom_data = $this->custom_get_custom_fields($_POST);

    // Add product to cart with custom data
    $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, [], ['custom_data' => $custom_data]);

   
    

        wp_send_json_success(['cart_count' => WC()->cart->get_cart_contents_count()]);
 

    wp_die();
}


    // Helper function to gather custom data from the POST request
    private function custom_get_custom_fields($data) {
        $custom_data = [
            'Body Fit' => sanitize_text_field($data['body_fit'] ?? ''),
            'Size' => sanitize_text_field($data['size'] ?? ''),
            'Collar Type' => sanitize_text_field($data['collar_type'] ?? ''),
            'Cup Type' => sanitize_text_field($data['cup_type'] ?? ''),
        ];

        if (!empty($data['form_data']) && is_array($data['form_data'])) {
            foreach ($data['form_data'] as $key => $value) {
                $custom_data[sanitize_text_field($key)] = sanitize_text_field($value);
            }
        }

        return $custom_data;
    }

    // Display custom data on the cart page
    public function custom_display_cart_item_data($cart_data, $cart_item) {
        if (!empty($cart_item['custom_data'])) {
            foreach ($cart_item['custom_data'] as $key => $value) {
                // Remove unwanted prefix (if any) from key
                //$clean_key = preg_replace('/^Mont Sizes\[/', '', $key);
                //$clean_key = rtrim($clean_key, ':'); // Remove any trailing colon

                // $cart_data[] = [
                //     //'name'  => esc_html($clean_key),
                //   // 'value' => esc_html($value),
                // ];
            }
        }
        // return $cart_data;
    }

    // Save custom order item meta to the order
    public function custom_save_order_item_meta($item, $cart_item_key, $values) {
        if (!empty($values['custom_data'])) {
            foreach ($values['custom_data'] as $key => $value) {
                // Clean up the key for better readability
                $clean_key = preg_replace('/^Mont Sizes\[/', '', $key);
                $clean_key = rtrim($clean_key, ':');

                // Add the custom metadata to the order item
                $item->add_meta_data(esc_html($clean_key), esc_html($value), true);
            }
        }
    }
    
    
    public function display_correct_price_in_cart($price, $cart_item, $cart_item_key) {
    if (isset($cart_item['custom_data']['custom_price'])) {
        $custom_price = floatval($cart_item['custom_data']['custom_price']);
        $product_price = floatval($cart_item['data']->get_price());

        // Correct total price
        $total_price = $product_price + $custom_price;

        // Return formatted price with currency symbol
        return wc_price($total_price);
    }
    return $price;
}

}

// Initialize the class
new Custom_Add_To_Cart();
?>
