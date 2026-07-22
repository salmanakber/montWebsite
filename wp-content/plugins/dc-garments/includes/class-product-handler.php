<?php
namespace DC_Product_Manager;

class Product_Handler {
    public function init() {
        // Register custom product management page
        add_action('admin_menu', array($this, 'add_product_management_page'));
        
        // Register AJAX handlers for product operations
        add_action('wp_ajax_dc_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_dc_get_product', array($this, 'ajax_get_product'));
        add_action('wp_ajax_dc_update_product', array($this, 'ajax_update_product'));
        add_action('wp_ajax_dc_bulk_update_products', array($this, 'ajax_bulk_update_products'));
		 add_action('wp_ajax_dc_bulk_delete_products', array($this, 'ajax_bulk_delete_products'));
        
        // Register custom product management scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_product_management_assets'));
        
        // Add rewrite rules for product edit page
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Handle product edit page
        add_action('template_redirect', array($this, 'handle_product_edit_page'));
    }
    
    public function add_product_management_page() {
add_menu_page(
    __('Stock Management', 'Stock-Management'),
    __('Stock Management', 'Stock-Management'),
    'manage_options',
    'Stock-Management',
    array($this, 'dc_product_manager_redirect'),
    'dashicons-cart',
    1
);
    }
public function dc_product_manager_redirect() {
    wp_redirect(home_url().'/crm');
    exit;
}


    
    public function add_rewrite_rules() {
        add_rewrite_rule(
            'dc-product-management/edit/([0-9]+)/?$',
            'index.php?dc_product_edit=$matches[1]',
            'top'
        );
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'dc_product_edit';
        return $vars;
    }
    
    public function handle_product_edit_page() {
        $product_id = get_query_var('dc_product_edit');
        
        if ($product_id) {
            // Check if user has permission
            if (!current_user_can('edit_products')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'dc-product-manager'));
            }
            
            // Check if product exists
            $product = get_post($product_id);
            if (!$product || $product->post_type !== 'product') {
                wp_die(__('Product not found.', 'dc-product-manager'));
            }
            
