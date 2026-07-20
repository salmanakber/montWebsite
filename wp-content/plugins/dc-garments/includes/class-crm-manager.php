<?php
/**
 * CRM Manager Class
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class CRM_Manager {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
       add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_ab'));
        add_action('wp_ajax_dc_crm_search', array($this, 'ajax_search'));
        add_action('wp_ajax_dc_crm_save_item', array($this, 'ajax_save_item'));
        add_action('wp_ajax_dc_crm_delete_item', array($this, 'ajax_delete_item'));
    }


    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts_ab($hook) {
        if ('toplevel_page_dc-product-manager' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'dc-crm-style',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/crm.css',
            array(),
            DC_PRODUCT_MANAGER_VERSION
        );

        wp_enqueue_script(
            'dc-crm-script',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/crm.js',
            array('jquery', 'jquery-ui-datepicker'),
            DC_PRODUCT_MANAGER_VERSION,
            true
        );

		

		
    }

    /**
     * AJAX handler for search functionality.
     */
    public function ajax_search() {
        check_ajax_referer('dc-product-management-nonce', 'nonce');

        if (!current_user_can('dc_access_crm')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'dc-product-manager')));
        }

        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'dashboard';
        
        $results = $this->perform_search($query, $tab);
        wp_send_json_success(array('content' => $results));
    }

    /**
     * AJAX handler for saving items.
     */
    public function ajax_save_item() {
        check_ajax_referer('dc_crm_nonce', 'nonce');

        if (!current_user_can('dc_access_crm')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'dc-product-manager')));
        }

        $item_data = $this->sanitize_item_data($_POST);
        $result = $this->save_item($item_data);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Item saved successfully.', 'dc-product-manager'),
            'redirect' => $result
        ));
    }

    /**
     * AJAX handler for deleting items.
     */
    public function ajax_delete_item() {
        check_ajax_referer('dc_crm_nonce', 'nonce');

        if (!current_user_can('dc_access_crm')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'dc-product-manager')));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $result = $this->delete_item($id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Item deleted successfully.', 'dc-product-manager')));
    }

    /**
     * Get dashboard tab content.
     */
    private function get_dashboard_tab() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Get customers tab content.
     */
    private function get_customers_tab() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/customers.php';
        return ob_get_clean();
    }

    /**
     * Get orders tab content.
     */
    private function get_orders_tab() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/orders.php';
        return ob_get_clean();
    }

    /**
     * Get products tab content.
     */
    private function get_products_tab() {
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/product-management.php';
        return ob_get_clean();
    }

    /**
     * Perform search based on query and tab.
     */
    private function perform_search($query, $tab) {
        // Implement search logic based on tab
        switch ($tab) {
            case 'customers':
                return $this->search_customers($query);
            case 'orders':
                return $this->search_orders($query);
            case 'products':
                return $this->search_products($query);
            default:
                return '';
        }
    }

    /**
     * Sanitize item data before saving.
     */
    private function sanitize_item_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_item_data($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }

        return $sanitized;
    }

    /**
     * Save item to database.
     */
    private function save_item($data) {
        // Implement save logic based on item type
        // Return WP_Error on failure or redirect URL on success
        return '';
    }

    /**
     * Delete item from database.
     */
    private function delete_item($id) {
        // Implement delete logic
        // Return WP_Error on failure or true on success
        return true;
    }
}

// Initialize the CRM Manager
CRM_Manager::get_instance(); 