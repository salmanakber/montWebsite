<?php
if (!defined('ABSPATH')) {
    exit;
}

class DC_Garment_Stock_Sync {
    private $api_url;

    public function __construct() {
        $this->api_url = get_option('dc_garment_api_url', 'https://dc-garment.com/staff/api/update-stock.php');
        add_action('woocommerce_order_status_completed', [$this, 'reduce_stock_on_dc_garment'], 10, 1);
    }

public function reduce_stock_on_dc_garment($order_id) {
    print_r($order_id);
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("Stock Sync: Order not found (Order ID: $order_id)");
        return;
    }

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();

        if ($product_id && $quantity) {
            error_log("Stock Sync: Sending update for Product ID: $product_id, Quantity: $quantity");
            $this->send_stock_update($product_id, $quantity);
        } else {
            error_log("Stock Sync: Missing product ID or quantity for Order ID: $order_id");
        }
    }
}


    private function send_stock_update($product_id, $quantity) {
        wp_remote_post($this->api_url, [
            'body'    => json_encode(['product_id' => $product_id, 'quantity' => $quantity]),
            'headers' => ['Content-Type' => 'application/json'],
            'method'  => 'POST',
        ]);
    }
 
}