            // Render product edit page
            $this->render_product_edit_page($product_id);
            exit;
        }
    }
    
    public function render_product_edit_page($product_id) {
        // Include the product edit template
        include DC_PM_PLUGIN_DIR . 'admin/partials/product-edit.php';
    }
    
    public function enqueue_product_management_assets($hook) {
        // Only load on our custom product management pages
        if ($hook !== 'toplevel_page_dc-product-management') {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'dc-product-management',
            DC_PM_PLUGIN_URL . 'assets/css/product-management.css',
            array(),
            DC_PM_VERSION
        );
        
        // Enqueue test styles
        wp_enqueue_style(
            'dc-test',
            DC_PM_PLUGIN_URL . 'assets/css/test.css',
            array(),
            DC_PM_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'dc-product-management',
            DC_PM_PLUGIN_URL . 'assets/js/product-management.js',
            array('jquery'),
            DC_PM_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('dc-product-management', 'dc_product_manager', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
            'nonce' => wp_create_nonce('dc-product-management-nonce'),
            'i18n' => array(
                'loading' => __('Loading...', 'dc-product-manager'),
                'saving' => __('Saving...', 'dc-product-manager'),
                'saved' => __('Saved!', 'dc-product-manager'),
                'error' => __('Error occurred', 'dc-product-manager'),
                'success' => __('Success', 'dc-product-manager'),
                'update' => __('Update Product', 'dc-product-manager'),
                'noProducts' => __('No products found', 'dc-product-manager'),
                'noImage' => __('No image available', 'dc-product-manager'),
                'sku' => __('SKU', 'dc-product-manager'),
                'price' => __('Price', 'dc-product-manager'),
                'stock' => __('Stock', 'dc-product-manager'),
                'edit' => __('Edit', 'dc-product-manager'),
                'lowStock' => __('Low Stock Alert ', 'dc-product-manager'),
                'lowStockMessage' => __('Some products have low stock ', 'dc-product-manager'),
                'requiredFields' => __('Please fill in all required fields ', 'dc-product-manager'),
                'productUpdated' => __('Product updated successfully', 'dc-product-manager'),
                'save' => __('Update Product', 'dc-product-manager'),
                'titlePreview' => __('Title will be generated automatically', 'dc-product-manager')
            )
        ));
		

		
    }
    
    public function render_product_management_page() {
        // Check if we're on the edit page
        if (isset($_GET['dc_product_edit'])) {
            $product_id = intval($_GET['dc_product_edit']);
            
            // Verify product exists
            $product = get_post($product_id);
            if (!$product || $product->post_type !== 'product') {
                wp_die(__('Product not found.', 'dc-product-manager'));
            }
            
            // Get WooCommerce product
            $wc_product = wc_get_product($product_id);
            if (!$wc_product) {
                wp_die(__('WooCommerce product not found.', 'dc-product-manager'));
            }
            
            // Get product data
            $product_data = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'fabric_color' => get_post_meta($product->ID, '_fabric_color', true),
				 'fabric_color_english' => get_post_meta($product->ID, '_fabric_color_english', true),
                'category_id' => get_post_meta($product->ID, '_category_id', true),
                'fabric_no' => get_post_meta($product->ID, '_fabric_no', true),
                'price' => $wc_product->get_price(),
                'multicurrency_prices' => DC_Multi_Currency::get_product_edit_prices($product->ID),
                'stock' => $wc_product->get_stock_quantity(),
                'moq' => get_post_meta($product->ID, '_moq', true),
                'b2b_product' => get_post_meta($product->ID, '_b2b_product', true),
                'supplier_id' => get_post_meta($product->ID, '_supplier_id', true),
                'supplier_sku' => get_post_meta($product->ID, '_supplier_sku', true),
                'quality' => get_post_meta($product->ID, '_quality', true),
                'fabric_width' => get_post_meta($product->ID, '_fabric_width', true),
                'weight' => get_post_meta($product->ID, '_weight', true),
                'supplier_price' => get_post_meta($product->ID, '_supplier_price', true),
                'custom_title' => get_post_meta($product->ID, '_custom_title', true)
				 
            );
            
            // Get category name
            $category = get_term($product_data['category_id'], 'product_cat');
            $product_data['category_name'] = $category ? $category->name : '';
            
            // Generate title string
            $product_data['generated_title'] = $this->generate_title_string(
                $product_data['fabric_color'],
                $product_data['category_name'],
                $product_data['fabric_no']
            );
            
            // Include the product edit template
            include DC_PM_PLUGIN_DIR . 'admin/partials/product-edit.php';
            return;
        }
        
        // Include the product management template
        include DC_PM_PLUGIN_DIR . 'admin/partials/product-management.php';
    }
    
    /**
     * Generate title string from components
     *
     * @param string $fabric_color
     * @param string $category_name
     * @param string $fabric_no
     * @return string
     */
    public function generate_title_string($fabric_color, $category_name, $fabric_no) {
        if (empty($fabric_color) || empty($category_name) || empty($fabric_no)) {
            return '';
        }
        
        return sprintf('%s %s %s', $fabric_color, $category_name, $fabric_no);
    }
    
    public function ajax_get_products() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc-product-management-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('edit_products')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get products
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        $products = get_posts($args);
        $formatted_products = array();
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            if (!$wc_product) {
                continue;
            }
            
                        $categories = array();
            $terms = get_the_terms($product->ID, 'product_cat');
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = (int)$term->term_id;
                }
            }
                    
            $formatted_products[] = array(
                'id' => $product->ID,
                'title' => $product->post_title,
                'sku' => $wc_product->get_sku(),
                'price' => $wc_product->get_price(),
                'multicurrency_prices' => DC_Multi_Currency::get_product_edit_prices($product->ID),
                'stock' => $wc_product->get_stock_quantity(),
                'stock_status' => $wc_product->get_stock_status(),
			   'image' => get_post_meta( $product->ID, '_dc_product_image', true) ? : wp_get_attachment_image_url(get_post_thumbnail_id($product->ID), 'large'),
                'categories' => $categories,
				'supplier_sku' => get_post_meta( $product->ID, '_supplier_sku', true)
            );
        }
        
        wp_send_json_success($formatted_products);
    }
    
    public function ajax_get_product() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc-product-management-nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('edit_products')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get product ID
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }
        
        // Get product
        $product = get_post($product_id);
        if (!$product || $product->post_type !== 'product') {
            wp_send_json_error('Product not found');
        }
        
        $wc_product = wc_get_product($product_id);
        if (!$wc_product) {
            wp_send_json_error('Product not found');
        }
        
        // Get product data
        $product_data = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => $product->post_content,
            'sku' => $wc_product->get_sku(),
            'price' => $wc_product->get_price(),
            'stock' => $wc_product->get_stock_quantity(),
            'stock_status' => $wc_product->get_stock_status(),
            'image' => wp_get_attachment_image_url(get_post_thumbnail_id($product->ID), 'thumbnail'),
            'categories' => wp_get_post_terms($product->ID, 'product_cat', array('fields' => 'ids')),
            'supplier' => get_post_meta($product->ID, '_dc_supplier_id', true),
        );
        
        wp_send_json_success($product_data);
    }
    
    public function ajax_update_product() {
        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            // Log the incoming request data
            error_log('Product update request data: ' . print_r($_POST, true));
            
            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc-product-management-nonce')) {
                error_log('Nonce verification failed');
                wp_send_json_error('Invalid nonce');
                return;
            }
            
            // Check permissions
            if (!current_user_can('edit_products')) {
                error_log('User lacks edit_products capability');
                wp_send_json_error('Insufficient permissions');
                return;
            }
            
            // Get product data
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            if (!$product_id) {
                error_log('Invalid product ID provided');
                wp_send_json_error('Invalid product ID');
                return;
            }
            
            // Get product
            $product = get_post($product_id);
            if (!$product || $product->post_type !== 'product') {
                error_log('Product not found or invalid post type: ' . $product_id);
                wp_send_json_error('Product not found');
                return;
            }
            
            $wc_product = wc_get_product($product_id);
            if (!$wc_product) {
                error_log('WooCommerce product not found: ' . $product_id);
                wp_send_json_error('WooCommerce product not found');
                return;
            }
            
            // Update product data
            $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $fabric_color = isset($_POST['fabric_color']) ? sanitize_text_field($_POST['fabric_color']) : '';
            $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
            $fabric_no = isset($_POST['fabric_no']) ? sanitize_text_field($_POST['fabric_no']) : '';
            $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
            $multicurrency_prices = array();
            if (isset($_POST['multicurrency_prices']) && is_array($_POST['multicurrency_prices'])) {
                foreach ($_POST['multicurrency_prices'] as $code => $value) {
                    $multicurrency_prices[sanitize_text_field($code)] = $value;
                }
            }
            $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $moq = isset($_POST['moq']) ? intval($_POST['moq']) : 0;
            $b2b_product = isset($_POST['b2b_product']) ? sanitize_text_field($_POST['b2b_product']) : 'no';
            $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
            $supplier_sku = isset($_POST['supplier_sku']) ? sanitize_text_field($_POST['supplier_sku']) : '';
            $quality = isset($_POST['quality']) ? sanitize_text_field($_POST['quality']) : '';
            $fabric_width = isset($_POST['fabric_width']) ? sanitize_text_field($_POST['fabric_width']) : '';
            $weight = isset($_POST['weight']) ? sanitize_text_field($_POST['weight']) : '';
            $supplier_price = isset($_POST['supplier_price']) ? floatval($_POST['supplier_price']) : 0;
			    $productImage = isset($_POST['product_image']) ? sanitize_text_field($_POST['product_image']) : '';
			$productImageid = isset($_POST['product_imageid']) ? sanitize_text_field($_POST['product_imageid']) : '';
			$fabric_color_english = isset($_POST['fabric_color_english']) ? sanitize_text_field($_POST['fabric_color_english']) : '';
            
            
            // Update post
            $update_result = wp_update_post(array(
                'ID' => $product_id,
                'post_title' => $title,
            ));
            
            if (is_wp_error($update_result)) {
                error_log('Error updating post: ' . $update_result->get_error_message());
                wp_send_json_error('Error updating post: ' . $update_result->get_error_message());
                return;
            }
            
            // Update product meta
            update_post_meta($product_id, '_fabric_color', $fabric_color);
            update_post_meta($product_id, '_category_id', $category_id);
            update_post_meta($product_id, '_fabric_no', $fabric_no);
            update_post_meta($product_id, '_moq', $moq);
            update_post_meta($product_id, '_b2b_product', $b2b_product);
            update_post_meta($product_id, '_supplier_id', $supplier_id);
            update_post_meta($product_id, '_supplier_sku', $supplier_sku);
            update_post_meta($product_id, '_quality', $quality);
            update_post_meta($product_id, '_fabric_width', $fabric_width);
            update_post_meta($product_id, '_weight', $weight);
            update_post_meta($product_id, '_supplier_price', $supplier_price);
			update_post_meta($product_id, '_dc_product_image', $productImage);
			update_post_meta($product_id, '_fabric_color_english', $fabric_color_english);
			if(!empty($productImageid))
			{
			set_post_thumbnail($product_id, $productImageid);
			}


            // Update WooCommerce product category
            if ($category_id > 0) {
                wp_set_object_terms($product_id, array($category_id), 'product_cat');
                error_log('Updated product category to: ' . $category_id);
            }
            
            // Update WooCommerce product
            if ($wc_product->is_type('variable')) {
                error_log('Updating variable product: ' . $product_id);
                // Get all variations
                $variations = $wc_product->get_children();
                if (empty($variations)) {
                    error_log('No variations found for product: ' . $product_id);
                    wp_send_json_error('No variations found for this product');
                    return;
                }

                $effective_price = $price;
                if (!empty($multicurrency_prices['NOK'])) {
                    $effective_price = floatval($multicurrency_prices['NOK']);
                } elseif (!empty($multicurrency_prices)) {
                    $effective_price = floatval(reset($multicurrency_prices));
                }
                
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        try {
                            $variation->set_regular_price($effective_price);
                            $variation->set_price($effective_price);
                            $variation->save();
                            error_log('Updated variation: ' . $variation_id . ' with price: ' . $effective_price);
                        } catch (Exception $e) {
                            error_log('Error updating variation ' . $variation_id . ': ' . $e->getMessage());
                            throw $e;
                        }
                    }
                }

                DC_Multi_Currency::save_prices_for_product($product_id, $multicurrency_prices, $wc_product);
                
                // Update parent product price range
                try {
                    // Calculate min and max prices
                    $min_price = $effective_price;
                    $max_price = $effective_price;
                    
                    // Update price meta
                    update_post_meta($product_id, '_min_price_variation_id', $variations[0]);
                    update_post_meta($product_id, '_max_price_variation_id', $variations[0]);
                    update_post_meta($product_id, '_min_variation_price', $min_price);
                    update_post_meta($product_id, '_max_variation_price', $max_price);
                    update_post_meta($product_id, '_min_variation_regular_price', $min_price);
                    update_post_meta($product_id, '_max_variation_regular_price', $max_price);
                    
                    error_log('Updated price meta for variable product: ' . $product_id);
                } catch (Exception $e) {
                    error_log('Error updating price meta: ' . $e->getMessage());
                    throw $e;
                }
            } else {
                $effective_price = $price;
                if (!empty($multicurrency_prices['NOK'])) {
                    $effective_price = floatval($multicurrency_prices['NOK']);
                } elseif (!empty($multicurrency_prices)) {
                    $effective_price = floatval(reset($multicurrency_prices));
                }
                $wc_product->set_regular_price($effective_price);
                $wc_product->set_price($effective_price);
                DC_Multi_Currency::save_prices_for_product($product_id, $multicurrency_prices, $wc_product);
            }
            
            try {
                $wc_product->set_stock_quantity($stock);
                $wc_product->save();
                error_log('Successfully updated product: ' . $product_id);
                wp_send_json_success('Product updated successfully  ');
            } catch (Exception $e) {
                error_log('Error saving product: ' . $e->getMessage());
                wp_send_json_error('Error saving product: ' . $e->getMessage());
                return;
            }
            
        } catch (Exception $e) {
            error_log('Product update error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error('Error updating product: ' . $e->getMessage());
            return;
        }
    }
	




    
     /**
     * AJAX handler for bulk updating products
     */
