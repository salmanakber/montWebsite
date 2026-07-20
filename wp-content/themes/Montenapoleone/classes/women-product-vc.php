<?php
/**
 * Women product page
 *
 * The Product_Helper Class.
 *
 * @class    Women product page
 * @category Class
 * @author   SixerWeb
 */
class WomenPage
{
    public function __construct()
    {
		add_filter('woocommerce_add_cart_item_data', array($this,  'cartItem') , 10, 3);

        // Display custom variation in cart
        add_filter('woocommerce_get_item_data', array($this, 'displayCustomVariationInCart'), 10, 2);

        // Display custom variation in order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'displayCustomVariationInOrder'), 10, 4);

        // Show variation in order page admin
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'adminOrder'), 10, 1);

        // Display custom color in cart
        add_filter('woocommerce_cart_item_name', array($this, 'displayCustomColorInCart'), 10, 3);

        // Save custom variation data as order item meta
        add_action('woocommerce_add_order_item_meta', array($this, 'saveCustomVariationToOrder'), 10 , 2);

        // Register product_color attribute and handle it during add to cart
        add_action('init', array($this, 'registerProductColorAttribute'));
        add_action('woocommerce_add_cart_item_data', array($this, 'handleProductColorDuringAddToCart'), 10, 2);
    }

    /**
     * Register product_color attribute
     */
    public function registerProductColorAttribute()
    {
        register_taxonomy(
            'pa_product_color',
            'product',
            array(
                'label' => __('Product Color', 'SixerWeb'),
                'public' => false,
                'hierarchical' => false,
            )
        );
    }
	
	function cartItem($cart_item_data, $product_id, $variation_id)
	{
    if (isset($_POST['custom_color']) && $_POST['custom_color'] !== 0) {
        $cart_item_data['product_color'] = sanitize_text_field($_POST['custom_color']);
    }

    return $cart_item_data;
	}

    /**
     * Handle product_color during add to cart
     *
     * @param array $cart_item_data
     * @param int $product_id
     * @return array
     */
    public function handleProductColorDuringAddToCart($cart_item_data, $product_id)
    {
        // Check if the product has the 'pa_product_color' attribute
        if (has_term('', 'pa_product_color', $product_id) && isset($_POST['product_color'])) {
            // Set product_color based on user selection or any logic
            $cart_item_data['product_color'] = sanitize_text_field($_POST['product_color']); // Adjust based on your setup
        }

        return $cart_item_data;
    }

    /**
     * Display custom variation in cart
     *
     * @param array $cart_data
     * @param array $cart_item
     * @return array
     */
    public function displayCustomVariationInCart($cart_data, $cart_item)
    {
        if (!empty($cart_item['product_color'])) {
            $decode = json_decode(stripslashes($cart_item['product_color']), true);
            $cart_data[] = array(
                'name'    => __('Fabric Color', 'SixerWeb'),
                'value'   => $decode['name'],
                'display' => '',
            );
        }

        return $cart_data;
    }

    /**
     * Display custom variation in order
     *
     * @param object $item
     * @param string $cart_item_key
     * @param array $values
     * @param object $order
     */
    public function displayCustomVariationInOrder($item, $cart_item_key, $values, $order)
    {
        $product_color = $item->get_meta('product_color');
        if ($product_color) {
            $decode = json_decode(stripslashes($product_color), true);
            $item->add_meta_data(__('Fabric Color', 'SixerWeb'), $decode['name'], true);
            $item->add_meta_data(__('Fabric image', 'SixerWeb'), $decode['image'], true);
        }
    }

    /**
     * Save custom variation data as order item meta
     *
     * @param int $order_id
     */
public function saveCustomVariationToOrder($item_id , $value)
{
   if(isset($value['product_color']) AND $value['product_color'] !== 0 AND !empty($value['product_color']))
   {
	
	$decode = json_decode(stripslashes($value['product_color']), true);
	wc_add_order_item_meta( $item_id, 'Fabric Color', $decode['name']);
	wc_add_order_item_meta( $item_id, 'Fabric image', '<img src="'.$decode['image'].'" width="70" height="70" />');
   }
}
	

    /**
     * Display custom color in cart
     *
     * @param string $product_name
     * @param array $cart_item
     * @param string $cart_item_key
     * @return string
     */
    public function displayCustomColorInCart($product_name, $cart_item, $cart_item_key)
    {
        if (!empty($cart_item['product_color'])) {
            $decode = json_decode(stripslashes($cart_item['product_color']), true);
            $product_name .= '<br><strong>' . __('Fabric Color:', 'SixerWeb') . '</strong> ' . $decode['name'];
        }
        return $product_name;
    }

    /**
     * Show custom variation in order page admin
     *
     * @param WC_Order $order
     */
    public function adminOrder($order)
    {
	
        foreach ($order->get_items() as $item_id => $item) {
            $product_color = wc_get_order_item_meta($item_id, 'product_color', true);
            if ($product_color) {
                $decoded_data = json_decode(stripslashes($product_color), true);

                if (isset($decoded_data['name'])) {
                    echo '<p><strong>' . __('Fabric Color:', 'SixerWeb') . '</strong> ' . esc_html($decoded_data['name']) . '</p>';
                }

                if (isset($decoded_data['image'])) {
                    echo '<p><strong>' . __('Fabric Image:', 'SixerWeb') . '</strong> ' . esc_url($decoded_data['image']) . '</p>';
                }
            }
        }
    }
}

// Initialize the WomenPage class
new WomenPage();
