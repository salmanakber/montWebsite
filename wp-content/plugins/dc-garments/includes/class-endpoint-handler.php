<?php
/**
 * The class responsible for handling custom endpoints and redirects.
 *
 * @since      1.0.0
 * @package    DC_Product_Manager
 * @subpackage DC_Product_Manager/includes
 */

namespace DC_Product_Manager;

class Endpoint_Handler {

    /**
     * The supplier manager instance.
     *
     * @var DC_Supplier_Manager
     */
    public $supplier_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Initialize the supplier manager
        $this->supplier_manager = new DC_Supplier_Manager();
        $this->supplier_manager->init();
    }

    /**
     * Initialize hooks
     */
    public function init() {
        // Add custom endpoints
        add_action('init', array($this, 'add_custom_endpoints'));
        
        // Handle template redirect
        add_action('template_redirect', array($this, 'handle_template_redirect'));
        
        // Handle login redirect
        add_filter('login_redirect', array($this, 'redirect_after_login'), 10, 3);
        
        // Add query vars
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Enqueue CRM styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_crm_styles'));
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'dc_crm';
        return $vars;
    }

    /**
     * Add custom endpoints for the CRM.
     *
     * @since    1.0.0
     */
    public function add_custom_endpoints() {
        // Add rewrite rule for CRM endpoint
        add_rewrite_rule(
            '^crm/?$',
            'index.php?dc_crm=1',
            'top'
        );
        
        // Add rewrite rule for product edit endpoint
        add_rewrite_rule(
            '^crm/product/([0-9]+)/edit/?$',
            'index.php?dc_product_edit=$matches[1]',
            'top'
        );
        
        // Add rewrite tags
        add_rewrite_tag('%dc_crm%', '([^&]+)');
        add_rewrite_tag('%dc_product_edit%', '([0-9]+)');
        
        // Flush rewrite rules only once
        if (!get_option('dc_crm_rewrite_rules_flushed')) {
            flush_rewrite_rules();
            update_option('dc_crm_rewrite_rules_flushed', true);
        }
    }

    /**
     * Handle template redirect for custom endpoints.
     *
     * @since    1.0.0
     */
    public function handle_template_redirect() {
        // Check if we're on the CRM endpoint
        if (get_query_var('dc_crm')) {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/crm/')));
                exit;
            }
            
            // Check if user has permission
            $user = wp_get_current_user();
            if (!in_array('administrator', $user->roles) && !in_array('shop_manager', $user->roles) && !in_array('dc_staff', $user->roles)) {
                wp_redirect(home_url());
                exit;
            }
            
            // Set the template to our CRM template
             include DC_PM_PLUGIN_DIR . 'public/partials/dc-database.php';
            exit;
        }
        
        // Check if we're on the product edit endpoint
        $product_id = get_query_var('dc_product_edit');
        if ($product_id) {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/crm/product/' . $product_id . '/edit/')));
                exit;
            }
            
            // Check if user has permission
            $user = wp_get_current_user();
            if (!in_array('administrator', $user->roles) && !in_array('shop_manager', $user->roles) && !in_array('dc_staff', $user->roles)) {
                wp_redirect(home_url());
                exit;
            }
            
            // Check if product exists
            $product = get_post($product_id);
            if (!$product || $product->post_type !== 'product') {
                wp_redirect(home_url('/crm/'));
                exit;
            }
            
            // Get WooCommerce product
            $wc_product = wc_get_product($product_id);
            if (!$wc_product) {
                wp_redirect(home_url('/crm/'));
                exit;
            }
            
             global $product_data;
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
            $product_data['category_name'] = '';
            if (!is_wp_error($category) && $category) {
                $product_data['category_name'] = $category->name;
            }
            
            
            // Generate title string
            $product_data['generated_title'] = $this->generate_title_string(
                $product_data['fabric_color'],
                $product_data['category_name'],
                $product_data['fabric_no']
            );
            
            
            // Set the template to our product edit template
            include DC_PM_PLUGIN_DIR . 'public/templates/product-edit-template.php';
            exit;
        }
    }

    /**
     * Redirect users after login based on their role.
     *
     * @since    1.0.0
     * @param    string    $redirect_to    The redirect destination URL.
     * @param    string    $requested_redirect_to    The requested redirect destination URL passed as a parameter.
     * @param    WP_User    $user    The WP_User object.
     * @return   string    The redirect destination URL.
     */
    public function redirect_after_login($redirect_to, $requested_redirect_to, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            // Check if user has appropriate role for CRM access
            if (in_array('dc_staff', $user->roles)) {
                return home_url('/crm/');
            }
        }
        return $redirect_to;
    }
    /**
     * Enqueue CRM styles and scripts
     */
    public function enqueue_crm_styles() {
        if (get_query_var('dc_crm')) {
            // Enqueue CRM styles
      wp_enqueue_style(
                'dc-crm-styles',
                DC_PM_PLUGIN_URL . 'public/css/dc-crm.css',
                array('wp-admin'),
                DC_PRODUCT_MANAGER_VERSION
            );

   
            
                        wp_enqueue_style(
                'dc-crm-styles-admin',
                DC_PM_PLUGIN_URL . 'public/css/admin.css',
                array('wp-admin'),
                DC_PRODUCT_MANAGER_VERSION
            );
            
                      wp_enqueue_style(
                'dc-crm-styles-crm',
                DC_PM_PLUGIN_URL . 'assets/css/crm.css',
                array('wp-admin'),
                DC_PRODUCT_MANAGER_VERSION
            );
            
                      wp_enqueue_style(
                'dc-crm-styles-product-management',
                DC_PM_PLUGIN_URL . 'assets/css/product-management.css',
                array('wp-admin'),
                DC_PRODUCT_MANAGER_VERSION
            );

            wp_enqueue_style(
                'dc-region-switcher',
                DC_PM_PLUGIN_URL . 'assets/css/region-switcher.css',
                array(),
                DC_PRODUCT_MANAGER_VERSION
            );

            wp_enqueue_style(
                'dc-order-portal',
                DC_PM_PLUGIN_URL . 'assets/css/order-portal.css',
                array(),
                DC_PRODUCT_MANAGER_VERSION
            );
            
                      wp_enqueue_style(
                'dc-crm-styles-supplier-management',
                DC_PM_PLUGIN_URL . 'assets/css/supplier-management.css',
                array('wp-admin'),
                DC_PRODUCT_MANAGER_VERSION
            );
            
       
            // Load CRM scripts
            wp_enqueue_script(
                'dc-crm-scripts',
                DC_PM_PLUGIN_URL . 'public/js/dc-crm.js',
                array('jquery'),
                DC_PRODUCT_MANAGER_VERSION,
                true
            );
            
                wp_enqueue_script(
                'dc-crm-scripts-admin',
                DC_PM_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                DC_PRODUCT_MANAGER_VERSION,
                true
            );
            
                        wp_enqueue_script(
                'dc-crm-scripts-crm',
                DC_PM_PLUGIN_URL . 'assets/js/crm.js',
                array('jquery'),
                DC_PRODUCT_MANAGER_VERSION,
                true
            );
            
                        wp_enqueue_script(
                'dc-product-management',
                DC_PM_PLUGIN_URL . 'assets/js/product-management.js',
                array('jquery'),
                DC_PRODUCT_MANAGER_VERSION,
                true
            );
            
                 wp_enqueue_script(
                'dc-crm-scripts-supplier-management',
                DC_PM_PLUGIN_URL . 'assets/js/supplier-management.js',
                array('jquery'),
                DC_PRODUCT_MANAGER_VERSION,
                true
            );
			
			        $supplier_manager_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dc_supplier_manager_nonce'),
            'i18n' => array(
                'error' => __('Error', 'dc-product-management'),
                'success' => __('Success', 'dc-product-management'),
                'confirm_delete' => __('Are you sure you want to delete this supplier?', 'dc-product-management'),
                'supplier_deleted' => __('Supplier deleted successfully', 'dc-product-management'),
                'supplier_saved' => __('Supplier saved successfully', 'dc-product-management'),
                'server_error' => __('Error connecting to the server', 'dc-product-management')
            )
        );

        // Add the dc_supplier_manager object directly in the head
        wp_add_inline_script('dc-crm-scripts-supplier-management', 'var dc_supplier_manager = ' . wp_json_encode($supplier_manager_data) . ';', 'before');
			
		
			
							wp_localize_script('dc-crm-scripts-crm', 'dc_crm', array(
	            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
    'nonce' => wp_create_nonce('dc_crm_nonce'),
    'i18n' => array(
        'confirmDelete' => __('Are you sure you want to delete this item?', 'dc-product-manager'),
        'loadError' => __('Error loading content. Please try again.', 'dc-product-manager'),
        'searchError' => __('Error performing search. Please try again.', 'dc-product-manager'),
        'saveError' => __('Error saving item. Please try again.', 'dc-product-manager'),
        'deleteError' => __('Error deleting item. Please try again.', 'dc-product-manager')
    )
));

            // Localize script
            wp_localize_script('dc-product-management', 'dc_product_manager', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'adminUrl' => admin_url(),
                'siteUrl' => home_url('/'),
                'nonce' => wp_create_nonce('dc-product-management-nonce'),
                'defaultCurrency' => 'NOK',
                'currencies' => array('USD', 'EUR', 'NOK', 'VND'),
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
                    'lowStock' => __('Low Stock Alert', 'dc-product-manager'),
                    'lowStockMessage' => __('Some products have low stock', 'dc-product-manager'),
                    'requiredFields' => __('Please fill in at least one field to update', 'dc-product-manager'),
                    'productUpdated' => __('Product updated successfully', 'dc-product-manager'),
                )
            ));
			
							wp_localize_script('dc-crm-script', 'dc_crm', array(
	            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url(),
    'nonce' => wp_create_nonce('dc_crm_nonce'),
    'i18n' => array(
        'confirmDelete' => __('Are you sure you want to delete this item?', 'dc-product-manager'),
        'loadError' => __('Error loading content. Please try again.', 'dc-product-manager'),
        'searchError' => __('Error performing search. Please try again.', 'dc-product-manager'),
        'saveError' => __('Error saving item. Please try again.', 'dc-product-manager'),
        'deleteError' => __('Error deleting item. Please try again.', 'dc-product-manager')
    )
));

            // Add body class for CRM page
            add_filter('body_class', function($classes) {
                $classes[] = 'dc-crm-page';
                return $classes;
            });
        }
    }
    private function generate_title_string($fabric_color, $category_name, $fabric_no) {
        $title_parts = array();
        
        if (!empty($fabric_color)) {
            $title_parts[] = $fabric_color;
        }
        
        if (!empty($category_name)) {
            $title_parts[] = $category_name;
        }
        
        if (!empty($fabric_no)) {
            $title_parts[] = $fabric_no;
        }
        
        return implode(' ', $title_parts);
    }
} 