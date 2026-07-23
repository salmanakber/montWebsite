<?php
class Custom_Add_To_Cart {

    /** Readable labels for custom measurement keys. */
    private static $size_labels = array(
        'shirt_length'        => 'Skjortelengde',
        'sleeve_length_left'  => 'Ermelengde (Venstre)',
        'sleeve_length_right' => 'Ermelengde (Høyre)',
        'waist'               => 'Midje',
        'chest'               => 'Bryststørrelse',
        'half_bottom'         => 'Nederst kant',
        'shoulder'            => 'Skulder',
    );

    public function __construct() {
        add_action('wp_ajax_custom_add_to_cart', array($this, 'custom_add_to_cart'));
        add_action('wp_ajax_nopriv_custom_add_to_cart', array($this, 'custom_add_to_cart'));

        add_filter('woocommerce_get_item_data', array($this, 'custom_display_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'custom_save_order_item_meta'), 10, 4);
        add_filter('woocommerce_order_item_get_formatted_meta_data', array($this, 'format_order_item_meta'), 10, 2);

        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_price_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'adjust_custom_price_from_session'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'display_correct_price_in_cart'), 10, 3);
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_custom_price_to_cart'), 20, 1);
    }

    public function add_custom_price_to_cart_item($cart_item_data, $product_id, $variation_id) {
        if (!empty($_POST['added_price'])) {
            $custom_price = floatval(sanitize_text_field(wp_unslash($_POST['added_price'])));
            if (!isset($cart_item_data['custom_data'])) {
                $cart_item_data['custom_data'] = array();
            }
            $cart_item_data['custom_data']['custom_price'] = $custom_price;
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }
        return $cart_item_data;
    }

    public function adjust_custom_price_from_session($cart_item, $values) {
        if (!empty($values['custom_data'])) {
            $cart_item['custom_data'] = $values['custom_data'];
        }
        return $cart_item;
    }

    /**
     * Apply custom surcharge once during cart totals (avoids double-adding in price display).
     */
    public function apply_custom_price_to_cart($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        if (!$cart) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (empty($cart_item['custom_data']['custom_price']) || empty($cart_item['data'])) {
                continue;
            }
            if (!empty($cart_item['data']->dc_custom_price_applied)) {
                continue;
            }
            $base = floatval($cart_item['data']->get_price());
            $extra = floatval($cart_item['custom_data']['custom_price']);
            $cart_item['data']->set_price($base + $extra);
            $cart_item['data']->dc_custom_price_applied = true;
        }
    }

    public function custom_add_to_cart() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$product_id) {
            wp_send_json_error(array('message' => 'Invalid product ID.'));
        }

        $custom_data = $this->custom_get_custom_fields($_POST);
        $custom_price = isset($_POST['added_price']) ? floatval($_POST['added_price']) : 0;
        if ($custom_price > 0) {
            $custom_data['custom_price'] = $custom_price;
        }

        $cart_item_data = array(
            'custom_data' => $custom_data,
            'unique_key'  => md5(wp_json_encode($custom_data) . microtime()),
        );

        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        if (!$cart_item_key) {
            wp_send_json_error(array('message' => 'Could not add product to cart.'));
        }

        wp_send_json_success(array(
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_key'   => $cart_item_key,
        ));
    }

    /**
     * Build clean custom_data from POST for cart + order.
     */
    private function custom_get_custom_fields($data) {
        $custom_data = array();

        $map = array(
            'body_fit'    => 'Passform',
            'size'        => 'Størrelse',
            'collar_type' => 'Snipp (Collar)',
            'cuff_type'   => 'Mansjetter (Cuff)',
            // legacy alias
            'cup_type'    => 'Mansjetter (Cuff)',
        );

        foreach ($map as $post_key => $label) {
            if (empty($data[$post_key])) {
                continue;
            }
            $value = sanitize_text_field(wp_unslash($data[$post_key]));
            if ($value === '') {
                continue;
            }
            // Prefer cuff_type over cup_type if both exist.
            if ($post_key === 'cup_type' && !empty($data['cuff_type'])) {
                continue;
            }
            $custom_data[$label] = $value;
        }

        // Measurements from form_data object (preferred).
        if (!empty($data['form_data']) && is_array($data['form_data'])) {
            foreach ($data['form_data'] as $key => $value) {
                $label = $this->normalize_size_label($key);
                $value = sanitize_text_field(wp_unslash($value));
                if ($value === '' || $value === '0') {
                    continue;
                }
                // Append unit if numeric.
                if (is_numeric($value)) {
                    $value = $value . ' cm';
                }
                $custom_data[$label] = $value;
            }
        }

        // Also accept mont_sizes[...] posted directly.
        if (!empty($data['mont_sizes']) && is_array($data['mont_sizes'])) {
            foreach ($data['mont_sizes'] as $key => $value) {
                $label = $this->normalize_size_label($key);
                $value = sanitize_text_field(wp_unslash($value));
                if ($value === '' || $value === '0') {
                    continue;
                }
                if (is_numeric($value)) {
                    $value = $value . ' cm';
                }
                $custom_data[$label] = $value;
            }
        }

        return $custom_data;
    }

    private function normalize_size_label($key) {
        $key = sanitize_text_field($key);
        $key = preg_replace('/^mont_sizes\[/i', '', $key);
        $key = preg_replace('/^Mont Sizes\[/i', '', $key);
        $key = rtrim($key, ']:');
        $key = strtolower(str_replace(array(' ', '-'), '_', $key));

        if (isset(self::$size_labels[$key])) {
            return self::$size_labels[$key];
        }

        // Title-case fallback.
        return ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Show options on cart & checkout.
     */
    public function custom_display_cart_item_data($cart_data, $cart_item) {
        if (empty($cart_item['custom_data']) || !is_array($cart_item['custom_data'])) {
            return $cart_data;
        }

        foreach ($cart_item['custom_data'] as $key => $value) {
            if ($key === 'custom_price' || $value === '' || $value === null) {
                continue;
            }
            $cart_data[] = array(
                'key'   => $key,
                'name'  => $key,
                'value' => $value,
                'display' => '',
            );
        }

        return $cart_data;
    }

    /**
     * Persist the same labels/values onto the order line item for admin.
     */
    public function custom_save_order_item_meta($item, $cart_item_key, $values, $order = null) {
        if (empty($values['custom_data']) || !is_array($values['custom_data'])) {
            return;
        }

        foreach ($values['custom_data'] as $key => $value) {
            if ($key === 'custom_price' || $value === '' || $value === null) {
                continue;
            }
            $item->add_meta_data($key, $value, true);
        }
    }

    /**
     * Keep order meta readable in admin (hide internal keys if any).
     */
    public function format_order_item_meta($formatted_meta, $item) {
        foreach ($formatted_meta as $meta_id => $meta) {
            if (isset($meta->key) && $meta->key === 'custom_price') {
                unset($formatted_meta[$meta_id]);
            }
        }
        return $formatted_meta;
    }

    public function display_correct_price_in_cart($price, $cart_item, $cart_item_key) {
        // Price already adjusted in apply_custom_price_to_cart — show product price as-is.
        return $price;
    }
}

new Custom_Add_To_Cart();
