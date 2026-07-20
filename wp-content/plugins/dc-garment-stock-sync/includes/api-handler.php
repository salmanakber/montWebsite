<?php
if (!defined('ABSPATH')) {
    exit;
}

class DC_Garment_API_Handler {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

public function register_rest_routes() {
    register_rest_route('dc-garment/v1', '/update-stock/', [
        'methods' => 'POST',
        'callback' => [$this, 'update_stock'],
        'permission_callback' => '__return_true', // No authentication required
    ]);


}



    public function update_stock($request) {
        $params = $request->get_json_params();
        $product_id = $params['product_id'] ?? 0;
        $stock_quantity = $params['stock_quantity'] ?? null;


        if (!$product_id || $stock_quantity === null) {
            return new WP_Error('missing_data', 'Product ID and stock quantity are required.', ['status' => 400]);
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Invalid product ID.', ['status' => 404]);
        }

        $product->set_stock_quantity($stock_quantity);
        $product->save();

        return rest_ensure_response(['success' => true, 'message' => 'Stock updated successfully']);
    }
}

new DC_Garment_API_Handler();