public function ajax_bulk_delete_products() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc-product-management-nonce')) {
        wp_send_json_error(array('message' => __('Invalid nonce', 'dc-product-manager')));
    }

    // Check user permissions
    if (!current_user_can('delete_products')) {
        wp_send_json_error(array('message' => __('You do not have permission to delete products', 'dc-product-manager')));
    }

    // Validate product IDs
    if (!isset($_POST['product_ids']) || !is_array($_POST['product_ids']) || empty($_POST['product_ids'])) {
        wp_send_json_error(array('message' => __('No products selected', 'dc-product-manager')));
    }

    $product_ids = $_POST['product_ids'];
    $deleted_count = 0;
    $errors = array();

    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $result = wp_delete_post($product_id, true); // true for permanent deletion
            if ($result) {
                $deleted_count++;
            } else {
                $errors[] = "Failed to delete product ID: $product_id";
            }
        } else {
            $errors[] = "Invalid product ID: $product_id";
        }
    }

    if (!empty($errors)) {
        wp_send_json_error(array(
            'message' => __('Some products could not be deleted.', 'dc-product-manager'),
            'errors' => $errors
        ));
    } else {
        wp_send_json_success(array(
            'message' => sprintf(__('%d products deleted successfully.', 'dc-product-manager'), $deleted_count)
        ));
    }
}

   public function ajax_bulk_update_products() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dc-product-management-nonce')) {
            wp_send_json_error(array('message' => __('Invalid nonce', 'dc-product-manager')));
        }
        
        // Check permissions
        if (!current_user_can('edit_products')) {
            wp_send_json_error(array('message' => __('You do not have permission to edit products', 'dc-product-manager')));
        }
        
        // Get product IDs
        if (!isset($_POST['product_ids']) || !is_array($_POST['product_ids']) || empty($_POST['product_ids'])) {
            wp_send_json_error(array('message' => __('No products selected', 'dc-product-manager')));
        }
        
        $product_ids = array_map('intval', $_POST['product_ids']);
        
        // Get update data
        $update_data = array();
        
        // Price
        if (isset($_POST['price']) && $_POST['price'] !== '') {
            $update_data['price'] = floatval($_POST['price']);
        }
        
        // Stock
        if (isset($_POST['stock']) && $_POST['stock'] !== '') {
            $update_data['stock'] = intval($_POST['stock']);
        }
        
        // Supplier Price
        if (isset($_POST['supplier_price']) && $_POST['supplier_price'] !== '') {
            $update_data['supplier_price'] = floatval($_POST['supplier_price']);
        }
        
        // Quality
        if (isset($_POST['quality']) && $_POST['quality'] !== '') {
            $update_data['quality'] = sanitize_text_field($_POST['quality']);
        }
        
        // Fabric Width
        if (isset($_POST['fabric_width']) && $_POST['fabric_width'] !== '') {
            $update_data['fabric_width'] = sanitize_text_field($_POST['fabric_width']);
        }
        
        // Weight
        if (isset($_POST['weight']) && $_POST['weight'] !== '') {
            $update_data['weight'] = sanitize_text_field($_POST['weight']);
        }
        
        // If no data to update
        if (empty($update_data)) {
            wp_send_json_error(array('message' => __('No data to update', 'dc-product-manager')));
        }
        
        $updated_count = 0;
        $errors = array();
        
        // Update each product
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                $errors[] = sprintf(__('Product ID %d not found', 'dc-product-manager'), $product_id);
                continue;
            }
            
            try {
                // Handle variable products
                if ($product->is_type('variable')) {
    $variations = $product->get_children();
    foreach ($variations as $variation_id) {
        $variation = wc_get_product($variation_id);
        if ($variation) {
            if (isset($update_data['price'])) {
                $variation->set_price($update_data['price']);
                $variation->set_regular_price($update_data['price']);
            }

            if (isset($update_data['stock'])) {
                $variation->set_manage_stock(true); // <-- Important!
                $variation->set_stock_quantity($update_data['stock']);
                $variation->set_stock_status($update_data['stock'] > 0 ? 'instock' : 'outofstock');
            }

            // Meta data
            if (isset($update_data['supplier_price'])) {
                update_post_meta($variation_id, '_supplier_price', $update_data['supplier_price']);
            }

            if (isset($update_data['quality'])) {
                update_post_meta($variation_id, '_quality', $update_data['quality']);
            }

            if (isset($update_data['fabric_width'])) {
                update_post_meta($variation_id, '_fabric_width', $update_data['fabric_width']);
            }

            if (isset($update_data['weight'])) {
                update_post_meta($variation_id, '_weight', $update_data['weight']);
            }

            $variation->save();
            wc_delete_product_transients($variation_id); // Clear cache
        }
    }
}
else {
                    // Update price for simple products
                    if (isset($update_data['price'])) {
                        $product->set_price($update_data['price']);
                        $product->set_regular_price($update_data['price']);
                    }
                    
                    // Update stock for simple products
                    if (isset($update_data['stock'])) {
                        $product->set_stock_quantity($update_data['stock']);
                        $product->set_stock_status($update_data['stock'] > 0 ? 'instock' : 'outofstock');
                    }
                    
                    // Update meta data for simple products
                    if (isset($update_data['supplier_price'])) {
                        update_post_meta($product_id, '_supplier_price', $update_data['supplier_price']);
                    }
                    
                    if (isset($update_data['quality'])) {
                        update_post_meta($product_id, '_quality', $update_data['quality']);
                    }
                    
                    if (isset($update_data['fabric_width'])) {
                        update_post_meta($product_id, '_fabric_width', $update_data['fabric_width']);
                    }
                    
                    if (isset($update_data['weight'])) {
                        update_post_meta($product_id, '_weight', $update_data['weight']);
                    }
                    
                    $product->save();
                }
                
                $updated_count++;
            } catch (Exception $e) {
                $errors[] = sprintf(__('Error updating product ID %d: %s', 'dc-product-manager'), $product_id, $e->getMessage());
            }
        }
        
        // Prepare response
        $response = array(
            'updated' => $updated_count,
            'total' => count($product_ids),
            'errors' => $errors
        );
        
        if ($updated_count > 0) {
            wp_send_json_success($response);
        } else {
            wp_send_json_error(array(
                'message' => __('No products were updated', 'dc-product-manager'),
                'errors' => $errors
            ));
        }
    }
} 